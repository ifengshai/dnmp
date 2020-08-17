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
 * The "returnpolicy" collection of methods.
 * Typical usage is:
 *  <code>
 *   $contentService = new Google_Service_ShoppingContent(...);
 *   $returnpolicy = $contentService->returnpolicy;
 *  </code>
 */
class Google_Service_ShoppingContent_Resource_Returnpolicy extends Google_Service_Resource
{
  /**
   * Batches multiple return policy related calls in a single request.
   * (returnpolicy.custombatch)
   *
   * @param Google_Service_ShoppingContent_ReturnpolicyCustomBatchRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_ShoppingContent_ReturnpolicyCustomBatchResponse
   */
  public function custombatch(Google_Service_ShoppingContent_ReturnpolicyCustomBatchRequest $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('custombatch', array($params), "Google_Service_ShoppingContent_ReturnpolicyCustomBatchResponse");
  }
  /**
   * Deletes a return policy for the given Merchant Center account.
   * (returnpolicy.delete)
   *
   * @param string $merchantId The Merchant Center account from which to delete
   * the given return policy.
   * @param string $returnPolicyId Return policy ID generated by Google.
   * @param array $optParams Optional parameters.
   */
  public function delete($merchantId, $returnPolicyId, $optParams = array())
  {
    $params = array('merchantId' => $merchantId, 'returnPolicyId' => $returnPolicyId);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }
  /**
   * Gets a return policy of the Merchant Center account. (returnpolicy.get)
   *
   * @param string $merchantId The Merchant Center account to get a return policy
   * for.
   * @param string $returnPolicyId Return policy ID generated by Google.
   * @param array $optParams Optional parameters.
   * @return Google_Service_ShoppingContent_ReturnPolicy
   */
  public function get($merchantId, $returnPolicyId, $optParams = array())
  {
    $params = array('merchantId' => $merchantId, 'returnPolicyId' => $returnPolicyId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_ShoppingContent_ReturnPolicy");
  }
  /**
   * Inserts a return policy for the Merchant Center account.
   * (returnpolicy.insert)
   *
   * @param string $merchantId The Merchant Center account to insert a return
   * policy for.
   * @param Google_Service_ShoppingContent_ReturnPolicy $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_ShoppingContent_ReturnPolicy
   */
  public function insert($merchantId, Google_Service_ShoppingContent_ReturnPolicy $postBody, $optParams = array())
  {
    $params = array('merchantId' => $merchantId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_ShoppingContent_ReturnPolicy");
  }
  /**
   * Lists the return policies of the Merchant Center account.
   * (returnpolicy.listReturnpolicy)
   *
   * @param string $merchantId The Merchant Center account to list return policies
   * for.
   * @param array $optParams Optional parameters.
   * @return Google_Service_ShoppingContent_ReturnpolicyListResponse
   */
  public function listReturnpolicy($merchantId, $optParams = array())
  {
    $params = array('merchantId' => $merchantId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_ShoppingContent_ReturnpolicyListResponse");
  }
}
