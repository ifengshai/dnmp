<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v6/common/bidding.proto

namespace Google\Ads\GoogleAds\V6\Common;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * An automated bid strategy that sets your bids to help get as many clicks
 * as possible within your budget.
 *
 * Generated from protobuf message <code>google.ads.googleads.v6.common.TargetSpend</code>
 */
class TargetSpend extends \Google\Protobuf\Internal\Message
{
    /**
     * The spend target under which to maximize clicks.
     * A TargetSpend bidder will attempt to spend the smaller of this value
     * or the natural throttling spend amount.
     * If not specified, the budget is used as the spend target.
     * This field is deprecated and should no longer be used. See
     * https://ads-developers.googleblog.com/2020/05/reminder-about-sunset-creation-of.html
     * for details.
     *
     * Generated from protobuf field <code>int64 target_spend_micros = 3 [deprecated = true];</code>
     */
    protected $target_spend_micros = null;
    /**
     * Maximum bid limit that can be set by the bid strategy.
     * The limit applies to all keywords managed by the strategy.
     *
     * Generated from protobuf field <code>int64 cpc_bid_ceiling_micros = 4;</code>
     */
    protected $cpc_bid_ceiling_micros = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int|string $target_spend_micros
     *           The spend target under which to maximize clicks.
     *           A TargetSpend bidder will attempt to spend the smaller of this value
     *           or the natural throttling spend amount.
     *           If not specified, the budget is used as the spend target.
     *           This field is deprecated and should no longer be used. See
     *           https://ads-developers.googleblog.com/2020/05/reminder-about-sunset-creation-of.html
     *           for details.
     *     @type int|string $cpc_bid_ceiling_micros
     *           Maximum bid limit that can be set by the bid strategy.
     *           The limit applies to all keywords managed by the strategy.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V6\Common\Bidding::initOnce();
        parent::__construct($data);
    }

    /**
     * The spend target under which to maximize clicks.
     * A TargetSpend bidder will attempt to spend the smaller of this value
     * or the natural throttling spend amount.
     * If not specified, the budget is used as the spend target.
     * This field is deprecated and should no longer be used. See
     * https://ads-developers.googleblog.com/2020/05/reminder-about-sunset-creation-of.html
     * for details.
     *
     * Generated from protobuf field <code>int64 target_spend_micros = 3 [deprecated = true];</code>
     * @return int|string
     */
    public function getTargetSpendMicros()
    {
        return isset($this->target_spend_micros) ? $this->target_spend_micros : 0;
    }

    public function hasTargetSpendMicros()
    {
        return isset($this->target_spend_micros);
    }

    public function clearTargetSpendMicros()
    {
        unset($this->target_spend_micros);
    }

    /**
     * The spend target under which to maximize clicks.
     * A TargetSpend bidder will attempt to spend the smaller of this value
     * or the natural throttling spend amount.
     * If not specified, the budget is used as the spend target.
     * This field is deprecated and should no longer be used. See
     * https://ads-developers.googleblog.com/2020/05/reminder-about-sunset-creation-of.html
     * for details.
     *
     * Generated from protobuf field <code>int64 target_spend_micros = 3 [deprecated = true];</code>
     * @param int|string $var
     * @return $this
     */
    public function setTargetSpendMicros($var)
    {
        GPBUtil::checkInt64($var);
        $this->target_spend_micros = $var;

        return $this;
    }

    /**
     * Maximum bid limit that can be set by the bid strategy.
     * The limit applies to all keywords managed by the strategy.
     *
     * Generated from protobuf field <code>int64 cpc_bid_ceiling_micros = 4;</code>
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
     * Maximum bid limit that can be set by the bid strategy.
     * The limit applies to all keywords managed by the strategy.
     *
     * Generated from protobuf field <code>int64 cpc_bid_ceiling_micros = 4;</code>
     * @param int|string $var
     * @return $this
     */
    public function setCpcBidCeilingMicros($var)
    {
        GPBUtil::checkInt64($var);
        $this->cpc_bid_ceiling_micros = $var;

        return $this;
    }

}

