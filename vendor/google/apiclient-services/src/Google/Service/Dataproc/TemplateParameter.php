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

class Google_Service_Dataproc_TemplateParameter extends Google_Collection
{
  protected $collection_key = 'fields';
  public $description;
  public $fields;
  public $name;
  protected $validationType = 'Google_Service_Dataproc_ParameterValidation';
  protected $validationDataType = '';

  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setFields($fields)
  {
    $this->fields = $fields;
  }
  public function getFields()
  {
    return $this->fields;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  /**
   * @param Google_Service_Dataproc_ParameterValidation
   */
  public function setValidation(Google_Service_Dataproc_ParameterValidation $validation)
  {
    $this->validation = $validation;
  }
  /**
   * @return Google_Service_Dataproc_ParameterValidation
   */
  public function getValidation()
  {
    return $this->validation;
  }
}
