<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/services/keyword_plan_service.proto

namespace Google\Ads\GoogleAds\V4\Services;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Response message for [KeywordPlanService.GenerateForecastCurve][google.ads.googleads.v4.services.KeywordPlanService.GenerateForecastCurve].
 *
 * Generated from protobuf message <code>google.ads.googleads.v4.services.GenerateForecastCurveResponse</code>
 */
class GenerateForecastCurveResponse extends \Google\Protobuf\Internal\Message
{
    /**
     * List of forecast curves for the keyword plan campaign.
     * One maximum.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v4.services.KeywordPlanCampaignForecastCurve campaign_forecast_curves = 1;</code>
     */
    private $campaign_forecast_curves;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Ads\GoogleAds\V4\Services\KeywordPlanCampaignForecastCurve[]|\Google\Protobuf\Internal\RepeatedField $campaign_forecast_curves
     *           List of forecast curves for the keyword plan campaign.
     *           One maximum.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V4\Services\KeywordPlanService::initOnce();
        parent::__construct($data);
    }

    /**
     * List of forecast curves for the keyword plan campaign.
     * One maximum.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v4.services.KeywordPlanCampaignForecastCurve campaign_forecast_curves = 1;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getCampaignForecastCurves()
    {
        return $this->campaign_forecast_curves;
    }

    /**
     * List of forecast curves for the keyword plan campaign.
     * One maximum.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v4.services.KeywordPlanCampaignForecastCurve campaign_forecast_curves = 1;</code>
     * @param \Google\Ads\GoogleAds\V4\Services\KeywordPlanCampaignForecastCurve[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setCampaignForecastCurves($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Ads\GoogleAds\V4\Services\KeywordPlanCampaignForecastCurve::class);
        $this->campaign_forecast_curves = $arr;

        return $this;
    }

}

