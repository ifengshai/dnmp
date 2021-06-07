<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/resources/campaign.proto

namespace Google\Ads\GoogleAds\V5\Resources\Campaign;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * The setting for controlling Dynamic Search Ads (DSA).
 *
 * Generated from protobuf message <code>google.ads.googleads.v5.resources.Campaign.DynamicSearchAdsSetting</code>
 */
class DynamicSearchAdsSetting extends \Google\Protobuf\Internal\Message
{
    /**
     * The Internet domain name that this setting represents, e.g., "google.com"
     * or "www.google.com".
     *
     * Generated from protobuf field <code>string domain_name = 6;</code>
     */
    protected $domain_name = null;
    /**
     * The language code specifying the language of the domain, e.g., "en".
     *
     * Generated from protobuf field <code>string language_code = 7;</code>
     */
    protected $language_code = null;
    /**
     * Whether the campaign uses advertiser supplied URLs exclusively.
     *
     * Generated from protobuf field <code>bool use_supplied_urls_only = 8;</code>
     */
    protected $use_supplied_urls_only = null;
    /**
     * The list of page feeds associated with the campaign.
     *
     * Generated from protobuf field <code>repeated string feeds = 9 [(.google.api.resource_reference) = {</code>
     */
    private $feeds;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $domain_name
     *           The Internet domain name that this setting represents, e.g., "google.com"
     *           or "www.google.com".
     *     @type string $language_code
     *           The language code specifying the language of the domain, e.g., "en".
     *     @type bool $use_supplied_urls_only
     *           Whether the campaign uses advertiser supplied URLs exclusively.
     *     @type string[]|\Google\Protobuf\Internal\RepeatedField $feeds
     *           The list of page feeds associated with the campaign.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V5\Resources\Campaign::initOnce();
        parent::__construct($data);
    }

    /**
     * The Internet domain name that this setting represents, e.g., "google.com"
     * or "www.google.com".
     *
     * Generated from protobuf field <code>string domain_name = 6;</code>
     * @return string
     */
    public function getDomainName()
    {
        return isset($this->domain_name) ? $this->domain_name : '';
    }

    public function hasDomainName()
    {
        return isset($this->domain_name);
    }

    public function clearDomainName()
    {
        unset($this->domain_name);
    }

    /**
     * The Internet domain name that this setting represents, e.g., "google.com"
     * or "www.google.com".
     *
     * Generated from protobuf field <code>string domain_name = 6;</code>
     * @param string $var
     * @return $this
     */
    public function setDomainName($var)
    {
        GPBUtil::checkString($var, True);
        $this->domain_name = $var;

        return $this;
    }

    /**
     * The language code specifying the language of the domain, e.g., "en".
     *
     * Generated from protobuf field <code>string language_code = 7;</code>
     * @return string
     */
    public function getLanguageCode()
    {
        return isset($this->language_code) ? $this->language_code : '';
    }

    public function hasLanguageCode()
    {
        return isset($this->language_code);
    }

    public function clearLanguageCode()
    {
        unset($this->language_code);
    }

    /**
     * The language code specifying the language of the domain, e.g., "en".
     *
     * Generated from protobuf field <code>string language_code = 7;</code>
     * @param string $var
     * @return $this
     */
    public function setLanguageCode($var)
    {
        GPBUtil::checkString($var, True);
        $this->language_code = $var;

        return $this;
    }

    /**
     * Whether the campaign uses advertiser supplied URLs exclusively.
     *
     * Generated from protobuf field <code>bool use_supplied_urls_only = 8;</code>
     * @return bool
     */
    public function getUseSuppliedUrlsOnly()
    {
        return isset($this->use_supplied_urls_only) ? $this->use_supplied_urls_only : false;
    }

    public function hasUseSuppliedUrlsOnly()
    {
        return isset($this->use_supplied_urls_only);
    }

    public function clearUseSuppliedUrlsOnly()
    {
        unset($this->use_supplied_urls_only);
    }

    /**
     * Whether the campaign uses advertiser supplied URLs exclusively.
     *
     * Generated from protobuf field <code>bool use_supplied_urls_only = 8;</code>
     * @param bool $var
     * @return $this
     */
    public function setUseSuppliedUrlsOnly($var)
    {
        GPBUtil::checkBool($var);
        $this->use_supplied_urls_only = $var;

        return $this;
    }

    /**
     * The list of page feeds associated with the campaign.
     *
     * Generated from protobuf field <code>repeated string feeds = 9 [(.google.api.resource_reference) = {</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getFeeds()
    {
        return $this->feeds;
    }

    /**
     * The list of page feeds associated with the campaign.
     *
     * Generated from protobuf field <code>repeated string feeds = 9 [(.google.api.resource_reference) = {</code>
     * @param string[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setFeeds($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::STRING);
        $this->feeds = $arr;

        return $this;
    }

}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(DynamicSearchAdsSetting::class, \Google\Ads\GoogleAds\V5\Resources\Campaign_DynamicSearchAdsSetting::class);

