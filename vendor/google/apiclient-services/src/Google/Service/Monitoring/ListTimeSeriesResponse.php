<?php
/*
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */

class Google_Service_Monitoring_ListTimeSeriesResponse extends Google_Collection
{
  protected $collection_key = 'timeSeries';
  protected $executionErrorsType = 'Google_Service_Monitoring_Status';
  protected $executionErrorsDataType = 'array';
  public $nextPageToken;
  protected $timeSeriesType = 'Google_Service_Monitoring_TimeSeries';
  protected $timeSeriesDataType = 'array';
  public $unit;

  /**
   * @param Google_Service_Monitoring_Status
   */
  public function setExecutionErrors($executionErrors)
  {
    $this->executionErrors = $executionErrors;
  }
  /**
   * @return Google_Service_Monitoring_Status
   */
  public function getExecutionErrors()
  {
    return $this->executionErrors;
  }
  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  /**
   * @param Google_Service_Monitoring_TimeSeries
   */
  public function setTimeSeries($timeSeries)
  {
    $this->timeSeries = $timeSeries;
  }
  /**
   * @return Google_Service_Monitoring_TimeSeries
   */
  public function getTimeSeries()
  {
    return $this->timeSeries;
  }
  public function setUnit($unit)
  {
    $this->unit = $unit;
  }
  public function getUnit()
  {
    return $this->unit;
  }
}
