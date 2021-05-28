<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/services/conversion_upload_service.proto

namespace Google\Ads\GoogleAds\V5\Services;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Response message for [ConversionUploadService.UploadClickConversions][google.ads.googleads.v5.services.ConversionUploadService.UploadClickConversions].
 *
 * Generated from protobuf message <code>google.ads.googleads.v5.services.UploadClickConversionsResponse</code>
 */
class UploadClickConversionsResponse extends \Google\Protobuf\Internal\Message
{
    /**
     * Errors that pertain to conversion failures in the partial failure mode.
     * Returned when all errors occur inside the conversions. If any errors occur
     * outside the conversions (e.g. auth errors), we return an RPC level error.
     * See
     * https://developers.google.com/google-ads/api/docs/best-practices/partial-failures
     * for more information about partial failure.
     *
     * Generated from protobuf field <code>.google.rpc.Status partial_failure_error = 1;</code>
     */
    protected $partial_failure_error = null;
    /**
     * Returned for successfully processed conversions. Proto will be empty for
     * rows that received an error. Results are not returned when validate_only is
     * true.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.services.ClickConversionResult results = 2;</code>
     */
    private $results;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Rpc\Status $partial_failure_error
     *           Errors that pertain to conversion failures in the partial failure mode.
     *           Returned when all errors occur inside the conversions. If any errors occur
     *           outside the conversions (e.g. auth errors), we return an RPC level error.
     *           See
     *           https://developers.google.com/google-ads/api/docs/best-practices/partial-failures
     *           for more information about partial failure.
     *     @type \Google\Ads\GoogleAds\V5\Services\ClickConversionResult[]|\Google\Protobuf\Internal\RepeatedField $results
     *           Returned for successfully processed conversions. Proto will be empty for
     *           rows that received an error. Results are not returned when validate_only is
     *           true.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V5\Services\ConversionUploadService::initOnce();
        parent::__construct($data);
    }

    /**
     * Errors that pertain to conversion failures in the partial failure mode.
     * Returned when all errors occur inside the conversions. If any errors occur
     * outside the conversions (e.g. auth errors), we return an RPC level error.
     * See
     * https://developers.google.com/google-ads/api/docs/best-practices/partial-failures
     * for more information about partial failure.
     *
     * Generated from protobuf field <code>.google.rpc.Status partial_failure_error = 1;</code>
     * @return \Google\Rpc\Status
     */
    public function getPartialFailureError()
    {
        return isset($this->partial_failure_error) ? $this->partial_failure_error : null;
    }

    public function hasPartialFailureError()
    {
        return isset($this->partial_failure_error);
    }

    public function clearPartialFailureError()
    {
        unset($this->partial_failure_error);
    }

    /**
     * Errors that pertain to conversion failures in the partial failure mode.
     * Returned when all errors occur inside the conversions. If any errors occur
     * outside the conversions (e.g. auth errors), we return an RPC level error.
     * See
     * https://developers.google.com/google-ads/api/docs/best-practices/partial-failures
     * for more information about partial failure.
     *
     * Generated from protobuf field <code>.google.rpc.Status partial_failure_error = 1;</code>
     * @param \Google\Rpc\Status $var
     * @return $this
     */
    public function setPartialFailureError($var)
    {
        GPBUtil::checkMessage($var, \Google\Rpc\Status::class);
        $this->partial_failure_error = $var;

        return $this;
    }

    /**
     * Returned for successfully processed conversions. Proto will be empty for
     * rows that received an error. Results are not returned when validate_only is
     * true.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.services.ClickConversionResult results = 2;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * Returned for successfully processed conversions. Proto will be empty for
     * rows that received an error. Results are not returned when validate_only is
     * true.
     *
     * Generated from protobuf field <code>repeated .google.ads.googleads.v5.services.ClickConversionResult results = 2;</code>
     * @param \Google\Ads\GoogleAds\V5\Services\ClickConversionResult[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setResults($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Ads\GoogleAds\V5\Services\ClickConversionResult::class);
        $this->results = $arr;

        return $this;
    }

}

