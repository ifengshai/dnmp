<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/services/reach_plan_service.proto

namespace Google\Ads\GoogleAds\V5\Services;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Request message for [ReachPlanService.GenerateProductMixIdeas][google.ads.googleads.v5.services.ReachPlanService.GenerateProductMixIdeas].
 *
 * Generated from protobuf message <code>google.ads.googleads.v5.services.GenerateProductMixIdeasRequest</code>
 */
class GenerateProductMixIdeasRequest extends \Google\Protobuf\Internal\Message
{
    /**
     * Required. The ID of the customer.
     *
     * Generated from protobuf field <code>string customer_id = 1 [(.google.api.field_behavior) = REQUIRED];</code>
     */
    protected $customer_id = '';
    /**
     * Required. The ID of the location, this is one of the ids returned by
     * ListPlannableLocations.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue plannable_location_id = 2 [(.google.api.field_behavior) = REQUIRED];</code>
     */
    protected $plannable_location_id = null;
    /**
     * Required. Currency code.
     * Three-character ISO 4217 currency code.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue currency_code = 3 [(.google.api.field_behavior) = REQUIRED];</code>
     */
    protected $currency_code = null;
    /**
     * Required. Total budget.
     * Amount in micros. One million is equivalent to one unit.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value budget_micros = 4 [(.google.api.field_behavior) = REQUIRED];</code>
     */
    protected $budget_micros = null;
    /**
     * The preferences of the suggested product mix.
     * An unset preference is interpreted as all possible values are allowed,
     * unless explicitly specified.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.services.Preferences preferences = 5;</code>
     */
    protected $preferences = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $customer_id
     *           Required. The ID of the customer.
     *     @type \Google\Protobuf\StringValue $plannable_location_id
     *           Required. The ID of the location, this is one of the ids returned by
     *           ListPlannableLocations.
     *     @type \Google\Protobuf\StringValue $currency_code
     *           Required. Currency code.
     *           Three-character ISO 4217 currency code.
     *     @type \Google\Protobuf\Int64Value $budget_micros
     *           Required. Total budget.
     *           Amount in micros. One million is equivalent to one unit.
     *     @type \Google\Ads\GoogleAds\V5\Services\Preferences $preferences
     *           The preferences of the suggested product mix.
     *           An unset preference is interpreted as all possible values are allowed,
     *           unless explicitly specified.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V5\Services\ReachPlanService::initOnce();
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
     * Required. The ID of the location, this is one of the ids returned by
     * ListPlannableLocations.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue plannable_location_id = 2 [(.google.api.field_behavior) = REQUIRED];</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getPlannableLocationId()
    {
        return isset($this->plannable_location_id) ? $this->plannable_location_id : null;
    }

    public function hasPlannableLocationId()
    {
        return isset($this->plannable_location_id);
    }

    public function clearPlannableLocationId()
    {
        unset($this->plannable_location_id);
    }

    /**
     * Returns the unboxed value from <code>getPlannableLocationId()</code>

     * Required. The ID of the location, this is one of the ids returned by
     * ListPlannableLocations.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue plannable_location_id = 2 [(.google.api.field_behavior) = REQUIRED];</code>
     * @return string|null
     */
    public function getPlannableLocationIdUnwrapped()
    {
        return $this->readWrapperValue("plannable_location_id");
    }

    /**
     * Required. The ID of the location, this is one of the ids returned by
     * ListPlannableLocations.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue plannable_location_id = 2 [(.google.api.field_behavior) = REQUIRED];</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setPlannableLocationId($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->plannable_location_id = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * Required. The ID of the location, this is one of the ids returned by
     * ListPlannableLocations.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue plannable_location_id = 2 [(.google.api.field_behavior) = REQUIRED];</code>
     * @param string|null $var
     * @return $this
     */
    public function setPlannableLocationIdUnwrapped($var)
    {
        $this->writeWrapperValue("plannable_location_id", $var);
        return $this;}

    /**
     * Required. Currency code.
     * Three-character ISO 4217 currency code.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue currency_code = 3 [(.google.api.field_behavior) = REQUIRED];</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getCurrencyCode()
    {
        return isset($this->currency_code) ? $this->currency_code : null;
    }

    public function hasCurrencyCode()
    {
        return isset($this->currency_code);
    }

    public function clearCurrencyCode()
    {
        unset($this->currency_code);
    }

    /**
     * Returns the unboxed value from <code>getCurrencyCode()</code>

     * Required. Currency code.
     * Three-character ISO 4217 currency code.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue currency_code = 3 [(.google.api.field_behavior) = REQUIRED];</code>
     * @return string|null
     */
    public function getCurrencyCodeUnwrapped()
    {
        return $this->readWrapperValue("currency_code");
    }

    /**
     * Required. Currency code.
     * Three-character ISO 4217 currency code.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue currency_code = 3 [(.google.api.field_behavior) = REQUIRED];</code>
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

     * Required. Currency code.
     * Three-character ISO 4217 currency code.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue currency_code = 3 [(.google.api.field_behavior) = REQUIRED];</code>
     * @param string|null $var
     * @return $this
     */
    public function setCurrencyCodeUnwrapped($var)
    {
        $this->writeWrapperValue("currency_code", $var);
        return $this;}

    /**
     * Required. Total budget.
     * Amount in micros. One million is equivalent to one unit.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value budget_micros = 4 [(.google.api.field_behavior) = REQUIRED];</code>
     * @return \Google\Protobuf\Int64Value
     */
    public function getBudgetMicros()
    {
        return isset($this->budget_micros) ? $this->budget_micros : null;
    }

    public function hasBudgetMicros()
    {
        return isset($this->budget_micros);
    }

    public function clearBudgetMicros()
    {
        unset($this->budget_micros);
    }

    /**
     * Returns the unboxed value from <code>getBudgetMicros()</code>

     * Required. Total budget.
     * Amount in micros. One million is equivalent to one unit.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value budget_micros = 4 [(.google.api.field_behavior) = REQUIRED];</code>
     * @return int|string|null
     */
    public function getBudgetMicrosUnwrapped()
    {
        return $this->readWrapperValue("budget_micros");
    }

    /**
     * Required. Total budget.
     * Amount in micros. One million is equivalent to one unit.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value budget_micros = 4 [(.google.api.field_behavior) = REQUIRED];</code>
     * @param \Google\Protobuf\Int64Value $var
     * @return $this
     */
    public function setBudgetMicros($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Int64Value::class);
        $this->budget_micros = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\Int64Value object.

     * Required. Total budget.
     * Amount in micros. One million is equivalent to one unit.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value budget_micros = 4 [(.google.api.field_behavior) = REQUIRED];</code>
     * @param int|string|null $var
     * @return $this
     */
    public function setBudgetMicrosUnwrapped($var)
    {
        $this->writeWrapperValue("budget_micros", $var);
        return $this;}

    /**
     * The preferences of the suggested product mix.
     * An unset preference is interpreted as all possible values are allowed,
     * unless explicitly specified.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.services.Preferences preferences = 5;</code>
     * @return \Google\Ads\GoogleAds\V5\Services\Preferences
     */
    public function getPreferences()
    {
        return isset($this->preferences) ? $this->preferences : null;
    }

    public function hasPreferences()
    {
        return isset($this->preferences);
    }

    public function clearPreferences()
    {
        unset($this->preferences);
    }

    /**
     * The preferences of the suggested product mix.
     * An unset preference is interpreted as all possible values are allowed,
     * unless explicitly specified.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.services.Preferences preferences = 5;</code>
     * @param \Google\Ads\GoogleAds\V5\Services\Preferences $var
     * @return $this
     */
    public function setPreferences($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V5\Services\Preferences::class);
        $this->preferences = $var;

        return $this;
    }

}

