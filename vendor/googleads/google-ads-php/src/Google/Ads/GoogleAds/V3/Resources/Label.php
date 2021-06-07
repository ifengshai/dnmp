<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/resources/label.proto

namespace Google\Ads\GoogleAds\V3\Resources;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * A label.
 *
 * Generated from protobuf message <code>google.ads.googleads.v3.resources.Label</code>
 */
class Label extends \Google\Protobuf\Internal\Message
{
    /**
     * Immutable. Name of the resource.
     * Label resource names have the form:
     * `customers/{customer_id}/labels/{label_id}`
     *
     * Generated from protobuf field <code>string resource_name = 1 [(.google.api.field_behavior) = IMMUTABLE, (.google.api.resource_reference) = {</code>
     */
    protected $resource_name = '';
    /**
     * Output only. Id of the label. Read only.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value id = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $id = null;
    /**
     * The name of the label.
     * This field is required and should not be empty when creating a new label.
     * The length of this string should be between 1 and 80, inclusive.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue name = 3;</code>
     */
    protected $name = null;
    /**
     * Output only. Status of the label. Read only.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v3.enums.LabelStatusEnum.LabelStatus status = 4 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $status = 0;
    /**
     * A type of label displaying text on a colored background.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v3.common.TextLabel text_label = 5;</code>
     */
    protected $text_label = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $resource_name
     *           Immutable. Name of the resource.
     *           Label resource names have the form:
     *           `customers/{customer_id}/labels/{label_id}`
     *     @type \Google\Protobuf\Int64Value $id
     *           Output only. Id of the label. Read only.
     *     @type \Google\Protobuf\StringValue $name
     *           The name of the label.
     *           This field is required and should not be empty when creating a new label.
     *           The length of this string should be between 1 and 80, inclusive.
     *     @type int $status
     *           Output only. Status of the label. Read only.
     *     @type \Google\Ads\GoogleAds\V3\Common\TextLabel $text_label
     *           A type of label displaying text on a colored background.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V3\Resources\Label::initOnce();
        parent::__construct($data);
    }

    /**
     * Immutable. Name of the resource.
     * Label resource names have the form:
     * `customers/{customer_id}/labels/{label_id}`
     *
     * Generated from protobuf field <code>string resource_name = 1 [(.google.api.field_behavior) = IMMUTABLE, (.google.api.resource_reference) = {</code>
     * @return string
     */
    public function getResourceName()
    {
        return $this->resource_name;
    }

    /**
     * Immutable. Name of the resource.
     * Label resource names have the form:
     * `customers/{customer_id}/labels/{label_id}`
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
     * Output only. Id of the label. Read only.
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

     * Output only. Id of the label. Read only.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value id = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return int|string|null
     */
    public function getIdUnwrapped()
    {
        return $this->readWrapperValue("id");
    }

    /**
     * Output only. Id of the label. Read only.
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

     * Output only. Id of the label. Read only.
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
     * The name of the label.
     * This field is required and should not be empty when creating a new label.
     * The length of this string should be between 1 and 80, inclusive.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue name = 3;</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the unboxed value from <code>getName()</code>

     * The name of the label.
     * This field is required and should not be empty when creating a new label.
     * The length of this string should be between 1 and 80, inclusive.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue name = 3;</code>
     * @return string|null
     */
    public function getNameUnwrapped()
    {
        return $this->readWrapperValue("name");
    }

    /**
     * The name of the label.
     * This field is required and should not be empty when creating a new label.
     * The length of this string should be between 1 and 80, inclusive.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue name = 3;</code>
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

     * The name of the label.
     * This field is required and should not be empty when creating a new label.
     * The length of this string should be between 1 and 80, inclusive.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue name = 3;</code>
     * @param string|null $var
     * @return $this
     */
    public function setNameUnwrapped($var)
    {
        $this->writeWrapperValue("name", $var);
        return $this;}

    /**
     * Output only. Status of the label. Read only.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v3.enums.LabelStatusEnum.LabelStatus status = 4 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Output only. Status of the label. Read only.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v3.enums.LabelStatusEnum.LabelStatus status = 4 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param int $var
     * @return $this
     */
    public function setStatus($var)
    {
        GPBUtil::checkEnum($var, \Google\Ads\GoogleAds\V3\Enums\LabelStatusEnum_LabelStatus::class);
        $this->status = $var;

        return $this;
    }

    /**
     * A type of label displaying text on a colored background.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v3.common.TextLabel text_label = 5;</code>
     * @return \Google\Ads\GoogleAds\V3\Common\TextLabel
     */
    public function getTextLabel()
    {
        return $this->text_label;
    }

    /**
     * A type of label displaying text on a colored background.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v3.common.TextLabel text_label = 5;</code>
     * @param \Google\Ads\GoogleAds\V3\Common\TextLabel $var
     * @return $this
     */
    public function setTextLabel($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V3\Common\TextLabel::class);
        $this->text_label = $var;

        return $this;
    }

}

