<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/analytics/data/v1alpha/data.proto

namespace Google\Analytics\Data\V1alpha;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Used to express a dimension which is the result of a formula of multiple
 * dimensions. Example usages:
 * 1) lower_case(dimension)
 * 2) concatenate(dimension1, symbol, dimension2).
 *
 * Generated from protobuf message <code>google.analytics.data.v1alpha.DimensionExpression</code>
 */
class DimensionExpression extends \Google\Protobuf\Internal\Message
{
    protected $one_expression;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Analytics\Data\V1alpha\DimensionExpression\CaseExpression $lower_case
     *           Used to convert a dimension value to lower case.
     *     @type \Google\Analytics\Data\V1alpha\DimensionExpression\CaseExpression $upper_case
     *           Used to convert a dimension value to upper case.
     *     @type \Google\Analytics\Data\V1alpha\DimensionExpression\ConcatenateExpression $concatenate
     *           Used to combine dimension values to a single dimension.
     *           For example, dimension "country, city": concatenate(country, ", ", city).
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Analytics\Data\V1Alpha\Data::initOnce();
        parent::__construct($data);
    }

    /**
     * Used to convert a dimension value to lower case.
     *
     * Generated from protobuf field <code>.google.analytics.data.v1alpha.DimensionExpression.CaseExpression lower_case = 4;</code>
     * @return \Google\Analytics\Data\V1alpha\DimensionExpression\CaseExpression|null
     */
    public function getLowerCase()
    {
        return $this->readOneof(4);
    }

    public function hasLowerCase()
    {
        return $this->hasOneof(4);
    }

    /**
     * Used to convert a dimension value to lower case.
     *
     * Generated from protobuf field <code>.google.analytics.data.v1alpha.DimensionExpression.CaseExpression lower_case = 4;</code>
     * @param \Google\Analytics\Data\V1alpha\DimensionExpression\CaseExpression $var
     * @return $this
     */
    public function setLowerCase($var)
    {
        GPBUtil::checkMessage($var, \Google\Analytics\Data\V1alpha\DimensionExpression\CaseExpression::class);
        $this->writeOneof(4, $var);

        return $this;
    }

    /**
     * Used to convert a dimension value to upper case.
     *
     * Generated from protobuf field <code>.google.analytics.data.v1alpha.DimensionExpression.CaseExpression upper_case = 5;</code>
     * @return \Google\Analytics\Data\V1alpha\DimensionExpression\CaseExpression|null
     */
    public function getUpperCase()
    {
        return $this->readOneof(5);
    }

    public function hasUpperCase()
    {
        return $this->hasOneof(5);
    }

    /**
     * Used to convert a dimension value to upper case.
     *
     * Generated from protobuf field <code>.google.analytics.data.v1alpha.DimensionExpression.CaseExpression upper_case = 5;</code>
     * @param \Google\Analytics\Data\V1alpha\DimensionExpression\CaseExpression $var
     * @return $this
     */
    public function setUpperCase($var)
    {
        GPBUtil::checkMessage($var, \Google\Analytics\Data\V1alpha\DimensionExpression\CaseExpression::class);
        $this->writeOneof(5, $var);

        return $this;
    }

    /**
     * Used to combine dimension values to a single dimension.
     * For example, dimension "country, city": concatenate(country, ", ", city).
     *
     * Generated from protobuf field <code>.google.analytics.data.v1alpha.DimensionExpression.ConcatenateExpression concatenate = 6;</code>
     * @return \Google\Analytics\Data\V1alpha\DimensionExpression\ConcatenateExpression|null
     */
    public function getConcatenate()
    {
        return $this->readOneof(6);
    }

    public function hasConcatenate()
    {
        return $this->hasOneof(6);
    }

    /**
     * Used to combine dimension values to a single dimension.
     * For example, dimension "country, city": concatenate(country, ", ", city).
     *
     * Generated from protobuf field <code>.google.analytics.data.v1alpha.DimensionExpression.ConcatenateExpression concatenate = 6;</code>
     * @param \Google\Analytics\Data\V1alpha\DimensionExpression\ConcatenateExpression $var
     * @return $this
     */
    public function setConcatenate($var)
    {
        GPBUtil::checkMessage($var, \Google\Analytics\Data\V1alpha\DimensionExpression\ConcatenateExpression::class);
        $this->writeOneof(6, $var);

        return $this;
    }

    /**
     * @return string
     */
    public function getOneExpression()
    {
        return $this->whichOneof("one_expression");
    }

}

