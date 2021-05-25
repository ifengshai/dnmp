<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v6/services/feed_mapping_service.proto

namespace Google\Ads\GoogleAds\V6\Services;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * A single operation (create, remove) on a feed mapping.
 *
 * Generated from protobuf message <code>google.ads.googleads.v6.services.FeedMappingOperation</code>
 */
class FeedMappingOperation extends \Google\Protobuf\Internal\Message
{
    protected $operation;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Ads\GoogleAds\V6\Resources\FeedMapping $create
     *           Create operation: No resource name is expected for the new feed mapping.
     *     @type string $remove
     *           Remove operation: A resource name for the removed feed mapping is
     *           expected, in this format:
     *           `customers/{customer_id}/feedMappings/{feed_id}~{feed_mapping_id}`
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V6\Services\FeedMappingService::initOnce();
        parent::__construct($data);
    }

    /**
     * Create operation: No resource name is expected for the new feed mapping.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v6.resources.FeedMapping create = 1;</code>
     * @return \Google\Ads\GoogleAds\V6\Resources\FeedMapping
     */
    public function getCreate()
    {
        return $this->readOneof(1);
    }

    public function hasCreate()
    {
        return $this->hasOneof(1);
    }

    /**
     * Create operation: No resource name is expected for the new feed mapping.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v6.resources.FeedMapping create = 1;</code>
     * @param \Google\Ads\GoogleAds\V6\Resources\FeedMapping $var
     * @return $this
     */
    public function setCreate($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V6\Resources\FeedMapping::class);
        $this->writeOneof(1, $var);

        return $this;
    }

    /**
     * Remove operation: A resource name for the removed feed mapping is
     * expected, in this format:
     * `customers/{customer_id}/feedMappings/{feed_id}~{feed_mapping_id}`
     *
     * Generated from protobuf field <code>string remove = 3;</code>
     * @return string
     */
    public function getRemove()
    {
        return $this->readOneof(3);
    }

    public function hasRemove()
    {
        return $this->hasOneof(3);
    }

    /**
     * Remove operation: A resource name for the removed feed mapping is
     * expected, in this format:
     * `customers/{customer_id}/feedMappings/{feed_id}~{feed_mapping_id}`
     *
     * Generated from protobuf field <code>string remove = 3;</code>
     * @param string $var
     * @return $this
     */
    public function setRemove($var)
    {
        GPBUtil::checkString($var, True);
        $this->writeOneof(3, $var);

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

