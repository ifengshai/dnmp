<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/services/keyword_plan_service.proto

namespace Google\Ads\GoogleAds\V5\Services;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Response message for [KeywordPlanService.GenerateForecastMetrics][google.ads.googleads.v5.services.KeywordPlanService.GenerateForecastMetrics].
 *
 * Generated from protobuf message <code>google.ads.googleads.v5.services.GenerateForecastMetricsResponse</code>
 */
class GenerateForecastMetricsResponse extends \Google\Protobuf\Internal\Message
{
    /**
     * List of campaign forecasts.
     * One maximum.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.services.KeywordPlanCampaignForecast campaign_forecasts = 1;</code>
     */
    private $campaign_forecasts;
    /**
     * List of ad group forecasts.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.services.KeywordPlanAdGroupForecast ad_group_forecasts = 2;</code>
     */
    private $ad_group_forecasts;
    /**
     * List of keyword forecasts.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.services.KeywordPlanKeywordForecast keyword_forecasts = 3;</code>
     */
    private $keyword_forecasts;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Ads\GoogleAds\V5\Services\KeywordPlanCampaignForecast[]|\Google\Protobuf\Internal\RepeatedField $campaign_forecasts
     *           List of campaign forecasts.
     *           One maximum.
     *     @type \Google\Ads\GoogleAds\V5\Services\KeywordPlanAdGroupForecast[]|\Google\Protobuf\Internal\RepeatedField $ad_group_forecasts
     *           List of ad group forecasts.
     *     @type \Google\Ads\GoogleAds\V5\Services\KeywordPlanKeywordForecast[]|\Google\Protobuf\Internal\RepeatedField $keyword_forecasts
     *           List of keyword forecasts.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V5\Services\KeywordPlanService::initOnce();
        parent::__construct($data);
    }

    /**
     * List of campaign forecasts.
     * One maximum.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.services.KeywordPlanCampaignForecast campaign_forecasts = 1;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getCampaignForecasts()
    {
        return $this->campaign_forecasts;
    }

    /**
     * List of campaign forecasts.
     * One maximum.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.services.KeywordPlanCampaignForecast campaign_forecasts = 1;</code>
     * @param \Google\Ads\GoogleAds\V5\Services\KeywordPlanCampaignForecast[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setCampaignForecasts($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Ads\GoogleAds\V5\Services\KeywordPlanCampaignForecast::class);
        $this->campaign_forecasts = $arr;

        return $this;
    }

    /**
     * List of ad group forecasts.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.services.KeywordPlanAdGroupForecast ad_group_forecasts = 2;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getAdGroupForecasts()
    {
        return $this->ad_group_forecasts;
    }

    /**
     * List of ad group forecasts.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.services.KeywordPlanAdGroupForecast ad_group_forecasts = 2;</code>
     * @param \Google\Ads\GoogleAds\V5\Services\KeywordPlanAdGroupForecast[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setAdGroupForecasts($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Ads\GoogleAds\V5\Services\KeywordPlanAdGroupForecast::class);
        $this->ad_group_forecasts = $arr;

        return $this;
    }

    /**
     * List of keyword forecasts.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.services.KeywordPlanKeywordForecast keyword_forecasts = 3;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getKeywordForecasts()
    {
        return $this->keyword_forecasts;
    }

    /**
     * List of keyword forecasts.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.services.KeywordPlanKeywordForecast keyword_forecasts = 3;</code>
     * @param \Google\Ads\GoogleAds\V5\Services\KeywordPlanKeywordForecast[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setKeywordForecasts($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Ads\GoogleAds\V5\Services\KeywordPlanKeywordForecast::class);
        $this->keyword_forecasts = $arr;

        return $this;
    }

}

