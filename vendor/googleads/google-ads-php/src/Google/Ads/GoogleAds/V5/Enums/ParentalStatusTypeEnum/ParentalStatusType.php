<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/enums/parental_status_type.proto

namespace Google\Ads\GoogleAds\V5\Enums\ParentalStatusTypeEnum;

use UnexpectedValueException;

/**
 * The type of parental statuses (e.g. not a parent).
 *
 * Protobuf type <code>google.ads.googleads.v5.enums.ParentalStatusTypeEnum.ParentalStatusType</code>
 */
class ParentalStatusType
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
     * Parent.
     *
     * Generated from protobuf enum <code>PARENT = 300;</code>
     */
    const PARENT = 300;
    /**
     * Not a parent.
     *
     * Generated from protobuf enum <code>NOT_A_PARENT = 301;</code>
     */
    const NOT_A_PARENT = 301;
    /**
     * Undetermined parental status.
     *
     * Generated from protobuf enum <code>UNDETERMINED = 302;</code>
     */
    const UNDETERMINED = 302;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::PARENT => 'PARENT',
        self::NOT_A_PARENT => 'NOT_A_PARENT',
        self::UNDETERMINED => 'UNDETERMINED',
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
class_alias(ParentalStatusType::class, \Google\Ads\GoogleAds\V5\Enums\ParentalStatusTypeEnum_ParentalStatusType::class);

