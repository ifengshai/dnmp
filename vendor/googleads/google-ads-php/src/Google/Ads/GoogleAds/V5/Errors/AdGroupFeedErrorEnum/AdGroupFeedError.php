<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/errors/ad_group_feed_error.proto

namespace Google\Ads\GoogleAds\V5\Errors\AdGroupFeedErrorEnum;

use UnexpectedValueException;

/**
 * Enum describing possible ad group feed errors.
 *
 * Protobuf type <code>google.ads.googleads.v5.errors.AdGroupFeedErrorEnum.AdGroupFeedError</code>
 */
class AdGroupFeedError
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
     * An active feed already exists for this ad group and place holder type.
     *
     * Generated from protobuf enum <code>FEED_ALREADY_EXISTS_FOR_PLACEHOLDER_TYPE = 2;</code>
     */
    const FEED_ALREADY_EXISTS_FOR_PLACEHOLDER_TYPE = 2;
    /**
     * The specified feed is removed.
     *
     * Generated from protobuf enum <code>CANNOT_CREATE_FOR_REMOVED_FEED = 3;</code>
     */
    const CANNOT_CREATE_FOR_REMOVED_FEED = 3;
    /**
     * The AdGroupFeed already exists. UPDATE operation should be used to modify
     * the existing AdGroupFeed.
     *
     * Generated from protobuf enum <code>ADGROUP_FEED_ALREADY_EXISTS = 4;</code>
     */
    const ADGROUP_FEED_ALREADY_EXISTS = 4;
    /**
     * Cannot operate on removed AdGroupFeed.
     *
     * Generated from protobuf enum <code>CANNOT_OPERATE_ON_REMOVED_ADGROUP_FEED = 5;</code>
     */
    const CANNOT_OPERATE_ON_REMOVED_ADGROUP_FEED = 5;
    /**
     * Invalid placeholder type.
     *
     * Generated from protobuf enum <code>INVALID_PLACEHOLDER_TYPE = 6;</code>
     */
    const INVALID_PLACEHOLDER_TYPE = 6;
    /**
     * Feed mapping for this placeholder type does not exist.
     *
     * Generated from protobuf enum <code>MISSING_FEEDMAPPING_FOR_PLACEHOLDER_TYPE = 7;</code>
     */
    const MISSING_FEEDMAPPING_FOR_PLACEHOLDER_TYPE = 7;
    /**
     * Location AdGroupFeeds cannot be created unless there is a location
     * CustomerFeed for the specified feed.
     *
     * Generated from protobuf enum <code>NO_EXISTING_LOCATION_CUSTOMER_FEED = 8;</code>
     */
    const NO_EXISTING_LOCATION_CUSTOMER_FEED = 8;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::FEED_ALREADY_EXISTS_FOR_PLACEHOLDER_TYPE => 'FEED_ALREADY_EXISTS_FOR_PLACEHOLDER_TYPE',
        self::CANNOT_CREATE_FOR_REMOVED_FEED => 'CANNOT_CREATE_FOR_REMOVED_FEED',
        self::ADGROUP_FEED_ALREADY_EXISTS => 'ADGROUP_FEED_ALREADY_EXISTS',
        self::CANNOT_OPERATE_ON_REMOVED_ADGROUP_FEED => 'CANNOT_OPERATE_ON_REMOVED_ADGROUP_FEED',
        self::INVALID_PLACEHOLDER_TYPE => 'INVALID_PLACEHOLDER_TYPE',
        self::MISSING_FEEDMAPPING_FOR_PLACEHOLDER_TYPE => 'MISSING_FEEDMAPPING_FOR_PLACEHOLDER_TYPE',
        self::NO_EXISTING_LOCATION_CUSTOMER_FEED => 'NO_EXISTING_LOCATION_CUSTOMER_FEED',
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
class_alias(AdGroupFeedError::class, \Google\Ads\GoogleAds\V5\Errors\AdGroupFeedErrorEnum_AdGroupFeedError::class);

