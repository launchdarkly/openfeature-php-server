<?php

declare(strict_types=1);

namespace LaunchDarkly\Tests;

use LaunchDarkly\EvaluationReason;
use LaunchDarkly\Integrations;
use LaunchDarkly\OpenFeature\Provider;
use OpenFeature\implementation\flags\Attributes;
use OpenFeature\implementation\flags\EvaluationContext;
use OpenFeature\interfaces\provider\ErrorCode;
use OpenFeature\interfaces\provider\Reason;
use OpenFeature\interfaces\provider\ResolutionError;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ProviderTest extends TestCase
{
    public function testMetadataNameIsSetCorrectly(): void
    {
        $provider = new Provider('sdk-key');

        $this->assertEquals("LaunchDarkly\\OpenFeature", $provider->getMetadata()->getName());
    }

    public function testNotProvidingContextReturnsError(): void
    {
        $provider = new Provider('sdk-key');
        $resolutionDetails = $provider->resolveBooleanValue("flag-key", true, null);

        $this->assertTrue($resolutionDetails->getValue());
        $this->assertEquals(Reason::ERROR, $resolutionDetails->getReason());
        $this->assertNull($resolutionDetails->getVariant());

        /** @var ResolutionError */
        $error = $resolutionDetails->getError();
        $this->assertEquals(ErrorCode::TARGETING_KEY_MISSING(), $error->getResolutionErrorCode());
    }

    public function testEvaluationResultsAreConvertedToDetails(): void
    {
        $td = new Integrations\TestData();
        $td->update($td->flag('flag-key'));

        $provider = new Provider('sdk-key', ['feature_requester' => $td]);
        $resolutionDetails = $provider->resolveBooleanValue("flag-key", true, new EvaluationContext("user-key"));

        $this->assertTrue($resolutionDetails->getValue());
        $this->assertEquals(EvaluationReason::FALLTHROUGH, $resolutionDetails->getReason());
        $this->assertEquals("0", $resolutionDetails->getVariant());
        $this->assertNull($resolutionDetails->getError());
    }

    public function testEvaluationErrorResultsAreConvertedCorrectly(): void
    {
        $provider = new Provider('sdk-key', ['base_uri' => 'http://invalid.host:8080', 'events_uri' => 'http://invalid.host:8080']);
        $resolutionDetails = $provider->resolveBooleanValue("flag-key", true, new EvaluationContext("user-key"));

        $this->assertTrue($resolutionDetails->getValue());
        $this->assertEquals(EvaluationReason::ERROR, $resolutionDetails->getReason());
        $this->assertNull($resolutionDetails->getVariant());

        /** @var ResolutionError */
        $error = $resolutionDetails->getError();
        $this->assertEquals(ErrorCode::GENERAL(), $error->getResolutionErrorCode());
    }

    public function testInvalidTypesGenerateTypeMismatchResults(): void
    {
        $td = new Integrations\TestData();
        $td->update($td->flag('flag-key'));

        $provider = new Provider('sdk-key', ['feature_requester' => $td]);
        $resolutionDetails = $provider->resolveStringValue("flag-key", "default-value", new EvaluationContext("user-key"));

        $this->assertEquals("default-value", $resolutionDetails->getValue());
        $this->assertEquals(EvaluationReason::ERROR, $resolutionDetails->getReason());
        $this->assertNull($resolutionDetails->getVariant());
        /** @var ResolutionError */
        $error = $resolutionDetails->getError();
        $this->assertEquals(ErrorCode::TYPE_MISMATCH(), $error->getResolutionErrorCode());
    }
    /**
     * @return array<int,mixed>
     */
    public function checkMethodAndResultMatchTypeProvider(): array
    {
        return [
            [true, false, false, 'resolveBooleanValue'],
            [false, true, true, 'resolveBooleanValue'],
            [false, 1, false, 'resolveBooleanValue'],
            [false, "true", false, 'resolveBooleanValue'],
            [true, [], true, 'resolveBooleanValue'],

            ['default-string', 'return-string', 'return-string', 'resolveStringValue'],
            ['default-string', 1, 'default-string', 'resolveStringValue'],
            ['default-string', true, 'default-string', 'resolveStringValue'],

            [1, 2, 2, 'resolveIntegerValue'],
            [1, true, 1, 'resolveIntegerValue'],
            [1, false, 1, 'resolveIntegerValue'],
            [1, "", 1, 'resolveIntegerValue'],

            [1.0, 2.0, 2.0, 'resolveFloatValue'],
            [1.0, 2, 2.0, 'resolveFloatValue'],
            [1.0, true, 1.0, 'resolveFloatValue'],

            [['default-value'], ['return-string'], ['return-string'], 'resolveObjectValue'],
            [['default-value'], true, ['default-value'], 'resolveObjectValue'],
            [['default-value'], 1, ['default-value'], 'resolveObjectValue'],
            [['default-value'], 'return-string', ['default-value'], 'resolveObjectValue'],
        ];
    }

    /**
     * @dataProvider checkMethodAndResultMatchTypeProvider
     */
    public function testCheckMethodAndResultMatchType(mixed $defaultValue, mixed $returnValue, mixed $expectedValue, string $methodName): void
    {
        $td = new Integrations\TestData();
        $td->update($td->flag('flag-key')->variations($defaultValue, $returnValue)->variationForAll(1));

        $provider = new Provider('sdk-key', ['feature_requester' => $td]);
        $resolutionDetails = $provider->{$methodName}("flag-key", $defaultValue, new EvaluationContext("user-key"));

        $this->assertEquals($expectedValue, $resolutionDetails->getValue());
    }

    public function testLoggerChangesShouldCascadeToEvaluationConverter(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with($this->equalTo("'kind' was set to non-string value; defaulting to user"));

        $td = new Integrations\TestData();
        $td->update($td->flag('flag-key'));

        $provider = new Provider('sdk-key', ['feature_requester' => $td, 'logger' => $logger]);
        $context = new EvaluationContext("user-key", new Attributes(['kind' => false]));
        $provider->resolveBooleanValue("flag-key", false, $context);
    }
}
