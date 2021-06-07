<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/enums/product_channel.proto

namespace Google\Ads\GoogleAds\V5\Enums\ProductChannelEnum;

use UnexpectedValueException;

/**
 * Enum describing the locality of a product offer.
 *
 * Protobuf type <code>google.ads.googleads.v5.enums.ProductChannelEnum.ProductChannel</code>
 */
class ProductChannel
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
     * The item is sold online.
     *
     * Generated from protobuf enum <code>ONLINE = 2;</code>
     */
    const ONLINE = 2;
    /**
     * The item is sold in local stores.
     *
     * Generated from protobuf enum <code>LOCAL = 3;</code>
     */
    const LOCAL = 3;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::ONLINE => 'ONLINE',
        self::LOCAL => 'LOCAL',
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
class_alias(ProductChannel::class, \Google\Ads\GoogleAds\V5\Enums\ProductChannelEnum_ProductChannel::class);

