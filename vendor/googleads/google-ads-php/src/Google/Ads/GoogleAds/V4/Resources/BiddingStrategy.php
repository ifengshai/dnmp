<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/resources/bidding_strategy.proto

namespace Google\Ads\GoogleAds\V4\Resources;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * A bidding strategy.
 *
 * Generated from protobuf message <code>google.ads.googleads.v4.resources.BiddingStrategy</code>
 */
class BiddingStrategy extends \Google\Protobuf\Internal\Message
{
    /**
     * Immutable. The resource name of the bidding strategy.
     * Bidding strategy resource names have the form:
     * `customers/{customer_id}/biddingStrategies/{bidding_strategy_id}`
     *
     * Generated from protobuf field <code>string resource_name = 1 [(.google.api.field_behavior) = IMMUTABLE, (.google.api.resource_reference) = {</code>
     */
    protected $resource_name = '';
    /**
     * Output only. The ID of the bidding strategy.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value id = 3 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $id = null;
    /**
     * The name of the bidding strategy.
     * All bidding strategies within an account must be named distinctly.
     * The length of this string should be between 1 and 255, inclusive,
     * in UTF-8 bytes, (trimmed).
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue name = 4;</code>
     */
    protected $name = null;
    /**
     * Output only. The status of the bidding strategy.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.enums.BiddingStrategyStatusEnum.BiddingStrategyStatus status = 15 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $status = 0;
    /**
     * Output only. The type of the bidding strategy.
     * Create a bidding strategy by setting the bidding scheme.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.enums.BiddingStrategyTypeEnum.BiddingStrategyType type = 5 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $type = 0;
    /**
     * Output only. The number of campaigns attached to this bidding strategy.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value campaign_count = 13 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $campaign_count = null;
    /**
     * Output only. The number of non-removed campaigns attached to this bidding strategy.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value non_removed_campaign_count = 14 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $non_removed_campaign_count = null;
    protected $scheme;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $resource_name
     *           Immutable. The resource name of the bidding strategy.
     *           Bidding strategy resource names have the form:
     *           `customers/{customer_id}/biddingStrategies/{bidding_strategy_id}`
     *     @type \Google\Protobuf\Int64Value $id
     *           Output only. The ID of the bidding strategy.
     *     @type \Google\Protobuf\StringValue $name
     *           The name of the bidding strategy.
     *           All bidding strategies within an account must be named distinctly.
     *           The length of this string should be between 1 and 255, inclusive,
     *           in UTF-8 bytes, (trimmed).
     *     @type int $status
     *           Output only. The status of the bidding strategy.
     *           This field is read-only.
     *     @type int $type
     *           Output only. The type of the bidding strategy.
     *           Create a bidding strategy by setting the bidding scheme.
     *           This field is read-only.
     *     @type \Google\Protobuf\Int64Value $campaign_count
     *           Output only. The number of campaigns attached to this bidding strategy.
     *           This field is read-only.
     *     @type \Google\Protobuf\Int64Value $non_removed_campaign_count
     *           Output only. The number of non-removed campaigns attached to this bidding strategy.
     *           This field is read-only.
     *     @type \Google\Ads\GoogleAds\V4\Common\EnhancedCpc $enhanced_cpc
     *           A bidding strategy that raises bids for clicks that seem more likely to
     *           lead to a conversion and lowers them for clicks where they seem less
     *           likely.
     *     @type \Google\Ads\GoogleAds\V4\Common\TargetCpa $target_cpa
     *           A bidding strategy that sets bids to help get as many conversions as
     *           possible at the target cost-per-acquisition (CPA) you set.
     *     @type \Google\Ads\GoogleAds\V4\Common\TargetImpressionShare $target_impression_share
     *           A bidding strategy that automatically optimizes towards a desired
     *           percentage of impressions.
     *     @type \Google\Ads\GoogleAds\V4\Common\TargetRoas $target_roas
     *           A bidding strategy that helps you maximize revenue while averaging a
     *           specific target Return On Ad Spend (ROAS).
     *     @type \Google\Ads\GoogleAds\V4\Common\TargetSpend $target_spend
     *           A bid strategy that sets your bids to help get as many clicks as
     *           possible within your budget.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V4\Resources\BiddingStrategy::initOnce();
        parent::__construct($data);
    }

    /**
     * Immutable. The resource name of the bidding strategy.
     * Bidding strategy resource names have the form:
     * `customers/{customer_id}/biddingStrategies/{bidding_strategy_id}`
     *
     * Generated from protobuf field <code>string resource_name = 1 [(.google.api.field_behavior) = IMMUTABLE, (.google.api.resource_reference) = {</code>
     * @return string
     */
    public function getResourceName()
    {
        return $this->resource_name;
    }

    /**
     * Immutable. The resource name of the bidding strategy.
     * Bidding strategy resource names have the form:
     * `customers/{customer_id}/biddingStrategies/{bidding_strategy_id}`
     *
     * Generated from protobuf field <code>string resource_name = 1 [(.google.api.field_behavior) = IMMUTABLE, (.google.api.resource_reference) = {</code>
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
     * Output only. The ID of the bidding strategy.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value id = 3 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\Int64Value
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the unboxed value from <code>getId()</code>

     * Output only. The ID of the bidding strategy.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value id = 3 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return int|string|null
     */
    public function getIdUnwrapped()
    {
        return $this->readWrapperValue("id");
    }

    /**
     * Output only. The ID of the bidding strategy.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value id = 3 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
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

     * Output only. The ID of the bidding strategy.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value id = 3 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param int|string|null $var
     * @return $this
     */
    public function setIdUnwrapped($var)
    {
        $this->writeWrapperValue("id", $var);
        return $this;}

    /**
     * The name of the bidding strategy.
     * All bidding strategies within an account must be named distinctly.
     * The length of this string should be between 1 and 255, inclusive,
     * in UTF-8 bytes, (trimmed).
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue name = 4;</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the unboxed value from <code>getName()</code>

     * The name of the bidding strategy.
     * All bidding strategies within an account must be named distinctly.
     * The length of this string should be between 1 and 255, inclusive,
     * in UTF-8 bytes, (trimmed).
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue name = 4;</code>
     * @return string|null
     */
    public function getNameUnwrapped()
    {
        return $this->readWrapperValue("name");
    }

    /**
     * The name of the bidding strategy.
     * All bidding strategies within an account must be named distinctly.
     * The length of this string should be between 1 and 255, inclusive,
     * in UTF-8 bytes, (trimmed).
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue name = 4;</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setName($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->name = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * The name of the bidding strategy.
     * All bidding strategies within an account must be named distinctly.
     * The length of this string should be between 1 and 255, inclusive,
     * in UTF-8 bytes, (trimmed).
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue name = 4;</code>
     * @param string|null $var
     * @return $this
     */
    public function setNameUnwrapped($var)
    {
        $this->writeWrapperValue("name", $var);
        return $this;}

    /**
     * Output only. The status of the bidding strategy.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.enums.BiddingStrategyStatusEnum.BiddingStrategyStatus status = 15 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Output only. The status of the bidding strategy.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.enums.BiddingStrategyStatusEnum.BiddingStrategyStatus status = 15 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param int $var
     * @return $this
     */
    public function setStatus($var)
    {
        GPBUtil::checkEnum($var, \Google\Ads\GoogleAds\V4\Enums\BiddingStrategyStatusEnum_BiddingStrategyStatus::class);
        $this->status = $var;

        return $this;
    }

    /**
     * Output only. The type of the bidding strategy.
     * Create a bidding strategy by setting the bidding scheme.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.enums.BiddingStrategyTypeEnum.BiddingStrategyType type = 5 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Output only. The type of the bidding strategy.
     * Create a bidding strategy by setting the bidding scheme.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.enums.BiddingStrategyTypeEnum.BiddingStrategyType type = 5 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param int $var
     * @return $this
     */
    public function setType($var)
    {
        GPBUtil::checkEnum($var, \Google\Ads\GoogleAds\V4\Enums\BiddingStrategyTypeEnum_BiddingStrategyType::class);
        $this->type = $var;

        return $this;
    }

    /**
     * Output only. The number of campaigns attached to this bidding strategy.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value campaign_count = 13 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\Int64Value
     */
    public function getCampaignCount()
    {
        return $this->campaign_count;
    }

    /**
     * Returns the unboxed value from <code>getCampaignCount()</code>

     * Output only. The number of campaigns attached to this bidding strategy.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value campaign_count = 13 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return int|string|null
     */
    public function getCampaignCountUnwrapped()
    {
        return $this->readWrapperValue("campaign_count");
    }

    /**
     * Output only. The number of campaigns attached to this bidding strategy.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value campaign_count = 13 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param \Google\Protobuf\Int64Value $var
     * @return $this
     */
    public function setCampaignCount($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Int64Value::class);
        $this->campaign_count = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\Int64Value object.

     * Output only. The number of campaigns attached to this bidding strategy.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value campaign_count = 13 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param int|string|null $var
     * @return $this
     */
    public function setCampaignCountUnwrapped($var)
    {
        $this->writeWrapperValue("campaign_count", $var);
        return $this;}

    /**
     * Output only. The number of non-removed campaigns attached to this bidding strategy.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value non_removed_campaign_count = 14 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\Int64Value
     */
    public function getNonRemovedCampaignCount()
    {
        return $this->non_removed_campaign_count;
    }

    /**
     * Returns the unboxed value from <code>getNonRemovedCampaignCount()</code>

     * Output only. The number of non-removed campaigns attached to this bidding strategy.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value non_removed_campaign_count = 14 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return int|string|null
     */
    public function getNonRemovedCampaignCountUnwrapped()
    {
        return $this->readWrapperValue("non_removed_campaign_count");
    }

    /**
     * Output only. The number of non-removed campaigns attached to this bidding strategy.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value non_removed_campaign_count = 14 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param \Google\Protobuf\Int64Value $var
     * @return $this
     */
    public function setNonRemovedCampaignCount($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Int64Value::class);
        $this->non_removed_campaign_count = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\Int64Value object.

     * Output only. The number of non-removed campaigns attached to this bidding strategy.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value non_removed_campaign_count = 14 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param int|string|null $var
     * @return $this
     */
    public function setNonRemovedCampaignCountUnwrapped($var)
    {
        $this->writeWrapperValue("non_removed_campaign_count", $var);
        return $this;}

    /**
     * A bidding strategy that raises bids for clicks that seem more likely to
     * lead to a conversion and lowers them for clicks where they seem less
     * likely.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.common.EnhancedCpc enhanced_cpc = 7;</code>
     * @return \Google\Ads\GoogleAds\V4\Common\EnhancedCpc
     */
    public function getEnhancedCpc()
    {
        return $this->readOneof(7);
    }

    /**
     * A bidding strategy that raises bids for clicks that seem more likely to
     * lead to a conversion and lowers them for clicks where they seem less
     * likely.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.common.EnhancedCpc enhanced_cpc = 7;</code>
     * @param \Google\Ads\GoogleAds\V4\Common\EnhancedCpc $var
     * @return $this
     */
    public function setEnhancedCpc($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V4\Common\EnhancedCpc::class);
        $this->writeOneof(7, $var);

        return $this;
    }

    /**
     * A bidding strategy that sets bids to help get as many conversions as
     * possible at the target cost-per-acquisition (CPA) you set.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.common.TargetCpa target_cpa = 9;</code>
     * @return \Google\Ads\GoogleAds\V4\Common\TargetCpa
     */
    public function getTargetCpa()
    {
        return $this->readOneof(9);
    }

    /**
     * A bidding strategy that sets bids to help get as many conversions as
     * possible at the target cost-per-acquisition (CPA) you set.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.common.TargetCpa target_cpa = 9;</code>
     * @param \Google\Ads\GoogleAds\V4\Common\TargetCpa $var
     * @return $this
     */
    public function setTargetCpa($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V4\Common\TargetCpa::class);
        $this->writeOneof(9, $var);

        return $this;
    }

    /**
     * A bidding strategy that automatically optimizes towards a desired
     * percentage of impressions.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.common.TargetImpressionShare target_impression_share = 48;</code>
     * @return \Google\Ads\GoogleAds\V4\Common\TargetImpressionShare
     */
    public function getTargetImpressionShare()
    {
        return $this->readOneof(48);
    }

    /**
     * A bidding strategy that automatically optimizes towards a desired
     * percentage of impressions.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.common.TargetImpressionShare target_impression_share = 48;</code>
     * @param \Google\Ads\GoogleAds\V4\Common\TargetImpressionShare $var
     * @return $this
     */
    public function setTargetImpressionShare($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V4\Common\TargetImpressionShare::class);
        $this->writeOneof(48, $var);

        return $this;
    }

    /**
     * A bidding strategy that helps you maximize revenue while averaging a
     * specific target Return On Ad Spend (ROAS).
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.common.TargetRoas target_roas = 11;</code>
     * @return \Google\Ads\GoogleAds\V4\Common\TargetRoas
     */
    public function getTargetRoas()
    {
        return $this->readOneof(11);
    }

    /**
     * A bidding strategy that helps you maximize revenue while averaging a
     * specific target Return On Ad Spend (ROAS).
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.common.TargetRoas target_roas = 11;</code>
     * @param \Google\Ads\GoogleAds\V4\Common\TargetRoas $var
     * @return $this
     */
    public function setTargetRoas($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V4\Common\TargetRoas::class);
        $this->writeOneof(11, $var);

        return $this;
    }

    /**
     * A bid strategy that sets your bids to help get as many clicks as
     * possible within your budget.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.common.TargetSpend target_spend = 12;</code>
     * @return \Google\Ads\GoogleAds\V4\Common\TargetSpend
     */
    public function getTargetSpend()
    {
        return $this->readOneof(12);
    }

    /**
     * A bid strategy that sets your bids to help get as many clicks as
     * possible within your budget.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.common.TargetSpend target_spend = 12;</code>
     * @param \Google\Ads\GoogleAds\V4\Common\TargetSpend $var
     * @return $this
     */
    public function setTargetSpend($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V4\Common\TargetSpend::class);
        $this->writeOneof(12, $var);

        return $this;
    }

    /**
     * @return string
     */
    public function getScheme()
    {
        return $this->whichOneof("scheme");
    }

}

