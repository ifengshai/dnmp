<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/common/policy.proto

namespace Google\Ads\GoogleAds\V5\Common;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Key of the violation. The key is used for referring to a violation
 * when filing an exemption request.
 *
 * Generated from protobuf message <code>google.ads.googleads.v5.common.PolicyViolationKey</code>
 */
class PolicyViolationKey extends \Google\Protobuf\Internal\Message
{
    /**
     * Unique ID of the violated policy.
     *
     * Generated from protobuf field <code>string policy_name = 3;</code>
     */
    protected $policy_name = null;
    /**
     * The text that violates the policy if specified.
     * Otherwise, refers to the policy in general
     * (e.g., when requesting to be exempt from the whole policy).
     * If not specified for criterion exemptions, the whole policy is implied.
     * Must be specified for ad exemptions.
     *
     * Generated from protobuf field <code>string violating_text = 4;</code>
     */
    protected $violating_text = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $policy_name
     *           Unique ID of the violated policy.
     *     @type string $violating_text
     *           The text that violates the policy if specified.
     *           Otherwise, refers to the policy in general
     *           (e.g., when requesting to be exempt from the whole policy).
     *           If not specified for criterion exemptions, the whole policy is implied.
     *           Must be specified for ad exemptions.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V5\Common\Policy::initOnce();
        parent::__construct($data);
    }

    /**
     * Unique ID of the violated policy.
     *
     * Generated from protobuf field <code>string policy_name = 3;</code>
     * @return string
     */
    public function getPolicyName()
    {
        return isset($this->policy_name) ? $this->policy_name : '';
    }

    public function hasPolicyName()
    {
        return isset($this->policy_name);
    }

    public function clearPolicyName()
    {
        unset($this->policy_name);
    }

    /**
     * Unique ID of the violated policy.
     *
     * Generated from protobuf field <code>string policy_name = 3;</code>
     * @param string $var
     * @return $this
     */
    public function setPolicyName($var)
    {
        GPBUtil::checkString($var, True);
        $this->policy_name = $var;

        return $this;
    }

    /**
     * The text that violates the policy if specified.
     * Otherwise, refers to the policy in general
     * (e.g., when requesting to be exempt from the whole policy).
     * If not specified for criterion exemptions, the whole policy is implied.
     * Must be specified for ad exemptions.
     *
     * Generated from protobuf field <code>string violating_text = 4;</code>
     * @return string
     */
    public function getViolatingText()
    {
        return isset($this->violating_text) ? $this->violating_text : '';
    }

    public function hasViolatingText()
    {
        return isset($this->violating_text);
    }

    public function clearViolatingText()
    {
        unset($this->violating_text);
    }

    /**
     * The text that violates the policy if specified.
     * Otherwise, refers to the policy in general
     * (e.g., when requesting to be exempt from the whole policy).
     * If not specified for criterion exemptions, the whole policy is implied.
     * Must be specified for ad exemptions.
     *
     * Generated from protobuf field <code>string violating_text = 4;</code>
     * @param string $var
     * @return $this
     */
    public function setViolatingText($var)
    {
        GPBUtil::checkString($var, True);
        $this->violating_text = $var;

        return $this;
    }

}

