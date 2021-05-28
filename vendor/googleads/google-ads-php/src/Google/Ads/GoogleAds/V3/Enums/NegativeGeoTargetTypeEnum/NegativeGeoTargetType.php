<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/enums/negative_geo_target_type.proto

namespace Google\Ads\GoogleAds\V3\Enums\NegativeGeoTargetTypeEnum;

use UnexpectedValueException;

/**
 * The possible negative geo target types.
 *
 * Protobuf type <code>google.ads.googleads.v3.enums.NegativeGeoTargetTypeEnum.NegativeGeoTargetType</code>
 */
class NegativeGeoTargetType
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
     * Specifies that a user is excluded from seeing the ad if they
     * are in, or show interest in, advertiser's excluded locations.
     *
     * Generated from protobuf enum <code>PRESENCE_OR_INTEREST = 4;</code>
     */
    const PRESENCE_OR_INTEREST = 4;
    /**
     * Specifies that a user is excluded from seeing the ad if they
     * are in advertiser's excluded locations.
     *
     * Generated from protobuf enum <code>PRESENCE = 5;</code>
     */
    const PRESENCE = 5;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::PRESENCE_OR_INTEREST => 'PRESENCE_OR_INTEREST',
        self::PRESENCE => 'PRESENCE',
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
class_alias(NegativeGeoTargetType::class, \Google\Ads\GoogleAds\V3\Enums\NegativeGeoTargetTypeEnum_NegativeGeoTargetType::class);

