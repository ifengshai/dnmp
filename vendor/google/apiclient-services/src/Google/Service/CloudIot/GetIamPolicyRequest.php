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

class Google_Service_CloudIot_GetIamPolicyRequest extends Google_Model
{
  protected $optionsType = 'Google_Service_CloudIot_GetPolicyOptions';
  protected $optionsDataType = '';

  /**
   * @param Google_Service_CloudIot_GetPolicyOptions
   */
  public function setOptions(Google_Service_CloudIot_GetPolicyOptions $options)
  {
    $this->options = $options;
  }
  /**
   * @return Google_Service_CloudIot_GetPolicyOptions
   */
  public function getOptions()
  {
    return $this->options;
  }
}
