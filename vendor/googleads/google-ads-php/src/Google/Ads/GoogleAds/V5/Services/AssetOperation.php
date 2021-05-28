<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/services/asset_service.proto

namespace Google\Ads\GoogleAds\V5\Services;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * A single operation to create an asset. Supported asset types are
 * YoutubeVideoAsset, MediaBundleAsset, ImageAsset, and LeadFormAsset. TextAsset
 * should be created with Ad inline.
 *
 * Generated from protobuf message <code>google.ads.googleads.v5.services.AssetOperation</code>
 */
class AssetOperation extends \Google\Protobuf\Internal\Message
{
    protected $operation;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Ads\GoogleAds\V5\Resources\Asset $create
     *           Create operation: No resource name is expected for the new asset.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V5\Services\AssetService::initOnce();
        parent::__construct($data);
    }

    /**
     * Create operation: No resource name is expected for the new asset.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.resources.Asset create = 1;</code>
     * @return \Google\Ads\GoogleAds\V5\Resources\Asset
     */
    public function getCreate()
    {
        return $this->readOneof(1);
    }

    public function hasCreate()
    {
        return $this->hasOneof(1);
    }

    /**
     * Create operation: No resource name is expected for the new asset.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.resources.Asset create = 1;</code>
     * @param \Google\Ads\GoogleAds\V5\Resources\Asset $var
     * @return $this
     */
    public function setCreate($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V5\Resources\Asset::class);
        $this->writeOneof(1, $var);

        return $this;
    }

    /**
     * @return string
     */
    public function getOperation()
    {
        return $this->whichOneof("operation");
    }

}

