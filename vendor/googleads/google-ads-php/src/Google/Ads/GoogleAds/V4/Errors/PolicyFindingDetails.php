<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/errors/errors.proto

namespace Google\Ads\GoogleAds\V4\Errors;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Error returned as part of a mutate response.
 * This error indicates one or more policy findings in the fields of a
 * resource.
 *
 * Generated from protobuf message <code>google.ads.googleads.v4.errors.PolicyFindingDetails</code>
 */
class PolicyFindingDetails extends \Google\Protobuf\Internal\Message
{
    /**
     * The list of policy topics for the resource. Contains the PROHIBITED or
     * FULLY_LIMITED policy topic entries that prevented the resource from being
     * saved (among any other entries the resource may also have).
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v4.common.PolicyTopicEntry policy_topic_entries = 1;</code>
     */
    private $policy_topic_entries;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Ads\GoogleAds\V4\Common\PolicyTopicEntry[]|\Google\Protobuf\Internal\RepeatedField $policy_topic_entries
     *           The list of policy topics for the resource. Contains the PROHIBITED or
     *           FULLY_LIMITED policy topic entries that prevented the resource from being
     *           saved (among any other entries the resource may also have).
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V4\Errors\Errors::initOnce();
        parent::__construct($data);
    }

    /**
     * The list of policy topics for the resource. Contains the PROHIBITED or
     * FULLY_LIMITED policy topic entries that prevented the resource from being
     * saved (among any other entries the resource may also have).
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v4.common.PolicyTopicEntry policy_topic_entries = 1;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getPolicyTopicEntries()
    {
        return $this->policy_topic_entries;
    }

    /**
     * The list of policy topics for the resource. Contains the PROHIBITED or
     * FULLY_LIMITED policy topic entries that prevented the resource from being
     * saved (among any other entries the resource may also have).
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v4.common.PolicyTopicEntry policy_topic_entries = 1;</code>
     * @param \Google\Ads\GoogleAds\V4\Common\PolicyTopicEntry[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setPolicyTopicEntries($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Ads\GoogleAds\V4\Common\PolicyTopicEntry::class);
        $this->policy_topic_entries = $arr;

        return $this;
    }

}

