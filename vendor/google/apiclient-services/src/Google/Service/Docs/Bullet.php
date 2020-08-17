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

class Google_Service_Docs_Bullet extends Google_Model
{
  public $listId;
  public $nestingLevel;
  protected $textStyleType = 'Google_Service_Docs_TextStyle';
  protected $textStyleDataType = '';

  public function setListId($listId)
  {
    $this->listId = $listId;
  }
  public function getListId()
  {
    return $this->listId;
  }
  public function setNestingLevel($nestingLevel)
  {
    $this->nestingLevel = $nestingLevel;
  }
  public function getNestingLevel()
  {
    return $this->nestingLevel;
  }
  /**
   * @param Google_Service_Docs_TextStyle
   */
  public function setTextStyle(Google_Service_Docs_TextStyle $textStyle)
  {
    $this->textStyle = $textStyle;
  }
  /**
   * @return Google_Service_Docs_TextStyle
   */
  public function getTextStyle()
  {
    return $this->textStyle;
  }
}
