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

class Google_Service_RecommendationsAI_GoogleCloudRecommendationengineV1beta1ImportMetadata extends Google_Model
{
  public $createTime;
  public $failureCount;
  public $operationName;
  public $requestId;
  public $successCount;
  public $updateTime;

  public function setCreateTime($createTime)
  {
    $this->createTime = $createTime;
  }
  public function getCreateTime()
  {
    return $this->createTime;
  }
  public function setFailureCount($failureCount)
  {
    $this->failureCount = $failureCount;
  }
  public function getFailureCount()
  {
    return $this->failureCount;
  }
  public function setOperationName($operationName)
  {
    $this->operationName = $operationName;
  }
  public function getOperationName()
  {
    return $this->operationName;
  }
  public function setRequestId($requestId)
  {
    $this->requestId = $requestId;
  }
  public function getRequestId()
  {
    return $this->requestId;
  }
  public function setSuccessCount($successCount)
  {
    $this->successCount = $successCount;
  }
  public function getSuccessCount()
  {
    return $this->successCount;
  }
  public function setUpdateTime($updateTime)
  {
    $this->updateTime = $updateTime;
  }
  public function getUpdateTime()
  {
    return $this->updateTime;
  }
}
