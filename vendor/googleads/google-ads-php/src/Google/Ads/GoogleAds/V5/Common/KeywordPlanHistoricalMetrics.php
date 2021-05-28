<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/common/keyword_plan_common.proto

namespace Google\Ads\GoogleAds\V5\Common;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Historical metrics specific to the targeting options selected.
 * Targeting options include geographies, network, etc.
 * Refer to https://support.google.com/google-ads/answer/3022575 for more
 * details.
 *
 * Generated from protobuf message <code>google.ads.googleads.v5.common.KeywordPlanHistoricalMetrics</code>
 */
class KeywordPlanHistoricalMetrics extends \Google\Protobuf\Internal\Message
{
    /**
     * Approximate number of monthly searches on this query averaged
     * for the past 12 months.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value avg_monthly_searches = 1;</code>
     */
    protected $avg_monthly_searches = null;
    /**
     * Approximate number of searches on this query for the past twelve months.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.common.MonthlySearchVolume monthly_search_volumes = 6;</code>
     */
    private $monthly_search_volumes;
    /**
     * The competition level for the query.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.enums.KeywordPlanCompetitionLevelEnum.KeywordPlanCompetitionLevel competition = 2;</code>
     */
    protected $competition = 0;
    /**
     * The competition index for the query in the range [0, 100].
     * Shows how competitive ad placement is for a keyword.
     * The level of competition from 0-100 is determined by the number of ad slots
     * filled divided by the total number of ad slots available. If not enough
     * data is available, null is returned.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value competition_index = 3;</code>
     */
    protected $competition_index = null;
    /**
     * Top of page bid low range (20th percentile) in micros for the keyword.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value low_top_of_page_bid_micros = 4;</code>
     */
    protected $low_top_of_page_bid_micros = null;
    /**
     * Top of page bid high range (80th percentile) in micros for the keyword.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value high_top_of_page_bid_micros = 5;</code>
     */
    protected $high_top_of_page_bid_micros = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Protobuf\Int64Value $avg_monthly_searches
     *           Approximate number of monthly searches on this query averaged
     *           for the past 12 months.
     *     @type \Google\Ads\GoogleAds\V5\Common\MonthlySearchVolume[]|\Google\Protobuf\Internal\RepeatedField $monthly_search_volumes
     *           Approximate number of searches on this query for the past twelve months.
     *     @type int $competition
     *           The competition level for the query.
     *     @type \Google\Protobuf\Int64Value $competition_index
     *           The competition index for the query in the range [0, 100].
     *           Shows how competitive ad placement is for a keyword.
     *           The level of competition from 0-100 is determined by the number of ad slots
     *           filled divided by the total number of ad slots available. If not enough
     *           data is available, null is returned.
     *     @type \Google\Protobuf\Int64Value $low_top_of_page_bid_micros
     *           Top of page bid low range (20th percentile) in micros for the keyword.
     *     @type \Google\Protobuf\Int64Value $high_top_of_page_bid_micros
     *           Top of page bid high range (80th percentile) in micros for the keyword.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V5\Common\KeywordPlanCommon::initOnce();
        parent::__construct($data);
    }

    /**
     * Approximate number of monthly searches on this query averaged
     * for the past 12 months.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value avg_monthly_searches = 1;</code>
     * @return \Google\Protobuf\Int64Value
     */
    public function getAvgMonthlySearches()
    {
        return isset($this->avg_monthly_searches) ? $this->avg_monthly_searches : null;
    }

    public function hasAvgMonthlySearches()
    {
        return isset($this->avg_monthly_searches);
    }

    public function clearAvgMonthlySearches()
    {
        unset($this->avg_monthly_searches);
    }

    /**
     * Returns the unboxed value from <code>getAvgMonthlySearches()</code>

     * Approximate number of monthly searches on this query averaged
     * for the past 12 months.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value avg_monthly_searches = 1;</code>
     * @return int|string|null
     */
    public function getAvgMonthlySearchesUnwrapped()
    {
        return $this->readWrapperValue("avg_monthly_searches");
    }

    /**
     * Approximate number of monthly searches on this query averaged
     * for the past 12 months.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value avg_monthly_searches = 1;</code>
     * @param \Google\Protobuf\Int64Value $var
     * @return $this
     */
    public function setAvgMonthlySearches($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Int64Value::class);
        $this->avg_monthly_searches = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\Int64Value object.

     * Approximate number of monthly searches on this query averaged
     * for the past 12 months.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value avg_monthly_searches = 1;</code>
     * @param int|string|null $var
     * @return $this
     */
    public function setAvgMonthlySearchesUnwrapped($var)
    {
        $this->writeWrapperValue("avg_monthly_searches", $var);
        return $this;}

    /**
     * Approximate number of searches on this query for the past twelve months.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.common.MonthlySearchVolume monthly_search_volumes = 6;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getMonthlySearchVolumes()
    {
        return $this->monthly_search_volumes;
    }

    /**
     * Approximate number of searches on this query for the past twelve months.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.common.MonthlySearchVolume monthly_search_volumes = 6;</code>
     * @param \Google\Ads\GoogleAds\V5\Common\MonthlySearchVolume[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setMonthlySearchVolumes($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Ads\GoogleAds\V5\Common\MonthlySearchVolume::class);
        $this->monthly_search_volumes = $arr;

        return $this;
    }

    /**
     * The competition level for the query.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.enums.KeywordPlanCompetitionLevelEnum.KeywordPlanCompetitionLevel competition = 2;</code>
     * @return int
     */
    public function getCompetition()
    {
        return $this->competition;
    }

    /**
     * The competition level for the query.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.enums.KeywordPlanCompetitionLevelEnum.KeywordPlanCompetitionLevel competition = 2;</code>
     * @param int $var
     * @return $this
     */
    public function setCompetition($var)
    {
        GPBUtil::checkEnum($var, \Google\Ads\GoogleAds\V5\Enums\KeywordPlanCompetitionLevelEnum\KeywordPlanCompetitionLevel::class);
        $this->competition = $var;

        return $this;
    }

    /**
     * The competition index for the query in the range [0, 100].
     * Shows how competitive ad placement is for a keyword.
     * The level of competition from 0-100 is determined by the number of ad slots
     * filled divided by the total number of ad slots available. If not enough
     * data is available, null is returned.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value competition_index = 3;</code>
     * @return \Google\Protobuf\Int64Value
     */
    public function getCompetitionIndex()
    {
        return isset($this->competition_index) ? $this->competition_index : null;
    }

    public function hasCompetitionIndex()
    {
        return isset($this->competition_index);
    }

    public function clearCompetitionIndex()
    {
        unset($this->competition_index);
    }

    /**
     * Returns the unboxed value from <code>getCompetitionIndex()</code>

     * The competition index for the query in the range [0, 100].
     * Shows how competitive ad placement is for a keyword.
     * The level of competition from 0-100 is determined by the number of ad slots
     * filled divided by the total number of ad slots available. If not enough
     * data is available, null is returned.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value competition_index = 3;</code>
     * @return int|string|null
     */
    public function getCompetitionIndexUnwrapped()
    {
        return $this->readWrapperValue("competition_index");
    }

    /**
     * The competition index for the query in the range [0, 100].
     * Shows how competitive ad placement is for a keyword.
     * The level of competition from 0-100 is determined by the number of ad slots
     * filled divided by the total number of ad slots available. If not enough
     * data is available, null is returned.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value competition_index = 3;</code>
     * @param \Google\Protobuf\Int64Value $var
     * @return $this
     */
    public function setCompetitionIndex($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Int64Value::class);
        $this->competition_index = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\Int64Value object.

     * The competition index for the query in the range [0, 100].
     * Shows how competitive ad placement is for a keyword.
     * The level of competition from 0-100 is determined by the number of ad slots
     * filled divided by the total number of ad slots available. If not enough
     * data is available, null is returned.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value competition_index = 3;</code>
     * @param int|string|null $var
     * @return $this
     */
    public function setCompetitionIndexUnwrapped($var)
    {
        $this->writeWrapperValue("competition_index", $var);
        return $this;}

    /**
     * Top of page bid low range (20th percentile) in micros for the keyword.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value low_top_of_page_bid_micros = 4;</code>
     * @return \Google\Protobuf\Int64Value
     */
    public function getLowTopOfPageBidMicros()
    {
        return isset($this->low_top_of_page_bid_micros) ? $this->low_top_of_page_bid_micros : null;
    }

    public function hasLowTopOfPageBidMicros()
    {
        return isset($this->low_top_of_page_bid_micros);
    }

    public function clearLowTopOfPageBidMicros()
    {
        unset($this->low_top_of_page_bid_micros);
    }

    /**
     * Returns the unboxed value from <code>getLowTopOfPageBidMicros()</code>

     * Top of page bid low range (20th percentile) in micros for the keyword.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value low_top_of_page_bid_micros = 4;</code>
     * @return int|string|null
     */
    public function getLowTopOfPageBidMicrosUnwrapped()
    {
        return $this->readWrapperValue("low_top_of_page_bid_micros");
    }

    /**
     * Top of page bid low range (20th percentile) in micros for the keyword.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value low_top_of_page_bid_micros = 4;</code>
     * @param \Google\Protobuf\Int64Value $var
     * @return $this
     */
    public function setLowTopOfPageBidMicros($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Int64Value::class);
        $this->low_top_of_page_bid_micros = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\Int64Value object.

     * Top of page bid low range (20th percentile) in micros for the keyword.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value low_top_of_page_bid_micros = 4;</code>
     * @param int|string|null $var
     * @return $this
     */
    public function setLowTopOfPageBidMicrosUnwrapped($var)
    {
        $this->writeWrapperValue("low_top_of_page_bid_micros", $var);
        return $this;}

    /**
     * Top of page bid high range (80th percentile) in micros for the keyword.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value high_top_of_page_bid_micros = 5;</code>
     * @return \Google\Protobuf\Int64Value
     */
    public function getHighTopOfPageBidMicros()
    {
        return isset($this->high_top_of_page_bid_micros) ? $this->high_top_of_page_bid_micros : null;
    }

    public function hasHighTopOfPageBidMicros()
    {
        return isset($this->high_top_of_page_bid_micros);
    }

    public function clearHighTopOfPageBidMicros()
    {
        unset($this->high_top_of_page_bid_micros);
    }

    /**
     * Returns the unboxed value from <code>getHighTopOfPageBidMicros()</code>

     * Top of page bid high range (80th percentile) in micros for the keyword.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value high_top_of_page_bid_micros = 5;</code>
     * @return int|string|null
     */
    public function getHighTopOfPageBidMicrosUnwrapped()
    {
        return $this->readWrapperValue("high_top_of_page_bid_micros");
    }

    /**
     * Top of page bid high range (80th percentile) in micros for the keyword.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value high_top_of_page_bid_micros = 5;</code>
     * @param \Google\Protobuf\Int64Value $var
     * @return $this
     */
    public function setHighTopOfPageBidMicros($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Int64Value::class);
        $this->high_top_of_page_bid_micros = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\Int64Value object.

     * Top of page bid high range (80th percentile) in micros for the keyword.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value high_top_of_page_bid_micros = 5;</code>
     * @param int|string|null $var
     * @return $this
     */
    public function setHighTopOfPageBidMicrosUnwrapped($var)
    {
        $this->writeWrapperValue("high_top_of_page_bid_micros", $var);
        return $this;}

}

