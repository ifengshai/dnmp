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

class Google_Service_CommentAnalyzer_SuggestCommentScoreRequest extends Google_Collection
{
  protected $collection_key = 'languages';
  protected $attributeScoresType = 'Google_Service_CommentAnalyzer_AttributeScores';
  protected $attributeScoresDataType = 'map';
  public $clientToken;
  protected $commentType = 'Google_Service_CommentAnalyzer_TextEntry';
  protected $commentDataType = '';
  public $communityId;
  protected $contextType = 'Google_Service_CommentAnalyzer_Context';
  protected $contextDataType = '';
  public $languages;
  public $sessionId;

  /**
   * @param Google_Service_CommentAnalyzer_AttributeScores
   */
  public function setAttributeScores($attributeScores)
  {
    $this->attributeScores = $attributeScores;
  }
  /**
   * @return Google_Service_CommentAnalyzer_AttributeScores
   */
  public function getAttributeScores()
  {
    return $this->attributeScores;
  }
  public function setClientToken($clientToken)
  {
    $this->clientToken = $clientToken;
  }
  public function getClientToken()
  {
    return $this->clientToken;
  }
  /**
   * @param Google_Service_CommentAnalyzer_TextEntry
   */
  public function setComment(Google_Service_CommentAnalyzer_TextEntry $comment)
  {
    $this->comment = $comment;
  }
  /**
   * @return Google_Service_CommentAnalyzer_TextEntry
   */
  public function getComment()
  {
    return $this->comment;
  }
  public function setCommunityId($communityId)
  {
    $this->communityId = $communityId;
  }
  public function getCommunityId()
  {
    return $this->communityId;
  }
  /**
   * @param Google_Service_CommentAnalyzer_Context
   */
  public function setContext(Google_Service_CommentAnalyzer_Context $context)
  {
    $this->context = $context;
  }
  /**
   * @return Google_Service_CommentAnalyzer_Context
   */
  public function getContext()
  {
    return $this->context;
  }
  public function setLanguages($languages)
  {
    $this->languages = $languages;
  }
  public function getLanguages()
  {
    return $this->languages;
  }
  public function setSessionId($sessionId)
  {
    $this->sessionId = $sessionId;
  }
  public function getSessionId()
  {
    return $this->sessionId;
  }
}
