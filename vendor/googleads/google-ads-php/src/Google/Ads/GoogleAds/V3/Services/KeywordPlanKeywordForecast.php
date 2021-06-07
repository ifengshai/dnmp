<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/services/keyword_plan_service.proto

namespace Google\Ads\GoogleAds\V3\Services;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * A keyword forecast.
 *
 * Generated from protobuf message <code>google.ads.googleads.v3.services.KeywordPlanKeywordForecast</code>
 */
class KeywordPlanKeywordForecast extends \Google\Protobuf\Internal\Message
{
    /**
     * The resource name of the Keyword Plan keyword related to the forecast.
     * `customers/{customer_id}/keywordPlanAdGroupKeywords/{keyword_plan_ad_group_keyword_id}`
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue keyword_plan_ad_group_keyword = 1;</code>
     */
    protected $keyword_plan_ad_group_keyword = null;
    /**
     * The forecast for the Keyword Plan keyword.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v3.services.ForecastMetrics keyword_forecast = 2;</code>
     */
    protected $keyword_forecast = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Protobuf\StringValue $keyword_plan_ad_group_keyword
     *           The resource name of the Keyword Plan keyword related to the forecast.
     *           `customers/{customer_id}/keywordPlanAdGroupKeywords/{keyword_plan_ad_group_keyword_id}`
     *     @type \Google\Ads\GoogleAds\V3\Services\ForecastMetrics $keyword_forecast
     *           The forecast for the Keyword Plan keyword.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V3\Services\KeywordPlanService::initOnce();
        parent::__construct($data);
    }

    /**
     * The resource name of the Keyword Plan keyword related to the forecast.
     * `customers/{customer_id}/keywordPlanAdGroupKeywords/{keyword_plan_ad_group_keyword_id}`
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue keyword_plan_ad_group_keyword = 1;</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getKeywordPlanAdGroupKeyword()
    {
        return $this->keyword_plan_ad_group_keyword;
    }

    /**
     * Returns the unboxed value from <code>getKeywordPlanAdGroupKeyword()</code>

     * The resource name of the Keyword Plan keyword related to the forecast.
     * `customers/{customer_id}/keywordPlanAdGroupKeywords/{keyword_plan_ad_group_keyword_id}`
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue keyword_plan_ad_group_keyword = 1;</code>
     * @return string|null
     */
    public function getKeywordPlanAdGroupKeywordUnwrapped()
    {
        return $this->readWrapperValue("keyword_plan_ad_group_keyword");
    }

    /**
     * The resource name of the Keyword Plan keyword related to the forecast.
     * `customers/{customer_id}/keywordPlanAdGroupKeywords/{keyword_plan_ad_group_keyword_id}`
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue keyword_plan_ad_group_keyword = 1;</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setKeywordPlanAdGroupKeyword($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->keyword_plan_ad_group_keyword = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * The resource name of the Keyword Plan keyword related to the forecast.
     * `customers/{customer_id}/keywordPlanAdGroupKeywords/{keyword_plan_ad_group_keyword_id}`
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue keyword_plan_ad_group_keyword = 1;</code>
     * @param string|null $var
     * @return $this
     */
    public function setKeywordPlanAdGroupKeywordUnwrapped($var)
    {
        $this->writeWrapperValue("keyword_plan_ad_group_keyword", $var);
        return $this;}

    /**
     * The forecast for the Keyword Plan keyword.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v3.services.ForecastMetrics keyword_forecast = 2;</code>
     * @return \Google\Ads\GoogleAds\V3\Services\ForecastMetrics
     */
    public function getKeywordForecast()
    {
        return $this->keyword_forecast;
    }

    /**
     * The forecast for the Keyword Plan keyword.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v3.services.ForecastMetrics keyword_forecast = 2;</code>
     * @param \Google\Ads\GoogleAds\V3\Services\ForecastMetrics $var
     * @return $this
     */
    public function setKeywordForecast($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V3\Services\ForecastMetrics::class);
        $this->keyword_forecast = $var;

        return $this;
    }

}

