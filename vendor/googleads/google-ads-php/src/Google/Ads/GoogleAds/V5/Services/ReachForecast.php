<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/services/reach_plan_service.proto

namespace Google\Ads\GoogleAds\V5\Services;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * A point on reach curve.
 *
 * Generated from protobuf message <code>google.ads.googleads.v5.services.ReachForecast</code>
 */
class ReachForecast extends \Google\Protobuf\Internal\Message
{
    /**
     * The cost in micros.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value cost_micros = 1;</code>
     */
    protected $cost_micros = null;
    /**
     * Forecasted traffic metrics for this point.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.services.Forecast forecast = 2;</code>
     */
    protected $forecast = null;
    /**
     * The forecasted allocation. This differs from the input allocation if one
     * or more product cannot fulfill the budget because of limited inventory.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.services.ProductAllocation forecasted_product_allocations = 3;</code>
     */
    private $forecasted_product_allocations;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Protobuf\Int64Value $cost_micros
     *           The cost in micros.
     *     @type \Google\Ads\GoogleAds\V5\Services\Forecast $forecast
     *           Forecasted traffic metrics for this point.
     *     @type \Google\Ads\GoogleAds\V5\Services\ProductAllocation[]|\Google\Protobuf\Internal\RepeatedField $forecasted_product_allocations
     *           The forecasted allocation. This differs from the input allocation if one
     *           or more product cannot fulfill the budget because of limited inventory.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V5\Services\ReachPlanService::initOnce();
        parent::__construct($data);
    }

    /**
     * The cost in micros.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value cost_micros = 1;</code>
     * @return \Google\Protobuf\Int64Value
     */
    public function getCostMicros()
    {
        return isset($this->cost_micros) ? $this->cost_micros : null;
    }

    public function hasCostMicros()
    {
        return isset($this->cost_micros);
    }

    public function clearCostMicros()
    {
        unset($this->cost_micros);
    }

    /**
     * Returns the unboxed value from <code>getCostMicros()</code>

     * The cost in micros.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value cost_micros = 1;</code>
     * @return int|string|null
     */
    public function getCostMicrosUnwrapped()
    {
        return $this->readWrapperValue("cost_micros");
    }

    /**
     * The cost in micros.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value cost_micros = 1;</code>
     * @param \Google\Protobuf\Int64Value $var
     * @return $this
     */
    public function setCostMicros($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Int64Value::class);
        $this->cost_micros = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\Int64Value object.

     * The cost in micros.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value cost_micros = 1;</code>
     * @param int|string|null $var
     * @return $this
     */
    public function setCostMicrosUnwrapped($var)
    {
        $this->writeWrapperValue("cost_micros", $var);
        return $this;}

    /**
     * Forecasted traffic metrics for this point.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.services.Forecast forecast = 2;</code>
     * @return \Google\Ads\GoogleAds\V5\Services\Forecast
     */
    public function getForecast()
    {
        return isset($this->forecast) ? $this->forecast : null;
    }

    public function hasForecast()
    {
        return isset($this->forecast);
    }

    public function clearForecast()
    {
        unset($this->forecast);
    }

    /**
     * Forecasted traffic metrics for this point.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.services.Forecast forecast = 2;</code>
     * @param \Google\Ads\GoogleAds\V5\Services\Forecast $var
     * @return $this
     */
    public function setForecast($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V5\Services\Forecast::class);
        $this->forecast = $var;

        return $this;
    }

    /**
     * The forecasted allocation. This differs from the input allocation if one
     * or more product cannot fulfill the budget because of limited inventory.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.services.ProductAllocation forecasted_product_allocations = 3;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getForecastedProductAllocations()
    {
        return $this->forecasted_product_allocations;
    }

    /**
     * The forecasted allocation. This differs from the input allocation if one
     * or more product cannot fulfill the budget because of limited inventory.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.services.ProductAllocation forecasted_product_allocations = 3;</code>
     * @param \Google\Ads\GoogleAds\V5\Services\ProductAllocation[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setForecastedProductAllocations($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Ads\GoogleAds\V5\Services\ProductAllocation::class);
        $this->forecasted_product_allocations = $arr;

        return $this;
    }

}

