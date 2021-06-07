<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/resources/dynamic_search_ads_search_term_view.proto

namespace Google\Ads\GoogleAds\V4\Resources;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * A dynamic search ads search term view.
 *
 * Generated from protobuf message <code>google.ads.googleads.v4.resources.DynamicSearchAdsSearchTermView</code>
 */
class DynamicSearchAdsSearchTermView extends \Google\Protobuf\Internal\Message
{
    /**
     * Output only. The resource name of the dynamic search ads search term view.
     * Dynamic search ads search term view resource names have the form:
     * `customers/{customer_id}/dynamicSearchAdsSearchTermViews/{ad_group_id}~{search_term_fp}~{headline_fp}~{landing_page_fp}~{page_url_fp}`
     *
     * Generated from protobuf field <code>string resource_name = 1 [(.google.api.field_behavior) = OUTPUT_ONLY, (.google.api.resource_reference) = {</code>
     */
    protected $resource_name = '';
    /**
     * Output only. Search term
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue search_term = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $search_term = null;
    /**
     * Output only. The dynamically generated headline of the Dynamic Search Ad.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue headline = 3 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $headline = null;
    /**
     * Output only. The dynamically selected landing page URL of the impression.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue landing_page = 4 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $landing_page = null;
    /**
     * Output only. The URL of page feed item served for the impression.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue page_url = 5 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $page_url = null;
    /**
     * Output only. True if query matches a negative keyword.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.BoolValue has_negative_keyword = 6 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $has_negative_keyword = null;
    /**
     * Output only. True if query is added to targeted keywords.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.BoolValue has_matching_keyword = 7 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $has_matching_keyword = null;
    /**
     * Output only. True if query matches a negative url.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.BoolValue has_negative_url = 8 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    protected $has_negative_url = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $resource_name
     *           Output only. The resource name of the dynamic search ads search term view.
     *           Dynamic search ads search term view resource names have the form:
     *           `customers/{customer_id}/dynamicSearchAdsSearchTermViews/{ad_group_id}~{search_term_fp}~{headline_fp}~{landing_page_fp}~{page_url_fp}`
     *     @type \Google\Protobuf\StringValue $search_term
     *           Output only. Search term
     *           This field is read-only.
     *     @type \Google\Protobuf\StringValue $headline
     *           Output only. The dynamically generated headline of the Dynamic Search Ad.
     *           This field is read-only.
     *     @type \Google\Protobuf\StringValue $landing_page
     *           Output only. The dynamically selected landing page URL of the impression.
     *           This field is read-only.
     *     @type \Google\Protobuf\StringValue $page_url
     *           Output only. The URL of page feed item served for the impression.
     *           This field is read-only.
     *     @type \Google\Protobuf\BoolValue $has_negative_keyword
     *           Output only. True if query matches a negative keyword.
     *           This field is read-only.
     *     @type \Google\Protobuf\BoolValue $has_matching_keyword
     *           Output only. True if query is added to targeted keywords.
     *           This field is read-only.
     *     @type \Google\Protobuf\BoolValue $has_negative_url
     *           Output only. True if query matches a negative url.
     *           This field is read-only.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V4\Resources\DynamicSearchAdsSearchTermView::initOnce();
        parent::__construct($data);
    }

    /**
     * Output only. The resource name of the dynamic search ads search term view.
     * Dynamic search ads search term view resource names have the form:
     * `customers/{customer_id}/dynamicSearchAdsSearchTermViews/{ad_group_id}~{search_term_fp}~{headline_fp}~{landing_page_fp}~{page_url_fp}`
     *
     * Generated from protobuf field <code>string resource_name = 1 [(.google.api.field_behavior) = OUTPUT_ONLY, (.google.api.resource_reference) = {</code>
     * @return string
     */
    public function getResourceName()
    {
        return $this->resource_name;
    }

    /**
     * Output only. The resource name of the dynamic search ads search term view.
     * Dynamic search ads search term view resource names have the form:
     * `customers/{customer_id}/dynamicSearchAdsSearchTermViews/{ad_group_id}~{search_term_fp}~{headline_fp}~{landing_page_fp}~{page_url_fp}`
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
     * Output only. Search term
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue search_term = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getSearchTerm()
    {
        return $this->search_term;
    }

    /**
     * Returns the unboxed value from <code>getSearchTerm()</code>

     * Output only. Search term
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue search_term = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return string|null
     */
    public function getSearchTermUnwrapped()
    {
        return $this->readWrapperValue("search_term");
    }

    /**
     * Output only. Search term
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue search_term = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setSearchTerm($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->search_term = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * Output only. Search term
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue search_term = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param string|null $var
     * @return $this
     */
    public function setSearchTermUnwrapped($var)
    {
        $this->writeWrapperValue("search_term", $var);
        return $this;}

    /**
     * Output only. The dynamically generated headline of the Dynamic Search Ad.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue headline = 3 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getHeadline()
    {
        return $this->headline;
    }

    /**
     * Returns the unboxed value from <code>getHeadline()</code>

     * Output only. The dynamically generated headline of the Dynamic Search Ad.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue headline = 3 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return string|null
     */
    public function getHeadlineUnwrapped()
    {
        return $this->readWrapperValue("headline");
    }

    /**
     * Output only. The dynamically generated headline of the Dynamic Search Ad.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue headline = 3 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setHeadline($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->headline = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * Output only. The dynamically generated headline of the Dynamic Search Ad.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue headline = 3 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param string|null $var
     * @return $this
     */
    public function setHeadlineUnwrapped($var)
    {
        $this->writeWrapperValue("headline", $var);
        return $this;}

    /**
     * Output only. The dynamically selected landing page URL of the impression.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue landing_page = 4 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getLandingPage()
    {
        return $this->landing_page;
    }

    /**
     * Returns the unboxed value from <code>getLandingPage()</code>

     * Output only. The dynamically selected landing page URL of the impression.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue landing_page = 4 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return string|null
     */
    public function getLandingPageUnwrapped()
    {
        return $this->readWrapperValue("landing_page");
    }

    /**
     * Output only. The dynamically selected landing page URL of the impression.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue landing_page = 4 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setLandingPage($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->landing_page = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * Output only. The dynamically selected landing page URL of the impression.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue landing_page = 4 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param string|null $var
     * @return $this
     */
    public function setLandingPageUnwrapped($var)
    {
        $this->writeWrapperValue("landing_page", $var);
        return $this;}

    /**
     * Output only. The URL of page feed item served for the impression.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue page_url = 5 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getPageUrl()
    {
        return $this->page_url;
    }

    /**
     * Returns the unboxed value from <code>getPageUrl()</code>

     * Output only. The URL of page feed item served for the impression.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue page_url = 5 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return string|null
     */
    public function getPageUrlUnwrapped()
    {
        return $this->readWrapperValue("page_url");
    }

    /**
     * Output only. The URL of page feed item served for the impression.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue page_url = 5 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setPageUrl($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->page_url = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * Output only. The URL of page feed item served for the impression.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue page_url = 5 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param string|null $var
     * @return $this
     */
    public function setPageUrlUnwrapped($var)
    {
        $this->writeWrapperValue("page_url", $var);
        return $this;}

    /**
     * Output only. True if query matches a negative keyword.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.BoolValue has_negative_keyword = 6 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\BoolValue
     */
    public function getHasNegativeKeyword()
    {
        return $this->has_negative_keyword;
    }

    /**
     * Returns the unboxed value from <code>getHasNegativeKeyword()</code>

     * Output only. True if query matches a negative keyword.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.BoolValue has_negative_keyword = 6 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return bool|null
     */
    public function getHasNegativeKeywordUnwrapped()
    {
        return $this->readWrapperValue("has_negative_keyword");
    }

    /**
     * Output only. True if query matches a negative keyword.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.BoolValue has_negative_keyword = 6 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param \Google\Protobuf\BoolValue $var
     * @return $this
     */
    public function setHasNegativeKeyword($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\BoolValue::class);
        $this->has_negative_keyword = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\BoolValue object.

     * Output only. True if query matches a negative keyword.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.BoolValue has_negative_keyword = 6 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param bool|null $var
     * @return $this
     */
    public function setHasNegativeKeywordUnwrapped($var)
    {
        $this->writeWrapperValue("has_negative_keyword", $var);
        return $this;}

    /**
     * Output only. True if query is added to targeted keywords.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.BoolValue has_matching_keyword = 7 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\BoolValue
     */
    public function getHasMatchingKeyword()
    {
        return $this->has_matching_keyword;
    }

    /**
     * Returns the unboxed value from <code>getHasMatchingKeyword()</code>

     * Output only. True if query is added to targeted keywords.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.BoolValue has_matching_keyword = 7 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return bool|null
     */
    public function getHasMatchingKeywordUnwrapped()
    {
        return $this->readWrapperValue("has_matching_keyword");
    }

    /**
     * Output only. True if query is added to targeted keywords.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.BoolValue has_matching_keyword = 7 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param \Google\Protobuf\BoolValue $var
     * @return $this
     */
    public function setHasMatchingKeyword($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\BoolValue::class);
        $this->has_matching_keyword = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\BoolValue object.

     * Output only. True if query is added to targeted keywords.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.BoolValue has_matching_keyword = 7 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param bool|null $var
     * @return $this
     */
    public function setHasMatchingKeywordUnwrapped($var)
    {
        $this->writeWrapperValue("has_matching_keyword", $var);
        return $this;}

    /**
     * Output only. True if query matches a negative url.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.BoolValue has_negative_url = 8 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return \Google\Protobuf\BoolValue
     */
    public function getHasNegativeUrl()
    {
        return $this->has_negative_url;
    }

    /**
     * Returns the unboxed value from <code>getHasNegativeUrl()</code>

     * Output only. True if query matches a negative url.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.BoolValue has_negative_url = 8 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return bool|null
     */
    public function getHasNegativeUrlUnwrapped()
    {
        return $this->readWrapperValue("has_negative_url");
    }

    /**
     * Output only. True if query matches a negative url.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.BoolValue has_negative_url = 8 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param \Google\Protobuf\BoolValue $var
     * @return $this
     */
    public function setHasNegativeUrl($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\BoolValue::class);
        $this->has_negative_url = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\BoolValue object.

     * Output only. True if query matches a negative url.
     * This field is read-only.
     *
     * Generated from protobuf field <code>.google.protobuf.BoolValue has_negative_url = 8 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param bool|null $var
     * @return $this
     */
    public function setHasNegativeUrlUnwrapped($var)
    {
        $this->writeWrapperValue("has_negative_url", $var);
        return $this;}

}

