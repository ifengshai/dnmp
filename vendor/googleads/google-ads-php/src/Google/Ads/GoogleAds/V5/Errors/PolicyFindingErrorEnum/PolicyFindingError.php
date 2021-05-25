<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/errors/policy_finding_error.proto

namespace Google\Ads\GoogleAds\V5\Errors\PolicyFindingErrorEnum;

use UnexpectedValueException;

/**
 * Enum describing possible policy finding errors.
 *
 * Protobuf type <code>google.ads.googleads.v5.errors.PolicyFindingErrorEnum.PolicyFindingError</code>
 */
class PolicyFindingError
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
     * The resource has been disapproved since the policy summary includes
     * policy topics of type PROHIBITED.
     *
     * Generated from protobuf enum <code>POLICY_FINDING = 2;</code>
     */
    const POLICY_FINDING = 2;
    /**
     * The given policy topic does not exist.
     *
     * Generated from protobuf enum <code>POLICY_TOPIC_NOT_FOUND = 3;</code>
     */
    const POLICY_TOPIC_NOT_FOUND = 3;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::POLICY_FINDING => 'POLICY_FINDING',
        self::POLICY_TOPIC_NOT_FOUND => 'POLICY_TOPIC_NOT_FOUND',
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
class_alias(PolicyFindingError::class, \Google\Ads\GoogleAds\V5\Errors\PolicyFindingErrorEnum_PolicyFindingError::class);

