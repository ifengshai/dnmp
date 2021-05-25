<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/services/shared_set_service.proto

namespace Google\Ads\GoogleAds\V5\Services;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * The result for the shared set mutate.
 *
 * Generated from protobuf message <code>google.ads.googleads.v5.services.MutateSharedSetResult</code>
 */
class MutateSharedSetResult extends \Google\Protobuf\Internal\Message
{
    /**
     * Returned for successful operations.
     *
     * Generated from protobuf field <code>string resource_name = 1;</code>
     */
    protected $resource_name = '';
    /**
     * The mutated shared set with only mutable fields after mutate. The field
     * will only be returned when response_content_type is set to
     * "MUTABLE_RESOURCE".
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.resources.SharedSet shared_set = 2;</code>
     */
    protected $shared_set = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $resource_name
     *           Returned for successful operations.
     *     @type \Google\Ads\GoogleAds\V5\Resources\SharedSet $shared_set
     *           The mutated shared set with only mutable fields after mutate. The field
     *           will only be returned when response_content_type is set to
     *           "MUTABLE_RESOURCE".
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V5\Services\SharedSetService::initOnce();
        parent::__construct($data);
    }

    /**
     * Returned for successful operations.
     *
     * Generated from protobuf field <code>string resource_name = 1;</code>
     * @return string
     */
    public function getResourceName()
    {
        return $this->resource_name;
    }

    /**
     * Returned for successful operations.
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

    /**
     * The mutated shared set with only mutable fields after mutate. The field
     * will only be returned when response_content_type is set to
     * "MUTABLE_RESOURCE".
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.resources.SharedSet shared_set = 2;</code>
     * @return \Google\Ads\GoogleAds\V5\Resources\SharedSet
     */
    public function getSharedSet()
    {
        return isset($this->shared_set) ? $this->shared_set : null;
    }

    public function hasSharedSet()
    {
        return isset($this->shared_set);
    }

    public function clearSharedSet()
    {
        unset($this->shared_set);
    }

    /**
     * The mutated shared set with only mutable fields after mutate. The field
     * will only be returned when response_content_type is set to
     * "MUTABLE_RESOURCE".
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.resources.SharedSet shared_set = 2;</code>
     * @param \Google\Ads\GoogleAds\V5\Resources\SharedSet $var
     * @return $this
     */
    public function setSharedSet($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V5\Resources\SharedSet::class);
        $this->shared_set = $var;

        return $this;
    }

}

