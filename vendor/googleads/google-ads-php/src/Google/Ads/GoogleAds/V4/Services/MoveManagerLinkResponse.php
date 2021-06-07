<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/services/customer_manager_link_service.proto

namespace Google\Ads\GoogleAds\V4\Services;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Response message for a CustomerManagerLink moveManagerLink.
 *
 * Generated from protobuf message <code>google.ads.googleads.v4.services.MoveManagerLinkResponse</code>
 */
class MoveManagerLinkResponse extends \Google\Protobuf\Internal\Message
{
    /**
     * Returned for successful operations. Represents a CustomerManagerLink
     * resource of the newly created link between client customer and new manager
     * customer.
     *
     * Generated from protobuf field <code>string resource_name = 1;</code>
     */
    protected $resource_name = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $resource_name
     *           Returned for successful operations. Represents a CustomerManagerLink
     *           resource of the newly created link between client customer and new manager
     *           customer.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V4\Services\CustomerManagerLinkService::initOnce();
        parent::__construct($data);
    }

    /**
     * Returned for successful operations. Represents a CustomerManagerLink
     * resource of the newly created link between client customer and new manager
     * customer.
     *
     * Generated from protobuf field <code>string resource_name = 1;</code>
     * @return string
     */
    public function getResourceName()
    {
        return $this->resource_name;
    }

    /**
     * Returned for successful operations. Represents a CustomerManagerLink
     * resource of the newly created link between client customer and new manager
     * customer.
     *
     * Generated from protobuf field <code>string resource_name = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setResourceName($var)
    {
        GPBUtil::checkString($var, True);
        $this->resource_name = $var;

        return $this;
    }

}

