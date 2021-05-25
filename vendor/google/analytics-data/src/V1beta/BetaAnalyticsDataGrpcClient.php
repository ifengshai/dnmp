<?php
// GENERATED CODE -- DO NOT EDIT!

// Original file comments:
// Copyright 2021 Google LLC
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
//     http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.
//
namespace Google\Analytics\Data\V1beta;

/**
 * Google Analytics reporting data service.
 */
class BetaAnalyticsDataGrpcClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * Returns a customized report of your Google Analytics event data. Reports
     * contain statistics derived from data collected by the Google Analytics
     * tracking code. The data returned from the API is as a table with columns
     * for the requested dimensions and metrics. Metrics are individual
     * measurements of user activity on your property, such as active users or
     * event count. Dimensions break down metrics across some common criteria,
     * such as country or event name.
     * @param \Google\Analytics\Data\V1beta\RunReportRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function RunReport(\Google\Analytics\Data\V1beta\RunReportRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/google.analytics.data.v1beta.BetaAnalyticsData/RunReport',
        $argument,
        ['\Google\Analytics\Data\V1beta\RunReportResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Returns a customized pivot report of your Google Analytics event data.
     * Pivot reports are more advanced and expressive formats than regular
     * reports. In a pivot report, dimensions are only visible if they are
     * included in a pivot. Multiple pivots can be specified to further dissect
     * your data.
     * @param \Google\Analytics\Data\V1beta\RunPivotReportRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function RunPivotReport(\Google\Analytics\Data\V1beta\RunPivotReportRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/google.analytics.data.v1beta.BetaAnalyticsData/RunPivotReport',
        $argument,
        ['\Google\Analytics\Data\V1beta\RunPivotReportResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Returns multiple reports in a batch. All reports must be for the same
     * GA4 Property.
     * @param \Google\Analytics\Data\V1beta\BatchRunReportsRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function BatchRunReports(\Google\Analytics\Data\V1beta\BatchRunReportsRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/google.analytics.data.v1beta.BetaAnalyticsData/BatchRunReports',
        $argument,
        ['\Google\Analytics\Data\V1beta\BatchRunReportsResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Returns multiple pivot reports in a batch. All reports must be for the same
     * GA4 Property.
     * @param \Google\Analytics\Data\V1beta\BatchRunPivotReportsRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function BatchRunPivotReports(\Google\Analytics\Data\V1beta\BatchRunPivotReportsRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/google.analytics.data.v1beta.BetaAnalyticsData/BatchRunPivotReports',
        $argument,
        ['\Google\Analytics\Data\V1beta\BatchRunPivotReportsResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Returns metadata for dimensions and metrics available in reporting methods.
     * Used to explore the dimensions and metrics. In this method, a Google
     * Analytics GA4 Property Identifier is specified in the request, and
     * the metadata response includes Custom dimensions and metrics as well as
     * Universal metadata.
     *
     * For example if a custom metric with parameter name `levels_unlocked` is
     * registered to a property, the Metadata response will contain
     * `customEvent:levels_unlocked`. Universal metadata are dimensions and
     * metrics applicable to any property such as `country` and `totalUsers`.
     * @param \Google\Analytics\Data\V1beta\GetMetadataRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GetMetadata(\Google\Analytics\Data\V1beta\GetMetadataRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/google.analytics.data.v1beta.BetaAnalyticsData/GetMetadata',
        $argument,
        ['\Google\Analytics\Data\V1beta\Metadata', 'decode'],
        $metadata, $options);
    }

    /**
     * The Google Analytics Realtime API returns a customized report of realtime
     * event data for your property. These reports show events and usage from the
     * last 30 minutes.
     * @param \Google\Analytics\Data\V1beta\RunRealtimeReportRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function RunRealtimeReport(\Google\Analytics\Data\V1beta\RunRealtimeReportRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/google.analytics.data.v1beta.BetaAnalyticsData/RunRealtimeReport',
        $argument,
        ['\Google\Analytics\Data\V1beta\RunRealtimeReportResponse', 'decode'],
        $metadata, $options);
    }

}
