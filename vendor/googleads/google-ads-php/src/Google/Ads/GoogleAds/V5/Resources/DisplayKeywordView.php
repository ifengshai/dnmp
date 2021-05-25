<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/resources/display_keyword_view.proto

namespace Google\Ads\GoogleAds\V5\Resources;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * A display keyword view.
 *
 * Generated from protobuf message <code>google.ads.googleads.v5.resources.DisplayKeywordView</code>
 */
class DisplayKeywordView extends \Google\Protobuf\Internal\Message
{
    /**
     * Output only. The resource name of the display keyword view.
     * Display Keyword view resource names have the form:
     * `customers/{customer_id}/displayKeywordViews/{ad_group_id}~{criterion_id}`
     *
     * Generated from protobuf field <code>string resource_name = 1 [(.google.api.field_behavior) = OUTPUT_ONLY, (.google.api.resource_reference) = {</code>
     */
    protected $resource_name = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $resource_name
     *           Output only. The resource name of the display keyword view.
     *           Display Keyword view resource names have the form:
     *           `customers/{customer_id}/displayKeywordViews/{ad_group_id}~{criterion_id}`
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V5\Resources\DisplayKeywordView::initOnce();
        parent::__construct($data);
    }

    /**
     * Output only. The resource name of the display keyword view.
     * Display Keyword view resource names have the form:
     * `customers/{customer_id}/displayKeywordViews/{ad_group_id}~{criterion_id}`
     *
     * Generated from protobuf field <code>string resource_name = 1 [(.google.api.field_behavior) = OUTPUT_ONLY, (.google.api.resource_reference) = {</code>
     * @return string
     */
    public function getResourceName()
    {
        return $this->resource_name;
    }

    /**
     * Output only. The resource name of the display keyword view.
     * Display Keyword view resource names have the form:
     * `customers/{customer_id}/displayKeywordViews/{ad_group_id}~{criterion_id}`
     *
     * Generated from protobuf field <code>string resource_name = 1 [(.google.api.field_behavior) = OUTPUT_ONLY, (.google.api.resource_reference) = {</code>
     * @param string $var
     * @return $this
     */
    public function setResourceName($var)
    {
        GPBUtil::checkString($var, True);
        $this->resource_name = $var;

        return $this;
    }

}

