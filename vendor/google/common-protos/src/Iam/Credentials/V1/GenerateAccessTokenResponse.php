<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/iam/credentials/v1/common.proto

namespace Google\Iam\Credentials\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>google.iam.credentials.v1.GenerateAccessTokenResponse</code>
 */
class GenerateAccessTokenResponse extends \Google\Protobuf\Internal\Message
{
    /**
     * The OAuth 2.0 access token.
     *
     * Generated from protobuf field <code>string access_token = 1;</code>
     */
    private $access_token = '';
    /**
     * Token expiration time.
     * The expiration time is always set.
     *
     * Generated from protobuf field <code>.google.protobuf.Timestamp expire_time = 3;</code>
     */
    private $expire_time = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $access_token
     *           The OAuth 2.0 access token.
     *     @type \Google\Protobuf\Timestamp $expire_time
     *           Token expiration time.
     *           The expiration time is always set.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Iam\Credentials\V1\Common::initOnce();
        parent::__construct($data);
    }

    /**
     * The OAuth 2.0 access token.
     *
     * Generated from protobuf field <code>string access_token = 1;</code>
     * @return string
     */
    public function getAccessToken()
    {
        return $this->access_token;
    }

    /**
     * The OAuth 2.0 access token.
     *
     * Generated from protobuf field <code>string access_token = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setAccessToken($var)
    {
        GPBUtil::checkString($var, True);
        $this->access_token = $var;

        return $this;
    }

    /**
     * Token expiration time.
     * The expiration time is always set.
     *
     * Generated from protobuf field <code>.google.protobuf.Timestamp expire_time = 3;</code>
     * @return \Google\Protobuf\Timestamp
     */
    public function getExpireTime()
    {
        return $this->expire_time;
    }

    /**
     * Token expiration time.
     * The expiration time is always set.
     *
     * Generated from protobuf field <code>.google.protobuf.Timestamp expire_time = 3;</code>
     * @param \Google\Protobuf\Timestamp $var
     * @return $this
     */
    public function setExpireTime($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Timestamp::class);
        $this->expire_time = $var;

        return $this;
    }

}

