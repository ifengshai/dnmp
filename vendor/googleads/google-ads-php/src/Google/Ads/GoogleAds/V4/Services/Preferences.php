<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/services/reach_plan_service.proto

namespace Google\Ads\GoogleAds\V4\Services;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Set of preferences about the planned mix.
 *
 * Generated from protobuf message <code>google.ads.googleads.v4.services.Preferences</code>
 */
class Preferences extends \Google\Protobuf\Internal\Message
{
    /**
     * True if ad skippable.
     * If not set, default is any value.
     *
     * Generated from protobuf field <code>.google.protobuf.BoolValue is_skippable = 1;</code>
     */
    protected $is_skippable = null;
    /**
     * True if ad start with sound.
     * If not set, default is any value.
     *
     * Generated from protobuf field <code>.google.protobuf.BoolValue starts_with_sound = 2;</code>
     */
    protected $starts_with_sound = null;
    /**
     * The length of the ad.
     * If not set, default is any value.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.enums.ReachPlanAdLengthEnum.ReachPlanAdLength ad_length = 3;</code>
     */
    protected $ad_length = 0;
    /**
     * True if ad will only show on the top content.
     * If not set, default is false.
     *
     * Generated from protobuf field <code>.google.protobuf.BoolValue top_content_only = 4;</code>
     */
    protected $top_content_only = null;
    /**
     * True if the price guaranteed. The cost of serving the ad is agreed upfront
     * and not subject to an auction.
     * If not set, default is any value.
     *
     * Generated from protobuf field <code>.google.protobuf.BoolValue has_guaranteed_price = 5;</code>
     */
    protected $has_guaranteed_price = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Protobuf\BoolValue $is_skippable
     *           True if ad skippable.
     *           If not set, default is any value.
     *     @type \Google\Protobuf\BoolValue $starts_with_sound
     *           True if ad start with sound.
     *           If not set, default is any value.
     *     @type int $ad_length
     *           The length of the ad.
     *           If not set, default is any value.
     *     @type \Google\Protobuf\BoolValue $top_content_only
     *           True if ad will only show on the top content.
     *           If not set, default is false.
     *     @type \Google\Protobuf\BoolValue $has_guaranteed_price
     *           True if the price guaranteed. The cost of serving the ad is agreed upfront
     *           and not subject to an auction.
     *           If not set, default is any value.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V4\Services\ReachPlanService::initOnce();
        parent::__construct($data);
    }

    /**
     * True if ad skippable.
     * If not set, default is any value.
     *
     * Generated from protobuf field <code>.google.protobuf.BoolValue is_skippable = 1;</code>
     * @return \Google\Protobuf\BoolValue
     */
    public function getIsSkippable()
    {
        return $this->is_skippable;
    }

    /**
     * Returns the unboxed value from <code>getIsSkippable()</code>

     * True if ad skippable.
     * If not set, default is any value.
     *
     * Generated from protobuf field <code>.google.protobuf.BoolValue is_skippable = 1;</code>
     * @return bool|null
     */
    public function getIsSkippableUnwrapped()
    {
        return $this->readWrapperValue("is_skippable");
    }

    /**
     * True if ad skippable.
     * If not set, default is any value.
     *
     * Generated from protobuf field <code>.google.protobuf.BoolValue is_skippable = 1;</code>
     * @param \Google\Protobuf\BoolValue $var
     * @return $this
     */
    public function setIsSkippable($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\BoolValue::class);
        $this->is_skippable = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\BoolValue object.

     * True if ad skippable.
     * If not set, default is any value.
     *
     * Generated from protobuf field <code>.google.protobuf.BoolValue is_skippable = 1;</code>
     * @param bool|null $var
     * @return $this
     */
    public function setIsSkippableUnwrapped($var)
    {
        $this->writeWrapperValue("is_skippable", $var);
        return $this;}

    /**
     * True if ad start with sound.
     * If not set, default is any value.
     *
     * Generated from protobuf field <code>.google.protobuf.BoolValue starts_with_sound = 2;</code>
     * @return \Google\Protobuf\BoolValue
     */
    public function getStartsWithSound()
    {
        return $this->starts_with_sound;
    }

    /**
     * Returns the unboxed value from <code>getStartsWithSound()</code>

     * True if ad start with sound.
     * If not set, default is any value.
     *
     * Generated from protobuf field <code>.google.protobuf.BoolValue starts_with_sound = 2;</code>
     * @return bool|null
     */
    public function getStartsWithSoundUnwrapped()
    {
        return $this->readWrapperValue("starts_with_sound");
    }

    /**
     * True if ad start with sound.
     * If not set, default is any value.
     *
     * Generated from protobuf field <code>.google.protobuf.BoolValue starts_with_sound = 2;</code>
     * @param \Google\Protobuf\BoolValue $var
     * @return $this
     */
    public function setStartsWithSound($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\BoolValue::class);
        $this->starts_with_sound = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\BoolValue object.

     * True if ad start with sound.
     * If not set, default is any value.
     *
     * Generated from protobuf field <code>.google.protobuf.BoolValue starts_with_sound = 2;</code>
     * @param bool|null $var
     * @return $this
     */
    public function setStartsWithSoundUnwrapped($var)
    {
        $this->writeWrapperValue("starts_with_sound", $var);
        return $this;}

    /**
     * The length of the ad.
     * If not set, default is any value.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.enums.ReachPlanAdLengthEnum.ReachPlanAdLength ad_length = 3;</code>
     * @return int
     */
    public function getAdLength()
    {
        return $this->ad_length;
    }

    /**
     * The length of the ad.
     * If not set, default is any value.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.enums.ReachPlanAdLengthEnum.ReachPlanAdLength ad_length = 3;</code>
     * @param int $var
     * @return $this
     */
    public function setAdLength($var)
    {
        GPBUtil::checkEnum($var, \Google\Ads\GoogleAds\V4\Enums\ReachPlanAdLengthEnum_ReachPlanAdLength::class);
        $this->ad_length = $var;

        return $this;
    }

    /**
     * True if ad will only show on the top content.
     * If not set, default is false.
     *
     * Generated from protobuf field <code>.google.protobuf.BoolValue top_content_only = 4;</code>
     * @return \Google\Protobuf\BoolValue
     */
    public function getTopContentOnly()
    {
        return $this->top_content_only;
    }

    /**
     * Returns the unboxed value from <code>getTopContentOnly()</code>

     * True if ad will only show on the top content.
     * If not set, default is false.
     *
     * Generated from protobuf field <code>.google.protobuf.BoolValue top_content_only = 4;</code>
     * @return bool|null
     */
    public function getTopContentOnlyUnwrapped()
    {
        return $this->readWrapperValue("top_content_only");
    }

    /**
     * True if ad will only show on the top content.
     * If not set, default is false.
     *
     * Generated from protobuf field <code>.google.protobuf.BoolValue top_content_only = 4;</code>
     * @param \Google\Protobuf\BoolValue $var
     * @return $this
     */
    public function setTopContentOnly($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\BoolValue::class);
        $this->top_content_only = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\BoolValue object.

     * True if ad will only show on the top content.
     * If not set, default is false.
     *
     * Generated from protobuf field <code>.google.protobuf.BoolValue top_content_only = 4;</code>
     * @param bool|null $var
     * @return $this
     */
    public function setTopContentOnlyUnwrapped($var)
    {
        $this->writeWrapperValue("top_content_only", $var);
        return $this;}

    /**
     * True if the price guaranteed. The cost of serving the ad is agreed upfront
     * and not subject to an auction.
     * If not set, default is any value.
     *
     * Generated from protobuf field <code>.google.protobuf.BoolValue has_guaranteed_price = 5;</code>
     * @return \Google\Protobuf\BoolValue
     */
    public function getHasGuaranteedPrice()
    {
        return $this->has_guaranteed_price;
    }

    /**
     * Returns the unboxed value from <code>getHasGuaranteedPrice()</code>

     * True if the price guaranteed. The cost of serving the ad is agreed upfront
     * and not subject to an auction.
     * If not set, default is any value.
     *
     * Generated from protobuf field <code>.google.protobuf.BoolValue has_guaranteed_price = 5;</code>
     * @return bool|null
     */
    public function getHasGuaranteedPriceUnwrapped()
    {
        return $this->readWrapperValue("has_guaranteed_price");
    }

    /**
     * True if the price guaranteed. The cost of serving the ad is agreed upfront
     * and not subject to an auction.
     * If not set, default is any value.
     *
     * Generated from protobuf field <code>.google.protobuf.BoolValue has_guaranteed_price = 5;</code>
     * @param \Google\Protobuf\BoolValue $var
     * @return $this
     */
    public function setHasGuaranteedPrice($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\BoolValue::class);
        $this->has_guaranteed_price = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\BoolValue object.

     * True if the price guaranteed. The cost of serving the ad is agreed upfront
     * and not subject to an auction.
     * If not set, default is any value.
     *
     * Generated from protobuf field <code>.google.protobuf.BoolValue has_guaranteed_price = 5;</code>
     * @param bool|null $var
     * @return $this
     */
    public function setHasGuaranteedPriceUnwrapped($var)
    {
        $this->writeWrapperValue("has_guaranteed_price", $var);
        return $this;}

}

