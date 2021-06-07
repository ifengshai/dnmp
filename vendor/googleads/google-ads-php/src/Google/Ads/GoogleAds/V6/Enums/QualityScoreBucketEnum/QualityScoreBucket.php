<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v6/enums/quality_score_bucket.proto

namespace Google\Ads\GoogleAds\V6\Enums\QualityScoreBucketEnum;

use UnexpectedValueException;

/**
 * Enum listing the possible quality score buckets.
 *
 * Protobuf type <code>google.ads.googleads.v6.enums.QualityScoreBucketEnum.QualityScoreBucket</code>
 */
class QualityScoreBucket
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
     * Quality of the creative is below average.
     *
     * Generated from protobuf enum <code>BELOW_AVERAGE = 2;</code>
     */
    const BELOW_AVERAGE = 2;
    /**
     * Quality of the creative is average.
     *
     * Generated from protobuf enum <code>AVERAGE = 3;</code>
     */
    const AVERAGE = 3;
    /**
     * Quality of the creative is above average.
     *
     * Generated from protobuf enum <code>ABOVE_AVERAGE = 4;</code>
     */
    const ABOVE_AVERAGE = 4;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::BELOW_AVERAGE => 'BELOW_AVERAGE',
        self::AVERAGE => 'AVERAGE',
        self::ABOVE_AVERAGE => 'ABOVE_AVERAGE',
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
class_alias(QualityScoreBucket::class, \Google\Ads\GoogleAds\V6\Enums\QualityScoreBucketEnum_QualityScoreBucket::class);

