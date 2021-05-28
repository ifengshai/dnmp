<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/enums/budget_delivery_method.proto

namespace Google\Ads\GoogleAds\V5\Enums\BudgetDeliveryMethodEnum;

use UnexpectedValueException;

/**
 * Possible delivery methods of a Budget.
 *
 * Protobuf type <code>google.ads.googleads.v5.enums.BudgetDeliveryMethodEnum.BudgetDeliveryMethod</code>
 */
class BudgetDeliveryMethod
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
     * The budget server will throttle serving evenly across
     * the entire time period.
     *
     * Generated from protobuf enum <code>STANDARD = 2;</code>
     */
    const STANDARD = 2;
    /**
     * The budget server will not throttle serving,
     * and ads will serve as fast as possible.
     *
     * Generated from protobuf enum <code>ACCELERATED = 3;</code>
     */
    const ACCELERATED = 3;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::STANDARD => 'STANDARD',
        self::ACCELERATED => 'ACCELERATED',
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
class_alias(BudgetDeliveryMethod::class, \Google\Ads\GoogleAds\V5\Enums\BudgetDeliveryMethodEnum_BudgetDeliveryMethod::class);

