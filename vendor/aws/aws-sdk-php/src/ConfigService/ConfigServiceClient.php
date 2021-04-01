<?php
namespace Aws\ConfigService;

use Aws\AwsClient;

/**
 * This client is used to interact with AWS Config.
 *
 * @method \Aws\Result batchGetAggregateResourceConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise batchGetAggregateResourceConfigAsync(array $args = [])
 * @method \Aws\Result batchGetResourceConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise batchGetResourceConfigAsync(array $args = [])
 * @method \Aws\Result deleteAggregationAuthorization(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteAggregationAuthorizationAsync(array $args = [])
 * @method \Aws\Result deleteConfigRule(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteConfigRuleAsync(array $args = [])
 * @method \Aws\Result deleteConfigurationAggregator(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteConfigurationAggregatorAsync(array $args = [])
 * @method \Aws\Result deleteConfigurationRecorder(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteConfigurationRecorderAsync(array $args = [])
 * @method \Aws\Result deleteConformancePack(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteConformancePackAsync(array $args = [])
 * @method \Aws\Result deleteDeliveryChannel(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteDeliveryChannelAsync(array $args = [])
 * @method \Aws\Result deleteEvaluationResults(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteEvaluationResultsAsync(array $args = [])
 * @method \Aws\Result deleteOrganizationConfigRule(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteOrganizationConfigRuleAsync(array $args = [])
 * @method \Aws\Result deleteOrganizationConformancePack(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteOrganizationConformancePackAsync(array $args = [])
 * @method \Aws\Result deletePendingAggregationRequest(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deletePendingAggregationRequestAsync(array $args = [])
 * @method \Aws\Result deleteRemediationConfiguration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteRemediationConfigurationAsync(array $args = [])
 * @method \Aws\Result deleteRemediationExceptions(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteRemediationExceptionsAsync(array $args = [])
 * @method \Aws\Result deleteResourceConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteResourceConfigAsync(array $args = [])
 * @method \Aws\Result deleteRetentionConfiguration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteRetentionConfigurationAsync(array $args = [])
 * @method \Aws\Result deleteStoredQuery(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteStoredQueryAsync(array $args = [])
 * @method \Aws\Result deliverConfigSnapshot(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deliverConfigSnapshotAsync(array $args = [])
 * @method \Aws\Result describeAggregateComplianceByConfigRules(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeAggregateComplianceByConfigRulesAsync(array $args = [])
 * @method \Aws\Result describeAggregateComplianceByConformancePacks(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeAggregateComplianceByConformancePacksAsync(array $args = [])
 * @method \Aws\Result describeAggregationAuthorizations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeAggregationAuthorizationsAsync(array $args = [])
 * @method \Aws\Result describeComplianceByConfigRule(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeComplianceByConfigRuleAsync(array $args = [])
 * @method \Aws\Result describeComplianceByResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeComplianceByResourceAsync(array $args = [])
 * @method \Aws\Result describeConfigRuleEvaluationStatus(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeConfigRuleEvaluationStatusAsync(array $args = [])
 * @method \Aws\Result describeConfigRules(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeConfigRulesAsync(array $args = [])
 * @method \Aws\Result describeConfigurationAggregatorSourcesStatus(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeConfigurationAggregatorSourcesStatusAsync(array $args = [])
 * @method \Aws\Result describeConfigurationAggregators(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeConfigurationAggregatorsAsync(array $args = [])
 * @method \Aws\Result describeConfigurationRecorderStatus(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeConfigurationRecorderStatusAsync(array $args = [])
 * @method \Aws\Result describeConfigurationRecorders(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeConfigurationRecordersAsync(array $args = [])
 * @method \Aws\Result describeConformancePackCompliance(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeConformancePackComplianceAsync(array $args = [])
 * @method \Aws\Result describeConformancePackStatus(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeConformancePackStatusAsync(array $args = [])
 * @method \Aws\Result describeConformancePacks(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeConformancePacksAsync(array $args = [])
 * @method \Aws\Result describeDeliveryChannelStatus(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeDeliveryChannelStatusAsync(array $args = [])
 * @method \Aws\Result describeDeliveryChannels(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeDeliveryChannelsAsync(array $args = [])
 * @method \Aws\Result describeOrganizationConfigRuleStatuses(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeOrganizationConfigRuleStatusesAsync(array $args = [])
 * @method \Aws\Result describeOrganizationConfigRules(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeOrganizationConfigRulesAsync(array $args = [])
 * @method \Aws\Result describeOrganizationConformancePackStatuses(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeOrganizationConformancePackStatusesAsync(array $args = [])
 * @method \Aws\Result describeOrganizationConformancePacks(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeOrganizationConformancePacksAsync(array $args = [])
 * @method \Aws\Result describePendingAggregationRequests(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describePendingAggregationRequestsAsync(array $args = [])
 * @method \Aws\Result describeRemediationConfigurations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeRemediationConfigurationsAsync(array $args = [])
 * @method \Aws\Result describeRemediationExceptions(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeRemediationExceptionsAsync(array $args = [])
 * @method \Aws\Result describeRemediationExecutionStatus(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeRemediationExecutionStatusAsync(array $args = [])
 * @method \Aws\Result describeRetentionConfigurations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeRetentionConfigurationsAsync(array $args = [])
 * @method \Aws\Result getAggregateComplianceDetailsByConfigRule(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getAggregateComplianceDetailsByConfigRuleAsync(array $args = [])
 * @method \Aws\Result getAggregateConfigRuleComplianceSummary(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getAggregateConfigRuleComplianceSummaryAsync(array $args = [])
 * @method \Aws\Result getAggregateConformancePackComplianceSummary(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getAggregateConformancePackComplianceSummaryAsync(array $args = [])
 * @method \Aws\Result getAggregateDiscoveredResourceCounts(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getAggregateDiscoveredResourceCountsAsync(array $args = [])
 * @method \Aws\Result getAggregateResourceConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getAggregateResourceConfigAsync(array $args = [])
 * @method \Aws\Result getComplianceDetailsByConfigRule(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getComplianceDetailsByConfigRuleAsync(array $args = [])
 * @method \Aws\Result getComplianceDetailsByResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getComplianceDetailsByResourceAsync(array $args = [])
 * @method \Aws\Result getComplianceSummaryByConfigRule(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getComplianceSummaryByConfigRuleAsync(array $args = [])
 * @method \Aws\Result getComplianceSummaryByResourceType(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getComplianceSummaryByResourceTypeAsync(array $args = [])
 * @method \Aws\Result getConformancePackComplianceDetails(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getConformancePackComplianceDetailsAsync(array $args = [])
 * @method \Aws\Result getConformancePackComplianceSummary(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getConformancePackComplianceSummaryAsync(array $args = [])
 * @method \Aws\Result getDiscoveredResourceCounts(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getDiscoveredResourceCountsAsync(array $args = [])
 * @method \Aws\Result getOrganizationConfigRuleDetailedStatus(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getOrganizationConfigRuleDetailedStatusAsync(array $args = [])
 * @method \Aws\Result getOrganizationConformancePackDetailedStatus(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getOrganizationConformancePackDetailedStatusAsync(array $args = [])
 * @method \Aws\Result getResourceConfigHistory(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getResourceConfigHistoryAsync(array $args = [])
 * @method \Aws\Result getStoredQuery(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getStoredQueryAsync(array $args = [])
 * @method \Aws\Result listAggregateDiscoveredResources(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listAggregateDiscoveredResourcesAsync(array $args = [])
 * @method \Aws\Result listDiscoveredResources(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listDiscoveredResourcesAsync(array $args = [])
 * @method \Aws\Result listStoredQueries(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listStoredQueriesAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result putAggregationAuthorization(array $args = [])
 * @method \GuzzleHttp\Promise\Promise putAggregationAuthorizationAsync(array $args = [])
 * @method \Aws\Result putConfigRule(array $args = [])
 * @method \GuzzleHttp\Promise\Promise putConfigRuleAsync(array $args = [])
 * @method \Aws\Result putConfigurationAggregator(array $args = [])
 * @method \GuzzleHttp\Promise\Promise putConfigurationAggregatorAsync(array $args = [])
 * @method \Aws\Result putConfigurationRecorder(array $args = [])
 * @method \GuzzleHttp\Promise\Promise putConfigurationRecorderAsync(array $args = [])
 * @method \Aws\Result putConformancePack(array $args = [])
 * @method \GuzzleHttp\Promise\Promise putConformancePackAsync(array $args = [])
 * @method \Aws\Result putDeliveryChannel(array $args = [])
 * @method \GuzzleHttp\Promise\Promise putDeliveryChannelAsync(array $args = [])
 * @method \Aws\Result putEvaluations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise putEvaluationsAsync(array $args = [])
 * @method \Aws\Result putExternalEvaluation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise putExternalEvaluationAsync(array $args = [])
 * @method \Aws\Result putOrganizationConfigRule(array $args = [])
 * @method \GuzzleHttp\Promise\Promise putOrganizationConfigRuleAsync(array $args = [])
 * @method \Aws\Result putOrganizationConformancePack(array $args = [])
 * @method \GuzzleHttp\Promise\Promise putOrganizationConformancePackAsync(array $args = [])
 * @method \Aws\Result putRemediationConfigurations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise putRemediationConfigurationsAsync(array $args = [])
 * @method \Aws\Result putRemediationExceptions(array $args = [])
 * @method \GuzzleHttp\Promise\Promise putRemediationExceptionsAsync(array $args = [])
 * @method \Aws\Result putResourceConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise putResourceConfigAsync(array $args = [])
 * @method \Aws\Result putRetentionConfiguration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise putRetentionConfigurationAsync(array $args = [])
 * @method \Aws\Result putStoredQuery(array $args = [])
 * @method \GuzzleHttp\Promise\Promise putStoredQueryAsync(array $args = [])
 * @method \Aws\Result selectAggregateResourceConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise selectAggregateResourceConfigAsync(array $args = [])
 * @method \Aws\Result selectResourceConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise selectResourceConfigAsync(array $args = [])
 * @method \Aws\Result startConfigRulesEvaluation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startConfigRulesEvaluationAsync(array $args = [])
 * @method \Aws\Result startConfigurationRecorder(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startConfigurationRecorderAsync(array $args = [])
 * @method \Aws\Result startRemediationExecution(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startRemediationExecutionAsync(array $args = [])
 * @method \Aws\Result stopConfigurationRecorder(array $args = [])
 * @method \GuzzleHttp\Promise\Promise stopConfigurationRecorderAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 */
class ConfigServiceClient extends AwsClient {}
