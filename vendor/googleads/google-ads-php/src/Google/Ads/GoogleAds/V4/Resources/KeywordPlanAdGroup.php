<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/resources/keyword_plan_ad_group.proto

namespace Google\Ads\GoogleAds\V4\Resources;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * A Keyword Planner ad group.
 * Max number of keyword plan ad groups per plan: 200.
 *
 * Generated from protobuf message <code>google.ads.googleads.v4.resources.KeywordPlanAdGroup</code>
 */
class KeywordPlanAdGroup extends \Google\Protobuf\Internal\Message
{
    /**
     * Immutable. The resource name of the Keyword Planner ad group.
     * KeywordPlanAdGroup resource names have the form:
     * `customers/{customer_id}/keywordPlanAdGroups/{kp_ad_group_id}`
     *
     * Generated from protobuf field <code>string resource_name = 1 [(.google.api.field_behavior) = IMMUTABLE, (.google.api.resource_reference) = {</code>
     */
    protected $resource_name = '';
    /**
     * The keyword plan campaign to which this ad group belongs.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue keyword_plan_campaign = 2 [(.google.api.resource_reference) = {</code>
     */
    protected $keyword_plan_campaign = null;
    /**
     * Output only. The ID of the keyword plan ad group.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value id = 3 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $id = null;
    /**
     * The name of the keyword plan ad group.
     * This field is required and should not be empty when creating keyword plan
     * ad group.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue name = 4;</code>
     */
    protected $name = null;
    /**
     * A default ad group max cpc bid in micros in account currency for all
     * biddable keywords under the keyword plan ad group.
     * If not set, will inherit from parent campaign.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value cpc_bid_micros = 5;</code>
     */
    protected $cpc_bid_micros = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $resource_name
     *           Immutable. The resource name of the Keyword Planner ad group.
     *           KeywordPlanAdGroup resource names have the form:
     *           `customers/{customer_id}/keywordPlanAdGroups/{kp_ad_group_id}`
     *     @type \Google\Protobuf\StringValue $keyword_plan_campaign
     *           The keyword plan campaign to which this ad group belongs.
     *     @type \Google\Protobuf\Int64Value $id
     *           Output only. The ID of the keyword plan ad group.
     *     @type \Google\Protobuf\StringValue $name
     *           The name of the keyword plan ad group.
     *           This field is required and should not be empty when creating keyword plan
     *           ad group.
     *     @type \Google\Protobuf\Int64Value $cpc_bid_micros
     *           A default ad group max cpc bid in micros in account currency for all
     *           biddable keywords under the keyword plan ad group.
     *           If not set, will inherit from parent campaign.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V4\Resources\KeywordPlanAdGroup::initOnce();
        parent::__construct($data);
    }

    /**
     * Immutable. The resource name of the Keyword Planner ad group.
     * KeywordPlanAdGroup resource names have the form:
     * `customers/{customer_id}/keywordPlanAdGroups/{kp_ad_group_id}`
     *
     * Generated from protobuf field <code>string resource_name = 1 [(.google.api.field_behavior) = IMMUTABLE, (.google.api.resource_reference) = {</code>
     * @return string
     */
    public function getResourceName()
    {
        return $this->resource_name;
    }

    /**
     * Immutable. The resource name of the Keyword Planner ad group.
     * KeywordPlanAdGroup resource names have the form:
     * `customers/{customer_id}/keywordPlanAdGroups/{kp_ad_group_id}`
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
     * The keyword plan campaign to which this ad group belongs.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue keyword_plan_campaign = 2 [(.google.api.resource_reference) = {</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getKeywordPlanCampaign()
    {
        return $this->keyword_plan_campaign;
    }

    /**
     * Returns the unboxed value from <code>getKeywordPlanCampaign()</code>

     * The keyword plan campaign to which this ad group belongs.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue keyword_plan_campaign = 2 [(.google.api.resource_reference) = {</code>
     * @return string|null
     */
    public function getKeywordPlanCampaignUnwrapped()
    {
        return $this->readWrapperValue("keyword_plan_campaign");
    }

    /**
     * The keyword plan campaign to which this ad group belongs.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue keyword_plan_campaign = 2 [(.google.api.resource_reference) = {</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setKeywordPlanCampaign($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->keyword_plan_campaign = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * The keyword plan campaign to which this ad group belongs.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue keyword_plan_campaign = 2 [(.google.api.resource_reference) = {</code>
     * @param string|null $var
     * @return $this
     */
    public function setKeywordPlanCampaignUnwrapped($var)
    {
        $this->writeWrapperValue("keyword_plan_campaign", $var);
        return $this;}

    /**
     * Output only. The ID of the keyword plan ad group.
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

     * Output only. The ID of the keyword plan ad group.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value id = 3 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return int|string|null
     */
    public function getIdUnwrapped()
    {
        return $this->readWrapperValue("id");
    }

    /**
     * Output only. The ID of the keyword plan ad group.
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

     * Output only. The ID of the keyword plan ad group.
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
     * The name of the keyword plan ad group.
     * This field is required and should not be empty when creating keyword plan
     * ad group.
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

     * The name of the keyword plan ad group.
     * This field is required and should not be empty when creating keyword plan
     * ad group.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue name = 4;</code>
     * @return string|null
     */
    public function getNameUnwrapped()
    {
        return $this->readWrapperValue("name");
    }

    /**
     * The name of the keyword plan ad group.
     * This field is required and should not be empty when creating keyword plan
     * ad group.
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

     * The name of the keyword plan ad group.
     * This field is required and should not be empty when creating keyword plan
     * ad group.
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
     * A default ad group max cpc bid in micros in account currency for all
     * biddable keywords under the keyword plan ad group.
     * If not set, will inherit from parent campaign.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value cpc_bid_micros = 5;</code>
     * @return \Google\Protobuf\Int64Value
     */
    public function getCpcBidMicros()
    {
        return $this->cpc_bid_micros;
    }

    /**
     * Returns the unboxed value from <code>getCpcBidMicros()</code>

     * A default ad group max cpc bid in micros in account currency for all
     * biddable keywords under the keyword plan ad group.
     * If not set, will inherit from parent campaign.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value cpc_bid_micros = 5;</code>
     * @return int|string|null
     */
    public function getCpcBidMicrosUnwrapped()
    {
        return $this->readWrapperValue("cpc_bid_micros");
    }

    /**
     * A default ad group max cpc bid in micros in account currency for all
     * biddable keywords under the keyword plan ad group.
     * If not set, will inherit from parent campaign.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value cpc_bid_micros = 5;</code>
     * @param \Google\Protobuf\Int64Value $var
     * @return $this
     */
    public function setCpcBidMicros($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Int64Value::class);
        $this->cpc_bid_micros = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\Int64Value object.

     * A default ad group max cpc bid in micros in account currency for all
     * biddable keywords under the keyword plan ad group.
     * If not set, will inherit from parent campaign.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value cpc_bid_micros = 5;</code>
     * @param int|string|null $var
     * @return $this
     */
    public function setCpcBidMicrosUnwrapped($var)
    {
        $this->writeWrapperValue("cpc_bid_micros", $var);
        return $this;}

}

