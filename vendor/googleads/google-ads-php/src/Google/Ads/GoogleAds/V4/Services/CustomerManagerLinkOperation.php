<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/services/customer_manager_link_service.proto

namespace Google\Ads\GoogleAds\V4\Services;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Updates the status of a CustomerManagerLink.
 * The following actions are possible:
 * 1. Update operation with status ACTIVE accepts a pending invitation.
 * 2. Update operation with status REFUSED declines a pending invitation.
 * 3. Update operation with status INACTIVE terminates link to manager.
 *
 * Generated from protobuf message <code>google.ads.googleads.v4.services.CustomerManagerLinkOperation</code>
 */
class CustomerManagerLinkOperation extends \Google\Protobuf\Internal\Message
{
    /**
     * FieldMask that determines which resource fields are modified in an update.
     *
     * Generated from protobuf field <code>.google.protobuf.FieldMask update_mask = 4;</code>
     */
    protected $update_mask = null;
    protected $operation;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Protobuf\FieldMask $update_mask
     *           FieldMask that determines which resource fields are modified in an update.
     *     @type \Google\Ads\GoogleAds\V4\Resources\CustomerManagerLink $update
     *           Update operation: The link is expected to have a valid resource name.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V4\Services\CustomerManagerLinkService::initOnce();
        parent::__construct($data);
    }

    /**
     * FieldMask that determines which resource fields are modified in an update.
     *
     * Generated from protobuf field <code>.google.protobuf.FieldMask update_mask = 4;</code>
     * @return \Google\Protobuf\FieldMask
     */
    public function getUpdateMask()
    {
        return $this->update_mask;
    }

    /**
     * FieldMask that determines which resource fields are modified in an update.
     *
     * Generated from protobuf field <code>.google.protobuf.FieldMask update_mask = 4;</code>
     * @param \Google\Protobuf\FieldMask $var
     * @return $this
     */
    public function setUpdateMask($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\FieldMask::class);
        $this->update_mask = $var;

        return $this;
    }

    /**
     * Update operation: The link is expected to have a valid resource name.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.resources.CustomerManagerLink update = 2;</code>
     * @return \Google\Ads\GoogleAds\V4\Resources\CustomerManagerLink
     */
    public function getUpdate()
    {
        return $this->readOneof(2);
    }

    /**
     * Update operation: The link is expected to have a valid resource name.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.resources.CustomerManagerLink update = 2;</code>
     * @param \Google\Ads\GoogleAds\V4\Resources\CustomerManagerLink $var
     * @return $this
     */
    public function setUpdate($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V4\Resources\CustomerManagerLink::class);
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

