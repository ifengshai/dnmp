<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v6/common/criteria.proto

namespace Google\Ads\GoogleAds\V6\Common;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Content Label for category exclusion.
 *
 * Generated from protobuf message <code>google.ads.googleads.v6.common.ContentLabelInfo</code>
 */
class ContentLabelInfo extends \Google\Protobuf\Internal\Message
{
    /**
     * Content label type, required for CREATE operations.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v6.enums.ContentLabelTypeEnum.ContentLabelType type = 1;</code>
     */
    protected $type = 0;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int $type
     *           Content label type, required for CREATE operations.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V6\Common\Criteria::initOnce();
        parent::__construct($data);
    }

    /**
     * Content label type, required for CREATE operations.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v6.enums.ContentLabelTypeEnum.ContentLabelType type = 1;</code>
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Content label type, required for CREATE operations.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v6.enums.ContentLabelTypeEnum.ContentLabelType type = 1;</code>
     * @param int $var
     * @return $this
     */
    public function setType($var)
    {
        GPBUtil::checkEnum($var, \Google\Ads\GoogleAds\V6\Enums\ContentLabelTypeEnum\ContentLabelType::class);
        $this->type = $var;

        return $this;
    }

}

