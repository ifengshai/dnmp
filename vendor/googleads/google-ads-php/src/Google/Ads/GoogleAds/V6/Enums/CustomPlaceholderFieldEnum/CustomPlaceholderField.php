<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v6/enums/custom_placeholder_field.proto

namespace Google\Ads\GoogleAds\V6\Enums\CustomPlaceholderFieldEnum;

use UnexpectedValueException;

/**
 * Possible values for Custom placeholder fields.
 *
 * Protobuf type <code>google.ads.googleads.v6.enums.CustomPlaceholderFieldEnum.CustomPlaceholderField</code>
 */
class CustomPlaceholderField
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
     * Data Type: STRING. Required. Combination ID and ID2 must be unique per
     * offer.
     *
     * Generated from protobuf enum <code>ID = 2;</code>
     */
    const ID = 2;
    /**
     * Data Type: STRING. Combination ID and ID2 must be unique per offer.
     *
     * Generated from protobuf enum <code>ID2 = 3;</code>
     */
    const ID2 = 3;
    /**
     * Data Type: STRING. Required. Main headline with product name to be shown
     * in dynamic ad.
     *
     * Generated from protobuf enum <code>ITEM_TITLE = 4;</code>
     */
    const ITEM_TITLE = 4;
    /**
     * Data Type: STRING. Optional text to be shown in the image ad.
     *
     * Generated from protobuf enum <code>ITEM_SUBTITLE = 5;</code>
     */
    const ITEM_SUBTITLE = 5;
    /**
     * Data Type: STRING. Optional description of the product to be shown in the
     * ad.
     *
     * Generated from protobuf enum <code>ITEM_DESCRIPTION = 6;</code>
     */
    const ITEM_DESCRIPTION = 6;
    /**
     * Data Type: STRING. Full address of your offer or service, including
     * postal code. This will be used to identify the closest product to the
     * user when there are multiple offers in the feed that are relevant to the
     * user.
     *
     * Generated from protobuf enum <code>ITEM_ADDRESS = 7;</code>
     */
    const ITEM_ADDRESS = 7;
    /**
     * Data Type: STRING. Price to be shown in the ad.
     * Example: "100.00 USD"
     *
     * Generated from protobuf enum <code>PRICE = 8;</code>
     */
    const PRICE = 8;
    /**
     * Data Type: STRING. Formatted price to be shown in the ad.
     * Example: "Starting at $100.00 USD", "$80 - $100"
     *
     * Generated from protobuf enum <code>FORMATTED_PRICE = 9;</code>
     */
    const FORMATTED_PRICE = 9;
    /**
     * Data Type: STRING. Sale price to be shown in the ad.
     * Example: "80.00 USD"
     *
     * Generated from protobuf enum <code>SALE_PRICE = 10;</code>
     */
    const SALE_PRICE = 10;
    /**
     * Data Type: STRING. Formatted sale price to be shown in the ad.
     * Example: "On sale for $80.00", "$60 - $80"
     *
     * Generated from protobuf enum <code>FORMATTED_SALE_PRICE = 11;</code>
     */
    const FORMATTED_SALE_PRICE = 11;
    /**
     * Data Type: URL. Image to be displayed in the ad. Highly recommended for
     * image ads.
     *
     * Generated from protobuf enum <code>IMAGE_URL = 12;</code>
     */
    const IMAGE_URL = 12;
    /**
     * Data Type: STRING. Used as a recommendation engine signal to serve items
     * in the same category.
     *
     * Generated from protobuf enum <code>ITEM_CATEGORY = 13;</code>
     */
    const ITEM_CATEGORY = 13;
    /**
     * Data Type: URL_LIST. Final URLs for the ad when using Upgraded
     * URLs. User will be redirected to these URLs when they click on an ad, or
     * when they click on a specific product for ads that have multiple
     * products.
     *
     * Generated from protobuf enum <code>FINAL_URLS = 14;</code>
     */
    const FINAL_URLS = 14;
    /**
     * Data Type: URL_LIST. Final mobile URLs for the ad when using Upgraded
     * URLs.
     *
     * Generated from protobuf enum <code>FINAL_MOBILE_URLS = 15;</code>
     */
    const FINAL_MOBILE_URLS = 15;
    /**
     * Data Type: URL. Tracking template for the ad when using Upgraded URLs.
     *
     * Generated from protobuf enum <code>TRACKING_URL = 16;</code>
     */
    const TRACKING_URL = 16;
    /**
     * Data Type: STRING_LIST. Keywords used for product retrieval.
     *
     * Generated from protobuf enum <code>CONTEXTUAL_KEYWORDS = 17;</code>
     */
    const CONTEXTUAL_KEYWORDS = 17;
    /**
     * Data Type: STRING. Android app link. Must be formatted as:
     * android-app://{package_id}/{scheme}/{host_path}.
     * The components are defined as follows:
     * package_id: app ID as specified in Google Play.
     * scheme: the scheme to pass to the application. Can be HTTP, or a custom
     *   scheme.
     * host_path: identifies the specific content within your application.
     *
     * Generated from protobuf enum <code>ANDROID_APP_LINK = 18;</code>
     */
    const ANDROID_APP_LINK = 18;
    /**
     * Data Type: STRING_LIST. List of recommended IDs to show together with
     * this item.
     *
     * Generated from protobuf enum <code>SIMILAR_IDS = 19;</code>
     */
    const SIMILAR_IDS = 19;
    /**
     * Data Type: STRING. iOS app link.
     *
     * Generated from protobuf enum <code>IOS_APP_LINK = 20;</code>
     */
    const IOS_APP_LINK = 20;
    /**
     * Data Type: INT64. iOS app store ID.
     *
     * Generated from protobuf enum <code>IOS_APP_STORE_ID = 21;</code>
     */
    const IOS_APP_STORE_ID = 21;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::ID => 'ID',
        self::ID2 => 'ID2',
        self::ITEM_TITLE => 'ITEM_TITLE',
        self::ITEM_SUBTITLE => 'ITEM_SUBTITLE',
        self::ITEM_DESCRIPTION => 'ITEM_DESCRIPTION',
        self::ITEM_ADDRESS => 'ITEM_ADDRESS',
        self::PRICE => 'PRICE',
        self::FORMATTED_PRICE => 'FORMATTED_PRICE',
        self::SALE_PRICE => 'SALE_PRICE',
        self::FORMATTED_SALE_PRICE => 'FORMATTED_SALE_PRICE',
        self::IMAGE_URL => 'IMAGE_URL',
        self::ITEM_CATEGORY => 'ITEM_CATEGORY',
        self::FINAL_URLS => 'FINAL_URLS',
        self::FINAL_MOBILE_URLS => 'FINAL_MOBILE_URLS',
        self::TRACKING_URL => 'TRACKING_URL',
        self::CONTEXTUAL_KEYWORDS => 'CONTEXTUAL_KEYWORDS',
        self::ANDROID_APP_LINK => 'ANDROID_APP_LINK',
        self::SIMILAR_IDS => 'SIMILAR_IDS',
        self::IOS_APP_LINK => 'IOS_APP_LINK',
        self::IOS_APP_STORE_ID => 'IOS_APP_STORE_ID',
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
class_alias(CustomPlaceholderField::class, \Google\Ads\GoogleAds\V6\Enums\CustomPlaceholderFieldEnum_CustomPlaceholderField::class);

