<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/errors/date_error.proto

namespace Google\Ads\GoogleAds\V4\Errors\DateErrorEnum;

use UnexpectedValueException;

/**
 * Enum describing possible date errors.
 *
 * Protobuf type <code>google.ads.googleads.v4.errors.DateErrorEnum.DateError</code>
 */
class DateError
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
     * Given field values do not correspond to a valid date.
     *
     * Generated from protobuf enum <code>INVALID_FIELD_VALUES_IN_DATE = 2;</code>
     */
    const INVALID_FIELD_VALUES_IN_DATE = 2;
    /**
     * Given field values do not correspond to a valid date time.
     *
     * Generated from protobuf enum <code>INVALID_FIELD_VALUES_IN_DATE_TIME = 3;</code>
     */
    const INVALID_FIELD_VALUES_IN_DATE_TIME = 3;
    /**
     * The string date's format should be yyyy-mm-dd.
     *
     * Generated from protobuf enum <code>INVALID_STRING_DATE = 4;</code>
     */
    const INVALID_STRING_DATE = 4;
    /**
     * The string date time's format should be yyyy-mm-dd hh:mm:ss.ssssss.
     *
     * Generated from protobuf enum <code>INVALID_STRING_DATE_TIME_MICROS = 6;</code>
     */
    const INVALID_STRING_DATE_TIME_MICROS = 6;
    /**
     * The string date time's format should be yyyy-mm-dd hh:mm:ss.
     *
     * Generated from protobuf enum <code>INVALID_STRING_DATE_TIME_SECONDS = 11;</code>
     */
    const INVALID_STRING_DATE_TIME_SECONDS = 11;
    /**
     * The string date time's format should be yyyy-mm-dd hh:mm:ss+|-hh:mm.
     *
     * Generated from protobuf enum <code>INVALID_STRING_DATE_TIME_SECONDS_WITH_OFFSET = 12;</code>
     */
    const INVALID_STRING_DATE_TIME_SECONDS_WITH_OFFSET = 12;
    /**
     * Date is before allowed minimum.
     *
     * Generated from protobuf enum <code>EARLIER_THAN_MINIMUM_DATE = 7;</code>
     */
    const EARLIER_THAN_MINIMUM_DATE = 7;
    /**
     * Date is after allowed maximum.
     *
     * Generated from protobuf enum <code>LATER_THAN_MAXIMUM_DATE = 8;</code>
     */
    const LATER_THAN_MAXIMUM_DATE = 8;
    /**
     * Date range bounds are not in order.
     *
     * Generated from protobuf enum <code>DATE_RANGE_MINIMUM_DATE_LATER_THAN_MAXIMUM_DATE = 9;</code>
     */
    const DATE_RANGE_MINIMUM_DATE_LATER_THAN_MAXIMUM_DATE = 9;
    /**
     * Both dates in range are null.
     *
     * Generated from protobuf enum <code>DATE_RANGE_MINIMUM_AND_MAXIMUM_DATES_BOTH_NULL = 10;</code>
     */
    const DATE_RANGE_MINIMUM_AND_MAXIMUM_DATES_BOTH_NULL = 10;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::INVALID_FIELD_VALUES_IN_DATE => 'INVALID_FIELD_VALUES_IN_DATE',
        self::INVALID_FIELD_VALUES_IN_DATE_TIME => 'INVALID_FIELD_VALUES_IN_DATE_TIME',
        self::INVALID_STRING_DATE => 'INVALID_STRING_DATE',
        self::INVALID_STRING_DATE_TIME_MICROS => 'INVALID_STRING_DATE_TIME_MICROS',
        self::INVALID_STRING_DATE_TIME_SECONDS => 'INVALID_STRING_DATE_TIME_SECONDS',
        self::INVALID_STRING_DATE_TIME_SECONDS_WITH_OFFSET => 'INVALID_STRING_DATE_TIME_SECONDS_WITH_OFFSET',
        self::EARLIER_THAN_MINIMUM_DATE => 'EARLIER_THAN_MINIMUM_DATE',
        self::LATER_THAN_MAXIMUM_DATE => 'LATER_THAN_MAXIMUM_DATE',
        self::DATE_RANGE_MINIMUM_DATE_LATER_THAN_MAXIMUM_DATE => 'DATE_RANGE_MINIMUM_DATE_LATER_THAN_MAXIMUM_DATE',
        self::DATE_RANGE_MINIMUM_AND_MAXIMUM_DATES_BOTH_NULL => 'DATE_RANGE_MINIMUM_AND_MAXIMUM_DATES_BOTH_NULL',
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
class_alias(DateError::class, \Google\Ads\GoogleAds\V4\Errors\DateErrorEnum_DateError::class);

