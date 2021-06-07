<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v6/services/campaign_label_service.proto

namespace Google\Ads\GoogleAds\V6\Services;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * A single operation (create, remove) on a campaign-label relationship.
 *
 * Generated from protobuf message <code>google.ads.googleads.v6.services.CampaignLabelOperation</code>
 */
class CampaignLabelOperation extends \Google\Protobuf\Internal\Message
{
    protected $operation;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Ads\GoogleAds\V6\Resources\CampaignLabel $create
     *           Create operation: No resource name is expected for the new campaign-label
     *           relationship.
     *     @type string $remove
     *           Remove operation: A resource name for the campaign-label relationship
     *           being removed, in this format:
     *           `customers/{customer_id}/campaignLabels/{campaign_id}~{label_id}`
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V6\Services\CampaignLabelService::initOnce();
        parent::__construct($data);
    }

    /**
     * Create operation: No resource name is expected for the new campaign-label
     * relationship.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v6.resources.CampaignLabel create = 1;</code>
     * @return \Google\Ads\GoogleAds\V6\Resources\CampaignLabel
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
     * Create operation: No resource name is expected for the new campaign-label
     * relationship.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v6.resources.CampaignLabel create = 1;</code>
     * @param \Google\Ads\GoogleAds\V6\Resources\CampaignLabel $var
     * @return $this
     */
    public function setCreate($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V6\Resources\CampaignLabel::class);
        $this->writeOneof(1, $var);

        return $this;
    }

    /**
     * Remove operation: A resource name for the campaign-label relationship
     * being removed, in this format:
     * `customers/{customer_id}/campaignLabels/{campaign_id}~{label_id}`
     *
     * Generated from protobuf field <code>string remove = 2;</code>
     * @return string
     */
    public function getRemove()
    {
        return $this->readOneof(2);
    }

    public function hasRemove()
    {
        return $this->hasOneof(2);
    }

    /**
     * Remove operation: A resource name for the campaign-label relationship
     * being removed, in this format:
     * `customers/{customer_id}/campaignLabels/{campaign_id}~{label_id}`
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

