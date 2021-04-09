<?php
namespace Aws\Connect;

use Aws\AwsClient;

/**
 * This client is used to interact with the **Amazon Connect Service** service.
 * @method \Aws\Result associateApprovedOrigin(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associateApprovedOriginAsync(array $args = [])
 * @method \Aws\Result associateInstanceStorageConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associateInstanceStorageConfigAsync(array $args = [])
 * @method \Aws\Result associateLambdaFunction(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associateLambdaFunctionAsync(array $args = [])
 * @method \Aws\Result associateLexBot(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associateLexBotAsync(array $args = [])
 * @method \Aws\Result associateQueueQuickConnects(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associateQueueQuickConnectsAsync(array $args = [])
 * @method \Aws\Result associateRoutingProfileQueues(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associateRoutingProfileQueuesAsync(array $args = [])
 * @method \Aws\Result associateSecurityKey(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associateSecurityKeyAsync(array $args = [])
 * @method \Aws\Result createContactFlow(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createContactFlowAsync(array $args = [])
 * @method \Aws\Result createInstance(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createInstanceAsync(array $args = [])
 * @method \Aws\Result createIntegrationAssociation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createIntegrationAssociationAsync(array $args = [])
 * @method \Aws\Result createQueue(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createQueueAsync(array $args = [])
 * @method \Aws\Result createQuickConnect(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createQuickConnectAsync(array $args = [])
 * @method \Aws\Result createRoutingProfile(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createRoutingProfileAsync(array $args = [])
 * @method \Aws\Result createUseCase(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createUseCaseAsync(array $args = [])
 * @method \Aws\Result createUser(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createUserAsync(array $args = [])
 * @method \Aws\Result createUserHierarchyGroup(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createUserHierarchyGroupAsync(array $args = [])
 * @method \Aws\Result deleteInstance(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteInstanceAsync(array $args = [])
 * @method \Aws\Result deleteIntegrationAssociation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteIntegrationAssociationAsync(array $args = [])
 * @method \Aws\Result deleteQuickConnect(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteQuickConnectAsync(array $args = [])
 * @method \Aws\Result deleteUseCase(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteUseCaseAsync(array $args = [])
 * @method \Aws\Result deleteUser(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteUserAsync(array $args = [])
 * @method \Aws\Result deleteUserHierarchyGroup(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteUserHierarchyGroupAsync(array $args = [])
 * @method \Aws\Result describeContactFlow(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeContactFlowAsync(array $args = [])
 * @method \Aws\Result describeHoursOfOperation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeHoursOfOperationAsync(array $args = [])
 * @method \Aws\Result describeInstance(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeInstanceAsync(array $args = [])
 * @method \Aws\Result describeInstanceAttribute(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeInstanceAttributeAsync(array $args = [])
 * @method \Aws\Result describeInstanceStorageConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeInstanceStorageConfigAsync(array $args = [])
 * @method \Aws\Result describeQueue(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeQueueAsync(array $args = [])
 * @method \Aws\Result describeQuickConnect(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeQuickConnectAsync(array $args = [])
 * @method \Aws\Result describeRoutingProfile(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeRoutingProfileAsync(array $args = [])
 * @method \Aws\Result describeUser(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeUserAsync(array $args = [])
 * @method \Aws\Result describeUserHierarchyGroup(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeUserHierarchyGroupAsync(array $args = [])
 * @method \Aws\Result describeUserHierarchyStructure(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeUserHierarchyStructureAsync(array $args = [])
 * @method \Aws\Result disassociateApprovedOrigin(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disassociateApprovedOriginAsync(array $args = [])
 * @method \Aws\Result disassociateInstanceStorageConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disassociateInstanceStorageConfigAsync(array $args = [])
 * @method \Aws\Result disassociateLambdaFunction(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disassociateLambdaFunctionAsync(array $args = [])
 * @method \Aws\Result disassociateLexBot(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disassociateLexBotAsync(array $args = [])
 * @method \Aws\Result disassociateQueueQuickConnects(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disassociateQueueQuickConnectsAsync(array $args = [])
 * @method \Aws\Result disassociateRoutingProfileQueues(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disassociateRoutingProfileQueuesAsync(array $args = [])
 * @method \Aws\Result disassociateSecurityKey(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disassociateSecurityKeyAsync(array $args = [])
 * @method \Aws\Result getContactAttributes(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getContactAttributesAsync(array $args = [])
 * @method \Aws\Result getCurrentMetricData(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getCurrentMetricDataAsync(array $args = [])
 * @method \Aws\Result getFederationToken(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getFederationTokenAsync(array $args = [])
 * @method \Aws\Result getMetricData(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getMetricDataAsync(array $args = [])
 * @method \Aws\Result listApprovedOrigins(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listApprovedOriginsAsync(array $args = [])
 * @method \Aws\Result listContactFlows(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listContactFlowsAsync(array $args = [])
 * @method \Aws\Result listHoursOfOperations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listHoursOfOperationsAsync(array $args = [])
 * @method \Aws\Result listInstanceAttributes(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listInstanceAttributesAsync(array $args = [])
 * @method \Aws\Result listInstanceStorageConfigs(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listInstanceStorageConfigsAsync(array $args = [])
 * @method \Aws\Result listInstances(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listInstancesAsync(array $args = [])
 * @method \Aws\Result listIntegrationAssociations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listIntegrationAssociationsAsync(array $args = [])
 * @method \Aws\Result listLambdaFunctions(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listLambdaFunctionsAsync(array $args = [])
 * @method \Aws\Result listLexBots(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listLexBotsAsync(array $args = [])
 * @method \Aws\Result listPhoneNumbers(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listPhoneNumbersAsync(array $args = [])
 * @method \Aws\Result listPrompts(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listPromptsAsync(array $args = [])
 * @method \Aws\Result listQueueQuickConnects(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listQueueQuickConnectsAsync(array $args = [])
 * @method \Aws\Result listQueues(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listQueuesAsync(array $args = [])
 * @method \Aws\Result listQuickConnects(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listQuickConnectsAsync(array $args = [])
 * @method \Aws\Result listRoutingProfileQueues(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listRoutingProfileQueuesAsync(array $args = [])
 * @method \Aws\Result listRoutingProfiles(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listRoutingProfilesAsync(array $args = [])
 * @method \Aws\Result listSecurityKeys(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listSecurityKeysAsync(array $args = [])
 * @method \Aws\Result listSecurityProfiles(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listSecurityProfilesAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result listUseCases(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listUseCasesAsync(array $args = [])
 * @method \Aws\Result listUserHierarchyGroups(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listUserHierarchyGroupsAsync(array $args = [])
 * @method \Aws\Result listUsers(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listUsersAsync(array $args = [])
 * @method \Aws\Result resumeContactRecording(array $args = [])
 * @method \GuzzleHttp\Promise\Promise resumeContactRecordingAsync(array $args = [])
 * @method \Aws\Result startChatContact(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startChatContactAsync(array $args = [])
 * @method \Aws\Result startContactRecording(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startContactRecordingAsync(array $args = [])
 * @method \Aws\Result startOutboundVoiceContact(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startOutboundVoiceContactAsync(array $args = [])
 * @method \Aws\Result startTaskContact(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startTaskContactAsync(array $args = [])
 * @method \Aws\Result stopContact(array $args = [])
 * @method \GuzzleHttp\Promise\Promise stopContactAsync(array $args = [])
 * @method \Aws\Result stopContactRecording(array $args = [])
 * @method \GuzzleHttp\Promise\Promise stopContactRecordingAsync(array $args = [])
 * @method \Aws\Result suspendContactRecording(array $args = [])
 * @method \GuzzleHttp\Promise\Promise suspendContactRecordingAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 * @method \Aws\Result updateContactAttributes(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateContactAttributesAsync(array $args = [])
 * @method \Aws\Result updateContactFlowContent(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateContactFlowContentAsync(array $args = [])
 * @method \Aws\Result updateContactFlowName(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateContactFlowNameAsync(array $args = [])
 * @method \Aws\Result updateInstanceAttribute(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateInstanceAttributeAsync(array $args = [])
 * @method \Aws\Result updateInstanceStorageConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateInstanceStorageConfigAsync(array $args = [])
 * @method \Aws\Result updateQueueHoursOfOperation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateQueueHoursOfOperationAsync(array $args = [])
 * @method \Aws\Result updateQueueMaxContacts(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateQueueMaxContactsAsync(array $args = [])
 * @method \Aws\Result updateQueueName(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateQueueNameAsync(array $args = [])
 * @method \Aws\Result updateQueueOutboundCallerConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateQueueOutboundCallerConfigAsync(array $args = [])
 * @method \Aws\Result updateQueueStatus(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateQueueStatusAsync(array $args = [])
 * @method \Aws\Result updateQuickConnectConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateQuickConnectConfigAsync(array $args = [])
 * @method \Aws\Result updateQuickConnectName(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateQuickConnectNameAsync(array $args = [])
 * @method \Aws\Result updateRoutingProfileConcurrency(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateRoutingProfileConcurrencyAsync(array $args = [])
 * @method \Aws\Result updateRoutingProfileDefaultOutboundQueue(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateRoutingProfileDefaultOutboundQueueAsync(array $args = [])
 * @method \Aws\Result updateRoutingProfileName(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateRoutingProfileNameAsync(array $args = [])
 * @method \Aws\Result updateRoutingProfileQueues(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateRoutingProfileQueuesAsync(array $args = [])
 * @method \Aws\Result updateUserHierarchy(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateUserHierarchyAsync(array $args = [])
 * @method \Aws\Result updateUserHierarchyGroupName(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateUserHierarchyGroupNameAsync(array $args = [])
 * @method \Aws\Result updateUserHierarchyStructure(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateUserHierarchyStructureAsync(array $args = [])
 * @method \Aws\Result updateUserIdentityInfo(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateUserIdentityInfoAsync(array $args = [])
 * @method \Aws\Result updateUserPhoneConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateUserPhoneConfigAsync(array $args = [])
 * @method \Aws\Result updateUserRoutingProfile(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateUserRoutingProfileAsync(array $args = [])
 * @method \Aws\Result updateUserSecurityProfiles(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateUserSecurityProfilesAsync(array $args = [])
 */
class ConnectClient extends AwsClient {}
