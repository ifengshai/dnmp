<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/iam/admin/v1/iam.proto

namespace Google\Iam\Admin\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * The grantable role query response.
 *
 * Generated from protobuf message <code>google.iam.admin.v1.QueryGrantableRolesResponse</code>
 */
class QueryGrantableRolesResponse extends \Google\Protobuf\Internal\Message
{
    /**
     * The list of matching roles.
     *
     * Generated from protobuf field <code>repeated .google.iam.admin.v1.Role roles = 1;</code>
     */
    private $roles;
    /**
     * To retrieve the next page of results, set
     * `QueryGrantableRolesRequest.page_token` to this value.
     *
     * Generated from protobuf field <code>string next_page_token = 2;</code>
     */
    private $next_page_token = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Iam\Admin\V1\Role[]|\Google\Protobuf\Internal\RepeatedField $roles
     *           The list of matching roles.
     *     @type string $next_page_token
     *           To retrieve the next page of results, set
     *           `QueryGrantableRolesRequest.page_token` to this value.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Iam\Admin\V1\Iam::initOnce();
        parent::__construct($data);
    }

    /**
     * The list of matching roles.
     *
     * Generated from protobuf field <code>repeated .google.iam.admin.v1.Role roles = 1;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * The list of matching roles.
     *
     * Generated from protobuf field <code>repeated .google.iam.admin.v1.Role roles = 1;</code>
     * @param \Google\Iam\Admin\V1\Role[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setRoles($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Iam\Admin\V1\Role::class);
        $this->roles = $arr;

        return $this;
    }

    /**
     * To retrieve the next page of results, set
     * `QueryGrantableRolesRequest.page_token` to this value.
     *
     * Generated from protobuf field <code>string next_page_token = 2;</code>
     * @return string
     */
    public function getNextPageToken()
    {
        return $this->next_page_token;
    }

    /**
     * To retrieve the next page of results, set
     * `QueryGrantableRolesRequest.page_token` to this value.
     *
     * Generated from protobuf field <code>string next_page_token = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setNextPageToken($var)
    {
        GPBUtil::checkString($var, True);
        $this->next_page_token = $var;

        return $this;
    }

}

