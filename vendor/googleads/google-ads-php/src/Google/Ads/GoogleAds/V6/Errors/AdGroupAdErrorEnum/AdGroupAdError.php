<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v6/errors/ad_group_ad_error.proto

namespace Google\Ads\GoogleAds\V6\Errors\AdGroupAdErrorEnum;

use UnexpectedValueException;

/**
 * Enum describing possible ad group ad errors.
 *
 * Protobuf type <code>google.ads.googleads.v6.errors.AdGroupAdErrorEnum.AdGroupAdError</code>
 */
class AdGroupAdError
{
    /**
     * Enum unspecified.
     *
     * Generated from protobuf enum <code>UNSPECIFIED = 0;</code>
     */
    const UNSPECIFIED = 0;
    /**
     * The received error code is not known in this version.
     *
     * Generated from protobuf enum <code>UNKNOWN = 1;</code>
     */
    const UNKNOWN = 1;
    /**
     * No link found between the adgroup ad and the label.
     *
     * Generated from protobuf enum <code>AD_GROUP_AD_LABEL_DOES_NOT_EXIST = 2;</code>
     */
    const AD_GROUP_AD_LABEL_DOES_NOT_EXIST = 2;
    /**
     * The label has already been attached to the adgroup ad.
     *
     * Generated from protobuf enum <code>AD_GROUP_AD_LABEL_ALREADY_EXISTS = 3;</code>
     */
    const AD_GROUP_AD_LABEL_ALREADY_EXISTS = 3;
    /**
     * The specified ad was not found in the adgroup
     *
     * Generated from protobuf enum <code>AD_NOT_UNDER_ADGROUP = 4;</code>
     */
    const AD_NOT_UNDER_ADGROUP = 4;
    /**
     * Removed ads may not be modified
     *
     * Generated from protobuf enum <code>CANNOT_OPERATE_ON_REMOVED_ADGROUPAD = 5;</code>
     */
    const CANNOT_OPERATE_ON_REMOVED_ADGROUPAD = 5;
    /**
     * An ad of this type is deprecated and cannot be created. Only deletions
     * are permitted.
     *
     * Generated from protobuf enum <code>CANNOT_CREATE_DEPRECATED_ADS = 6;</code>
     */
    const CANNOT_CREATE_DEPRECATED_ADS = 6;
    /**
     * Text ads are deprecated and cannot be created. Use expanded text ads
     * instead.
     *
     * Generated from protobuf enum <code>CANNOT_CREATE_TEXT_ADS = 7;</code>
     */
    const CANNOT_CREATE_TEXT_ADS = 7;
    /**
     * A required field was not specified or is an empty string.
     *
     * Generated from protobuf enum <code>EMPTY_FIELD = 8;</code>
     */
    const EMPTY_FIELD = 8;
    /**
     * An ad may only be modified once per call
     *
     * Generated from protobuf enum <code>RESOURCE_REFERENCED_IN_MULTIPLE_OPS = 9;</code>
     */
    const RESOURCE_REFERENCED_IN_MULTIPLE_OPS = 9;
    /**
     * AdGroupAds with the given ad type cannot be paused.
     *
     * Generated from protobuf enum <code>AD_TYPE_CANNOT_BE_PAUSED = 10;</code>
     */
    const AD_TYPE_CANNOT_BE_PAUSED = 10;
    /**
     * AdGroupAds with the given ad type cannot be removed.
     *
     * Generated from protobuf enum <code>AD_TYPE_CANNOT_BE_REMOVED = 11;</code>
     */
    const AD_TYPE_CANNOT_BE_REMOVED = 11;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::AD_GROUP_AD_LABEL_DOES_NOT_EXIST => 'AD_GROUP_AD_LABEL_DOES_NOT_EXIST',
        self::AD_GROUP_AD_LABEL_ALREADY_EXISTS => 'AD_GROUP_AD_LABEL_ALREADY_EXISTS',
        self::AD_NOT_UNDER_ADGROUP => 'AD_NOT_UNDER_ADGROUP',
        self::CANNOT_OPERATE_ON_REMOVED_ADGROUPAD => 'CANNOT_OPERATE_ON_REMOVED_ADGROUPAD',
        self::CANNOT_CREATE_DEPRECATED_ADS => 'CANNOT_CREATE_DEPRECATED_ADS',
        self::CANNOT_CREATE_TEXT_ADS => 'CANNOT_CREATE_TEXT_ADS',
        self::EMPTY_FIELD => 'EMPTY_FIELD',
        self::RESOURCE_REFERENCED_IN_MULTIPLE_OPS => 'RESOURCE_REFERENCED_IN_MULTIPLE_OPS',
        self::AD_TYPE_CANNOT_BE_PAUSED => 'AD_TYPE_CANNOT_BE_PAUSED',
        self::AD_TYPE_CANNOT_BE_REMOVED => 'AD_TYPE_CANNOT_BE_REMOVED',
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
class_alias(AdGroupAdError::class, \Google\Ads\GoogleAds\V6\Errors\AdGroupAdErrorEnum_AdGroupAdError::class);

