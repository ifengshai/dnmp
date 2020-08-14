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

class Google_Service_Compute_AllocationSpecificSKUAllocationReservedInstanceProperties extends Google_Collection
{
  protected $collection_key = 'localSsds';
  protected $guestAcceleratorsType = 'Google_Service_Compute_AcceleratorConfig';
  protected $guestAcceleratorsDataType = 'array';
  protected $localSsdsType = 'Google_Service_Compute_AllocationSpecificSKUAllocationAllocatedInstancePropertiesReservedDisk';
  protected $localSsdsDataType = 'array';
  public $machineType;
  public $maintenanceInterval;
  public $minCpuPlatform;

  /**
   * @param Google_Service_Compute_AcceleratorConfig
   */
  public function setGuestAccelerators($guestAccelerators)
  {
    $this->guestAccelerators = $guestAccelerators;
  }
  /**
   * @return Google_Service_Compute_AcceleratorConfig
   */
  public function getGuestAccelerators()
  {
    return $this->guestAccelerators;
  }
  /**
   * @param Google_Service_Compute_AllocationSpecificSKUAllocationAllocatedInstancePropertiesReservedDisk
   */
  public function setLocalSsds($localSsds)
  {
    $this->localSsds = $localSsds;
  }
  /**
   * @return Google_Service_Compute_AllocationSpecificSKUAllocationAllocatedInstancePropertiesReservedDisk
   */
  public function getLocalSsds()
  {
    return $this->localSsds;
  }
  public function setMachineType($machineType)
  {
    $this->machineType = $machineType;
  }
  public function getMachineType()
  {
    return $this->machineType;
  }
  public function setMaintenanceInterval($maintenanceInterval)
  {
    $this->maintenanceInterval = $maintenanceInterval;
  }
  public function getMaintenanceInterval()
  {
    return $this->maintenanceInterval;
  }
  public function setMinCpuPlatform($minCpuPlatform)
  {
    $this->minCpuPlatform = $minCpuPlatform;
  }
  public function getMinCpuPlatform()
  {
    return $this->minCpuPlatform;
  }
}
