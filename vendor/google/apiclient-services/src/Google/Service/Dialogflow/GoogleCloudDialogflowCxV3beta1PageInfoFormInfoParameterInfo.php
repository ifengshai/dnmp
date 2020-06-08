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

class Google_Service_Dialogflow_GoogleCloudDialogflowCxV3beta1PageInfoFormInfoParameterInfo extends Google_Collection
{
  protected $collection_key = 'prompt';
  public $displayName;
  public $justCollected;
  protected $promptType = 'Google_Service_Dialogflow_GoogleCloudDialogflowCxV3beta1ResponseMessage';
  protected $promptDataType = 'array';
  public $required;
  public $state;
  public $value;

  public function setDisplayName($displayName)
  {
    $this->displayName = $displayName;
  }
  public function getDisplayName()
  {
    return $this->displayName;
  }
  public function setJustCollected($justCollected)
  {
    $this->justCollected = $justCollected;
  }
  public function getJustCollected()
  {
    return $this->justCollected;
  }
  /**
   * @param Google_Service_Dialogflow_GoogleCloudDialogflowCxV3beta1ResponseMessage
   */
  public function setPrompt($prompt)
  {
    $this->prompt = $prompt;
  }
  /**
   * @return Google_Service_Dialogflow_GoogleCloudDialogflowCxV3beta1ResponseMessage
   */
  public function getPrompt()
  {
    return $this->prompt;
  }
  public function setRequired($required)
  {
    $this->required = $required;
  }
  public function getRequired()
  {
    return $this->required;
  }
  public function setState($state)
  {
    $this->state = $state;
  }
  public function getState()
  {
    return $this->state;
  }
  public function setValue($value)
  {
    $this->value = $value;
  }
  public function getValue()
  {
    return $this->value;
  }
}
