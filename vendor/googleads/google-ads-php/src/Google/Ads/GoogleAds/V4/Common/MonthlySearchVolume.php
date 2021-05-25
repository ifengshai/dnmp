<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/common/keyword_plan_common.proto

namespace Google\Ads\GoogleAds\V4\Common;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Monthly search volume.
 *
 * Generated from protobuf message <code>google.ads.googleads.v4.common.MonthlySearchVolume</code>
 */
class MonthlySearchVolume extends \Google\Protobuf\Internal\Message
{
    /**
     * The year of the search volume (e.g. 2020).
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value year = 1;</code>
     */
    protected $year = null;
    /**
     * The month of the search volume.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.enums.MonthOfYearEnum.MonthOfYear month = 2;</code>
     */
    protected $month = 0;
    /**
     * Approximate number of searches for the month.
     * A null value indicates the search volume is unavailable for
     * that month.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value monthly_searches = 3;</code>
     */
    protected $monthly_searches = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Protobuf\Int64Value $year
     *           The year of the search volume (e.g. 2020).
     *     @type int $month
     *           The month of the search volume.
     *     @type \Google\Protobuf\Int64Value $monthly_searches
     *           Approximate number of searches for the month.
     *           A null value indicates the search volume is unavailable for
     *           that month.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V4\Common\KeywordPlanCommon::initOnce();
        parent::__construct($data);
    }

    /**
     * The year of the search volume (e.g. 2020).
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value year = 1;</code>
     * @return \Google\Protobuf\Int64Value
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * Returns the unboxed value from <code>getYear()</code>

     * The year of the search volume (e.g. 2020).
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value year = 1;</code>
     * @return int|string|null
     */
    public function getYearUnwrapped()
    {
        return $this->readWrapperValue("year");
    }

    /**
     * The year of the search volume (e.g. 2020).
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value year = 1;</code>
     * @param \Google\Protobuf\Int64Value $var
     * @return $this
     */
    public function setYear($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Int64Value::class);
        $this->year = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\Int64Value object.

     * The year of the search volume (e.g. 2020).
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value year = 1;</code>
     * @param int|string|null $var
     * @return $this
     */
    public function setYearUnwrapped($var)
    {
        $this->writeWrapperValue("year", $var);
        return $this;}

    /**
     * The month of the search volume.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.enums.MonthOfYearEnum.MonthOfYear month = 2;</code>
     * @return int
     */
    public function getMonth()
    {
        return $this->month;
    }

    /**
     * The month of the search volume.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v4.enums.MonthOfYearEnum.MonthOfYear month = 2;</code>
     * @param int $var
     * @return $this
     */
    public function setMonth($var)
    {
        GPBUtil::checkEnum($var, \Google\Ads\GoogleAds\V4\Enums\MonthOfYearEnum_MonthOfYear::class);
        $this->month = $var;

        return $this;
    }

    /**
     * Approximate number of searches for the month.
     * A null value indicates the search volume is unavailable for
     * that month.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value monthly_searches = 3;</code>
     * @return \Google\Protobuf\Int64Value
     */
    public function getMonthlySearches()
    {
        return $this->monthly_searches;
    }

    /**
     * Returns the unboxed value from <code>getMonthlySearches()</code>

     * Approximate number of searches for the month.
     * A null value indicates the search volume is unavailable for
     * that month.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value monthly_searches = 3;</code>
     * @return int|string|null
     */
    public function getMonthlySearchesUnwrapped()
    {
        return $this->readWrapperValue("monthly_searches");
    }

    /**
     * Approximate number of searches for the month.
     * A null value indicates the search volume is unavailable for
     * that month.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value monthly_searches = 3;</code>
     * @param \Google\Protobuf\Int64Value $var
     * @return $this
     */
    public function setMonthlySearches($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Int64Value::class);
        $this->monthly_searches = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\Int64Value object.

     * Approximate number of searches for the month.
     * A null value indicates the search volume is unavailable for
     * that month.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value monthly_searches = 3;</code>
     * @param int|string|null $var
     * @return $this
     */
    public function setMonthlySearchesUnwrapped($var)
    {
        $this->writeWrapperValue("monthly_searches", $var);
        return $this;}

}

