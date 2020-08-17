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

class Google_Service_Bigquery_RegressionMetrics extends Google_Model
{
  public $meanAbsoluteError;
  public $meanSquaredError;
  public $meanSquaredLogError;
  public $medianAbsoluteError;
  public $rSquared;

  public function setMeanAbsoluteError($meanAbsoluteError)
  {
    $this->meanAbsoluteError = $meanAbsoluteError;
  }
  public function getMeanAbsoluteError()
  {
    return $this->meanAbsoluteError;
  }
  public function setMeanSquaredError($meanSquaredError)
  {
    $this->meanSquaredError = $meanSquaredError;
  }
  public function getMeanSquaredError()
  {
    return $this->meanSquaredError;
  }
  public function setMeanSquaredLogError($meanSquaredLogError)
  {
    $this->meanSquaredLogError = $meanSquaredLogError;
  }
  public function getMeanSquaredLogError()
  {
    return $this->meanSquaredLogError;
  }
  public function setMedianAbsoluteError($medianAbsoluteError)
  {
    $this->medianAbsoluteError = $medianAbsoluteError;
  }
  public function getMedianAbsoluteError()
  {
    return $this->medianAbsoluteError;
  }
  public function setRSquared($rSquared)
  {
    $this->rSquared = $rSquared;
  }
  public function getRSquared()
  {
    return $this->rSquared;
  }
}
