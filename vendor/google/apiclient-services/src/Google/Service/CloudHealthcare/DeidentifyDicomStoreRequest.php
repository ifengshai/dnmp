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

class Google_Service_CloudHealthcare_DeidentifyDicomStoreRequest extends Google_Model
{
  protected $configType = 'Google_Service_CloudHealthcare_DeidentifyConfig';
  protected $configDataType = '';
  public $destinationStore;
  protected $filterConfigType = 'Google_Service_CloudHealthcare_DicomFilterConfig';
  protected $filterConfigDataType = '';

  /**
   * @param Google_Service_CloudHealthcare_DeidentifyConfig
   */
  public function setConfig(Google_Service_CloudHealthcare_DeidentifyConfig $config)
  {
    $this->config = $config;
  }
  /**
   * @return Google_Service_CloudHealthcare_DeidentifyConfig
   */
  public function getConfig()
  {
    return $this->config;
  }
  public function setDestinationStore($destinationStore)
  {
    $this->destinationStore = $destinationStore;
  }
  public function getDestinationStore()
  {
    return $this->destinationStore;
  }
  /**
   * @param Google_Service_CloudHealthcare_DicomFilterConfig
   */
  public function setFilterConfig(Google_Service_CloudHealthcare_DicomFilterConfig $filterConfig)
  {
    $this->filterConfig = $filterConfig;
  }
  /**
   * @return Google_Service_CloudHealthcare_DicomFilterConfig
   */
  public function getFilterConfig()
  {
    return $this->filterConfig;
  }
}
