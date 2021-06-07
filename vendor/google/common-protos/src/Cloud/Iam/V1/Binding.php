<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/iam/v1/policy.proto

namespace Google\Cloud\Iam\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Associates `members` with a `role`.
 *
 * Generated from protobuf message <code>google.iam.v1.Binding</code>
 */
class Binding extends \Google\Protobuf\Internal\Message
{
    /**
     * Role that is assigned to `members`.
     * For example, `roles/viewer`, `roles/editor`, or `roles/owner`.
     *
     * Generated from protobuf field <code>string role = 1;</code>
     */
    private $role = '';
    /**
     * Specifies the identities requesting access for a Cloud Platform resource.
     * `members` can have the following values:
     * * `allUsers`: A special identifier that represents anyone who is
     *    on the internet; with or without a Google account.
     * * `allAuthenticatedUsers`: A special identifier that represents anyone
     *    who is authenticated with a Google account or a service account.
     * * `user:{emailid}`: An email address that represents a specific Google
     *    account. For example, `alice&#64;example.com` .
     * * `serviceAccount:{emailid}`: An email address that represents a service
     *    account. For example, `my-other-app&#64;appspot.gserviceaccount.com`.
     * * `group:{emailid}`: An email address that represents a Google group.
     *    For example, `admins&#64;example.com`.
     * * `domain:{domain}`: The G Suite domain (primary) that represents all the
     *    users of that domain. For example, `google.com` or `example.com`.
     *
     * Generated from protobuf field <code>repeated string members = 2;</code>
     */
    private $members;
    /**
     * The condition that is associated with this binding.
     * NOTE: An unsatisfied condition will not allow user access via current
     * binding. Different bindings, including their conditions, are examined
     * independently.
     *
     * Generated from protobuf field <code>.google.type.Expr condition = 3;</code>
     */
    private $condition = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $role
     *           Role that is assigned to `members`.
     *           For example, `roles/viewer`, `roles/editor`, or `roles/owner`.
     *     @type string[]|\Google\Protobuf\Internal\RepeatedField $members
     *           Specifies the identities requesting access for a Cloud Platform resource.
     *           `members` can have the following values:
     *           * `allUsers`: A special identifier that represents anyone who is
     *              on the internet; with or without a Google account.
     *           * `allAuthenticatedUsers`: A special identifier that represents anyone
     *              who is authenticated with a Google account or a service account.
     *           * `user:{emailid}`: An email address that represents a specific Google
     *              account. For example, `alice&#64;example.com` .
     *           * `serviceAccount:{emailid}`: An email address that represents a service
     *              account. For example, `my-other-app&#64;appspot.gserviceaccount.com`.
     *           * `group:{emailid}`: An email address that represents a Google group.
     *              For example, `admins&#64;example.com`.
     *           * `domain:{domain}`: The G Suite domain (primary) that represents all the
     *              users of that domain. For example, `google.com` or `example.com`.
     *     @type \Google\Type\Expr $condition
     *           The condition that is associated with this binding.
     *           NOTE: An unsatisfied condition will not allow user access via current
     *           binding. Different bindings, including their conditions, are examined
     *           independently.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Iam\V1\Policy::initOnce();
        parent::__construct($data);
    }

    /**
     * Role that is assigned to `members`.
     * For example, `roles/viewer`, `roles/editor`, or `roles/owner`.
     *
     * Generated from protobuf field <code>string role = 1;</code>
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Role that is assigned to `members`.
     * For example, `roles/viewer`, `roles/editor`, or `roles/owner`.
     *
     * Generated from protobuf field <code>string role = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setRole($var)
    {
        GPBUtil::checkString($var, True);
        $this->role = $var;

        return $this;
    }

    /**
     * Specifies the identities requesting access for a Cloud Platform resource.
     * `members` can have the following values:
     * * `allUsers`: A special identifier that represents anyone who is
     *    on the internet; with or without a Google account.
     * * `allAuthenticatedUsers`: A special identifier that represents anyone
     *    who is authenticated with a Google account or a service account.
     * * `user:{emailid}`: An email address that represents a specific Google
     *    account. For example, `alice&#64;example.com` .
     * * `serviceAccount:{emailid}`: An email address that represents a service
     *    account. For example, `my-other-app&#64;appspot.gserviceaccount.com`.
     * * `group:{emailid}`: An email address that represents a Google group.
     *    For example, `admins&#64;example.com`.
     * * `domain:{domain}`: The G Suite domain (primary) that represents all the
     *    users of that domain. For example, `google.com` or `example.com`.
     *
     * Generated from protobuf field <code>repeated string members = 2;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getMembers()
    {
        return $this->members;
    }

    /**
     * Specifies the identities requesting access for a Cloud Platform resource.
     * `members` can have the following values:
     * * `allUsers`: A special identifier that represents anyone who is
     *    on the internet; with or without a Google account.
     * * `allAuthenticatedUsers`: A special identifier that represents anyone
     *    who is authenticated with a Google account or a service account.
     * * `user:{emailid}`: An email address that represents a specific Google
     *    account. For example, `alice&#64;example.com` .
     * * `serviceAccount:{emailid}`: An email address that represents a service
     *    account. For example, `my-other-app&#64;appspot.gserviceaccount.com`.
     * * `group:{emailid}`: An email address that represents a Google group.
     *    For example, `admins&#64;example.com`.
     * * `domain:{domain}`: The G Suite domain (primary) that represents all the
     *    users of that domain. For example, `google.com` or `example.com`.
     *
     * Generated from protobuf field <code>repeated string members = 2;</code>
     * @param string[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setMembers($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::STRING);
        $this->members = $arr;

        return $this;
    }

    /**
     * The condition that is associated with this binding.
     * NOTE: An unsatisfied condition will not allow user access via current
     * binding. Different bindings, including their conditions, are examined
     * independently.
     *
     * Generated from protobuf field <code>.google.type.Expr condition = 3;</code>
     * @return \Google\Type\Expr
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * The condition that is associated with this binding.
     * NOTE: An unsatisfied condition will not allow user access via current
     * binding. Different bindings, including their conditions, are examined
     * independently.
     *
     * Generated from protobuf field <code>.google.type.Expr condition = 3;</code>
     * @param \Google\Type\Expr $var
     * @return $this
     */
    public function setCondition($var)
    {
        GPBUtil::checkMessage($var, \Google\Type\Expr::class);
        $this->condition = $var;

        return $this;
    }

}

