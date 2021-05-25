<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/resources/recommendation.proto

namespace Google\Ads\GoogleAds\V3\Resources\Recommendation;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * The Target CPA opt-in recommendation.
 *
 * Generated from protobuf message <code>google.ads.googleads.v3.resources.Recommendation.TargetCpaOptInRecommendation</code>
 */
class TargetCpaOptInRecommendation extends \Google\Protobuf\Internal\Message
{
    /**
     * Output only. The available goals and corresponding options for Target CPA strategy.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v3.resources.Recommendation.TargetCpaOptInRecommendation.TargetCpaOptInRecommendationOption options = 1 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    private $options;
    /**
     * Output only. The recommended average CPA target. See required budget amount and impact
     * of using this recommendation in options list.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value recommended_target_cpa_micros = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $recommended_target_cpa_micros = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Ads\GoogleAds\V3\Resources\Recommendation\TargetCpaOptInRecommendation\TargetCpaOptInRecommendationOption[]|\Google\Protobuf\Internal\RepeatedField $options
     *           Output only. The available goals and corresponding options for Target CPA strategy.
     *     @type \Google\Protobuf\Int64Value $recommended_target_cpa_micros
     *           Output only. The recommended average CPA target. See required budget amount and impact
     *           of using this recommendation in options list.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V3\Resources\Recommendation::initOnce();
        parent::__construct($data);
    }

    /**
     * Output only. The available goals and corresponding options for Target CPA strategy.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v3.resources.Recommendation.TargetCpaOptInRecommendation.TargetCpaOptInRecommendationOption options = 1 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Output only. The available goals and corresponding options for Target CPA strategy.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v3.resources.Recommendation.TargetCpaOptInRecommendation.TargetCpaOptInRecommendationOption options = 1 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param \Google\Ads\GoogleAds\V3\Resources\Recommendation\TargetCpaOptInRecommendation\TargetCpaOptInRecommendationOption[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setOptions($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Ads\GoogleAds\V3\Resources\Recommendation\TargetCpaOptInRecommendation\TargetCpaOptInRecommendationOption::class);
        $this->options = $arr;

        return $this;
    }

    /**
     * Output only. The recommended average CPA target. See required budget amount and impact
     * of using this recommendation in options list.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value recommended_target_cpa_micros = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\Int64Value
     */
    public function getRecommendedTargetCpaMicros()
    {
        return $this->recommended_target_cpa_micros;
    }

    /**
     * Returns the unboxed value from <code>getRecommendedTargetCpaMicros()</code>

     * Output only. The recommended average CPA target. See required budget amount and impact
     * of using this recommendation in options list.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value recommended_target_cpa_micros = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return int|string|null
     */
    public function getRecommendedTargetCpaMicrosUnwrapped()
    {
        return $this->readWrapperValue("recommended_target_cpa_micros");
    }

    /**
     * Output only. The recommended average CPA target. See required budget amount and impact
     * of using this recommendation in options list.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value recommended_target_cpa_micros = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param \Google\Protobuf\Int64Value $var
     * @return $this
     */
    public function setRecommendedTargetCpaMicros($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Int64Value::class);
        $this->recommended_target_cpa_micros = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\Int64Value object.

     * Output only. The recommended average CPA target. See required budget amount and impact
     * of using this recommendation in options list.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value recommended_target_cpa_micros = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param int|string|null $var
     * @return $this
     */
    public function setRecommendedTargetCpaMicrosUnwrapped($var)
    {
        $this->writeWrapperValue("recommended_target_cpa_micros", $var);
        return $this;}

}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(TargetCpaOptInRecommendation::class, \Google\Ads\GoogleAds\V3\Resources\Recommendation_TargetCpaOptInRecommendation::class);

