<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/errors/multiplier_error.proto

namespace Google\Ads\GoogleAds\V4\Errors\MultiplierErrorEnum;

use UnexpectedValueException;

/**
 * Enum describing possible multiplier errors.
 *
 * Protobuf type <code>google.ads.googleads.v4.errors.MultiplierErrorEnum.MultiplierError</code>
 */
class MultiplierError
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
     * Multiplier value is too high
     *
     * Generated from protobuf enum <code>MULTIPLIER_TOO_HIGH = 2;</code>
     */
    const MULTIPLIER_TOO_HIGH = 2;
    /**
     * Multiplier value is too low
     *
     * Generated from protobuf enum <code>MULTIPLIER_TOO_LOW = 3;</code>
     */
    const MULTIPLIER_TOO_LOW = 3;
    /**
     * Too many fractional digits
     *
     * Generated from protobuf enum <code>TOO_MANY_FRACTIONAL_DIGITS = 4;</code>
     */
    const TOO_MANY_FRACTIONAL_DIGITS = 4;
    /**
     * A multiplier cannot be set for this bidding strategy
     *
     * Generated from protobuf enum <code>MULTIPLIER_NOT_ALLOWED_FOR_BIDDING_STRATEGY = 5;</code>
     */
    const MULTIPLIER_NOT_ALLOWED_FOR_BIDDING_STRATEGY = 5;
    /**
     * A multiplier cannot be set when there is no base bid (e.g., content max
     * cpc)
     *
     * Generated from protobuf enum <code>MULTIPLIER_NOT_ALLOWED_WHEN_BASE_BID_IS_MISSING = 6;</code>
     */
    const MULTIPLIER_NOT_ALLOWED_WHEN_BASE_BID_IS_MISSING = 6;
    /**
     * A bid multiplier must be specified
     *
     * Generated from protobuf enum <code>NO_MULTIPLIER_SPECIFIED = 7;</code>
     */
    const NO_MULTIPLIER_SPECIFIED = 7;
    /**
     * Multiplier causes bid to exceed daily budget
     *
     * Generated from protobuf enum <code>MULTIPLIER_CAUSES_BID_TO_EXCEED_DAILY_BUDGET = 8;</code>
     */
    const MULTIPLIER_CAUSES_BID_TO_EXCEED_DAILY_BUDGET = 8;
    /**
     * Multiplier causes bid to exceed monthly budget
     *
     * Generated from protobuf enum <code>MULTIPLIER_CAUSES_BID_TO_EXCEED_MONTHLY_BUDGET = 9;</code>
     */
    const MULTIPLIER_CAUSES_BID_TO_EXCEED_MONTHLY_BUDGET = 9;
    /**
     * Multiplier causes bid to exceed custom budget
     *
     * Generated from protobuf enum <code>MULTIPLIER_CAUSES_BID_TO_EXCEED_CUSTOM_BUDGET = 10;</code>
     */
    const MULTIPLIER_CAUSES_BID_TO_EXCEED_CUSTOM_BUDGET = 10;
    /**
     * Multiplier causes bid to exceed maximum allowed bid
     *
     * Generated from protobuf enum <code>MULTIPLIER_CAUSES_BID_TO_EXCEED_MAX_ALLOWED_BID = 11;</code>
     */
    const MULTIPLIER_CAUSES_BID_TO_EXCEED_MAX_ALLOWED_BID = 11;
    /**
     * Multiplier causes bid to become less than the minimum bid allowed
     *
     * Generated from protobuf enum <code>BID_LESS_THAN_MIN_ALLOWED_BID_WITH_MULTIPLIER = 12;</code>
     */
    const BID_LESS_THAN_MIN_ALLOWED_BID_WITH_MULTIPLIER = 12;
    /**
     * Multiplier type (cpc vs. cpm) needs to match campaign's bidding strategy
     *
     * Generated from protobuf enum <code>MULTIPLIER_AND_BIDDING_STRATEGY_TYPE_MISMATCH = 13;</code>
     */
    const MULTIPLIER_AND_BIDDING_STRATEGY_TYPE_MISMATCH = 13;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::MULTIPLIER_TOO_HIGH => 'MULTIPLIER_TOO_HIGH',
        self::MULTIPLIER_TOO_LOW => 'MULTIPLIER_TOO_LOW',
        self::TOO_MANY_FRACTIONAL_DIGITS => 'TOO_MANY_FRACTIONAL_DIGITS',
        self::MULTIPLIER_NOT_ALLOWED_FOR_BIDDING_STRATEGY => 'MULTIPLIER_NOT_ALLOWED_FOR_BIDDING_STRATEGY',
        self::MULTIPLIER_NOT_ALLOWED_WHEN_BASE_BID_IS_MISSING => 'MULTIPLIER_NOT_ALLOWED_WHEN_BASE_BID_IS_MISSING',
        self::NO_MULTIPLIER_SPECIFIED => 'NO_MULTIPLIER_SPECIFIED',
        self::MULTIPLIER_CAUSES_BID_TO_EXCEED_DAILY_BUDGET => 'MULTIPLIER_CAUSES_BID_TO_EXCEED_DAILY_BUDGET',
        self::MULTIPLIER_CAUSES_BID_TO_EXCEED_MONTHLY_BUDGET => 'MULTIPLIER_CAUSES_BID_TO_EXCEED_MONTHLY_BUDGET',
        self::MULTIPLIER_CAUSES_BID_TO_EXCEED_CUSTOM_BUDGET => 'MULTIPLIER_CAUSES_BID_TO_EXCEED_CUSTOM_BUDGET',
        self::MULTIPLIER_CAUSES_BID_TO_EXCEED_MAX_ALLOWED_BID => 'MULTIPLIER_CAUSES_BID_TO_EXCEED_MAX_ALLOWED_BID',
        self::BID_LESS_THAN_MIN_ALLOWED_BID_WITH_MULTIPLIER => 'BID_LESS_THAN_MIN_ALLOWED_BID_WITH_MULTIPLIER',
        self::MULTIPLIER_AND_BIDDING_STRATEGY_TYPE_MISMATCH => 'MULTIPLIER_AND_BIDDING_STRATEGY_TYPE_MISMATCH',
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
class_alias(MultiplierError::class, \Google\Ads\GoogleAds\V4\Errors\MultiplierErrorEnum_MultiplierError::class);

