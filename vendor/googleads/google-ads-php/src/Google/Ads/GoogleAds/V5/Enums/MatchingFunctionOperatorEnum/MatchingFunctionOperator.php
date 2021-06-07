<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/enums/matching_function_operator.proto

namespace Google\Ads\GoogleAds\V5\Enums\MatchingFunctionOperatorEnum;

use UnexpectedValueException;

/**
 * Possible operators in a matching function.
 *
 * Protobuf type <code>google.ads.googleads.v5.enums.MatchingFunctionOperatorEnum.MatchingFunctionOperator</code>
 */
class MatchingFunctionOperator
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
     * The IN operator.
     *
     * Generated from protobuf enum <code>IN = 2;</code>
     */
    const IN = 2;
    /**
     * The IDENTITY operator.
     *
     * Generated from protobuf enum <code>IDENTITY = 3;</code>
     */
    const IDENTITY = 3;
    /**
     * The EQUALS operator
     *
     * Generated from protobuf enum <code>EQUALS = 4;</code>
     */
    const EQUALS = 4;
    /**
     * Operator that takes two or more operands that are of type
     * FunctionOperand and checks that all the operands evaluate to true.
     * For functions related to ad formats, all the operands must be in
     * left_operands.
     *
     * Generated from protobuf enum <code>AND = 5;</code>
     */
    const PBAND = 5;
    /**
     * Operator that returns true if the elements in left_operands contain any
     * of the elements in right_operands. Otherwise, return false. The
     * right_operands must contain at least 1 and no more than 3
     * ConstantOperands.
     *
     * Generated from protobuf enum <code>CONTAINS_ANY = 6;</code>
     */
    const CONTAINS_ANY = 6;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::IN => 'IN',
        self::IDENTITY => 'IDENTITY',
        self::EQUALS => 'EQUALS',
        self::PBAND => 'PBAND',
        self::CONTAINS_ANY => 'CONTAINS_ANY',
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
class_alias(MatchingFunctionOperator::class, \Google\Ads\GoogleAds\V5\Enums\MatchingFunctionOperatorEnum_MatchingFunctionOperator::class);

