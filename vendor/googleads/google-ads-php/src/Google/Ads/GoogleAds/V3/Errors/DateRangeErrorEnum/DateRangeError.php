<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/errors/date_range_error.proto

namespace Google\Ads\GoogleAds\V3\Errors\DateRangeErrorEnum;

use UnexpectedValueException;

/**
 * Enum describing possible date range errors.
 *
 * Protobuf type <code>google.ads.googleads.v3.errors.DateRangeErrorEnum.DateRangeError</code>
 */
class DateRangeError
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
     * Invalid date.
     *
     * Generated from protobuf enum <code>INVALID_DATE = 2;</code>
     */
    const INVALID_DATE = 2;
    /**
     * The start date was after the end date.
     *
     * Generated from protobuf enum <code>START_DATE_AFTER_END_DATE = 3;</code>
     */
    const START_DATE_AFTER_END_DATE = 3;
    /**
     * Cannot set date to past time
     *
     * Generated from protobuf enum <code>CANNOT_SET_DATE_TO_PAST = 4;</code>
     */
    const CANNOT_SET_DATE_TO_PAST = 4;
    /**
     * A date was used that is past the system "last" date.
     *
     * Generated from protobuf enum <code>AFTER_MAXIMUM_ALLOWABLE_DATE = 5;</code>
     */
    const AFTER_MAXIMUM_ALLOWABLE_DATE = 5;
    /**
     * Trying to change start date on a resource that has started.
     *
     * Generated from protobuf enum <code>CANNOT_MODIFY_START_DATE_IF_ALREADY_STARTED = 6;</code>
     */
    const CANNOT_MODIFY_START_DATE_IF_ALREADY_STARTED = 6;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::INVALID_DATE => 'INVALID_DATE',
        self::START_DATE_AFTER_END_DATE => 'START_DATE_AFTER_END_DATE',
        self::CANNOT_SET_DATE_TO_PAST => 'CANNOT_SET_DATE_TO_PAST',
        self::AFTER_MAXIMUM_ALLOWABLE_DATE => 'AFTER_MAXIMUM_ALLOWABLE_DATE',
        self::CANNOT_MODIFY_START_DATE_IF_ALREADY_STARTED => 'CANNOT_MODIFY_START_DATE_IF_ALREADY_STARTED',
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
class_alias(DateRangeError::class, \Google\Ads\GoogleAds\V3\Errors\DateRangeErrorEnum_DateRangeError::class);

