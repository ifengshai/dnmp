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

class Google_Service_Document_GoogleCloudDocumentaiV1beta2DocumentPage extends Google_Collection
{
  protected $collection_key = 'visualElements';
  protected $blocksType = 'Google_Service_Document_GoogleCloudDocumentaiV1beta2DocumentPageBlock';
  protected $blocksDataType = 'array';
  protected $detectedLanguagesType = 'Google_Service_Document_GoogleCloudDocumentaiV1beta2DocumentPageDetectedLanguage';
  protected $detectedLanguagesDataType = 'array';
  protected $dimensionType = 'Google_Service_Document_GoogleCloudDocumentaiV1beta2DocumentPageDimension';
  protected $dimensionDataType = '';
  protected $formFieldsType = 'Google_Service_Document_GoogleCloudDocumentaiV1beta2DocumentPageFormField';
  protected $formFieldsDataType = 'array';
  protected $layoutType = 'Google_Service_Document_GoogleCloudDocumentaiV1beta2DocumentPageLayout';
  protected $layoutDataType = '';
  protected $linesType = 'Google_Service_Document_GoogleCloudDocumentaiV1beta2DocumentPageLine';
  protected $linesDataType = 'array';
  public $pageNumber;
  protected $paragraphsType = 'Google_Service_Document_GoogleCloudDocumentaiV1beta2DocumentPageParagraph';
  protected $paragraphsDataType = 'array';
  protected $tablesType = 'Google_Service_Document_GoogleCloudDocumentaiV1beta2DocumentPageTable';
  protected $tablesDataType = 'array';
  protected $tokensType = 'Google_Service_Document_GoogleCloudDocumentaiV1beta2DocumentPageToken';
  protected $tokensDataType = 'array';
  protected $visualElementsType = 'Google_Service_Document_GoogleCloudDocumentaiV1beta2DocumentPageVisualElement';
  protected $visualElementsDataType = 'array';

  /**
   * @param Google_Service_Document_GoogleCloudDocumentaiV1beta2DocumentPageBlock
   */
  public function setBlocks($blocks)
  {
    $this->blocks = $blocks;
  }
  /**
   * @return Google_Service_Document_GoogleCloudDocumentaiV1beta2DocumentPageBlock
   */
  public function getBlocks()
  {
    return $this->blocks;
  }
  /**
   * @param Google_Service_Document_GoogleCloudDocumentaiV1beta2DocumentPageDetectedLanguage
   */
  public function setDetectedLanguages($detectedLanguages)
  {
    $this->detectedLanguages = $detectedLanguages;
  }
  /**
   * @return Google_Service_Document_GoogleCloudDocumentaiV1beta2DocumentPageDetectedLanguage
   */
  public function getDetectedLanguages()
  {
    return $this->detectedLanguages;
  }
  /**
   * @param Google_Service_Document_GoogleCloudDocumentaiV1beta2DocumentPageDimension
   */
  public function setDimension(Google_Service_Document_GoogleCloudDocumentaiV1beta2DocumentPageDimension $dimension)
  {
    $this->dimension = $dimension;
  }
  /**
   * @return Google_Service_Document_GoogleCloudDocumentaiV1beta2DocumentPageDimension
   */
  public function getDimension()
  {
    return $this->dimension;
  }
  /**
   * @param Google_Service_Document_GoogleCloudDocumentaiV1beta2DocumentPageFormField
   */
  public function setFormFields($formFields)
  {
    $this->formFields = $formFields;
  }
  /**
   * @return Google_Service_Document_GoogleCloudDocumentaiV1beta2DocumentPageFormField
   */
  public function getFormFields()
  {
    return $this->formFields;
  }
  /**
   * @param Google_Service_Document_GoogleCloudDocumentaiV1beta2DocumentPageLayout
   */
  public function setLayout(Google_Service_Document_GoogleCloudDocumentaiV1beta2DocumentPageLayout $layout)
  {
    $this->layout = $layout;
  }
  /**
   * @return Google_Service_Document_GoogleCloudDocumentaiV1beta2DocumentPageLayout
   */
  public function getLayout()
  {
    return $this->layout;
  }
  /**
   * @param Google_Service_Document_GoogleCloudDocumentaiV1beta2DocumentPageLine
   */
  public function setLines($lines)
  {
    $this->lines = $lines;
  }
  /**
   * @return Google_Service_Document_GoogleCloudDocumentaiV1beta2DocumentPageLine
   */
  public function getLines()
  {
    return $this->lines;
  }
  public function setPageNumber($pageNumber)
  {
    $this->pageNumber = $pageNumber;
  }
  public function getPageNumber()
  {
    return $this->pageNumber;
  }
  /**
   * @param Google_Service_Document_GoogleCloudDocumentaiV1beta2DocumentPageParagraph
   */
  public function setParagraphs($paragraphs)
  {
    $this->paragraphs = $paragraphs;
  }
  /**
   * @return Google_Service_Document_GoogleCloudDocumentaiV1beta2DocumentPageParagraph
   */
  public function getParagraphs()
  {
    return $this->paragraphs;
  }
  /**
   * @param Google_Service_Document_GoogleCloudDocumentaiV1beta2DocumentPageTable
   */
  public function setTables($tables)
  {
    $this->tables = $tables;
  }
  /**
   * @return Google_Service_Document_GoogleCloudDocumentaiV1beta2DocumentPageTable
   */
  public function getTables()
  {
    return $this->tables;
  }
  /**
   * @param Google_Service_Document_GoogleCloudDocumentaiV1beta2DocumentPageToken
   */
  public function setTokens($tokens)
  {
    $this->tokens = $tokens;
  }
  /**
   * @return Google_Service_Document_GoogleCloudDocumentaiV1beta2DocumentPageToken
   */
  public function getTokens()
  {
    return $this->tokens;
  }
  /**
   * @param Google_Service_Document_GoogleCloudDocumentaiV1beta2DocumentPageVisualElement
   */
  public function setVisualElements($visualElements)
  {
    $this->visualElements = $visualElements;
  }
  /**
   * @return Google_Service_Document_GoogleCloudDocumentaiV1beta2DocumentPageVisualElement
   */
  public function getVisualElements()
  {
    return $this->visualElements;
  }
}
