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

class Google_Service_Dialogflow_GoogleCloudDialogflowV2SessionEntityType extends Google_Collection
{
  protected $collection_key = 'entities';
  protected $entitiesType = 'Google_Service_Dialogflow_GoogleCloudDialogflowV2EntityTypeEntity';
  protected $entitiesDataType = 'array';
  public $entityOverrideMode;
  public $name;

  /**
   * @param Google_Service_Dialogflow_GoogleCloudDialogflowV2EntityTypeEntity
   */
  public function setEntities($entities)
  {
    $this->entities = $entities;
  }
  /**
   * @return Google_Service_Dialogflow_GoogleCloudDialogflowV2EntityTypeEntity
   */
  public function getEntities()
  {
    return $this->entities;
  }
  public function setEntityOverrideMode($entityOverrideMode)
  {
    $this->entityOverrideMode = $entityOverrideMode;
  }
  public function getEntityOverrideMode()
  {
    return $this->entityOverrideMode;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
}
