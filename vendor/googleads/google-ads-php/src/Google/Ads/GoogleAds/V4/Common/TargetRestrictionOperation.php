<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/common/targeting_setting.proto

namespace Google\Ads\GoogleAds\V4\Common;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Operation to be performed on a target restriction list in a mutate.
 *
 * Generated from protobuf message <code>google.ads.googleads.v4.common.TargetRestrictionOperation</code>
 */
class TargetRestrictionOperation extends \Google\Protobuf\Internal\Message
{
    /**
     * Type of list operation to perform.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.common.TargetRestrictionOperation.Operator operator = 1;</code>
     */
    protected $operator = 0;
    /**
     * The target restriction being added to or removed from the list.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.common.TargetRestriction value = 2;</code>
     */
    protected $value = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int $operator
     *           Type of list operation to perform.
     *     @type \Google\Ads\GoogleAds\V4\Common\TargetRestriction $value
     *           The target restriction being added to or removed from the list.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V4\Common\TargetingSetting::initOnce();
        parent::__construct($data);
    }

    /**
     * Type of list operation to perform.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.common.TargetRestrictionOperation.Operator operator = 1;</code>
     * @return int
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * Type of list operation to perform.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.common.TargetRestrictionOperation.Operator operator = 1;</code>
     * @param int $var
     * @return $this
     */
    public function setOperator($var)
    {
        GPBUtil::checkEnum($var, \Google\Ads\GoogleAds\V4\Common\TargetRestrictionOperation_Operator::class);
        $this->operator = $var;

        return $this;
    }

    /**
     * The target restriction being added to or removed from the list.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.common.TargetRestriction value = 2;</code>
     * @return \Google\Ads\GoogleAds\V4\Common\TargetRestriction
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * The target restriction being added to or removed from the list.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.common.TargetRestriction value = 2;</code>
     * @param \Google\Ads\GoogleAds\V4\Common\TargetRestriction $var
     * @return $this
     */
    public function setValue($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V4\Common\TargetRestriction::class);
        $this->value = $var;

        return $this;
    }

}

