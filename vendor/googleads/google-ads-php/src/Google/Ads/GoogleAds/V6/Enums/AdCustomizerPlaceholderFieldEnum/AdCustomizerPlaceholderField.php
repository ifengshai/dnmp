<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v6/enums/ad_customizer_placeholder_field.proto

namespace Google\Ads\GoogleAds\V6\Enums\AdCustomizerPlaceholderFieldEnum;

use UnexpectedValueException;

/**
 * Possible values for Ad Customizers placeholder fields.
 *
 * Protobuf type <code>google.ads.googleads.v6.enums.AdCustomizerPlaceholderFieldEnum.AdCustomizerPlaceholderField</code>
 */
class AdCustomizerPlaceholderField
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
     * Data Type: INT64. Integer value to be inserted.
     *
     * Generated from protobuf enum <code>INTEGER = 2;</code>
     */
    const INTEGER = 2;
    /**
     * Data Type: STRING. Price value to be inserted.
     *
     * Generated from protobuf enum <code>PRICE = 3;</code>
     */
    const PRICE = 3;
    /**
     * Data Type: DATE_TIME. Date value to be inserted.
     *
     * Generated from protobuf enum <code>DATE = 4;</code>
     */
    const DATE = 4;
    /**
     * Data Type: STRING. String value to be inserted.
     *
     * Generated from protobuf enum <code>STRING = 5;</code>
     */
    const STRING = 5;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::INTEGER => 'INTEGER',
        self::PRICE => 'PRICE',
        self::DATE => 'DATE',
        self::STRING => 'STRING',
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
class_alias(AdCustomizerPlaceholderField::class, \Google\Ads\GoogleAds\V6\Enums\AdCustomizerPlaceholderFieldEnum_AdCustomizerPlaceholderField::class);

