<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/iam/admin/v1/iam.proto

namespace Google\Iam\Admin\V1\ListServiceAccountKeysRequest;

use UnexpectedValueException;

/**
 * `KeyType` filters to selectively retrieve certain varieties
 * of keys.
 *
 * Protobuf type <code>google.iam.admin.v1.ListServiceAccountKeysRequest.KeyType</code>
 */
class KeyType
{
    /**
     * Unspecified key type. The presence of this in the
     * message will immediately result in an error.
     *
     * Generated from protobuf enum <code>KEY_TYPE_UNSPECIFIED = 0;</code>
     */
    const KEY_TYPE_UNSPECIFIED = 0;
    /**
     * User-managed keys (managed and rotated by the user).
     *
     * Generated from protobuf enum <code>USER_MANAGED = 1;</code>
     */
    const USER_MANAGED = 1;
    /**
     * System-managed keys (managed and rotated by Google).
     *
     * Generated from protobuf enum <code>SYSTEM_MANAGED = 2;</code>
     */
    const SYSTEM_MANAGED = 2;

    private static $valueToName = [
        self::KEY_TYPE_UNSPECIFIED => 'KEY_TYPE_UNSPECIFIED',
        self::USER_MANAGED => 'USER_MANAGED',
        self::SYSTEM_MANAGED => 'SYSTEM_MANAGED',
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
class_alias(KeyType::class, \Google\Iam\Admin\V1\ListServiceAccountKeysRequest_KeyType::class);

