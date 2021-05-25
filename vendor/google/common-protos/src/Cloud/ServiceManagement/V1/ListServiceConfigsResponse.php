<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/api/servicemanagement/v1/servicemanager.proto

namespace Google\Cloud\ServiceManagement\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Response message for ListServiceConfigs method.
 *
 * Generated from protobuf message <code>google.api.servicemanagement.v1.ListServiceConfigsResponse</code>
 */
class ListServiceConfigsResponse extends \Google\Protobuf\Internal\Message
{
    /**
     * The list of service configuration resources.
     *
     * Generated from protobuf field <code>repeated .google.api.Service service_configs = 1;</code>
     */
    private $service_configs;
    /**
     * The token of the next page of results.
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
     *     @type \Google\Api\Service[]|\Google\Protobuf\Internal\RepeatedField $service_configs
     *           The list of service configuration resources.
     *     @type string $next_page_token
     *           The token of the next page of results.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Api\Servicemanagement\V1\Servicemanager::initOnce();
        parent::__construct($data);
    }

    /**
     * The list of service configuration resources.
     *
     * Generated from protobuf field <code>repeated .google.api.Service service_configs = 1;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getServiceConfigs()
    {
        return $this->service_configs;
    }

    /**
     * The list of service configuration resources.
     *
     * Generated from protobuf field <code>repeated .google.api.Service service_configs = 1;</code>
     * @param \Google\Api\Service[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setServiceConfigs($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Api\Service::class);
        $this->service_configs = $arr;

        return $this;
    }

    /**
     * The token of the next page of results.
     *
     * Generated from protobuf field <code>string next_page_token = 2;</code>
     * @return string
     */
    public function getNextPageToken()
    {
        return $this->next_page_token;
    }

    /**
     * The token of the next page of results.
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

