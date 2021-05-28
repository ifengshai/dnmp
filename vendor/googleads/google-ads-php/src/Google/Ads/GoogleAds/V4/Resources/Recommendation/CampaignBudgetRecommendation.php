<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/resources/recommendation.proto

namespace Google\Ads\GoogleAds\V4\Resources\Recommendation;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * The budget recommendation for budget constrained campaigns.
 *
 * Generated from protobuf message <code>google.ads.googleads.v4.resources.Recommendation.CampaignBudgetRecommendation</code>
 */
class CampaignBudgetRecommendation extends \Google\Protobuf\Internal\Message
{
    /**
     * Output only. The current budget amount in micros.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value current_budget_amount_micros = 1 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $current_budget_amount_micros = null;
    /**
     * Output only. The recommended budget amount in micros.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value recommended_budget_amount_micros = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $recommended_budget_amount_micros = null;
    /**
     * Output only. The budget amounts and associated impact estimates for some values of
     * possible budget amounts.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v4.resources.Recommendation.CampaignBudgetRecommendation.CampaignBudgetRecommendationOption budget_options = 3 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    private $budget_options;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Protobuf\Int64Value $current_budget_amount_micros
     *           Output only. The current budget amount in micros.
     *     @type \Google\Protobuf\Int64Value $recommended_budget_amount_micros
     *           Output only. The recommended budget amount in micros.
     *     @type \Google\Ads\GoogleAds\V4\Resources\Recommendation\CampaignBudgetRecommendation\CampaignBudgetRecommendationOption[]|\Google\Protobuf\Internal\RepeatedField $budget_options
     *           Output only. The budget amounts and associated impact estimates for some values of
     *           possible budget amounts.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V4\Resources\Recommendation::initOnce();
        parent::__construct($data);
    }

    /**
     * Output only. The current budget amount in micros.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value current_budget_amount_micros = 1 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\Int64Value
     */
    public function getCurrentBudgetAmountMicros()
    {
        return $this->current_budget_amount_micros;
    }

    /**
     * Returns the unboxed value from <code>getCurrentBudgetAmountMicros()</code>

     * Output only. The current budget amount in micros.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value current_budget_amount_micros = 1 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return int|string|null
     */
    public function getCurrentBudgetAmountMicrosUnwrapped()
    {
        return $this->readWrapperValue("current_budget_amount_micros");
    }

    /**
     * Output only. The current budget amount in micros.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value current_budget_amount_micros = 1 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param \Google\Protobuf\Int64Value $var
     * @return $this
     */
    public function setCurrentBudgetAmountMicros($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Int64Value::class);
        $this->current_budget_amount_micros = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\Int64Value object.

     * Output only. The current budget amount in micros.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value current_budget_amount_micros = 1 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param int|string|null $var
     * @return $this
     */
    public function setCurrentBudgetAmountMicrosUnwrapped($var)
    {
        $this->writeWrapperValue("current_budget_amount_micros", $var);
        return $this;}

    /**
     * Output only. The recommended budget amount in micros.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value recommended_budget_amount_micros = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\Int64Value
     */
    public function getRecommendedBudgetAmountMicros()
    {
        return $this->recommended_budget_amount_micros;
    }

    /**
     * Returns the unboxed value from <code>getRecommendedBudgetAmountMicros()</code>

     * Output only. The recommended budget amount in micros.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value recommended_budget_amount_micros = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return int|string|null
     */
    public function getRecommendedBudgetAmountMicrosUnwrapped()
    {
        return $this->readWrapperValue("recommended_budget_amount_micros");
    }

    /**
     * Output only. The recommended budget amount in micros.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value recommended_budget_amount_micros = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param \Google\Protobuf\Int64Value $var
     * @return $this
     */
    public function setRecommendedBudgetAmountMicros($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Int64Value::class);
        $this->recommended_budget_amount_micros = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\Int64Value object.

     * Output only. The recommended budget amount in micros.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value recommended_budget_amount_micros = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param int|string|null $var
     * @return $this
     */
    public function setRecommendedBudgetAmountMicrosUnwrapped($var)
    {
        $this->writeWrapperValue("recommended_budget_amount_micros", $var);
        return $this;}

    /**
     * Output only. The budget amounts and associated impact estimates for some values of
     * possible budget amounts.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v4.resources.Recommendation.CampaignBudgetRecommendation.CampaignBudgetRecommendationOption budget_options = 3 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getBudgetOptions()
    {
        return $this->budget_options;
    }

    /**
     * Output only. The budget amounts and associated impact estimates for some values of
     * possible budget amounts.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v4.resources.Recommendation.CampaignBudgetRecommendation.CampaignBudgetRecommendationOption budget_options = 3 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param \Google\Ads\GoogleAds\V4\Resources\Recommendation\CampaignBudgetRecommendation\CampaignBudgetRecommendationOption[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setBudgetOptions($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Ads\GoogleAds\V4\Resources\Recommendation\CampaignBudgetRecommendation\CampaignBudgetRecommendationOption::class);
        $this->budget_options = $arr;

        return $this;
    }

}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(CampaignBudgetRecommendation::class, \Google\Ads\GoogleAds\V4\Resources\Recommendation_CampaignBudgetRecommendation::class);

