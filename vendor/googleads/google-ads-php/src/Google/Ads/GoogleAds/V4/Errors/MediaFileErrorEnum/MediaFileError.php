<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/errors/media_file_error.proto

namespace Google\Ads\GoogleAds\V4\Errors\MediaFileErrorEnum;

use UnexpectedValueException;

/**
 * Enum describing possible media file errors.
 *
 * Protobuf type <code>google.ads.googleads.v4.errors.MediaFileErrorEnum.MediaFileError</code>
 */
class MediaFileError
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
     * Cannot create a standard icon type.
     *
     * Generated from protobuf enum <code>CANNOT_CREATE_STANDARD_ICON = 2;</code>
     */
    const CANNOT_CREATE_STANDARD_ICON = 2;
    /**
     * May only select Standard Icons alone.
     *
     * Generated from protobuf enum <code>CANNOT_SELECT_STANDARD_ICON_WITH_OTHER_TYPES = 3;</code>
     */
    const CANNOT_SELECT_STANDARD_ICON_WITH_OTHER_TYPES = 3;
    /**
     * Image contains both a media file ID and data.
     *
     * Generated from protobuf enum <code>CANNOT_SPECIFY_MEDIA_FILE_ID_AND_DATA = 4;</code>
     */
    const CANNOT_SPECIFY_MEDIA_FILE_ID_AND_DATA = 4;
    /**
     * A media file with given type and reference ID already exists.
     *
     * Generated from protobuf enum <code>DUPLICATE_MEDIA = 5;</code>
     */
    const DUPLICATE_MEDIA = 5;
    /**
     * A required field was not specified or is an empty string.
     *
     * Generated from protobuf enum <code>EMPTY_FIELD = 6;</code>
     */
    const EMPTY_FIELD = 6;
    /**
     * A media file may only be modified once per call.
     *
     * Generated from protobuf enum <code>RESOURCE_REFERENCED_IN_MULTIPLE_OPS = 7;</code>
     */
    const RESOURCE_REFERENCED_IN_MULTIPLE_OPS = 7;
    /**
     * Field is not supported for the media sub type.
     *
     * Generated from protobuf enum <code>FIELD_NOT_SUPPORTED_FOR_MEDIA_SUB_TYPE = 8;</code>
     */
    const FIELD_NOT_SUPPORTED_FOR_MEDIA_SUB_TYPE = 8;
    /**
     * The media file ID is invalid.
     *
     * Generated from protobuf enum <code>INVALID_MEDIA_FILE_ID = 9;</code>
     */
    const INVALID_MEDIA_FILE_ID = 9;
    /**
     * The media subtype is invalid.
     *
     * Generated from protobuf enum <code>INVALID_MEDIA_SUB_TYPE = 10;</code>
     */
    const INVALID_MEDIA_SUB_TYPE = 10;
    /**
     * The media file type is invalid.
     *
     * Generated from protobuf enum <code>INVALID_MEDIA_FILE_TYPE = 11;</code>
     */
    const INVALID_MEDIA_FILE_TYPE = 11;
    /**
     * The mimetype is invalid.
     *
     * Generated from protobuf enum <code>INVALID_MIME_TYPE = 12;</code>
     */
    const INVALID_MIME_TYPE = 12;
    /**
     * The media reference ID is invalid.
     *
     * Generated from protobuf enum <code>INVALID_REFERENCE_ID = 13;</code>
     */
    const INVALID_REFERENCE_ID = 13;
    /**
     * The YouTube video ID is invalid.
     *
     * Generated from protobuf enum <code>INVALID_YOU_TUBE_ID = 14;</code>
     */
    const INVALID_YOU_TUBE_ID = 14;
    /**
     * Media file has failed transcoding
     *
     * Generated from protobuf enum <code>MEDIA_FILE_FAILED_TRANSCODING = 15;</code>
     */
    const MEDIA_FILE_FAILED_TRANSCODING = 15;
    /**
     * Media file has not been transcoded.
     *
     * Generated from protobuf enum <code>MEDIA_NOT_TRANSCODED = 16;</code>
     */
    const MEDIA_NOT_TRANSCODED = 16;
    /**
     * The media type does not match the actual media file's type.
     *
     * Generated from protobuf enum <code>MEDIA_TYPE_DOES_NOT_MATCH_MEDIA_FILE_TYPE = 17;</code>
     */
    const MEDIA_TYPE_DOES_NOT_MATCH_MEDIA_FILE_TYPE = 17;
    /**
     * None of the fields have been specified.
     *
     * Generated from protobuf enum <code>NO_FIELDS_SPECIFIED = 18;</code>
     */
    const NO_FIELDS_SPECIFIED = 18;
    /**
     * One of reference ID or media file ID must be specified.
     *
     * Generated from protobuf enum <code>NULL_REFERENCE_ID_AND_MEDIA_ID = 19;</code>
     */
    const NULL_REFERENCE_ID_AND_MEDIA_ID = 19;
    /**
     * The string has too many characters.
     *
     * Generated from protobuf enum <code>TOO_LONG = 20;</code>
     */
    const TOO_LONG = 20;
    /**
     * The specified type is not supported.
     *
     * Generated from protobuf enum <code>UNSUPPORTED_TYPE = 21;</code>
     */
    const UNSUPPORTED_TYPE = 21;
    /**
     * YouTube is unavailable for requesting video data.
     *
     * Generated from protobuf enum <code>YOU_TUBE_SERVICE_UNAVAILABLE = 22;</code>
     */
    const YOU_TUBE_SERVICE_UNAVAILABLE = 22;
    /**
     * The YouTube video has a non positive duration.
     *
     * Generated from protobuf enum <code>YOU_TUBE_VIDEO_HAS_NON_POSITIVE_DURATION = 23;</code>
     */
    const YOU_TUBE_VIDEO_HAS_NON_POSITIVE_DURATION = 23;
    /**
     * The YouTube video ID is syntactically valid but the video was not found.
     *
     * Generated from protobuf enum <code>YOU_TUBE_VIDEO_NOT_FOUND = 24;</code>
     */
    const YOU_TUBE_VIDEO_NOT_FOUND = 24;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::CANNOT_CREATE_STANDARD_ICON => 'CANNOT_CREATE_STANDARD_ICON',
        self::CANNOT_SELECT_STANDARD_ICON_WITH_OTHER_TYPES => 'CANNOT_SELECT_STANDARD_ICON_WITH_OTHER_TYPES',
        self::CANNOT_SPECIFY_MEDIA_FILE_ID_AND_DATA => 'CANNOT_SPECIFY_MEDIA_FILE_ID_AND_DATA',
        self::DUPLICATE_MEDIA => 'DUPLICATE_MEDIA',
        self::EMPTY_FIELD => 'EMPTY_FIELD',
        self::RESOURCE_REFERENCED_IN_MULTIPLE_OPS => 'RESOURCE_REFERENCED_IN_MULTIPLE_OPS',
        self::FIELD_NOT_SUPPORTED_FOR_MEDIA_SUB_TYPE => 'FIELD_NOT_SUPPORTED_FOR_MEDIA_SUB_TYPE',
        self::INVALID_MEDIA_FILE_ID => 'INVALID_MEDIA_FILE_ID',
        self::INVALID_MEDIA_SUB_TYPE => 'INVALID_MEDIA_SUB_TYPE',
        self::INVALID_MEDIA_FILE_TYPE => 'INVALID_MEDIA_FILE_TYPE',
        self::INVALID_MIME_TYPE => 'INVALID_MIME_TYPE',
        self::INVALID_REFERENCE_ID => 'INVALID_REFERENCE_ID',
        self::INVALID_YOU_TUBE_ID => 'INVALID_YOU_TUBE_ID',
        self::MEDIA_FILE_FAILED_TRANSCODING => 'MEDIA_FILE_FAILED_TRANSCODING',
        self::MEDIA_NOT_TRANSCODED => 'MEDIA_NOT_TRANSCODED',
        self::MEDIA_TYPE_DOES_NOT_MATCH_MEDIA_FILE_TYPE => 'MEDIA_TYPE_DOES_NOT_MATCH_MEDIA_FILE_TYPE',
        self::NO_FIELDS_SPECIFIED => 'NO_FIELDS_SPECIFIED',
        self::NULL_REFERENCE_ID_AND_MEDIA_ID => 'NULL_REFERENCE_ID_AND_MEDIA_ID',
        self::TOO_LONG => 'TOO_LONG',
        self::UNSUPPORTED_TYPE => 'UNSUPPORTED_TYPE',
        self::YOU_TUBE_SERVICE_UNAVAILABLE => 'YOU_TUBE_SERVICE_UNAVAILABLE',
        self::YOU_TUBE_VIDEO_HAS_NON_POSITIVE_DURATION => 'YOU_TUBE_VIDEO_HAS_NON_POSITIVE_DURATION',
        self::YOU_TUBE_VIDEO_NOT_FOUND => 'YOU_TUBE_VIDEO_NOT_FOUND',
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
class_alias(MediaFileError::class, \Google\Ads\GoogleAds\V4\Errors\MediaFileErrorEnum_MediaFileError::class);

