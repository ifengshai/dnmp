<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/enums/offline_user_data_job_failure_reason.proto

namespace Google\Ads\GoogleAds\V3\Enums\OfflineUserDataJobFailureReasonEnum;

use UnexpectedValueException;

/**
 * The failure reason of an offline user data job.
 *
 * Protobuf type <code>google.ads.googleads.v3.enums.OfflineUserDataJobFailureReasonEnum.OfflineUserDataJobFailureReason</code>
 */
class OfflineUserDataJobFailureReason
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
     * The matched transactions are insufficient.
     *
     * Generated from protobuf enum <code>INSUFFICIENT_MATCHED_TRANSACTIONS = 2;</code>
     */
    const INSUFFICIENT_MATCHED_TRANSACTIONS = 2;
    /**
     * The uploaded transactions are insufficient.
     *
     * Generated from protobuf enum <code>INSUFFICIENT_TRANSACTIONS = 3;</code>
     */
    const INSUFFICIENT_TRANSACTIONS = 3;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::INSUFFICIENT_MATCHED_TRANSACTIONS => 'INSUFFICIENT_MATCHED_TRANSACTIONS',
        self::INSUFFICIENT_TRANSACTIONS => 'INSUFFICIENT_TRANSACTIONS',
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
class_alias(OfflineUserDataJobFailureReason::class, \Google\Ads\GoogleAds\V3\Enums\OfflineUserDataJobFailureReasonEnum_OfflineUserDataJobFailureReason::class);

