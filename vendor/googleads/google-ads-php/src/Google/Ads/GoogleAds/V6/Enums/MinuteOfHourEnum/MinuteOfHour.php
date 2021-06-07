<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v6/enums/minute_of_hour.proto

namespace Google\Ads\GoogleAds\V6\Enums\MinuteOfHourEnum;

use UnexpectedValueException;

/**
 * Enumerates of quarter-hours. E.g. "FIFTEEN"
 *
 * Protobuf type <code>google.ads.googleads.v6.enums.MinuteOfHourEnum.MinuteOfHour</code>
 */
class MinuteOfHour
{
    /**
     * Not specified.
     *
     * Generated from protobuf enum <code>UNSPECIFIED = 0;</code>
     */
    const UNSPECIFIED = 0;
    /**
     * The value is unknown in this version.
     *
     * Generated from protobuf enum <code>UNKNOWN = 1;</code>
     */
    const UNKNOWN = 1;
    /**
     * Zero minutes past the hour.
     *
     * Generated from protobuf enum <code>ZERO = 2;</code>
     */
    const ZERO = 2;
    /**
     * Fifteen minutes past the hour.
     *
     * Generated from protobuf enum <code>FIFTEEN = 3;</code>
     */
    const FIFTEEN = 3;
    /**
     * Thirty minutes past the hour.
     *
     * Generated from protobuf enum <code>THIRTY = 4;</code>
     */
    const THIRTY = 4;
    /**
     * Forty-five minutes past the hour.
     *
     * Generated from protobuf enum <code>FORTY_FIVE = 5;</code>
     */
    const FORTY_FIVE = 5;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::ZERO => 'ZERO',
        self::FIFTEEN => 'FIFTEEN',
        self::THIRTY => 'THIRTY',
        self::FORTY_FIVE => 'FORTY_FIVE',
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
class_alias(MinuteOfHour::class, \Google\Ads\GoogleAds\V6\Enums\MinuteOfHourEnum_MinuteOfHour::class);

