<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v6/enums/system_managed_entity_source.proto

namespace Google\Ads\GoogleAds\V6\Enums\SystemManagedResourceSourceEnum;

use UnexpectedValueException;

/**
 * Enum listing the possible system managed entity sources.
 *
 * Protobuf type <code>google.ads.googleads.v6.enums.SystemManagedResourceSourceEnum.SystemManagedResourceSource</code>
 */
class SystemManagedResourceSource
{
    /**
     * Not specified.
     *
     * Generated from protobuf enum <code>UNSPECIFIED = 0;</code>
     */
    const UNSPECIFIED = 0;
    /**
     * Used for return value only. Represents value unknown in this version.
     *
     * Generated from protobuf enum <code>UNKNOWN = 1;</code>
     */
    const UNKNOWN = 1;
    /**
     * Generated ad variations experiment ad.
     *
     * Generated from protobuf enum <code>AD_VARIATIONS = 2;</code>
     */
    const AD_VARIATIONS = 2;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::AD_VARIATIONS => 'AD_VARIATIONS',
    ];

    public static function name($value)
    {
        if (!isset(self::$valueToName[$value])) {
            throw new UnexpectedValueException(sprintf(
                    'Enum %s has no name defined for value %s', __CLASS__, $value));
        }
        return self::$valueToName[$value];
    }


    public static function value($name)
    {
        $const = __CLASS__ . '::' . strtoupper($name);
        if (!defined($const)) {
            throw new UnexpectedValueException(sprintf(
                    'Enum %s has no value defined for name %s', __CLASS__, $name));
        }
        return constant($const);
    }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(SystemManagedResourceSource::class, \Google\Ads\GoogleAds\V6\Enums\SystemManagedResourceSourceEnum_SystemManagedResourceSource::class);

