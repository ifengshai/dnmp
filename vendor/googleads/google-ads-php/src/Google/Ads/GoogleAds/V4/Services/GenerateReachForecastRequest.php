<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/services/reach_plan_service.proto

namespace Google\Ads\GoogleAds\V4\Services;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Request message for [ReachPlanService.GenerateReachForecast][google.ads.googleads.v4.services.ReachPlanService.GenerateReachForecast].
 *
 * Generated from protobuf message <code>google.ads.googleads.v4.services.GenerateReachForecastRequest</code>
 */
class GenerateReachForecastRequest extends \Google\Protobuf\Internal\Message
{
    /**
     * Required. The ID of the customer.
     *
     * Generated from protobuf field <code>string customer_id = 1 [(.google.api.field_behavior) = REQUIRED];</code>
     */
    protected $customer_id = '';
    /**
     * The currency code.
     * Three-character ISO 4217 currency code.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue currency_code = 2;</code>
     */
    protected $currency_code = null;
    /**
     * Required. Campaign duration.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.services.CampaignDuration campaign_duration = 3 [(.google.api.field_behavior) = REQUIRED];</code>
     */
    protected $campaign_duration = null;
    /**
     * Desired cookie frequency cap that will be applied to each planned product.
     * This is equivalent to the frequency cap exposed in Google Ads when creating
     * a campaign, it represents the maximum number of times an ad can be shown to
     * the same user.
     * If not specified no cap is applied.
     * This field is deprecated in v4 and will eventually be removed.
     * Please use cookie_frequency_cap_setting instead.
     *
     * Generated from protobuf field <code>.google.protobuf.Int32Value cookie_frequency_cap = 4;</code>
     */
    protected $cookie_frequency_cap = null;
    /**
     * Desired cookie frequency cap that will be applied to each planned product.
     * This is equivalent to the frequency cap exposed in Google Ads when creating
     * a campaign, it represents the maximum number of times an ad can be shown to
     * the same user during a specified time interval.
     * If not specified, no cap is applied.
     * This field replaces the deprecated cookie_frequency_cap field.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.services.FrequencyCap cookie_frequency_cap_setting = 8;</code>
     */
    protected $cookie_frequency_cap_setting = null;
    /**
     * Desired minimum effective frequency (the number of times a person was
     * exposed to the ad) for the reported reach metrics [1-10].
     * This won't affect the targeting, but just the reporting.
     * If not specified, a default of 1 is applied.
     *
     * Generated from protobuf field <code>.google.protobuf.Int32Value min_effective_frequency = 5;</code>
     */
    protected $min_effective_frequency = null;
    /**
     * The targeting to be applied to all products selected in the product mix.
     * This is planned targeting: execution details might vary based on the
     * advertising product, please consult an implementation specialist.
     * See specific metrics for details on how targeting affects them.
     * In some cases, targeting may be overridden using the
     * PlannedProduct.advanced_product_targeting field.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.services.Targeting targeting = 6;</code>
     */
    protected $targeting = null;
    /**
     * Required. The products to be forecast.
     * The max number of allowed planned products is 15.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v4.services.PlannedProduct planned_products = 7 [(.google.api.field_behavior) = REQUIRED];</code>
     */
    private $planned_products;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $customer_id
     *           Required. The ID of the customer.
     *     @type \Google\Protobuf\StringValue $currency_code
     *           The currency code.
     *           Three-character ISO 4217 currency code.
     *     @type \Google\Ads\GoogleAds\V4\Services\CampaignDuration $campaign_duration
     *           Required. Campaign duration.
     *     @type \Google\Protobuf\Int32Value $cookie_frequency_cap
     *           Desired cookie frequency cap that will be applied to each planned product.
     *           This is equivalent to the frequency cap exposed in Google Ads when creating
     *           a campaign, it represents the maximum number of times an ad can be shown to
     *           the same user.
     *           If not specified no cap is applied.
     *           This field is deprecated in v4 and will eventually be removed.
     *           Please use cookie_frequency_cap_setting instead.
     *     @type \Google\Ads\GoogleAds\V4\Services\FrequencyCap $cookie_frequency_cap_setting
     *           Desired cookie frequency cap that will be applied to each planned product.
     *           This is equivalent to the frequency cap exposed in Google Ads when creating
     *           a campaign, it represents the maximum number of times an ad can be shown to
     *           the same user during a specified time interval.
     *           If not specified, no cap is applied.
     *           This field replaces the deprecated cookie_frequency_cap field.
     *     @type \Google\Protobuf\Int32Value $min_effective_frequency
     *           Desired minimum effective frequency (the number of times a person was
     *           exposed to the ad) for the reported reach metrics [1-10].
     *           This won't affect the targeting, but just the reporting.
     *           If not specified, a default of 1 is applied.
     *     @type \Google\Ads\GoogleAds\V4\Services\Targeting $targeting
     *           The targeting to be applied to all products selected in the product mix.
     *           This is planned targeting: execution details might vary based on the
     *           advertising product, please consult an implementation specialist.
     *           See specific metrics for details on how targeting affects them.
     *           In some cases, targeting may be overridden using the
     *           PlannedProduct.advanced_product_targeting field.
     *     @type \Google\Ads\GoogleAds\V4\Services\PlannedProduct[]|\Google\Protobuf\Internal\RepeatedField $planned_products
     *           Required. The products to be forecast.
     *           The max number of allowed planned products is 15.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V4\Services\ReachPlanService::initOnce();
        parent::__construct($data);
    }

    /**
     * Required. The ID of the customer.
     *
     * Generated from protobuf field <code>string customer_id = 1 [(.google.api.field_behavior) = REQUIRED];</code>
     * @return string
     */
    public function getCustomerId()
    {
        return $this->customer_id;
    }

    /**
     * Required. The ID of the customer.
     *
     * Generated from protobuf field <code>string customer_id = 1 [(.google.api.field_behavior) = REQUIRED];</code>
     * @param string $var
     * @return $this
     */
    public function setCustomerId($var)
    {
        GPBUtil::checkString($var, True);
        $this->customer_id = $var;

        return $this;
    }

    /**
     * The currency code.
     * Three-character ISO 4217 currency code.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue currency_code = 2;</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getCurrencyCode()
    {
        return $this->currency_code;
    }

    /**
     * Returns the unboxed value from <code>getCurrencyCode()</code>

     * The currency code.
     * Three-character ISO 4217 currency code.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue currency_code = 2;</code>
     * @return string|null
     */
    public function getCurrencyCodeUnwrapped()
    {
        return $this->readWrapperValue("currency_code");
    }

    /**
     * The currency code.
     * Three-character ISO 4217 currency code.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue currency_code = 2;</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setCurrencyCode($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->currency_code = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * The currency code.
     * Three-character ISO 4217 currency code.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue currency_code = 2;</code>
     * @param string|null $var
     * @return $this
     */
    public function setCurrencyCodeUnwrapped($var)
    {
        $this->writeWrapperValue("currency_code", $var);
        return $this;}

    /**
     * Required. Campaign duration.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.services.CampaignDuration campaign_duration = 3 [(.google.api.field_behavior) = REQUIRED];</code>
     * @return \Google\Ads\GoogleAds\V4\Services\CampaignDuration
     */
    public function getCampaignDuration()
    {
        return $this->campaign_duration;
    }

    /**
     * Required. Campaign duration.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.services.CampaignDuration campaign_duration = 3 [(.google.api.field_behavior) = REQUIRED];</code>
     * @param \Google\Ads\GoogleAds\V4\Services\CampaignDuration $var
     * @return $this
     */
    public function setCampaignDuration($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V4\Services\CampaignDuration::class);
        $this->campaign_duration = $var;

        return $this;
    }

    /**
     * Desired cookie frequency cap that will be applied to each planned product.
     * This is equivalent to the frequency cap exposed in Google Ads when creating
     * a campaign, it represents the maximum number of times an ad can be shown to
     * the same user.
     * If not specified no cap is applied.
     * This field is deprecated in v4 and will eventually be removed.
     * Please use cookie_frequency_cap_setting instead.
     *
     * Generated from protobuf field <code>.google.protobuf.Int32Value cookie_frequency_cap = 4;</code>
     * @return \Google\Protobuf\Int32Value
     */
    public function getCookieFrequencyCap()
    {
        return $this->cookie_frequency_cap;
    }

    /**
     * Returns the unboxed value from <code>getCookieFrequencyCap()</code>

     * Desired cookie frequency cap that will be applied to each planned product.
     * This is equivalent to the frequency cap exposed in Google Ads when creating
     * a campaign, it represents the maximum number of times an ad can be shown to
     * the same user.
     * If not specified no cap is applied.
     * This field is deprecated in v4 and will eventually be removed.
     * Please use cookie_frequency_cap_setting instead.
     *
     * Generated from protobuf field <code>.google.protobuf.Int32Value cookie_frequency_cap = 4;</code>
     * @return int|null
     */
    public function getCookieFrequencyCapUnwrapped()
    {
        return $this->readWrapperValue("cookie_frequency_cap");
    }

    /**
     * Desired cookie frequency cap that will be applied to each planned product.
     * This is equivalent to the frequency cap exposed in Google Ads when creating
     * a campaign, it represents the maximum number of times an ad can be shown to
     * the same user.
     * If not specified no cap is applied.
     * This field is deprecated in v4 and will eventually be removed.
     * Please use cookie_frequency_cap_setting instead.
     *
     * Generated from protobuf field <code>.google.protobuf.Int32Value cookie_frequency_cap = 4;</code>
     * @param \Google\Protobuf\Int32Value $var
     * @return $this
     */
    public function setCookieFrequencyCap($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Int32Value::class);
        $this->cookie_frequency_cap = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\Int32Value object.

     * Desired cookie frequency cap that will be applied to each planned product.
     * This is equivalent to the frequency cap exposed in Google Ads when creating
     * a campaign, it represents the maximum number of times an ad can be shown to
     * the same user.
     * If not specified no cap is applied.
     * This field is deprecated in v4 and will eventually be removed.
     * Please use cookie_frequency_cap_setting instead.
     *
     * Generated from protobuf field <code>.google.protobuf.Int32Value cookie_frequency_cap = 4;</code>
     * @param int|null $var
     * @return $this
     */
    public function setCookieFrequencyCapUnwrapped($var)
    {
        $this->writeWrapperValue("cookie_frequency_cap", $var);
        return $this;}

    /**
     * Desired cookie frequency cap that will be applied to each planned product.
     * This is equivalent to the frequency cap exposed in Google Ads when creating
     * a campaign, it represents the maximum number of times an ad can be shown to
     * the same user during a specified time interval.
     * If not specified, no cap is applied.
     * This field replaces the deprecated cookie_frequency_cap field.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.services.FrequencyCap cookie_frequency_cap_setting = 8;</code>
     * @return \Google\Ads\GoogleAds\V4\Services\FrequencyCap
     */
    public function getCookieFrequencyCapSetting()
    {
        return $this->cookie_frequency_cap_setting;
    }

    /**
     * Desired cookie frequency cap that will be applied to each planned product.
     * This is equivalent to the frequency cap exposed in Google Ads when creating
     * a campaign, it represents the maximum number of times an ad can be shown to
     * the same user during a specified time interval.
     * If not specified, no cap is applied.
     * This field replaces the deprecated cookie_frequency_cap field.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.services.FrequencyCap cookie_frequency_cap_setting = 8;</code>
     * @param \Google\Ads\GoogleAds\V4\Services\FrequencyCap $var
     * @return $this
     */
    public function setCookieFrequencyCapSetting($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V4\Services\FrequencyCap::class);
        $this->cookie_frequency_cap_setting = $var;

        return $this;
    }

    /**
     * Desired minimum effective frequency (the number of times a person was
     * exposed to the ad) for the reported reach metrics [1-10].
     * This won't affect the targeting, but just the reporting.
     * If not specified, a default of 1 is applied.
     *
     * Generated from protobuf field <code>.google.protobuf.Int32Value min_effective_frequency = 5;</code>
     * @return \Google\Protobuf\Int32Value
     */
    public function getMinEffectiveFrequency()
    {
        return $this->min_effective_frequency;
    }

    /**
     * Returns the unboxed value from <code>getMinEffectiveFrequency()</code>

     * Desired minimum effective frequency (the number of times a person was
     * exposed to the ad) for the reported reach metrics [1-10].
     * This won't affect the targeting, but just the reporting.
     * If not specified, a default of 1 is applied.
     *
     * Generated from protobuf field <code>.google.protobuf.Int32Value min_effective_frequency = 5;</code>
     * @return int|null
     */
    public function getMinEffectiveFrequencyUnwrapped()
    {
        return $this->readWrapperValue("min_effective_frequency");
    }

    /**
     * Desired minimum effective frequency (the number of times a person was
     * exposed to the ad) for the reported reach metrics [1-10].
     * This won't affect the targeting, but just the reporting.
     * If not specified, a default of 1 is applied.
     *
     * Generated from protobuf field <code>.google.protobuf.Int32Value min_effective_frequency = 5;</code>
     * @param \Google\Protobuf\Int32Value $var
     * @return $this
     */
    public function setMinEffectiveFrequency($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Int32Value::class);
        $this->min_effective_frequency = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\Int32Value object.

     * Desired minimum effective frequency (the number of times a person was
     * exposed to the ad) for the reported reach metrics [1-10].
     * This won't affect the targeting, but just the reporting.
     * If not specified, a default of 1 is applied.
     *
     * Generated from protobuf field <code>.google.protobuf.Int32Value min_effective_frequency = 5;</code>
     * @param int|null $var
     * @return $this
     */
    public function setMinEffectiveFrequencyUnwrapped($var)
    {
        $this->writeWrapperValue("min_effective_frequency", $var);
        return $this;}

    /**
     * The targeting to be applied to all products selected in the product mix.
     * This is planned targeting: execution details might vary based on the
     * advertising product, please consult an implementation specialist.
     * See specific metrics for details on how targeting affects them.
     * In some cases, targeting may be overridden using the
     * PlannedProduct.advanced_product_targeting field.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.services.Targeting targeting = 6;</code>
     * @return \Google\Ads\GoogleAds\V4\Services\Targeting
     */
    public function getTargeting()
    {
        return $this->targeting;
    }

    /**
     * The targeting to be applied to all products selected in the product mix.
     * This is planned targeting: execution details might vary based on the
     * advertising product, please consult an implementation specialist.
     * See specific metrics for details on how targeting affects them.
     * In some cases, targeting may be overridden using the
     * PlannedProduct.advanced_product_targeting field.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.services.Targeting targeting = 6;</code>
     * @param \Google\Ads\GoogleAds\V4\Services\Targeting $var
     * @return $this
     */
    public function setTargeting($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V4\Services\Targeting::class);
        $this->targeting = $var;

        return $this;
    }

    /**
     * Required. The products to be forecast.
     * The max number of allowed planned products is 15.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v4.services.PlannedProduct planned_products = 7 [(.google.api.field_behavior) = REQUIRED];</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getPlannedProducts()
    {
        return $this->planned_products;
    }

    /**
     * Required. The products to be forecast.
     * The max number of allowed planned products is 15.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v4.services.PlannedProduct planned_products = 7 [(.google.api.field_behavior) = REQUIRED];</code>
     * @param \Google\Ads\GoogleAds\V4\Services\PlannedProduct[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setPlannedProducts($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Ads\GoogleAds\V4\Services\PlannedProduct::class);
        $this->planned_products = $arr;

        return $this;
    }

}

