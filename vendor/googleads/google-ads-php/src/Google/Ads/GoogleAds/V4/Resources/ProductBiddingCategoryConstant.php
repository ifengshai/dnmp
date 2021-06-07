<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/resources/product_bidding_category_constant.proto

namespace Google\Ads\GoogleAds\V4\Resources;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * A Product Bidding Category.
 *
 * Generated from protobuf message <code>google.ads.googleads.v4.resources.ProductBiddingCategoryConstant</code>
 */
class ProductBiddingCategoryConstant extends \Google\Protobuf\Internal\Message
{
    /**
     * Output only. The resource name of the product bidding category.
     * Product bidding category resource names have the form:
     * `productBiddingCategoryConstants/{country_code}~{level}~{id}`
     *
     * Generated from protobuf field <code>string resource_name = 1 [(.google.api.field_behavior) = OUTPUT_ONLY, (.google.api.resource_reference) = {</code>
     */
    protected $resource_name = '';
    /**
     * Output only. ID of the product bidding category.
     * This ID is equivalent to the google_product_category ID as described in
     * this article: https://support.google.com/merchants/answer/6324436.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value id = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $id = null;
    /**
     * Output only. Two-letter upper-case country code of the product bidding category.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue country_code = 3 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $country_code = null;
    /**
     * Output only. Resource name of the parent product bidding category.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue product_bidding_category_constant_parent = 4 [(.google.api.field_behavior) = OUTPUT_ONLY, (.google.api.resource_reference) = {</code>
     */
    protected $product_bidding_category_constant_parent = null;
    /**
     * Output only. Level of the product bidding category.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.enums.ProductBiddingCategoryLevelEnum.ProductBiddingCategoryLevel level = 5 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $level = 0;
    /**
     * Output only. Status of the product bidding category.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.enums.ProductBiddingCategoryStatusEnum.ProductBiddingCategoryStatus status = 6 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $status = 0;
    /**
     * Output only. Language code of the product bidding category.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue language_code = 7 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $language_code = null;
    /**
     * Output only. Display value of the product bidding category localized according to
     * language_code.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue localized_name = 8 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $localized_name = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $resource_name
     *           Output only. The resource name of the product bidding category.
     *           Product bidding category resource names have the form:
     *           `productBiddingCategoryConstants/{country_code}~{level}~{id}`
     *     @type \Google\Protobuf\Int64Value $id
     *           Output only. ID of the product bidding category.
     *           This ID is equivalent to the google_product_category ID as described in
     *           this article: https://support.google.com/merchants/answer/6324436.
     *     @type \Google\Protobuf\StringValue $country_code
     *           Output only. Two-letter upper-case country code of the product bidding category.
     *     @type \Google\Protobuf\StringValue $product_bidding_category_constant_parent
     *           Output only. Resource name of the parent product bidding category.
     *     @type int $level
     *           Output only. Level of the product bidding category.
     *     @type int $status
     *           Output only. Status of the product bidding category.
     *     @type \Google\Protobuf\StringValue $language_code
     *           Output only. Language code of the product bidding category.
     *     @type \Google\Protobuf\StringValue $localized_name
     *           Output only. Display value of the product bidding category localized according to
     *           language_code.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V4\Resources\ProductBiddingCategoryConstant::initOnce();
        parent::__construct($data);
    }

    /**
     * Output only. The resource name of the product bidding category.
     * Product bidding category resource names have the form:
     * `productBiddingCategoryConstants/{country_code}~{level}~{id}`
     *
     * Generated from protobuf field <code>string resource_name = 1 [(.google.api.field_behavior) = OUTPUT_ONLY, (.google.api.resource_reference) = {</code>
     * @return string
     */
    public function getResourceName()
    {
        return $this->resource_name;
    }

    /**
     * Output only. The resource name of the product bidding category.
     * Product bidding category resource names have the form:
     * `productBiddingCategoryConstants/{country_code}~{level}~{id}`
     *
     * Generated from protobuf field <code>string resource_name = 1 [(.google.api.field_behavior) = OUTPUT_ONLY, (.google.api.resource_reference) = {</code>
     * @param string $var
     * @return $this
     */
    public function setResourceName($var)
    {
        GPBUtil::checkString($var, True);
        $this->resource_name = $var;

        return $this;
    }

    /**
     * Output only. ID of the product bidding category.
     * This ID is equivalent to the google_product_category ID as described in
     * this article: https://support.google.com/merchants/answer/6324436.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value id = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\Int64Value
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the unboxed value from <code>getId()</code>

     * Output only. ID of the product bidding category.
     * This ID is equivalent to the google_product_category ID as described in
     * this article: https://support.google.com/merchants/answer/6324436.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value id = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return int|string|null
     */
    public function getIdUnwrapped()
    {
        return $this->readWrapperValue("id");
    }

    /**
     * Output only. ID of the product bidding category.
     * This ID is equivalent to the google_product_category ID as described in
     * this article: https://support.google.com/merchants/answer/6324436.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value id = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param \Google\Protobuf\Int64Value $var
     * @return $this
     */
    public function setId($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Int64Value::class);
        $this->id = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\Int64Value object.

     * Output only. ID of the product bidding category.
     * This ID is equivalent to the google_product_category ID as described in
     * this article: https://support.google.com/merchants/answer/6324436.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value id = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param int|string|null $var
     * @return $this
     */
    public function setIdUnwrapped($var)
    {
        $this->writeWrapperValue("id", $var);
        return $this;}

    /**
     * Output only. Two-letter upper-case country code of the product bidding category.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue country_code = 3 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getCountryCode()
    {
        return $this->country_code;
    }

    /**
     * Returns the unboxed value from <code>getCountryCode()</code>

     * Output only. Two-letter upper-case country code of the product bidding category.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue country_code = 3 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return string|null
     */
    public function getCountryCodeUnwrapped()
    {
        return $this->readWrapperValue("country_code");
    }

    /**
     * Output only. Two-letter upper-case country code of the product bidding category.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue country_code = 3 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setCountryCode($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->country_code = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * Output only. Two-letter upper-case country code of the product bidding category.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue country_code = 3 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param string|null $var
     * @return $this
     */
    public function setCountryCodeUnwrapped($var)
    {
        $this->writeWrapperValue("country_code", $var);
        return $this;}

    /**
     * Output only. Resource name of the parent product bidding category.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue product_bidding_category_constant_parent = 4 [(.google.api.field_behavior) = OUTPUT_ONLY, (.google.api.resource_reference) = {</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getProductBiddingCategoryConstantParent()
    {
        return $this->product_bidding_category_constant_parent;
    }

    /**
     * Returns the unboxed value from <code>getProductBiddingCategoryConstantParent()</code>

     * Output only. Resource name of the parent product bidding category.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue product_bidding_category_constant_parent = 4 [(.google.api.field_behavior) = OUTPUT_ONLY, (.google.api.resource_reference) = {</code>
     * @return string|null
     */
    public function getProductBiddingCategoryConstantParentUnwrapped()
    {
        return $this->readWrapperValue("product_bidding_category_constant_parent");
    }

    /**
     * Output only. Resource name of the parent product bidding category.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue product_bidding_category_constant_parent = 4 [(.google.api.field_behavior) = OUTPUT_ONLY, (.google.api.resource_reference) = {</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setProductBiddingCategoryConstantParent($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->product_bidding_category_constant_parent = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * Output only. Resource name of the parent product bidding category.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue product_bidding_category_constant_parent = 4 [(.google.api.field_behavior) = OUTPUT_ONLY, (.google.api.resource_reference) = {</code>
     * @param string|null $var
     * @return $this
     */
    public function setProductBiddingCategoryConstantParentUnwrapped($var)
    {
        $this->writeWrapperValue("product_bidding_category_constant_parent", $var);
        return $this;}

    /**
     * Output only. Level of the product bidding category.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.enums.ProductBiddingCategoryLevelEnum.ProductBiddingCategoryLevel level = 5 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Output only. Level of the product bidding category.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.enums.ProductBiddingCategoryLevelEnum.ProductBiddingCategoryLevel level = 5 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param int $var
     * @return $this
     */
    public function setLevel($var)
    {
        GPBUtil::checkEnum($var, \Google\Ads\GoogleAds\V4\Enums\ProductBiddingCategoryLevelEnum_ProductBiddingCategoryLevel::class);
        $this->level = $var;

        return $this;
    }

    /**
     * Output only. Status of the product bidding category.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.enums.ProductBiddingCategoryStatusEnum.ProductBiddingCategoryStatus status = 6 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Output only. Status of the product bidding category.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.enums.ProductBiddingCategoryStatusEnum.ProductBiddingCategoryStatus status = 6 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param int $var
     * @return $this
     */
    public function setStatus($var)
    {
        GPBUtil::checkEnum($var, \Google\Ads\GoogleAds\V4\Enums\ProductBiddingCategoryStatusEnum_ProductBiddingCategoryStatus::class);
        $this->status = $var;

        return $this;
    }

    /**
     * Output only. Language code of the product bidding category.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue language_code = 7 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getLanguageCode()
    {
        return $this->language_code;
    }

    /**
     * Returns the unboxed value from <code>getLanguageCode()</code>

     * Output only. Language code of the product bidding category.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue language_code = 7 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return string|null
     */
    public function getLanguageCodeUnwrapped()
    {
        return $this->readWrapperValue("language_code");
    }

    /**
     * Output only. Language code of the product bidding category.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue language_code = 7 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setLanguageCode($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->language_code = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * Output only. Language code of the product bidding category.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue language_code = 7 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param string|null $var
     * @return $this
     */
    public function setLanguageCodeUnwrapped($var)
    {
        $this->writeWrapperValue("language_code", $var);
        return $this;}

    /**
     * Output only. Display value of the product bidding category localized according to
     * language_code.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue localized_name = 8 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getLocalizedName()
    {
        return $this->localized_name;
    }

    /**
     * Returns the unboxed value from <code>getLocalizedName()</code>

     * Output only. Display value of the product bidding category localized according to
     * language_code.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue localized_name = 8 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return string|null
     */
    public function getLocalizedNameUnwrapped()
    {
        return $this->readWrapperValue("localized_name");
    }

    /**
     * Output only. Display value of the product bidding category localized according to
     * language_code.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue localized_name = 8 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setLocalizedName($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->localized_name = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * Output only. Display value of the product bidding category localized according to
     * language_code.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue localized_name = 8 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param string|null $var
     * @return $this
     */
    public function setLocalizedNameUnwrapped($var)
    {
        $this->writeWrapperValue("localized_name", $var);
        return $this;}

}

