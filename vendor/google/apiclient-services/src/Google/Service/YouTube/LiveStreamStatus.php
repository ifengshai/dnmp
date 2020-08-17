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

class Google_Service_YouTube_LiveStreamStatus extends Google_Model
{
  protected $healthStatusType = 'Google_Service_YouTube_LiveStreamHealthStatus';
  protected $healthStatusDataType = '';
  public $streamStatus;

  /**
   * @param Google_Service_YouTube_LiveStreamHealthStatus
   */
  public function setHealthStatus(Google_Service_YouTube_LiveStreamHealthStatus $healthStatus)
  {
    $this->healthStatus = $healthStatus;
  }
  /**
   * @return Google_Service_YouTube_LiveStreamHealthStatus
   */
  public function getHealthStatus()
  {
    return $this->healthStatus;
  }
  public function setStreamStatus($streamStatus)
  {
    $this->streamStatus = $streamStatus;
  }
  public function getStreamStatus()
  {
    return $this->streamStatus;
  }
}
