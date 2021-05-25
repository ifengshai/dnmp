<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/errors/resource_count_limit_exceeded_error.proto

namespace Google\Ads\GoogleAds\V3\Errors\ResourceCountLimitExceededErrorEnum;

use UnexpectedValueException;

/**
 * Enum describing possible resource count limit exceeded errors.
 *
 * Protobuf type <code>google.ads.googleads.v3.errors.ResourceCountLimitExceededErrorEnum.ResourceCountLimitExceededError</code>
 */
class ResourceCountLimitExceededError
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
     * Indicates that this request would exceed the number of allowed resources
     * for the Google Ads account. The exact resource type and limit being
     * checked can be inferred from accountLimitType.
     *
     * Generated from protobuf enum <code>ACCOUNT_LIMIT = 2;</code>
     */
    const ACCOUNT_LIMIT = 2;
    /**
     * Indicates that this request would exceed the number of allowed resources
     * in a Campaign. The exact resource type and limit being checked can be
     * inferred from accountLimitType, and the numeric id of the
     * Campaign involved is given by enclosingId.
     *
     * Generated from protobuf enum <code>CAMPAIGN_LIMIT = 3;</code>
     */
    const CAMPAIGN_LIMIT = 3;
    /**
     * Indicates that this request would exceed the number of allowed resources
     * in an ad group. The exact resource type and limit being checked can be
     * inferred from accountLimitType, and the numeric id of the
     * ad group involved is given by enclosingId.
     *
     * Generated from protobuf enum <code>ADGROUP_LIMIT = 4;</code>
     */
    const ADGROUP_LIMIT = 4;
    /**
     * Indicates that this request would exceed the number of allowed resources
     * in an ad group ad. The exact resource type and limit being checked can
     * be inferred from accountLimitType, and the enclosingId
     * contains the ad group id followed by the ad id, separated by a single
     * comma (,).
     *
     * Generated from protobuf enum <code>AD_GROUP_AD_LIMIT = 5;</code>
     */
    const AD_GROUP_AD_LIMIT = 5;
    /**
     * Indicates that this request would exceed the number of allowed resources
     * in an ad group criterion. The exact resource type and limit being checked
     * can be inferred from accountLimitType, and the
     * enclosingId contains the ad group id followed by the
     * criterion id, separated by a single comma (,).
     *
     * Generated from protobuf enum <code>AD_GROUP_CRITERION_LIMIT = 6;</code>
     */
    const AD_GROUP_CRITERION_LIMIT = 6;
    /**
     * Indicates that this request would exceed the number of allowed resources
     * in this shared set. The exact resource type and limit being checked can
     * be inferred from accountLimitType, and the numeric id of the
     * shared set involved is given by enclosingId.
     *
     * Generated from protobuf enum <code>SHARED_SET_LIMIT = 7;</code>
     */
    const SHARED_SET_LIMIT = 7;
    /**
     * Exceeds a limit related to a matching function.
     *
     * Generated from protobuf enum <code>MATCHING_FUNCTION_LIMIT = 8;</code>
     */
    const MATCHING_FUNCTION_LIMIT = 8;
    /**
     * The response for this request would exceed the maximum number of rows
     * that can be returned.
     *
     * Generated from protobuf enum <code>RESPONSE_ROW_LIMIT_EXCEEDED = 9;</code>
     */
    const RESPONSE_ROW_LIMIT_EXCEEDED = 9;
    /**
     * This request would exceed a limit on the number of allowed resources.
     * The details of which type of limit was exceeded will eventually be
     * returned in ErrorDetails.
     *
     * Generated from protobuf enum <code>RESOURCE_LIMIT = 10;</code>
     */
    const RESOURCE_LIMIT = 10;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::ACCOUNT_LIMIT => 'ACCOUNT_LIMIT',
        self::CAMPAIGN_LIMIT => 'CAMPAIGN_LIMIT',
        self::ADGROUP_LIMIT => 'ADGROUP_LIMIT',
        self::AD_GROUP_AD_LIMIT => 'AD_GROUP_AD_LIMIT',
        self::AD_GROUP_CRITERION_LIMIT => 'AD_GROUP_CRITERION_LIMIT',
        self::SHARED_SET_LIMIT => 'SHARED_SET_LIMIT',
        self::MATCHING_FUNCTION_LIMIT => 'MATCHING_FUNCTION_LIMIT',
        self::RESPONSE_ROW_LIMIT_EXCEEDED => 'RESPONSE_ROW_LIMIT_EXCEEDED',
        self::RESOURCE_LIMIT => 'RESOURCE_LIMIT',
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
class_alias(ResourceCountLimitExceededError::class, \Google\Ads\GoogleAds\V3\Errors\ResourceCountLimitExceededErrorEnum_ResourceCountLimitExceededError::class);

