<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v6/services/campaign_label_service.proto

namespace Google\Ads\GoogleAds\V6\Services;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Request message for [CampaignLabelService.MutateCampaignLabels][google.ads.googleads.v6.services.CampaignLabelService.MutateCampaignLabels].
 *
 * Generated from protobuf message <code>google.ads.googleads.v6.services.MutateCampaignLabelsRequest</code>
 */
class MutateCampaignLabelsRequest extends \Google\Protobuf\Internal\Message
{
    /**
     * Required. ID of the customer whose campaign-label relationships are being modified.
     *
     * Generated from protobuf field <code>string customer_id = 1 [(.google.api.field_behavior) = REQUIRED];</code>
     */
    protected $customer_id = '';
    /**
     * Required. The list of operations to perform on campaign-label relationships.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v6.services.CampaignLabelOperation operations = 2 [(.google.api.field_behavior) = REQUIRED];</code>
     */
    private $operations;
    /**
     * If true, successful operations will be carried out and invalid
     * operations will return errors. If false, all operations will be carried
     * out in one transaction if and only if they are all valid.
     * Default is false.
     *
     * Generated from protobuf field <code>bool partial_failure = 3;</code>
     */
    protected $partial_failure = false;
    /**
     * If true, the request is validated but not executed. Only errors are
     * returned, not results.
     *
     * Generated from protobuf field <code>bool validate_only = 4;</code>
     */
    protected $validate_only = false;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $customer_id
     *           Required. ID of the customer whose campaign-label relationships are being modified.
     *     @type \Google\Ads\GoogleAds\V6\Services\CampaignLabelOperation[]|\Google\Protobuf\Internal\RepeatedField $operations
     *           Required. The list of operations to perform on campaign-label relationships.
     *     @type bool $partial_failure
     *           If true, successful operations will be carried out and invalid
     *           operations will return errors. If false, all operations will be carried
     *           out in one transaction if and only if they are all valid.
     *           Default is false.
     *     @type bool $validate_only
     *           If true, the request is validated but not executed. Only errors are
     *           returned, not results.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V6\Services\CampaignLabelService::initOnce();
        parent::__construct($data);
    }

    /**
     * Required. ID of the customer whose campaign-label relationships are being modified.
     *
     * Generated from protobuf field <code>string customer_id = 1 [(.google.api.field_behavior) = REQUIRED];</code>
     * @return string
     */
    public function getCustomerId()
    {
        return $this->customer_id;
    }

    /**
     * Required. ID of the customer whose campaign-label relationships are being modified.
     *
     * Generated from protobuf field <code>string customer_id = 1 [(.google.api.field_behavior) = REQUIRED];</code>
     * @param string $var
     * @return $this
     */
    public function setCustomerId($var)
    {
        GPBUtil::checkString($var, True);
        $this->customer_id = $var;

        return $this;
    }

    /**
     * Required. The list of operations to perform on campaign-label relationships.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v6.services.CampaignLabelOperation operations = 2 [(.google.api.field_behavior) = REQUIRED];</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getOperations()
    {
        return $this->operations;
    }

    /**
     * Required. The list of operations to perform on campaign-label relationships.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v6.services.CampaignLabelOperation operations = 2 [(.google.api.field_behavior) = REQUIRED];</code>
     * @param \Google\Ads\GoogleAds\V6\Services\CampaignLabelOperation[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setOperations($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Ads\GoogleAds\V6\Services\CampaignLabelOperation::class);
        $this->operations = $arr;

        return $this;
    }

    /**
     * If true, successful operations will be carried out and invalid
     * operations will return errors. If false, all operations will be carried
     * out in one transaction if and only if they are all valid.
     * Default is false.
     *
     * Generated from protobuf field <code>bool partial_failure = 3;</code>
     * @return bool
     */
    public function getPartialFailure()
    {
        return $this->partial_failure;
    }

    /**
     * If true, successful operations will be carried out and invalid
     * operations will return errors. If false, all operations will be carried
     * out in one transaction if and only if they are all valid.
     * Default is false.
     *
     * Generated from protobuf field <code>bool partial_failure = 3;</code>
     * @param bool $var
     * @return $this
     */
    public function setPartialFailure($var)
    {
        GPBUtil::checkBool($var);
        $this->partial_failure = $var;

        return $this;
    }

    /**
     * If true, the request is validated but not executed. Only errors are
     * returned, not results.
     *
     * Generated from protobuf field <code>bool validate_only = 4;</code>
     * @return bool
     */
    public function getValidateOnly()
    {
        return $this->validate_only;
    }

    /**
     * If true, the request is validated but not executed. Only errors are
     * returned, not results.
     *
     * Generated from protobuf field <code>bool validate_only = 4;</code>
     * @param bool $var
     * @return $this
     */
    public function setValidateOnly($var)
    {
        GPBUtil::checkBool($var);
        $this->validate_only = $var;

        return $this;
    }

}

