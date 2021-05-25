<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/common/criteria.proto

namespace Google\Ads\GoogleAds\V5\Common;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * A device criterion.
 *
 * Generated from protobuf message <code>google.ads.googleads.v5.common.DeviceInfo</code>
 */
class DeviceInfo extends \Google\Protobuf\Internal\Message
{
    /**
     * Type of the device.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.enums.DeviceEnum.Device type = 1;</code>
     */
    protected $type = 0;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int $type
     *           Type of the device.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V5\Common\Criteria::initOnce();
        parent::__construct($data);
    }

    /**
     * Type of the device.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.enums.DeviceEnum.Device type = 1;</code>
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Type of the device.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.enums.DeviceEnum.Device type = 1;</code>
     * @param int $var
     * @return $this
     */
    public function setType($var)
    {
        GPBUtil::checkEnum($var, \Google\Ads\GoogleAds\V5\Enums\DeviceEnum\Device::class);
        $this->type = $var;

        return $this;
    }

}

