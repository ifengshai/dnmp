<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/enums/policy_topic_evidence_destination_not_working_device.proto

namespace Google\Ads\GoogleAds\V3\Enums\PolicyTopicEvidenceDestinationNotWorkingDeviceEnum;

use UnexpectedValueException;

/**
 * The possible policy topic evidence destination not working devices.
 *
 * Protobuf type <code>google.ads.googleads.v3.enums.PolicyTopicEvidenceDestinationNotWorkingDeviceEnum.PolicyTopicEvidenceDestinationNotWorkingDevice</code>
 */
class PolicyTopicEvidenceDestinationNotWorkingDevice
{
    /**
     * No value has been specified.
     *
     * Generated from protobuf enum <code>UNSPECIFIED = 0;</code>
     */
    const UNSPECIFIED = 0;
    /**
     * The received value is not known in this version.
     * This is a response-only value.
     *
     * Generated from protobuf enum <code>UNKNOWN = 1;</code>
     */
    const UNKNOWN = 1;
    /**
     * Landing page doesn't work on desktop device.
     *
     * Generated from protobuf enum <code>DESKTOP = 2;</code>
     */
    const DESKTOP = 2;
    /**
     * Landing page doesn't work on Android device.
     *
     * Generated from protobuf enum <code>ANDROID = 3;</code>
     */
    const ANDROID = 3;
    /**
     * Landing page doesn't work on iOS device.
     *
     * Generated from protobuf enum <code>IOS = 4;</code>
     */
    const IOS = 4;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::DESKTOP => 'DESKTOP',
        self::ANDROID => 'ANDROID',
        self::IOS => 'IOS',
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
class_alias(PolicyTopicEvidenceDestinationNotWorkingDevice::class, \Google\Ads\GoogleAds\V3\Enums\PolicyTopicEvidenceDestinationNotWorkingDeviceEnum_PolicyTopicEvidenceDestinationNotWorkingDevice::class);

