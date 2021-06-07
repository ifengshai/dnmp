<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/errors/custom_interest_error.proto

namespace Google\Ads\GoogleAds\V3\Errors\CustomInterestErrorEnum;

use UnexpectedValueException;

/**
 * Enum describing possible custom interest errors.
 *
 * Protobuf type <code>google.ads.googleads.v3.errors.CustomInterestErrorEnum.CustomInterestError</code>
 */
class CustomInterestError
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
     * Duplicate custom interest name ignoring case.
     *
     * Generated from protobuf enum <code>NAME_ALREADY_USED = 2;</code>
     */
    const NAME_ALREADY_USED = 2;
    /**
     * In the remove custom interest member operation, both member ID and
     * pair [type, parameter] are not present.
     *
     * Generated from protobuf enum <code>CUSTOM_INTEREST_MEMBER_ID_AND_TYPE_PARAMETER_NOT_PRESENT_IN_REMOVE = 3;</code>
     */
    const CUSTOM_INTEREST_MEMBER_ID_AND_TYPE_PARAMETER_NOT_PRESENT_IN_REMOVE = 3;
    /**
     * The pair of [type, parameter] does not exist.
     *
     * Generated from protobuf enum <code>TYPE_AND_PARAMETER_NOT_FOUND = 4;</code>
     */
    const TYPE_AND_PARAMETER_NOT_FOUND = 4;
    /**
     * The pair of [type, parameter] already exists.
     *
     * Generated from protobuf enum <code>TYPE_AND_PARAMETER_ALREADY_EXISTED = 5;</code>
     */
    const TYPE_AND_PARAMETER_ALREADY_EXISTED = 5;
    /**
     * Unsupported custom interest member type.
     *
     * Generated from protobuf enum <code>INVALID_CUSTOM_INTEREST_MEMBER_TYPE = 6;</code>
     */
    const INVALID_CUSTOM_INTEREST_MEMBER_TYPE = 6;
    /**
     * Cannot remove a custom interest while it's still being targeted.
     *
     * Generated from protobuf enum <code>CANNOT_REMOVE_WHILE_IN_USE = 7;</code>
     */
    const CANNOT_REMOVE_WHILE_IN_USE = 7;
    /**
     * Cannot mutate custom interest type.
     *
     * Generated from protobuf enum <code>CANNOT_CHANGE_TYPE = 8;</code>
     */
    const CANNOT_CHANGE_TYPE = 8;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::NAME_ALREADY_USED => 'NAME_ALREADY_USED',
        self::CUSTOM_INTEREST_MEMBER_ID_AND_TYPE_PARAMETER_NOT_PRESENT_IN_REMOVE => 'CUSTOM_INTEREST_MEMBER_ID_AND_TYPE_PARAMETER_NOT_PRESENT_IN_REMOVE',
        self::TYPE_AND_PARAMETER_NOT_FOUND => 'TYPE_AND_PARAMETER_NOT_FOUND',
        self::TYPE_AND_PARAMETER_ALREADY_EXISTED => 'TYPE_AND_PARAMETER_ALREADY_EXISTED',
        self::INVALID_CUSTOM_INTEREST_MEMBER_TYPE => 'INVALID_CUSTOM_INTEREST_MEMBER_TYPE',
        self::CANNOT_REMOVE_WHILE_IN_USE => 'CANNOT_REMOVE_WHILE_IN_USE',
        self::CANNOT_CHANGE_TYPE => 'CANNOT_CHANGE_TYPE',
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
class_alias(CustomInterestError::class, \Google\Ads\GoogleAds\V3\Errors\CustomInterestErrorEnum_CustomInterestError::class);

