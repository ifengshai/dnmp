<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v6/enums/data_driven_model_status.proto

namespace Google\Ads\GoogleAds\V6\Enums\DataDrivenModelStatusEnum;

use UnexpectedValueException;

/**
 * Enumerates data driven model statuses.
 *
 * Protobuf type <code>google.ads.googleads.v6.enums.DataDrivenModelStatusEnum.DataDrivenModelStatus</code>
 */
class DataDrivenModelStatus
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
     * The data driven model is available.
     *
     * Generated from protobuf enum <code>AVAILABLE = 2;</code>
     */
    const AVAILABLE = 2;
    /**
     * The data driven model is stale. It hasn't been updated for at least 7
     * days. It is still being used, but will become expired if it does not get
     * updated for 30 days.
     *
     * Generated from protobuf enum <code>STALE = 3;</code>
     */
    const STALE = 3;
    /**
     * The data driven model expired. It hasn't been updated for at least 30
     * days and cannot be used. Most commonly this is because there hasn't been
     * the required number of events in a recent 30-day period.
     *
     * Generated from protobuf enum <code>EXPIRED = 4;</code>
     */
    const EXPIRED = 4;
    /**
     * The data driven model has never been generated. Most commonly this is
     * because there has never been the required number of events in any 30-day
     * period.
     *
     * Generated from protobuf enum <code>NEVER_GENERATED = 5;</code>
     */
    const NEVER_GENERATED = 5;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::AVAILABLE => 'AVAILABLE',
        self::STALE => 'STALE',
        self::EXPIRED => 'EXPIRED',
        self::NEVER_GENERATED => 'NEVER_GENERATED',
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
class_alias(DataDrivenModelStatus::class, \Google\Ads\GoogleAds\V6\Enums\DataDrivenModelStatusEnum_DataDrivenModelStatus::class);

