<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/errors/invoice_error.proto

namespace Google\Ads\GoogleAds\V3\Errors\InvoiceErrorEnum;

use UnexpectedValueException;

/**
 * Enum describing possible invoice errors.
 *
 * Protobuf type <code>google.ads.googleads.v3.errors.InvoiceErrorEnum.InvoiceError</code>
 */
class InvoiceError
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
     * Cannot request invoices issued before 2019-01-01.
     *
     * Generated from protobuf enum <code>YEAR_MONTH_TOO_OLD = 2;</code>
     */
    const YEAR_MONTH_TOO_OLD = 2;
    /**
     * Cannot request invoices for customer who doesn't receive invoices.
     *
     * Generated from protobuf enum <code>NOT_INVOICED_CUSTOMER = 3;</code>
     */
    const NOT_INVOICED_CUSTOMER = 3;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::YEAR_MONTH_TOO_OLD => 'YEAR_MONTH_TOO_OLD',
        self::NOT_INVOICED_CUSTOMER => 'NOT_INVOICED_CUSTOMER',
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
class_alias(InvoiceError::class, \Google\Ads\GoogleAds\V3\Errors\InvoiceErrorEnum_InvoiceError::class);

