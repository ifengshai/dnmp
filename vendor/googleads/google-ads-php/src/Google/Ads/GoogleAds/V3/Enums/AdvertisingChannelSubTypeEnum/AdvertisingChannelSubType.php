<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/enums/advertising_channel_sub_type.proto

namespace Google\Ads\GoogleAds\V3\Enums\AdvertisingChannelSubTypeEnum;

use UnexpectedValueException;

/**
 * Enum describing the different channel subtypes.
 *
 * Protobuf type <code>google.ads.googleads.v3.enums.AdvertisingChannelSubTypeEnum.AdvertisingChannelSubType</code>
 */
class AdvertisingChannelSubType
{
    /**
     * Not specified.
     *
     * Generated from protobuf enum <code>UNSPECIFIED = 0;</code>
     */
    const UNSPECIFIED = 0;
    /**
     * Used as a return value only. Represents value unknown in this version.
     *
     * Generated from protobuf enum <code>UNKNOWN = 1;</code>
     */
    const UNKNOWN = 1;
    /**
     * Mobile app campaigns for Search.
     *
     * Generated from protobuf enum <code>SEARCH_MOBILE_APP = 2;</code>
     */
    const SEARCH_MOBILE_APP = 2;
    /**
     * Mobile app campaigns for Display.
     *
     * Generated from protobuf enum <code>DISPLAY_MOBILE_APP = 3;</code>
     */
    const DISPLAY_MOBILE_APP = 3;
    /**
     * AdWords express campaigns for search.
     *
     * Generated from protobuf enum <code>SEARCH_EXPRESS = 4;</code>
     */
    const SEARCH_EXPRESS = 4;
    /**
     * AdWords Express campaigns for display.
     *
     * Generated from protobuf enum <code>DISPLAY_EXPRESS = 5;</code>
     */
    const DISPLAY_EXPRESS = 5;
    /**
     * Smart Shopping campaigns.
     *
     * Generated from protobuf enum <code>SHOPPING_SMART_ADS = 6;</code>
     */
    const SHOPPING_SMART_ADS = 6;
    /**
     * Gmail Ad campaigns.
     *
     * Generated from protobuf enum <code>DISPLAY_GMAIL_AD = 7;</code>
     */
    const DISPLAY_GMAIL_AD = 7;
    /**
     * Smart display campaigns.
     *
     * Generated from protobuf enum <code>DISPLAY_SMART_CAMPAIGN = 8;</code>
     */
    const DISPLAY_SMART_CAMPAIGN = 8;
    /**
     * Video Outstream campaigns.
     *
     * Generated from protobuf enum <code>VIDEO_OUTSTREAM = 9;</code>
     */
    const VIDEO_OUTSTREAM = 9;
    /**
     * Video TrueView for Action campaigns.
     *
     * Generated from protobuf enum <code>VIDEO_ACTION = 10;</code>
     */
    const VIDEO_ACTION = 10;
    /**
     * Video campaigns with non-skippable video ads.
     *
     * Generated from protobuf enum <code>VIDEO_NON_SKIPPABLE = 11;</code>
     */
    const VIDEO_NON_SKIPPABLE = 11;
    /**
     * App Campaign that allows you to easily promote your Android or iOS app
     * across Google's top properties including Search, Play, YouTube, and the
     * Google Display Network.
     *
     * Generated from protobuf enum <code>APP_CAMPAIGN = 12;</code>
     */
    const APP_CAMPAIGN = 12;
    /**
     * App Campaign for engagement, focused on driving re-engagement with the
     * app across several of Google’s top properties including Search, YouTube,
     * and the Google Display Network.
     *
     * Generated from protobuf enum <code>APP_CAMPAIGN_FOR_ENGAGEMENT = 13;</code>
     */
    const APP_CAMPAIGN_FOR_ENGAGEMENT = 13;
    /**
     * Shopping Comparison Listing campaigns.
     *
     * Generated from protobuf enum <code>SHOPPING_COMPARISON_LISTING_ADS = 15;</code>
     */
    const SHOPPING_COMPARISON_LISTING_ADS = 15;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::SEARCH_MOBILE_APP => 'SEARCH_MOBILE_APP',
        self::DISPLAY_MOBILE_APP => 'DISPLAY_MOBILE_APP',
        self::SEARCH_EXPRESS => 'SEARCH_EXPRESS',
        self::DISPLAY_EXPRESS => 'DISPLAY_EXPRESS',
        self::SHOPPING_SMART_ADS => 'SHOPPING_SMART_ADS',
        self::DISPLAY_GMAIL_AD => 'DISPLAY_GMAIL_AD',
        self::DISPLAY_SMART_CAMPAIGN => 'DISPLAY_SMART_CAMPAIGN',
        self::VIDEO_OUTSTREAM => 'VIDEO_OUTSTREAM',
        self::VIDEO_ACTION => 'VIDEO_ACTION',
        self::VIDEO_NON_SKIPPABLE => 'VIDEO_NON_SKIPPABLE',
        self::APP_CAMPAIGN => 'APP_CAMPAIGN',
        self::APP_CAMPAIGN_FOR_ENGAGEMENT => 'APP_CAMPAIGN_FOR_ENGAGEMENT',
        self::SHOPPING_COMPARISON_LISTING_ADS => 'SHOPPING_COMPARISON_LISTING_ADS',
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
class_alias(AdvertisingChannelSubType::class, \Google\Ads\GoogleAds\V3\Enums\AdvertisingChannelSubTypeEnum_AdvertisingChannelSubType::class);

