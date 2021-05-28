<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/common/ad_type_infos.proto

namespace Google\Ads\GoogleAds\V5\Common;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * A local ad.
 *
 * Generated from protobuf message <code>google.ads.googleads.v5.common.LocalAdInfo</code>
 */
class LocalAdInfo extends \Google\Protobuf\Internal\Message
{
    /**
     * List of text assets for headlines. When the ad serves the headlines will
     * be selected from this list. At least 1 and at most 5 headlines must be
     * specified.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.common.AdTextAsset headlines = 1;</code>
     */
    private $headlines;
    /**
     * List of text assets for descriptions. When the ad serves the descriptions
     * will be selected from this list. At least 1 and at most 5 descriptions must
     * be specified.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.common.AdTextAsset descriptions = 2;</code>
     */
    private $descriptions;
    /**
     * List of text assets for call-to-actions. When the ad serves the
     * call-to-actions will be selected from this list. Call-to-actions are
     * optional and at most 5 can be specified.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.common.AdTextAsset call_to_actions = 3;</code>
     */
    private $call_to_actions;
    /**
     * List of marketing image assets that may be displayed with the ad. The
     * images must be 314x600 pixels or 320x320 pixels. At least 1 and at most
     * 20 image assets must be specified.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.common.AdImageAsset marketing_images = 4;</code>
     */
    private $marketing_images;
    /**
     * List of logo image assets that may be displayed with the ad. The images
     * must be 128x128 pixels and not larger than 120KB. At least 1 and at most 5
     * image assets must be specified.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.common.AdImageAsset logo_images = 5;</code>
     */
    private $logo_images;
    /**
     * List of YouTube video assets that may be displayed with the ad. Videos
     * are optional and at most 20 can be specified.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.common.AdVideoAsset videos = 6;</code>
     */
    private $videos;
    /**
     * First part of optional text that may appear appended to the url displayed
     * in the ad.
     *
     * Generated from protobuf field <code>string path1 = 9;</code>
     */
    protected $path1 = null;
    /**
     * Second part of optional text that may appear appended to the url displayed
     * in the ad. This field can only be set when path1 is also set.
     *
     * Generated from protobuf field <code>string path2 = 10;</code>
     */
    protected $path2 = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Ads\GoogleAds\V5\Common\AdTextAsset[]|\Google\Protobuf\Internal\RepeatedField $headlines
     *           List of text assets for headlines. When the ad serves the headlines will
     *           be selected from this list. At least 1 and at most 5 headlines must be
     *           specified.
     *     @type \Google\Ads\GoogleAds\V5\Common\AdTextAsset[]|\Google\Protobuf\Internal\RepeatedField $descriptions
     *           List of text assets for descriptions. When the ad serves the descriptions
     *           will be selected from this list. At least 1 and at most 5 descriptions must
     *           be specified.
     *     @type \Google\Ads\GoogleAds\V5\Common\AdTextAsset[]|\Google\Protobuf\Internal\RepeatedField $call_to_actions
     *           List of text assets for call-to-actions. When the ad serves the
     *           call-to-actions will be selected from this list. Call-to-actions are
     *           optional and at most 5 can be specified.
     *     @type \Google\Ads\GoogleAds\V5\Common\AdImageAsset[]|\Google\Protobuf\Internal\RepeatedField $marketing_images
     *           List of marketing image assets that may be displayed with the ad. The
     *           images must be 314x600 pixels or 320x320 pixels. At least 1 and at most
     *           20 image assets must be specified.
     *     @type \Google\Ads\GoogleAds\V5\Common\AdImageAsset[]|\Google\Protobuf\Internal\RepeatedField $logo_images
     *           List of logo image assets that may be displayed with the ad. The images
     *           must be 128x128 pixels and not larger than 120KB. At least 1 and at most 5
     *           image assets must be specified.
     *     @type \Google\Ads\GoogleAds\V5\Common\AdVideoAsset[]|\Google\Protobuf\Internal\RepeatedField $videos
     *           List of YouTube video assets that may be displayed with the ad. Videos
     *           are optional and at most 20 can be specified.
     *     @type string $path1
     *           First part of optional text that may appear appended to the url displayed
     *           in the ad.
     *     @type string $path2
     *           Second part of optional text that may appear appended to the url displayed
     *           in the ad. This field can only be set when path1 is also set.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V5\Common\AdTypeInfos::initOnce();
        parent::__construct($data);
    }

    /**
     * List of text assets for headlines. When the ad serves the headlines will
     * be selected from this list. At least 1 and at most 5 headlines must be
     * specified.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.common.AdTextAsset headlines = 1;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getHeadlines()
    {
        return $this->headlines;
    }

    /**
     * List of text assets for headlines. When the ad serves the headlines will
     * be selected from this list. At least 1 and at most 5 headlines must be
     * specified.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.common.AdTextAsset headlines = 1;</code>
     * @param \Google\Ads\GoogleAds\V5\Common\AdTextAsset[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setHeadlines($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Ads\GoogleAds\V5\Common\AdTextAsset::class);
        $this->headlines = $arr;

        return $this;
    }

    /**
     * List of text assets for descriptions. When the ad serves the descriptions
     * will be selected from this list. At least 1 and at most 5 descriptions must
     * be specified.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.common.AdTextAsset descriptions = 2;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getDescriptions()
    {
        return $this->descriptions;
    }

    /**
     * List of text assets for descriptions. When the ad serves the descriptions
     * will be selected from this list. At least 1 and at most 5 descriptions must
     * be specified.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.common.AdTextAsset descriptions = 2;</code>
     * @param \Google\Ads\GoogleAds\V5\Common\AdTextAsset[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setDescriptions($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Ads\GoogleAds\V5\Common\AdTextAsset::class);
        $this->descriptions = $arr;

        return $this;
    }

    /**
     * List of text assets for call-to-actions. When the ad serves the
     * call-to-actions will be selected from this list. Call-to-actions are
     * optional and at most 5 can be specified.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.common.AdTextAsset call_to_actions = 3;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getCallToActions()
    {
        return $this->call_to_actions;
    }

    /**
     * List of text assets for call-to-actions. When the ad serves the
     * call-to-actions will be selected from this list. Call-to-actions are
     * optional and at most 5 can be specified.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.common.AdTextAsset call_to_actions = 3;</code>
     * @param \Google\Ads\GoogleAds\V5\Common\AdTextAsset[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setCallToActions($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Ads\GoogleAds\V5\Common\AdTextAsset::class);
        $this->call_to_actions = $arr;

        return $this;
    }

    /**
     * List of marketing image assets that may be displayed with the ad. The
     * images must be 314x600 pixels or 320x320 pixels. At least 1 and at most
     * 20 image assets must be specified.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.common.AdImageAsset marketing_images = 4;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getMarketingImages()
    {
        return $this->marketing_images;
    }

    /**
     * List of marketing image assets that may be displayed with the ad. The
     * images must be 314x600 pixels or 320x320 pixels. At least 1 and at most
     * 20 image assets must be specified.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.common.AdImageAsset marketing_images = 4;</code>
     * @param \Google\Ads\GoogleAds\V5\Common\AdImageAsset[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setMarketingImages($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Ads\GoogleAds\V5\Common\AdImageAsset::class);
        $this->marketing_images = $arr;

        return $this;
    }

    /**
     * List of logo image assets that may be displayed with the ad. The images
     * must be 128x128 pixels and not larger than 120KB. At least 1 and at most 5
     * image assets must be specified.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.common.AdImageAsset logo_images = 5;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getLogoImages()
    {
        return $this->logo_images;
    }

    /**
     * List of logo image assets that may be displayed with the ad. The images
     * must be 128x128 pixels and not larger than 120KB. At least 1 and at most 5
     * image assets must be specified.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.common.AdImageAsset logo_images = 5;</code>
     * @param \Google\Ads\GoogleAds\V5\Common\AdImageAsset[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setLogoImages($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Ads\GoogleAds\V5\Common\AdImageAsset::class);
        $this->logo_images = $arr;

        return $this;
    }

    /**
     * List of YouTube video assets that may be displayed with the ad. Videos
     * are optional and at most 20 can be specified.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.common.AdVideoAsset videos = 6;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getVideos()
    {
        return $this->videos;
    }

    /**
     * List of YouTube video assets that may be displayed with the ad. Videos
     * are optional and at most 20 can be specified.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.common.AdVideoAsset videos = 6;</code>
     * @param \Google\Ads\GoogleAds\V5\Common\AdVideoAsset[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setVideos($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Ads\GoogleAds\V5\Common\AdVideoAsset::class);
        $this->videos = $arr;

        return $this;
    }

    /**
     * First part of optional text that may appear appended to the url displayed
     * in the ad.
     *
     * Generated from protobuf field <code>string path1 = 9;</code>
     * @return string
     */
    public function getPath1()
    {
        return isset($this->path1) ? $this->path1 : '';
    }

    public function hasPath1()
    {
        return isset($this->path1);
    }

    public function clearPath1()
    {
        unset($this->path1);
    }

    /**
     * First part of optional text that may appear appended to the url displayed
     * in the ad.
     *
     * Generated from protobuf field <code>string path1 = 9;</code>
     * @param string $var
     * @return $this
     */
    public function setPath1($var)
    {
        GPBUtil::checkString($var, True);
        $this->path1 = $var;

        return $this;
    }

    /**
     * Second part of optional text that may appear appended to the url displayed
     * in the ad. This field can only be set when path1 is also set.
     *
     * Generated from protobuf field <code>string path2 = 10;</code>
     * @return string
     */
    public function getPath2()
    {
        return isset($this->path2) ? $this->path2 : '';
    }

    public function hasPath2()
    {
        return isset($this->path2);
    }

    public function clearPath2()
    {
        unset($this->path2);
    }

    /**
     * Second part of optional text that may appear appended to the url displayed
     * in the ad. This field can only be set when path1 is also set.
     *
     * Generated from protobuf field <code>string path2 = 10;</code>
     * @param string $var
     * @return $this
     */
    public function setPath2($var)
    {
        GPBUtil::checkString($var, True);
        $this->path2 = $var;

        return $this;
    }

}

