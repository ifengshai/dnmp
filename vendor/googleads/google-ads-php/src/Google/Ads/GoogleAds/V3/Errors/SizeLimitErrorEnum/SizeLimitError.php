<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/errors/size_limit_error.proto

namespace Google\Ads\GoogleAds\V3\Errors\SizeLimitErrorEnum;

use UnexpectedValueException;

/**
 * Enum describing possible size limit errors.
 *
 * Protobuf type <code>google.ads.googleads.v3.errors.SizeLimitErrorEnum.SizeLimitError</code>
 */
class SizeLimitError
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
     * The number of entries in the request exceeds the system limit.
     *
     * Generated from protobuf enum <code>REQUEST_SIZE_LIMIT_EXCEEDED = 2;</code>
     */
    const REQUEST_SIZE_LIMIT_EXCEEDED = 2;
    /**
     * The number of entries in the response exceeds the system limit.
     *
     * Generated from protobuf enum <code>RESPONSE_SIZE_LIMIT_EXCEEDED = 3;</code>
     */
    const RESPONSE_SIZE_LIMIT_EXCEEDED = 3;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::REQUEST_SIZE_LIMIT_EXCEEDED => 'REQUEST_SIZE_LIMIT_EXCEEDED',
        self::RESPONSE_SIZE_LIMIT_EXCEEDED => 'RESPONSE_SIZE_LIMIT_EXCEEDED',
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
class_alias(SizeLimitError::class, \Google\Ads\GoogleAds\V3\Errors\SizeLimitErrorEnum_SizeLimitError::class);

