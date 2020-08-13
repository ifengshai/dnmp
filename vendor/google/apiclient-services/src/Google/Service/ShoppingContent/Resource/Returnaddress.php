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

/**
 * The "returnaddress" collection of methods.
 * Typical usage is:
 *  <code>
 *   $contentService = new Google_Service_ShoppingContent(...);
 *   $returnaddress = $contentService->returnaddress;
 *  </code>
 */
class Google_Service_ShoppingContent_Resource_Returnaddress extends Google_Service_Resource
{
  /**
   * Batches multiple return address related calls in a single request.
   * (returnaddress.custombatch)
   *
   * @param Google_Service_ShoppingContent_ReturnaddressCustomBatchRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_ShoppingContent_ReturnaddressCustomBatchResponse
   */
  public function custombatch(Google_Service_ShoppingContent_ReturnaddressCustomBatchRequest $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('custombatch', array($params), "Google_Service_ShoppingContent_ReturnaddressCustomBatchResponse");
  }
  /**
   * Deletes a return address for the given Merchant Center account.
   * (returnaddress.delete)
   *
   * @param string $merchantId The Merchant Center account from which to delete
   * the given return address.
   * @param string $returnAddressId Return address ID generated by Google.
   * @param array $optParams Optional parameters.
   */
  public function delete($merchantId, $returnAddressId, $optParams = array())
  {
    $params = array('merchantId' => $merchantId, 'returnAddressId' => $returnAddressId);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }
  /**
   * Gets a return address of the Merchant Center account. (returnaddress.get)
   *
   * @param string $merchantId The Merchant Center account to get a return address
   * for.
   * @param string $returnAddressId Return address ID generated by Google.
   * @param array $optParams Optional parameters.
   * @return Google_Service_ShoppingContent_ReturnAddress
   */
  public function get($merchantId, $returnAddressId, $optParams = array())
  {
    $params = array('merchantId' => $merchantId, 'returnAddressId' => $returnAddressId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_ShoppingContent_ReturnAddress");
  }
  /**
   * Inserts a return address for the Merchant Center account.
   * (returnaddress.insert)
   *
   * @param string $merchantId The Merchant Center account to insert a return
   * address for.
   * @param Google_Service_ShoppingContent_ReturnAddress $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_ShoppingContent_ReturnAddress
   */
  public function insert($merchantId, Google_Service_ShoppingContent_ReturnAddress $postBody, $optParams = array())
  {
    $params = array('merchantId' => $merchantId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_ShoppingContent_ReturnAddress");
  }
  /**
   * Lists the return addresses of the Merchant Center account.
   * (returnaddress.listReturnaddress)
   *
   * @param string $merchantId The Merchant Center account to list return
   * addresses for.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string country List only return addresses applicable to the given
   * country of sale. When omitted, all return addresses are listed.
   * @opt_param string maxResults The maximum number of addresses in the response,
   * used for paging.
   * @opt_param string pageToken The token returned by the previous request.
   * @return Google_Service_ShoppingContent_ReturnaddressListResponse
   */
  public function listReturnaddress($merchantId, $optParams = array())
  {
    $params = array('merchantId' => $merchantId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_ShoppingContent_ReturnaddressListResponse");
  }
}
