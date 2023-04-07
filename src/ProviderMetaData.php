<?php

declare(strict_types=1);

namespace LaunchDarkly\OpenFeature;

use OpenFeature\interfaces\common\Metadata;

/**
 * Object responsible for returning metadata information associated with the
 * configured provider.
 */
class ProviderMetaData implements Metadata
{
    /**
     * Returns string identifier for this provider type.
     */
    public function getName(): string
    {
        return "LaunchDarkly\\OpenFeature";
    }
}
