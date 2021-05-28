<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/resources/recommendation.proto

namespace Google\Ads\GoogleAds\V4\Resources\Recommendation;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * The Sitelink extension recommendation.
 *
 * Generated from protobuf message <code>google.ads.googleads.v4.resources.Recommendation.SitelinkExtensionRecommendation</code>
 */
class SitelinkExtensionRecommendation extends \Google\Protobuf\Internal\Message
{
    /**
     * Output only. Sitelink extensions recommended to be added.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v4.common.SitelinkFeedItem recommended_extensions = 1 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    private $recommended_extensions;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Ads\GoogleAds\V4\Common\SitelinkFeedItem[]|\Google\Protobuf\Internal\RepeatedField $recommended_extensions
     *           Output only. Sitelink extensions recommended to be added.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V4\Resources\Recommendation::initOnce();
        parent::__construct($data);
    }

    /**
     * Output only. Sitelink extensions recommended to be added.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v4.common.SitelinkFeedItem recommended_extensions = 1 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getRecommendedExtensions()
    {
        return $this->recommended_extensions;
    }

    /**
     * Output only. Sitelink extensions recommended to be added.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v4.common.SitelinkFeedItem recommended_extensions = 1 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param \Google\Ads\GoogleAds\V4\Common\SitelinkFeedItem[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setRecommendedExtensions($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Ads\GoogleAds\V4\Common\SitelinkFeedItem::class);
        $this->recommended_extensions = $arr;

        return $this;
    }

}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(SitelinkExtensionRecommendation::class, \Google\Ads\GoogleAds\V4\Resources\Recommendation_SitelinkExtensionRecommendation::class);

