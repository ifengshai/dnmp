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

class Google_Service_ContainerAnalysis_GoogleDevtoolsContaineranalysisV1alpha1CloudRepoSourceContext extends Google_Model
{
  protected $aliasContextType = 'Google_Service_ContainerAnalysis_GoogleDevtoolsContaineranalysisV1alpha1AliasContext';
  protected $aliasContextDataType = '';
  protected $repoIdType = 'Google_Service_ContainerAnalysis_GoogleDevtoolsContaineranalysisV1alpha1RepoId';
  protected $repoIdDataType = '';
  public $revisionId;

  /**
   * @param Google_Service_ContainerAnalysis_GoogleDevtoolsContaineranalysisV1alpha1AliasContext
   */
  public function setAliasContext(Google_Service_ContainerAnalysis_GoogleDevtoolsContaineranalysisV1alpha1AliasContext $aliasContext)
  {
    $this->aliasContext = $aliasContext;
  }
  /**
   * @return Google_Service_ContainerAnalysis_GoogleDevtoolsContaineranalysisV1alpha1AliasContext
   */
  public function getAliasContext()
  {
    return $this->aliasContext;
  }
  /**
   * @param Google_Service_ContainerAnalysis_GoogleDevtoolsContaineranalysisV1alpha1RepoId
   */
  public function setRepoId(Google_Service_ContainerAnalysis_GoogleDevtoolsContaineranalysisV1alpha1RepoId $repoId)
  {
    $this->repoId = $repoId;
  }
  /**
   * @return Google_Service_ContainerAnalysis_GoogleDevtoolsContaineranalysisV1alpha1RepoId
   */
  public function getRepoId()
  {
    return $this->repoId;
  }
  public function setRevisionId($revisionId)
  {
    $this->revisionId = $revisionId;
  }
  public function getRevisionId()
  {
    return $this->revisionId;
  }
}
