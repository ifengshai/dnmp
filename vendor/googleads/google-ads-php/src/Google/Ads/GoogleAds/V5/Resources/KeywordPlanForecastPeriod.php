<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/resources/keyword_plan.proto

namespace Google\Ads\GoogleAds\V5\Resources;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * The forecasting period associated with the keyword plan.
 *
 * Generated from protobuf message <code>google.ads.googleads.v5.resources.KeywordPlanForecastPeriod</code>
 */
class KeywordPlanForecastPeriod extends \Google\Protobuf\Internal\Message
{
    protected $interval;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int $date_interval
     *           A future date range relative to the current date used for forecasting.
     *     @type \Google\Ads\GoogleAds\V5\Common\DateRange $date_range
     *           The custom date range used for forecasting.
     *           The start and end dates must be in the future. Otherwise, an error will
     *           be returned when the forecasting action is performed.
     *           The start and end dates are inclusive.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V5\Resources\KeywordPlan::initOnce();
        parent::__construct($data);
    }

    /**
     * A future date range relative to the current date used for forecasting.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.enums.KeywordPlanForecastIntervalEnum.KeywordPlanForecastInterval date_interval = 1;</code>
     * @return int
     */
    public function getDateInterval()
    {
        return $this->readOneof(1);
    }

    public function hasDateInterval()
    {
        return $this->hasOneof(1);
    }

    /**
     * A future date range relative to the current date used for forecasting.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.enums.KeywordPlanForecastIntervalEnum.KeywordPlanForecastInterval date_interval = 1;</code>
     * @param int $var
     * @return $this
     */
    public function setDateInterval($var)
    {
        GPBUtil::checkEnum($var, \Google\Ads\GoogleAds\V5\Enums\KeywordPlanForecastIntervalEnum\KeywordPlanForecastInterval::class);
        $this->writeOneof(1, $var);

        return $this;
    }

    /**
     * The custom date range used for forecasting.
     * The start and end dates must be in the future. Otherwise, an error will
     * be returned when the forecasting action is performed.
     * The start and end dates are inclusive.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.common.DateRange date_range = 2;</code>
     * @return \Google\Ads\GoogleAds\V5\Common\DateRange
     */
    public function getDateRange()
    {
        return $this->readOneof(2);
    }

    public function hasDateRange()
    {
        return $this->hasOneof(2);
    }

    /**
     * The custom date range used for forecasting.
     * The start and end dates must be in the future. Otherwise, an error will
     * be returned when the forecasting action is performed.
     * The start and end dates are inclusive.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.common.DateRange date_range = 2;</code>
     * @param \Google\Ads\GoogleAds\V5\Common\DateRange $var
     * @return $this
     */
    public function setDateRange($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V5\Common\DateRange::class);
        $this->writeOneof(2, $var);

        return $this;
    }

    /**
     * @return string
     */
    public function getInterval()
    {
        return $this->whichOneof("interval");
    }

}

