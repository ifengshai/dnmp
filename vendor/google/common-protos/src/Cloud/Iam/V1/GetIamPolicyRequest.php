<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/iam/v1/iam_policy.proto

namespace Google\Cloud\Iam\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Request message for `GetIamPolicy` method.
 *
 * Generated from protobuf message <code>google.iam.v1.GetIamPolicyRequest</code>
 */
class GetIamPolicyRequest extends \Google\Protobuf\Internal\Message
{
    /**
     * REQUIRED: The resource for which the policy is being requested.
     * See the operation documentation for the appropriate value for this field.
     *
     * Generated from protobuf field <code>string resource = 1 [(.google.api.field_behavior) = REQUIRED, (.google.api.resource_reference) = {</code>
     */
    private $resource = '';
    /**
     * OPTIONAL: A `GetPolicyOptions` object for specifying options to
     * `GetIamPolicy`. This field is only used by Cloud IAM.
     *
     * Generated from protobuf field <code>.google.iam.v1.GetPolicyOptions options = 2;</code>
     */
    private $options = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $resource
     *           REQUIRED: The resource for which the policy is being requested.
     *           See the operation documentation for the appropriate value for this field.
     *     @type \Google\Cloud\Iam\V1\GetPolicyOptions $options
     *           OPTIONAL: A `GetPolicyOptions` object for specifying options to
     *           `GetIamPolicy`. This field is only used by Cloud IAM.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Iam\V1\IamPolicy::initOnce();
        parent::__construct($data);
    }

    /**
     * REQUIRED: The resource for which the policy is being requested.
     * See the operation documentation for the appropriate value for this field.
     *
     * Generated from protobuf field <code>string resource = 1 [(.google.api.field_behavior) = REQUIRED, (.google.api.resource_reference) = {</code>
     * @return string
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * REQUIRED: The resource for which the policy is being requested.
     * See the operation documentation for the appropriate value for this field.
     *
     * Generated from protobuf field <code>string resource = 1 [(.google.api.field_behavior) = REQUIRED, (.google.api.resource_reference) = {</code>
     * @param string $var
     * @return $this
     */
    public function setResource($var)
    {
        GPBUtil::checkString($var, True);
        $this->resource = $var;

        return $this;
    }

    /**
     * OPTIONAL: A `GetPolicyOptions` object for specifying options to
     * `GetIamPolicy`. This field is only used by Cloud IAM.
     *
     * Generated from protobuf field <code>.google.iam.v1.GetPolicyOptions options = 2;</code>
     * @return \Google\Cloud\Iam\V1\GetPolicyOptions
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * OPTIONAL: A `GetPolicyOptions` object for specifying options to
     * `GetIamPolicy`. This field is only used by Cloud IAM.
     *
     * Generated from protobuf field <code>.google.iam.v1.GetPolicyOptions options = 2;</code>
     * @param \Google\Cloud\Iam\V1\GetPolicyOptions $var
     * @return $this
     */
    public function setOptions($var)
    {
        GPBUtil::checkMessage($var, \Google\Cloud\Iam\V1\GetPolicyOptions::class);
        $this->options = $var;

        return $this;
    }

}

