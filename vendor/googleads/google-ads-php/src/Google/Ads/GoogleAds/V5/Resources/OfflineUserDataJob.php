<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/resources/offline_user_data_job.proto

namespace Google\Ads\GoogleAds\V5\Resources;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * A job containing offline user data of store visitors, or user list members
 * that will be processed asynchronously. The uploaded data isn't readable and
 * the processing results of the job can only be read using
 * OfflineUserDataJobService.GetOfflineUserDataJob.
 *
 * Generated from protobuf message <code>google.ads.googleads.v5.resources.OfflineUserDataJob</code>
 */
class OfflineUserDataJob extends \Google\Protobuf\Internal\Message
{
    /**
     * Immutable. The resource name of the offline user data job.
     * Offline user data job resource names have the form:
     * `customers/{customer_id}/offlineUserDataJobs/{offline_user_data_job_id}`
     *
     * Generated from protobuf field <code>string resource_name = 1 [(.google.api.field_behavior) = IMMUTABLE, (.google.api.resource_reference) = {</code>
     */
    protected $resource_name = '';
    /**
     * Output only. ID of this offline user data job.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value id = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $id = null;
    /**
     * Immutable. User specified job ID.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value external_id = 3 [(.google.api.field_behavior) = IMMUTABLE];</code>
     */
    protected $external_id = null;
    /**
     * Immutable. Type of the job.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.enums.OfflineUserDataJobTypeEnum.OfflineUserDataJobType type = 4 [(.google.api.field_behavior) = IMMUTABLE];</code>
     */
    protected $type = 0;
    /**
     * Output only. Status of the job.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.enums.OfflineUserDataJobStatusEnum.OfflineUserDataJobStatus status = 5 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $status = 0;
    /**
     * Output only. Reason for the processing failure, if status is FAILED.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.enums.OfflineUserDataJobFailureReasonEnum.OfflineUserDataJobFailureReason failure_reason = 6 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $failure_reason = 0;
    protected $metadata;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $resource_name
     *           Immutable. The resource name of the offline user data job.
     *           Offline user data job resource names have the form:
     *           `customers/{customer_id}/offlineUserDataJobs/{offline_user_data_job_id}`
     *     @type \Google\Protobuf\Int64Value $id
     *           Output only. ID of this offline user data job.
     *     @type \Google\Protobuf\Int64Value $external_id
     *           Immutable. User specified job ID.
     *     @type int $type
     *           Immutable. Type of the job.
     *     @type int $status
     *           Output only. Status of the job.
     *     @type int $failure_reason
     *           Output only. Reason for the processing failure, if status is FAILED.
     *     @type \Google\Ads\GoogleAds\V5\Common\CustomerMatchUserListMetadata $customer_match_user_list_metadata
     *           Immutable. Metadata for data updates to a CRM-based user list.
     *     @type \Google\Ads\GoogleAds\V5\Common\StoreSalesMetadata $store_sales_metadata
     *           Immutable. Metadata for store sales data update.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V5\Resources\OfflineUserDataJob::initOnce();
        parent::__construct($data);
    }

    /**
     * Immutable. The resource name of the offline user data job.
     * Offline user data job resource names have the form:
     * `customers/{customer_id}/offlineUserDataJobs/{offline_user_data_job_id}`
     *
     * Generated from protobuf field <code>string resource_name = 1 [(.google.api.field_behavior) = IMMUTABLE, (.google.api.resource_reference) = {</code>
     * @return string
     */
    public function getResourceName()
    {
        return $this->resource_name;
    }

    /**
     * Immutable. The resource name of the offline user data job.
     * Offline user data job resource names have the form:
     * `customers/{customer_id}/offlineUserDataJobs/{offline_user_data_job_id}`
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
     * Output only. ID of this offline user data job.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value id = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\Int64Value
     */
    public function getId()
    {
        return isset($this->id) ? $this->id : null;
    }

    public function hasId()
    {
        return isset($this->id);
    }

    public function clearId()
    {
        unset($this->id);
    }

    /**
     * Returns the unboxed value from <code>getId()</code>

     * Output only. ID of this offline user data job.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value id = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return int|string|null
     */
    public function getIdUnwrapped()
    {
        return $this->readWrapperValue("id");
    }

    /**
     * Output only. ID of this offline user data job.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value id = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param \Google\Protobuf\Int64Value $var
     * @return $this
     */
    public function setId($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Int64Value::class);
        $this->id = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\Int64Value object.

     * Output only. ID of this offline user data job.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value id = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param int|string|null $var
     * @return $this
     */
    public function setIdUnwrapped($var)
    {
        $this->writeWrapperValue("id", $var);
        return $this;}

    /**
     * Immutable. User specified job ID.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value external_id = 3 [(.google.api.field_behavior) = IMMUTABLE];</code>
     * @return \Google\Protobuf\Int64Value
     */
    public function getExternalId()
    {
        return isset($this->external_id) ? $this->external_id : null;
    }

    public function hasExternalId()
    {
        return isset($this->external_id);
    }

    public function clearExternalId()
    {
        unset($this->external_id);
    }

    /**
     * Returns the unboxed value from <code>getExternalId()</code>

     * Immutable. User specified job ID.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value external_id = 3 [(.google.api.field_behavior) = IMMUTABLE];</code>
     * @return int|string|null
     */
    public function getExternalIdUnwrapped()
    {
        return $this->readWrapperValue("external_id");
    }

    /**
     * Immutable. User specified job ID.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value external_id = 3 [(.google.api.field_behavior) = IMMUTABLE];</code>
     * @param \Google\Protobuf\Int64Value $var
     * @return $this
     */
    public function setExternalId($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Int64Value::class);
        $this->external_id = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\Int64Value object.

     * Immutable. User specified job ID.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value external_id = 3 [(.google.api.field_behavior) = IMMUTABLE];</code>
     * @param int|string|null $var
     * @return $this
     */
    public function setExternalIdUnwrapped($var)
    {
        $this->writeWrapperValue("external_id", $var);
        return $this;}

    /**
     * Immutable. Type of the job.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.enums.OfflineUserDataJobTypeEnum.OfflineUserDataJobType type = 4 [(.google.api.field_behavior) = IMMUTABLE];</code>
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Immutable. Type of the job.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.enums.OfflineUserDataJobTypeEnum.OfflineUserDataJobType type = 4 [(.google.api.field_behavior) = IMMUTABLE];</code>
     * @param int $var
     * @return $this
     */
    public function setType($var)
    {
        GPBUtil::checkEnum($var, \Google\Ads\GoogleAds\V5\Enums\OfflineUserDataJobTypeEnum\OfflineUserDataJobType::class);
        $this->type = $var;

        return $this;
    }

    /**
     * Output only. Status of the job.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.enums.OfflineUserDataJobStatusEnum.OfflineUserDataJobStatus status = 5 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Output only. Status of the job.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.enums.OfflineUserDataJobStatusEnum.OfflineUserDataJobStatus status = 5 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param int $var
     * @return $this
     */
    public function setStatus($var)
    {
        GPBUtil::checkEnum($var, \Google\Ads\GoogleAds\V5\Enums\OfflineUserDataJobStatusEnum\OfflineUserDataJobStatus::class);
        $this->status = $var;

        return $this;
    }

    /**
     * Output only. Reason for the processing failure, if status is FAILED.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.enums.OfflineUserDataJobFailureReasonEnum.OfflineUserDataJobFailureReason failure_reason = 6 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return int
     */
    public function getFailureReason()
    {
        return $this->failure_reason;
    }

    /**
     * Output only. Reason for the processing failure, if status is FAILED.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.enums.OfflineUserDataJobFailureReasonEnum.OfflineUserDataJobFailureReason failure_reason = 6 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param int $var
     * @return $this
     */
    public function setFailureReason($var)
    {
        GPBUtil::checkEnum($var, \Google\Ads\GoogleAds\V5\Enums\OfflineUserDataJobFailureReasonEnum\OfflineUserDataJobFailureReason::class);
        $this->failure_reason = $var;

        return $this;
    }

    /**
     * Immutable. Metadata for data updates to a CRM-based user list.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.common.CustomerMatchUserListMetadata customer_match_user_list_metadata = 7 [(.google.api.field_behavior) = IMMUTABLE];</code>
     * @return \Google\Ads\GoogleAds\V5\Common\CustomerMatchUserListMetadata
     */
    public function getCustomerMatchUserListMetadata()
    {
        return $this->readOneof(7);
    }

    public function hasCustomerMatchUserListMetadata()
    {
        return $this->hasOneof(7);
    }

    /**
     * Immutable. Metadata for data updates to a CRM-based user list.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.common.CustomerMatchUserListMetadata customer_match_user_list_metadata = 7 [(.google.api.field_behavior) = IMMUTABLE];</code>
     * @param \Google\Ads\GoogleAds\V5\Common\CustomerMatchUserListMetadata $var
     * @return $this
     */
    public function setCustomerMatchUserListMetadata($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V5\Common\CustomerMatchUserListMetadata::class);
        $this->writeOneof(7, $var);

        return $this;
    }

    /**
     * Immutable. Metadata for store sales data update.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.common.StoreSalesMetadata store_sales_metadata = 8 [(.google.api.field_behavior) = IMMUTABLE];</code>
     * @return \Google\Ads\GoogleAds\V5\Common\StoreSalesMetadata
     */
    public function getStoreSalesMetadata()
    {
        return $this->readOneof(8);
    }

    public function hasStoreSalesMetadata()
    {
        return $this->hasOneof(8);
    }

    /**
     * Immutable. Metadata for store sales data update.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.common.StoreSalesMetadata store_sales_metadata = 8 [(.google.api.field_behavior) = IMMUTABLE];</code>
     * @param \Google\Ads\GoogleAds\V5\Common\StoreSalesMetadata $var
     * @return $this
     */
    public function setStoreSalesMetadata($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V5\Common\StoreSalesMetadata::class);
        $this->writeOneof(8, $var);

        return $this;
    }

    /**
     * @return string
     */
    public function getMetadata()
    {
        return $this->whichOneof("metadata");
    }

}

