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

class Google_Service_AndroidEnterprise_ManagedConfigurationsForDeviceListResponse extends Google_Collection
{
  protected $collection_key = 'managedConfigurationForDevice';
  protected $managedConfigurationForDeviceType = 'Google_Service_AndroidEnterprise_ManagedConfiguration';
  protected $managedConfigurationForDeviceDataType = 'array';

  /**
   * @param Google_Service_AndroidEnterprise_ManagedConfiguration
   */
  public function setManagedConfigurationForDevice($managedConfigurationForDevice)
  {
    $this->managedConfigurationForDevice = $managedConfigurationForDevice;
  }
  /**
   * @return Google_Service_AndroidEnterprise_ManagedConfiguration
   */
  public function getManagedConfigurationForDevice()
  {
    return $this->managedConfigurationForDevice;
  }
}
