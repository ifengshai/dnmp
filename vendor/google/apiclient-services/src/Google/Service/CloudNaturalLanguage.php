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
 * Service definition for CloudNaturalLanguage (v1).
 *
 * <p>
 * Provides natural language understanding technologies, such as sentiment
 * analysis, entity recognition, entity sentiment analysis, and other text
 * annotations, to developers.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://cloud.google.com/natural-language/" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_CloudNaturalLanguage extends Google_Service
{
  /** Apply machine learning models to reveal the structure and meaning of text. */
  const CLOUD_LANGUAGE =
      "https://www.googleapis.com/auth/cloud-language";
  /** View and manage your data across Google Cloud Platform services. */
  const CLOUD_PLATFORM =
      "https://www.googleapis.com/auth/cloud-platform";

  public $documents;
  
  /**
   * Constructs the internal representation of the CloudNaturalLanguage service.
   *
   * @param Google_Client $client The client used to deliver requests.
   * @param string $rootUrl The root URL used for requests to the service.
   */
  public function __construct(Google_Client $client, $rootUrl = null)
  {
    parent::__construct($client);
    $this->rootUrl = $rootUrl ?: 'https://language.googleapis.com/';
    $this->servicePath = '';
    $this->batchPath = 'batch';
    $this->version = 'v1';
    $this->serviceName = 'language';

    $this->documents = new Google_Service_CloudNaturalLanguage_Resource_Documents(
        $this,
        $this->serviceName,
        'documents',
        array(
          'methods' => array(
            'analyzeEntities' => array(
              'path' => 'v1/documents:analyzeEntities',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),'analyzeEntitySentiment' => array(
              'path' => 'v1/documents:analyzeEntitySentiment',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),'analyzeSentiment' => array(
              'path' => 'v1/documents:analyzeSentiment',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),'analyzeSyntax' => array(
              'path' => 'v1/documents:analyzeSyntax',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),'annotateText' => array(
              'path' => 'v1/documents:annotateText',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),'classifyText' => array(
              'path' => 'v1/documents:classifyText',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),
          )
        )
    );
  }
}
