<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/resources/payments_account.proto

namespace Google\Ads\GoogleAds\V3\Resources;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * A payments account, which can be used to set up billing for an Ads customer.
 *
 * Generated from protobuf message <code>google.ads.googleads.v3.resources.PaymentsAccount</code>
 */
class PaymentsAccount extends \Google\Protobuf\Internal\Message
{
    /**
     * Output only. The resource name of the payments account.
     * PaymentsAccount resource names have the form:
     * `customers/{customer_id}/paymentsAccounts/{payments_account_id}`
     *
     * Generated from protobuf field <code>string resource_name = 1 [(.google.api.field_behavior) = OUTPUT_ONLY, (.google.api.resource_reference) = {</code>
     */
    protected $resource_name = '';
    /**
     * Output only. A 16 digit ID used to identify a payments account.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue payments_account_id = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $payments_account_id = null;
    /**
     * Output only. The name of the payments account.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue name = 3 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $name = null;
    /**
     * Output only. The currency code of the payments account.
     * A subset of the currency codes derived from the ISO 4217 standard is
     * supported.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue currency_code = 4 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $currency_code = null;
    /**
     * Output only. A 12 digit ID used to identify the payments profile associated with the
     * payments account.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue payments_profile_id = 5 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $payments_profile_id = null;
    /**
     * Output only. A secondary payments profile ID present in uncommon situations, e.g.
     * when a sequential liability agreement has been arranged.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue secondary_payments_profile_id = 6 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $secondary_payments_profile_id = null;
    /**
     * Output only. Paying manager of this payment account.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue paying_manager_customer = 7 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $paying_manager_customer = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $resource_name
     *           Output only. The resource name of the payments account.
     *           PaymentsAccount resource names have the form:
     *           `customers/{customer_id}/paymentsAccounts/{payments_account_id}`
     *     @type \Google\Protobuf\StringValue $payments_account_id
     *           Output only. A 16 digit ID used to identify a payments account.
     *     @type \Google\Protobuf\StringValue $name
     *           Output only. The name of the payments account.
     *     @type \Google\Protobuf\StringValue $currency_code
     *           Output only. The currency code of the payments account.
     *           A subset of the currency codes derived from the ISO 4217 standard is
     *           supported.
     *     @type \Google\Protobuf\StringValue $payments_profile_id
     *           Output only. A 12 digit ID used to identify the payments profile associated with the
     *           payments account.
     *     @type \Google\Protobuf\StringValue $secondary_payments_profile_id
     *           Output only. A secondary payments profile ID present in uncommon situations, e.g.
     *           when a sequential liability agreement has been arranged.
     *     @type \Google\Protobuf\StringValue $paying_manager_customer
     *           Output only. Paying manager of this payment account.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V3\Resources\PaymentsAccount::initOnce();
        parent::__construct($data);
    }

    /**
     * Output only. The resource name of the payments account.
     * PaymentsAccount resource names have the form:
     * `customers/{customer_id}/paymentsAccounts/{payments_account_id}`
     *
     * Generated from protobuf field <code>string resource_name = 1 [(.google.api.field_behavior) = OUTPUT_ONLY, (.google.api.resource_reference) = {</code>
     * @return string
     */
    public function getResourceName()
    {
        return $this->resource_name;
    }

    /**
     * Output only. The resource name of the payments account.
     * PaymentsAccount resource names have the form:
     * `customers/{customer_id}/paymentsAccounts/{payments_account_id}`
     *
     * Generated from protobuf field <code>string resource_name = 1 [(.google.api.field_behavior) = OUTPUT_ONLY, (.google.api.resource_reference) = {</code>
     * @param string $var
     * @return $this
     */
    public function setResourceName($var)
    {
        GPBUtil::checkString($var, True);
        $this->resource_name = $var;

        return $this;
    }

    /**
     * Output only. A 16 digit ID used to identify a payments account.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue payments_account_id = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getPaymentsAccountId()
    {
        return $this->payments_account_id;
    }

    /**
     * Returns the unboxed value from <code>getPaymentsAccountId()</code>

     * Output only. A 16 digit ID used to identify a payments account.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue payments_account_id = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return string|null
     */
    public function getPaymentsAccountIdUnwrapped()
    {
        return $this->readWrapperValue("payments_account_id");
    }

    /**
     * Output only. A 16 digit ID used to identify a payments account.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue payments_account_id = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setPaymentsAccountId($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->payments_account_id = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * Output only. A 16 digit ID used to identify a payments account.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue payments_account_id = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param string|null $var
     * @return $this
     */
    public function setPaymentsAccountIdUnwrapped($var)
    {
        $this->writeWrapperValue("payments_account_id", $var);
        return $this;}

    /**
     * Output only. The name of the payments account.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue name = 3 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the unboxed value from <code>getName()</code>

     * Output only. The name of the payments account.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue name = 3 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return string|null
     */
    public function getNameUnwrapped()
    {
        return $this->readWrapperValue("name");
    }

    /**
     * Output only. The name of the payments account.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue name = 3 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setName($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->name = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * Output only. The name of the payments account.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue name = 3 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param string|null $var
     * @return $this
     */
    public function setNameUnwrapped($var)
    {
        $this->writeWrapperValue("name", $var);
        return $this;}

    /**
     * Output only. The currency code of the payments account.
     * A subset of the currency codes derived from the ISO 4217 standard is
     * supported.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue currency_code = 4 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getCurrencyCode()
    {
        return $this->currency_code;
    }

    /**
     * Returns the unboxed value from <code>getCurrencyCode()</code>

     * Output only. The currency code of the payments account.
     * A subset of the currency codes derived from the ISO 4217 standard is
     * supported.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue currency_code = 4 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return string|null
     */
    public function getCurrencyCodeUnwrapped()
    {
        return $this->readWrapperValue("currency_code");
    }

    /**
     * Output only. The currency code of the payments account.
     * A subset of the currency codes derived from the ISO 4217 standard is
     * supported.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue currency_code = 4 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setCurrencyCode($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->currency_code = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * Output only. The currency code of the payments account.
     * A subset of the currency codes derived from the ISO 4217 standard is
     * supported.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue currency_code = 4 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param string|null $var
     * @return $this
     */
    public function setCurrencyCodeUnwrapped($var)
    {
        $this->writeWrapperValue("currency_code", $var);
        return $this;}

    /**
     * Output only. A 12 digit ID used to identify the payments profile associated with the
     * payments account.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue payments_profile_id = 5 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getPaymentsProfileId()
    {
        return $this->payments_profile_id;
    }

    /**
     * Returns the unboxed value from <code>getPaymentsProfileId()</code>

     * Output only. A 12 digit ID used to identify the payments profile associated with the
     * payments account.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue payments_profile_id = 5 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return string|null
     */
    public function getPaymentsProfileIdUnwrapped()
    {
        return $this->readWrapperValue("payments_profile_id");
    }

    /**
     * Output only. A 12 digit ID used to identify the payments profile associated with the
     * payments account.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue payments_profile_id = 5 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setPaymentsProfileId($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->payments_profile_id = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * Output only. A 12 digit ID used to identify the payments profile associated with the
     * payments account.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue payments_profile_id = 5 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param string|null $var
     * @return $this
     */
    public function setPaymentsProfileIdUnwrapped($var)
    {
        $this->writeWrapperValue("payments_profile_id", $var);
        return $this;}

    /**
     * Output only. A secondary payments profile ID present in uncommon situations, e.g.
     * when a sequential liability agreement has been arranged.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue secondary_payments_profile_id = 6 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getSecondaryPaymentsProfileId()
    {
        return $this->secondary_payments_profile_id;
    }

    /**
     * Returns the unboxed value from <code>getSecondaryPaymentsProfileId()</code>

     * Output only. A secondary payments profile ID present in uncommon situations, e.g.
     * when a sequential liability agreement has been arranged.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue secondary_payments_profile_id = 6 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return string|null
     */
    public function getSecondaryPaymentsProfileIdUnwrapped()
    {
        return $this->readWrapperValue("secondary_payments_profile_id");
    }

    /**
     * Output only. A secondary payments profile ID present in uncommon situations, e.g.
     * when a sequential liability agreement has been arranged.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue secondary_payments_profile_id = 6 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setSecondaryPaymentsProfileId($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->secondary_payments_profile_id = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * Output only. A secondary payments profile ID present in uncommon situations, e.g.
     * when a sequential liability agreement has been arranged.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue secondary_payments_profile_id = 6 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param string|null $var
     * @return $this
     */
    public function setSecondaryPaymentsProfileIdUnwrapped($var)
    {
        $this->writeWrapperValue("secondary_payments_profile_id", $var);
        return $this;}

    /**
     * Output only. Paying manager of this payment account.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue paying_manager_customer = 7 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getPayingManagerCustomer()
    {
        return $this->paying_manager_customer;
    }

    /**
     * Returns the unboxed value from <code>getPayingManagerCustomer()</code>

     * Output only. Paying manager of this payment account.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue paying_manager_customer = 7 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return string|null
     */
    public function getPayingManagerCustomerUnwrapped()
    {
        return $this->readWrapperValue("paying_manager_customer");
    }

    /**
     * Output only. Paying manager of this payment account.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue paying_manager_customer = 7 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setPayingManagerCustomer($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->paying_manager_customer = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * Output only. Paying manager of this payment account.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue paying_manager_customer = 7 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param string|null $var
     * @return $this
     */
    public function setPayingManagerCustomerUnwrapped($var)
    {
        $this->writeWrapperValue("paying_manager_customer", $var);
        return $this;}

}

