<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/enums/slot.proto

namespace Google\Ads\GoogleAds\V3\Enums\SlotEnum;

use UnexpectedValueException;

/**
 * Enumerates possible positions of the Ad.
 *
 * Protobuf type <code>google.ads.googleads.v3.enums.SlotEnum.Slot</code>
 */
class Slot
{
    /**
     * Not specified.
     *
     * Generated from protobuf enum <code>UNSPECIFIED = 0;</code>
     */
    const UNSPECIFIED = 0;
    /**
     * The value is unknown in this version.
     *
     * Generated from protobuf enum <code>UNKNOWN = 1;</code>
     */
    const UNKNOWN = 1;
    /**
     * Google search: Side.
     *
     * Generated from protobuf enum <code>SEARCH_SIDE = 2;</code>
     */
    const SEARCH_SIDE = 2;
    /**
     * Google search: Top.
     *
     * Generated from protobuf enum <code>SEARCH_TOP = 3;</code>
     */
    const SEARCH_TOP = 3;
    /**
     * Google search: Other.
     *
     * Generated from protobuf enum <code>SEARCH_OTHER = 4;</code>
     */
    const SEARCH_OTHER = 4;
    /**
     * Google Display Network.
     *
     * Generated from protobuf enum <code>CONTENT = 5;</code>
     */
    const CONTENT = 5;
    /**
     * Search partners: Top.
     *
     * Generated from protobuf enum <code>SEARCH_PARTNER_TOP = 6;</code>
     */
    const SEARCH_PARTNER_TOP = 6;
    /**
     * Search partners: Other.
     *
     * Generated from protobuf enum <code>SEARCH_PARTNER_OTHER = 7;</code>
     */
    const SEARCH_PARTNER_OTHER = 7;
    /**
     * Cross-network.
     *
     * Generated from protobuf enum <code>MIXED = 8;</code>
     */
    const MIXED = 8;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::SEARCH_SIDE => 'SEARCH_SIDE',
        self::SEARCH_TOP => 'SEARCH_TOP',
        self::SEARCH_OTHER => 'SEARCH_OTHER',
        self::CONTENT => 'CONTENT',
        self::SEARCH_PARTNER_TOP => 'SEARCH_PARTNER_TOP',
        self::SEARCH_PARTNER_OTHER => 'SEARCH_PARTNER_OTHER',
        self::MIXED => 'MIXED',
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
class_alias(Slot::class, \Google\Ads\GoogleAds\V3\Enums\SlotEnum_Slot::class);

