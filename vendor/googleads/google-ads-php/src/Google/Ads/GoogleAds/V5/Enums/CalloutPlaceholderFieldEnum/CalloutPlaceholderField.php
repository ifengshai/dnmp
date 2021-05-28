<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/enums/callout_placeholder_field.proto

namespace Google\Ads\GoogleAds\V5\Enums\CalloutPlaceholderFieldEnum;

use UnexpectedValueException;

/**
 * Possible values for Callout placeholder fields.
 *
 * Protobuf type <code>google.ads.googleads.v5.enums.CalloutPlaceholderFieldEnum.CalloutPlaceholderField</code>
 */
class CalloutPlaceholderField
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
     * Data Type: STRING. Callout text.
     *
     * Generated from protobuf enum <code>CALLOUT_TEXT = 2;</code>
     */
    const CALLOUT_TEXT = 2;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::CALLOUT_TEXT => 'CALLOUT_TEXT',
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
class_alias(CalloutPlaceholderField::class, \Google\Ads\GoogleAds\V5\Enums\CalloutPlaceholderFieldEnum_CalloutPlaceholderField::class);

