<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/errors/not_allowlisted_error.proto

namespace Google\Ads\GoogleAds\V5\Errors\NotAllowlistedErrorEnum;

use UnexpectedValueException;

/**
 * Enum describing possible not allowlisted errors.
 *
 * Protobuf type <code>google.ads.googleads.v5.errors.NotAllowlistedErrorEnum.NotAllowlistedError</code>
 */
class NotAllowlistedError
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
     * Customer is not allowlisted for accessing this feature.
     *
     * Generated from protobuf enum <code>CUSTOMER_NOT_ALLOWLISTED_FOR_THIS_FEATURE = 2;</code>
     */
    const CUSTOMER_NOT_ALLOWLISTED_FOR_THIS_FEATURE = 2;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::CUSTOMER_NOT_ALLOWLISTED_FOR_THIS_FEATURE => 'CUSTOMER_NOT_ALLOWLISTED_FOR_THIS_FEATURE',
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
class_alias(NotAllowlistedError::class, \Google\Ads\GoogleAds\V5\Errors\NotAllowlistedErrorEnum_NotAllowlistedError::class);

