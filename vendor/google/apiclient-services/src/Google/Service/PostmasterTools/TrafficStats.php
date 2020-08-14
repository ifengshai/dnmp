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

class Google_Service_PostmasterTools_TrafficStats extends Google_Collection
{
  protected $collection_key = 'spammyFeedbackLoops';
  protected $deliveryErrorsType = 'Google_Service_PostmasterTools_DeliveryError';
  protected $deliveryErrorsDataType = 'array';
  public $dkimSuccessRatio;
  public $dmarcSuccessRatio;
  public $domainReputation;
  public $inboundEncryptionRatio;
  protected $ipReputationsType = 'Google_Service_PostmasterTools_IpReputation';
  protected $ipReputationsDataType = 'array';
  public $name;
  public $outboundEncryptionRatio;
  protected $spammyFeedbackLoopsType = 'Google_Service_PostmasterTools_FeedbackLoop';
  protected $spammyFeedbackLoopsDataType = 'array';
  public $spfSuccessRatio;
  public $userReportedSpamRatio;

  /**
   * @param Google_Service_PostmasterTools_DeliveryError
   */
  public function setDeliveryErrors($deliveryErrors)
  {
    $this->deliveryErrors = $deliveryErrors;
  }
  /**
   * @return Google_Service_PostmasterTools_DeliveryError
   */
  public function getDeliveryErrors()
  {
    return $this->deliveryErrors;
  }
  public function setDkimSuccessRatio($dkimSuccessRatio)
  {
    $this->dkimSuccessRatio = $dkimSuccessRatio;
  }
  public function getDkimSuccessRatio()
  {
    return $this->dkimSuccessRatio;
  }
  public function setDmarcSuccessRatio($dmarcSuccessRatio)
  {
    $this->dmarcSuccessRatio = $dmarcSuccessRatio;
  }
  public function getDmarcSuccessRatio()
  {
    return $this->dmarcSuccessRatio;
  }
  public function setDomainReputation($domainReputation)
  {
    $this->domainReputation = $domainReputation;
  }
  public function getDomainReputation()
  {
    return $this->domainReputation;
  }
  public function setInboundEncryptionRatio($inboundEncryptionRatio)
  {
    $this->inboundEncryptionRatio = $inboundEncryptionRatio;
  }
  public function getInboundEncryptionRatio()
  {
    return $this->inboundEncryptionRatio;
  }
  /**
   * @param Google_Service_PostmasterTools_IpReputation
   */
  public function setIpReputations($ipReputations)
  {
    $this->ipReputations = $ipReputations;
  }
  /**
   * @return Google_Service_PostmasterTools_IpReputation
   */
  public function getIpReputations()
  {
    return $this->ipReputations;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setOutboundEncryptionRatio($outboundEncryptionRatio)
  {
    $this->outboundEncryptionRatio = $outboundEncryptionRatio;
  }
  public function getOutboundEncryptionRatio()
  {
    return $this->outboundEncryptionRatio;
  }
  /**
   * @param Google_Service_PostmasterTools_FeedbackLoop
   */
  public function setSpammyFeedbackLoops($spammyFeedbackLoops)
  {
    $this->spammyFeedbackLoops = $spammyFeedbackLoops;
  }
  /**
   * @return Google_Service_PostmasterTools_FeedbackLoop
   */
  public function getSpammyFeedbackLoops()
  {
    return $this->spammyFeedbackLoops;
  }
  public function setSpfSuccessRatio($spfSuccessRatio)
  {
    $this->spfSuccessRatio = $spfSuccessRatio;
  }
  public function getSpfSuccessRatio()
  {
    return $this->spfSuccessRatio;
  }
  public function setUserReportedSpamRatio($userReportedSpamRatio)
  {
    $this->userReportedSpamRatio = $userReportedSpamRatio;
  }
  public function getUserReportedSpamRatio()
  {
    return $this->userReportedSpamRatio;
  }
}
