<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/common/asset_types.proto

namespace Google\Ads\GoogleAds\V4\Common;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * An Image asset.
 *
 * Generated from protobuf message <code>google.ads.googleads.v4.common.ImageAsset</code>
 */
class ImageAsset extends \Google\Protobuf\Internal\Message
{
    /**
     * The raw bytes data of an image. This field is mutate only.
     *
     * Generated from protobuf field <code>.google.protobuf.BytesValue data = 1;</code>
     */
    protected $data = null;
    /**
     * File size of the image asset in bytes.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value file_size = 2;</code>
     */
    protected $file_size = null;
    /**
     * MIME type of the image asset.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.enums.MimeTypeEnum.MimeType mime_type = 3;</code>
     */
    protected $mime_type = 0;
    /**
     * Metadata for this image at its original size.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.common.ImageDimension full_size = 4;</code>
     */
    protected $full_size = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Protobuf\BytesValue $data
     *           The raw bytes data of an image. This field is mutate only.
     *     @type \Google\Protobuf\Int64Value $file_size
     *           File size of the image asset in bytes.
     *     @type int $mime_type
     *           MIME type of the image asset.
     *     @type \Google\Ads\GoogleAds\V4\Common\ImageDimension $full_size
     *           Metadata for this image at its original size.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V4\Common\AssetTypes::initOnce();
        parent::__construct($data);
    }

    /**
     * The raw bytes data of an image. This field is mutate only.
     *
     * Generated from protobuf field <code>.google.protobuf.BytesValue data = 1;</code>
     * @return \Google\Protobuf\BytesValue
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Returns the unboxed value from <code>getData()</code>

     * The raw bytes data of an image. This field is mutate only.
     *
     * Generated from protobuf field <code>.google.protobuf.BytesValue data = 1;</code>
     * @return string|null
     */
    public function getDataUnwrapped()
    {
        return $this->readWrapperValue("data");
    }

    /**
     * The raw bytes data of an image. This field is mutate only.
     *
     * Generated from protobuf field <code>.google.protobuf.BytesValue data = 1;</code>
     * @param \Google\Protobuf\BytesValue $var
     * @return $this
     */
    public function setData($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\BytesValue::class);
        $this->data = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\BytesValue object.

     * The raw bytes data of an image. This field is mutate only.
     *
     * Generated from protobuf field <code>.google.protobuf.BytesValue data = 1;</code>
     * @param string|null $var
     * @return $this
     */
    public function setDataUnwrapped($var)
    {
        $this->writeWrapperValue("data", $var);
        return $this;}

    /**
     * File size of the image asset in bytes.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value file_size = 2;</code>
     * @return \Google\Protobuf\Int64Value
     */
    public function getFileSize()
    {
        return $this->file_size;
    }

    /**
     * Returns the unboxed value from <code>getFileSize()</code>

     * File size of the image asset in bytes.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value file_size = 2;</code>
     * @return int|string|null
     */
    public function getFileSizeUnwrapped()
    {
        return $this->readWrapperValue("file_size");
    }

    /**
     * File size of the image asset in bytes.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value file_size = 2;</code>
     * @param \Google\Protobuf\Int64Value $var
     * @return $this
     */
    public function setFileSize($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Int64Value::class);
        $this->file_size = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\Int64Value object.

     * File size of the image asset in bytes.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value file_size = 2;</code>
     * @param int|string|null $var
     * @return $this
     */
    public function setFileSizeUnwrapped($var)
    {
        $this->writeWrapperValue("file_size", $var);
        return $this;}

    /**
     * MIME type of the image asset.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.enums.MimeTypeEnum.MimeType mime_type = 3;</code>
     * @return int
     */
    public function getMimeType()
    {
        return $this->mime_type;
    }

    /**
     * MIME type of the image asset.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.enums.MimeTypeEnum.MimeType mime_type = 3;</code>
     * @param int $var
     * @return $this
     */
    public function setMimeType($var)
    {
        GPBUtil::checkEnum($var, \Google\Ads\GoogleAds\V4\Enums\MimeTypeEnum_MimeType::class);
        $this->mime_type = $var;

        return $this;
    }

    /**
     * Metadata for this image at its original size.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.common.ImageDimension full_size = 4;</code>
     * @return \Google\Ads\GoogleAds\V4\Common\ImageDimension
     */
    public function getFullSize()
    {
        return $this->full_size;
    }

    /**
     * Metadata for this image at its original size.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.common.ImageDimension full_size = 4;</code>
     * @param \Google\Ads\GoogleAds\V4\Common\ImageDimension $var
     * @return $this
     */
    public function setFullSize($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V4\Common\ImageDimension::class);
        $this->full_size = $var;

        return $this;
    }

}

