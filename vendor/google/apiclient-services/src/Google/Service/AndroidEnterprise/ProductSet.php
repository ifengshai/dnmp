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

class Google_Service_AndroidEnterprise_ProductSet extends Google_Collection
{
  protected $collection_key = 'productVisibility';
  public $productId;
  public $productSetBehavior;
  protected $productVisibilityType = 'Google_Service_AndroidEnterprise_ProductVisibility';
  protected $productVisibilityDataType = 'array';

  public function setProductId($productId)
  {
    $this->productId = $productId;
  }
  public function getProductId()
  {
    return $this->productId;
  }
  public function setProductSetBehavior($productSetBehavior)
  {
    $this->productSetBehavior = $productSetBehavior;
  }
  public function getProductSetBehavior()
  {
    return $this->productSetBehavior;
  }
  /**
   * @param Google_Service_AndroidEnterprise_ProductVisibility
   */
  public function setProductVisibility($productVisibility)
  {
    $this->productVisibility = $productVisibility;
  }
  /**
   * @return Google_Service_AndroidEnterprise_ProductVisibility
   */
  public function getProductVisibility()
  {
    return $this->productVisibility;
  }
}
