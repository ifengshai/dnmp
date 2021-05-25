<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v6/enums/placement_type.proto

namespace Google\Ads\GoogleAds\V6\Enums\PlacementTypeEnum;

use UnexpectedValueException;

/**
 * Possible placement types for a feed mapping.
 *
 * Protobuf type <code>google.ads.googleads.v6.enums.PlacementTypeEnum.PlacementType</code>
 */
class PlacementType
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
     * Websites(e.g. 'www.flowers4sale.com').
     *
     * Generated from protobuf enum <code>WEBSITE = 2;</code>
     */
    const WEBSITE = 2;
    /**
     * Mobile application categories(e.g. 'Games').
     *
     * Generated from protobuf enum <code>MOBILE_APP_CATEGORY = 3;</code>
     */
    const MOBILE_APP_CATEGORY = 3;
    /**
     * mobile applications(e.g. 'mobileapp::2-com.whatsthewordanswers').
     *
     * Generated from protobuf enum <code>MOBILE_APPLICATION = 4;</code>
     */
    const MOBILE_APPLICATION = 4;
    /**
     * YouTube videos(e.g. 'youtube.com/video/wtLJPvx7-ys').
     *
     * Generated from protobuf enum <code>YOUTUBE_VIDEO = 5;</code>
     */
    const YOUTUBE_VIDEO = 5;
    /**
     * YouTube channels(e.g. 'youtube.com::L8ZULXASCc1I_oaOT0NaOQ').
     *
     * Generated from protobuf enum <code>YOUTUBE_CHANNEL = 6;</code>
     */
    const YOUTUBE_CHANNEL = 6;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::WEBSITE => 'WEBSITE',
        self::MOBILE_APP_CATEGORY => 'MOBILE_APP_CATEGORY',
        self::MOBILE_APPLICATION => 'MOBILE_APPLICATION',
        self::YOUTUBE_VIDEO => 'YOUTUBE_VIDEO',
        self::YOUTUBE_CHANNEL => 'YOUTUBE_CHANNEL',
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
class_alias(PlacementType::class, \Google\Ads\GoogleAds\V6\Enums\PlacementTypeEnum_PlacementType::class);

