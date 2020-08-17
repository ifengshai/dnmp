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
 * The "assets" collection of methods.
 * Typical usage is:
 *  <code>
 *   $polyService = new Google_Service_PolyService(...);
 *   $assets = $polyService->assets;
 *  </code>
 */
class Google_Service_PolyService_Resource_UsersAssets extends Google_Service_Resource
{
  /**
   * Lists assets authored by the given user. Only the value 'me', representing
   * the currently-authenticated user, is supported. May include assets with an
   * access level of PRIVATE or UNLISTED and assets which are All Rights Reserved
   * for the currently-authenticated user. (assets.listUsersAssets)
   *
   * @param string $name A valid user id. Currently, only the special value 'me',
   * representing the currently-authenticated user is supported. To use 'me', you
   * must pass an OAuth token with the request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param int pageSize The maximum number of assets to be returned. This
   * value must be between `1` and `100`. Defaults to `20`.
   * @opt_param string visibility The visibility of the assets to be returned.
   * Defaults to VISIBILITY_UNSPECIFIED which returns all assets.
   * @opt_param string orderBy Specifies an ordering for assets. Acceptable values
   * are: `BEST`, `NEWEST`, `OLDEST`. Defaults to `BEST`, which ranks assets based
   * on a combination of popularity and other features.
   * @opt_param string format Return only assets with the matching format.
   * Acceptable values are: `BLOCKS`, `FBX`, `GLTF`, `GLTF2`, `OBJ`, and `TILT`.
   * @opt_param string pageToken Specifies a continuation token from a previous
   * search whose results were split into multiple pages. To get the next page,
   * submit the same request specifying the value from next_page_token.
   * @return Google_Service_PolyService_ListUserAssetsResponse
   */
  public function listUsersAssets($name, $optParams = array())
  {
    $params = array('name' => $name);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_PolyService_ListUserAssetsResponse");
  }
}
