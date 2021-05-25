<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v6/errors/ad_sharing_error.proto

namespace Google\Ads\GoogleAds\V6\Errors\AdSharingErrorEnum;

use UnexpectedValueException;

/**
 * Enum describing possible ad sharing errors.
 *
 * Protobuf type <code>google.ads.googleads.v6.errors.AdSharingErrorEnum.AdSharingError</code>
 */
class AdSharingError
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
     * Error resulting in attempting to add an Ad to an AdGroup that already
     * contains the Ad.
     *
     * Generated from protobuf enum <code>AD_GROUP_ALREADY_CONTAINS_AD = 2;</code>
     */
    const AD_GROUP_ALREADY_CONTAINS_AD = 2;
    /**
     * Ad is not compatible with the AdGroup it is being shared with.
     *
     * Generated from protobuf enum <code>INCOMPATIBLE_AD_UNDER_AD_GROUP = 3;</code>
     */
    const INCOMPATIBLE_AD_UNDER_AD_GROUP = 3;
    /**
     * Cannot add AdGroupAd on inactive Ad.
     *
     * Generated from protobuf enum <code>CANNOT_SHARE_INACTIVE_AD = 4;</code>
     */
    const CANNOT_SHARE_INACTIVE_AD = 4;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::AD_GROUP_ALREADY_CONTAINS_AD => 'AD_GROUP_ALREADY_CONTAINS_AD',
        self::INCOMPATIBLE_AD_UNDER_AD_GROUP => 'INCOMPATIBLE_AD_UNDER_AD_GROUP',
        self::CANNOT_SHARE_INACTIVE_AD => 'CANNOT_SHARE_INACTIVE_AD',
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
class_alias(AdSharingError::class, \Google\Ads\GoogleAds\V6\Errors\AdSharingErrorEnum_AdSharingError::class);

