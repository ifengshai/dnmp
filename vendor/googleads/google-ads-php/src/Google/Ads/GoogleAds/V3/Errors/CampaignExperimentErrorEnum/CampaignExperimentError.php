<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/errors/campaign_experiment_error.proto

namespace Google\Ads\GoogleAds\V3\Errors\CampaignExperimentErrorEnum;

use UnexpectedValueException;

/**
 * Enum describing possible campaign experiment errors.
 *
 * Protobuf type <code>google.ads.googleads.v3.errors.CampaignExperimentErrorEnum.CampaignExperimentError</code>
 */
class CampaignExperimentError
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
     * An active campaign or experiment with this name already exists.
     *
     * Generated from protobuf enum <code>DUPLICATE_NAME = 2;</code>
     */
    const DUPLICATE_NAME = 2;
    /**
     * Experiment cannot be updated from the current state to the
     * requested target state. For example, an experiment can only graduate
     * if its status is ENABLED.
     *
     * Generated from protobuf enum <code>INVALID_TRANSITION = 3;</code>
     */
    const INVALID_TRANSITION = 3;
    /**
     * Cannot create an experiment from a campaign using an explicitly shared
     * budget.
     *
     * Generated from protobuf enum <code>CANNOT_CREATE_EXPERIMENT_WITH_SHARED_BUDGET = 4;</code>
     */
    const CANNOT_CREATE_EXPERIMENT_WITH_SHARED_BUDGET = 4;
    /**
     * Cannot create an experiment for a removed base campaign.
     *
     * Generated from protobuf enum <code>CANNOT_CREATE_EXPERIMENT_FOR_REMOVED_BASE_CAMPAIGN = 5;</code>
     */
    const CANNOT_CREATE_EXPERIMENT_FOR_REMOVED_BASE_CAMPAIGN = 5;
    /**
     * Cannot create an experiment from a draft, which has a status other than
     * proposed.
     *
     * Generated from protobuf enum <code>CANNOT_CREATE_EXPERIMENT_FOR_NON_PROPOSED_DRAFT = 6;</code>
     */
    const CANNOT_CREATE_EXPERIMENT_FOR_NON_PROPOSED_DRAFT = 6;
    /**
     * This customer is not allowed to create an experiment.
     *
     * Generated from protobuf enum <code>CUSTOMER_CANNOT_CREATE_EXPERIMENT = 7;</code>
     */
    const CUSTOMER_CANNOT_CREATE_EXPERIMENT = 7;
    /**
     * This campaign is not allowed to create an experiment.
     *
     * Generated from protobuf enum <code>CAMPAIGN_CANNOT_CREATE_EXPERIMENT = 8;</code>
     */
    const CAMPAIGN_CANNOT_CREATE_EXPERIMENT = 8;
    /**
     * Trying to set an experiment duration which overlaps with another
     * experiment.
     *
     * Generated from protobuf enum <code>EXPERIMENT_DURATIONS_MUST_NOT_OVERLAP = 9;</code>
     */
    const EXPERIMENT_DURATIONS_MUST_NOT_OVERLAP = 9;
    /**
     * All non-removed experiments must start and end within their campaign's
     * duration.
     *
     * Generated from protobuf enum <code>EXPERIMENT_DURATION_MUST_BE_WITHIN_CAMPAIGN_DURATION = 10;</code>
     */
    const EXPERIMENT_DURATION_MUST_BE_WITHIN_CAMPAIGN_DURATION = 10;
    /**
     * The experiment cannot be modified because its status is in a terminal
     * state, such as REMOVED.
     *
     * Generated from protobuf enum <code>CANNOT_MUTATE_EXPERIMENT_DUE_TO_STATUS = 11;</code>
     */
    const CANNOT_MUTATE_EXPERIMENT_DUE_TO_STATUS = 11;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::DUPLICATE_NAME => 'DUPLICATE_NAME',
        self::INVALID_TRANSITION => 'INVALID_TRANSITION',
        self::CANNOT_CREATE_EXPERIMENT_WITH_SHARED_BUDGET => 'CANNOT_CREATE_EXPERIMENT_WITH_SHARED_BUDGET',
        self::CANNOT_CREATE_EXPERIMENT_FOR_REMOVED_BASE_CAMPAIGN => 'CANNOT_CREATE_EXPERIMENT_FOR_REMOVED_BASE_CAMPAIGN',
        self::CANNOT_CREATE_EXPERIMENT_FOR_NON_PROPOSED_DRAFT => 'CANNOT_CREATE_EXPERIMENT_FOR_NON_PROPOSED_DRAFT',
        self::CUSTOMER_CANNOT_CREATE_EXPERIMENT => 'CUSTOMER_CANNOT_CREATE_EXPERIMENT',
        self::CAMPAIGN_CANNOT_CREATE_EXPERIMENT => 'CAMPAIGN_CANNOT_CREATE_EXPERIMENT',
        self::EXPERIMENT_DURATIONS_MUST_NOT_OVERLAP => 'EXPERIMENT_DURATIONS_MUST_NOT_OVERLAP',
        self::EXPERIMENT_DURATION_MUST_BE_WITHIN_CAMPAIGN_DURATION => 'EXPERIMENT_DURATION_MUST_BE_WITHIN_CAMPAIGN_DURATION',
        self::CANNOT_MUTATE_EXPERIMENT_DUE_TO_STATUS => 'CANNOT_MUTATE_EXPERIMENT_DUE_TO_STATUS',
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
class_alias(CampaignExperimentError::class, \Google\Ads\GoogleAds\V3\Errors\CampaignExperimentErrorEnum_CampaignExperimentError::class);

