<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/errors/errors.proto

namespace Google\Ads\GoogleAds\V5\Errors;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Additional quota error details when there is QuotaError.
 *
 * Generated from protobuf message <code>google.ads.googleads.v5.errors.QuotaErrorDetails</code>
 */
class QuotaErrorDetails extends \Google\Protobuf\Internal\Message
{
    /**
     * The rate scope of the quota limit.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.errors.QuotaErrorDetails.QuotaRateScope rate_scope = 1;</code>
     */
    protected $rate_scope = 0;
    /**
     * The high level description of the quota bucket.
     * Examples are "Get requests for standard access" or "Requests per account".
     *
     * Generated from protobuf field <code>string rate_name = 2;</code>
     */
    protected $rate_name = '';
    /**
     * Backoff period that customers should wait before sending next request.
     *
     * Generated from protobuf field <code>.google.protobuf.Duration retry_delay = 3;</code>
     */
    protected $retry_delay = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int $rate_scope
     *           The rate scope of the quota limit.
     *     @type string $rate_name
     *           The high level description of the quota bucket.
     *           Examples are "Get requests for standard access" or "Requests per account".
     *     @type \Google\Protobuf\Duration $retry_delay
     *           Backoff period that customers should wait before sending next request.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V5\Errors\Errors::initOnce();
        parent::__construct($data);
    }

    /**
     * The rate scope of the quota limit.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.errors.QuotaErrorDetails.QuotaRateScope rate_scope = 1;</code>
     * @return int
     */
    public function getRateScope()
    {
        return $this->rate_scope;
    }

    /**
     * The rate scope of the quota limit.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.errors.QuotaErrorDetails.QuotaRateScope rate_scope = 1;</code>
     * @param int $var
     * @return $this
     */
    public function setRateScope($var)
    {
        GPBUtil::checkEnum($var, \Google\Ads\GoogleAds\V5\Errors\QuotaErrorDetails\QuotaRateScope::class);
        $this->rate_scope = $var;

        return $this;
    }

    /**
     * The high level description of the quota bucket.
     * Examples are "Get requests for standard access" or "Requests per account".
     *
     * Generated from protobuf field <code>string rate_name = 2;</code>
     * @return string
     */
    public function getRateName()
    {
        return $this->rate_name;
    }

    /**
     * The high level description of the quota bucket.
     * Examples are "Get requests for standard access" or "Requests per account".
     *
     * Generated from protobuf field <code>string rate_name = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setRateName($var)
    {
        GPBUtil::checkString($var, True);
        $this->rate_name = $var;

        return $this;
    }

    /**
     * Backoff period that customers should wait before sending next request.
     *
     * Generated from protobuf field <code>.google.protobuf.Duration retry_delay = 3;</code>
     * @return \Google\Protobuf\Duration
     */
    public function getRetryDelay()
    {
        return isset($this->retry_delay) ? $this->retry_delay : null;
    }

    public function hasRetryDelay()
    {
        return isset($this->retry_delay);
    }

    public function clearRetryDelay()
    {
        unset($this->retry_delay);
    }

    /**
     * Backoff period that customers should wait before sending next request.
     *
     * Generated from protobuf field <code>.google.protobuf.Duration retry_delay = 3;</code>
     * @param \Google\Protobuf\Duration $var
     * @return $this
     */
    public function setRetryDelay($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Duration::class);
        $this->retry_delay = $var;

        return $this;
    }

}

