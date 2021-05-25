<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/resources/ad_group_ad_label.proto

namespace Google\Ads\GoogleAds\V3\Resources;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * A relationship between an ad group ad and a label.
 *
 * Generated from protobuf message <code>google.ads.googleads.v3.resources.AdGroupAdLabel</code>
 */
class AdGroupAdLabel extends \Google\Protobuf\Internal\Message
{
    /**
     * Immutable. The resource name of the ad group ad label.
     * Ad group ad label resource names have the form:
     * `customers/{customer_id}/adGroupAdLabels/{ad_group_id}~{ad_id}~{label_id}`
     *
     * Generated from protobuf field <code>string resource_name = 1 [(.google.api.field_behavior) = IMMUTABLE, (.google.api.resource_reference) = {</code>
     */
    protected $resource_name = '';
    /**
     * Immutable. The ad group ad to which the label is attached.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue ad_group_ad = 2 [(.google.api.field_behavior) = IMMUTABLE, (.google.api.resource_reference) = {</code>
     */
    protected $ad_group_ad = null;
    /**
     * Immutable. The label assigned to the ad group ad.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue label = 3 [(.google.api.field_behavior) = IMMUTABLE, (.google.api.resource_reference) = {</code>
     */
    protected $label = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $resource_name
     *           Immutable. The resource name of the ad group ad label.
     *           Ad group ad label resource names have the form:
     *           `customers/{customer_id}/adGroupAdLabels/{ad_group_id}~{ad_id}~{label_id}`
     *     @type \Google\Protobuf\StringValue $ad_group_ad
     *           Immutable. The ad group ad to which the label is attached.
     *     @type \Google\Protobuf\StringValue $label
     *           Immutable. The label assigned to the ad group ad.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V3\Resources\AdGroupAdLabel::initOnce();
        parent::__construct($data);
    }

    /**
     * Immutable. The resource name of the ad group ad label.
     * Ad group ad label resource names have the form:
     * `customers/{customer_id}/adGroupAdLabels/{ad_group_id}~{ad_id}~{label_id}`
     *
     * Generated from protobuf field <code>string resource_name = 1 [(.google.api.field_behavior) = IMMUTABLE, (.google.api.resource_reference) = {</code>
     * @return string
     */
    public function getResourceName()
    {
        return $this->resource_name;
    }

    /**
     * Immutable. The resource name of the ad group ad label.
     * Ad group ad label resource names have the form:
     * `customers/{customer_id}/adGroupAdLabels/{ad_group_id}~{ad_id}~{label_id}`
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
     * Immutable. The ad group ad to which the label is attached.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue ad_group_ad = 2 [(.google.api.field_behavior) = IMMUTABLE, (.google.api.resource_reference) = {</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getAdGroupAd()
    {
        return $this->ad_group_ad;
    }

    /**
     * Returns the unboxed value from <code>getAdGroupAd()</code>

     * Immutable. The ad group ad to which the label is attached.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue ad_group_ad = 2 [(.google.api.field_behavior) = IMMUTABLE, (.google.api.resource_reference) = {</code>
     * @return string|null
     */
    public function getAdGroupAdUnwrapped()
    {
        return $this->readWrapperValue("ad_group_ad");
    }

    /**
     * Immutable. The ad group ad to which the label is attached.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue ad_group_ad = 2 [(.google.api.field_behavior) = IMMUTABLE, (.google.api.resource_reference) = {</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setAdGroupAd($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->ad_group_ad = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * Immutable. The ad group ad to which the label is attached.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue ad_group_ad = 2 [(.google.api.field_behavior) = IMMUTABLE, (.google.api.resource_reference) = {</code>
     * @param string|null $var
     * @return $this
     */
    public function setAdGroupAdUnwrapped($var)
    {
        $this->writeWrapperValue("ad_group_ad", $var);
        return $this;}

    /**
     * Immutable. The label assigned to the ad group ad.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue label = 3 [(.google.api.field_behavior) = IMMUTABLE, (.google.api.resource_reference) = {</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Returns the unboxed value from <code>getLabel()</code>

     * Immutable. The label assigned to the ad group ad.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue label = 3 [(.google.api.field_behavior) = IMMUTABLE, (.google.api.resource_reference) = {</code>
     * @return string|null
     */
    public function getLabelUnwrapped()
    {
        return $this->readWrapperValue("label");
    }

    /**
     * Immutable. The label assigned to the ad group ad.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue label = 3 [(.google.api.field_behavior) = IMMUTABLE, (.google.api.resource_reference) = {</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setLabel($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->label = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * Immutable. The label assigned to the ad group ad.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue label = 3 [(.google.api.field_behavior) = IMMUTABLE, (.google.api.resource_reference) = {</code>
     * @param string|null $var
     * @return $this
     */
    public function setLabelUnwrapped($var)
    {
        $this->writeWrapperValue("label", $var);
        return $this;}

}

