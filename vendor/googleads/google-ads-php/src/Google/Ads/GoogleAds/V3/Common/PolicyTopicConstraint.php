<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/common/policy.proto

namespace Google\Ads\GoogleAds\V3\Common;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Describes the effect on serving that a policy topic entry will have.
 *
 * Generated from protobuf message <code>google.ads.googleads.v3.common.PolicyTopicConstraint</code>
 */
class PolicyTopicConstraint extends \Google\Protobuf\Internal\Message
{
    protected $value;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Ads\GoogleAds\V3\Common\PolicyTopicConstraint\CountryConstraintList $country_constraint_list
     *           Countries where the resource cannot serve.
     *     @type \Google\Ads\GoogleAds\V3\Common\PolicyTopicConstraint\ResellerConstraint $reseller_constraint
     *           Reseller constraint.
     *     @type \Google\Ads\GoogleAds\V3\Common\PolicyTopicConstraint\CountryConstraintList $certificate_missing_in_country_list
     *           Countries where a certificate is required for serving.
     *     @type \Google\Ads\GoogleAds\V3\Common\PolicyTopicConstraint\CountryConstraintList $certificate_domain_mismatch_in_country_list
     *           Countries where the resource's domain is not covered by the
     *           certificates associated with it.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V3\Common\Policy::initOnce();
        parent::__construct($data);
    }

    /**
     * Countries where the resource cannot serve.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v3.common.PolicyTopicConstraint.CountryConstraintList country_constraint_list = 1;</code>
     * @return \Google\Ads\GoogleAds\V3\Common\PolicyTopicConstraint\CountryConstraintList
     */
    public function getCountryConstraintList()
    {
        return $this->readOneof(1);
    }

    /**
     * Countries where the resource cannot serve.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v3.common.PolicyTopicConstraint.CountryConstraintList country_constraint_list = 1;</code>
     * @param \Google\Ads\GoogleAds\V3\Common\PolicyTopicConstraint\CountryConstraintList $var
     * @return $this
     */
    public function setCountryConstraintList($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V3\Common\PolicyTopicConstraint_CountryConstraintList::class);
        $this->writeOneof(1, $var);

        return $this;
    }

    /**
     * Reseller constraint.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v3.common.PolicyTopicConstraint.ResellerConstraint reseller_constraint = 2;</code>
     * @return \Google\Ads\GoogleAds\V3\Common\PolicyTopicConstraint\ResellerConstraint
     */
    public function getResellerConstraint()
    {
        return $this->readOneof(2);
    }

    /**
     * Reseller constraint.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v3.common.PolicyTopicConstraint.ResellerConstraint reseller_constraint = 2;</code>
     * @param \Google\Ads\GoogleAds\V3\Common\PolicyTopicConstraint\ResellerConstraint $var
     * @return $this
     */
    public function setResellerConstraint($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V3\Common\PolicyTopicConstraint_ResellerConstraint::class);
        $this->writeOneof(2, $var);

        return $this;
    }

    /**
     * Countries where a certificate is required for serving.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v3.common.PolicyTopicConstraint.CountryConstraintList certificate_missing_in_country_list = 3;</code>
     * @return \Google\Ads\GoogleAds\V3\Common\PolicyTopicConstraint\CountryConstraintList
     */
    public function getCertificateMissingInCountryList()
    {
        return $this->readOneof(3);
    }

    /**
     * Countries where a certificate is required for serving.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v3.common.PolicyTopicConstraint.CountryConstraintList certificate_missing_in_country_list = 3;</code>
     * @param \Google\Ads\GoogleAds\V3\Common\PolicyTopicConstraint\CountryConstraintList $var
     * @return $this
     */
    public function setCertificateMissingInCountryList($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V3\Common\PolicyTopicConstraint_CountryConstraintList::class);
        $this->writeOneof(3, $var);

        return $this;
    }

    /**
     * Countries where the resource's domain is not covered by the
     * certificates associated with it.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v3.common.PolicyTopicConstraint.CountryConstraintList certificate_domain_mismatch_in_country_list = 4;</code>
     * @return \Google\Ads\GoogleAds\V3\Common\PolicyTopicConstraint\CountryConstraintList
     */
    public function getCertificateDomainMismatchInCountryList()
    {
        return $this->readOneof(4);
    }

    /**
     * Countries where the resource's domain is not covered by the
     * certificates associated with it.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v3.common.PolicyTopicConstraint.CountryConstraintList certificate_domain_mismatch_in_country_list = 4;</code>
     * @param \Google\Ads\GoogleAds\V3\Common\PolicyTopicConstraint\CountryConstraintList $var
     * @return $this
     */
    public function setCertificateDomainMismatchInCountryList($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V3\Common\PolicyTopicConstraint_CountryConstraintList::class);
        $this->writeOneof(4, $var);

        return $this;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->whichOneof("value");
    }

}

