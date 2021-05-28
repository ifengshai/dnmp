<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/api/servicemanagement/v1/resources.proto

namespace Google\Cloud\ServiceManagement\V1\Rollout;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Strategy used to delete a service. This strategy is a placeholder only
 * used by the system generated rollout to delete a service.
 *
 * Generated from protobuf message <code>google.api.servicemanagement.v1.Rollout.DeleteServiceStrategy</code>
 */
class DeleteServiceStrategy extends \Google\Protobuf\Internal\Message
{

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Api\Servicemanagement\V1\Resources::initOnce();
        parent::__construct($data);
    }

}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(DeleteServiceStrategy::class, \Google\Cloud\ServiceManagement\V1\Rollout_DeleteServiceStrategy::class);

