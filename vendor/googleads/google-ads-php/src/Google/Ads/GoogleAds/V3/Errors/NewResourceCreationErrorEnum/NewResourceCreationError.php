<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/errors/new_resource_creation_error.proto

namespace Google\Ads\GoogleAds\V3\Errors\NewResourceCreationErrorEnum;

use UnexpectedValueException;

/**
 * Enum describing possible new resource creation errors.
 *
 * Protobuf type <code>google.ads.googleads.v3.errors.NewResourceCreationErrorEnum.NewResourceCreationError</code>
 */
class NewResourceCreationError
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
     * Do not set the id field while creating new resources.
     *
     * Generated from protobuf enum <code>CANNOT_SET_ID_FOR_CREATE = 2;</code>
     */
    const CANNOT_SET_ID_FOR_CREATE = 2;
    /**
     * Creating more than one resource with the same temp ID is not allowed.
     *
     * Generated from protobuf enum <code>DUPLICATE_TEMP_IDS = 3;</code>
     */
    const DUPLICATE_TEMP_IDS = 3;
    /**
     * Parent resource with specified temp ID failed validation, so no
     * validation will be done for this child resource.
     *
     * Generated from protobuf enum <code>TEMP_ID_RESOURCE_HAD_ERRORS = 4;</code>
     */
    const TEMP_ID_RESOURCE_HAD_ERRORS = 4;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::CANNOT_SET_ID_FOR_CREATE => 'CANNOT_SET_ID_FOR_CREATE',
        self::DUPLICATE_TEMP_IDS => 'DUPLICATE_TEMP_IDS',
        self::TEMP_ID_RESOURCE_HAD_ERRORS => 'TEMP_ID_RESOURCE_HAD_ERRORS',
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
class_alias(NewResourceCreationError::class, \Google\Ads\GoogleAds\V3\Errors\NewResourceCreationErrorEnum_NewResourceCreationError::class);

