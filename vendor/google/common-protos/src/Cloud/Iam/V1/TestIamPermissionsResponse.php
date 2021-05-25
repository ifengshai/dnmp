<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/iam/v1/iam_policy.proto

namespace Google\Cloud\Iam\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Response message for `TestIamPermissions` method.
 *
 * Generated from protobuf message <code>google.iam.v1.TestIamPermissionsResponse</code>
 */
class TestIamPermissionsResponse extends \Google\Protobuf\Internal\Message
{
    /**
     * A subset of `TestPermissionsRequest.permissions` that the caller is
     * allowed.
     *
     * Generated from protobuf field <code>repeated string permissions = 1;</code>
     */
    private $permissions;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string[]|\Google\Protobuf\Internal\RepeatedField $permissions
     *           A subset of `TestPermissionsRequest.permissions` that the caller is
     *           allowed.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Iam\V1\IamPolicy::initOnce();
        parent::__construct($data);
    }

    /**
     * A subset of `TestPermissionsRequest.permissions` that the caller is
     * allowed.
     *
     * Generated from protobuf field <code>repeated string permissions = 1;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * A subset of `TestPermissionsRequest.permissions` that the caller is
     * allowed.
     *
     * Generated from protobuf field <code>repeated string permissions = 1;</code>
     * @param string[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setPermissions($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::STRING);
        $this->permissions = $arr;

        return $this;
    }

}

