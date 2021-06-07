<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/errors/keyword_plan_ad_group_error.proto

namespace Google\Ads\GoogleAds\V4\Errors\KeywordPlanAdGroupErrorEnum;

use UnexpectedValueException;

/**
 * Enum describing possible errors from applying a keyword plan ad group.
 *
 * Protobuf type <code>google.ads.googleads.v4.errors.KeywordPlanAdGroupErrorEnum.KeywordPlanAdGroupError</code>
 */
class KeywordPlanAdGroupError
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
     * The keyword plan ad group name is missing, empty, longer than allowed
     * limit or contains invalid chars.
     *
     * Generated from protobuf enum <code>INVALID_NAME = 2;</code>
     */
    const INVALID_NAME = 2;
    /**
     * The keyword plan ad group name is duplicate to an existing keyword plan
     * AdGroup name or other keyword plan AdGroup name in the request.
     *
     * Generated from protobuf enum <code>DUPLICATE_NAME = 3;</code>
     */
    const DUPLICATE_NAME = 3;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::INVALID_NAME => 'INVALID_NAME',
        self::DUPLICATE_NAME => 'DUPLICATE_NAME',
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
class_alias(KeywordPlanAdGroupError::class, \Google\Ads\GoogleAds\V4\Errors\KeywordPlanAdGroupErrorEnum_KeywordPlanAdGroupError::class);

