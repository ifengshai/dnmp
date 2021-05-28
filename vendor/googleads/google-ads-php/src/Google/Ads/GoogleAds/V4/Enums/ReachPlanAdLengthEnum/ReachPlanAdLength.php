<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/enums/reach_plan_ad_length.proto

namespace Google\Ads\GoogleAds\V4\Enums\ReachPlanAdLengthEnum;

use UnexpectedValueException;

/**
 * Possible ad length values.
 *
 * Protobuf type <code>google.ads.googleads.v4.enums.ReachPlanAdLengthEnum.ReachPlanAdLength</code>
 */
class ReachPlanAdLength
{
    /**
     * Not specified.
     *
     * Generated from protobuf enum <code>UNSPECIFIED = 0;</code>
     */
    const UNSPECIFIED = 0;
    /**
     * The value is unknown in this version.
     *
     * Generated from protobuf enum <code>UNKNOWN = 1;</code>
     */
    const UNKNOWN = 1;
    /**
     * 6 seconds long ad.
     *
     * Generated from protobuf enum <code>SIX_SECONDS = 2;</code>
     */
    const SIX_SECONDS = 2;
    /**
     * 15 or 20 seconds long ad.
     *
     * Generated from protobuf enum <code>FIFTEEN_OR_TWENTY_SECONDS = 3;</code>
     */
    const FIFTEEN_OR_TWENTY_SECONDS = 3;
    /**
     * More than 20 seconds long ad.
     *
     * Generated from protobuf enum <code>TWENTY_SECONDS_OR_MORE = 4;</code>
     */
    const TWENTY_SECONDS_OR_MORE = 4;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::SIX_SECONDS => 'SIX_SECONDS',
        self::FIFTEEN_OR_TWENTY_SECONDS => 'FIFTEEN_OR_TWENTY_SECONDS',
        self::TWENTY_SECONDS_OR_MORE => 'TWENTY_SECONDS_OR_MORE',
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
class_alias(ReachPlanAdLength::class, \Google\Ads\GoogleAds\V4\Enums\ReachPlanAdLengthEnum_ReachPlanAdLength::class);

