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

class Google_Service_ServiceManagement_HttpRule extends Google_Collection
{
  protected $collection_key = 'additionalBindings';
  protected $additionalBindingsType = 'Google_Service_ServiceManagement_HttpRule';
  protected $additionalBindingsDataType = 'array';
  public $allowHalfDuplex;
  public $body;
  protected $customType = 'Google_Service_ServiceManagement_CustomHttpPattern';
  protected $customDataType = '';
  public $delete;
  public $get;
  public $patch;
  public $post;
  public $put;
  public $responseBody;
  public $selector;

  /**
   * @param Google_Service_ServiceManagement_HttpRule
   */
  public function setAdditionalBindings($additionalBindings)
  {
    $this->additionalBindings = $additionalBindings;
  }
  /**
   * @return Google_Service_ServiceManagement_HttpRule
   */
  public function getAdditionalBindings()
  {
    return $this->additionalBindings;
  }
  public function setAllowHalfDuplex($allowHalfDuplex)
  {
    $this->allowHalfDuplex = $allowHalfDuplex;
  }
  public function getAllowHalfDuplex()
  {
    return $this->allowHalfDuplex;
  }
  public function setBody($body)
  {
    $this->body = $body;
  }
  public function getBody()
  {
    return $this->body;
  }
  /**
   * @param Google_Service_ServiceManagement_CustomHttpPattern
   */
  public function setCustom(Google_Service_ServiceManagement_CustomHttpPattern $custom)
  {
    $this->custom = $custom;
  }
  /**
   * @return Google_Service_ServiceManagement_CustomHttpPattern
   */
  public function getCustom()
  {
    return $this->custom;
  }
  public function setDelete($delete)
  {
    $this->delete = $delete;
  }
  public function getDelete()
  {
    return $this->delete;
  }
  public function setGet($get)
  {
    $this->get = $get;
  }
  public function getGet()
  {
    return $this->get;
  }
  public function setPatch($patch)
  {
    $this->patch = $patch;
  }
  public function getPatch()
  {
    return $this->patch;
  }
  public function setPost($post)
  {
    $this->post = $post;
  }
  public function getPost()
  {
    return $this->post;
  }
  public function setPut($put)
  {
    $this->put = $put;
  }
  public function getPut()
  {
    return $this->put;
  }
  public function setResponseBody($responseBody)
  {
    $this->responseBody = $responseBody;
  }
  public function getResponseBody()
  {
    return $this->responseBody;
  }
  public function setSelector($selector)
  {
    $this->selector = $selector;
  }
  public function getSelector()
  {
    return $this->selector;
  }
}
