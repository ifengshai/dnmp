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
 * The "operations" collection of methods.
 * Typical usage is:
 *  <code>
 *   $cloudfunctionsService = new Google_Service_CloudFunctions(...);
 *   $operations = $cloudfunctionsService->operations;
 *  </code>
 */
class Google_Service_CloudFunctions_Resource_Operations extends Google_Service_Resource
{
  /**
   * Gets the latest state of a long-running operation.  Clients can use this
   * method to poll the operation result at intervals as recommended by the API
   * service. (operations.get)
   *
   * @param string $name The name of the operation resource.
   * @param array $optParams Optional parameters.
   * @return Google_Service_CloudFunctions_Operation
   */
  public function get($name, $optParams = array())
  {
    $params = array('name' => $name);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_CloudFunctions_Operation");
  }
  /**
   * Lists operations that match the specified filter in the request. If the
   * server doesn't support this method, it returns `UNIMPLEMENTED`.
   *
   * NOTE: the `name` binding allows API services to override the binding to use
   * different resource name schemes, such as `users/operations`. To override the
   * binding, API services can add a binding such as
   * `"/v1/{name=users}/operations"` to their service configuration. For backwards
   * compatibility, the default name includes the operations collection id,
   * however overriding users must ensure the name binding is the parent resource,
   * without the operations collection id. (operations.listOperations)
   *
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter Required. A filter for matching the requested
   * operations. The supported formats of filter are: To query for a specific
   * function: project:*,location:*,function:* To query for all of the latest
   * operations for a project: project:*,latest:true
   * @opt_param string name Must not be set.
   * @opt_param string pageToken Token identifying which result to start with,
   * which is returned by a previous list call. Pagination is only supported when
   * querying for a specific function.
   * @opt_param int pageSize The maximum number of records that should be
   * returned. Requested page size cannot exceed 100. If not set, the default page
   * size is 100. Pagination is only supported when querying for a specific
   * function.
   * @return Google_Service_CloudFunctions_ListOperationsResponse
   */
  public function listOperations($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_CloudFunctions_ListOperationsResponse");
  }
}
