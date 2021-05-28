<?php
/*
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/*
 * GENERATED CODE WARNING
 * This file was generated from the file
 * https://github.com/google/googleapis/blob/master/google/ads/googleads/v4/services/keyword_plan_ad_group_keyword_service.proto
 * and updates to that file get reflected here through a refresh process.
 *
 * @experimental
 */

namespace Google\Ads\GoogleAds\V4\Services\Gapic;

use Google\Ads\GoogleAds\V4\Resources\KeywordPlanAdGroupKeyword;
use Google\Ads\GoogleAds\V4\Services\GetKeywordPlanAdGroupKeywordRequest;
use Google\Ads\GoogleAds\V4\Services\KeywordPlanAdGroupKeywordOperation;
use Google\Ads\GoogleAds\V4\Services\MutateKeywordPlanAdGroupKeywordsRequest;
use Google\Ads\GoogleAds\V4\Services\MutateKeywordPlanAdGroupKeywordsResponse;
use Google\ApiCore\ApiException;
use Google\ApiCore\CredentialsWrapper;
use Google\ApiCore\GapicClientTrait;
use Google\ApiCore\PathTemplate;
use Google\ApiCore\RequestParamsHeaderDescriptor;
use Google\ApiCore\RetrySettings;
use Google\ApiCore\Transport\TransportInterface;
use Google\ApiCore\ValidationException;
use Google\Auth\FetchAuthTokenInterface;

/**
 * Service Description: Service to manage Keyword Plan ad group keywords. KeywordPlanAdGroup is
 * required to add ad group keywords. Positive and negative keywords are
 * supported. A maximum of 10,000 positive keywords are allowed per keyword
 * plan. A maximum of 1,000 negative keywords are allower per keyword plan. This
 * includes campaign negative keywords and ad group negative keywords.
 *
 * This class provides the ability to make remote calls to the backing service through method
 * calls that map to API methods. Sample code to get started:
 *
 * ```
 * $keywordPlanAdGroupKeywordServiceClient = new KeywordPlanAdGroupKeywordServiceClient();
 * try {
 *     $formattedResourceName = $keywordPlanAdGroupKeywordServiceClient->keywordPlanAdGroupKeywordName('[CUSTOMER]', '[KEYWORD_PLAN_AD_GROUP_KEYWORD]');
 *     $response = $keywordPlanAdGroupKeywordServiceClient->getKeywordPlanAdGroupKeyword($formattedResourceName);
 * } finally {
 *     $keywordPlanAdGroupKeywordServiceClient->close();
 * }
 * ```
 *
 * Many parameters require resource names to be formatted in a particular way. To assist
 * with these names, this class includes a format method for each type of name, and additionally
 * a parseName method to extract the individual identifiers contained within formatted names
 * that are returned by the API.
 *
 * @experimental
 */
class KeywordPlanAdGroupKeywordServiceGapicClient
{
    use GapicClientTrait;

    /**
     * The name of the service.
     */
    const SERVICE_NAME = 'google.ads.googleads.v4.services.KeywordPlanAdGroupKeywordService';

    /**
     * The default address of the service.
     */
    const SERVICE_ADDRESS = 'googleads.googleapis.com';

    /**
     * The default port of the service.
     */
    const DEFAULT_SERVICE_PORT = 443;

    /**
     * The name of the code generator, to be included in the agent header.
     */
    const CODEGEN_NAME = 'gapic';

    /**
     * The default scopes required by the service.
     */
    public static $serviceScopes = [
    ];
    private static $keywordPlanAdGroupKeywordNameTemplate;
    private static $pathTemplateMap;

    private static function getClientDefaults()
    {
        return [
            'serviceName' => self::SERVICE_NAME,
            'serviceAddress' => self::SERVICE_ADDRESS.':'.self::DEFAULT_SERVICE_PORT,
            'clientConfig' => __DIR__.'/../resources/keyword_plan_ad_group_keyword_service_client_config.json',
            'descriptorsConfigPath' => __DIR__.'/../resources/keyword_plan_ad_group_keyword_service_descriptor_config.php',
            'gcpApiConfigPath' => __DIR__.'/../resources/keyword_plan_ad_group_keyword_service_grpc_config.json',
            'credentialsConfig' => [
                'scopes' => self::$serviceScopes,
            ],
            'transportConfig' => [
                'rest' => [
                    'restClientConfigPath' => __DIR__.'/../resources/keyword_plan_ad_group_keyword_service_rest_client_config.php',
                ],
            ],
        ];
    }

    private static function getKeywordPlanAdGroupKeywordNameTemplate()
    {
        if (null == self::$keywordPlanAdGroupKeywordNameTemplate) {
            self::$keywordPlanAdGroupKeywordNameTemplate = new PathTemplate('customers/{customer}/keywordPlanAdGroupKeywords/{keyword_plan_ad_group_keyword}');
        }

        return self::$keywordPlanAdGroupKeywordNameTemplate;
    }

    private static function getPathTemplateMap()
    {
        if (null == self::$pathTemplateMap) {
            self::$pathTemplateMap = [
                'keywordPlanAdGroupKeyword' => self::getKeywordPlanAdGroupKeywordNameTemplate(),
            ];
        }

        return self::$pathTemplateMap;
    }

    /**
     * Formats a string containing the fully-qualified path to represent
     * a keyword_plan_ad_group_keyword resource.
     *
     * @param string $customer
     * @param string $keywordPlanAdGroupKeyword
     *
     * @return string The formatted keyword_plan_ad_group_keyword resource.
     * @experimental
     */
    public static function keywordPlanAdGroupKeywordName($customer, $keywordPlanAdGroupKeyword)
    {
        return self::getKeywordPlanAdGroupKeywordNameTemplate()->render([
            'customer' => $customer,
            'keyword_plan_ad_group_keyword' => $keywordPlanAdGroupKeyword,
        ]);
    }

    /**
     * Parses a formatted name string and returns an associative array of the components in the name.
     * The following name formats are supported:
     * Template: Pattern
     * - keywordPlanAdGroupKeyword: customers/{customer}/keywordPlanAdGroupKeywords/{keyword_plan_ad_group_keyword}.
     *
     * The optional $template argument can be supplied to specify a particular pattern, and must
     * match one of the templates listed above. If no $template argument is provided, or if the
     * $template argument does not match one of the templates listed, then parseName will check
     * each of the supported templates, and return the first match.
     *
     * @param string $formattedName The formatted name string
     * @param string $template      Optional name of template to match
     *
     * @return array An associative array from name component IDs to component values.
     *
     * @throws ValidationException If $formattedName could not be matched.
     * @experimental
     */
    public static function parseName($formattedName, $template = null)
    {
        $templateMap = self::getPathTemplateMap();

        if ($template) {
            if (!isset($templateMap[$template])) {
                throw new ValidationException("Template name $template does not exist");
            }

            return $templateMap[$template]->match($formattedName);
        }

        foreach ($templateMap as $templateName => $pathTemplate) {
            try {
                return $pathTemplate->match($formattedName);
            } catch (ValidationException $ex) {
                // Swallow the exception to continue trying other path templates
            }
        }
        throw new ValidationException("Input did not match any known format. Input: $formattedName");
    }

    /**
     * Constructor.
     *
     * @param array $options {
     *                       Optional. Options for configuring the service API wrapper.
     *
     *     @type string $serviceAddress
     *           The address of the API remote host. May optionally include the port, formatted
     *           as "<uri>:<port>". Default 'googleads.googleapis.com:443'.
     *     @type string|array|FetchAuthTokenInterface|CredentialsWrapper $credentials
     *           The credentials to be used by the client to authorize API calls. This option
     *           accepts either a path to a credentials file, or a decoded credentials file as a
     *           PHP array.
     *           *Advanced usage*: In addition, this option can also accept a pre-constructed
     *           {@see \Google\Auth\FetchAuthTokenInterface} object or
     *           {@see \Google\ApiCore\CredentialsWrapper} object. Note that when one of these
     *           objects are provided, any settings in $credentialsConfig will be ignored.
     *     @type array $credentialsConfig
     *           Options used to configure credentials, including auth token caching, for the client.
     *           For a full list of supporting configuration options, see
     *           {@see \Google\ApiCore\CredentialsWrapper::build()}.
     *     @type bool $disableRetries
     *           Determines whether or not retries defined by the client configuration should be
     *           disabled. Defaults to `false`.
     *     @type string|array $clientConfig
     *           Client method configuration, including retry settings. This option can be either a
     *           path to a JSON file, or a PHP array containing the decoded JSON data.
     *           By default this settings points to the default client config file, which is provided
     *           in the resources folder.
     *     @type string|TransportInterface $transport
     *           The transport used for executing network requests. May be either the string `rest`
     *           or `grpc`. Defaults to `grpc` if gRPC support is detected on the system.
     *           *Advanced usage*: Additionally, it is possible to pass in an already instantiated
     *           {@see \Google\ApiCore\Transport\TransportInterface} object. Note that when this
     *           object is provided, any settings in $transportConfig, and any $serviceAddress
     *           setting, will be ignored.
     *     @type array $transportConfig
     *           Configuration options that will be used to construct the transport. Options for
     *           each supported transport type should be passed in a key for that transport. For
     *           example:
     *           $transportConfig = [
     *               'grpc' => [...],
     *               'rest' => [...]
     *           ];
     *           See the {@see \Google\ApiCore\Transport\GrpcTransport::build()} and
     *           {@see \Google\ApiCore\Transport\RestTransport::build()} methods for the
     *           supported options.
     * }
     *
     * @throws ValidationException
     * @experimental
     */
    public function __construct(array $options = [])
    {
        $clientOptions = $this->buildClientOptions($options);
        $this->setClientOptions($clientOptions);
    }

    /**
     * Returns the requested Keyword Plan ad group keyword in full detail.
     *
     * Sample code:
     * ```
     * $keywordPlanAdGroupKeywordServiceClient = new KeywordPlanAdGroupKeywordServiceClient();
     * try {
     *     $formattedResourceName = $keywordPlanAdGroupKeywordServiceClient->keywordPlanAdGroupKeywordName('[CUSTOMER]', '[KEYWORD_PLAN_AD_GROUP_KEYWORD]');
     *     $response = $keywordPlanAdGroupKeywordServiceClient->getKeywordPlanAdGroupKeyword($formattedResourceName);
     * } finally {
     *     $keywordPlanAdGroupKeywordServiceClient->close();
     * }
     * ```
     *
     * @param string $resourceName Required. The resource name of the ad group keyword to fetch.
     * @param array  $optionalArgs {
     *                             Optional.
     *
     *     @type RetrySettings|array $retrySettings
     *          Retry settings to use for this call. Can be a
     *          {@see Google\ApiCore\RetrySettings} object, or an associative array
     *          of retry settings parameters. See the documentation on
     *          {@see Google\ApiCore\RetrySettings} for example usage.
     * }
     *
     * @return \Google\Ads\GoogleAds\V4\Resources\KeywordPlanAdGroupKeyword
     *
     * @throws ApiException if the remote call fails
     * @experimental
     */
    public function getKeywordPlanAdGroupKeyword($resourceName, array $optionalArgs = [])
    {
        $request = new GetKeywordPlanAdGroupKeywordRequest();
        $request->setResourceName($resourceName);

        $requestParams = new RequestParamsHeaderDescriptor([
          'resource_name' => $request->getResourceName(),
        ]);
        $optionalArgs['headers'] = isset($optionalArgs['headers'])
            ? array_merge($requestParams->getHeader(), $optionalArgs['headers'])
            : $requestParams->getHeader();

        return $this->startCall(
            'GetKeywordPlanAdGroupKeyword',
            KeywordPlanAdGroupKeyword::class,
            $optionalArgs,
            $request
        )->wait();
    }

    /**
     * Creates, updates, or removes Keyword Plan ad group keywords. Operation
     * statuses are returned.
     *
     * Sample code:
     * ```
     * $keywordPlanAdGroupKeywordServiceClient = new KeywordPlanAdGroupKeywordServiceClient();
     * try {
     *     $customerId = '';
     *     $operations = [];
     *     $response = $keywordPlanAdGroupKeywordServiceClient->mutateKeywordPlanAdGroupKeywords($customerId, $operations);
     * } finally {
     *     $keywordPlanAdGroupKeywordServiceClient->close();
     * }
     * ```
     *
     * @param string                               $customerId   Required. The ID of the customer whose Keyword Plan ad group keywords are being
     *                                                           modified.
     * @param KeywordPlanAdGroupKeywordOperation[] $operations   Required. The list of operations to perform on individual Keyword Plan ad group
     *                                                           keywords.
     * @param array                                $optionalArgs {
     *                                                           Optional.
     *
     *     @type bool $partialFailure
     *          If true, successful operations will be carried out and invalid
     *          operations will return errors. If false, all operations will be carried
     *          out in one transaction if and only if they are all valid.
     *          Default is false.
     *     @type bool $validateOnly
     *          If true, the request is validated but not executed. Only errors are
     *          returned, not results.
     *     @type RetrySettings|array $retrySettings
     *          Retry settings to use for this call. Can be a
     *          {@see Google\ApiCore\RetrySettings} object, or an associative array
     *          of retry settings parameters. See the documentation on
     *          {@see Google\ApiCore\RetrySettings} for example usage.
     * }
     *
     * @return \Google\Ads\GoogleAds\V4\Services\MutateKeywordPlanAdGroupKeywordsResponse
     *
     * @throws ApiException if the remote call fails
     * @experimental
     */
    public function mutateKeywordPlanAdGroupKeywords($customerId, $operations, array $optionalArgs = [])
    {
        $request = new MutateKeywordPlanAdGroupKeywordsRequest();
        $request->setCustomerId($customerId);
        $request->setOperations($operations);
        if (isset($optionalArgs['partialFailure'])) {
            $request->setPartialFailure($optionalArgs['partialFailure']);
        }
        if (isset($optionalArgs['validateOnly'])) {
            $request->setValidateOnly($optionalArgs['validateOnly']);
        }

        $requestParams = new RequestParamsHeaderDescriptor([
          'customer_id' => $request->getCustomerId(),
        ]);
        $optionalArgs['headers'] = isset($optionalArgs['headers'])
            ? array_merge($requestParams->getHeader(), $optionalArgs['headers'])
            : $requestParams->getHeader();

        return $this->startCall(
            'MutateKeywordPlanAdGroupKeywords',
            MutateKeywordPlanAdGroupKeywordsResponse::class,
            $optionalArgs,
            $request
        )->wait();
    }
}
