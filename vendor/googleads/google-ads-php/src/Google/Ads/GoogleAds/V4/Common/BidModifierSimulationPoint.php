<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/common/simulation.proto

namespace Google\Ads\GoogleAds\V4\Common;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Projected metrics for a specific bid modifier amount.
 *
 * Generated from protobuf message <code>google.ads.googleads.v4.common.BidModifierSimulationPoint</code>
 */
class BidModifierSimulationPoint extends \Google\Protobuf\Internal\Message
{
    /**
     * The simulated bid modifier upon which projected metrics are based.
     *
     * Generated from protobuf field <code>.google.protobuf.DoubleValue bid_modifier = 1;</code>
     */
    protected $bid_modifier = null;
    /**
     * Projected number of biddable conversions.
     * Only search advertising channel type supports this field.
     *
     * Generated from protobuf field <code>.google.protobuf.DoubleValue biddable_conversions = 2;</code>
     */
    protected $biddable_conversions = null;
    /**
     * Projected total value of biddable conversions.
     * Only search advertising channel type supports this field.
     *
     * Generated from protobuf field <code>.google.protobuf.DoubleValue biddable_conversions_value = 3;</code>
     */
    protected $biddable_conversions_value = null;
    /**
     * Projected number of clicks.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value clicks = 4;</code>
     */
    protected $clicks = null;
    /**
     * Projected cost in micros.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value cost_micros = 5;</code>
     */
    protected $cost_micros = null;
    /**
     * Projected number of impressions.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value impressions = 6;</code>
     */
    protected $impressions = null;
    /**
     * Projected number of top slot impressions.
     * Only search advertising channel type supports this field.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value top_slot_impressions = 7;</code>
     */
    protected $top_slot_impressions = null;
    /**
     * Projected number of biddable conversions for the parent resource.
     * Only search advertising channel type supports this field.
     *
     * Generated from protobuf field <code>.google.protobuf.DoubleValue parent_biddable_conversions = 8;</code>
     */
    protected $parent_biddable_conversions = null;
    /**
     * Projected total value of biddable conversions for the parent resource.
     * Only search advertising channel type supports this field.
     *
     * Generated from protobuf field <code>.google.protobuf.DoubleValue parent_biddable_conversions_value = 9;</code>
     */
    protected $parent_biddable_conversions_value = null;
    /**
     * Projected number of clicks for the parent resource.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value parent_clicks = 10;</code>
     */
    protected $parent_clicks = null;
    /**
     * Projected cost in micros for the parent resource.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value parent_cost_micros = 11;</code>
     */
    protected $parent_cost_micros = null;
    /**
     * Projected number of impressions for the parent resource.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value parent_impressions = 12;</code>
     */
    protected $parent_impressions = null;
    /**
     * Projected number of top slot impressions for the parent resource.
     * Only search advertising channel type supports this field.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value parent_top_slot_impressions = 13;</code>
     */
    protected $parent_top_slot_impressions = null;
    /**
     * Projected minimum daily budget that must be available to the parent
     * resource to realize this simulation.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value parent_required_budget_micros = 14;</code>
     */
    protected $parent_required_budget_micros = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Protobuf\DoubleValue $bid_modifier
     *           The simulated bid modifier upon which projected metrics are based.
     *     @type \Google\Protobuf\DoubleValue $biddable_conversions
     *           Projected number of biddable conversions.
     *           Only search advertising channel type supports this field.
     *     @type \Google\Protobuf\DoubleValue $biddable_conversions_value
     *           Projected total value of biddable conversions.
     *           Only search advertising channel type supports this field.
     *     @type \Google\Protobuf\Int64Value $clicks
     *           Projected number of clicks.
     *     @type \Google\Protobuf\Int64Value $cost_micros
     *           Projected cost in micros.
     *     @type \Google\Protobuf\Int64Value $impressions
     *           Projected number of impressions.
     *     @type \Google\Protobuf\Int64Value $top_slot_impressions
     *           Projected number of top slot impressions.
     *           Only search advertising channel type supports this field.
     *     @type \Google\Protobuf\DoubleValue $parent_biddable_conversions
     *           Projected number of biddable conversions for the parent resource.
     *           Only search advertising channel type supports this field.
     *     @type \Google\Protobuf\DoubleValue $parent_biddable_conversions_value
     *           Projected total value of biddable conversions for the parent resource.
     *           Only search advertising channel type supports this field.
     *     @type \Google\Protobuf\Int64Value $parent_clicks
     *           Projected number of clicks for the parent resource.
     *     @type \Google\Protobuf\Int64Value $parent_cost_micros
     *           Projected cost in micros for the parent resource.
     *     @type \Google\Protobuf\Int64Value $parent_impressions
     *           Projected number of impressions for the parent resource.
     *     @type \Google\Protobuf\Int64Value $parent_top_slot_impressions
     *           Projected number of top slot impressions for the parent resource.
     *           Only search advertising channel type supports this field.
     *     @type \Google\Protobuf\Int64Value $parent_required_budget_micros
     *           Projected minimum daily budget that must be available to the parent
     *           resource to realize this simulation.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V4\Common\Simulation::initOnce();
        parent::__construct($data);
    }

    /**
     * The simulated bid modifier upon which projected metrics are based.
     *
     * Generated from protobuf field <code>.google.protobuf.DoubleValue bid_modifier = 1;</code>
     * @return \Google\Protobuf\DoubleValue
     */
    public function getBidModifier()
    {
        return $this->bid_modifier;
    }

    /**
     * Returns the unboxed value from <code>getBidModifier()</code>

     * The simulated bid modifier upon which projected metrics are based.
     *
     * Generated from protobuf field <code>.google.protobuf.DoubleValue bid_modifier = 1;</code>
     * @return float|null
     */
    public function getBidModifierUnwrapped()
    {
        return $this->readWrapperValue("bid_modifier");
    }

    /**
     * The simulated bid modifier upon which projected metrics are based.
     *
     * Generated from protobuf field <code>.google.protobuf.DoubleValue bid_modifier = 1;</code>
     * @param \Google\Protobuf\DoubleValue $var
     * @return $this
     */
    public function setBidModifier($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\DoubleValue::class);
        $this->bid_modifier = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\DoubleValue object.

     * The simulated bid modifier upon which projected metrics are based.
     *
     * Generated from protobuf field <code>.google.protobuf.DoubleValue bid_modifier = 1;</code>
     * @param float|null $var
     * @return $this
     */
    public function setBidModifierUnwrapped($var)
    {
        $this->writeWrapperValue("bid_modifier", $var);
        return $this;}

    /**
     * Projected number of biddable conversions.
     * Only search advertising channel type supports this field.
     *
     * Generated from protobuf field <code>.google.protobuf.DoubleValue biddable_conversions = 2;</code>
     * @return \Google\Protobuf\DoubleValue
     */
    public function getBiddableConversions()
    {
        return $this->biddable_conversions;
    }

    /**
     * Returns the unboxed value from <code>getBiddableConversions()</code>

     * Projected number of biddable conversions.
     * Only search advertising channel type supports this field.
     *
     * Generated from protobuf field <code>.google.protobuf.DoubleValue biddable_conversions = 2;</code>
     * @return float|null
     */
    public function getBiddableConversionsUnwrapped()
    {
        return $this->readWrapperValue("biddable_conversions");
    }

    /**
     * Projected number of biddable conversions.
     * Only search advertising channel type supports this field.
     *
     * Generated from protobuf field <code>.google.protobuf.DoubleValue biddable_conversions = 2;</code>
     * @param \Google\Protobuf\DoubleValue $var
     * @return $this
     */
    public function setBiddableConversions($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\DoubleValue::class);
        $this->biddable_conversions = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\DoubleValue object.

     * Projected number of biddable conversions.
     * Only search advertising channel type supports this field.
     *
     * Generated from protobuf field <code>.google.protobuf.DoubleValue biddable_conversions = 2;</code>
     * @param float|null $var
     * @return $this
     */
    public function setBiddableConversionsUnwrapped($var)
    {
        $this->writeWrapperValue("biddable_conversions", $var);
        return $this;}

    /**
     * Projected total value of biddable conversions.
     * Only search advertising channel type supports this field.
     *
     * Generated from protobuf field <code>.google.protobuf.DoubleValue biddable_conversions_value = 3;</code>
     * @return \Google\Protobuf\DoubleValue
     */
    public function getBiddableConversionsValue()
    {
        return $this->biddable_conversions_value;
    }

    /**
     * Returns the unboxed value from <code>getBiddableConversionsValue()</code>

     * Projected total value of biddable conversions.
     * Only search advertising channel type supports this field.
     *
     * Generated from protobuf field <code>.google.protobuf.DoubleValue biddable_conversions_value = 3;</code>
     * @return float|null
     */
    public function getBiddableConversionsValueUnwrapped()
    {
        return $this->readWrapperValue("biddable_conversions_value");
    }

    /**
     * Projected total value of biddable conversions.
     * Only search advertising channel type supports this field.
     *
     * Generated from protobuf field <code>.google.protobuf.DoubleValue biddable_conversions_value = 3;</code>
     * @param \Google\Protobuf\DoubleValue $var
     * @return $this
     */
    public function setBiddableConversionsValue($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\DoubleValue::class);
        $this->biddable_conversions_value = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\DoubleValue object.

     * Projected total value of biddable conversions.
     * Only search advertising channel type supports this field.
     *
     * Generated from protobuf field <code>.google.protobuf.DoubleValue biddable_conversions_value = 3;</code>
     * @param float|null $var
     * @return $this
     */
    public function setBiddableConversionsValueUnwrapped($var)
    {
        $this->writeWrapperValue("biddable_conversions_value", $var);
        return $this;}

    /**
     * Projected number of clicks.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value clicks = 4;</code>
     * @return \Google\Protobuf\Int64Value
     */
    public function getClicks()
    {
        return $this->clicks;
    }

    /**
     * Returns the unboxed value from <code>getClicks()</code>

     * Projected number of clicks.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value clicks = 4;</code>
     * @return int|string|null
     */
    public function getClicksUnwrapped()
    {
        return $this->readWrapperValue("clicks");
    }

    /**
     * Projected number of clicks.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value clicks = 4;</code>
     * @param \Google\Protobuf\Int64Value $var
     * @return $this
     */
    public function setClicks($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Int64Value::class);
        $this->clicks = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\Int64Value object.

     * Projected number of clicks.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value clicks = 4;</code>
     * @param int|string|null $var
     * @return $this
     */
    public function setClicksUnwrapped($var)
    {
        $this->writeWrapperValue("clicks", $var);
        return $this;}

    /**
     * Projected cost in micros.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value cost_micros = 5;</code>
     * @return \Google\Protobuf\Int64Value
     */
    public function getCostMicros()
    {
        return $this->cost_micros;
    }

    /**
     * Returns the unboxed value from <code>getCostMicros()</code>

     * Projected cost in micros.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value cost_micros = 5;</code>
     * @return int|string|null
     */
    public function getCostMicrosUnwrapped()
    {
        return $this->readWrapperValue("cost_micros");
    }

    /**
     * Projected cost in micros.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value cost_micros = 5;</code>
     * @param \Google\Protobuf\Int64Value $var
     * @return $this
     */
    public function setCostMicros($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Int64Value::class);
        $this->cost_micros = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\Int64Value object.

     * Projected cost in micros.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value cost_micros = 5;</code>
     * @param int|string|null $var
     * @return $this
     */
    public function setCostMicrosUnwrapped($var)
    {
        $this->writeWrapperValue("cost_micros", $var);
        return $this;}

    /**
     * Projected number of impressions.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value impressions = 6;</code>
     * @return \Google\Protobuf\Int64Value
     */
    public function getImpressions()
    {
        return $this->impressions;
    }

    /**
     * Returns the unboxed value from <code>getImpressions()</code>

     * Projected number of impressions.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value impressions = 6;</code>
     * @return int|string|null
     */
    public function getImpressionsUnwrapped()
    {
        return $this->readWrapperValue("impressions");
    }

    /**
     * Projected number of impressions.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value impressions = 6;</code>
     * @param \Google\Protobuf\Int64Value $var
     * @return $this
     */
    public function setImpressions($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Int64Value::class);
        $this->impressions = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\Int64Value object.

     * Projected number of impressions.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value impressions = 6;</code>
     * @param int|string|null $var
     * @return $this
     */
    public function setImpressionsUnwrapped($var)
    {
        $this->writeWrapperValue("impressions", $var);
        return $this;}

    /**
     * Projected number of top slot impressions.
     * Only search advertising channel type supports this field.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value top_slot_impressions = 7;</code>
     * @return \Google\Protobuf\Int64Value
     */
    public function getTopSlotImpressions()
    {
        return $this->top_slot_impressions;
    }

    /**
     * Returns the unboxed value from <code>getTopSlotImpressions()</code>

     * Projected number of top slot impressions.
     * Only search advertising channel type supports this field.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value top_slot_impressions = 7;</code>
     * @return int|string|null
     */
    public function getTopSlotImpressionsUnwrapped()
    {
        return $this->readWrapperValue("top_slot_impressions");
    }

    /**
     * Projected number of top slot impressions.
     * Only search advertising channel type supports this field.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value top_slot_impressions = 7;</code>
     * @param \Google\Protobuf\Int64Value $var
     * @return $this
     */
    public function setTopSlotImpressions($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Int64Value::class);
        $this->top_slot_impressions = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\Int64Value object.

     * Projected number of top slot impressions.
     * Only search advertising channel type supports this field.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value top_slot_impressions = 7;</code>
     * @param int|string|null $var
     * @return $this
     */
    public function setTopSlotImpressionsUnwrapped($var)
    {
        $this->writeWrapperValue("top_slot_impressions", $var);
        return $this;}

    /**
     * Projected number of biddable conversions for the parent resource.
     * Only search advertising channel type supports this field.
     *
     * Generated from protobuf field <code>.google.protobuf.DoubleValue parent_biddable_conversions = 8;</code>
     * @return \Google\Protobuf\DoubleValue
     */
    public function getParentBiddableConversions()
    {
        return $this->parent_biddable_conversions;
    }

    /**
     * Returns the unboxed value from <code>getParentBiddableConversions()</code>

     * Projected number of biddable conversions for the parent resource.
     * Only search advertising channel type supports this field.
     *
     * Generated from protobuf field <code>.google.protobuf.DoubleValue parent_biddable_conversions = 8;</code>
     * @return float|null
     */
    public function getParentBiddableConversionsUnwrapped()
    {
        return $this->readWrapperValue("parent_biddable_conversions");
    }

    /**
     * Projected number of biddable conversions for the parent resource.
     * Only search advertising channel type supports this field.
     *
     * Generated from protobuf field <code>.google.protobuf.DoubleValue parent_biddable_conversions = 8;</code>
     * @param \Google\Protobuf\DoubleValue $var
     * @return $this
     */
    public function setParentBiddableConversions($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\DoubleValue::class);
        $this->parent_biddable_conversions = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\DoubleValue object.

     * Projected number of biddable conversions for the parent resource.
     * Only search advertising channel type supports this field.
     *
     * Generated from protobuf field <code>.google.protobuf.DoubleValue parent_biddable_conversions = 8;</code>
     * @param float|null $var
     * @return $this
     */
    public function setParentBiddableConversionsUnwrapped($var)
    {
        $this->writeWrapperValue("parent_biddable_conversions", $var);
        return $this;}

    /**
     * Projected total value of biddable conversions for the parent resource.
     * Only search advertising channel type supports this field.
     *
     * Generated from protobuf field <code>.google.protobuf.DoubleValue parent_biddable_conversions_value = 9;</code>
     * @return \Google\Protobuf\DoubleValue
     */
    public function getParentBiddableConversionsValue()
    {
        return $this->parent_biddable_conversions_value;
    }

    /**
     * Returns the unboxed value from <code>getParentBiddableConversionsValue()</code>

     * Projected total value of biddable conversions for the parent resource.
     * Only search advertising channel type supports this field.
     *
     * Generated from protobuf field <code>.google.protobuf.DoubleValue parent_biddable_conversions_value = 9;</code>
     * @return float|null
     */
    public function getParentBiddableConversionsValueUnwrapped()
    {
        return $this->readWrapperValue("parent_biddable_conversions_value");
    }

    /**
     * Projected total value of biddable conversions for the parent resource.
     * Only search advertising channel type supports this field.
     *
     * Generated from protobuf field <code>.google.protobuf.DoubleValue parent_biddable_conversions_value = 9;</code>
     * @param \Google\Protobuf\DoubleValue $var
     * @return $this
     */
    public function setParentBiddableConversionsValue($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\DoubleValue::class);
        $this->parent_biddable_conversions_value = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\DoubleValue object.

     * Projected total value of biddable conversions for the parent resource.
     * Only search advertising channel type supports this field.
     *
     * Generated from protobuf field <code>.google.protobuf.DoubleValue parent_biddable_conversions_value = 9;</code>
     * @param float|null $var
     * @return $this
     */
    public function setParentBiddableConversionsValueUnwrapped($var)
    {
        $this->writeWrapperValue("parent_biddable_conversions_value", $var);
        return $this;}

    /**
     * Projected number of clicks for the parent resource.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value parent_clicks = 10;</code>
     * @return \Google\Protobuf\Int64Value
     */
    public function getParentClicks()
    {
        return $this->parent_clicks;
    }

    /**
     * Returns the unboxed value from <code>getParentClicks()</code>

     * Projected number of clicks for the parent resource.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value parent_clicks = 10;</code>
     * @return int|string|null
     */
    public function getParentClicksUnwrapped()
    {
        return $this->readWrapperValue("parent_clicks");
    }

    /**
     * Projected number of clicks for the parent resource.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value parent_clicks = 10;</code>
     * @param \Google\Protobuf\Int64Value $var
     * @return $this
     */
    public function setParentClicks($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Int64Value::class);
        $this->parent_clicks = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\Int64Value object.

     * Projected number of clicks for the parent resource.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value parent_clicks = 10;</code>
     * @param int|string|null $var
     * @return $this
     */
    public function setParentClicksUnwrapped($var)
    {
        $this->writeWrapperValue("parent_clicks", $var);
        return $this;}

    /**
     * Projected cost in micros for the parent resource.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value parent_cost_micros = 11;</code>
     * @return \Google\Protobuf\Int64Value
     */
    public function getParentCostMicros()
    {
        return $this->parent_cost_micros;
    }

    /**
     * Returns the unboxed value from <code>getParentCostMicros()</code>

     * Projected cost in micros for the parent resource.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value parent_cost_micros = 11;</code>
     * @return int|string|null
     */
    public function getParentCostMicrosUnwrapped()
    {
        return $this->readWrapperValue("parent_cost_micros");
    }

    /**
     * Projected cost in micros for the parent resource.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value parent_cost_micros = 11;</code>
     * @param \Google\Protobuf\Int64Value $var
     * @return $this
     */
    public function setParentCostMicros($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Int64Value::class);
        $this->parent_cost_micros = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\Int64Value object.

     * Projected cost in micros for the parent resource.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value parent_cost_micros = 11;</code>
     * @param int|string|null $var
     * @return $this
     */
    public function setParentCostMicrosUnwrapped($var)
    {
        $this->writeWrapperValue("parent_cost_micros", $var);
        return $this;}

    /**
     * Projected number of impressions for the parent resource.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value parent_impressions = 12;</code>
     * @return \Google\Protobuf\Int64Value
     */
    public function getParentImpressions()
    {
        return $this->parent_impressions;
    }

    /**
     * Returns the unboxed value from <code>getParentImpressions()</code>

     * Projected number of impressions for the parent resource.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value parent_impressions = 12;</code>
     * @return int|string|null
     */
    public function getParentImpressionsUnwrapped()
    {
        return $this->readWrapperValue("parent_impressions");
    }

    /**
     * Projected number of impressions for the parent resource.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value parent_impressions = 12;</code>
     * @param \Google\Protobuf\Int64Value $var
     * @return $this
     */
    public function setParentImpressions($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Int64Value::class);
        $this->parent_impressions = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\Int64Value object.

     * Projected number of impressions for the parent resource.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value parent_impressions = 12;</code>
     * @param int|string|null $var
     * @return $this
     */
    public function setParentImpressionsUnwrapped($var)
    {
        $this->writeWrapperValue("parent_impressions", $var);
        return $this;}

    /**
     * Projected number of top slot impressions for the parent resource.
     * Only search advertising channel type supports this field.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value parent_top_slot_impressions = 13;</code>
     * @return \Google\Protobuf\Int64Value
     */
    public function getParentTopSlotImpressions()
    {
        return $this->parent_top_slot_impressions;
    }

    /**
     * Returns the unboxed value from <code>getParentTopSlotImpressions()</code>

     * Projected number of top slot impressions for the parent resource.
     * Only search advertising channel type supports this field.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value parent_top_slot_impressions = 13;</code>
     * @return int|string|null
     */
    public function getParentTopSlotImpressionsUnwrapped()
    {
        return $this->readWrapperValue("parent_top_slot_impressions");
    }

    /**
     * Projected number of top slot impressions for the parent resource.
     * Only search advertising channel type supports this field.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value parent_top_slot_impressions = 13;</code>
     * @param \Google\Protobuf\Int64Value $var
     * @return $this
     */
    public function setParentTopSlotImpressions($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Int64Value::class);
        $this->parent_top_slot_impressions = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\Int64Value object.

     * Projected number of top slot impressions for the parent resource.
     * Only search advertising channel type supports this field.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value parent_top_slot_impressions = 13;</code>
     * @param int|string|null $var
     * @return $this
     */
    public function setParentTopSlotImpressionsUnwrapped($var)
    {
        $this->writeWrapperValue("parent_top_slot_impressions", $var);
        return $this;}

    /**
     * Projected minimum daily budget that must be available to the parent
     * resource to realize this simulation.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value parent_required_budget_micros = 14;</code>
     * @return \Google\Protobuf\Int64Value
     */
    public function getParentRequiredBudgetMicros()
    {
        return $this->parent_required_budget_micros;
    }

    /**
     * Returns the unboxed value from <code>getParentRequiredBudgetMicros()</code>

     * Projected minimum daily budget that must be available to the parent
     * resource to realize this simulation.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value parent_required_budget_micros = 14;</code>
     * @return int|string|null
     */
    public function getParentRequiredBudgetMicrosUnwrapped()
    {
        return $this->readWrapperValue("parent_required_budget_micros");
    }

    /**
     * Projected minimum daily budget that must be available to the parent
     * resource to realize this simulation.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value parent_required_budget_micros = 14;</code>
     * @param \Google\Protobuf\Int64Value $var
     * @return $this
     */
    public function setParentRequiredBudgetMicros($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Int64Value::class);
        $this->parent_required_budget_micros = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\Int64Value object.

     * Projected minimum daily budget that must be available to the parent
     * resource to realize this simulation.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value parent_required_budget_micros = 14;</code>
     * @param int|string|null $var
     * @return $this
     */
    public function setParentRequiredBudgetMicrosUnwrapped($var)
    {
        $this->writeWrapperValue("parent_required_budget_micros", $var);
        return $this;}

}

