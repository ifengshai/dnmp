<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/resources/batch_job.proto

namespace Google\Ads\GoogleAds\V4\Resources\BatchJob;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Additional information about the batch job. This message is also used as
 * metadata returned in batch job Long Running Operations.
 *
 * Generated from protobuf message <code>google.ads.googleads.v4.resources.BatchJob.BatchJobMetadata</code>
 */
class BatchJobMetadata extends \Google\Protobuf\Internal\Message
{
    /**
     * Output only. The time when this batch job was created.
     * Formatted as yyyy-mm-dd hh:mm:ss. Example: "2018-03-05 09:15:00"
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue creation_date_time = 1 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $creation_date_time = null;
    /**
     * Output only. The time when this batch job was completed.
     * Formatted as yyyy-MM-dd HH:mm:ss. Example: "2018-03-05 09:16:00"
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue completion_date_time = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $completion_date_time = null;
    /**
     * Output only. The fraction (between 0.0 and 1.0) of mutates that have been processed.
     * This is empty if the job hasn't started running yet.
     *
     * Generated from protobuf field <code>.google.protobuf.DoubleValue estimated_completion_ratio = 3 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $estimated_completion_ratio = null;
    /**
     * Output only. The number of mutate operations in the batch job.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value operation_count = 4 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $operation_count = null;
    /**
     * Output only. The number of mutate operations executed by the batch job.
     * Present only if the job has started running.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value executed_operation_count = 5 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $executed_operation_count = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Protobuf\StringValue $creation_date_time
     *           Output only. The time when this batch job was created.
     *           Formatted as yyyy-mm-dd hh:mm:ss. Example: "2018-03-05 09:15:00"
     *     @type \Google\Protobuf\StringValue $completion_date_time
     *           Output only. The time when this batch job was completed.
     *           Formatted as yyyy-MM-dd HH:mm:ss. Example: "2018-03-05 09:16:00"
     *     @type \Google\Protobuf\DoubleValue $estimated_completion_ratio
     *           Output only. The fraction (between 0.0 and 1.0) of mutates that have been processed.
     *           This is empty if the job hasn't started running yet.
     *     @type \Google\Protobuf\Int64Value $operation_count
     *           Output only. The number of mutate operations in the batch job.
     *     @type \Google\Protobuf\Int64Value $executed_operation_count
     *           Output only. The number of mutate operations executed by the batch job.
     *           Present only if the job has started running.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V4\Resources\BatchJob::initOnce();
        parent::__construct($data);
    }

    /**
     * Output only. The time when this batch job was created.
     * Formatted as yyyy-mm-dd hh:mm:ss. Example: "2018-03-05 09:15:00"
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue creation_date_time = 1 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getCreationDateTime()
    {
        return $this->creation_date_time;
    }

    /**
     * Returns the unboxed value from <code>getCreationDateTime()</code>

     * Output only. The time when this batch job was created.
     * Formatted as yyyy-mm-dd hh:mm:ss. Example: "2018-03-05 09:15:00"
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue creation_date_time = 1 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return string|null
     */
    public function getCreationDateTimeUnwrapped()
    {
        return $this->readWrapperValue("creation_date_time");
    }

    /**
     * Output only. The time when this batch job was created.
     * Formatted as yyyy-mm-dd hh:mm:ss. Example: "2018-03-05 09:15:00"
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue creation_date_time = 1 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setCreationDateTime($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->creation_date_time = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * Output only. The time when this batch job was created.
     * Formatted as yyyy-mm-dd hh:mm:ss. Example: "2018-03-05 09:15:00"
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue creation_date_time = 1 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param string|null $var
     * @return $this
     */
    public function setCreationDateTimeUnwrapped($var)
    {
        $this->writeWrapperValue("creation_date_time", $var);
        return $this;}

    /**
     * Output only. The time when this batch job was completed.
     * Formatted as yyyy-MM-dd HH:mm:ss. Example: "2018-03-05 09:16:00"
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue completion_date_time = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getCompletionDateTime()
    {
        return $this->completion_date_time;
    }

    /**
     * Returns the unboxed value from <code>getCompletionDateTime()</code>

     * Output only. The time when this batch job was completed.
     * Formatted as yyyy-MM-dd HH:mm:ss. Example: "2018-03-05 09:16:00"
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue completion_date_time = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return string|null
     */
    public function getCompletionDateTimeUnwrapped()
    {
        return $this->readWrapperValue("completion_date_time");
    }

    /**
     * Output only. The time when this batch job was completed.
     * Formatted as yyyy-MM-dd HH:mm:ss. Example: "2018-03-05 09:16:00"
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue completion_date_time = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setCompletionDateTime($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->completion_date_time = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * Output only. The time when this batch job was completed.
     * Formatted as yyyy-MM-dd HH:mm:ss. Example: "2018-03-05 09:16:00"
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue completion_date_time = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param string|null $var
     * @return $this
     */
    public function setCompletionDateTimeUnwrapped($var)
    {
        $this->writeWrapperValue("completion_date_time", $var);
        return $this;}

    /**
     * Output only. The fraction (between 0.0 and 1.0) of mutates that have been processed.
     * This is empty if the job hasn't started running yet.
     *
     * Generated from protobuf field <code>.google.protobuf.DoubleValue estimated_completion_ratio = 3 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\DoubleValue
     */
    public function getEstimatedCompletionRatio()
    {
        return $this->estimated_completion_ratio;
    }

    /**
     * Returns the unboxed value from <code>getEstimatedCompletionRatio()</code>

     * Output only. The fraction (between 0.0 and 1.0) of mutates that have been processed.
     * This is empty if the job hasn't started running yet.
     *
     * Generated from protobuf field <code>.google.protobuf.DoubleValue estimated_completion_ratio = 3 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return float|null
     */
    public function getEstimatedCompletionRatioUnwrapped()
    {
        return $this->readWrapperValue("estimated_completion_ratio");
    }

    /**
     * Output only. The fraction (between 0.0 and 1.0) of mutates that have been processed.
     * This is empty if the job hasn't started running yet.
     *
     * Generated from protobuf field <code>.google.protobuf.DoubleValue estimated_completion_ratio = 3 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param \Google\Protobuf\DoubleValue $var
     * @return $this
     */
    public function setEstimatedCompletionRatio($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\DoubleValue::class);
        $this->estimated_completion_ratio = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\DoubleValue object.

     * Output only. The fraction (between 0.0 and 1.0) of mutates that have been processed.
     * This is empty if the job hasn't started running yet.
     *
     * Generated from protobuf field <code>.google.protobuf.DoubleValue estimated_completion_ratio = 3 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param float|null $var
     * @return $this
     */
    public function setEstimatedCompletionRatioUnwrapped($var)
    {
        $this->writeWrapperValue("estimated_completion_ratio", $var);
        return $this;}

    /**
     * Output only. The number of mutate operations in the batch job.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value operation_count = 4 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\Int64Value
     */
    public function getOperationCount()
    {
        return $this->operation_count;
    }

    /**
     * Returns the unboxed value from <code>getOperationCount()</code>

     * Output only. The number of mutate operations in the batch job.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value operation_count = 4 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return int|string|null
     */
    public function getOperationCountUnwrapped()
    {
        return $this->readWrapperValue("operation_count");
    }

    /**
     * Output only. The number of mutate operations in the batch job.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value operation_count = 4 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param \Google\Protobuf\Int64Value $var
     * @return $this
     */
    public function setOperationCount($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Int64Value::class);
        $this->operation_count = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\Int64Value object.

     * Output only. The number of mutate operations in the batch job.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value operation_count = 4 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param int|string|null $var
     * @return $this
     */
    public function setOperationCountUnwrapped($var)
    {
        $this->writeWrapperValue("operation_count", $var);
        return $this;}

    /**
     * Output only. The number of mutate operations executed by the batch job.
     * Present only if the job has started running.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value executed_operation_count = 5 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\Int64Value
     */
    public function getExecutedOperationCount()
    {
        return $this->executed_operation_count;
    }

    /**
     * Returns the unboxed value from <code>getExecutedOperationCount()</code>

     * Output only. The number of mutate operations executed by the batch job.
     * Present only if the job has started running.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value executed_operation_count = 5 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return int|string|null
     */
    public function getExecutedOperationCountUnwrapped()
    {
        return $this->readWrapperValue("executed_operation_count");
    }

    /**
     * Output only. The number of mutate operations executed by the batch job.
     * Present only if the job has started running.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value executed_operation_count = 5 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param \Google\Protobuf\Int64Value $var
     * @return $this
     */
    public function setExecutedOperationCount($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Int64Value::class);
        $this->executed_operation_count = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\Int64Value object.

     * Output only. The number of mutate operations executed by the batch job.
     * Present only if the job has started running.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value executed_operation_count = 5 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param int|string|null $var
     * @return $this
     */
    public function setExecutedOperationCountUnwrapped($var)
    {
        $this->writeWrapperValue("executed_operation_count", $var);
        return $this;}

}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(BatchJobMetadata::class, \Google\Ads\GoogleAds\V4\Resources\BatchJob_BatchJobMetadata::class);

