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
 * The "channelSections" collection of methods.
 * Typical usage is:
 *  <code>
 *   $youtubeService = new Google_Service_YouTube(...);
 *   $channelSections = $youtubeService->channelSections;
 *  </code>
 */
class Google_Service_YouTube_Resource_ChannelSections extends Google_Service_Resource
{
  /**
   * Deletes a resource. (channelSections.delete)
   *
   * @param string $id
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.The onBehalfOfContentOwner parameter
   * indicates that the request's authorization credentials identify a YouTube CMS
   * user who is acting on behalf of the content owner specified in the parameter
   * value. This parameter is intended for YouTube content partners that own and
   * manage many different YouTube channels. It allows content owners to
   * authenticate once and get access to all their video and channel data, without
   * having to provide authentication credentials for each individual channel. The
   * CMS account that the user authenticates with must be linked to the specified
   * YouTube content owner.
   */
  public function delete($id, $optParams = array())
  {
    $params = array('id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }
  /**
   * Inserts a new resource into this collection. (channelSections.insert)
   *
   * @param string|array $part The part parameter serves two purposes in this
   * operation. It identifies the properties that the write operation will set as
   * well as the properties that the API response will include.The part names that
   * you can include in the parameter value are snippet and contentDetails.
   * @param Google_Service_YouTube_ChannelSection $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwnerChannel This parameter can only be
   * used in a properly authorized request. Note: This parameter is intended
   * exclusively for YouTube content partners.The onBehalfOfContentOwnerChannel
   * parameter specifies the YouTube channel ID of the channel to which a video is
   * being added. This parameter is required when a request specifies a value for
   * the onBehalfOfContentOwner parameter, and it can only be used in conjunction
   * with that parameter. In addition, the request must be authorized using a CMS
   * account that is linked to the content owner that the onBehalfOfContentOwner
   * parameter specifies. Finally, the channel that the
   * onBehalfOfContentOwnerChannel parameter value specifies must be linked to the
   * content owner that the onBehalfOfContentOwner parameter specifies.This
   * parameter is intended for YouTube content partners that own and manage many
   * different YouTube channels. It allows content owners to authenticate once and
   * perform actions on behalf of the channel specified in the parameter value,
   * without having to provide authentication credentials for each separate
   * channel.
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.The onBehalfOfContentOwner parameter
   * indicates that the request's authorization credentials identify a YouTube CMS
   * user who is acting on behalf of the content owner specified in the parameter
   * value. This parameter is intended for YouTube content partners that own and
   * manage many different YouTube channels. It allows content owners to
   * authenticate once and get access to all their video and channel data, without
   * having to provide authentication credentials for each individual channel. The
   * CMS account that the user authenticates with must be linked to the specified
   * YouTube content owner.
   * @return Google_Service_YouTube_ChannelSection
   */
  public function insert($part, Google_Service_YouTube_ChannelSection $postBody, $optParams = array())
  {
    $params = array('part' => $part, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_YouTube_ChannelSection");
  }
  /**
   * Retrieves a list of resources, possibly filtered.
   * (channelSections.listChannelSections)
   *
   * @param string|array $part The part parameter specifies a comma-separated list
   * of one or more channelSection resource properties that the API response will
   * include. The part names that you can include in the parameter value are id,
   * snippet, and contentDetails.If the parameter identifies a property that
   * contains child properties, the child properties will be included in the
   * response. For example, in a channelSection resource, the snippet property
   * contains other properties, such as a display title for the channelSection. If
   * you set part=snippet, the API response will also contain all of those nested
   * properties.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string channelId Return the ChannelSections owned by the specified
   * channel ID.
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.The onBehalfOfContentOwner parameter
   * indicates that the request's authorization credentials identify a YouTube CMS
   * user who is acting on behalf of the content owner specified in the parameter
   * value. This parameter is intended for YouTube content partners that own and
   * manage many different YouTube channels. It allows content owners to
   * authenticate once and get access to all their video and channel data, without
   * having to provide authentication credentials for each individual channel. The
   * CMS account that the user authenticates with must be linked to the specified
   * YouTube content owner.
   * @opt_param string id Return the ChannelSections with the given IDs for Stubby
   * or Apiary.
   * @opt_param bool mine Return the ChannelSections owned by the authenticated
   * user.
   * @opt_param string hl Return content in specified language
   * @return Google_Service_YouTube_ChannelSectionListResponse
   */
  public function listChannelSections($part, $optParams = array())
  {
    $params = array('part' => $part);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_YouTube_ChannelSectionListResponse");
  }
  /**
   * Updates an existing resource. (channelSections.update)
   *
   * @param string|array $part The part parameter serves two purposes in this
   * operation. It identifies the properties that the write operation will set as
   * well as the properties that the API response will include.The part names that
   * you can include in the parameter value are snippet and contentDetails.
   * @param Google_Service_YouTube_ChannelSection $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.The onBehalfOfContentOwner parameter
   * indicates that the request's authorization credentials identify a YouTube CMS
   * user who is acting on behalf of the content owner specified in the parameter
   * value. This parameter is intended for YouTube content partners that own and
   * manage many different YouTube channels. It allows content owners to
   * authenticate once and get access to all their video and channel data, without
   * having to provide authentication credentials for each individual channel. The
   * CMS account that the user authenticates with must be linked to the specified
   * YouTube content owner.
   * @return Google_Service_YouTube_ChannelSection
   */
  public function update($part, Google_Service_YouTube_ChannelSection $postBody, $optParams = array())
  {
    $params = array('part' => $part, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_YouTube_ChannelSection");
  }
}
