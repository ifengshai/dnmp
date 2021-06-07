<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v6/common/bidding.proto

namespace Google\Ads\GoogleAds\V6\Common;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * A bidding strategy where bids are a fraction of the advertised price for
 * some good or service.
 *
 * Generated from protobuf message <code>google.ads.googleads.v6.common.PercentCpc</code>
 */
class PercentCpc extends \Google\Protobuf\Internal\Message
{
    /**
     * Maximum bid limit that can be set by the bid strategy. This is
     * an optional field entered by the advertiser and specified in local micros.
     * Note: A zero value is interpreted in the same way as having bid_ceiling
     * undefined.
     *
     * Generated from protobuf field <code>int64 cpc_bid_ceiling_micros = 3;</code>
     */
    protected $cpc_bid_ceiling_micros = null;
    /**
     * Adjusts the bid for each auction upward or downward, depending on the
     * likelihood of a conversion. Individual bids may exceed
     * cpc_bid_ceiling_micros, but the average bid amount for a campaign should
     * not.
     *
     * Generated from protobuf field <code>bool enhanced_cpc_enabled = 4;</code>
     */
    protected $enhanced_cpc_enabled = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int|string $cpc_bid_ceiling_micros
     *           Maximum bid limit that can be set by the bid strategy. This is
     *           an optional field entered by the advertiser and specified in local micros.
     *           Note: A zero value is interpreted in the same way as having bid_ceiling
     *           undefined.
     *     @type bool $enhanced_cpc_enabled
     *           Adjusts the bid for each auction upward or downward, depending on the
     *           likelihood of a conversion. Individual bids may exceed
     *           cpc_bid_ceiling_micros, but the average bid amount for a campaign should
     *           not.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V6\Common\Bidding::initOnce();
        parent::__construct($data);
    }

    /**
     * Maximum bid limit that can be set by the bid strategy. This is
     * an optional field entered by the advertiser and specified in local micros.
     * Note: A zero value is interpreted in the same way as having bid_ceiling
     * undefined.
     *
     * Generated from protobuf field <code>int64 cpc_bid_ceiling_micros = 3;</code>
     * @return int|string
     */
    public function getCpcBidCeilingMicros()
    {
        return isset($this->cpc_bid_ceiling_micros) ? $this->cpc_bid_ceiling_micros : 0;
    }

    public function hasCpcBidCeilingMicros()
    {
        return isset($this->cpc_bid_ceiling_micros);
    }

    public function clearCpcBidCeilingMicros()
    {
        unset($this->cpc_bid_ceiling_micros);
    }

    /**
     * Maximum bid limit that can be set by the bid strategy. This is
     * an optional field entered by the advertiser and specified in local micros.
     * Note: A zero value is interpreted in the same way as having bid_ceiling
     * undefined.
     *
     * Generated from protobuf field <code>int64 cpc_bid_ceiling_micros = 3;</code>
     * @param int|string $var
     * @return $this
     */
    public function setCpcBidCeilingMicros($var)
    {
        GPBUtil::checkInt64($var);
        $this->cpc_bid_ceiling_micros = $var;

        return $this;
    }

    /**
     * Adjusts the bid for each auction upward or downward, depending on the
     * likelihood of a conversion. Individual bids may exceed
     * cpc_bid_ceiling_micros, but the average bid amount for a campaign should
     * not.
     *
     * Generated from protobuf field <code>bool enhanced_cpc_enabled = 4;</code>
     * @return bool
     */
    public function getEnhancedCpcEnabled()
    {
        return isset($this->enhanced_cpc_enabled) ? $this->enhanced_cpc_enabled : false;
    }

    public function hasEnhancedCpcEnabled()
    {
        return isset($this->enhanced_cpc_enabled);
    }

    public function clearEnhancedCpcEnabled()
    {
        unset($this->enhanced_cpc_enabled);
    }

    /**
     * Adjusts the bid for each auction upward or downward, depending on the
     * likelihood of a conversion. Individual bids may exceed
     * cpc_bid_ceiling_micros, but the average bid amount for a campaign should
     * not.
     *
     * Generated from protobuf field <code>bool enhanced_cpc_enabled = 4;</code>
     * @param bool $var
     * @return $this
     */
    public function setEnhancedCpcEnabled($var)
    {
        GPBUtil::checkBool($var);
        $this->enhanced_cpc_enabled = $var;

        return $this;
    }

}

