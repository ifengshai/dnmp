<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/iam/admin/v1/iam.proto

namespace Google\Iam\Admin\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * The service account sign JWT response.
 *
 * Generated from protobuf message <code>google.iam.admin.v1.SignJwtResponse</code>
 */
class SignJwtResponse extends \Google\Protobuf\Internal\Message
{
    /**
     * The id of the key used to sign the JWT.
     *
     * Generated from protobuf field <code>string key_id = 1;</code>
     */
    private $key_id = '';
    /**
     * The signed JWT.
     *
     * Generated from protobuf field <code>string signed_jwt = 2;</code>
     */
    private $signed_jwt = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $key_id
     *           The id of the key used to sign the JWT.
     *     @type string $signed_jwt
     *           The signed JWT.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Iam\Admin\V1\Iam::initOnce();
        parent::__construct($data);
    }

    /**
     * The id of the key used to sign the JWT.
     *
     * Generated from protobuf field <code>string key_id = 1;</code>
     * @return string
     */
    public function getKeyId()
    {
        return $this->key_id;
    }

    /**
     * The id of the key used to sign the JWT.
     *
     * Generated from protobuf field <code>string key_id = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setKeyId($var)
    {
        GPBUtil::checkString($var, True);
        $this->key_id = $var;

        return $this;
    }

    /**
     * The signed JWT.
     *
     * Generated from protobuf field <code>string signed_jwt = 2;</code>
     * @return string
     */
    public function getSignedJwt()
    {
        return $this->signed_jwt;
    }

    /**
     * The signed JWT.
     *
     * Generated from protobuf field <code>string signed_jwt = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setSignedJwt($var)
    {
        GPBUtil::checkString($var, True);
        $this->signed_jwt = $var;

        return $this;
    }

}

