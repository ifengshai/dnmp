<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/common/criteria.proto

namespace Google\Ads\GoogleAds\V3\Common;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Criterion for Interaction Type.
 *
 * Generated from protobuf message <code>google.ads.googleads.v3.common.InteractionTypeInfo</code>
 */
class InteractionTypeInfo extends \Google\Protobuf\Internal\Message
{
    /**
     * The interaction type.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v3.enums.InteractionTypeEnum.InteractionType type = 1;</code>
     */
    protected $type = 0;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int $type
     *           The interaction type.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V3\Common\Criteria::initOnce();
        parent::__construct($data);
    }

    /**
     * The interaction type.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v3.enums.InteractionTypeEnum.InteractionType type = 1;</code>
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * The interaction type.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v3.enums.InteractionTypeEnum.InteractionType type = 1;</code>
     * @param int $var
     * @return $this
     */
    public function setType($var)
    {
        GPBUtil::checkEnum($var, \Google\Ads\GoogleAds\V3\Enums\InteractionTypeEnum_InteractionType::class);
        $this->type = $var;

        return $this;
    }

}

