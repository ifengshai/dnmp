<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/common/extensions.proto

namespace Google\Ads\GoogleAds\V5\Common;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Represents a structured snippet extension.
 *
 * Generated from protobuf message <code>google.ads.googleads.v5.common.StructuredSnippetFeedItem</code>
 */
class StructuredSnippetFeedItem extends \Google\Protobuf\Internal\Message
{
    /**
     * The header of the snippet.
     * This string must not be empty.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue header = 1;</code>
     */
    protected $header = null;
    /**
     * The values in the snippet.
     * The maximum size of this collection is 10.
     *
     * Generated from protobuf field <code>repeated .google.protobuf.StringValue values = 2;</code>
     */
    private $values;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Protobuf\StringValue $header
     *           The header of the snippet.
     *           This string must not be empty.
     *     @type \Google\Protobuf\StringValue[]|\Google\Protobuf\Internal\RepeatedField $values
     *           The values in the snippet.
     *           The maximum size of this collection is 10.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V5\Common\Extensions::initOnce();
        parent::__construct($data);
    }

    /**
     * The header of the snippet.
     * This string must not be empty.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue header = 1;</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getHeader()
    {
        return isset($this->header) ? $this->header : null;
    }

    public function hasHeader()
    {
        return isset($this->header);
    }

    public function clearHeader()
    {
        unset($this->header);
    }

    /**
     * Returns the unboxed value from <code>getHeader()</code>

     * The header of the snippet.
     * This string must not be empty.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue header = 1;</code>
     * @return string|null
     */
    public function getHeaderUnwrapped()
    {
        return $this->readWrapperValue("header");
    }

    /**
     * The header of the snippet.
     * This string must not be empty.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue header = 1;</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setHeader($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->header = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * The header of the snippet.
     * This string must not be empty.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue header = 1;</code>
     * @param string|null $var
     * @return $this
     */
    public function setHeaderUnwrapped($var)
    {
        $this->writeWrapperValue("header", $var);
        return $this;}

    /**
     * The values in the snippet.
     * The maximum size of this collection is 10.
     *
     * Generated from protobuf field <code>repeated .google.protobuf.StringValue values = 2;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * The values in the snippet.
     * The maximum size of this collection is 10.
     *
     * Generated from protobuf field <code>repeated .google.protobuf.StringValue values = 2;</code>
     * @param \Google\Protobuf\StringValue[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setValues($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Protobuf\StringValue::class);
        $this->values = $arr;

        return $this;
    }

}

