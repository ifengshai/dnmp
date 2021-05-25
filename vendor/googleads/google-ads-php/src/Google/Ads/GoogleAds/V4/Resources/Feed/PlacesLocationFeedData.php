<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/resources/feed.proto

namespace Google\Ads\GoogleAds\V4\Resources\Feed;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Data used to configure a location feed populated from Google My Business
 * Locations.
 *
 * Generated from protobuf message <code>google.ads.googleads.v4.resources.Feed.PlacesLocationFeedData</code>
 */
class PlacesLocationFeedData extends \Google\Protobuf\Internal\Message
{
    /**
     * Immutable. Required authentication token (from OAuth API) for the email.
     * This field can only be specified in a create request. All its subfields
     * are not selectable.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.resources.Feed.PlacesLocationFeedData.OAuthInfo oauth_info = 1 [(.google.api.field_behavior) = IMMUTABLE];</code>
     */
    protected $oauth_info = null;
    /**
     * Email address of a Google My Business account or email address of a
     * manager of the Google My Business account. Required.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue email_address = 2;</code>
     */
    protected $email_address = null;
    /**
     * Plus page ID of the managed business whose locations should be used. If
     * this field is not set, then all businesses accessible by the user
     * (specified by email_address) are used.
     * This field is mutate-only and is not selectable.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue business_account_id = 10;</code>
     */
    protected $business_account_id = null;
    /**
     * Used to filter Google My Business listings by business name. If
     * business_name_filter is set, only listings with a matching business name
     * are candidates to be sync'd into FeedItems.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue business_name_filter = 4;</code>
     */
    protected $business_name_filter = null;
    /**
     * Used to filter Google My Business listings by categories. If entries
     * exist in category_filters, only listings that belong to any of the
     * categories are candidates to be sync'd into FeedItems. If no entries
     * exist in category_filters, then all listings are candidates for syncing.
     *
     * Generated from protobuf field <code>repeated .google.protobuf.StringValue category_filters = 5;</code>
     */
    private $category_filters;
    /**
     * Used to filter Google My Business listings by labels. If entries exist in
     * label_filters, only listings that has any of the labels set are
     * candidates to be synchronized into FeedItems. If no entries exist in
     * label_filters, then all listings are candidates for syncing.
     *
     * Generated from protobuf field <code>repeated .google.protobuf.StringValue label_filters = 6;</code>
     */
    private $label_filters;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Ads\GoogleAds\V4\Resources\Feed\PlacesLocationFeedData\OAuthInfo $oauth_info
     *           Immutable. Required authentication token (from OAuth API) for the email.
     *           This field can only be specified in a create request. All its subfields
     *           are not selectable.
     *     @type \Google\Protobuf\StringValue $email_address
     *           Email address of a Google My Business account or email address of a
     *           manager of the Google My Business account. Required.
     *     @type \Google\Protobuf\StringValue $business_account_id
     *           Plus page ID of the managed business whose locations should be used. If
     *           this field is not set, then all businesses accessible by the user
     *           (specified by email_address) are used.
     *           This field is mutate-only and is not selectable.
     *     @type \Google\Protobuf\StringValue $business_name_filter
     *           Used to filter Google My Business listings by business name. If
     *           business_name_filter is set, only listings with a matching business name
     *           are candidates to be sync'd into FeedItems.
     *     @type \Google\Protobuf\StringValue[]|\Google\Protobuf\Internal\RepeatedField $category_filters
     *           Used to filter Google My Business listings by categories. If entries
     *           exist in category_filters, only listings that belong to any of the
     *           categories are candidates to be sync'd into FeedItems. If no entries
     *           exist in category_filters, then all listings are candidates for syncing.
     *     @type \Google\Protobuf\StringValue[]|\Google\Protobuf\Internal\RepeatedField $label_filters
     *           Used to filter Google My Business listings by labels. If entries exist in
     *           label_filters, only listings that has any of the labels set are
     *           candidates to be synchronized into FeedItems. If no entries exist in
     *           label_filters, then all listings are candidates for syncing.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V4\Resources\Feed::initOnce();
        parent::__construct($data);
    }

    /**
     * Immutable. Required authentication token (from OAuth API) for the email.
     * This field can only be specified in a create request. All its subfields
     * are not selectable.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.resources.Feed.PlacesLocationFeedData.OAuthInfo oauth_info = 1 [(.google.api.field_behavior) = IMMUTABLE];</code>
     * @return \Google\Ads\GoogleAds\V4\Resources\Feed\PlacesLocationFeedData\OAuthInfo
     */
    public function getOauthInfo()
    {
        return $this->oauth_info;
    }

    /**
     * Immutable. Required authentication token (from OAuth API) for the email.
     * This field can only be specified in a create request. All its subfields
     * are not selectable.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.resources.Feed.PlacesLocationFeedData.OAuthInfo oauth_info = 1 [(.google.api.field_behavior) = IMMUTABLE];</code>
     * @param \Google\Ads\GoogleAds\V4\Resources\Feed\PlacesLocationFeedData\OAuthInfo $var
     * @return $this
     */
    public function setOauthInfo($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V4\Resources\Feed_PlacesLocationFeedData_OAuthInfo::class);
        $this->oauth_info = $var;

        return $this;
    }

    /**
     * Email address of a Google My Business account or email address of a
     * manager of the Google My Business account. Required.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue email_address = 2;</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getEmailAddress()
    {
        return $this->email_address;
    }

    /**
     * Returns the unboxed value from <code>getEmailAddress()</code>

     * Email address of a Google My Business account or email address of a
     * manager of the Google My Business account. Required.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue email_address = 2;</code>
     * @return string|null
     */
    public function getEmailAddressUnwrapped()
    {
        return $this->readWrapperValue("email_address");
    }

    /**
     * Email address of a Google My Business account or email address of a
     * manager of the Google My Business account. Required.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue email_address = 2;</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setEmailAddress($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->email_address = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * Email address of a Google My Business account or email address of a
     * manager of the Google My Business account. Required.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue email_address = 2;</code>
     * @param string|null $var
     * @return $this
     */
    public function setEmailAddressUnwrapped($var)
    {
        $this->writeWrapperValue("email_address", $var);
        return $this;}

    /**
     * Plus page ID of the managed business whose locations should be used. If
     * this field is not set, then all businesses accessible by the user
     * (specified by email_address) are used.
     * This field is mutate-only and is not selectable.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue business_account_id = 10;</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getBusinessAccountId()
    {
        return $this->business_account_id;
    }

    /**
     * Returns the unboxed value from <code>getBusinessAccountId()</code>

     * Plus page ID of the managed business whose locations should be used. If
     * this field is not set, then all businesses accessible by the user
     * (specified by email_address) are used.
     * This field is mutate-only and is not selectable.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue business_account_id = 10;</code>
     * @return string|null
     */
    public function getBusinessAccountIdUnwrapped()
    {
        return $this->readWrapperValue("business_account_id");
    }

    /**
     * Plus page ID of the managed business whose locations should be used. If
     * this field is not set, then all businesses accessible by the user
     * (specified by email_address) are used.
     * This field is mutate-only and is not selectable.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue business_account_id = 10;</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setBusinessAccountId($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->business_account_id = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * Plus page ID of the managed business whose locations should be used. If
     * this field is not set, then all businesses accessible by the user
     * (specified by email_address) are used.
     * This field is mutate-only and is not selectable.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue business_account_id = 10;</code>
     * @param string|null $var
     * @return $this
     */
    public function setBusinessAccountIdUnwrapped($var)
    {
        $this->writeWrapperValue("business_account_id", $var);
        return $this;}

    /**
     * Used to filter Google My Business listings by business name. If
     * business_name_filter is set, only listings with a matching business name
     * are candidates to be sync'd into FeedItems.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue business_name_filter = 4;</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getBusinessNameFilter()
    {
        return $this->business_name_filter;
    }

    /**
     * Returns the unboxed value from <code>getBusinessNameFilter()</code>

     * Used to filter Google My Business listings by business name. If
     * business_name_filter is set, only listings with a matching business name
     * are candidates to be sync'd into FeedItems.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue business_name_filter = 4;</code>
     * @return string|null
     */
    public function getBusinessNameFilterUnwrapped()
    {
        return $this->readWrapperValue("business_name_filter");
    }

    /**
     * Used to filter Google My Business listings by business name. If
     * business_name_filter is set, only listings with a matching business name
     * are candidates to be sync'd into FeedItems.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue business_name_filter = 4;</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setBusinessNameFilter($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->business_name_filter = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * Used to filter Google My Business listings by business name. If
     * business_name_filter is set, only listings with a matching business name
     * are candidates to be sync'd into FeedItems.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue business_name_filter = 4;</code>
     * @param string|null $var
     * @return $this
     */
    public function setBusinessNameFilterUnwrapped($var)
    {
        $this->writeWrapperValue("business_name_filter", $var);
        return $this;}

    /**
     * Used to filter Google My Business listings by categories. If entries
     * exist in category_filters, only listings that belong to any of the
     * categories are candidates to be sync'd into FeedItems. If no entries
     * exist in category_filters, then all listings are candidates for syncing.
     *
     * Generated from protobuf field <code>repeated .google.protobuf.StringValue category_filters = 5;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getCategoryFilters()
    {
        return $this->category_filters;
    }

    /**
     * Used to filter Google My Business listings by categories. If entries
     * exist in category_filters, only listings that belong to any of the
     * categories are candidates to be sync'd into FeedItems. If no entries
     * exist in category_filters, then all listings are candidates for syncing.
     *
     * Generated from protobuf field <code>repeated .google.protobuf.StringValue category_filters = 5;</code>
     * @param \Google\Protobuf\StringValue[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setCategoryFilters($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Protobuf\StringValue::class);
        $this->category_filters = $arr;

        return $this;
    }

    /**
     * Used to filter Google My Business listings by labels. If entries exist in
     * label_filters, only listings that has any of the labels set are
     * candidates to be synchronized into FeedItems. If no entries exist in
     * label_filters, then all listings are candidates for syncing.
     *
     * Generated from protobuf field <code>repeated .google.protobuf.StringValue label_filters = 6;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getLabelFilters()
    {
        return $this->label_filters;
    }

    /**
     * Used to filter Google My Business listings by labels. If entries exist in
     * label_filters, only listings that has any of the labels set are
     * candidates to be synchronized into FeedItems. If no entries exist in
     * label_filters, then all listings are candidates for syncing.
     *
     * Generated from protobuf field <code>repeated .google.protobuf.StringValue label_filters = 6;</code>
     * @param \Google\Protobuf\StringValue[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setLabelFilters($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Protobuf\StringValue::class);
        $this->label_filters = $arr;

        return $this;
    }

}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(PlacesLocationFeedData::class, \Google\Ads\GoogleAds\V4\Resources\Feed_PlacesLocationFeedData::class);

