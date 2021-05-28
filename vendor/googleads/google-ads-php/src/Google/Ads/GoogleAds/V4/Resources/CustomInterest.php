<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/resources/custom_interest.proto

namespace Google\Ads\GoogleAds\V4\Resources;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * A custom interest. This is a list of users by interest.
 *
 * Generated from protobuf message <code>google.ads.googleads.v4.resources.CustomInterest</code>
 */
class CustomInterest extends \Google\Protobuf\Internal\Message
{
    /**
     * Immutable. The resource name of the custom interest.
     * Custom interest resource names have the form:
     * `customers/{customer_id}/customInterests/{custom_interest_id}`
     *
     * Generated from protobuf field <code>string resource_name = 1 [(.google.api.field_behavior) = IMMUTABLE, (.google.api.resource_reference) = {</code>
     */
    protected $resource_name = '';
    /**
     * Output only. Id of the custom interest.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value id = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $id = null;
    /**
     * Status of this custom interest. Indicates whether the custom interest is
     * enabled or removed.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.enums.CustomInterestStatusEnum.CustomInterestStatus status = 3;</code>
     */
    protected $status = 0;
    /**
     * Name of the custom interest. It should be unique across the same custom
     * affinity audience.
     * This field is required for create operations.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue name = 4;</code>
     */
    protected $name = null;
    /**
     * Type of the custom interest, CUSTOM_AFFINITY or CUSTOM_INTENT.
     * By default the type is set to CUSTOM_AFFINITY.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.enums.CustomInterestTypeEnum.CustomInterestType type = 5;</code>
     */
    protected $type = 0;
    /**
     * Description of this custom interest audience.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue description = 6;</code>
     */
    protected $description = null;
    /**
     * List of custom interest members that this custom interest is composed of.
     * Members can be added during CustomInterest creation. If members are
     * presented in UPDATE operation, existing members will be overridden.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v4.resources.CustomInterestMember members = 7;</code>
     */
    private $members;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $resource_name
     *           Immutable. The resource name of the custom interest.
     *           Custom interest resource names have the form:
     *           `customers/{customer_id}/customInterests/{custom_interest_id}`
     *     @type \Google\Protobuf\Int64Value $id
     *           Output only. Id of the custom interest.
     *     @type int $status
     *           Status of this custom interest. Indicates whether the custom interest is
     *           enabled or removed.
     *     @type \Google\Protobuf\StringValue $name
     *           Name of the custom interest. It should be unique across the same custom
     *           affinity audience.
     *           This field is required for create operations.
     *     @type int $type
     *           Type of the custom interest, CUSTOM_AFFINITY or CUSTOM_INTENT.
     *           By default the type is set to CUSTOM_AFFINITY.
     *     @type \Google\Protobuf\StringValue $description
     *           Description of this custom interest audience.
     *     @type \Google\Ads\GoogleAds\V4\Resources\CustomInterestMember[]|\Google\Protobuf\Internal\RepeatedField $members
     *           List of custom interest members that this custom interest is composed of.
     *           Members can be added during CustomInterest creation. If members are
     *           presented in UPDATE operation, existing members will be overridden.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V4\Resources\CustomInterest::initOnce();
        parent::__construct($data);
    }

    /**
     * Immutable. The resource name of the custom interest.
     * Custom interest resource names have the form:
     * `customers/{customer_id}/customInterests/{custom_interest_id}`
     *
     * Generated from protobuf field <code>string resource_name = 1 [(.google.api.field_behavior) = IMMUTABLE, (.google.api.resource_reference) = {</code>
     * @return string
     */
    public function getResourceName()
    {
        return $this->resource_name;
    }

    /**
     * Immutable. The resource name of the custom interest.
     * Custom interest resource names have the form:
     * `customers/{customer_id}/customInterests/{custom_interest_id}`
     *
     * Generated from protobuf field <code>string resource_name = 1 [(.google.api.field_behavior) = IMMUTABLE, (.google.api.resource_reference) = {</code>
     * @param string $var
     * @return $this
     */
    public function setResourceName($var)
    {
        GPBUtil::checkString($var, True);
        $this->resource_name = $var;

        return $this;
    }

    /**
     * Output only. Id of the custom interest.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value id = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\Int64Value
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the unboxed value from <code>getId()</code>

     * Output only. Id of the custom interest.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value id = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return int|string|null
     */
    public function getIdUnwrapped()
    {
        return $this->readWrapperValue("id");
    }

    /**
     * Output only. Id of the custom interest.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value id = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param \Google\Protobuf\Int64Value $var
     * @return $this
     */
    public function setId($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Int64Value::class);
        $this->id = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\Int64Value object.

     * Output only. Id of the custom interest.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value id = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param int|string|null $var
     * @return $this
     */
    public function setIdUnwrapped($var)
    {
        $this->writeWrapperValue("id", $var);
        return $this;}

    /**
     * Status of this custom interest. Indicates whether the custom interest is
     * enabled or removed.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.enums.CustomInterestStatusEnum.CustomInterestStatus status = 3;</code>
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Status of this custom interest. Indicates whether the custom interest is
     * enabled or removed.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.enums.CustomInterestStatusEnum.CustomInterestStatus status = 3;</code>
     * @param int $var
     * @return $this
     */
    public function setStatus($var)
    {
        GPBUtil::checkEnum($var, \Google\Ads\GoogleAds\V4\Enums\CustomInterestStatusEnum_CustomInterestStatus::class);
        $this->status = $var;

        return $this;
    }

    /**
     * Name of the custom interest. It should be unique across the same custom
     * affinity audience.
     * This field is required for create operations.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue name = 4;</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the unboxed value from <code>getName()</code>

     * Name of the custom interest. It should be unique across the same custom
     * affinity audience.
     * This field is required for create operations.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue name = 4;</code>
     * @return string|null
     */
    public function getNameUnwrapped()
    {
        return $this->readWrapperValue("name");
    }

    /**
     * Name of the custom interest. It should be unique across the same custom
     * affinity audience.
     * This field is required for create operations.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue name = 4;</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setName($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->name = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * Name of the custom interest. It should be unique across the same custom
     * affinity audience.
     * This field is required for create operations.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue name = 4;</code>
     * @param string|null $var
     * @return $this
     */
    public function setNameUnwrapped($var)
    {
        $this->writeWrapperValue("name", $var);
        return $this;}

    /**
     * Type of the custom interest, CUSTOM_AFFINITY or CUSTOM_INTENT.
     * By default the type is set to CUSTOM_AFFINITY.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.enums.CustomInterestTypeEnum.CustomInterestType type = 5;</code>
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Type of the custom interest, CUSTOM_AFFINITY or CUSTOM_INTENT.
     * By default the type is set to CUSTOM_AFFINITY.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.enums.CustomInterestTypeEnum.CustomInterestType type = 5;</code>
     * @param int $var
     * @return $this
     */
    public function setType($var)
    {
        GPBUtil::checkEnum($var, \Google\Ads\GoogleAds\V4\Enums\CustomInterestTypeEnum_CustomInterestType::class);
        $this->type = $var;

        return $this;
    }

    /**
     * Description of this custom interest audience.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue description = 6;</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Returns the unboxed value from <code>getDescription()</code>

     * Description of this custom interest audience.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue description = 6;</code>
     * @return string|null
     */
    public function getDescriptionUnwrapped()
    {
        return $this->readWrapperValue("description");
    }

    /**
     * Description of this custom interest audience.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue description = 6;</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setDescription($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->description = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * Description of this custom interest audience.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue description = 6;</code>
     * @param string|null $var
     * @return $this
     */
    public function setDescriptionUnwrapped($var)
    {
        $this->writeWrapperValue("description", $var);
        return $this;}

    /**
     * List of custom interest members that this custom interest is composed of.
     * Members can be added during CustomInterest creation. If members are
     * presented in UPDATE operation, existing members will be overridden.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v4.resources.CustomInterestMember members = 7;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getMembers()
    {
        return $this->members;
    }

    /**
     * List of custom interest members that this custom interest is composed of.
     * Members can be added during CustomInterest creation. If members are
     * presented in UPDATE operation, existing members will be overridden.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v4.resources.CustomInterestMember members = 7;</code>
     * @param \Google\Ads\GoogleAds\V4\Resources\CustomInterestMember[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setMembers($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Ads\GoogleAds\V4\Resources\CustomInterestMember::class);
        $this->members = $arr;

        return $this;
    }

}

