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
 * The "superChatEvents" collection of methods.
 * Typical usage is:
 *  <code>
 *   $youtubeService = new Google_Service_YouTube(...);
 *   $superChatEvents = $youtubeService->superChatEvents;
 *  </code>
 */
class Google_Service_YouTube_Resource_SuperChatEvents extends Google_Service_Resource
{
  /**
   * Retrieves a list of resources, possibly filtered.
   * (superChatEvents.listSuperChatEvents)
   *
   * @param string|array $part The part parameter specifies the superChatEvent
   * resource parts that the API response will include. Supported values are id
   * and snippet.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string maxResults The maxResults parameter specifies the maximum
   * number of items that should be returned in the result set.
   * @opt_param string pageToken The pageToken parameter identifies a specific
   * page in the result set that should be returned. In an API response, the
   * nextPageToken and prevPageToken properties identify other pages that could be
   * retrieved.
   * @opt_param string hl Return rendered funding amounts in specified language.
   * @return Google_Service_YouTube_SuperChatEventListResponse
   */
  public function listSuperChatEvents($part, $optParams = array())
  {
    $params = array('part' => $part);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_YouTube_SuperChatEventListResponse");
  }
}
