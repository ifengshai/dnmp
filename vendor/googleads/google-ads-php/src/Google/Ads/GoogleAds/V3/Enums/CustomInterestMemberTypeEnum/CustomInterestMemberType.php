<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/enums/custom_interest_member_type.proto

namespace Google\Ads\GoogleAds\V3\Enums\CustomInterestMemberTypeEnum;

use UnexpectedValueException;

/**
 * Enum containing possible custom interest member types.
 *
 * Protobuf type <code>google.ads.googleads.v3.enums.CustomInterestMemberTypeEnum.CustomInterestMemberType</code>
 */
class CustomInterestMemberType
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
     * Custom interest member type KEYWORD.
     *
     * Generated from protobuf enum <code>KEYWORD = 2;</code>
     */
    const KEYWORD = 2;
    /**
     * Custom interest member type URL.
     *
     * Generated from protobuf enum <code>URL = 3;</code>
     */
    const URL = 3;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::KEYWORD => 'KEYWORD',
        self::URL => 'URL',
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
class_alias(CustomInterestMemberType::class, \Google\Ads\GoogleAds\V3\Enums\CustomInterestMemberTypeEnum_CustomInterestMemberType::class);

