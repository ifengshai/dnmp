<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v6/enums/conversion_attribution_event_type.proto

namespace Google\Ads\GoogleAds\V6\Enums\ConversionAttributionEventTypeEnum;

use UnexpectedValueException;

/**
 * The event type of conversions that are attributed to.
 *
 * Protobuf type <code>google.ads.googleads.v6.enums.ConversionAttributionEventTypeEnum.ConversionAttributionEventType</code>
 */
class ConversionAttributionEventType
{
    /**
     * Not specified.
     *
     * Generated from protobuf enum <code>UNSPECIFIED = 0;</code>
     */
    const UNSPECIFIED = 0;
    /**
     * Represents value unknown in this version.
     *
     * Generated from protobuf enum <code>UNKNOWN = 1;</code>
     */
    const UNKNOWN = 1;
    /**
     * The conversion is attributed to an impression.
     *
     * Generated from protobuf enum <code>IMPRESSION = 2;</code>
     */
    const IMPRESSION = 2;
    /**
     * The conversion is attributed to an interaction.
     *
     * Generated from protobuf enum <code>INTERACTION = 3;</code>
     */
    const INTERACTION = 3;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::IMPRESSION => 'IMPRESSION',
        self::INTERACTION => 'INTERACTION',
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
class_alias(ConversionAttributionEventType::class, \Google\Ads\GoogleAds\V6\Enums\ConversionAttributionEventTypeEnum_ConversionAttributionEventType::class);

