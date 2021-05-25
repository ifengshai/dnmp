<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v6/errors/image_error.proto

namespace Google\Ads\GoogleAds\V6\Errors\ImageErrorEnum;

use UnexpectedValueException;

/**
 * Enum describing possible image errors.
 *
 * Protobuf type <code>google.ads.googleads.v6.errors.ImageErrorEnum.ImageError</code>
 */
class ImageError
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
     * The image is not valid.
     *
     * Generated from protobuf enum <code>INVALID_IMAGE = 2;</code>
     */
    const INVALID_IMAGE = 2;
    /**
     * The image could not be stored.
     *
     * Generated from protobuf enum <code>STORAGE_ERROR = 3;</code>
     */
    const STORAGE_ERROR = 3;
    /**
     * There was a problem with the request.
     *
     * Generated from protobuf enum <code>BAD_REQUEST = 4;</code>
     */
    const BAD_REQUEST = 4;
    /**
     * The image is not of legal dimensions.
     *
     * Generated from protobuf enum <code>UNEXPECTED_SIZE = 5;</code>
     */
    const UNEXPECTED_SIZE = 5;
    /**
     * Animated image are not permitted.
     *
     * Generated from protobuf enum <code>ANIMATED_NOT_ALLOWED = 6;</code>
     */
    const ANIMATED_NOT_ALLOWED = 6;
    /**
     * Animation is too long.
     *
     * Generated from protobuf enum <code>ANIMATION_TOO_LONG = 7;</code>
     */
    const ANIMATION_TOO_LONG = 7;
    /**
     * There was an error on the server.
     *
     * Generated from protobuf enum <code>SERVER_ERROR = 8;</code>
     */
    const SERVER_ERROR = 8;
    /**
     * Image cannot be in CMYK color format.
     *
     * Generated from protobuf enum <code>CMYK_JPEG_NOT_ALLOWED = 9;</code>
     */
    const CMYK_JPEG_NOT_ALLOWED = 9;
    /**
     * Flash images are not permitted.
     *
     * Generated from protobuf enum <code>FLASH_NOT_ALLOWED = 10;</code>
     */
    const FLASH_NOT_ALLOWED = 10;
    /**
     * Flash images must support clickTag.
     *
     * Generated from protobuf enum <code>FLASH_WITHOUT_CLICKTAG = 11;</code>
     */
    const FLASH_WITHOUT_CLICKTAG = 11;
    /**
     * A flash error has occurred after fixing the click tag.
     *
     * Generated from protobuf enum <code>FLASH_ERROR_AFTER_FIXING_CLICK_TAG = 12;</code>
     */
    const FLASH_ERROR_AFTER_FIXING_CLICK_TAG = 12;
    /**
     * Unacceptable visual effects.
     *
     * Generated from protobuf enum <code>ANIMATED_VISUAL_EFFECT = 13;</code>
     */
    const ANIMATED_VISUAL_EFFECT = 13;
    /**
     * There was a problem with the flash image.
     *
     * Generated from protobuf enum <code>FLASH_ERROR = 14;</code>
     */
    const FLASH_ERROR = 14;
    /**
     * Incorrect image layout.
     *
     * Generated from protobuf enum <code>LAYOUT_PROBLEM = 15;</code>
     */
    const LAYOUT_PROBLEM = 15;
    /**
     * There was a problem reading the image file.
     *
     * Generated from protobuf enum <code>PROBLEM_READING_IMAGE_FILE = 16;</code>
     */
    const PROBLEM_READING_IMAGE_FILE = 16;
    /**
     * There was an error storing the image.
     *
     * Generated from protobuf enum <code>ERROR_STORING_IMAGE = 17;</code>
     */
    const ERROR_STORING_IMAGE = 17;
    /**
     * The aspect ratio of the image is not allowed.
     *
     * Generated from protobuf enum <code>ASPECT_RATIO_NOT_ALLOWED = 18;</code>
     */
    const ASPECT_RATIO_NOT_ALLOWED = 18;
    /**
     * Flash cannot have network objects.
     *
     * Generated from protobuf enum <code>FLASH_HAS_NETWORK_OBJECTS = 19;</code>
     */
    const FLASH_HAS_NETWORK_OBJECTS = 19;
    /**
     * Flash cannot have network methods.
     *
     * Generated from protobuf enum <code>FLASH_HAS_NETWORK_METHODS = 20;</code>
     */
    const FLASH_HAS_NETWORK_METHODS = 20;
    /**
     * Flash cannot have a Url.
     *
     * Generated from protobuf enum <code>FLASH_HAS_URL = 21;</code>
     */
    const FLASH_HAS_URL = 21;
    /**
     * Flash cannot use mouse tracking.
     *
     * Generated from protobuf enum <code>FLASH_HAS_MOUSE_TRACKING = 22;</code>
     */
    const FLASH_HAS_MOUSE_TRACKING = 22;
    /**
     * Flash cannot have a random number.
     *
     * Generated from protobuf enum <code>FLASH_HAS_RANDOM_NUM = 23;</code>
     */
    const FLASH_HAS_RANDOM_NUM = 23;
    /**
     * Ad click target cannot be '_self'.
     *
     * Generated from protobuf enum <code>FLASH_SELF_TARGETS = 24;</code>
     */
    const FLASH_SELF_TARGETS = 24;
    /**
     * GetUrl method should only use '_blank'.
     *
     * Generated from protobuf enum <code>FLASH_BAD_GETURL_TARGET = 25;</code>
     */
    const FLASH_BAD_GETURL_TARGET = 25;
    /**
     * Flash version is not supported.
     *
     * Generated from protobuf enum <code>FLASH_VERSION_NOT_SUPPORTED = 26;</code>
     */
    const FLASH_VERSION_NOT_SUPPORTED = 26;
    /**
     * Flash movies need to have hard coded click URL or clickTAG
     *
     * Generated from protobuf enum <code>FLASH_WITHOUT_HARD_CODED_CLICK_URL = 27;</code>
     */
    const FLASH_WITHOUT_HARD_CODED_CLICK_URL = 27;
    /**
     * Uploaded flash file is corrupted.
     *
     * Generated from protobuf enum <code>INVALID_FLASH_FILE = 28;</code>
     */
    const INVALID_FLASH_FILE = 28;
    /**
     * Uploaded flash file can be parsed, but the click tag can not be fixed
     * properly.
     *
     * Generated from protobuf enum <code>FAILED_TO_FIX_CLICK_TAG_IN_FLASH = 29;</code>
     */
    const FAILED_TO_FIX_CLICK_TAG_IN_FLASH = 29;
    /**
     * Flash movie accesses network resources
     *
     * Generated from protobuf enum <code>FLASH_ACCESSES_NETWORK_RESOURCES = 30;</code>
     */
    const FLASH_ACCESSES_NETWORK_RESOURCES = 30;
    /**
     * Flash movie attempts to call external javascript code
     *
     * Generated from protobuf enum <code>FLASH_EXTERNAL_JS_CALL = 31;</code>
     */
    const FLASH_EXTERNAL_JS_CALL = 31;
    /**
     * Flash movie attempts to call flash system commands
     *
     * Generated from protobuf enum <code>FLASH_EXTERNAL_FS_CALL = 32;</code>
     */
    const FLASH_EXTERNAL_FS_CALL = 32;
    /**
     * Image file is too large.
     *
     * Generated from protobuf enum <code>FILE_TOO_LARGE = 33;</code>
     */
    const FILE_TOO_LARGE = 33;
    /**
     * Image data is too large.
     *
     * Generated from protobuf enum <code>IMAGE_DATA_TOO_LARGE = 34;</code>
     */
    const IMAGE_DATA_TOO_LARGE = 34;
    /**
     * Error while processing the image.
     *
     * Generated from protobuf enum <code>IMAGE_PROCESSING_ERROR = 35;</code>
     */
    const IMAGE_PROCESSING_ERROR = 35;
    /**
     * Image is too small.
     *
     * Generated from protobuf enum <code>IMAGE_TOO_SMALL = 36;</code>
     */
    const IMAGE_TOO_SMALL = 36;
    /**
     * Input was invalid.
     *
     * Generated from protobuf enum <code>INVALID_INPUT = 37;</code>
     */
    const INVALID_INPUT = 37;
    /**
     * There was a problem reading the image file.
     *
     * Generated from protobuf enum <code>PROBLEM_READING_FILE = 38;</code>
     */
    const PROBLEM_READING_FILE = 38;
    /**
     * Image constraints are violated, but details like ASPECT_RATIO_NOT_ALLOWED
     * can't be provided. This happens when asset spec contains more than one
     * constraint and different criteria of different constraints are violated.
     *
     * Generated from protobuf enum <code>IMAGE_CONSTRAINTS_VIOLATED = 39;</code>
     */
    const IMAGE_CONSTRAINTS_VIOLATED = 39;
    /**
     * Image format is not allowed.
     *
     * Generated from protobuf enum <code>FORMAT_NOT_ALLOWED = 40;</code>
     */
    const FORMAT_NOT_ALLOWED = 40;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::INVALID_IMAGE => 'INVALID_IMAGE',
        self::STORAGE_ERROR => 'STORAGE_ERROR',
        self::BAD_REQUEST => 'BAD_REQUEST',
        self::UNEXPECTED_SIZE => 'UNEXPECTED_SIZE',
        self::ANIMATED_NOT_ALLOWED => 'ANIMATED_NOT_ALLOWED',
        self::ANIMATION_TOO_LONG => 'ANIMATION_TOO_LONG',
        self::SERVER_ERROR => 'SERVER_ERROR',
        self::CMYK_JPEG_NOT_ALLOWED => 'CMYK_JPEG_NOT_ALLOWED',
        self::FLASH_NOT_ALLOWED => 'FLASH_NOT_ALLOWED',
        self::FLASH_WITHOUT_CLICKTAG => 'FLASH_WITHOUT_CLICKTAG',
        self::FLASH_ERROR_AFTER_FIXING_CLICK_TAG => 'FLASH_ERROR_AFTER_FIXING_CLICK_TAG',
        self::ANIMATED_VISUAL_EFFECT => 'ANIMATED_VISUAL_EFFECT',
        self::FLASH_ERROR => 'FLASH_ERROR',
        self::LAYOUT_PROBLEM => 'LAYOUT_PROBLEM',
        self::PROBLEM_READING_IMAGE_FILE => 'PROBLEM_READING_IMAGE_FILE',
        self::ERROR_STORING_IMAGE => 'ERROR_STORING_IMAGE',
        self::ASPECT_RATIO_NOT_ALLOWED => 'ASPECT_RATIO_NOT_ALLOWED',
        self::FLASH_HAS_NETWORK_OBJECTS => 'FLASH_HAS_NETWORK_OBJECTS',
        self::FLASH_HAS_NETWORK_METHODS => 'FLASH_HAS_NETWORK_METHODS',
        self::FLASH_HAS_URL => 'FLASH_HAS_URL',
        self::FLASH_HAS_MOUSE_TRACKING => 'FLASH_HAS_MOUSE_TRACKING',
        self::FLASH_HAS_RANDOM_NUM => 'FLASH_HAS_RANDOM_NUM',
        self::FLASH_SELF_TARGETS => 'FLASH_SELF_TARGETS',
        self::FLASH_BAD_GETURL_TARGET => 'FLASH_BAD_GETURL_TARGET',
        self::FLASH_VERSION_NOT_SUPPORTED => 'FLASH_VERSION_NOT_SUPPORTED',
        self::FLASH_WITHOUT_HARD_CODED_CLICK_URL => 'FLASH_WITHOUT_HARD_CODED_CLICK_URL',
        self::INVALID_FLASH_FILE => 'INVALID_FLASH_FILE',
        self::FAILED_TO_FIX_CLICK_TAG_IN_FLASH => 'FAILED_TO_FIX_CLICK_TAG_IN_FLASH',
        self::FLASH_ACCESSES_NETWORK_RESOURCES => 'FLASH_ACCESSES_NETWORK_RESOURCES',
        self::FLASH_EXTERNAL_JS_CALL => 'FLASH_EXTERNAL_JS_CALL',
        self::FLASH_EXTERNAL_FS_CALL => 'FLASH_EXTERNAL_FS_CALL',
        self::FILE_TOO_LARGE => 'FILE_TOO_LARGE',
        self::IMAGE_DATA_TOO_LARGE => 'IMAGE_DATA_TOO_LARGE',
        self::IMAGE_PROCESSING_ERROR => 'IMAGE_PROCESSING_ERROR',
        self::IMAGE_TOO_SMALL => 'IMAGE_TOO_SMALL',
        self::INVALID_INPUT => 'INVALID_INPUT',
        self::PROBLEM_READING_FILE => 'PROBLEM_READING_FILE',
        self::IMAGE_CONSTRAINTS_VIOLATED => 'IMAGE_CONSTRAINTS_VIOLATED',
        self::FORMAT_NOT_ALLOWED => 'FORMAT_NOT_ALLOWED',
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
class_alias(ImageError::class, \Google\Ads\GoogleAds\V6\Errors\ImageErrorEnum_ImageError::class);

