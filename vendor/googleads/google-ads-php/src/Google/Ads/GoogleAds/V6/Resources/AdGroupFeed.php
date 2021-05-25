<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v6/resources/ad_group_feed.proto

namespace Google\Ads\GoogleAds\V6\Resources;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * An ad group feed.
 *
 * Generated from protobuf message <code>google.ads.googleads.v6.resources.AdGroupFeed</code>
 */
class AdGroupFeed extends \Google\Protobuf\Internal\Message
{
    /**
     * Immutable. The resource name of the ad group feed.
     * Ad group feed resource names have the form:
     * `customers/{customer_id}/adGroupFeeds/{ad_group_id}~{feed_id}
     *
     * Generated from protobuf field <code>string resource_name = 1 [(.google.api.field_behavior) = IMMUTABLE, (.google.api.resource_reference) = {</code>
     */
    protected $resource_name = '';
    /**
     * Immutable. The feed being linked to the ad group.
     *
     * Generated from protobuf field <code>string feed = 7 [(.google.api.field_behavior) = IMMUTABLE, (.google.api.resource_reference) = {</code>
     */
    protected $feed = null;
    /**
     * Immutable. The ad group being linked to the feed.
     *
     * Generated from protobuf field <code>string ad_group = 8 [(.google.api.field_behavior) = IMMUTABLE, (.google.api.resource_reference) = {</code>
     */
    protected $ad_group = null;
    /**
     * Indicates which placeholder types the feed may populate under the connected
     * ad group. Required.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v6.enums.PlaceholderTypeEnum.PlaceholderType placeholder_types = 4;</code>
     */
    private $placeholder_types;
    /**
     * Matching function associated with the AdGroupFeed.
     * The matching function is used to filter the set of feed items selected.
     * Required.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v6.common.MatchingFunction matching_function = 5;</code>
     */
    protected $matching_function = null;
    /**
     * Output only. Status of the ad group feed.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v6.enums.FeedLinkStatusEnum.FeedLinkStatus status = 6 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $status = 0;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $resource_name
     *           Immutable. The resource name of the ad group feed.
     *           Ad group feed resource names have the form:
     *           `customers/{customer_id}/adGroupFeeds/{ad_group_id}~{feed_id}
     *     @type string $feed
     *           Immutable. The feed being linked to the ad group.
     *     @type string $ad_group
     *           Immutable. The ad group being linked to the feed.
     *     @type int[]|\Google\Protobuf\Internal\RepeatedField $placeholder_types
     *           Indicates which placeholder types the feed may populate under the connected
     *           ad group. Required.
     *     @type \Google\Ads\GoogleAds\V6\Common\MatchingFunction $matching_function
     *           Matching function associated with the AdGroupFeed.
     *           The matching function is used to filter the set of feed items selected.
     *           Required.
     *     @type int $status
     *           Output only. Status of the ad group feed.
     *           This field is read-only.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V6\Resources\AdGroupFeed::initOnce();
        parent::__construct($data);
    }

    /**
     * Immutable. The resource name of the ad group feed.
     * Ad group feed resource names have the form:
     * `customers/{customer_id}/adGroupFeeds/{ad_group_id}~{feed_id}
     *
     * Generated from protobuf field <code>string resource_name = 1 [(.google.api.field_behavior) = IMMUTABLE, (.google.api.resource_reference) = {</code>
     * @return string
     */
    public function getResourceName()
    {
        return $this->resource_name;
    }

    /**
     * Immutable. The resource name of the ad group feed.
     * Ad group feed resource names have the form:
     * `customers/{customer_id}/adGroupFeeds/{ad_group_id}~{feed_id}
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
     * Immutable. The feed being linked to the ad group.
     *
     * Generated from protobuf field <code>string feed = 7 [(.google.api.field_behavior) = IMMUTABLE, (.google.api.resource_reference) = {</code>
     * @return string
     */
    public function getFeed()
    {
        return isset($this->feed) ? $this->feed : '';
    }

    public function hasFeed()
    {
        return isset($this->feed);
    }

    public function clearFeed()
    {
        unset($this->feed);
    }

    /**
     * Immutable. The feed being linked to the ad group.
     *
     * Generated from protobuf field <code>string feed = 7 [(.google.api.field_behavior) = IMMUTABLE, (.google.api.resource_reference) = {</code>
     * @param string $var
     * @return $this
     */
    public function setFeed($var)
    {
        GPBUtil::checkString($var, True);
        $this->feed = $var;

        return $this;
    }

    /**
     * Immutable. The ad group being linked to the feed.
     *
     * Generated from protobuf field <code>string ad_group = 8 [(.google.api.field_behavior) = IMMUTABLE, (.google.api.resource_reference) = {</code>
     * @return string
     */
    public function getAdGroup()
    {
        return isset($this->ad_group) ? $this->ad_group : '';
    }

    public function hasAdGroup()
    {
        return isset($this->ad_group);
    }

    public function clearAdGroup()
    {
        unset($this->ad_group);
    }

    /**
     * Immutable. The ad group being linked to the feed.
     *
     * Generated from protobuf field <code>string ad_group = 8 [(.google.api.field_behavior) = IMMUTABLE, (.google.api.resource_reference) = {</code>
     * @param string $var
     * @return $this
     */
    public function setAdGroup($var)
    {
        GPBUtil::checkString($var, True);
        $this->ad_group = $var;

        return $this;
    }

    /**
     * Indicates which placeholder types the feed may populate under the connected
     * ad group. Required.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v6.enums.PlaceholderTypeEnum.PlaceholderType placeholder_types = 4;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getPlaceholderTypes()
    {
        return $this->placeholder_types;
    }

    /**
     * Indicates which placeholder types the feed may populate under the connected
     * ad group. Required.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v6.enums.PlaceholderTypeEnum.PlaceholderType placeholder_types = 4;</code>
     * @param int[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setPlaceholderTypes($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::ENUM, \Google\Ads\GoogleAds\V6\Enums\PlaceholderTypeEnum\PlaceholderType::class);
        $this->placeholder_types = $arr;

        return $this;
    }

    /**
     * Matching function associated with the AdGroupFeed.
     * The matching function is used to filter the set of feed items selected.
     * Required.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v6.common.MatchingFunction matching_function = 5;</code>
     * @return \Google\Ads\GoogleAds\V6\Common\MatchingFunction
     */
    public function getMatchingFunction()
    {
        return isset($this->matching_function) ? $this->matching_function : null;
    }

    public function hasMatchingFunction()
    {
        return isset($this->matching_function);
    }

    public function clearMatchingFunction()
    {
        unset($this->matching_function);
    }

    /**
     * Matching function associated with the AdGroupFeed.
     * The matching function is used to filter the set of feed items selected.
     * Required.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v6.common.MatchingFunction matching_function = 5;</code>
     * @param \Google\Ads\GoogleAds\V6\Common\MatchingFunction $var
     * @return $this
     */
    public function setMatchingFunction($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V6\Common\MatchingFunction::class);
        $this->matching_function = $var;

        return $this;
    }

    /**
     * Output only. Status of the ad group feed.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v6.enums.FeedLinkStatusEnum.FeedLinkStatus status = 6 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Output only. Status of the ad group feed.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v6.enums.FeedLinkStatusEnum.FeedLinkStatus status = 6 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param int $var
     * @return $this
     */
    public function setStatus($var)
    {
        GPBUtil::checkEnum($var, \Google\Ads\GoogleAds\V6\Enums\FeedLinkStatusEnum\FeedLinkStatus::class);
        $this->status = $var;

        return $this;
    }

}

