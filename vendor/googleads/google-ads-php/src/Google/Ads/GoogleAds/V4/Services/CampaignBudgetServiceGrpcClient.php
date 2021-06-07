<?php
// GENERATED CODE -- DO NOT EDIT!

// Original file comments:
// Copyright 2020 Google LLC
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
namespace Google\Ads\GoogleAds\V4\Services;

/**
 * Proto file describing the Campaign Budget service.
 *
 * Service to manage campaign budgets.
 */
class CampaignBudgetServiceGrpcClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * Returns the requested Campaign Budget in full detail.
     * @param \Google\Ads\GoogleAds\V4\Services\GetCampaignBudgetRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Google\Ads\GoogleAds\V4\Resources\CampaignBudget
     */
    public function GetCampaignBudget(\Google\Ads\GoogleAds\V4\Services\GetCampaignBudgetRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/google.ads.googleads.v4.services.CampaignBudgetService/GetCampaignBudget',
        $argument,
        ['\Google\Ads\GoogleAds\V4\Resources\CampaignBudget', 'decode'],
        $metadata, $options);
    }

    /**
     * Creates, updates, or removes campaign budgets. Operation statuses are
     * returned.
     * @param \Google\Ads\GoogleAds\V4\Services\MutateCampaignBudgetsRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Google\Ads\GoogleAds\V4\Services\MutateCampaignBudgetsResponse
     */
    public function MutateCampaignBudgets(\Google\Ads\GoogleAds\V4\Services\MutateCampaignBudgetsRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/google.ads.googleads.v4.services.CampaignBudgetService/MutateCampaignBudgets',
        $argument,
        ['\Google\Ads\GoogleAds\V4\Services\MutateCampaignBudgetsResponse', 'decode'],
        $metadata, $options);
    }

}
