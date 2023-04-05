<?php

declare(strict_types=1);

namespace LaunchDarkly\OpenFeature;

use LaunchDarkly\EvaluationDetail;
use LaunchDarkly\EvaluationReason;
use OpenFeature\implementation\provider\ResolutionDetailsBuilder;
use OpenFeature\implementation\provider\ResolutionError;
use OpenFeature\interfaces\provider\ErrorCode;
use OpenFeature\interfaces\provider\Reason;
use OpenFeature\interfaces\provider\ResolutionDetails;

/**
 * Converts an EvaluationDetail into an OpenFeature ResolutionDetails.
 */
class ResolutionDetailsConverter
{
    public function toResolutionDetails(EvaluationDetail $result): ResolutionDetails
    {
        $value = $result->getValue();
        $reason = $result->getReason();
        $isDefault = $result->isDefaultValue();
        $variationIndex = $result->getVariationIndex();

        $builder = new ResolutionDetailsBuilder();
        $builder->withValue($value);
        $builder->withReason(self::kindToString($reason->getKind()));

        if ($reason->getKind() == EvaluationReason::ERROR) {
            $builder->withError(self::errorKindToCode($reason->getErrorKind()));
        }

        if (!$isDefault) {
            $builder->withVariant(strval($variationIndex));
        }

        return $builder->build();
    }

    private static function kindToString(string $kind): string
    {
        switch ($kind) {
            case EvaluationReason::OFF:
                return Reason::DISABLED;
            case EvaluationReason::TARGET_MATCH:
                return Reason::TARGETING_MATCH;
            case EvaluationReason::ERROR:
                return Reason::ERROR;
            case EvaluationReason::FALLTHROUGH:
                // intentional fallthrough
            case EvaluationReason::RULE_MATCH:
                // intentional fallthrough
            case EvaluationReason::PREREQUISITE_FAILED:
                // intentional fallthrough
            default:
                return $kind;
        }
    }

    private static function errorKindToCode(?string $errorKind): ResolutionError
    {
        switch ($errorKind) {
            case EvaluationReason::CLIENT_NOT_READY_ERROR:
                return new ResolutionError(ErrorCode::PROVIDER_NOT_READY());
            case EvaluationReason::FLAG_NOT_FOUND_ERROR:
                return new ResolutionError(ErrorCode::FLAG_NOT_FOUND());
            case EvaluationReason::MALFORMED_FLAG_ERROR:
                return new ResolutionError(ErrorCode::PARSE_ERROR());
            case EvaluationReason::USER_NOT_SPECIFIED_ERROR:
                return new ResolutionError(ErrorCode::TARGETING_KEY_MISSING());
            case EvaluationReason::EXCEPTION_ERROR:
                // intentional fallthrough
            default:
                return new ResolutionError(ErrorCode::GENERAL());
        }
    }
}
