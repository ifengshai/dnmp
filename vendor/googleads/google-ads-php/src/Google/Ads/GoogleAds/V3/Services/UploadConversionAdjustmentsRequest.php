<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/services/conversion_adjustment_upload_service.proto

namespace Google\Ads\GoogleAds\V3\Services;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Request message for
 * [ConversionAdjustmentUploadService.UploadConversionAdjustments][google.ads.googleads.v3.services.ConversionAdjustmentUploadService.UploadConversionAdjustments].
 *
 * Generated from protobuf message <code>google.ads.googleads.v3.services.UploadConversionAdjustmentsRequest</code>
 */
class UploadConversionAdjustmentsRequest extends \Google\Protobuf\Internal\Message
{
    /**
     * Required. The ID of the customer performing the upload.
     *
     * Generated from protobuf field <code>string customer_id = 1 [(.google.api.field_behavior) = REQUIRED];</code>
     */
    protected $customer_id = '';
    /**
     * Required. The conversion adjustments that are being uploaded.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v3.services.ConversionAdjustment conversion_adjustments = 2 [(.google.api.field_behavior) = REQUIRED];</code>
     */
    private $conversion_adjustments;
    /**
     * Required. If true, successful operations will be carried out and invalid
     * operations will return errors. If false, all operations will be carried out
     * in one transaction if and only if they are all valid. This should always be
     * set to true.
     *
     * Generated from protobuf field <code>bool partial_failure = 3 [(.google.api.field_behavior) = REQUIRED];</code>
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
     *           Required. The ID of the customer performing the upload.
     *     @type \Google\Ads\GoogleAds\V3\Services\ConversionAdjustment[]|\Google\Protobuf\Internal\RepeatedField $conversion_adjustments
     *           Required. The conversion adjustments that are being uploaded.
     *     @type bool $partial_failure
     *           Required. If true, successful operations will be carried out and invalid
     *           operations will return errors. If false, all operations will be carried out
     *           in one transaction if and only if they are all valid. This should always be
     *           set to true.
     *     @type bool $validate_only
     *           If true, the request is validated but not executed. Only errors are
     *           returned, not results.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V3\Services\ConversionAdjustmentUploadService::initOnce();
        parent::__construct($data);
    }

    /**
     * Required. The ID of the customer performing the upload.
     *
     * Generated from protobuf field <code>string customer_id = 1 [(.google.api.field_behavior) = REQUIRED];</code>
     * @return string
     */
    public function getCustomerId()
    {
        return $this->customer_id;
    }

    /**
     * Required. The ID of the customer performing the upload.
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
     * Required. The conversion adjustments that are being uploaded.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v3.services.ConversionAdjustment conversion_adjustments = 2 [(.google.api.field_behavior) = REQUIRED];</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getConversionAdjustments()
    {
        return $this->conversion_adjustments;
    }

    /**
     * Required. The conversion adjustments that are being uploaded.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v3.services.ConversionAdjustment conversion_adjustments = 2 [(.google.api.field_behavior) = REQUIRED];</code>
     * @param \Google\Ads\GoogleAds\V3\Services\ConversionAdjustment[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setConversionAdjustments($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Ads\GoogleAds\V3\Services\ConversionAdjustment::class);
        $this->conversion_adjustments = $arr;

        return $this;
    }

    /**
     * Required. If true, successful operations will be carried out and invalid
     * operations will return errors. If false, all operations will be carried out
     * in one transaction if and only if they are all valid. This should always be
     * set to true.
     *
     * Generated from protobuf field <code>bool partial_failure = 3 [(.google.api.field_behavior) = REQUIRED];</code>
     * @return bool
     */
    public function getPartialFailure()
    {
        return $this->partial_failure;
    }

    /**
     * Required. If true, successful operations will be carried out and invalid
     * operations will return errors. If false, all operations will be carried out
     * in one transaction if and only if they are all valid. This should always be
     * set to true.
     *
     * Generated from protobuf field <code>bool partial_failure = 3 [(.google.api.field_behavior) = REQUIRED];</code>
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

