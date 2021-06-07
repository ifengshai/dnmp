<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/services/reach_plan_service.proto

namespace Google\Ads\GoogleAds\V3\Services;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Request to list available products in a given location.
 *
 * Generated from protobuf message <code>google.ads.googleads.v3.services.ListPlannableProductsRequest</code>
 */
class ListPlannableProductsRequest extends \Google\Protobuf\Internal\Message
{
    /**
     * Required. The ID of the selected location for planning. To list the available
     * plannable location ids use ListPlannableLocations.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue plannable_location_id = 1 [(.google.api.field_behavior) = REQUIRED];</code>
     */
    protected $plannable_location_id = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Protobuf\StringValue $plannable_location_id
     *           Required. The ID of the selected location for planning. To list the available
     *           plannable location ids use ListPlannableLocations.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V3\Services\ReachPlanService::initOnce();
        parent::__construct($data);
    }

    /**
     * Required. The ID of the selected location for planning. To list the available
     * plannable location ids use ListPlannableLocations.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue plannable_location_id = 1 [(.google.api.field_behavior) = REQUIRED];</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getPlannableLocationId()
    {
        return $this->plannable_location_id;
    }

    /**
     * Returns the unboxed value from <code>getPlannableLocationId()</code>

     * Required. The ID of the selected location for planning. To list the available
     * plannable location ids use ListPlannableLocations.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue plannable_location_id = 1 [(.google.api.field_behavior) = REQUIRED];</code>
     * @return string|null
     */
    public function getPlannableLocationIdUnwrapped()
    {
        return $this->readWrapperValue("plannable_location_id");
    }

    /**
     * Required. The ID of the selected location for planning. To list the available
     * plannable location ids use ListPlannableLocations.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue plannable_location_id = 1 [(.google.api.field_behavior) = REQUIRED];</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setPlannableLocationId($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->plannable_location_id = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * Required. The ID of the selected location for planning. To list the available
     * plannable location ids use ListPlannableLocations.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue plannable_location_id = 1 [(.google.api.field_behavior) = REQUIRED];</code>
     * @param string|null $var
     * @return $this
     */
    public function setPlannableLocationIdUnwrapped($var)
    {
        $this->writeWrapperValue("plannable_location_id", $var);
        return $this;}

}

