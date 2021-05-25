<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/api/auth.proto

namespace Google\Api;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * `Authentication` defines the authentication configuration for an API.
 * Example for an API targeted for external use:
 *     name: calendar.googleapis.com
 *     authentication:
 *       providers:
 *       - id: google_calendar_auth
 *         jwks_uri: https://www.googleapis.com/oauth2/v1/certs
 *         issuer: https://securetoken.google.com
 *       rules:
 *       - selector: "*"
 *         requirements:
 *           provider_id: google_calendar_auth
 *
 * Generated from protobuf message <code>google.api.Authentication</code>
 */
class Authentication extends \Google\Protobuf\Internal\Message
{
    /**
     * A list of authentication rules that apply to individual API methods.
     * **NOTE:** All service configuration rules follow "last one wins" order.
     *
     * Generated from protobuf field <code>repeated .google.api.AuthenticationRule rules = 3;</code>
     */
    private $rules;
    /**
     * Defines a set of authentication providers that a service supports.
     *
     * Generated from protobuf field <code>repeated .google.api.AuthProvider providers = 4;</code>
     */
    private $providers;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Api\AuthenticationRule[]|\Google\Protobuf\Internal\RepeatedField $rules
     *           A list of authentication rules that apply to individual API methods.
     *           **NOTE:** All service configuration rules follow "last one wins" order.
     *     @type \Google\Api\AuthProvider[]|\Google\Protobuf\Internal\RepeatedField $providers
     *           Defines a set of authentication providers that a service supports.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Api\Auth::initOnce();
        parent::__construct($data);
    }

    /**
     * A list of authentication rules that apply to individual API methods.
     * **NOTE:** All service configuration rules follow "last one wins" order.
     *
     * Generated from protobuf field <code>repeated .google.api.AuthenticationRule rules = 3;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * A list of authentication rules that apply to individual API methods.
     * **NOTE:** All service configuration rules follow "last one wins" order.
     *
     * Generated from protobuf field <code>repeated .google.api.AuthenticationRule rules = 3;</code>
     * @param \Google\Api\AuthenticationRule[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setRules($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Api\AuthenticationRule::class);
        $this->rules = $arr;

        return $this;
    }

    /**
     * Defines a set of authentication providers that a service supports.
     *
     * Generated from protobuf field <code>repeated .google.api.AuthProvider providers = 4;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getProviders()
    {
        return $this->providers;
    }

    /**
     * Defines a set of authentication providers that a service supports.
     *
     * Generated from protobuf field <code>repeated .google.api.AuthProvider providers = 4;</code>
     * @param \Google\Api\AuthProvider[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setProviders($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Api\AuthProvider::class);
        $this->providers = $arr;

        return $this;
    }

}

