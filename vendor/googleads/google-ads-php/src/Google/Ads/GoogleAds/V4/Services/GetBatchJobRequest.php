<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/services/batch_job_service.proto

namespace Google\Ads\GoogleAds\V4\Services;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Request message for [BatchJobService.GetBatchJob][google.ads.googleads.v4.services.BatchJobService.GetBatchJob]
 *
 * Generated from protobuf message <code>google.ads.googleads.v4.services.GetBatchJobRequest</code>
 */
class GetBatchJobRequest extends \Google\Protobuf\Internal\Message
{
    /**
     * Required. The resource name of the batch job to get.
     *
     * Generated from protobuf field <code>string resource_name = 1 [(.google.api.field_behavior) = REQUIRED, (.google.api.resource_reference) = {</code>
     */
    protected $resource_name = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $resource_name
     *           Required. The resource name of the batch job to get.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V4\Services\BatchJobService::initOnce();
        parent::__construct($data);
    }

    /**
     * Required. The resource name of the batch job to get.
     *
     * Generated from protobuf field <code>string resource_name = 1 [(.google.api.field_behavior) = REQUIRED, (.google.api.resource_reference) = {</code>
     * @return string
     */
    public function getResourceName()
    {
        return $this->resource_name;
    }

    /**
     * Required. The resource name of the batch job to get.
     *
     * Generated from protobuf field <code>string resource_name = 1 [(.google.api.field_behavior) = REQUIRED, (.google.api.resource_reference) = {</code>
     * @param string $var
     * @return $this
     */
    public function setResourceName($var)
    {
        GPBUtil::checkString($var, True);
        $this->resource_name = $var;

        return $this;
    }

}

