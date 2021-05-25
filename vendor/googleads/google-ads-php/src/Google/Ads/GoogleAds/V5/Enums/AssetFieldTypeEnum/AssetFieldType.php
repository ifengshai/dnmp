<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/enums/asset_field_type.proto

namespace Google\Ads\GoogleAds\V5\Enums\AssetFieldTypeEnum;

use UnexpectedValueException;

/**
 * Enum describing the possible placements of an asset.
 *
 * Protobuf type <code>google.ads.googleads.v5.enums.AssetFieldTypeEnum.AssetFieldType</code>
 */
class AssetFieldType
{
    /**
     * Not specified.
     *
     * Generated from protobuf enum <code>UNSPECIFIED = 0;</code>
     */
    const UNSPECIFIED = 0;
    /**
     * Used for return value only. Represents value unknown in this version.
     *
     * Generated from protobuf enum <code>UNKNOWN = 1;</code>
     */
    const UNKNOWN = 1;
    /**
     * The asset is linked for use as a headline.
     *
     * Generated from protobuf enum <code>HEADLINE = 2;</code>
     */
    const HEADLINE = 2;
    /**
     * The asset is linked for use as a description.
     *
     * Generated from protobuf enum <code>DESCRIPTION = 3;</code>
     */
    const DESCRIPTION = 3;
    /**
     * The asset is linked for use as mandatory ad text.
     *
     * Generated from protobuf enum <code>MANDATORY_AD_TEXT = 4;</code>
     */
    const MANDATORY_AD_TEXT = 4;
    /**
     * The asset is linked for use as a marketing image.
     *
     * Generated from protobuf enum <code>MARKETING_IMAGE = 5;</code>
     */
    const MARKETING_IMAGE = 5;
    /**
     * The asset is linked for use as a media bundle.
     *
     * Generated from protobuf enum <code>MEDIA_BUNDLE = 6;</code>
     */
    const MEDIA_BUNDLE = 6;
    /**
     * The asset is linked for use as a YouTube video.
     *
     * Generated from protobuf enum <code>YOUTUBE_VIDEO = 7;</code>
     */
    const YOUTUBE_VIDEO = 7;
    /**
     * The asset is linked to indicate that a hotels campaign is "Book on
     * Google" enabled.
     *
     * Generated from protobuf enum <code>BOOK_ON_GOOGLE = 8;</code>
     */
    const BOOK_ON_GOOGLE = 8;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::HEADLINE => 'HEADLINE',
        self::DESCRIPTION => 'DESCRIPTION',
        self::MANDATORY_AD_TEXT => 'MANDATORY_AD_TEXT',
        self::MARKETING_IMAGE => 'MARKETING_IMAGE',
        self::MEDIA_BUNDLE => 'MEDIA_BUNDLE',
        self::YOUTUBE_VIDEO => 'YOUTUBE_VIDEO',
        self::BOOK_ON_GOOGLE => 'BOOK_ON_GOOGLE',
    ];

    public static function name($value)
    {
        if (!isset(self::$valueToName[$value])) {
            throw new UnexpectedValueException(sprintf(
                    'Enum %s has no name defined for value %s', __CLASS__, $value));
        }
        return self::$valueToName[$value];
    }


    public static function value($name)
    {
        $const = __CLASS__ . '::' . strtoupper($name);
        if (!defined($const)) {
            throw new UnexpectedValueException(sprintf(
                    'Enum %s has no value defined for name %s', __CLASS__, $name));
        }
        return constant($const);
    }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(AssetFieldType::class, \Google\Ads\GoogleAds\V5\Enums\AssetFieldTypeEnum_AssetFieldType::class);

