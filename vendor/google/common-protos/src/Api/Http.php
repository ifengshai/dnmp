<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/api/http.proto

namespace Google\Api;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Defines the HTTP configuration for an API service. It contains a list of
 * [HttpRule][google.api.HttpRule], each specifying the mapping of an RPC method
 * to one or more HTTP REST API methods.
 *
 * Generated from protobuf message <code>google.api.Http</code>
 */
class Http extends \Google\Protobuf\Internal\Message
{
    /**
     * A list of HTTP configuration rules that apply to individual API methods.
     * **NOTE:** All service configuration rules follow "last one wins" order.
     *
     * Generated from protobuf field <code>repeated .google.api.HttpRule rules = 1;</code>
     */
    private $rules;
    /**
     * When set to true, URL path parmeters will be fully URI-decoded except in
     * cases of single segment matches in reserved expansion, where "%2F" will be
     * left encoded.
     * The default behavior is to not decode RFC 6570 reserved characters in multi
     * segment matches.
     *
     * Generated from protobuf field <code>bool fully_decode_reserved_expansion = 2;</code>
     */
    private $fully_decode_reserved_expansion = false;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Api\HttpRule[]|\Google\Protobuf\Internal\RepeatedField $rules
     *           A list of HTTP configuration rules that apply to individual API methods.
     *           **NOTE:** All service configuration rules follow "last one wins" order.
     *     @type bool $fully_decode_reserved_expansion
     *           When set to true, URL path parmeters will be fully URI-decoded except in
     *           cases of single segment matches in reserved expansion, where "%2F" will be
     *           left encoded.
     *           The default behavior is to not decode RFC 6570 reserved characters in multi
     *           segment matches.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Api\Http::initOnce();
        parent::__construct($data);
    }

    /**
     * A list of HTTP configuration rules that apply to individual API methods.
     * **NOTE:** All service configuration rules follow "last one wins" order.
     *
     * Generated from protobuf field <code>repeated .google.api.HttpRule rules = 1;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * A list of HTTP configuration rules that apply to individual API methods.
     * **NOTE:** All service configuration rules follow "last one wins" order.
     *
     * Generated from protobuf field <code>repeated .google.api.HttpRule rules = 1;</code>
     * @param \Google\Api\HttpRule[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setRules($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Api\HttpRule::class);
        $this->rules = $arr;

        return $this;
    }

    /**
     * When set to true, URL path parmeters will be fully URI-decoded except in
     * cases of single segment matches in reserved expansion, where "%2F" will be
     * left encoded.
     * The default behavior is to not decode RFC 6570 reserved characters in multi
     * segment matches.
     *
     * Generated from protobuf field <code>bool fully_decode_reserved_expansion = 2;</code>
     * @return bool
     */
    public function getFullyDecodeReservedExpansion()
    {
        return $this->fully_decode_reserved_expansion;
    }

    /**
     * When set to true, URL path parmeters will be fully URI-decoded except in
     * cases of single segment matches in reserved expansion, where "%2F" will be
     * left encoded.
     * The default behavior is to not decode RFC 6570 reserved characters in multi
     * segment matches.
     *
     * Generated from protobuf field <code>bool fully_decode_reserved_expansion = 2;</code>
     * @param bool $var
     * @return $this
     */
    public function setFullyDecodeReservedExpansion($var)
    {
        GPBUtil::checkBool($var);
        $this->fully_decode_reserved_expansion = $var;

        return $this;
    }

}

