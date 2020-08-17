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

class Google_Service_CloudMachineLearningEngine_GoogleCloudMlV1TrainingOutput extends Google_Collection
{
  protected $collection_key = 'trials';
  protected $builtInAlgorithmOutputType = 'Google_Service_CloudMachineLearningEngine_GoogleCloudMlV1BuiltInAlgorithmOutput';
  protected $builtInAlgorithmOutputDataType = '';
  public $completedTrialCount;
  public $consumedMLUnits;
  public $hyperparameterMetricTag;
  public $isBuiltInAlgorithmJob;
  public $isHyperparameterTuningJob;
  protected $trialsType = 'Google_Service_CloudMachineLearningEngine_GoogleCloudMlV1HyperparameterOutput';
  protected $trialsDataType = 'array';

  /**
   * @param Google_Service_CloudMachineLearningEngine_GoogleCloudMlV1BuiltInAlgorithmOutput
   */
  public function setBuiltInAlgorithmOutput(Google_Service_CloudMachineLearningEngine_GoogleCloudMlV1BuiltInAlgorithmOutput $builtInAlgorithmOutput)
  {
    $this->builtInAlgorithmOutput = $builtInAlgorithmOutput;
  }
  /**
   * @return Google_Service_CloudMachineLearningEngine_GoogleCloudMlV1BuiltInAlgorithmOutput
   */
  public function getBuiltInAlgorithmOutput()
  {
    return $this->builtInAlgorithmOutput;
  }
  public function setCompletedTrialCount($completedTrialCount)
  {
    $this->completedTrialCount = $completedTrialCount;
  }
  public function getCompletedTrialCount()
  {
    return $this->completedTrialCount;
  }
  public function setConsumedMLUnits($consumedMLUnits)
  {
    $this->consumedMLUnits = $consumedMLUnits;
  }
  public function getConsumedMLUnits()
  {
    return $this->consumedMLUnits;
  }
  public function setHyperparameterMetricTag($hyperparameterMetricTag)
  {
    $this->hyperparameterMetricTag = $hyperparameterMetricTag;
  }
  public function getHyperparameterMetricTag()
  {
    return $this->hyperparameterMetricTag;
  }
  public function setIsBuiltInAlgorithmJob($isBuiltInAlgorithmJob)
  {
    $this->isBuiltInAlgorithmJob = $isBuiltInAlgorithmJob;
  }
  public function getIsBuiltInAlgorithmJob()
  {
    return $this->isBuiltInAlgorithmJob;
  }
  public function setIsHyperparameterTuningJob($isHyperparameterTuningJob)
  {
    $this->isHyperparameterTuningJob = $isHyperparameterTuningJob;
  }
  public function getIsHyperparameterTuningJob()
  {
    return $this->isHyperparameterTuningJob;
  }
  /**
   * @param Google_Service_CloudMachineLearningEngine_GoogleCloudMlV1HyperparameterOutput
   */
  public function setTrials($trials)
  {
    $this->trials = $trials;
  }
  /**
   * @return Google_Service_CloudMachineLearningEngine_GoogleCloudMlV1HyperparameterOutput
   */
  public function getTrials()
  {
    return $this->trials;
  }
}
