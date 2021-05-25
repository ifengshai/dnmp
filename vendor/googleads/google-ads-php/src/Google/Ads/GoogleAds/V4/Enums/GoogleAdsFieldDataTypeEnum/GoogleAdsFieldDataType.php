<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/enums/google_ads_field_data_type.proto

namespace Google\Ads\GoogleAds\V4\Enums\GoogleAdsFieldDataTypeEnum;

use UnexpectedValueException;

/**
 * These are the various types a GoogleAdsService artifact may take on.
 *
 * Protobuf type <code>google.ads.googleads.v4.enums.GoogleAdsFieldDataTypeEnum.GoogleAdsFieldDataType</code>
 */
class GoogleAdsFieldDataType
{
    /**
     * Unspecified
     *
     * Generated from protobuf enum <code>UNSPECIFIED = 0;</code>
     */
    const UNSPECIFIED = 0;
    /**
     * Unknown
     *
     * Generated from protobuf enum <code>UNKNOWN = 1;</code>
     */
    const UNKNOWN = 1;
    /**
     * Maps to google.protobuf.BoolValue
     * Applicable operators:  =, !=
     *
     * Generated from protobuf enum <code>BOOLEAN = 2;</code>
     */
    const BOOLEAN = 2;
    /**
     * Maps to google.protobuf.StringValue. It can be compared using the set of
     * operators specific to dates however.
     * Applicable operators:  =, <, >, <=, >=, BETWEEN, DURING, and IN
     *
     * Generated from protobuf enum <code>DATE = 3;</code>
     */
    const DATE = 3;
    /**
     * Maps to google.protobuf.DoubleValue
     * Applicable operators:  =, !=, <, >, IN, NOT IN
     *
     * Generated from protobuf enum <code>DOUBLE = 4;</code>
     */
    const DOUBLE = 4;
    /**
     * Maps to an enum. It's specific definition can be found at type_url.
     * Applicable operators:  =, !=, IN, NOT IN
     *
     * Generated from protobuf enum <code>ENUM = 5;</code>
     */
    const ENUM = 5;
    /**
     * Maps to google.protobuf.FloatValue
     * Applicable operators:  =, !=, <, >, IN, NOT IN
     *
     * Generated from protobuf enum <code>FLOAT = 6;</code>
     */
    const FLOAT = 6;
    /**
     * Maps to google.protobuf.Int32Value
     * Applicable operators:  =, !=, <, >, <=, >=, BETWEEN, IN, NOT IN
     *
     * Generated from protobuf enum <code>INT32 = 7;</code>
     */
    const INT32 = 7;
    /**
     * Maps to google.protobuf.Int64Value
     * Applicable operators:  =, !=, <, >, <=, >=, BETWEEN, IN, NOT IN
     *
     * Generated from protobuf enum <code>INT64 = 8;</code>
     */
    const INT64 = 8;
    /**
     * Maps to a protocol buffer message type. The data type's details can be
     * found in type_url.
     * No operators work with MESSAGE fields.
     *
     * Generated from protobuf enum <code>MESSAGE = 9;</code>
     */
    const MESSAGE = 9;
    /**
     * Maps to google.protobuf.StringValue. Represents the resource name
     * (unique id) of a resource or one of its foreign keys.
     * No operators work with RESOURCE_NAME fields.
     *
     * Generated from protobuf enum <code>RESOURCE_NAME = 10;</code>
     */
    const RESOURCE_NAME = 10;
    /**
     * Maps to google.protobuf.StringValue.
     * Applicable operators:  =, !=, LIKE, NOT LIKE, IN, NOT IN
     *
     * Generated from protobuf enum <code>STRING = 11;</code>
     */
    const STRING = 11;
    /**
     * Maps to google.protobuf.UInt64Value
     * Applicable operators:  =, !=, <, >, <=, >=, BETWEEN, IN, NOT IN
     *
     * Generated from protobuf enum <code>UINT64 = 12;</code>
     */
    const UINT64 = 12;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::BOOLEAN => 'BOOLEAN',
        self::DATE => 'DATE',
        self::DOUBLE => 'DOUBLE',
        self::ENUM => 'ENUM',
        self::FLOAT => 'FLOAT',
        self::INT32 => 'INT32',
        self::INT64 => 'INT64',
        self::MESSAGE => 'MESSAGE',
        self::RESOURCE_NAME => 'RESOURCE_NAME',
        self::STRING => 'STRING',
        self::UINT64 => 'UINT64',
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
class_alias(GoogleAdsFieldDataType::class, \Google\Ads\GoogleAds\V4\Enums\GoogleAdsFieldDataTypeEnum_GoogleAdsFieldDataType::class);

