<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/resources/campaign.proto

namespace Google\Ads\GoogleAds\V4\Resources\Campaign;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Campaign-level settings for tracking information.
 *
 * Generated from protobuf message <code>google.ads.googleads.v4.resources.Campaign.TrackingSetting</code>
 */
class TrackingSetting extends \Google\Protobuf\Internal\Message
{
    /**
     * Output only. The url used for dynamic tracking.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue tracking_url = 1 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $tracking_url = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Protobuf\StringValue $tracking_url
     *           Output only. The url used for dynamic tracking.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V4\Resources\Campaign::initOnce();
        parent::__construct($data);
    }

    /**
     * Output only. The url used for dynamic tracking.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue tracking_url = 1 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getTrackingUrl()
    {
        return $this->tracking_url;
    }

    /**
     * Returns the unboxed value from <code>getTrackingUrl()</code>

     * Output only. The url used for dynamic tracking.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue tracking_url = 1 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return string|null
     */
    public function getTrackingUrlUnwrapped()
    {
        return $this->readWrapperValue("tracking_url");
    }

    /**
     * Output only. The url used for dynamic tracking.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue tracking_url = 1 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setTrackingUrl($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->tracking_url = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * Output only. The url used for dynamic tracking.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue tracking_url = 1 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param string|null $var
     * @return $this
     */
    public function setTrackingUrlUnwrapped($var)
    {
        $this->writeWrapperValue("tracking_url", $var);
        return $this;}

}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(TrackingSetting::class, \Google\Ads\GoogleAds\V4\Resources\Campaign_TrackingSetting::class);

