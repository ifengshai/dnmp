<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/enums/account_budget_proposal_status.proto

namespace Google\Ads\GoogleAds\V3\Enums\AccountBudgetProposalStatusEnum;

use UnexpectedValueException;

/**
 * The possible statuses of an AccountBudgetProposal.
 *
 * Protobuf type <code>google.ads.googleads.v3.enums.AccountBudgetProposalStatusEnum.AccountBudgetProposalStatus</code>
 */
class AccountBudgetProposalStatus
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
     * The proposal is pending approval.
     *
     * Generated from protobuf enum <code>PENDING = 2;</code>
     */
    const PENDING = 2;
    /**
     * The proposal has been approved but the corresponding billing setup
     * has not.  This can occur for proposals that set up the first budget
     * when signing up for billing or when performing a change of bill-to
     * operation.
     *
     * Generated from protobuf enum <code>APPROVED_HELD = 3;</code>
     */
    const APPROVED_HELD = 3;
    /**
     * The proposal has been approved.
     *
     * Generated from protobuf enum <code>APPROVED = 4;</code>
     */
    const APPROVED = 4;
    /**
     * The proposal has been cancelled by the user.
     *
     * Generated from protobuf enum <code>CANCELLED = 5;</code>
     */
    const CANCELLED = 5;
    /**
     * The proposal has been rejected by the user, e.g. by rejecting an
     * acceptance email.
     *
     * Generated from protobuf enum <code>REJECTED = 6;</code>
     */
    const REJECTED = 6;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::PENDING => 'PENDING',
        self::APPROVED_HELD => 'APPROVED_HELD',
        self::APPROVED => 'APPROVED',
        self::CANCELLED => 'CANCELLED',
        self::REJECTED => 'REJECTED',
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
class_alias(AccountBudgetProposalStatus::class, \Google\Ads\GoogleAds\V3\Enums\AccountBudgetProposalStatusEnum_AccountBudgetProposalStatus::class);

