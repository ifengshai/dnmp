<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/enums/promotion_placeholder_field.proto

namespace Google\Ads\GoogleAds\V3\Enums\PromotionPlaceholderFieldEnum;

use UnexpectedValueException;

/**
 * Possible values for Promotion placeholder fields.
 *
 * Protobuf type <code>google.ads.googleads.v3.enums.PromotionPlaceholderFieldEnum.PromotionPlaceholderField</code>
 */
class PromotionPlaceholderField
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
     * Data Type: STRING. The text that appears on the ad when the extension is
     * shown.
     *
     * Generated from protobuf enum <code>PROMOTION_TARGET = 2;</code>
     */
    const PROMOTION_TARGET = 2;
    /**
     * Data Type: STRING. Allows you to add "up to" phrase to the promotion,
     * in case you have variable promotion rates.
     *
     * Generated from protobuf enum <code>DISCOUNT_MODIFIER = 3;</code>
     */
    const DISCOUNT_MODIFIER = 3;
    /**
     * Data Type: INT64. Takes a value in micros, where 1 million micros
     * represents 1%, and is shown as a percentage when rendered.
     *
     * Generated from protobuf enum <code>PERCENT_OFF = 4;</code>
     */
    const PERCENT_OFF = 4;
    /**
     * Data Type: MONEY. Requires a currency and an amount of money.
     *
     * Generated from protobuf enum <code>MONEY_AMOUNT_OFF = 5;</code>
     */
    const MONEY_AMOUNT_OFF = 5;
    /**
     * Data Type: STRING. A string that the user enters to get the discount.
     *
     * Generated from protobuf enum <code>PROMOTION_CODE = 6;</code>
     */
    const PROMOTION_CODE = 6;
    /**
     * Data Type: MONEY. A minimum spend before the user qualifies for the
     * promotion.
     *
     * Generated from protobuf enum <code>ORDERS_OVER_AMOUNT = 7;</code>
     */
    const ORDERS_OVER_AMOUNT = 7;
    /**
     * Data Type: DATE. The start date of the promotion.
     *
     * Generated from protobuf enum <code>PROMOTION_START = 8;</code>
     */
    const PROMOTION_START = 8;
    /**
     * Data Type: DATE. The end date of the promotion.
     *
     * Generated from protobuf enum <code>PROMOTION_END = 9;</code>
     */
    const PROMOTION_END = 9;
    /**
     * Data Type: STRING. Describes the associated event for the promotion using
     * one of the PromotionExtensionOccasion enum values, for example NEW_YEARS.
     *
     * Generated from protobuf enum <code>OCCASION = 10;</code>
     */
    const OCCASION = 10;
    /**
     * Data Type: URL_LIST. Final URLs to be used in the ad when using Upgraded
     * URLs.
     *
     * Generated from protobuf enum <code>FINAL_URLS = 11;</code>
     */
    const FINAL_URLS = 11;
    /**
     * Data Type: URL_LIST. Final mobile URLs for the ad when using Upgraded
     * URLs.
     *
     * Generated from protobuf enum <code>FINAL_MOBILE_URLS = 12;</code>
     */
    const FINAL_MOBILE_URLS = 12;
    /**
     * Data Type: URL. Tracking template for the ad when using Upgraded URLs.
     *
     * Generated from protobuf enum <code>TRACKING_URL = 13;</code>
     */
    const TRACKING_URL = 13;
    /**
     * Data Type: STRING. A string represented by a language code for the
     * promotion.
     *
     * Generated from protobuf enum <code>LANGUAGE = 14;</code>
     */
    const LANGUAGE = 14;
    /**
     * Data Type: STRING. Final URL suffix for the ad when using parallel
     * tracking.
     *
     * Generated from protobuf enum <code>FINAL_URL_SUFFIX = 15;</code>
     */
    const FINAL_URL_SUFFIX = 15;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::PROMOTION_TARGET => 'PROMOTION_TARGET',
        self::DISCOUNT_MODIFIER => 'DISCOUNT_MODIFIER',
        self::PERCENT_OFF => 'PERCENT_OFF',
        self::MONEY_AMOUNT_OFF => 'MONEY_AMOUNT_OFF',
        self::PROMOTION_CODE => 'PROMOTION_CODE',
        self::ORDERS_OVER_AMOUNT => 'ORDERS_OVER_AMOUNT',
        self::PROMOTION_START => 'PROMOTION_START',
        self::PROMOTION_END => 'PROMOTION_END',
        self::OCCASION => 'OCCASION',
        self::FINAL_URLS => 'FINAL_URLS',
        self::FINAL_MOBILE_URLS => 'FINAL_MOBILE_URLS',
        self::TRACKING_URL => 'TRACKING_URL',
        self::LANGUAGE => 'LANGUAGE',
        self::FINAL_URL_SUFFIX => 'FINAL_URL_SUFFIX',
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
class_alias(PromotionPlaceholderField::class, \Google\Ads\GoogleAds\V3\Enums\PromotionPlaceholderFieldEnum_PromotionPlaceholderField::class);

