<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/enums/display_upload_product_type.proto

namespace Google\Ads\GoogleAds\V4\Enums;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Container for display upload product types. Product types that have the word
 * "DYNAMIC" in them must be associated with a campaign that has a dynamic
 * remarketing feed. See https://support.google.com/google-ads/answer/6053288
 * for more info about dynamic remarketing. Other product types are regarded
 * as "static" and do not have this requirement.
 *
 * Generated from protobuf message <code>google.ads.googleads.v4.enums.DisplayUploadProductTypeEnum</code>
 */
class DisplayUploadProductTypeEnum extends \Google\Protobuf\Internal\Message
{

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V4\Enums\DisplayUploadProductType::initOnce();
        parent::__construct($data);
    }

}

