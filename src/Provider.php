<?php

declare(strict_types=1);

namespace LaunchDarkly\OpenFeature;

use LaunchDarkly\LDClient;
use OpenFeature\implementation\provider\ResolutionDetailsBuilder;
use OpenFeature\implementation\provider\ResolutionError;
use OpenFeature\interfaces\common\Metadata;
use OpenFeature\interfaces\flags\EvaluationContext;
use OpenFeature\interfaces\flags\FlagValueType;
use OpenFeature\interfaces\provider\ErrorCode;
use OpenFeature\interfaces\provider\Provider as OpenFeatureProvider;
use OpenFeature\interfaces\provider\Reason;
use OpenFeature\interfaces\provider\ResolutionDetails;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * An OpenFeatureProvider which enables the use of the LaunchDarkly Server-Side
 * SDK for PHP with OpenFeature.
 */
class Provider implements OpenFeatureProvider
{
    private LDClient $client;
    private EvaluationContextConverter $contextConverter;
    private ResolutionDetailsConverter $detailsConverter;
    private LoggerInterface $logger;

    /**
     * Instantiate a new instance of this provider, backed by the provided LDClient instance.
     */
    public function __construct(LDClient $client, ?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();

        $this->client = $client;
        $this->contextConverter = new EvaluationContextConverter($this->logger);
        $this->detailsConverter = new ResolutionDetailsConverter();
    }

    public function getMetadata(): Metadata
    {
        return new ProviderMetaData();
    }

    /**
     * Sets a logger instance on the object.
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
        $this->contextConverter->setLogger($logger);
    }

    public function getHooks(): array
    {
        return [];
    }

    /**
     * Resolves the flag value for the provided flag key as a boolean
     */
    public function resolveBooleanValue(string $flagKey, bool $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->resolveValue($flagKey, FlagValueType::BOOLEAN, $defaultValue, $context);
    }

    /**
     * Resolves the flag value for the provided flag key as a string
     */
    public function resolveStringValue(string $flagKey, string $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->resolveValue($flagKey, FlagValueType::STRING, $defaultValue, $context);
    }

    /**
     * Resolves the flag value for the provided flag key as an integer
     */
    public function resolveIntegerValue(string $flagKey, int $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->resolveValue($flagKey, FlagValueType::INTEGER, $defaultValue, $context);
    }

    /**
     * Resolves the flag value for the provided flag key as a float
     */
    public function resolveFloatValue(string $flagKey, float $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->resolveValue($flagKey, FlagValueType::FLOAT, $defaultValue, $context);
    }

    /**
     * Resolves the flag value for the provided flag key as an object
     */
    public function resolveObjectValue(string $flagKey, array $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->resolveValue($flagKey, FlagValueType::OBJECT, $defaultValue, $context);
    }

    private function resolveValue(string $flagKey, string $flagValueType, mixed $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        if ($context === null) {
            $builder = new ResolutionDetailsBuilder();
            $builder->withValue($defaultValue);
            $builder->withReason(Reason::ERROR);
            $builder->withError(new ResolutionError(ErrorCode::TARGETING_KEY_MISSING()));

            return $builder->build();
        }

        $ldContext = $this->contextConverter->toLdContext($context);
        $result = $this->client->variationDetail($flagKey, $ldContext, $defaultValue);

        if ($flagValueType == FlagValueType::BOOLEAN && !is_bool($result->getValue())) {
            return $this->mismatchedTypeDetails($defaultValue);
        } elseif ($flagValueType == FlagValueType::STRING && !is_string($result->getValue())) {
            return $this->mismatchedTypeDetails($defaultValue);
        } elseif ($flagValueType == FlagValueType::INTEGER && !is_numeric($result->getValue())) {
            return $this->mismatchedTypeDetails($defaultValue);
        } elseif ($flagValueType == FlagValueType::FLOAT && !is_numeric($result->getValue())) {
            return $this->mismatchedTypeDetails($defaultValue);
        } elseif ($flagValueType == FlagValueType::OBJECT && !is_array($result->getValue())) {
            return $this->mismatchedTypeDetails($defaultValue);
        }

        return $this->detailsConverter->toResolutionDetails($result);
    }

    private function mismatchedTypeDetails(mixed $defaultValue): ResolutionDetails
    {
        $builder = new ResolutionDetailsBuilder();
        $builder->withValue($defaultValue);
        $builder->withReason(Reason::ERROR);
        $builder->withError(new ResolutionError(ErrorCode::TYPE_MISMATCH()));

        return $builder->build();
    }
}
