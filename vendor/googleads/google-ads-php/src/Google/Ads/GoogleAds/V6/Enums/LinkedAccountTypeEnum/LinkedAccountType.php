<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v6/enums/linked_account_type.proto

namespace Google\Ads\GoogleAds\V6\Enums\LinkedAccountTypeEnum;

use UnexpectedValueException;

/**
 * Describes the possible link types between a Google Ads customer
 * and another account.
 *
 * Protobuf type <code>google.ads.googleads.v6.enums.LinkedAccountTypeEnum.LinkedAccountType</code>
 */
class LinkedAccountType
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
     * A link to provide third party app analytics data.
     *
     * Generated from protobuf enum <code>THIRD_PARTY_APP_ANALYTICS = 2;</code>
     */
    const THIRD_PARTY_APP_ANALYTICS = 2;
    /**
     * A link to Data partner.
     *
     * Generated from protobuf enum <code>DATA_PARTNER = 3;</code>
     */
    const DATA_PARTNER = 3;
    /**
     * A link to Google Ads.
     *
     * Generated from protobuf enum <code>GOOGLE_ADS = 4;</code>
     */
    const GOOGLE_ADS = 4;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::THIRD_PARTY_APP_ANALYTICS => 'THIRD_PARTY_APP_ANALYTICS',
        self::DATA_PARTNER => 'DATA_PARTNER',
        self::GOOGLE_ADS => 'GOOGLE_ADS',
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
class_alias(LinkedAccountType::class, \Google\Ads\GoogleAds\V6\Enums\LinkedAccountTypeEnum_LinkedAccountType::class);

