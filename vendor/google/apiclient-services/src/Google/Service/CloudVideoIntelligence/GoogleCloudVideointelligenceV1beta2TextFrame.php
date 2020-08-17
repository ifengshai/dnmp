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

class Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1beta2TextFrame extends Google_Model
{
  protected $rotatedBoundingBoxType = 'Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1beta2NormalizedBoundingPoly';
  protected $rotatedBoundingBoxDataType = '';
  public $timeOffset;

  /**
   * @param Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1beta2NormalizedBoundingPoly
   */
  public function setRotatedBoundingBox(Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1beta2NormalizedBoundingPoly $rotatedBoundingBox)
  {
    $this->rotatedBoundingBox = $rotatedBoundingBox;
  }
  /**
   * @return Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1beta2NormalizedBoundingPoly
   */
  public function getRotatedBoundingBox()
  {
    return $this->rotatedBoundingBox;
  }
  public function setTimeOffset($timeOffset)
  {
    $this->timeOffset = $timeOffset;
  }
  public function getTimeOffset()
  {
    return $this->timeOffset;
  }
}
