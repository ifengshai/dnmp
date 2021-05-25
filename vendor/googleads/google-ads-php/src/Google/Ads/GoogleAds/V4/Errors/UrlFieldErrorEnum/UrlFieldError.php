<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/errors/url_field_error.proto

namespace Google\Ads\GoogleAds\V4\Errors\UrlFieldErrorEnum;

use UnexpectedValueException;

/**
 * Enum describing possible url field errors.
 *
 * Protobuf type <code>google.ads.googleads.v4.errors.UrlFieldErrorEnum.UrlFieldError</code>
 */
class UrlFieldError
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
     * The tracking url template is invalid.
     *
     * Generated from protobuf enum <code>INVALID_TRACKING_URL_TEMPLATE = 2;</code>
     */
    const INVALID_TRACKING_URL_TEMPLATE = 2;
    /**
     * The tracking url template contains invalid tag.
     *
     * Generated from protobuf enum <code>INVALID_TAG_IN_TRACKING_URL_TEMPLATE = 3;</code>
     */
    const INVALID_TAG_IN_TRACKING_URL_TEMPLATE = 3;
    /**
     * The tracking url template must contain at least one tag (e.g. {lpurl}),
     * This applies only to tracking url template associated with website ads or
     * product ads.
     *
     * Generated from protobuf enum <code>MISSING_TRACKING_URL_TEMPLATE_TAG = 4;</code>
     */
    const MISSING_TRACKING_URL_TEMPLATE_TAG = 4;
    /**
     * The tracking url template must start with a valid protocol (or lpurl
     * tag).
     *
     * Generated from protobuf enum <code>MISSING_PROTOCOL_IN_TRACKING_URL_TEMPLATE = 5;</code>
     */
    const MISSING_PROTOCOL_IN_TRACKING_URL_TEMPLATE = 5;
    /**
     * The tracking url template starts with an invalid protocol.
     *
     * Generated from protobuf enum <code>INVALID_PROTOCOL_IN_TRACKING_URL_TEMPLATE = 6;</code>
     */
    const INVALID_PROTOCOL_IN_TRACKING_URL_TEMPLATE = 6;
    /**
     * The tracking url template contains illegal characters.
     *
     * Generated from protobuf enum <code>MALFORMED_TRACKING_URL_TEMPLATE = 7;</code>
     */
    const MALFORMED_TRACKING_URL_TEMPLATE = 7;
    /**
     * The tracking url template must contain a host name (or lpurl tag).
     *
     * Generated from protobuf enum <code>MISSING_HOST_IN_TRACKING_URL_TEMPLATE = 8;</code>
     */
    const MISSING_HOST_IN_TRACKING_URL_TEMPLATE = 8;
    /**
     * The tracking url template has an invalid or missing top level domain
     * extension.
     *
     * Generated from protobuf enum <code>INVALID_TLD_IN_TRACKING_URL_TEMPLATE = 9;</code>
     */
    const INVALID_TLD_IN_TRACKING_URL_TEMPLATE = 9;
    /**
     * The tracking url template contains nested occurrences of the same
     * conditional tag (i.e. {ifmobile:{ifmobile:x}}).
     *
     * Generated from protobuf enum <code>REDUNDANT_NESTED_TRACKING_URL_TEMPLATE_TAG = 10;</code>
     */
    const REDUNDANT_NESTED_TRACKING_URL_TEMPLATE_TAG = 10;
    /**
     * The final url is invalid.
     *
     * Generated from protobuf enum <code>INVALID_FINAL_URL = 11;</code>
     */
    const INVALID_FINAL_URL = 11;
    /**
     * The final url contains invalid tag.
     *
     * Generated from protobuf enum <code>INVALID_TAG_IN_FINAL_URL = 12;</code>
     */
    const INVALID_TAG_IN_FINAL_URL = 12;
    /**
     * The final url contains nested occurrences of the same conditional tag
     * (i.e. {ifmobile:{ifmobile:x}}).
     *
     * Generated from protobuf enum <code>REDUNDANT_NESTED_FINAL_URL_TAG = 13;</code>
     */
    const REDUNDANT_NESTED_FINAL_URL_TAG = 13;
    /**
     * The final url must start with a valid protocol.
     *
     * Generated from protobuf enum <code>MISSING_PROTOCOL_IN_FINAL_URL = 14;</code>
     */
    const MISSING_PROTOCOL_IN_FINAL_URL = 14;
    /**
     * The final url starts with an invalid protocol.
     *
     * Generated from protobuf enum <code>INVALID_PROTOCOL_IN_FINAL_URL = 15;</code>
     */
    const INVALID_PROTOCOL_IN_FINAL_URL = 15;
    /**
     * The final url contains illegal characters.
     *
     * Generated from protobuf enum <code>MALFORMED_FINAL_URL = 16;</code>
     */
    const MALFORMED_FINAL_URL = 16;
    /**
     * The final url must contain a host name.
     *
     * Generated from protobuf enum <code>MISSING_HOST_IN_FINAL_URL = 17;</code>
     */
    const MISSING_HOST_IN_FINAL_URL = 17;
    /**
     * The tracking url template has an invalid or missing top level domain
     * extension.
     *
     * Generated from protobuf enum <code>INVALID_TLD_IN_FINAL_URL = 18;</code>
     */
    const INVALID_TLD_IN_FINAL_URL = 18;
    /**
     * The final mobile url is invalid.
     *
     * Generated from protobuf enum <code>INVALID_FINAL_MOBILE_URL = 19;</code>
     */
    const INVALID_FINAL_MOBILE_URL = 19;
    /**
     * The final mobile url contains invalid tag.
     *
     * Generated from protobuf enum <code>INVALID_TAG_IN_FINAL_MOBILE_URL = 20;</code>
     */
    const INVALID_TAG_IN_FINAL_MOBILE_URL = 20;
    /**
     * The final mobile url contains nested occurrences of the same conditional
     * tag (i.e. {ifmobile:{ifmobile:x}}).
     *
     * Generated from protobuf enum <code>REDUNDANT_NESTED_FINAL_MOBILE_URL_TAG = 21;</code>
     */
    const REDUNDANT_NESTED_FINAL_MOBILE_URL_TAG = 21;
    /**
     * The final mobile url must start with a valid protocol.
     *
     * Generated from protobuf enum <code>MISSING_PROTOCOL_IN_FINAL_MOBILE_URL = 22;</code>
     */
    const MISSING_PROTOCOL_IN_FINAL_MOBILE_URL = 22;
    /**
     * The final mobile url starts with an invalid protocol.
     *
     * Generated from protobuf enum <code>INVALID_PROTOCOL_IN_FINAL_MOBILE_URL = 23;</code>
     */
    const INVALID_PROTOCOL_IN_FINAL_MOBILE_URL = 23;
    /**
     * The final mobile url contains illegal characters.
     *
     * Generated from protobuf enum <code>MALFORMED_FINAL_MOBILE_URL = 24;</code>
     */
    const MALFORMED_FINAL_MOBILE_URL = 24;
    /**
     * The final mobile url must contain a host name.
     *
     * Generated from protobuf enum <code>MISSING_HOST_IN_FINAL_MOBILE_URL = 25;</code>
     */
    const MISSING_HOST_IN_FINAL_MOBILE_URL = 25;
    /**
     * The tracking url template has an invalid or missing top level domain
     * extension.
     *
     * Generated from protobuf enum <code>INVALID_TLD_IN_FINAL_MOBILE_URL = 26;</code>
     */
    const INVALID_TLD_IN_FINAL_MOBILE_URL = 26;
    /**
     * The final app url is invalid.
     *
     * Generated from protobuf enum <code>INVALID_FINAL_APP_URL = 27;</code>
     */
    const INVALID_FINAL_APP_URL = 27;
    /**
     * The final app url contains invalid tag.
     *
     * Generated from protobuf enum <code>INVALID_TAG_IN_FINAL_APP_URL = 28;</code>
     */
    const INVALID_TAG_IN_FINAL_APP_URL = 28;
    /**
     * The final app url contains nested occurrences of the same conditional tag
     * (i.e. {ifmobile:{ifmobile:x}}).
     *
     * Generated from protobuf enum <code>REDUNDANT_NESTED_FINAL_APP_URL_TAG = 29;</code>
     */
    const REDUNDANT_NESTED_FINAL_APP_URL_TAG = 29;
    /**
     * More than one app url found for the same OS type.
     *
     * Generated from protobuf enum <code>MULTIPLE_APP_URLS_FOR_OSTYPE = 30;</code>
     */
    const MULTIPLE_APP_URLS_FOR_OSTYPE = 30;
    /**
     * The OS type given for an app url is not valid.
     *
     * Generated from protobuf enum <code>INVALID_OSTYPE = 31;</code>
     */
    const INVALID_OSTYPE = 31;
    /**
     * The protocol given for an app url is not valid. (E.g. "android-app://")
     *
     * Generated from protobuf enum <code>INVALID_PROTOCOL_FOR_APP_URL = 32;</code>
     */
    const INVALID_PROTOCOL_FOR_APP_URL = 32;
    /**
     * The package id (app id) given for an app url is not valid.
     *
     * Generated from protobuf enum <code>INVALID_PACKAGE_ID_FOR_APP_URL = 33;</code>
     */
    const INVALID_PACKAGE_ID_FOR_APP_URL = 33;
    /**
     * The number of url custom parameters for an resource exceeds the maximum
     * limit allowed.
     *
     * Generated from protobuf enum <code>URL_CUSTOM_PARAMETERS_COUNT_EXCEEDS_LIMIT = 34;</code>
     */
    const URL_CUSTOM_PARAMETERS_COUNT_EXCEEDS_LIMIT = 34;
    /**
     * An invalid character appears in the parameter key.
     *
     * Generated from protobuf enum <code>INVALID_CHARACTERS_IN_URL_CUSTOM_PARAMETER_KEY = 39;</code>
     */
    const INVALID_CHARACTERS_IN_URL_CUSTOM_PARAMETER_KEY = 39;
    /**
     * An invalid character appears in the parameter value.
     *
     * Generated from protobuf enum <code>INVALID_CHARACTERS_IN_URL_CUSTOM_PARAMETER_VALUE = 40;</code>
     */
    const INVALID_CHARACTERS_IN_URL_CUSTOM_PARAMETER_VALUE = 40;
    /**
     * The url custom parameter value fails url tag validation.
     *
     * Generated from protobuf enum <code>INVALID_TAG_IN_URL_CUSTOM_PARAMETER_VALUE = 41;</code>
     */
    const INVALID_TAG_IN_URL_CUSTOM_PARAMETER_VALUE = 41;
    /**
     * The custom parameter contains nested occurrences of the same conditional
     * tag (i.e. {ifmobile:{ifmobile:x}}).
     *
     * Generated from protobuf enum <code>REDUNDANT_NESTED_URL_CUSTOM_PARAMETER_TAG = 42;</code>
     */
    const REDUNDANT_NESTED_URL_CUSTOM_PARAMETER_TAG = 42;
    /**
     * The protocol (http:// or https://) is missing.
     *
     * Generated from protobuf enum <code>MISSING_PROTOCOL = 43;</code>
     */
    const MISSING_PROTOCOL = 43;
    /**
     * Unsupported protocol in URL. Only http and https are supported.
     *
     * Generated from protobuf enum <code>INVALID_PROTOCOL = 52;</code>
     */
    const INVALID_PROTOCOL = 52;
    /**
     * The url is invalid.
     *
     * Generated from protobuf enum <code>INVALID_URL = 44;</code>
     */
    const INVALID_URL = 44;
    /**
     * Destination Url is deprecated.
     *
     * Generated from protobuf enum <code>DESTINATION_URL_DEPRECATED = 45;</code>
     */
    const DESTINATION_URL_DEPRECATED = 45;
    /**
     * The url contains invalid tag.
     *
     * Generated from protobuf enum <code>INVALID_TAG_IN_URL = 46;</code>
     */
    const INVALID_TAG_IN_URL = 46;
    /**
     * The url must contain at least one tag (e.g. {lpurl}), This applies only
     * to urls associated with website ads or product ads.
     *
     * Generated from protobuf enum <code>MISSING_URL_TAG = 47;</code>
     */
    const MISSING_URL_TAG = 47;
    /**
     * Duplicate url id.
     *
     * Generated from protobuf enum <code>DUPLICATE_URL_ID = 48;</code>
     */
    const DUPLICATE_URL_ID = 48;
    /**
     * Invalid url id.
     *
     * Generated from protobuf enum <code>INVALID_URL_ID = 49;</code>
     */
    const INVALID_URL_ID = 49;
    /**
     * The final url suffix cannot begin with '?' or '&' characters and must be
     * a valid query string.
     *
     * Generated from protobuf enum <code>FINAL_URL_SUFFIX_MALFORMED = 50;</code>
     */
    const FINAL_URL_SUFFIX_MALFORMED = 50;
    /**
     * The final url suffix cannot contain {lpurl} related or {ignore} tags.
     *
     * Generated from protobuf enum <code>INVALID_TAG_IN_FINAL_URL_SUFFIX = 51;</code>
     */
    const INVALID_TAG_IN_FINAL_URL_SUFFIX = 51;
    /**
     * The top level domain is invalid, e.g, not a public top level domain
     * listed in publicsuffix.org.
     *
     * Generated from protobuf enum <code>INVALID_TOP_LEVEL_DOMAIN = 53;</code>
     */
    const INVALID_TOP_LEVEL_DOMAIN = 53;
    /**
     * Malformed top level domain in URL.
     *
     * Generated from protobuf enum <code>MALFORMED_TOP_LEVEL_DOMAIN = 54;</code>
     */
    const MALFORMED_TOP_LEVEL_DOMAIN = 54;
    /**
     * Malformed URL.
     *
     * Generated from protobuf enum <code>MALFORMED_URL = 55;</code>
     */
    const MALFORMED_URL = 55;
    /**
     * No host found in URL.
     *
     * Generated from protobuf enum <code>MISSING_HOST = 56;</code>
     */
    const MISSING_HOST = 56;
    /**
     * Custom parameter value cannot be null.
     *
     * Generated from protobuf enum <code>NULL_CUSTOM_PARAMETER_VALUE = 57;</code>
     */
    const NULL_CUSTOM_PARAMETER_VALUE = 57;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::INVALID_TRACKING_URL_TEMPLATE => 'INVALID_TRACKING_URL_TEMPLATE',
        self::INVALID_TAG_IN_TRACKING_URL_TEMPLATE => 'INVALID_TAG_IN_TRACKING_URL_TEMPLATE',
        self::MISSING_TRACKING_URL_TEMPLATE_TAG => 'MISSING_TRACKING_URL_TEMPLATE_TAG',
        self::MISSING_PROTOCOL_IN_TRACKING_URL_TEMPLATE => 'MISSING_PROTOCOL_IN_TRACKING_URL_TEMPLATE',
        self::INVALID_PROTOCOL_IN_TRACKING_URL_TEMPLATE => 'INVALID_PROTOCOL_IN_TRACKING_URL_TEMPLATE',
        self::MALFORMED_TRACKING_URL_TEMPLATE => 'MALFORMED_TRACKING_URL_TEMPLATE',
        self::MISSING_HOST_IN_TRACKING_URL_TEMPLATE => 'MISSING_HOST_IN_TRACKING_URL_TEMPLATE',
        self::INVALID_TLD_IN_TRACKING_URL_TEMPLATE => 'INVALID_TLD_IN_TRACKING_URL_TEMPLATE',
        self::REDUNDANT_NESTED_TRACKING_URL_TEMPLATE_TAG => 'REDUNDANT_NESTED_TRACKING_URL_TEMPLATE_TAG',
        self::INVALID_FINAL_URL => 'INVALID_FINAL_URL',
        self::INVALID_TAG_IN_FINAL_URL => 'INVALID_TAG_IN_FINAL_URL',
        self::REDUNDANT_NESTED_FINAL_URL_TAG => 'REDUNDANT_NESTED_FINAL_URL_TAG',
        self::MISSING_PROTOCOL_IN_FINAL_URL => 'MISSING_PROTOCOL_IN_FINAL_URL',
        self::INVALID_PROTOCOL_IN_FINAL_URL => 'INVALID_PROTOCOL_IN_FINAL_URL',
        self::MALFORMED_FINAL_URL => 'MALFORMED_FINAL_URL',
        self::MISSING_HOST_IN_FINAL_URL => 'MISSING_HOST_IN_FINAL_URL',
        self::INVALID_TLD_IN_FINAL_URL => 'INVALID_TLD_IN_FINAL_URL',
        self::INVALID_FINAL_MOBILE_URL => 'INVALID_FINAL_MOBILE_URL',
        self::INVALID_TAG_IN_FINAL_MOBILE_URL => 'INVALID_TAG_IN_FINAL_MOBILE_URL',
        self::REDUNDANT_NESTED_FINAL_MOBILE_URL_TAG => 'REDUNDANT_NESTED_FINAL_MOBILE_URL_TAG',
        self::MISSING_PROTOCOL_IN_FINAL_MOBILE_URL => 'MISSING_PROTOCOL_IN_FINAL_MOBILE_URL',
        self::INVALID_PROTOCOL_IN_FINAL_MOBILE_URL => 'INVALID_PROTOCOL_IN_FINAL_MOBILE_URL',
        self::MALFORMED_FINAL_MOBILE_URL => 'MALFORMED_FINAL_MOBILE_URL',
        self::MISSING_HOST_IN_FINAL_MOBILE_URL => 'MISSING_HOST_IN_FINAL_MOBILE_URL',
        self::INVALID_TLD_IN_FINAL_MOBILE_URL => 'INVALID_TLD_IN_FINAL_MOBILE_URL',
        self::INVALID_FINAL_APP_URL => 'INVALID_FINAL_APP_URL',
        self::INVALID_TAG_IN_FINAL_APP_URL => 'INVALID_TAG_IN_FINAL_APP_URL',
        self::REDUNDANT_NESTED_FINAL_APP_URL_TAG => 'REDUNDANT_NESTED_FINAL_APP_URL_TAG',
        self::MULTIPLE_APP_URLS_FOR_OSTYPE => 'MULTIPLE_APP_URLS_FOR_OSTYPE',
        self::INVALID_OSTYPE => 'INVALID_OSTYPE',
        self::INVALID_PROTOCOL_FOR_APP_URL => 'INVALID_PROTOCOL_FOR_APP_URL',
        self::INVALID_PACKAGE_ID_FOR_APP_URL => 'INVALID_PACKAGE_ID_FOR_APP_URL',
        self::URL_CUSTOM_PARAMETERS_COUNT_EXCEEDS_LIMIT => 'URL_CUSTOM_PARAMETERS_COUNT_EXCEEDS_LIMIT',
        self::INVALID_CHARACTERS_IN_URL_CUSTOM_PARAMETER_KEY => 'INVALID_CHARACTERS_IN_URL_CUSTOM_PARAMETER_KEY',
        self::INVALID_CHARACTERS_IN_URL_CUSTOM_PARAMETER_VALUE => 'INVALID_CHARACTERS_IN_URL_CUSTOM_PARAMETER_VALUE',
        self::INVALID_TAG_IN_URL_CUSTOM_PARAMETER_VALUE => 'INVALID_TAG_IN_URL_CUSTOM_PARAMETER_VALUE',
        self::REDUNDANT_NESTED_URL_CUSTOM_PARAMETER_TAG => 'REDUNDANT_NESTED_URL_CUSTOM_PARAMETER_TAG',
        self::MISSING_PROTOCOL => 'MISSING_PROTOCOL',
        self::INVALID_PROTOCOL => 'INVALID_PROTOCOL',
        self::INVALID_URL => 'INVALID_URL',
        self::DESTINATION_URL_DEPRECATED => 'DESTINATION_URL_DEPRECATED',
        self::INVALID_TAG_IN_URL => 'INVALID_TAG_IN_URL',
        self::MISSING_URL_TAG => 'MISSING_URL_TAG',
        self::DUPLICATE_URL_ID => 'DUPLICATE_URL_ID',
        self::INVALID_URL_ID => 'INVALID_URL_ID',
        self::FINAL_URL_SUFFIX_MALFORMED => 'FINAL_URL_SUFFIX_MALFORMED',
        self::INVALID_TAG_IN_FINAL_URL_SUFFIX => 'INVALID_TAG_IN_FINAL_URL_SUFFIX',
        self::INVALID_TOP_LEVEL_DOMAIN => 'INVALID_TOP_LEVEL_DOMAIN',
        self::MALFORMED_TOP_LEVEL_DOMAIN => 'MALFORMED_TOP_LEVEL_DOMAIN',
        self::MALFORMED_URL => 'MALFORMED_URL',
        self::MISSING_HOST => 'MISSING_HOST',
        self::NULL_CUSTOM_PARAMETER_VALUE => 'NULL_CUSTOM_PARAMETER_VALUE',
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
class_alias(UrlFieldError::class, \Google\Ads\GoogleAds\V4\Errors\UrlFieldErrorEnum_UrlFieldError::class);

