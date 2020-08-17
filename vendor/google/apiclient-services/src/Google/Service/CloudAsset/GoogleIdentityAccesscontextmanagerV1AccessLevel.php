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

class Google_Service_CloudAsset_GoogleIdentityAccesscontextmanagerV1AccessLevel extends Google_Model
{
  protected $basicType = 'Google_Service_CloudAsset_GoogleIdentityAccesscontextmanagerV1BasicLevel';
  protected $basicDataType = '';
  protected $customType = 'Google_Service_CloudAsset_GoogleIdentityAccesscontextmanagerV1CustomLevel';
  protected $customDataType = '';
  public $description;
  public $name;
  public $title;

  /**
   * @param Google_Service_CloudAsset_GoogleIdentityAccesscontextmanagerV1BasicLevel
   */
  public function setBasic(Google_Service_CloudAsset_GoogleIdentityAccesscontextmanagerV1BasicLevel $basic)
  {
    $this->basic = $basic;
  }
  /**
   * @return Google_Service_CloudAsset_GoogleIdentityAccesscontextmanagerV1BasicLevel
   */
  public function getBasic()
  {
    return $this->basic;
  }
  /**
   * @param Google_Service_CloudAsset_GoogleIdentityAccesscontextmanagerV1CustomLevel
   */
  public function setCustom(Google_Service_CloudAsset_GoogleIdentityAccesscontextmanagerV1CustomLevel $custom)
  {
    $this->custom = $custom;
  }
  /**
   * @return Google_Service_CloudAsset_GoogleIdentityAccesscontextmanagerV1CustomLevel
   */
  public function getCustom()
  {
    return $this->custom;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setTitle($title)
  {
    $this->title = $title;
  }
  public function getTitle()
  {
    return $this->title;
  }
}
