<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/analytics/data/v1beta/data.proto

namespace Google\Analytics\Data\V1beta;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * The value of a dimension.
 *
 * Generated from protobuf message <code>google.analytics.data.v1beta.DimensionValue</code>
 */
class DimensionValue extends \Google\Protobuf\Internal\Message
{
    protected $one_value;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $value
     *           Value as a string if the dimension type is a string.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Analytics\Data\V1Beta\Data::initOnce();
        parent::__construct($data);
    }

    /**
     * Value as a string if the dimension type is a string.
     *
     * Generated from protobuf field <code>string value = 1;</code>
     * @return string
     */
    public function getValue()
    {
        return $this->readOneof(1);
    }

    public function hasValue()
    {
        return $this->hasOneof(1);
    }

    /**
     * Value as a string if the dimension type is a string.
     *
     * Generated from protobuf field <code>string value = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setValue($var)
    {
        GPBUtil::checkString($var, True);
        $this->writeOneof(1, $var);

        return $this;
    }

    /**
     * @return string
     */
    public function getOneValue()
    {
        return $this->whichOneof("one_value");
    }

}

