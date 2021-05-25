<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v6/enums/user_list_logical_rule_operator.proto

namespace Google\Ads\GoogleAds\V6\Enums\UserListLogicalRuleOperatorEnum;

use UnexpectedValueException;

/**
 * Enum describing possible user list logical rule operators.
 *
 * Protobuf type <code>google.ads.googleads.v6.enums.UserListLogicalRuleOperatorEnum.UserListLogicalRuleOperator</code>
 */
class UserListLogicalRuleOperator
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
     * And - all of the operands.
     *
     * Generated from protobuf enum <code>ALL = 2;</code>
     */
    const ALL = 2;
    /**
     * Or - at least one of the operands.
     *
     * Generated from protobuf enum <code>ANY = 3;</code>
     */
    const ANY = 3;
    /**
     * Not - none of the operands.
     *
     * Generated from protobuf enum <code>NONE = 4;</code>
     */
    const NONE = 4;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::ALL => 'ALL',
        self::ANY => 'ANY',
        self::NONE => 'NONE',
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
class_alias(UserListLogicalRuleOperator::class, \Google\Ads\GoogleAds\V6\Enums\UserListLogicalRuleOperatorEnum_UserListLogicalRuleOperator::class);

