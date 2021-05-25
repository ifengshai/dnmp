<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/enums/proximity_radius_units.proto

namespace Google\Ads\GoogleAds\V3\Enums\ProximityRadiusUnitsEnum;

use UnexpectedValueException;

/**
 * The unit of radius distance in proximity (e.g. MILES)
 *
 * Protobuf type <code>google.ads.googleads.v3.enums.ProximityRadiusUnitsEnum.ProximityRadiusUnits</code>
 */
class ProximityRadiusUnits
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
     * Miles
     *
     * Generated from protobuf enum <code>MILES = 2;</code>
     */
    const MILES = 2;
    /**
     * Kilometers
     *
     * Generated from protobuf enum <code>KILOMETERS = 3;</code>
     */
    const KILOMETERS = 3;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::MILES => 'MILES',
        self::KILOMETERS => 'KILOMETERS',
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
class_alias(ProximityRadiusUnits::class, \Google\Ads\GoogleAds\V3\Enums\ProximityRadiusUnitsEnum_ProximityRadiusUnits::class);

