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

class Google_Service_Compute_ExchangedPeeringRoute extends Google_Model
{
  public $destRange;
  public $imported;
  public $nextHopRegion;
  public $priority;
  public $type;

  public function setDestRange($destRange)
  {
    $this->destRange = $destRange;
  }
  public function getDestRange()
  {
    return $this->destRange;
  }
  public function setImported($imported)
  {
    $this->imported = $imported;
  }
  public function getImported()
  {
    return $this->imported;
  }
  public function setNextHopRegion($nextHopRegion)
  {
    $this->nextHopRegion = $nextHopRegion;
  }
  public function getNextHopRegion()
  {
    return $this->nextHopRegion;
  }
  public function setPriority($priority)
  {
    $this->priority = $priority;
  }
  public function getPriority()
  {
    return $this->priority;
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
