<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v6/services/media_file_service.proto

namespace Google\Ads\GoogleAds\V6\Services;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * The result for the media file mutate.
 *
 * Generated from protobuf message <code>google.ads.googleads.v6.services.MutateMediaFileResult</code>
 */
class MutateMediaFileResult extends \Google\Protobuf\Internal\Message
{
    /**
     * The resource name returned for successful operations.
     *
     * Generated from protobuf field <code>string resource_name = 1;</code>
     */
    protected $resource_name = '';
    /**
     * The mutated media file with only mutable fields after mutate. The field
     * will only be returned when response_content_type is set to
     * "MUTABLE_RESOURCE".
     *
     * Generated from protobuf field <code>.google.ads.googleads.v6.resources.MediaFile media_file = 2;</code>
     */
    protected $media_file = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $resource_name
     *           The resource name returned for successful operations.
     *     @type \Google\Ads\GoogleAds\V6\Resources\MediaFile $media_file
     *           The mutated media file with only mutable fields after mutate. The field
     *           will only be returned when response_content_type is set to
     *           "MUTABLE_RESOURCE".
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V6\Services\MediaFileService::initOnce();
        parent::__construct($data);
    }

    /**
     * The resource name returned for successful operations.
     *
     * Generated from protobuf field <code>string resource_name = 1;</code>
     * @return string
     */
    public function getResourceName()
    {
        return $this->resource_name;
    }

    /**
     * The resource name returned for successful operations.
     *
     * Generated from protobuf field <code>string resource_name = 1;</code>
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
     * The mutated media file with only mutable fields after mutate. The field
     * will only be returned when response_content_type is set to
     * "MUTABLE_RESOURCE".
     *
     * Generated from protobuf field <code>.google.ads.googleads.v6.resources.MediaFile media_file = 2;</code>
     * @return \Google\Ads\GoogleAds\V6\Resources\MediaFile
     */
    public function getMediaFile()
    {
        return isset($this->media_file) ? $this->media_file : null;
    }

    public function hasMediaFile()
    {
        return isset($this->media_file);
    }

    public function clearMediaFile()
    {
        unset($this->media_file);
    }

    /**
     * The mutated media file with only mutable fields after mutate. The field
     * will only be returned when response_content_type is set to
     * "MUTABLE_RESOURCE".
     *
     * Generated from protobuf field <code>.google.ads.googleads.v6.resources.MediaFile media_file = 2;</code>
     * @param \Google\Ads\GoogleAds\V6\Resources\MediaFile $var
     * @return $this
     */
    public function setMediaFile($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V6\Resources\MediaFile::class);
        $this->media_file = $var;

        return $this;
    }

}

