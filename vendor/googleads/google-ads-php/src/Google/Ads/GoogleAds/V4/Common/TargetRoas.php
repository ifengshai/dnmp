<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/common/bidding.proto

namespace Google\Ads\GoogleAds\V4\Common;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * An automated bidding strategy that helps you maximize revenue while
 * averaging a specific target return on ad spend (ROAS).
 *
 * Generated from protobuf message <code>google.ads.googleads.v4.common.TargetRoas</code>
 */
class TargetRoas extends \Google\Protobuf\Internal\Message
{
    /**
     * Required. The desired revenue (based on conversion data) per unit of spend.
     * Value must be between 0.01 and 1000.0, inclusive.
     *
     * Generated from protobuf field <code>.google.protobuf.DoubleValue target_roas = 1;</code>
     */
    protected $target_roas = null;
    /**
     * Maximum bid limit that can be set by the bid strategy.
     * The limit applies to all keywords managed by the strategy.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value cpc_bid_ceiling_micros = 2;</code>
     */
    protected $cpc_bid_ceiling_micros = null;
    /**
     * Minimum bid limit that can be set by the bid strategy.
     * The limit applies to all keywords managed by the strategy.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value cpc_bid_floor_micros = 3;</code>
     */
    protected $cpc_bid_floor_micros = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Protobuf\DoubleValue $target_roas
     *           Required. The desired revenue (based on conversion data) per unit of spend.
     *           Value must be between 0.01 and 1000.0, inclusive.
     *     @type \Google\Protobuf\Int64Value $cpc_bid_ceiling_micros
     *           Maximum bid limit that can be set by the bid strategy.
     *           The limit applies to all keywords managed by the strategy.
     *     @type \Google\Protobuf\Int64Value $cpc_bid_floor_micros
     *           Minimum bid limit that can be set by the bid strategy.
     *           The limit applies to all keywords managed by the strategy.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V4\Common\Bidding::initOnce();
        parent::__construct($data);
    }

    /**
     * Required. The desired revenue (based on conversion data) per unit of spend.
     * Value must be between 0.01 and 1000.0, inclusive.
     *
     * Generated from protobuf field <code>.google.protobuf.DoubleValue target_roas = 1;</code>
     * @return \Google\Protobuf\DoubleValue
     */
    public function getTargetRoas()
    {
        return $this->target_roas;
    }

    /**
     * Returns the unboxed value from <code>getTargetRoas()</code>

     * Required. The desired revenue (based on conversion data) per unit of spend.
     * Value must be between 0.01 and 1000.0, inclusive.
     *
     * Generated from protobuf field <code>.google.protobuf.DoubleValue target_roas = 1;</code>
     * @return float|null
     */
    public function getTargetRoasUnwrapped()
    {
        return $this->readWrapperValue("target_roas");
    }

    /**
     * Required. The desired revenue (based on conversion data) per unit of spend.
     * Value must be between 0.01 and 1000.0, inclusive.
     *
     * Generated from protobuf field <code>.google.protobuf.DoubleValue target_roas = 1;</code>
     * @param \Google\Protobuf\DoubleValue $var
     * @return $this
     */
    public function setTargetRoas($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\DoubleValue::class);
        $this->target_roas = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\DoubleValue object.

     * Required. The desired revenue (based on conversion data) per unit of spend.
     * Value must be between 0.01 and 1000.0, inclusive.
     *
     * Generated from protobuf field <code>.google.protobuf.DoubleValue target_roas = 1;</code>
     * @param float|null $var
     * @return $this
     */
    public function setTargetRoasUnwrapped($var)
    {
        $this->writeWrapperValue("target_roas", $var);
        return $this;}

    /**
     * Maximum bid limit that can be set by the bid strategy.
     * The limit applies to all keywords managed by the strategy.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value cpc_bid_ceiling_micros = 2;</code>
     * @return \Google\Protobuf\Int64Value
     */
    public function getCpcBidCeilingMicros()
    {
        return $this->cpc_bid_ceiling_micros;
    }

    /**
     * Returns the unboxed value from <code>getCpcBidCeilingMicros()</code>

     * Maximum bid limit that can be set by the bid strategy.
     * The limit applies to all keywords managed by the strategy.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value cpc_bid_ceiling_micros = 2;</code>
     * @return int|string|null
     */
    public function getCpcBidCeilingMicrosUnwrapped()
    {
        return $this->readWrapperValue("cpc_bid_ceiling_micros");
    }

    /**
     * Maximum bid limit that can be set by the bid strategy.
     * The limit applies to all keywords managed by the strategy.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value cpc_bid_ceiling_micros = 2;</code>
     * @param \Google\Protobuf\Int64Value $var
     * @return $this
     */
    public function setCpcBidCeilingMicros($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Int64Value::class);
        $this->cpc_bid_ceiling_micros = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\Int64Value object.

     * Maximum bid limit that can be set by the bid strategy.
     * The limit applies to all keywords managed by the strategy.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value cpc_bid_ceiling_micros = 2;</code>
     * @param int|string|null $var
     * @return $this
     */
    public function setCpcBidCeilingMicrosUnwrapped($var)
    {
        $this->writeWrapperValue("cpc_bid_ceiling_micros", $var);
        return $this;}

    /**
     * Minimum bid limit that can be set by the bid strategy.
     * The limit applies to all keywords managed by the strategy.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value cpc_bid_floor_micros = 3;</code>
     * @return \Google\Protobuf\Int64Value
     */
    public function getCpcBidFloorMicros()
    {
        return $this->cpc_bid_floor_micros;
    }

    /**
     * Returns the unboxed value from <code>getCpcBidFloorMicros()</code>

     * Minimum bid limit that can be set by the bid strategy.
     * The limit applies to all keywords managed by the strategy.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value cpc_bid_floor_micros = 3;</code>
     * @return int|string|null
     */
    public function getCpcBidFloorMicrosUnwrapped()
    {
        return $this->readWrapperValue("cpc_bid_floor_micros");
    }

    /**
     * Minimum bid limit that can be set by the bid strategy.
     * The limit applies to all keywords managed by the strategy.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value cpc_bid_floor_micros = 3;</code>
     * @param \Google\Protobuf\Int64Value $var
     * @return $this
     */
    public function setCpcBidFloorMicros($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Int64Value::class);
        $this->cpc_bid_floor_micros = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\Int64Value object.

     * Minimum bid limit that can be set by the bid strategy.
     * The limit applies to all keywords managed by the strategy.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value cpc_bid_floor_micros = 3;</code>
     * @param int|string|null $var
     * @return $this
     */
    public function setCpcBidFloorMicrosUnwrapped($var)
    {
        $this->writeWrapperValue("cpc_bid_floor_micros", $var);
        return $this;}

}

