<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/resources/billing_setup.proto

namespace Google\Ads\GoogleAds\V5\Resources\BillingSetup;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Container of payments account information for this billing.
 *
 * Generated from protobuf message <code>google.ads.googleads.v5.resources.BillingSetup.PaymentsAccountInfo</code>
 */
class PaymentsAccountInfo extends \Google\Protobuf\Internal\Message
{
    /**
     * Output only. A 16 digit id used to identify the payments account associated with the
     * billing setup.
     * This must be passed as a string with dashes, e.g. "1234-5678-9012-3456".
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue payments_account_id = 1 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $payments_account_id = null;
    /**
     * Immutable. The name of the payments account associated with the billing setup.
     * This enables the user to specify a meaningful name for a payments account
     * to aid in reconciling monthly invoices.
     * This name will be printed in the monthly invoices.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue payments_account_name = 2 [(.google.api.field_behavior) = IMMUTABLE];</code>
     */
    protected $payments_account_name = null;
    /**
     * Immutable. A 12 digit id used to identify the payments profile associated with the
     * billing setup.
     * This must be passed in as a string with dashes, e.g. "1234-5678-9012".
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue payments_profile_id = 3 [(.google.api.field_behavior) = IMMUTABLE];</code>
     */
    protected $payments_profile_id = null;
    /**
     * Output only. The name of the payments profile associated with the billing setup.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue payments_profile_name = 4 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $payments_profile_name = null;
    /**
     * Output only. A secondary payments profile id present in uncommon situations, e.g.
     * when a sequential liability agreement has been arranged.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue secondary_payments_profile_id = 5 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $secondary_payments_profile_id = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Protobuf\StringValue $payments_account_id
     *           Output only. A 16 digit id used to identify the payments account associated with the
     *           billing setup.
     *           This must be passed as a string with dashes, e.g. "1234-5678-9012-3456".
     *     @type \Google\Protobuf\StringValue $payments_account_name
     *           Immutable. The name of the payments account associated with the billing setup.
     *           This enables the user to specify a meaningful name for a payments account
     *           to aid in reconciling monthly invoices.
     *           This name will be printed in the monthly invoices.
     *     @type \Google\Protobuf\StringValue $payments_profile_id
     *           Immutable. A 12 digit id used to identify the payments profile associated with the
     *           billing setup.
     *           This must be passed in as a string with dashes, e.g. "1234-5678-9012".
     *     @type \Google\Protobuf\StringValue $payments_profile_name
     *           Output only. The name of the payments profile associated with the billing setup.
     *     @type \Google\Protobuf\StringValue $secondary_payments_profile_id
     *           Output only. A secondary payments profile id present in uncommon situations, e.g.
     *           when a sequential liability agreement has been arranged.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V5\Resources\BillingSetup::initOnce();
        parent::__construct($data);
    }

    /**
     * Output only. A 16 digit id used to identify the payments account associated with the
     * billing setup.
     * This must be passed as a string with dashes, e.g. "1234-5678-9012-3456".
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue payments_account_id = 1 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getPaymentsAccountId()
    {
        return isset($this->payments_account_id) ? $this->payments_account_id : null;
    }

    public function hasPaymentsAccountId()
    {
        return isset($this->payments_account_id);
    }

    public function clearPaymentsAccountId()
    {
        unset($this->payments_account_id);
    }

    /**
     * Returns the unboxed value from <code>getPaymentsAccountId()</code>

     * Output only. A 16 digit id used to identify the payments account associated with the
     * billing setup.
     * This must be passed as a string with dashes, e.g. "1234-5678-9012-3456".
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue payments_account_id = 1 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return string|null
     */
    public function getPaymentsAccountIdUnwrapped()
    {
        return $this->readWrapperValue("payments_account_id");
    }

    /**
     * Output only. A 16 digit id used to identify the payments account associated with the
     * billing setup.
     * This must be passed as a string with dashes, e.g. "1234-5678-9012-3456".
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue payments_account_id = 1 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
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

     * Output only. A 16 digit id used to identify the payments account associated with the
     * billing setup.
     * This must be passed as a string with dashes, e.g. "1234-5678-9012-3456".
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue payments_account_id = 1 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param string|null $var
     * @return $this
     */
    public function setPaymentsAccountIdUnwrapped($var)
    {
        $this->writeWrapperValue("payments_account_id", $var);
        return $this;}

    /**
     * Immutable. The name of the payments account associated with the billing setup.
     * This enables the user to specify a meaningful name for a payments account
     * to aid in reconciling monthly invoices.
     * This name will be printed in the monthly invoices.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue payments_account_name = 2 [(.google.api.field_behavior) = IMMUTABLE];</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getPaymentsAccountName()
    {
        return isset($this->payments_account_name) ? $this->payments_account_name : null;
    }

    public function hasPaymentsAccountName()
    {
        return isset($this->payments_account_name);
    }

    public function clearPaymentsAccountName()
    {
        unset($this->payments_account_name);
    }

    /**
     * Returns the unboxed value from <code>getPaymentsAccountName()</code>

     * Immutable. The name of the payments account associated with the billing setup.
     * This enables the user to specify a meaningful name for a payments account
     * to aid in reconciling monthly invoices.
     * This name will be printed in the monthly invoices.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue payments_account_name = 2 [(.google.api.field_behavior) = IMMUTABLE];</code>
     * @return string|null
     */
    public function getPaymentsAccountNameUnwrapped()
    {
        return $this->readWrapperValue("payments_account_name");
    }

    /**
     * Immutable. The name of the payments account associated with the billing setup.
     * This enables the user to specify a meaningful name for a payments account
     * to aid in reconciling monthly invoices.
     * This name will be printed in the monthly invoices.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue payments_account_name = 2 [(.google.api.field_behavior) = IMMUTABLE];</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setPaymentsAccountName($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->payments_account_name = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * Immutable. The name of the payments account associated with the billing setup.
     * This enables the user to specify a meaningful name for a payments account
     * to aid in reconciling monthly invoices.
     * This name will be printed in the monthly invoices.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue payments_account_name = 2 [(.google.api.field_behavior) = IMMUTABLE];</code>
     * @param string|null $var
     * @return $this
     */
    public function setPaymentsAccountNameUnwrapped($var)
    {
        $this->writeWrapperValue("payments_account_name", $var);
        return $this;}

    /**
     * Immutable. A 12 digit id used to identify the payments profile associated with the
     * billing setup.
     * This must be passed in as a string with dashes, e.g. "1234-5678-9012".
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue payments_profile_id = 3 [(.google.api.field_behavior) = IMMUTABLE];</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getPaymentsProfileId()
    {
        return isset($this->payments_profile_id) ? $this->payments_profile_id : null;
    }

    public function hasPaymentsProfileId()
    {
        return isset($this->payments_profile_id);
    }

    public function clearPaymentsProfileId()
    {
        unset($this->payments_profile_id);
    }

    /**
     * Returns the unboxed value from <code>getPaymentsProfileId()</code>

     * Immutable. A 12 digit id used to identify the payments profile associated with the
     * billing setup.
     * This must be passed in as a string with dashes, e.g. "1234-5678-9012".
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue payments_profile_id = 3 [(.google.api.field_behavior) = IMMUTABLE];</code>
     * @return string|null
     */
    public function getPaymentsProfileIdUnwrapped()
    {
        return $this->readWrapperValue("payments_profile_id");
    }

    /**
     * Immutable. A 12 digit id used to identify the payments profile associated with the
     * billing setup.
     * This must be passed in as a string with dashes, e.g. "1234-5678-9012".
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue payments_profile_id = 3 [(.google.api.field_behavior) = IMMUTABLE];</code>
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

     * Immutable. A 12 digit id used to identify the payments profile associated with the
     * billing setup.
     * This must be passed in as a string with dashes, e.g. "1234-5678-9012".
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue payments_profile_id = 3 [(.google.api.field_behavior) = IMMUTABLE];</code>
     * @param string|null $var
     * @return $this
     */
    public function setPaymentsProfileIdUnwrapped($var)
    {
        $this->writeWrapperValue("payments_profile_id", $var);
        return $this;}

    /**
     * Output only. The name of the payments profile associated with the billing setup.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue payments_profile_name = 4 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getPaymentsProfileName()
    {
        return isset($this->payments_profile_name) ? $this->payments_profile_name : null;
    }

    public function hasPaymentsProfileName()
    {
        return isset($this->payments_profile_name);
    }

    public function clearPaymentsProfileName()
    {
        unset($this->payments_profile_name);
    }

    /**
     * Returns the unboxed value from <code>getPaymentsProfileName()</code>

     * Output only. The name of the payments profile associated with the billing setup.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue payments_profile_name = 4 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return string|null
     */
    public function getPaymentsProfileNameUnwrapped()
    {
        return $this->readWrapperValue("payments_profile_name");
    }

    /**
     * Output only. The name of the payments profile associated with the billing setup.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue payments_profile_name = 4 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setPaymentsProfileName($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->payments_profile_name = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * Output only. The name of the payments profile associated with the billing setup.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue payments_profile_name = 4 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param string|null $var
     * @return $this
     */
    public function setPaymentsProfileNameUnwrapped($var)
    {
        $this->writeWrapperValue("payments_profile_name", $var);
        return $this;}

    /**
     * Output only. A secondary payments profile id present in uncommon situations, e.g.
     * when a sequential liability agreement has been arranged.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue secondary_payments_profile_id = 5 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getSecondaryPaymentsProfileId()
    {
        return isset($this->secondary_payments_profile_id) ? $this->secondary_payments_profile_id : null;
    }

    public function hasSecondaryPaymentsProfileId()
    {
        return isset($this->secondary_payments_profile_id);
    }

    public function clearSecondaryPaymentsProfileId()
    {
        unset($this->secondary_payments_profile_id);
    }

    /**
     * Returns the unboxed value from <code>getSecondaryPaymentsProfileId()</code>

     * Output only. A secondary payments profile id present in uncommon situations, e.g.
     * when a sequential liability agreement has been arranged.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue secondary_payments_profile_id = 5 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return string|null
     */
    public function getSecondaryPaymentsProfileIdUnwrapped()
    {
        return $this->readWrapperValue("secondary_payments_profile_id");
    }

    /**
     * Output only. A secondary payments profile id present in uncommon situations, e.g.
     * when a sequential liability agreement has been arranged.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue secondary_payments_profile_id = 5 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
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

     * Output only. A secondary payments profile id present in uncommon situations, e.g.
     * when a sequential liability agreement has been arranged.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue secondary_payments_profile_id = 5 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param string|null $var
     * @return $this
     */
    public function setSecondaryPaymentsProfileIdUnwrapped($var)
    {
        $this->writeWrapperValue("secondary_payments_profile_id", $var);
        return $this;}

}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(PaymentsAccountInfo::class, \Google\Ads\GoogleAds\V5\Resources\BillingSetup_PaymentsAccountInfo::class);

