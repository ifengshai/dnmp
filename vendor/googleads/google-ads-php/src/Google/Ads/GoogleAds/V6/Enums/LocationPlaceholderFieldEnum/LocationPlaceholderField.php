<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v6/enums/location_placeholder_field.proto

namespace Google\Ads\GoogleAds\V6\Enums\LocationPlaceholderFieldEnum;

use UnexpectedValueException;

/**
 * Possible values for Location placeholder fields.
 *
 * Protobuf type <code>google.ads.googleads.v6.enums.LocationPlaceholderFieldEnum.LocationPlaceholderField</code>
 */
class LocationPlaceholderField
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
     * Data Type: STRING. The name of the business.
     *
     * Generated from protobuf enum <code>BUSINESS_NAME = 2;</code>
     */
    const BUSINESS_NAME = 2;
    /**
     * Data Type: STRING. Line 1 of the business address.
     *
     * Generated from protobuf enum <code>ADDRESS_LINE_1 = 3;</code>
     */
    const ADDRESS_LINE_1 = 3;
    /**
     * Data Type: STRING. Line 2 of the business address.
     *
     * Generated from protobuf enum <code>ADDRESS_LINE_2 = 4;</code>
     */
    const ADDRESS_LINE_2 = 4;
    /**
     * Data Type: STRING. City of the business address.
     *
     * Generated from protobuf enum <code>CITY = 5;</code>
     */
    const CITY = 5;
    /**
     * Data Type: STRING. Province of the business address.
     *
     * Generated from protobuf enum <code>PROVINCE = 6;</code>
     */
    const PROVINCE = 6;
    /**
     * Data Type: STRING. Postal code of the business address.
     *
     * Generated from protobuf enum <code>POSTAL_CODE = 7;</code>
     */
    const POSTAL_CODE = 7;
    /**
     * Data Type: STRING. Country code of the business address.
     *
     * Generated from protobuf enum <code>COUNTRY_CODE = 8;</code>
     */
    const COUNTRY_CODE = 8;
    /**
     * Data Type: STRING. Phone number of the business.
     *
     * Generated from protobuf enum <code>PHONE_NUMBER = 9;</code>
     */
    const PHONE_NUMBER = 9;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::BUSINESS_NAME => 'BUSINESS_NAME',
        self::ADDRESS_LINE_1 => 'ADDRESS_LINE_1',
        self::ADDRESS_LINE_2 => 'ADDRESS_LINE_2',
        self::CITY => 'CITY',
        self::PROVINCE => 'PROVINCE',
        self::POSTAL_CODE => 'POSTAL_CODE',
        self::COUNTRY_CODE => 'COUNTRY_CODE',
        self::PHONE_NUMBER => 'PHONE_NUMBER',
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
class_alias(LocationPlaceholderField::class, \Google\Ads\GoogleAds\V6\Enums\LocationPlaceholderFieldEnum_LocationPlaceholderField::class);

