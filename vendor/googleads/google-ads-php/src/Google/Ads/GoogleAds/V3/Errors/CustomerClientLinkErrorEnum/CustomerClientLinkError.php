<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/errors/customer_client_link_error.proto

namespace Google\Ads\GoogleAds\V3\Errors\CustomerClientLinkErrorEnum;

use UnexpectedValueException;

/**
 * Enum describing possible CustomerClientLink errors.
 *
 * Protobuf type <code>google.ads.googleads.v3.errors.CustomerClientLinkErrorEnum.CustomerClientLinkError</code>
 */
class CustomerClientLinkError
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
     * Trying to manage a client that already in being managed by customer.
     *
     * Generated from protobuf enum <code>CLIENT_ALREADY_INVITED_BY_THIS_MANAGER = 2;</code>
     */
    const CLIENT_ALREADY_INVITED_BY_THIS_MANAGER = 2;
    /**
     * Already managed by some other manager in the hierarchy.
     *
     * Generated from protobuf enum <code>CLIENT_ALREADY_MANAGED_IN_HIERARCHY = 3;</code>
     */
    const CLIENT_ALREADY_MANAGED_IN_HIERARCHY = 3;
    /**
     * Attempt to create a cycle in the hierarchy.
     *
     * Generated from protobuf enum <code>CYCLIC_LINK_NOT_ALLOWED = 4;</code>
     */
    const CYCLIC_LINK_NOT_ALLOWED = 4;
    /**
     * Managed accounts has the maximum number of linked accounts.
     *
     * Generated from protobuf enum <code>CUSTOMER_HAS_TOO_MANY_ACCOUNTS = 5;</code>
     */
    const CUSTOMER_HAS_TOO_MANY_ACCOUNTS = 5;
    /**
     * Invitor has the maximum pending invitations.
     *
     * Generated from protobuf enum <code>CLIENT_HAS_TOO_MANY_INVITATIONS = 6;</code>
     */
    const CLIENT_HAS_TOO_MANY_INVITATIONS = 6;
    /**
     * Attempt to change hidden status of a link that is not active.
     *
     * Generated from protobuf enum <code>CANNOT_HIDE_OR_UNHIDE_MANAGER_ACCOUNTS = 7;</code>
     */
    const CANNOT_HIDE_OR_UNHIDE_MANAGER_ACCOUNTS = 7;
    /**
     * Parent manager account has the maximum number of linked accounts.
     *
     * Generated from protobuf enum <code>CUSTOMER_HAS_TOO_MANY_ACCOUNTS_AT_MANAGER = 8;</code>
     */
    const CUSTOMER_HAS_TOO_MANY_ACCOUNTS_AT_MANAGER = 8;
    /**
     * Client has too many managers.
     *
     * Generated from protobuf enum <code>CLIENT_HAS_TOO_MANY_MANAGERS = 9;</code>
     */
    const CLIENT_HAS_TOO_MANY_MANAGERS = 9;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::CLIENT_ALREADY_INVITED_BY_THIS_MANAGER => 'CLIENT_ALREADY_INVITED_BY_THIS_MANAGER',
        self::CLIENT_ALREADY_MANAGED_IN_HIERARCHY => 'CLIENT_ALREADY_MANAGED_IN_HIERARCHY',
        self::CYCLIC_LINK_NOT_ALLOWED => 'CYCLIC_LINK_NOT_ALLOWED',
        self::CUSTOMER_HAS_TOO_MANY_ACCOUNTS => 'CUSTOMER_HAS_TOO_MANY_ACCOUNTS',
        self::CLIENT_HAS_TOO_MANY_INVITATIONS => 'CLIENT_HAS_TOO_MANY_INVITATIONS',
        self::CANNOT_HIDE_OR_UNHIDE_MANAGER_ACCOUNTS => 'CANNOT_HIDE_OR_UNHIDE_MANAGER_ACCOUNTS',
        self::CUSTOMER_HAS_TOO_MANY_ACCOUNTS_AT_MANAGER => 'CUSTOMER_HAS_TOO_MANY_ACCOUNTS_AT_MANAGER',
        self::CLIENT_HAS_TOO_MANY_MANAGERS => 'CLIENT_HAS_TOO_MANY_MANAGERS',
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
class_alias(CustomerClientLinkError::class, \Google\Ads\GoogleAds\V3\Errors\CustomerClientLinkErrorEnum_CustomerClientLinkError::class);

