<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/common/criteria.proto

namespace Google\Ads\GoogleAds\V5\Common;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * A placement criterion. This can be used to modify bids for sites when
 * targeting the content network.
 *
 * Generated from protobuf message <code>google.ads.googleads.v5.common.PlacementInfo</code>
 */
class PlacementInfo extends \Google\Protobuf\Internal\Message
{
    /**
     * URL of the placement.
     * For example, "http://www.domain.com".
     *
     * Generated from protobuf field <code>string url = 2;</code>
     */
    protected $url = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $url
     *           URL of the placement.
     *           For example, "http://www.domain.com".
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V5\Common\Criteria::initOnce();
        parent::__construct($data);
    }

    /**
     * URL of the placement.
     * For example, "http://www.domain.com".
     *
     * Generated from protobuf field <code>string url = 2;</code>
     * @return string
     */
    public function getUrl()
    {
        return isset($this->url) ? $this->url : '';
    }

    public function hasUrl()
    {
        return isset($this->url);
    }

    public function clearUrl()
    {
        unset($this->url);
    }

    /**
     * URL of the placement.
     * For example, "http://www.domain.com".
     *
     * Generated from protobuf field <code>string url = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setUrl($var)
    {
        GPBUtil::checkString($var, True);
        $this->url = $var;

        return $this;
    }

}

