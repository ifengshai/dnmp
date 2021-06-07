<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/api/servicecontrol/v1/service_controller.proto

namespace Google\Api\Servicecontrol\V1\CheckResponse;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * `ConsumerInfo` provides information about the consumer project.
 *
 * Generated from protobuf message <code>google.api.servicecontrol.v1.CheckResponse.ConsumerInfo</code>
 */
class ConsumerInfo extends \Google\Protobuf\Internal\Message
{
    /**
     * The Google cloud project number, e.g. 1234567890. A value of 0 indicates
     * no project number is found.
     *
     * Generated from protobuf field <code>int64 project_number = 1;</code>
     */
    private $project_number = 0;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int|string $project_number
     *           The Google cloud project number, e.g. 1234567890. A value of 0 indicates
     *           no project number is found.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Api\Servicecontrol\V1\ServiceController::initOnce();
        parent::__construct($data);
    }

    /**
     * The Google cloud project number, e.g. 1234567890. A value of 0 indicates
     * no project number is found.
     *
     * Generated from protobuf field <code>int64 project_number = 1;</code>
     * @return int|string
     */
    public function getProjectNumber()
    {
        return $this->project_number;
    }

    /**
     * The Google cloud project number, e.g. 1234567890. A value of 0 indicates
     * no project number is found.
     *
     * Generated from protobuf field <code>int64 project_number = 1;</code>
     * @param int|string $var
     * @return $this
     */
    public function setProjectNumber($var)
    {
        GPBUtil::checkInt64($var);
        $this->project_number = $var;

        return $this;
    }

}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ConsumerInfo::class, \Google\Api\Servicecontrol\V1\CheckResponse_ConsumerInfo::class);

