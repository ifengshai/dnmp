<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/common/extensions.proto

namespace Google\Ads\GoogleAds\V3\Common;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Represents a sitelink extension.
 *
 * Generated from protobuf message <code>google.ads.googleads.v3.common.SitelinkFeedItem</code>
 */
class SitelinkFeedItem extends \Google\Protobuf\Internal\Message
{
    /**
     * URL display text for the sitelink.
     * The length of this string should be between 1 and 25, inclusive.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue link_text = 1;</code>
     */
    protected $link_text = null;
    /**
     * First line of the description for the sitelink.
     * If this value is set, line2 must also be set.
     * The length of this string should be between 0 and 35, inclusive.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue line1 = 2;</code>
     */
    protected $line1 = null;
    /**
     * Second line of the description for the sitelink.
     * If this value is set, line1 must also be set.
     * The length of this string should be between 0 and 35, inclusive.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue line2 = 3;</code>
     */
    protected $line2 = null;
    /**
     * A list of possible final URLs after all cross domain redirects.
     *
     * Generated from protobuf field <code>repeated .google.protobuf.StringValue final_urls = 4;</code>
     */
    private $final_urls;
    /**
     * A list of possible final mobile URLs after all cross domain redirects.
     *
     * Generated from protobuf field <code>repeated .google.protobuf.StringValue final_mobile_urls = 5;</code>
     */
    private $final_mobile_urls;
    /**
     * URL template for constructing a tracking URL.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue tracking_url_template = 6;</code>
     */
    protected $tracking_url_template = null;
    /**
     * A list of mappings to be used for substituting URL custom parameter tags in
     * the tracking_url_template, final_urls, and/or final_mobile_urls.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v3.common.CustomParameter url_custom_parameters = 7;</code>
     */
    private $url_custom_parameters;
    /**
     * Final URL suffix to be appended to landing page URLs served with
     * parallel tracking.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue final_url_suffix = 8;</code>
     */
    protected $final_url_suffix = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Protobuf\StringValue $link_text
     *           URL display text for the sitelink.
     *           The length of this string should be between 1 and 25, inclusive.
     *     @type \Google\Protobuf\StringValue $line1
     *           First line of the description for the sitelink.
     *           If this value is set, line2 must also be set.
     *           The length of this string should be between 0 and 35, inclusive.
     *     @type \Google\Protobuf\StringValue $line2
     *           Second line of the description for the sitelink.
     *           If this value is set, line1 must also be set.
     *           The length of this string should be between 0 and 35, inclusive.
     *     @type \Google\Protobuf\StringValue[]|\Google\Protobuf\Internal\RepeatedField $final_urls
     *           A list of possible final URLs after all cross domain redirects.
     *     @type \Google\Protobuf\StringValue[]|\Google\Protobuf\Internal\RepeatedField $final_mobile_urls
     *           A list of possible final mobile URLs after all cross domain redirects.
     *     @type \Google\Protobuf\StringValue $tracking_url_template
     *           URL template for constructing a tracking URL.
     *     @type \Google\Ads\GoogleAds\V3\Common\CustomParameter[]|\Google\Protobuf\Internal\RepeatedField $url_custom_parameters
     *           A list of mappings to be used for substituting URL custom parameter tags in
     *           the tracking_url_template, final_urls, and/or final_mobile_urls.
     *     @type \Google\Protobuf\StringValue $final_url_suffix
     *           Final URL suffix to be appended to landing page URLs served with
     *           parallel tracking.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V3\Common\Extensions::initOnce();
        parent::__construct($data);
    }

    /**
     * URL display text for the sitelink.
     * The length of this string should be between 1 and 25, inclusive.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue link_text = 1;</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getLinkText()
    {
        return $this->link_text;
    }

    /**
     * Returns the unboxed value from <code>getLinkText()</code>

     * URL display text for the sitelink.
     * The length of this string should be between 1 and 25, inclusive.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue link_text = 1;</code>
     * @return string|null
     */
    public function getLinkTextUnwrapped()
    {
        return $this->readWrapperValue("link_text");
    }

    /**
     * URL display text for the sitelink.
     * The length of this string should be between 1 and 25, inclusive.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue link_text = 1;</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setLinkText($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->link_text = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * URL display text for the sitelink.
     * The length of this string should be between 1 and 25, inclusive.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue link_text = 1;</code>
     * @param string|null $var
     * @return $this
     */
    public function setLinkTextUnwrapped($var)
    {
        $this->writeWrapperValue("link_text", $var);
        return $this;}

    /**
     * First line of the description for the sitelink.
     * If this value is set, line2 must also be set.
     * The length of this string should be between 0 and 35, inclusive.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue line1 = 2;</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getLine1()
    {
        return $this->line1;
    }

    /**
     * Returns the unboxed value from <code>getLine1()</code>

     * First line of the description for the sitelink.
     * If this value is set, line2 must also be set.
     * The length of this string should be between 0 and 35, inclusive.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue line1 = 2;</code>
     * @return string|null
     */
    public function getLine1Unwrapped()
    {
        return $this->readWrapperValue("line1");
    }

    /**
     * First line of the description for the sitelink.
     * If this value is set, line2 must also be set.
     * The length of this string should be between 0 and 35, inclusive.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue line1 = 2;</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setLine1($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->line1 = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * First line of the description for the sitelink.
     * If this value is set, line2 must also be set.
     * The length of this string should be between 0 and 35, inclusive.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue line1 = 2;</code>
     * @param string|null $var
     * @return $this
     */
    public function setLine1Unwrapped($var)
    {
        $this->writeWrapperValue("line1", $var);
        return $this;}

    /**
     * Second line of the description for the sitelink.
     * If this value is set, line1 must also be set.
     * The length of this string should be between 0 and 35, inclusive.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue line2 = 3;</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getLine2()
    {
        return $this->line2;
    }

    /**
     * Returns the unboxed value from <code>getLine2()</code>

     * Second line of the description for the sitelink.
     * If this value is set, line1 must also be set.
     * The length of this string should be between 0 and 35, inclusive.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue line2 = 3;</code>
     * @return string|null
     */
    public function getLine2Unwrapped()
    {
        return $this->readWrapperValue("line2");
    }

    /**
     * Second line of the description for the sitelink.
     * If this value is set, line1 must also be set.
     * The length of this string should be between 0 and 35, inclusive.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue line2 = 3;</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setLine2($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->line2 = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * Second line of the description for the sitelink.
     * If this value is set, line1 must also be set.
     * The length of this string should be between 0 and 35, inclusive.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue line2 = 3;</code>
     * @param string|null $var
     * @return $this
     */
    public function setLine2Unwrapped($var)
    {
        $this->writeWrapperValue("line2", $var);
        return $this;}

    /**
     * A list of possible final URLs after all cross domain redirects.
     *
     * Generated from protobuf field <code>repeated .google.protobuf.StringValue final_urls = 4;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getFinalUrls()
    {
        return $this->final_urls;
    }

    /**
     * A list of possible final URLs after all cross domain redirects.
     *
     * Generated from protobuf field <code>repeated .google.protobuf.StringValue final_urls = 4;</code>
     * @param \Google\Protobuf\StringValue[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setFinalUrls($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Protobuf\StringValue::class);
        $this->final_urls = $arr;

        return $this;
    }

    /**
     * A list of possible final mobile URLs after all cross domain redirects.
     *
     * Generated from protobuf field <code>repeated .google.protobuf.StringValue final_mobile_urls = 5;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getFinalMobileUrls()
    {
        return $this->final_mobile_urls;
    }

    /**
     * A list of possible final mobile URLs after all cross domain redirects.
     *
     * Generated from protobuf field <code>repeated .google.protobuf.StringValue final_mobile_urls = 5;</code>
     * @param \Google\Protobuf\StringValue[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setFinalMobileUrls($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Protobuf\StringValue::class);
        $this->final_mobile_urls = $arr;

        return $this;
    }

    /**
     * URL template for constructing a tracking URL.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue tracking_url_template = 6;</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getTrackingUrlTemplate()
    {
        return $this->tracking_url_template;
    }

    /**
     * Returns the unboxed value from <code>getTrackingUrlTemplate()</code>

     * URL template for constructing a tracking URL.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue tracking_url_template = 6;</code>
     * @return string|null
     */
    public function getTrackingUrlTemplateUnwrapped()
    {
        return $this->readWrapperValue("tracking_url_template");
    }

    /**
     * URL template for constructing a tracking URL.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue tracking_url_template = 6;</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setTrackingUrlTemplate($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->tracking_url_template = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * URL template for constructing a tracking URL.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue tracking_url_template = 6;</code>
     * @param string|null $var
     * @return $this
     */
    public function setTrackingUrlTemplateUnwrapped($var)
    {
        $this->writeWrapperValue("tracking_url_template", $var);
        return $this;}

    /**
     * A list of mappings to be used for substituting URL custom parameter tags in
     * the tracking_url_template, final_urls, and/or final_mobile_urls.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v3.common.CustomParameter url_custom_parameters = 7;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getUrlCustomParameters()
    {
        return $this->url_custom_parameters;
    }

    /**
     * A list of mappings to be used for substituting URL custom parameter tags in
     * the tracking_url_template, final_urls, and/or final_mobile_urls.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v3.common.CustomParameter url_custom_parameters = 7;</code>
     * @param \Google\Ads\GoogleAds\V3\Common\CustomParameter[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setUrlCustomParameters($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Ads\GoogleAds\V3\Common\CustomParameter::class);
        $this->url_custom_parameters = $arr;

        return $this;
    }

    /**
     * Final URL suffix to be appended to landing page URLs served with
     * parallel tracking.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue final_url_suffix = 8;</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getFinalUrlSuffix()
    {
        return $this->final_url_suffix;
    }

    /**
     * Returns the unboxed value from <code>getFinalUrlSuffix()</code>

     * Final URL suffix to be appended to landing page URLs served with
     * parallel tracking.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue final_url_suffix = 8;</code>
     * @return string|null
     */
    public function getFinalUrlSuffixUnwrapped()
    {
        return $this->readWrapperValue("final_url_suffix");
    }

    /**
     * Final URL suffix to be appended to landing page URLs served with
     * parallel tracking.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue final_url_suffix = 8;</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setFinalUrlSuffix($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->final_url_suffix = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * Final URL suffix to be appended to landing page URLs served with
     * parallel tracking.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue final_url_suffix = 8;</code>
     * @param string|null $var
     * @return $this
     */
    public function setFinalUrlSuffixUnwrapped($var)
    {
        $this->writeWrapperValue("final_url_suffix", $var);
        return $this;}

}

