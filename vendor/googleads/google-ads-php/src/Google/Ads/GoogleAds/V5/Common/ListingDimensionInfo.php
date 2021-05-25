<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/common/criteria.proto

namespace Google\Ads\GoogleAds\V5\Common;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Listing dimensions for listing group criterion.
 *
 * Generated from protobuf message <code>google.ads.googleads.v5.common.ListingDimensionInfo</code>
 */
class ListingDimensionInfo extends \Google\Protobuf\Internal\Message
{
    protected $dimension;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Ads\GoogleAds\V5\Common\HotelIdInfo $hotel_id
     *           Advertiser-specific hotel ID.
     *     @type \Google\Ads\GoogleAds\V5\Common\HotelClassInfo $hotel_class
     *           Class of the hotel as a number of stars 1 to 5.
     *     @type \Google\Ads\GoogleAds\V5\Common\HotelCountryRegionInfo $hotel_country_region
     *           Country or Region the hotel is located in.
     *     @type \Google\Ads\GoogleAds\V5\Common\HotelStateInfo $hotel_state
     *           State the hotel is located in.
     *     @type \Google\Ads\GoogleAds\V5\Common\HotelCityInfo $hotel_city
     *           City the hotel is located in.
     *     @type \Google\Ads\GoogleAds\V5\Common\ProductBiddingCategoryInfo $product_bidding_category
     *           Bidding category of a product offer.
     *     @type \Google\Ads\GoogleAds\V5\Common\ProductBrandInfo $product_brand
     *           Brand of a product offer.
     *     @type \Google\Ads\GoogleAds\V5\Common\ProductChannelInfo $product_channel
     *           Locality of a product offer.
     *     @type \Google\Ads\GoogleAds\V5\Common\ProductChannelExclusivityInfo $product_channel_exclusivity
     *           Availability of a product offer.
     *     @type \Google\Ads\GoogleAds\V5\Common\ProductConditionInfo $product_condition
     *           Condition of a product offer.
     *     @type \Google\Ads\GoogleAds\V5\Common\ProductCustomAttributeInfo $product_custom_attribute
     *           Custom attribute of a product offer.
     *     @type \Google\Ads\GoogleAds\V5\Common\ProductItemIdInfo $product_item_id
     *           Item id of a product offer.
     *     @type \Google\Ads\GoogleAds\V5\Common\ProductTypeInfo $product_type
     *           Type of a product offer.
     *     @type \Google\Ads\GoogleAds\V5\Common\UnknownListingDimensionInfo $unknown_listing_dimension
     *           Unknown dimension. Set when no other listing dimension is set.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V5\Common\Criteria::initOnce();
        parent::__construct($data);
    }

    /**
     * Advertiser-specific hotel ID.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.common.HotelIdInfo hotel_id = 2;</code>
     * @return \Google\Ads\GoogleAds\V5\Common\HotelIdInfo
     */
    public function getHotelId()
    {
        return $this->readOneof(2);
    }

    public function hasHotelId()
    {
        return $this->hasOneof(2);
    }

    /**
     * Advertiser-specific hotel ID.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.common.HotelIdInfo hotel_id = 2;</code>
     * @param \Google\Ads\GoogleAds\V5\Common\HotelIdInfo $var
     * @return $this
     */
    public function setHotelId($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V5\Common\HotelIdInfo::class);
        $this->writeOneof(2, $var);

        return $this;
    }

    /**
     * Class of the hotel as a number of stars 1 to 5.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.common.HotelClassInfo hotel_class = 3;</code>
     * @return \Google\Ads\GoogleAds\V5\Common\HotelClassInfo
     */
    public function getHotelClass()
    {
        return $this->readOneof(3);
    }

    public function hasHotelClass()
    {
        return $this->hasOneof(3);
    }

    /**
     * Class of the hotel as a number of stars 1 to 5.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.common.HotelClassInfo hotel_class = 3;</code>
     * @param \Google\Ads\GoogleAds\V5\Common\HotelClassInfo $var
     * @return $this
     */
    public function setHotelClass($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V5\Common\HotelClassInfo::class);
        $this->writeOneof(3, $var);

        return $this;
    }

    /**
     * Country or Region the hotel is located in.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.common.HotelCountryRegionInfo hotel_country_region = 4;</code>
     * @return \Google\Ads\GoogleAds\V5\Common\HotelCountryRegionInfo
     */
    public function getHotelCountryRegion()
    {
        return $this->readOneof(4);
    }

    public function hasHotelCountryRegion()
    {
        return $this->hasOneof(4);
    }

    /**
     * Country or Region the hotel is located in.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.common.HotelCountryRegionInfo hotel_country_region = 4;</code>
     * @param \Google\Ads\GoogleAds\V5\Common\HotelCountryRegionInfo $var
     * @return $this
     */
    public function setHotelCountryRegion($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V5\Common\HotelCountryRegionInfo::class);
        $this->writeOneof(4, $var);

        return $this;
    }

    /**
     * State the hotel is located in.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.common.HotelStateInfo hotel_state = 5;</code>
     * @return \Google\Ads\GoogleAds\V5\Common\HotelStateInfo
     */
    public function getHotelState()
    {
        return $this->readOneof(5);
    }

    public function hasHotelState()
    {
        return $this->hasOneof(5);
    }

    /**
     * State the hotel is located in.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.common.HotelStateInfo hotel_state = 5;</code>
     * @param \Google\Ads\GoogleAds\V5\Common\HotelStateInfo $var
     * @return $this
     */
    public function setHotelState($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V5\Common\HotelStateInfo::class);
        $this->writeOneof(5, $var);

        return $this;
    }

    /**
     * City the hotel is located in.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.common.HotelCityInfo hotel_city = 6;</code>
     * @return \Google\Ads\GoogleAds\V5\Common\HotelCityInfo
     */
    public function getHotelCity()
    {
        return $this->readOneof(6);
    }

    public function hasHotelCity()
    {
        return $this->hasOneof(6);
    }

    /**
     * City the hotel is located in.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.common.HotelCityInfo hotel_city = 6;</code>
     * @param \Google\Ads\GoogleAds\V5\Common\HotelCityInfo $var
     * @return $this
     */
    public function setHotelCity($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V5\Common\HotelCityInfo::class);
        $this->writeOneof(6, $var);

        return $this;
    }

    /**
     * Bidding category of a product offer.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.common.ProductBiddingCategoryInfo product_bidding_category = 13;</code>
     * @return \Google\Ads\GoogleAds\V5\Common\ProductBiddingCategoryInfo
     */
    public function getProductBiddingCategory()
    {
        return $this->readOneof(13);
    }

    public function hasProductBiddingCategory()
    {
        return $this->hasOneof(13);
    }

    /**
     * Bidding category of a product offer.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.common.ProductBiddingCategoryInfo product_bidding_category = 13;</code>
     * @param \Google\Ads\GoogleAds\V5\Common\ProductBiddingCategoryInfo $var
     * @return $this
     */
    public function setProductBiddingCategory($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V5\Common\ProductBiddingCategoryInfo::class);
        $this->writeOneof(13, $var);

        return $this;
    }

    /**
     * Brand of a product offer.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.common.ProductBrandInfo product_brand = 15;</code>
     * @return \Google\Ads\GoogleAds\V5\Common\ProductBrandInfo
     */
    public function getProductBrand()
    {
        return $this->readOneof(15);
    }

    public function hasProductBrand()
    {
        return $this->hasOneof(15);
    }

    /**
     * Brand of a product offer.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.common.ProductBrandInfo product_brand = 15;</code>
     * @param \Google\Ads\GoogleAds\V5\Common\ProductBrandInfo $var
     * @return $this
     */
    public function setProductBrand($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V5\Common\ProductBrandInfo::class);
        $this->writeOneof(15, $var);

        return $this;
    }

    /**
     * Locality of a product offer.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.common.ProductChannelInfo product_channel = 8;</code>
     * @return \Google\Ads\GoogleAds\V5\Common\ProductChannelInfo
     */
    public function getProductChannel()
    {
        return $this->readOneof(8);
    }

    public function hasProductChannel()
    {
        return $this->hasOneof(8);
    }

    /**
     * Locality of a product offer.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.common.ProductChannelInfo product_channel = 8;</code>
     * @param \Google\Ads\GoogleAds\V5\Common\ProductChannelInfo $var
     * @return $this
     */
    public function setProductChannel($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V5\Common\ProductChannelInfo::class);
        $this->writeOneof(8, $var);

        return $this;
    }

    /**
     * Availability of a product offer.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.common.ProductChannelExclusivityInfo product_channel_exclusivity = 9;</code>
     * @return \Google\Ads\GoogleAds\V5\Common\ProductChannelExclusivityInfo
     */
    public function getProductChannelExclusivity()
    {
        return $this->readOneof(9);
    }

    public function hasProductChannelExclusivity()
    {
        return $this->hasOneof(9);
    }

    /**
     * Availability of a product offer.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.common.ProductChannelExclusivityInfo product_channel_exclusivity = 9;</code>
     * @param \Google\Ads\GoogleAds\V5\Common\ProductChannelExclusivityInfo $var
     * @return $this
     */
    public function setProductChannelExclusivity($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V5\Common\ProductChannelExclusivityInfo::class);
        $this->writeOneof(9, $var);

        return $this;
    }

    /**
     * Condition of a product offer.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.common.ProductConditionInfo product_condition = 10;</code>
     * @return \Google\Ads\GoogleAds\V5\Common\ProductConditionInfo
     */
    public function getProductCondition()
    {
        return $this->readOneof(10);
    }

    public function hasProductCondition()
    {
        return $this->hasOneof(10);
    }

    /**
     * Condition of a product offer.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.common.ProductConditionInfo product_condition = 10;</code>
     * @param \Google\Ads\GoogleAds\V5\Common\ProductConditionInfo $var
     * @return $this
     */
    public function setProductCondition($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V5\Common\ProductConditionInfo::class);
        $this->writeOneof(10, $var);

        return $this;
    }

    /**
     * Custom attribute of a product offer.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.common.ProductCustomAttributeInfo product_custom_attribute = 16;</code>
     * @return \Google\Ads\GoogleAds\V5\Common\ProductCustomAttributeInfo
     */
    public function getProductCustomAttribute()
    {
        return $this->readOneof(16);
    }

    public function hasProductCustomAttribute()
    {
        return $this->hasOneof(16);
    }

    /**
     * Custom attribute of a product offer.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.common.ProductCustomAttributeInfo product_custom_attribute = 16;</code>
     * @param \Google\Ads\GoogleAds\V5\Common\ProductCustomAttributeInfo $var
     * @return $this
     */
    public function setProductCustomAttribute($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V5\Common\ProductCustomAttributeInfo::class);
        $this->writeOneof(16, $var);

        return $this;
    }

    /**
     * Item id of a product offer.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.common.ProductItemIdInfo product_item_id = 11;</code>
     * @return \Google\Ads\GoogleAds\V5\Common\ProductItemIdInfo
     */
    public function getProductItemId()
    {
        return $this->readOneof(11);
    }

    public function hasProductItemId()
    {
        return $this->hasOneof(11);
    }

    /**
     * Item id of a product offer.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.common.ProductItemIdInfo product_item_id = 11;</code>
     * @param \Google\Ads\GoogleAds\V5\Common\ProductItemIdInfo $var
     * @return $this
     */
    public function setProductItemId($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V5\Common\ProductItemIdInfo::class);
        $this->writeOneof(11, $var);

        return $this;
    }

    /**
     * Type of a product offer.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.common.ProductTypeInfo product_type = 12;</code>
     * @return \Google\Ads\GoogleAds\V5\Common\ProductTypeInfo
     */
    public function getProductType()
    {
        return $this->readOneof(12);
    }

    public function hasProductType()
    {
        return $this->hasOneof(12);
    }

    /**
     * Type of a product offer.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.common.ProductTypeInfo product_type = 12;</code>
     * @param \Google\Ads\GoogleAds\V5\Common\ProductTypeInfo $var
     * @return $this
     */
    public function setProductType($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V5\Common\ProductTypeInfo::class);
        $this->writeOneof(12, $var);

        return $this;
    }

    /**
     * Unknown dimension. Set when no other listing dimension is set.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.common.UnknownListingDimensionInfo unknown_listing_dimension = 14;</code>
     * @return \Google\Ads\GoogleAds\V5\Common\UnknownListingDimensionInfo
     */
    public function getUnknownListingDimension()
    {
        return $this->readOneof(14);
    }

    public function hasUnknownListingDimension()
    {
        return $this->hasOneof(14);
    }

    /**
     * Unknown dimension. Set when no other listing dimension is set.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.common.UnknownListingDimensionInfo unknown_listing_dimension = 14;</code>
     * @param \Google\Ads\GoogleAds\V5\Common\UnknownListingDimensionInfo $var
     * @return $this
     */
    public function setUnknownListingDimension($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V5\Common\UnknownListingDimensionInfo::class);
        $this->writeOneof(14, $var);

        return $this;
    }

    /**
     * @return string
     */
    public function getDimension()
    {
        return $this->whichOneof("dimension");
    }

}

