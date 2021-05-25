<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/enums/campaign_serving_status.proto

namespace Google\Ads\GoogleAds\V4\Enums\CampaignServingStatusEnum;

use UnexpectedValueException;

/**
 * Possible serving statuses of a campaign.
 *
 * Protobuf type <code>google.ads.googleads.v4.enums.CampaignServingStatusEnum.CampaignServingStatus</code>
 */
class CampaignServingStatus
{
    /**
     * No value has been specified.
     *
     * Generated from protobuf enum <code>UNSPECIFIED = 0;</code>
     */
    const UNSPECIFIED = 0;
    /**
     * The received value is not known in this version.
     * This is a response-only value.
     *
     * Generated from protobuf enum <code>UNKNOWN = 1;</code>
     */
    const UNKNOWN = 1;
    /**
     * Serving.
     *
     * Generated from protobuf enum <code>SERVING = 2;</code>
     */
    const SERVING = 2;
    /**
     * None.
     *
     * Generated from protobuf enum <code>NONE = 3;</code>
     */
    const NONE = 3;
    /**
     * Ended.
     *
     * Generated from protobuf enum <code>ENDED = 4;</code>
     */
    const ENDED = 4;
    /**
     * Pending.
     *
     * Generated from protobuf enum <code>PENDING = 5;</code>
     */
    const PENDING = 5;
    /**
     * Suspended.
     *
     * Generated from protobuf enum <code>SUSPENDED = 6;</code>
     */
    const SUSPENDED = 6;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::SERVING => 'SERVING',
        self::NONE => 'NONE',
        self::ENDED => 'ENDED',
        self::PENDING => 'PENDING',
        self::SUSPENDED => 'SUSPENDED',
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
class_alias(CampaignServingStatus::class, \Google\Ads\GoogleAds\V4\Enums\CampaignServingStatusEnum_CampaignServingStatus::class);

