<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/services/feed_item_target_service.proto

namespace Google\Ads\GoogleAds\V3\Services;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * A single operation (create, remove) on an feed item target.
 *
 * Generated from protobuf message <code>google.ads.googleads.v3.services.FeedItemTargetOperation</code>
 */
class FeedItemTargetOperation extends \Google\Protobuf\Internal\Message
{
    protected $operation;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Ads\GoogleAds\V3\Resources\FeedItemTarget $create
     *           Create operation: No resource name is expected for the new feed item
     *           target.
     *     @type string $remove
     *           Remove operation: A resource name for the removed feed item target is
     *           expected, in this format:
     *           `customers/{customer_id}/feedItemTargets/{feed_id}~{feed_item_id}~{feed_item_target_type}~{feed_item_target_id}`
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V3\Services\FeedItemTargetService::initOnce();
        parent::__construct($data);
    }

    /**
     * Create operation: No resource name is expected for the new feed item
     * target.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v3.resources.FeedItemTarget create = 1;</code>
     * @return \Google\Ads\GoogleAds\V3\Resources\FeedItemTarget
     */
    public function getCreate()
    {
        return $this->readOneof(1);
    }

    /**
     * Create operation: No resource name is expected for the new feed item
     * target.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v3.resources.FeedItemTarget create = 1;</code>
     * @param \Google\Ads\GoogleAds\V3\Resources\FeedItemTarget $var
     * @return $this
     */
    public function setCreate($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V3\Resources\FeedItemTarget::class);
        $this->writeOneof(1, $var);

        return $this;
    }

    /**
     * Remove operation: A resource name for the removed feed item target is
     * expected, in this format:
     * `customers/{customer_id}/feedItemTargets/{feed_id}~{feed_item_id}~{feed_item_target_type}~{feed_item_target_id}`
     *
     * Generated from protobuf field <code>string remove = 2;</code>
     * @return string
     */
    public function getRemove()
    {
        return $this->readOneof(2);
    }

    /**
     * Remove operation: A resource name for the removed feed item target is
     * expected, in this format:
     * `customers/{customer_id}/feedItemTargets/{feed_id}~{feed_item_id}~{feed_item_target_type}~{feed_item_target_id}`
     *
     * Generated from protobuf field <code>string remove = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setRemove($var)
    {
        GPBUtil::checkString($var, True);
        $this->writeOneof(2, $var);

        return $this;
    }

    /**
     * @return string
     */
    public function getOperation()
    {
        return $this->whichOneof("operation");
    }

}

