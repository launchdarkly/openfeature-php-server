<?php

declare(strict_types=1);

namespace LaunchDarkly\Tests;

use LaunchDarkly\BigSegmentsEvaluationStatus;
use LaunchDarkly\EvaluationDetail;
use LaunchDarkly\EvaluationReason;
use LaunchDarkly\Integrations;
use LaunchDarkly\OpenFeature\Provider;
use LaunchDarkly\OpenFeature\ResolutionDetailsConverter;
use OpenFeature\implementation\flags\EvaluationContext;
use PHPUnit\Framework\TestCase;

class ResolutionDetailsConverterTest extends TestCase
{
    private ResolutionDetailsConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new ResolutionDetailsConverter();
    }

    public function testFallthroughHasOnlyVariationIndex(): void
    {
        $details = $this->converter->toResolutionDetails(
            new EvaluationDetail(true, 1, EvaluationReason::fallthrough())
        );

        $this->assertSame(['variationIndex' => 1], $details->getMetadata());
    }

    public function testExperimentFallthroughIncludesInExperiment(): void
    {
        $details = $this->converter->toResolutionDetails(
            new EvaluationDetail(true, 0, EvaluationReason::fallthrough(true))
        );

        $this->assertSame(['variationIndex' => 0, 'inExperiment' => true], $details->getMetadata());
    }

    public function testRuleMatchIncludesRuleIndexAndId(): void
    {
        $details = $this->converter->toResolutionDetails(
            new EvaluationDetail('value', 2, EvaluationReason::ruleMatch(3, 'rule-id', true))
        );

        $this->assertSame(
            ['variationIndex' => 2, 'inExperiment' => true, 'ruleIndex' => 3, 'ruleId' => 'rule-id'],
            $details->getMetadata()
        );
    }

    public function testPrerequisiteFailedIncludesPrerequisiteKey(): void
    {
        $details = $this->converter->toResolutionDetails(
            new EvaluationDetail(false, 0, EvaluationReason::prerequisiteFailed('prereq-key'))
        );

        $this->assertSame(['variationIndex' => 0, 'prerequisiteKey' => 'prereq-key'], $details->getMetadata());
    }

    public function testBigSegmentsStatusIsIncluded(): void
    {
        $reason = EvaluationReason::fallthrough()
            ->withBigSegmentsEvaluationStatus(BigSegmentsEvaluationStatus::STALE);
        $details = $this->converter->toResolutionDetails(new EvaluationDetail(true, 0, $reason));

        $this->assertSame(['variationIndex' => 0, 'bigSegmentsStatus' => 'STALE'], $details->getMetadata());
    }

    public function testDefaultValueHasNoMetadata(): void
    {
        $details = $this->converter->toResolutionDetails(
            new EvaluationDetail(true, null, EvaluationReason::error(EvaluationReason::FLAG_NOT_FOUND_ERROR))
        );

        $this->assertSame([], $details->getMetadata());
    }

    public function testOffWithoutOffVariationHasNoMetadata(): void
    {
        $details = $this->converter->toResolutionDetails(
            new EvaluationDetail(null, null, EvaluationReason::off())
        );

        $this->assertSame([], $details->getMetadata());
    }

    public function testProviderEvaluationIncludesRuleMetadata(): void
    {
        $td = new Integrations\TestData();
        // @phpstan-ignore-next-line argument.type (the SDK documents the variadic values as an array)
        $rule = $td->flag('flag-key')->ifMatch('key', 'user-key');
        $td->update($rule->thenReturn(true));

        $provider = new Provider('sdk-key', ['feature_requester' => $td, 'send_events' => false]);
        $details = $provider->resolveBooleanValue('flag-key', false, new EvaluationContext('user-key'));

        $metadata = $details->getMetadata();
        $this->assertNotNull($metadata);
        $this->assertSame(0, $metadata['variationIndex']);
        $this->assertSame(0, $metadata['ruleIndex']);
        $this->assertArrayHasKey('ruleId', $metadata);
        $this->assertArrayNotHasKey('inExperiment', $metadata);
        $this->assertArrayNotHasKey('prerequisiteKey', $metadata);
        $this->assertArrayNotHasKey('bigSegmentsStatus', $metadata);
    }
}
