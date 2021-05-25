<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v6/enums/reach_plan_network.proto

namespace Google\Ads\GoogleAds\V6\Enums\ReachPlanNetworkEnum;

use UnexpectedValueException;

/**
 * Possible plannable network values.
 *
 * Protobuf type <code>google.ads.googleads.v6.enums.ReachPlanNetworkEnum.ReachPlanNetwork</code>
 */
class ReachPlanNetwork
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
     * YouTube network.
     *
     * Generated from protobuf enum <code>YOUTUBE = 2;</code>
     */
    const YOUTUBE = 2;
    /**
     * Google Video Partners (GVP) network.
     *
     * Generated from protobuf enum <code>GOOGLE_VIDEO_PARTNERS = 3;</code>
     */
    const GOOGLE_VIDEO_PARTNERS = 3;
    /**
     * A combination of the YouTube network and the Google Video Partners
     * network.
     *
     * Generated from protobuf enum <code>YOUTUBE_AND_GOOGLE_VIDEO_PARTNERS = 4;</code>
     */
    const YOUTUBE_AND_GOOGLE_VIDEO_PARTNERS = 4;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::YOUTUBE => 'YOUTUBE',
        self::GOOGLE_VIDEO_PARTNERS => 'GOOGLE_VIDEO_PARTNERS',
        self::YOUTUBE_AND_GOOGLE_VIDEO_PARTNERS => 'YOUTUBE_AND_GOOGLE_VIDEO_PARTNERS',
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
class_alias(ReachPlanNetwork::class, \Google\Ads\GoogleAds\V6\Enums\ReachPlanNetworkEnum_ReachPlanNetwork::class);

