<?php

declare(strict_types=1);

namespace LaunchDarkly\OpenFeature;

use DateTime;
use LaunchDarkly\LDContext;
use LaunchDarkly\LDContextBuilder;
use LaunchDarkly\LDContextMultiBuilder;
use OpenFeature\interfaces\flags\EvaluationContext;
use Psr\Log\LoggerInterface;

/**
 * Converts an OpenFeature EvaluationContext into a LDContext.
 */
class EvaluationContextConverter
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Sets a logger instance on the object.
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Create an LDContext from an EvaluationContext.
     *
     * A context will always be created, but the created context may be invalid.
     * Log messages will be written to indicate the source of the problem.
     */
    public function toLdContext(EvaluationContext $evaluationContext): LDContext
    {
        $attributes = $evaluationContext->getAttributes();

        $kind = $attributes->get("kind");
        if ($kind === "multi") {
            return $this->buildMultiContext($evaluationContext);
        }

        if ($kind !== null && !is_string($kind)) {
            $this->logger->warning("'kind' was set to non-string value; defaulting to user");
            $kind = "user";
        }

        $targetingKey = $evaluationContext->getTargetingKey();
        $key = $attributes->get("key");

        $targetingKey = $this->getTargetingKey($targetingKey, $key);

        return $this->buildSingleContext($evaluationContext->getAttributes()->toArray(), $kind ?? "user", $targetingKey);
    }

    /**
     * @param bool|string|int|float|DateTime|array|mixed[]|null $key
     */
    private function getTargetingKey(?string $targetingKey, $key): string
    {
        // Currently the targeting key will always have a value, but it can be empty.
        // So we want to treat an empty string as a not defined one. Later it could
        // become null, so we will need to check that.
        if ($targetingKey !== null && $targetingKey !== "" && is_string($key)) {
            // There is both a targeting key and a key. It will work, but probably
            // is not intentional.
            $this->logger->warning("EvaluationContext contained both a 'key' and 'targetingKey'.");
        }

        if ($key !== null && !is_string($key)) {
            $this->logger->warning("A non-string 'key' attribute was provided.");
        }

        if ($key !== null && is_string($key)) {
            // Targeting key takes precedence over key, because targeting key is in the spec.
            $targetingKey = $targetingKey ?: $key;
        }

        if ($targetingKey === null || $targetingKey === "") {
            $this->logger->error("The EvaluationContext must contain either a 'targetingKey' or a 'key' and the type must be a string.");
        }

        return $targetingKey ?? "";
    }

    private function buildMultiContext(EvaluationContext $evaluationContext): LDContext
    {
        $builder = new LDContextMultiBuilder();

        foreach ($evaluationContext->getAttributes()->toArray() as $kind => $attributes) {
            if ($kind === "kind") {
                continue;
            }

            if (!is_array($attributes)) {
                // The attributes need to be a structure to be part of a multi-context.
                $this->logger->warning("Top level attributes in a multi-kind context should be Structure types.");
                continue;
            }

            $key = $attributes["key"] ?? null;
            $targetingKey = $attributes["targetingKey"] ?? null;

            if ($targetingKey !== null && !is_string($targetingKey)) {
                // We need to log some warning about the wrong type of the targeting key here
                continue;
            }

            $targetingKey = $this->getTargetingKey($targetingKey, $key);
            $singleContext = $this->buildSingleContext($attributes, $kind, $targetingKey);

            $builder->add($singleContext);
        }

        return $builder->build();
    }

    /**
     * @param Array<array-key, bool|string|int|float|DateTime|mixed[]|null> $attributes
     */
    private function buildSingleContext(array $attributes, string $kind, string $key): LDContext
    {
        $builder = new LDContextBuilder($key);
        $builder->kind($kind);

        foreach ($attributes as $k => $v) {
            // Key has been processed, so we can skip it.
            if ($k === "key" || $key === "targetingKey") {
                continue;
            }

            if ($k === "name" && is_string($v)) {
                $builder->name($v);
            } elseif ($k === "name") {
                $this->logger->error("The attribute 'name' must be a string");
            } elseif ($k === "anonymous" && is_bool($v)) {
                $builder->anonymous($v);
            } elseif ($k === "anonymous") {
                $this->logger->error("The attribute 'anonymous' must be a boolean");
            } elseif ($k === "privateAttributes" && is_array($v)) {
                $privateAttributes = array_values($v);
                foreach ($privateAttributes as $privateAttribute) {
                    if (!is_string($privateAttribute)) {
                        $this->logger->error("'privateAttributes' must be an array of only string values");
                        continue;
                    }

                    $builder->private($privateAttribute);
                }
            } elseif ($k === "privateAttributes") {
                $this->logger->error("'privateAttributes' in an evaluation context must be an array");
            } else {
                // Catch all for remaining attributes
                $builder->set($k, $v);
            }
        }

        return $builder->build();
    }
}
