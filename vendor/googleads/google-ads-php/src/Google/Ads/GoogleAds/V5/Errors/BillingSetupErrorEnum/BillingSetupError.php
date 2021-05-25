<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/errors/billing_setup_error.proto

namespace Google\Ads\GoogleAds\V5\Errors\BillingSetupErrorEnum;

use UnexpectedValueException;

/**
 * Enum describing possible billing setup errors.
 *
 * Protobuf type <code>google.ads.googleads.v5.errors.BillingSetupErrorEnum.BillingSetupError</code>
 */
class BillingSetupError
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
     * Cannot specify both an existing payments account and a new payments
     * account when setting up billing.
     *
     * Generated from protobuf enum <code>CANNOT_USE_EXISTING_AND_NEW_ACCOUNT = 2;</code>
     */
    const CANNOT_USE_EXISTING_AND_NEW_ACCOUNT = 2;
    /**
     * Cannot cancel an approved billing setup whose start time has passed.
     *
     * Generated from protobuf enum <code>CANNOT_REMOVE_STARTED_BILLING_SETUP = 3;</code>
     */
    const CANNOT_REMOVE_STARTED_BILLING_SETUP = 3;
    /**
     * Cannot perform a Change of Bill-To (CBT) to the same payments account.
     *
     * Generated from protobuf enum <code>CANNOT_CHANGE_BILLING_TO_SAME_PAYMENTS_ACCOUNT = 4;</code>
     */
    const CANNOT_CHANGE_BILLING_TO_SAME_PAYMENTS_ACCOUNT = 4;
    /**
     * Billing setups can only be used by customers with ENABLED or DRAFT
     * status.
     *
     * Generated from protobuf enum <code>BILLING_SETUP_NOT_PERMITTED_FOR_CUSTOMER_STATUS = 5;</code>
     */
    const BILLING_SETUP_NOT_PERMITTED_FOR_CUSTOMER_STATUS = 5;
    /**
     * Billing setups must either include a correctly formatted existing
     * payments account id, or a non-empty new payments account name.
     *
     * Generated from protobuf enum <code>INVALID_PAYMENTS_ACCOUNT = 6;</code>
     */
    const INVALID_PAYMENTS_ACCOUNT = 6;
    /**
     * Only billable and third-party customers can create billing setups.
     *
     * Generated from protobuf enum <code>BILLING_SETUP_NOT_PERMITTED_FOR_CUSTOMER_CATEGORY = 7;</code>
     */
    const BILLING_SETUP_NOT_PERMITTED_FOR_CUSTOMER_CATEGORY = 7;
    /**
     * Billing setup creations can only use NOW for start time type.
     *
     * Generated from protobuf enum <code>INVALID_START_TIME_TYPE = 8;</code>
     */
    const INVALID_START_TIME_TYPE = 8;
    /**
     * Billing setups can only be created for a third-party customer if they do
     * not already have a setup.
     *
     * Generated from protobuf enum <code>THIRD_PARTY_ALREADY_HAS_BILLING = 9;</code>
     */
    const THIRD_PARTY_ALREADY_HAS_BILLING = 9;
    /**
     * Billing setups cannot be created if there is already a pending billing in
     * progress.
     *
     * Generated from protobuf enum <code>BILLING_SETUP_IN_PROGRESS = 10;</code>
     */
    const BILLING_SETUP_IN_PROGRESS = 10;
    /**
     * Billing setups can only be created by customers who have permission to
     * setup billings. Users can contact a representative for help setting up
     * permissions.
     *
     * Generated from protobuf enum <code>NO_SIGNUP_PERMISSION = 11;</code>
     */
    const NO_SIGNUP_PERMISSION = 11;
    /**
     * Billing setups cannot be created if there is already a future-approved
     * billing.
     *
     * Generated from protobuf enum <code>CHANGE_OF_BILL_TO_IN_PROGRESS = 12;</code>
     */
    const CHANGE_OF_BILL_TO_IN_PROGRESS = 12;
    /**
     * Requested payments profile not found.
     *
     * Generated from protobuf enum <code>PAYMENTS_PROFILE_NOT_FOUND = 13;</code>
     */
    const PAYMENTS_PROFILE_NOT_FOUND = 13;
    /**
     * Requested payments account not found.
     *
     * Generated from protobuf enum <code>PAYMENTS_ACCOUNT_NOT_FOUND = 14;</code>
     */
    const PAYMENTS_ACCOUNT_NOT_FOUND = 14;
    /**
     * Billing setup creation failed because the payments profile is ineligible.
     *
     * Generated from protobuf enum <code>PAYMENTS_PROFILE_INELIGIBLE = 15;</code>
     */
    const PAYMENTS_PROFILE_INELIGIBLE = 15;
    /**
     * Billing setup creation failed because the payments account is ineligible.
     *
     * Generated from protobuf enum <code>PAYMENTS_ACCOUNT_INELIGIBLE = 16;</code>
     */
    const PAYMENTS_ACCOUNT_INELIGIBLE = 16;
    /**
     * Billing setup creation failed because the payments profile needs internal
     * approval.
     *
     * Generated from protobuf enum <code>CUSTOMER_NEEDS_INTERNAL_APPROVAL = 17;</code>
     */
    const CUSTOMER_NEEDS_INTERNAL_APPROVAL = 17;
    /**
     * Payments account has different currency code than the current customer
     * and hence cannot be used to setup billing.
     *
     * Generated from protobuf enum <code>PAYMENTS_ACCOUNT_INELIGIBLE_CURRENCY_CODE_MISMATCH = 19;</code>
     */
    const PAYMENTS_ACCOUNT_INELIGIBLE_CURRENCY_CODE_MISMATCH = 19;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::CANNOT_USE_EXISTING_AND_NEW_ACCOUNT => 'CANNOT_USE_EXISTING_AND_NEW_ACCOUNT',
        self::CANNOT_REMOVE_STARTED_BILLING_SETUP => 'CANNOT_REMOVE_STARTED_BILLING_SETUP',
        self::CANNOT_CHANGE_BILLING_TO_SAME_PAYMENTS_ACCOUNT => 'CANNOT_CHANGE_BILLING_TO_SAME_PAYMENTS_ACCOUNT',
        self::BILLING_SETUP_NOT_PERMITTED_FOR_CUSTOMER_STATUS => 'BILLING_SETUP_NOT_PERMITTED_FOR_CUSTOMER_STATUS',
        self::INVALID_PAYMENTS_ACCOUNT => 'INVALID_PAYMENTS_ACCOUNT',
        self::BILLING_SETUP_NOT_PERMITTED_FOR_CUSTOMER_CATEGORY => 'BILLING_SETUP_NOT_PERMITTED_FOR_CUSTOMER_CATEGORY',
        self::INVALID_START_TIME_TYPE => 'INVALID_START_TIME_TYPE',
        self::THIRD_PARTY_ALREADY_HAS_BILLING => 'THIRD_PARTY_ALREADY_HAS_BILLING',
        self::BILLING_SETUP_IN_PROGRESS => 'BILLING_SETUP_IN_PROGRESS',
        self::NO_SIGNUP_PERMISSION => 'NO_SIGNUP_PERMISSION',
        self::CHANGE_OF_BILL_TO_IN_PROGRESS => 'CHANGE_OF_BILL_TO_IN_PROGRESS',
        self::PAYMENTS_PROFILE_NOT_FOUND => 'PAYMENTS_PROFILE_NOT_FOUND',
        self::PAYMENTS_ACCOUNT_NOT_FOUND => 'PAYMENTS_ACCOUNT_NOT_FOUND',
        self::PAYMENTS_PROFILE_INELIGIBLE => 'PAYMENTS_PROFILE_INELIGIBLE',
        self::PAYMENTS_ACCOUNT_INELIGIBLE => 'PAYMENTS_ACCOUNT_INELIGIBLE',
        self::CUSTOMER_NEEDS_INTERNAL_APPROVAL => 'CUSTOMER_NEEDS_INTERNAL_APPROVAL',
        self::PAYMENTS_ACCOUNT_INELIGIBLE_CURRENCY_CODE_MISMATCH => 'PAYMENTS_ACCOUNT_INELIGIBLE_CURRENCY_CODE_MISMATCH',
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
class_alias(BillingSetupError::class, \Google\Ads\GoogleAds\V5\Errors\BillingSetupErrorEnum_BillingSetupError::class);

