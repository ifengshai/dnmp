<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v6/services/ad_group_feed_service.proto

namespace Google\Ads\GoogleAds\V6\Services;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * The result for the ad group feed mutate.
 *
 * Generated from protobuf message <code>google.ads.googleads.v6.services.MutateAdGroupFeedResult</code>
 */
class MutateAdGroupFeedResult extends \Google\Protobuf\Internal\Message
{
    /**
     * Returned for successful operations.
     *
     * Generated from protobuf field <code>string resource_name = 1;</code>
     */
    protected $resource_name = '';
    /**
     * The mutated ad group feed with only mutable fields after mutate. The field
     * will only be returned when response_content_type is set to
     * "MUTABLE_RESOURCE".
     *
     * Generated from protobuf field <code>.google.ads.googleads.v6.resources.AdGroupFeed ad_group_feed = 2;</code>
     */
    protected $ad_group_feed = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $resource_name
     *           Returned for successful operations.
     *     @type \Google\Ads\GoogleAds\V6\Resources\AdGroupFeed $ad_group_feed
     *           The mutated ad group feed with only mutable fields after mutate. The field
     *           will only be returned when response_content_type is set to
     *           "MUTABLE_RESOURCE".
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V6\Services\AdGroupFeedService::initOnce();
        parent::__construct($data);
    }

    /**
     * Returned for successful operations.
     *
     * Generated from protobuf field <code>string resource_name = 1;</code>
     * @return string
     */
    public function getResourceName()
    {
        return $this->resource_name;
    }

    /**
     * Returned for successful operations.
     *
     * Generated from protobuf field <code>string resource_name = 1;</code>
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
     * The mutated ad group feed with only mutable fields after mutate. The field
     * will only be returned when response_content_type is set to
     * "MUTABLE_RESOURCE".
     *
     * Generated from protobuf field <code>.google.ads.googleads.v6.resources.AdGroupFeed ad_group_feed = 2;</code>
     * @return \Google\Ads\GoogleAds\V6\Resources\AdGroupFeed
     */
    public function getAdGroupFeed()
    {
        return isset($this->ad_group_feed) ? $this->ad_group_feed : null;
    }

    public function hasAdGroupFeed()
    {
        return isset($this->ad_group_feed);
    }

    public function clearAdGroupFeed()
    {
        unset($this->ad_group_feed);
    }

    /**
     * The mutated ad group feed with only mutable fields after mutate. The field
     * will only be returned when response_content_type is set to
     * "MUTABLE_RESOURCE".
     *
     * Generated from protobuf field <code>.google.ads.googleads.v6.resources.AdGroupFeed ad_group_feed = 2;</code>
     * @param \Google\Ads\GoogleAds\V6\Resources\AdGroupFeed $var
     * @return $this
     */
    public function setAdGroupFeed($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V6\Resources\AdGroupFeed::class);
        $this->ad_group_feed = $var;

        return $this;
    }

}

