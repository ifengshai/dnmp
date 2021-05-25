<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v6/enums/access_reason.proto

namespace Google\Ads\GoogleAds\V6\Enums\AccessReasonEnum;

use UnexpectedValueException;

/**
 * Enum describing possible access reasons.
 *
 * Protobuf type <code>google.ads.googleads.v6.enums.AccessReasonEnum.AccessReason</code>
 */
class AccessReason
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
     * The resource is owned by the user.
     *
     * Generated from protobuf enum <code>OWNED = 2;</code>
     */
    const OWNED = 2;
    /**
     * The resource is shared to the user.
     *
     * Generated from protobuf enum <code>SHARED = 3;</code>
     */
    const SHARED = 3;
    /**
     * The resource is licensed to the user.
     *
     * Generated from protobuf enum <code>LICENSED = 4;</code>
     */
    const LICENSED = 4;
    /**
     * The user subscribed to the resource.
     *
     * Generated from protobuf enum <code>SUBSCRIBED = 5;</code>
     */
    const SUBSCRIBED = 5;
    /**
     * The resource is accessible to the user.
     *
     * Generated from protobuf enum <code>AFFILIATED = 6;</code>
     */
    const AFFILIATED = 6;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::OWNED => 'OWNED',
        self::SHARED => 'SHARED',
        self::LICENSED => 'LICENSED',
        self::SUBSCRIBED => 'SUBSCRIBED',
        self::AFFILIATED => 'AFFILIATED',
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
class_alias(AccessReason::class, \Google\Ads\GoogleAds\V6\Enums\AccessReasonEnum_AccessReason::class);

