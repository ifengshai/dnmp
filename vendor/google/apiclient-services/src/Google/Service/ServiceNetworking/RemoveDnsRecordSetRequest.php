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

class Google_Service_ServiceNetworking_RemoveDnsRecordSetRequest extends Google_Model
{
  public $consumerNetwork;
  protected $dnsRecordSetType = 'Google_Service_ServiceNetworking_DnsRecordSet';
  protected $dnsRecordSetDataType = '';
  public $zone;

  public function setConsumerNetwork($consumerNetwork)
  {
    $this->consumerNetwork = $consumerNetwork;
  }
  public function getConsumerNetwork()
  {
    return $this->consumerNetwork;
  }
  /**
   * @param Google_Service_ServiceNetworking_DnsRecordSet
   */
  public function setDnsRecordSet(Google_Service_ServiceNetworking_DnsRecordSet $dnsRecordSet)
  {
    $this->dnsRecordSet = $dnsRecordSet;
  }
  /**
   * @return Google_Service_ServiceNetworking_DnsRecordSet
   */
  public function getDnsRecordSet()
  {
    return $this->dnsRecordSet;
  }
  public function setZone($zone)
  {
    $this->zone = $zone;
  }
  public function getZone()
  {
    return $this->zone;
  }
}
