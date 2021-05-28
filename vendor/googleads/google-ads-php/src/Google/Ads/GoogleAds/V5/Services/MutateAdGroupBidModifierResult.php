<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/services/ad_group_bid_modifier_service.proto

namespace Google\Ads\GoogleAds\V5\Services;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * The result for the criterion mutate.
 *
 * Generated from protobuf message <code>google.ads.googleads.v5.services.MutateAdGroupBidModifierResult</code>
 */
class MutateAdGroupBidModifierResult extends \Google\Protobuf\Internal\Message
{
    /**
     * Returned for successful operations.
     *
     * Generated from protobuf field <code>string resource_name = 1;</code>
     */
    protected $resource_name = '';
    /**
     * The mutated ad group bid modifier with only mutable fields after mutate.
     * The field will only be returned when response_content_type is set to
     * "MUTABLE_RESOURCE".
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.resources.AdGroupBidModifier ad_group_bid_modifier = 2;</code>
     */
    protected $ad_group_bid_modifier = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $resource_name
     *           Returned for successful operations.
     *     @type \Google\Ads\GoogleAds\V5\Resources\AdGroupBidModifier $ad_group_bid_modifier
     *           The mutated ad group bid modifier with only mutable fields after mutate.
     *           The field will only be returned when response_content_type is set to
     *           "MUTABLE_RESOURCE".
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V5\Services\AdGroupBidModifierService::initOnce();
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
     * The mutated ad group bid modifier with only mutable fields after mutate.
     * The field will only be returned when response_content_type is set to
     * "MUTABLE_RESOURCE".
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.resources.AdGroupBidModifier ad_group_bid_modifier = 2;</code>
     * @return \Google\Ads\GoogleAds\V5\Resources\AdGroupBidModifier
     */
    public function getAdGroupBidModifier()
    {
        return isset($this->ad_group_bid_modifier) ? $this->ad_group_bid_modifier : null;
    }

    public function hasAdGroupBidModifier()
    {
        return isset($this->ad_group_bid_modifier);
    }

    public function clearAdGroupBidModifier()
    {
        unset($this->ad_group_bid_modifier);
    }

    /**
     * The mutated ad group bid modifier with only mutable fields after mutate.
     * The field will only be returned when response_content_type is set to
     * "MUTABLE_RESOURCE".
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.resources.AdGroupBidModifier ad_group_bid_modifier = 2;</code>
     * @param \Google\Ads\GoogleAds\V5\Resources\AdGroupBidModifier $var
     * @return $this
     */
    public function setAdGroupBidModifier($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V5\Resources\AdGroupBidModifier::class);
        $this->ad_group_bid_modifier = $var;

        return $this;
    }

}

