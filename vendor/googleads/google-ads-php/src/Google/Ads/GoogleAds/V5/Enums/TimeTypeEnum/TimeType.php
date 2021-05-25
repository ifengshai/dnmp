<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/enums/time_type.proto

namespace Google\Ads\GoogleAds\V5\Enums\TimeTypeEnum;

use UnexpectedValueException;

/**
 * The possible time types used by certain resources as an alternative to
 * absolute timestamps.
 *
 * Protobuf type <code>google.ads.googleads.v5.enums.TimeTypeEnum.TimeType</code>
 */
class TimeType
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
     * As soon as possible.
     *
     * Generated from protobuf enum <code>NOW = 2;</code>
     */
    const NOW = 2;
    /**
     * An infinite point in the future.
     *
     * Generated from protobuf enum <code>FOREVER = 3;</code>
     */
    const FOREVER = 3;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::NOW => 'NOW',
        self::FOREVER => 'FOREVER',
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
class_alias(TimeType::class, \Google\Ads\GoogleAds\V5\Enums\TimeTypeEnum_TimeType::class);

