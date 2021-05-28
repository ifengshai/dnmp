<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v6/errors/function_error.proto

namespace Google\Ads\GoogleAds\V6\Errors\FunctionErrorEnum;

use UnexpectedValueException;

/**
 * Enum describing possible function errors.
 *
 * Protobuf type <code>google.ads.googleads.v6.errors.FunctionErrorEnum.FunctionError</code>
 */
class FunctionError
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
     * The format of the function is not recognized as a supported function
     * format.
     *
     * Generated from protobuf enum <code>INVALID_FUNCTION_FORMAT = 2;</code>
     */
    const INVALID_FUNCTION_FORMAT = 2;
    /**
     * Operand data types do not match.
     *
     * Generated from protobuf enum <code>DATA_TYPE_MISMATCH = 3;</code>
     */
    const DATA_TYPE_MISMATCH = 3;
    /**
     * The operands cannot be used together in a conjunction.
     *
     * Generated from protobuf enum <code>INVALID_CONJUNCTION_OPERANDS = 4;</code>
     */
    const INVALID_CONJUNCTION_OPERANDS = 4;
    /**
     * Invalid numer of Operands.
     *
     * Generated from protobuf enum <code>INVALID_NUMBER_OF_OPERANDS = 5;</code>
     */
    const INVALID_NUMBER_OF_OPERANDS = 5;
    /**
     * Operand Type not supported.
     *
     * Generated from protobuf enum <code>INVALID_OPERAND_TYPE = 6;</code>
     */
    const INVALID_OPERAND_TYPE = 6;
    /**
     * Operator not supported.
     *
     * Generated from protobuf enum <code>INVALID_OPERATOR = 7;</code>
     */
    const INVALID_OPERATOR = 7;
    /**
     * Request context type not supported.
     *
     * Generated from protobuf enum <code>INVALID_REQUEST_CONTEXT_TYPE = 8;</code>
     */
    const INVALID_REQUEST_CONTEXT_TYPE = 8;
    /**
     * The matching function is not allowed for call placeholders
     *
     * Generated from protobuf enum <code>INVALID_FUNCTION_FOR_CALL_PLACEHOLDER = 9;</code>
     */
    const INVALID_FUNCTION_FOR_CALL_PLACEHOLDER = 9;
    /**
     * The matching function is not allowed for the specified placeholder
     *
     * Generated from protobuf enum <code>INVALID_FUNCTION_FOR_PLACEHOLDER = 10;</code>
     */
    const INVALID_FUNCTION_FOR_PLACEHOLDER = 10;
    /**
     * Invalid operand.
     *
     * Generated from protobuf enum <code>INVALID_OPERAND = 11;</code>
     */
    const INVALID_OPERAND = 11;
    /**
     * Missing value for the constant operand.
     *
     * Generated from protobuf enum <code>MISSING_CONSTANT_OPERAND_VALUE = 12;</code>
     */
    const MISSING_CONSTANT_OPERAND_VALUE = 12;
    /**
     * The value of the constant operand is invalid.
     *
     * Generated from protobuf enum <code>INVALID_CONSTANT_OPERAND_VALUE = 13;</code>
     */
    const INVALID_CONSTANT_OPERAND_VALUE = 13;
    /**
     * Invalid function nesting.
     *
     * Generated from protobuf enum <code>INVALID_NESTING = 14;</code>
     */
    const INVALID_NESTING = 14;
    /**
     * The Feed ID was different from another Feed ID in the same function.
     *
     * Generated from protobuf enum <code>MULTIPLE_FEED_IDS_NOT_SUPPORTED = 15;</code>
     */
    const MULTIPLE_FEED_IDS_NOT_SUPPORTED = 15;
    /**
     * The matching function is invalid for use with a feed with a fixed schema.
     *
     * Generated from protobuf enum <code>INVALID_FUNCTION_FOR_FEED_WITH_FIXED_SCHEMA = 16;</code>
     */
    const INVALID_FUNCTION_FOR_FEED_WITH_FIXED_SCHEMA = 16;
    /**
     * Invalid attribute name.
     *
     * Generated from protobuf enum <code>INVALID_ATTRIBUTE_NAME = 17;</code>
     */
    const INVALID_ATTRIBUTE_NAME = 17;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::INVALID_FUNCTION_FORMAT => 'INVALID_FUNCTION_FORMAT',
        self::DATA_TYPE_MISMATCH => 'DATA_TYPE_MISMATCH',
        self::INVALID_CONJUNCTION_OPERANDS => 'INVALID_CONJUNCTION_OPERANDS',
        self::INVALID_NUMBER_OF_OPERANDS => 'INVALID_NUMBER_OF_OPERANDS',
        self::INVALID_OPERAND_TYPE => 'INVALID_OPERAND_TYPE',
        self::INVALID_OPERATOR => 'INVALID_OPERATOR',
        self::INVALID_REQUEST_CONTEXT_TYPE => 'INVALID_REQUEST_CONTEXT_TYPE',
        self::INVALID_FUNCTION_FOR_CALL_PLACEHOLDER => 'INVALID_FUNCTION_FOR_CALL_PLACEHOLDER',
        self::INVALID_FUNCTION_FOR_PLACEHOLDER => 'INVALID_FUNCTION_FOR_PLACEHOLDER',
        self::INVALID_OPERAND => 'INVALID_OPERAND',
        self::MISSING_CONSTANT_OPERAND_VALUE => 'MISSING_CONSTANT_OPERAND_VALUE',
        self::INVALID_CONSTANT_OPERAND_VALUE => 'INVALID_CONSTANT_OPERAND_VALUE',
        self::INVALID_NESTING => 'INVALID_NESTING',
        self::MULTIPLE_FEED_IDS_NOT_SUPPORTED => 'MULTIPLE_FEED_IDS_NOT_SUPPORTED',
        self::INVALID_FUNCTION_FOR_FEED_WITH_FIXED_SCHEMA => 'INVALID_FUNCTION_FOR_FEED_WITH_FIXED_SCHEMA',
        self::INVALID_ATTRIBUTE_NAME => 'INVALID_ATTRIBUTE_NAME',
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
class_alias(FunctionError::class, \Google\Ads\GoogleAds\V6\Errors\FunctionErrorEnum_FunctionError::class);

