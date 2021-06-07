<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/errors/country_code_error.proto

namespace Google\Ads\GoogleAds\V4\Errors\CountryCodeErrorEnum;

use UnexpectedValueException;

/**
 * Enum describing country code errors.
 *
 * Protobuf type <code>google.ads.googleads.v4.errors.CountryCodeErrorEnum.CountryCodeError</code>
 */
class CountryCodeError
{
    /**
     * Enum unspecified.
     *
     * Generated from protobuf enum <code>UNSPECIFIED = 0;</code>
     */
    const UNSPECIFIED = 0;
    /**
     * The received error code is not known in this version.
     *
     * Generated from protobuf enum <code>UNKNOWN = 1;</code>
     */
    const UNKNOWN = 1;
    /**
     * The country code is invalid.
     *
     * Generated from protobuf enum <code>INVALID_COUNTRY_CODE = 2;</code>
     */
    const INVALID_COUNTRY_CODE = 2;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::INVALID_COUNTRY_CODE => 'INVALID_COUNTRY_CODE',
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
class_alias(CountryCodeError::class, \Google\Ads\GoogleAds\V4\Errors\CountryCodeErrorEnum_CountryCodeError::class);

