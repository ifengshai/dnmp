<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/common/user_lists.proto

namespace Google\Ads\GoogleAds\V4\Common;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Visitors of a page. The page visit is defined by one boolean rule expression.
 *
 * Generated from protobuf message <code>google.ads.googleads.v4.common.ExpressionRuleUserListInfo</code>
 */
class ExpressionRuleUserListInfo extends \Google\Protobuf\Internal\Message
{
    /**
     * Boolean rule that defines this user list. The rule consists of a list of
     * rule item groups and each rule item group consists of a list of rule items.
     * All the rule item groups are ORed or ANDed together for evaluation based on
     * rule.rule_type.
     * Required for creating an expression rule user list.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.common.UserListRuleInfo rule = 1;</code>
     */
    protected $rule = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Ads\GoogleAds\V4\Common\UserListRuleInfo $rule
     *           Boolean rule that defines this user list. The rule consists of a list of
     *           rule item groups and each rule item group consists of a list of rule items.
     *           All the rule item groups are ORed or ANDed together for evaluation based on
     *           rule.rule_type.
     *           Required for creating an expression rule user list.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V4\Common\UserLists::initOnce();
        parent::__construct($data);
    }

    /**
     * Boolean rule that defines this user list. The rule consists of a list of
     * rule item groups and each rule item group consists of a list of rule items.
     * All the rule item groups are ORed or ANDed together for evaluation based on
     * rule.rule_type.
     * Required for creating an expression rule user list.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.common.UserListRuleInfo rule = 1;</code>
     * @return \Google\Ads\GoogleAds\V4\Common\UserListRuleInfo
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * Boolean rule that defines this user list. The rule consists of a list of
     * rule item groups and each rule item group consists of a list of rule items.
     * All the rule item groups are ORed or ANDed together for evaluation based on
     * rule.rule_type.
     * Required for creating an expression rule user list.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.common.UserListRuleInfo rule = 1;</code>
     * @param \Google\Ads\GoogleAds\V4\Common\UserListRuleInfo $var
     * @return $this
     */
    public function setRule($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V4\Common\UserListRuleInfo::class);
        $this->rule = $var;

        return $this;
    }

}

