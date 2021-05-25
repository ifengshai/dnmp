<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/services/ad_group_ad_service.proto

namespace Google\Ads\GoogleAds\V3\Services;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * A single operation (create, update, remove) on an ad group ad.
 *
 * Generated from protobuf message <code>google.ads.googleads.v3.services.AdGroupAdOperation</code>
 */
class AdGroupAdOperation extends \Google\Protobuf\Internal\Message
{
    /**
     * FieldMask that determines which resource fields are modified in an update.
     *
     * Generated from protobuf field <code>.google.protobuf.FieldMask update_mask = 4;</code>
     */
    protected $update_mask = null;
    /**
     * Configuration for how policies are validated.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v3.common.PolicyValidationParameter policy_validation_parameter = 5;</code>
     */
    protected $policy_validation_parameter = null;
    protected $operation;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Protobuf\FieldMask $update_mask
     *           FieldMask that determines which resource fields are modified in an update.
     *     @type \Google\Ads\GoogleAds\V3\Common\PolicyValidationParameter $policy_validation_parameter
     *           Configuration for how policies are validated.
     *     @type \Google\Ads\GoogleAds\V3\Resources\AdGroupAd $create
     *           Create operation: No resource name is expected for the new ad.
     *     @type \Google\Ads\GoogleAds\V3\Resources\AdGroupAd $update
     *           Update operation: The ad is expected to have a valid resource name.
     *     @type string $remove
     *           Remove operation: A resource name for the removed ad is expected,
     *           in this format:
     *           `customers/{customer_id}/adGroupAds/{ad_group_id}~{ad_id}`
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V3\Services\AdGroupAdService::initOnce();
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
     * Configuration for how policies are validated.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v3.common.PolicyValidationParameter policy_validation_parameter = 5;</code>
     * @return \Google\Ads\GoogleAds\V3\Common\PolicyValidationParameter
     */
    public function getPolicyValidationParameter()
    {
        return $this->policy_validation_parameter;
    }

    /**
     * Configuration for how policies are validated.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v3.common.PolicyValidationParameter policy_validation_parameter = 5;</code>
     * @param \Google\Ads\GoogleAds\V3\Common\PolicyValidationParameter $var
     * @return $this
     */
    public function setPolicyValidationParameter($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V3\Common\PolicyValidationParameter::class);
        $this->policy_validation_parameter = $var;

        return $this;
    }

    /**
     * Create operation: No resource name is expected for the new ad.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v3.resources.AdGroupAd create = 1;</code>
     * @return \Google\Ads\GoogleAds\V3\Resources\AdGroupAd
     */
    public function getCreate()
    {
        return $this->readOneof(1);
    }

    /**
     * Create operation: No resource name is expected for the new ad.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v3.resources.AdGroupAd create = 1;</code>
     * @param \Google\Ads\GoogleAds\V3\Resources\AdGroupAd $var
     * @return $this
     */
    public function setCreate($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V3\Resources\AdGroupAd::class);
        $this->writeOneof(1, $var);

        return $this;
    }

    /**
     * Update operation: The ad is expected to have a valid resource name.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v3.resources.AdGroupAd update = 2;</code>
     * @return \Google\Ads\GoogleAds\V3\Resources\AdGroupAd
     */
    public function getUpdate()
    {
        return $this->readOneof(2);
    }

    /**
     * Update operation: The ad is expected to have a valid resource name.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v3.resources.AdGroupAd update = 2;</code>
     * @param \Google\Ads\GoogleAds\V3\Resources\AdGroupAd $var
     * @return $this
     */
    public function setUpdate($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V3\Resources\AdGroupAd::class);
        $this->writeOneof(2, $var);

        return $this;
    }

    /**
     * Remove operation: A resource name for the removed ad is expected,
     * in this format:
     * `customers/{customer_id}/adGroupAds/{ad_group_id}~{ad_id}`
     *
     * Generated from protobuf field <code>string remove = 3;</code>
     * @return string
     */
    public function getRemove()
    {
        return $this->readOneof(3);
    }

    /**
     * Remove operation: A resource name for the removed ad is expected,
     * in this format:
     * `customers/{customer_id}/adGroupAds/{ad_group_id}~{ad_id}`
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

