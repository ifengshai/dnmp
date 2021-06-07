<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/enums/price_extension_price_qualifier.proto

namespace Google\Ads\GoogleAds\V5\Enums\PriceExtensionPriceQualifierEnum;

use UnexpectedValueException;

/**
 * Enums of price extension price qualifier.
 *
 * Protobuf type <code>google.ads.googleads.v5.enums.PriceExtensionPriceQualifierEnum.PriceExtensionPriceQualifier</code>
 */
class PriceExtensionPriceQualifier
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
     * 'From' qualifier for the price.
     *
     * Generated from protobuf enum <code>FROM = 2;</code>
     */
    const FROM = 2;
    /**
     * 'Up to' qualifier for the price.
     *
     * Generated from protobuf enum <code>UP_TO = 3;</code>
     */
    const UP_TO = 3;
    /**
     * 'Average' qualifier for the price.
     *
     * Generated from protobuf enum <code>AVERAGE = 4;</code>
     */
    const AVERAGE = 4;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::FROM => 'FROM',
        self::UP_TO => 'UP_TO',
        self::AVERAGE => 'AVERAGE',
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
class_alias(PriceExtensionPriceQualifier::class, \Google\Ads\GoogleAds\V5\Enums\PriceExtensionPriceQualifierEnum_PriceExtensionPriceQualifier::class);

