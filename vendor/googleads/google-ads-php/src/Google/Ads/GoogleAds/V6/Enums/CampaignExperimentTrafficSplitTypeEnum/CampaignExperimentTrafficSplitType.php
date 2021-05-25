<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v6/enums/campaign_experiment_traffic_split_type.proto

namespace Google\Ads\GoogleAds\V6\Enums\CampaignExperimentTrafficSplitTypeEnum;

use UnexpectedValueException;

/**
 * Enum of strategies for splitting traffic between base and experiment
 * campaigns in campaign experiment.
 *
 * Protobuf type <code>google.ads.googleads.v6.enums.CampaignExperimentTrafficSplitTypeEnum.CampaignExperimentTrafficSplitType</code>
 */
class CampaignExperimentTrafficSplitType
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
     * Traffic is randomly assigned to the base or experiment arm for each
     * query, independent of previous assignments for the same user.
     *
     * Generated from protobuf enum <code>RANDOM_QUERY = 2;</code>
     */
    const RANDOM_QUERY = 2;
    /**
     * Traffic is split using cookies to keep users in the same arm (base or
     * experiment) of the experiment.
     *
     * Generated from protobuf enum <code>COOKIE = 3;</code>
     */
    const COOKIE = 3;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::RANDOM_QUERY => 'RANDOM_QUERY',
        self::COOKIE => 'COOKIE',
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
class_alias(CampaignExperimentTrafficSplitType::class, \Google\Ads\GoogleAds\V6\Enums\CampaignExperimentTrafficSplitTypeEnum_CampaignExperimentTrafficSplitType::class);

