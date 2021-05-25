<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/resources/expanded_landing_page_view.proto

namespace Google\Ads\GoogleAds\V4\Resources;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * A landing page view with metrics aggregated at the expanded final URL
 * level.
 *
 * Generated from protobuf message <code>google.ads.googleads.v4.resources.ExpandedLandingPageView</code>
 */
class ExpandedLandingPageView extends \Google\Protobuf\Internal\Message
{
    /**
     * Output only. The resource name of the expanded landing page view.
     * Expanded landing page view resource names have the form:
     * `customers/{customer_id}/expandedLandingPageViews/{expanded_final_url_fingerprint}`
     *
     * Generated from protobuf field <code>string resource_name = 1 [(.google.api.field_behavior) = OUTPUT_ONLY, (.google.api.resource_reference) = {</code>
     */
    protected $resource_name = '';
    /**
     * Output only. The final URL that clicks are directed to.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue expanded_final_url = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $expanded_final_url = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $resource_name
     *           Output only. The resource name of the expanded landing page view.
     *           Expanded landing page view resource names have the form:
     *           `customers/{customer_id}/expandedLandingPageViews/{expanded_final_url_fingerprint}`
     *     @type \Google\Protobuf\StringValue $expanded_final_url
     *           Output only. The final URL that clicks are directed to.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V4\Resources\ExpandedLandingPageView::initOnce();
        parent::__construct($data);
    }

    /**
     * Output only. The resource name of the expanded landing page view.
     * Expanded landing page view resource names have the form:
     * `customers/{customer_id}/expandedLandingPageViews/{expanded_final_url_fingerprint}`
     *
     * Generated from protobuf field <code>string resource_name = 1 [(.google.api.field_behavior) = OUTPUT_ONLY, (.google.api.resource_reference) = {</code>
     * @return string
     */
    public function getResourceName()
    {
        return $this->resource_name;
    }

    /**
     * Output only. The resource name of the expanded landing page view.
     * Expanded landing page view resource names have the form:
     * `customers/{customer_id}/expandedLandingPageViews/{expanded_final_url_fingerprint}`
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

    /**
     * Output only. The final URL that clicks are directed to.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue expanded_final_url = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getExpandedFinalUrl()
    {
        return $this->expanded_final_url;
    }

    /**
     * Returns the unboxed value from <code>getExpandedFinalUrl()</code>

     * Output only. The final URL that clicks are directed to.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue expanded_final_url = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return string|null
     */
    public function getExpandedFinalUrlUnwrapped()
    {
        return $this->readWrapperValue("expanded_final_url");
    }

    /**
     * Output only. The final URL that clicks are directed to.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue expanded_final_url = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setExpandedFinalUrl($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->expanded_final_url = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * Output only. The final URL that clicks are directed to.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue expanded_final_url = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param string|null $var
     * @return $this
     */
    public function setExpandedFinalUrlUnwrapped($var)
    {
        $this->writeWrapperValue("expanded_final_url", $var);
        return $this;}

}

