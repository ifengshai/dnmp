<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/enums/mutate_job_status.proto

namespace Google\Ads\GoogleAds\V3\Enums\MutateJobStatusEnum;

use UnexpectedValueException;

/**
 * The mutate job statuses.
 *
 * Protobuf type <code>google.ads.googleads.v3.enums.MutateJobStatusEnum.MutateJobStatus</code>
 */
class MutateJobStatus
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
     * The job is not currently running.
     *
     * Generated from protobuf enum <code>PENDING = 2;</code>
     */
    const PENDING = 2;
    /**
     * The job is running.
     *
     * Generated from protobuf enum <code>RUNNING = 3;</code>
     */
    const RUNNING = 3;
    /**
     * The job is done.
     *
     * Generated from protobuf enum <code>DONE = 4;</code>
     */
    const DONE = 4;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::PENDING => 'PENDING',
        self::RUNNING => 'RUNNING',
        self::DONE => 'DONE',
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
class_alias(MutateJobStatus::class, \Google\Ads\GoogleAds\V3\Enums\MutateJobStatusEnum_MutateJobStatus::class);

