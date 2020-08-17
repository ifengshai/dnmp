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

class Google_Service_Drive_Change extends Google_Model
{
  public $changeType;
  protected $driveType = 'Google_Service_Drive_Drive';
  protected $driveDataType = '';
  public $driveId;
  protected $fileType = 'Google_Service_Drive_DriveFile';
  protected $fileDataType = '';
  public $fileId;
  public $kind;
  public $removed;
  protected $teamDriveType = 'Google_Service_Drive_TeamDrive';
  protected $teamDriveDataType = '';
  public $teamDriveId;
  public $time;
  public $type;

  public function setChangeType($changeType)
  {
    $this->changeType = $changeType;
  }
  public function getChangeType()
  {
    return $this->changeType;
  }
  /**
   * @param Google_Service_Drive_Drive
   */
  public function setDrive(Google_Service_Drive_Drive $drive)
  {
    $this->drive = $drive;
  }
  /**
   * @return Google_Service_Drive_Drive
   */
  public function getDrive()
  {
    return $this->drive;
  }
  public function setDriveId($driveId)
  {
    $this->driveId = $driveId;
  }
  public function getDriveId()
  {
    return $this->driveId;
  }
  /**
   * @param Google_Service_Drive_DriveFile
   */
  public function setFile(Google_Service_Drive_DriveFile $file)
  {
    $this->file = $file;
  }
  /**
   * @return Google_Service_Drive_DriveFile
   */
  public function getFile()
  {
    return $this->file;
  }
  public function setFileId($fileId)
  {
    $this->fileId = $fileId;
  }
  public function getFileId()
  {
    return $this->fileId;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setRemoved($removed)
  {
    $this->removed = $removed;
  }
  public function getRemoved()
  {
    return $this->removed;
  }
  /**
   * @param Google_Service_Drive_TeamDrive
   */
  public function setTeamDrive(Google_Service_Drive_TeamDrive $teamDrive)
  {
    $this->teamDrive = $teamDrive;
  }
  /**
   * @return Google_Service_Drive_TeamDrive
   */
  public function getTeamDrive()
  {
    return $this->teamDrive;
  }
  public function setTeamDriveId($teamDriveId)
  {
    $this->teamDriveId = $teamDriveId;
  }
  public function getTeamDriveId()
  {
    return $this->teamDriveId;
  }
  public function setTime($time)
  {
    $this->time = $time;
  }
  public function getTime()
  {
    return $this->time;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
}
