<?php
// This file was auto-generated from sdk-root/src/data/health/2016-08-04/api-2.json
return [ 'version' => '2.0', 'metadata' => [ 'apiVersion' => '2016-08-04', 'endpointPrefix' => 'health', 'jsonVersion' => '1.1', 'protocol' => 'json', 'serviceAbbreviation' => 'AWSHealth', 'serviceFullName' => 'AWS Health APIs and Notifications', 'serviceId' => 'Health', 'signatureVersion' => 'v4', 'targetPrefix' => 'AWSHealth_20160804', 'uid' => 'health-2016-08-04', ], 'operations' => [ 'DescribeAffectedAccountsForOrganization' => [ 'name' => 'DescribeAffectedAccountsForOrganization', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'DescribeAffectedAccountsForOrganizationRequest', ], 'output' => [ 'shape' => 'DescribeAffectedAccountsForOrganizationResponse', ], 'errors' => [ [ 'shape' => 'InvalidPaginationToken', ], ], 'idempotent' => true, ], 'DescribeAffectedEntities' => [ 'name' => 'DescribeAffectedEntities', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'DescribeAffectedEntitiesRequest', ], 'output' => [ 'shape' => 'DescribeAffectedEntitiesResponse', ], 'errors' => [ [ 'shape' => 'InvalidPaginationToken', ], [ 'shape' => 'UnsupportedLocale', ], ], 'idempotent' => true, ], 'DescribeAffectedEntitiesForOrganization' => [ 'name' => 'DescribeAffectedEntitiesForOrganization', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'DescribeAffectedEntitiesForOrganizationRequest', ], 'output' => [ 'shape' => 'DescribeAffectedEntitiesForOrganizationResponse', ], 'errors' => [ [ 'shape' => 'InvalidPaginationToken', ], [ 'shape' => 'UnsupportedLocale', ], ], 'idempotent' => true, ], 'DescribeEntityAggregates' => [ 'name' => 'DescribeEntityAggregates', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'DescribeEntityAggregatesRequest', ], 'output' => [ 'shape' => 'DescribeEntityAggregatesResponse', ], 'idempotent' => true, ], 'DescribeEventAggregates' => [ 'name' => 'DescribeEventAggregates', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'DescribeEventAggregatesRequest', ], 'output' => [ 'shape' => 'DescribeEventAggregatesResponse', ], 'errors' => [ [ 'shape' => 'InvalidPaginationToken', ], ], 'idempotent' => true, ], 'DescribeEventDetails' => [ 'name' => 'DescribeEventDetails', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'DescribeEventDetailsRequest', ], 'output' => [ 'shape' => 'DescribeEventDetailsResponse', ], 'errors' => [ [ 'shape' => 'UnsupportedLocale', ], ], 'idempotent' => true, ], 'DescribeEventDetailsForOrganization' => [ 'name' => 'DescribeEventDetailsForOrganization', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'DescribeEventDetailsForOrganizationRequest', ], 'output' => [ 'shape' => 'DescribeEventDetailsForOrganizationResponse', ], 'errors' => [ [ 'shape' => 'UnsupportedLocale', ], ], 'idempotent' => true, ], 'DescribeEventTypes' => [ 'name' => 'DescribeEventTypes', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'DescribeEventTypesRequest', ], 'output' => [ 'shape' => 'DescribeEventTypesResponse', ], 'errors' => [ [ 'shape' => 'InvalidPaginationToken', ], [ 'shape' => 'UnsupportedLocale', ], ], 'idempotent' => true, ], 'DescribeEvents' => [ 'name' => 'DescribeEvents', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'DescribeEventsRequest', ], 'output' => [ 'shape' => 'DescribeEventsResponse', ], 'errors' => [ [ 'shape' => 'InvalidPaginationToken', ], [ 'shape' => 'UnsupportedLocale', ], ], 'idempotent' => true, ], 'DescribeEventsForOrganization' => [ 'name' => 'DescribeEventsForOrganization', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'DescribeEventsForOrganizationRequest', ], 'output' => [ 'shape' => 'DescribeEventsForOrganizationResponse', ], 'errors' => [ [ 'shape' => 'InvalidPaginationToken', ], [ 'shape' => 'UnsupportedLocale', ], ], 'idempotent' => true, ], 'DescribeHealthServiceStatusForOrganization' => [ 'name' => 'DescribeHealthServiceStatusForOrganization', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'output' => [ 'shape' => 'DescribeHealthServiceStatusForOrganizationResponse', ], 'idempotent' => true, ], 'DisableHealthServiceAccessForOrganization' => [ 'name' => 'DisableHealthServiceAccessForOrganization', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'errors' => [ [ 'shape' => 'ConcurrentModificationException', ], ], 'idempotent' => true, ], 'EnableHealthServiceAccessForOrganization' => [ 'name' => 'EnableHealthServiceAccessForOrganization', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'errors' => [ [ 'shape' => 'ConcurrentModificationException', ], ], 'idempotent' => true, ], ], 'shapes' => [ 'AffectedEntity' => [ 'type' => 'structure', 'members' => [ 'entityArn' => [ 'shape' => 'entityArn', ], 'eventArn' => [ 'shape' => 'eventArn', ], 'entityValue' => [ 'shape' => 'entityValue', ], 'entityUrl' => [ 'shape' => 'entityUrl', ], 'awsAccountId' => [ 'shape' => 'accountId', ], 'lastUpdatedTime' => [ 'shape' => 'timestamp', ], 'statusCode' => [ 'shape' => 'entityStatusCode', ], 'tags' => [ 'shape' => 'tagSet', ], ], ], 'ConcurrentModificationException' => [ 'type' => 'structure', 'members' => [ 'message' => [ 'shape' => 'string', ], ], 'exception' => true, ], 'DateTimeRange' => [ 'type' => 'structure', 'members' => [ 'from' => [ 'shape' => 'timestamp', ], 'to' => [ 'shape' => 'timestamp', ], ], ], 'DescribeAffectedAccountsForOrganizationRequest' => [ 'type' => 'structure', 'required' => [ 'eventArn', ], 'members' => [ 'eventArn' => [ 'shape' => 'eventArn', ], 'nextToken' => [ 'shape' => 'nextToken', ], 'maxResults' => [ 'shape' => 'maxResults', ], ], ], 'DescribeAffectedAccountsForOrganizationResponse' => [ 'type' => 'structure', 'members' => [ 'affectedAccounts' => [ 'shape' => 'affectedAccountsList', ], 'eventScopeCode' => [ 'shape' => 'eventScopeCode', ], 'nextToken' => [ 'shape' => 'nextToken', ], ], ], 'DescribeAffectedEntitiesForOrganizationFailedSet' => [ 'type' => 'list', 'member' => [ 'shape' => 'OrganizationAffectedEntitiesErrorItem', ], ], 'DescribeAffectedEntitiesForOrganizationRequest' => [ 'type' => 'structure', 'required' => [ 'organizationEntityFilters', ], 'members' => [ 'organizationEntityFilters' => [ 'shape' => 'OrganizationEntityFiltersList', ], 'locale' => [ 'shape' => 'locale', ], 'nextToken' => [ 'shape' => 'nextToken', ], 'maxResults' => [ 'shape' => 'maxResults', ], ], ], 'DescribeAffectedEntitiesForOrganizationResponse' => [ 'type' => 'structure', 'members' => [ 'entities' => [ 'shape' => 'EntityList', ], 'failedSet' => [ 'shape' => 'DescribeAffectedEntitiesForOrganizationFailedSet', ], 'nextToken' => [ 'shape' => 'nextToken', ], ], ], 'DescribeAffectedEntitiesRequest' => [ 'type' => 'structure', 'required' => [ 'filter', ], 'members' => [ 'filter' => [ 'shape' => 'EntityFilter', ], 'locale' => [ 'shape' => 'locale', ], 'nextToken' => [ 'shape' => 'nextToken', ], 'maxResults' => [ 'shape' => 'maxResults', ], ], ], 'DescribeAffectedEntitiesResponse' => [ 'type' => 'structure', 'members' => [ 'entities' => [ 'shape' => 'EntityList', ], 'nextToken' => [ 'shape' => 'nextToken', ], ], ], 'DescribeEntityAggregatesRequest' => [ 'type' => 'structure', 'members' => [ 'eventArns' => [ 'shape' => 'EventArnsList', ], ], ], 'DescribeEntityAggregatesResponse' => [ 'type' => 'structure', 'members' => [ 'entityAggregates' => [ 'shape' => 'EntityAggregateList', ], ], ], 'DescribeEventAggregatesRequest' => [ 'type' => 'structure', 'required' => [ 'aggregateField', ], 'members' => [ 'filter' => [ 'shape' => 'EventFilter', ], 'aggregateField' => [ 'shape' => 'eventAggregateField', ], 'maxResults' => [ 'shape' => 'maxResults', ], 'nextToken' => [ 'shape' => 'nextToken', ], ], ], 'DescribeEventAggregatesResponse' => [ 'type' => 'structure', 'members' => [ 'eventAggregates' => [ 'shape' => 'EventAggregateList', ], 'nextToken' => [ 'shape' => 'nextToken', ], ], ], 'DescribeEventDetailsFailedSet' => [ 'type' => 'list', 'member' => [ 'shape' => 'EventDetailsErrorItem', ], ], 'DescribeEventDetailsForOrganizationFailedSet' => [ 'type' => 'list', 'member' => [ 'shape' => 'OrganizationEventDetailsErrorItem', ], ], 'DescribeEventDetailsForOrganizationRequest' => [ 'type' => 'structure', 'required' => [ 'organizationEventDetailFilters', ], 'members' => [ 'organizationEventDetailFilters' => [ 'shape' => 'OrganizationEventDetailFiltersList', ], 'locale' => [ 'shape' => 'locale', ], ], ], 'DescribeEventDetailsForOrganizationResponse' => [ 'type' => 'structure', 'members' => [ 'successfulSet' => [ 'shape' => 'DescribeEventDetailsForOrganizationSuccessfulSet', ], 'failedSet' => [ 'shape' => 'DescribeEventDetailsForOrganizationFailedSet', ], ], ], 'DescribeEventDetailsForOrganizationSuccessfulSet' => [ 'type' => 'list', 'member' => [ 'shape' => 'OrganizationEventDetails', ], ], 'DescribeEventDetailsRequest' => [ 'type' => 'structure', 'required' => [ 'eventArns', ], 'members' => [ 'eventArns' => [ 'shape' => 'eventArnList', ], 'locale' => [ 'shape' => 'locale', ], ], ], 'DescribeEventDetailsResponse' => [ 'type' => 'structure', 'members' => [ 'successfulSet' => [ 'shape' => 'DescribeEventDetailsSuccessfulSet', ], 'failedSet' => [ 'shape' => 'DescribeEventDetailsFailedSet', ], ], ], 'DescribeEventDetailsSuccessfulSet' => [ 'type' => 'list', 'member' => [ 'shape' => 'EventDetails', ], ], 'DescribeEventTypesRequest' => [ 'type' => 'structure', 'members' => [ 'filter' => [ 'shape' => 'EventTypeFilter', ], 'locale' => [ 'shape' => 'locale', ], 'nextToken' => [ 'shape' => 'nextToken', ], 'maxResults' => [ 'shape' => 'maxResults', ], ], ], 'DescribeEventTypesResponse' => [ 'type' => 'structure', 'members' => [ 'eventTypes' => [ 'shape' => 'EventTypeList', ], 'nextToken' => [ 'shape' => 'nextToken', ], ], ], 'DescribeEventsForOrganizationRequest' => [ 'type' => 'structure', 'members' => [ 'filter' => [ 'shape' => 'OrganizationEventFilter', ], 'nextToken' => [ 'shape' => 'nextToken', ], 'maxResults' => [ 'shape' => 'maxResults', ], 'locale' => [ 'shape' => 'locale', ], ], ], 'DescribeEventsForOrganizationResponse' => [ 'type' => 'structure', 'members' => [ 'events' => [ 'shape' => 'OrganizationEventList', ], 'nextToken' => [ 'shape' => 'nextToken', ], ], ], 'DescribeEventsRequest' => [ 'type' => 'structure', 'members' => [ 'filter' => [ 'shape' => 'EventFilter', ], 'nextToken' => [ 'shape' => 'nextToken', ], 'maxResults' => [ 'shape' => 'maxResults', ], 'locale' => [ 'shape' => 'locale', ], ], ], 'DescribeEventsResponse' => [ 'type' => 'structure', 'members' => [ 'events' => [ 'shape' => 'EventList', ], 'nextToken' => [ 'shape' => 'nextToken', ], ], ], 'DescribeHealthServiceStatusForOrganizationResponse' => [ 'type' => 'structure', 'members' => [ 'healthServiceAccessStatusForOrganization' => [ 'shape' => 'healthServiceAccessStatusForOrganization', ], ], ], 'EntityAggregate' => [ 'type' => 'structure', 'members' => [ 'eventArn' => [ 'shape' => 'eventArn', ], 'count' => [ 'shape' => 'count', ], ], ], 'EntityAggregateList' => [ 'type' => 'list', 'member' => [ 'shape' => 'EntityAggregate', ], ], 'EntityFilter' => [ 'type' => 'structure', 'required' => [ 'eventArns', ], 'members' => [ 'eventArns' => [ 'shape' => 'eventArnList', ], 'entityArns' => [ 'shape' => 'entityArnList', ], 'entityValues' => [ 'shape' => 'entityValueList', ], 'lastUpdatedTimes' => [ 'shape' => 'dateTimeRangeList', ], 'tags' => [ 'shape' => 'tagFilter', ], 'statusCodes' => [ 'shape' => 'entityStatusCodeList', ], ], ], 'EntityList' => [ 'type' => 'list', 'member' => [ 'shape' => 'AffectedEntity', ], ], 'Event' => [ 'type' => 'structure', 'members' => [ 'arn' => [ 'shape' => 'eventArn', ], 'service' => [ 'shape' => 'service', ], 'eventTypeCode' => [ 'shape' => 'eventTypeCode', ], 'eventTypeCategory' => [ 'shape' => 'eventTypeCategory', ], 'region' => [ 'shape' => 'region', ], 'availabilityZone' => [ 'shape' => 'availabilityZone', ], 'startTime' => [ 'shape' => 'timestamp', ], 'endTime' => [ 'shape' => 'timestamp', ], 'lastUpdatedTime' => [ 'shape' => 'timestamp', ], 'statusCode' => [ 'shape' => 'eventStatusCode', ], 'eventScopeCode' => [ 'shape' => 'eventScopeCode', ], ], ], 'EventAccountFilter' => [ 'type' => 'structure', 'required' => [ 'eventArn', ], 'members' => [ 'eventArn' => [ 'shape' => 'eventArn', ], 'awsAccountId' => [ 'shape' => 'accountId', ], ], ], 'EventAggregate' => [ 'type' => 'structure', 'members' => [ 'aggregateValue' => [ 'shape' => 'aggregateValue', ], 'count' => [ 'shape' => 'count', ], ], ], 'EventAggregateList' => [ 'type' => 'list', 'member' => [ 'shape' => 'EventAggregate', ], ], 'EventArnsList' => [ 'type' => 'list', 'member' => [ 'shape' => 'eventArn', ], 'max' => 50, 'min' => 1, ], 'EventDescription' => [ 'type' => 'structure', 'members' => [ 'latestDescription' => [ 'shape' => 'eventDescription', ], ], ], 'EventDetails' => [ 'type' => 'structure', 'members' => [ 'event' => [ 'shape' => 'Event', ], 'eventDescription' => [ 'shape' => 'EventDescription', ], 'eventMetadata' => [ 'shape' => 'eventMetadata', ], ], ], 'EventDetailsErrorItem' => [ 'type' => 'structure', 'members' => [ 'eventArn' => [ 'shape' => 'eventArn', ], 'errorName' => [ 'shape' => 'string', ], 'errorMessage' => [ 'shape' => 'string', ], ], ], 'EventFilter' => [ 'type' => 'structure', 'members' => [ 'eventArns' => [ 'shape' => 'eventArnList', ], 'eventTypeCodes' => [ 'shape' => 'eventTypeList', ], 'services' => [ 'shape' => 'serviceList', ], 'regions' => [ 'shape' => 'regionList', ], 'availabilityZones' => [ 'shape' => 'availabilityZones', ], 'startTimes' => [ 'shape' => 'dateTimeRangeList', ], 'endTimes' => [ 'shape' => 'dateTimeRangeList', ], 'lastUpdatedTimes' => [ 'shape' => 'dateTimeRangeList', ], 'entityArns' => [ 'shape' => 'entityArnList', ], 'entityValues' => [ 'shape' => 'entityValueList', ], 'eventTypeCategories' => [ 'shape' => 'eventTypeCategoryList', ], 'tags' => [ 'shape' => 'tagFilter', ], 'eventStatusCodes' => [ 'shape' => 'eventStatusCodeList', ], ], ], 'EventList' => [ 'type' => 'list', 'member' => [ 'shape' => 'Event', ], ], 'EventType' => [ 'type' => 'structure', 'members' => [ 'service' => [ 'shape' => 'service', ], 'code' => [ 'shape' => 'eventTypeCode', ], 'category' => [ 'shape' => 'eventTypeCategory', ], ], ], 'EventTypeCategoryList' => [ 'type' => 'list', 'member' => [ 'shape' => 'eventTypeCategory', ], 'max' => 10, 'min' => 1, ], 'EventTypeCodeList' => [ 'type' => 'list', 'member' => [ 'shape' => 'eventTypeCode', ], 'max' => 10, 'min' => 1, ], 'EventTypeFilter' => [ 'type' => 'structure', 'members' => [ 'eventTypeCodes' => [ 'shape' => 'EventTypeCodeList', ], 'services' => [ 'shape' => 'serviceList', ], 'eventTypeCategories' => [ 'shape' => 'EventTypeCategoryList', ], ], ], 'EventTypeList' => [ 'type' => 'list', 'member' => [ 'shape' => 'EventType', ], ], 'InvalidPaginationToken' => [ 'type' => 'structure', 'members' => [ 'message' => [ 'shape' => 'string', ], ], 'exception' => true, ], 'OrganizationAffectedEntitiesErrorItem' => [ 'type' => 'structure', 'members' => [ 'awsAccountId' => [ 'shape' => 'accountId', ], 'eventArn' => [ 'shape' => 'eventArn', ], 'errorName' => [ 'shape' => 'string', ], 'errorMessage' => [ 'shape' => 'string', ], ], ], 'OrganizationEntityFiltersList' => [ 'type' => 'list', 'member' => [ 'shape' => 'EventAccountFilter', ], 'max' => 10, 'min' => 1, ], 'OrganizationEvent' => [ 'type' => 'structure', 'members' => [ 'arn' => [ 'shape' => 'eventArn', ], 'service' => [ 'shape' => 'service', ], 'eventTypeCode' => [ 'shape' => 'eventTypeCode', ], 'eventTypeCategory' => [ 'shape' => 'eventTypeCategory', ], 'eventScopeCode' => [ 'shape' => 'eventScopeCode', ], 'region' => [ 'shape' => 'region', ], 'startTime' => [ 'shape' => 'timestamp', ], 'endTime' => [ 'shape' => 'timestamp', ], 'lastUpdatedTime' => [ 'shape' => 'timestamp', ], 'statusCode' => [ 'shape' => 'eventStatusCode', ], ], ], 'OrganizationEventDetailFiltersList' => [ 'type' => 'list', 'member' => [ 'shape' => 'EventAccountFilter', ], 'max' => 10, 'min' => 1, ], 'OrganizationEventDetails' => [ 'type' => 'structure', 'members' => [ 'awsAccountId' => [ 'shape' => 'accountId', ], 'event' => [ 'shape' => 'Event', ], 'eventDescription' => [ 'shape' => 'EventDescription', ], 'eventMetadata' => [ 'shape' => 'eventMetadata', ], ], ], 'OrganizationEventDetailsErrorItem' => [ 'type' => 'structure', 'members' => [ 'awsAccountId' => [ 'shape' => 'accountId', ], 'eventArn' => [ 'shape' => 'eventArn', ], 'errorName' => [ 'shape' => 'string', ], 'errorMessage' => [ 'shape' => 'string', ], ], ], 'OrganizationEventFilter' => [ 'type' => 'structure', 'members' => [ 'eventTypeCodes' => [ 'shape' => 'eventTypeList', ], 'awsAccountIds' => [ 'shape' => 'awsAccountIdsList', ], 'services' => [ 'shape' => 'serviceList', ], 'regions' => [ 'shape' => 'regionList', ], 'startTime' => [ 'shape' => 'DateTimeRange', ], 'endTime' => [ 'shape' => 'DateTimeRange', ], 'lastUpdatedTime' => [ 'shape' => 'DateTimeRange', ], 'entityArns' => [ 'shape' => 'entityArnList', ], 'entityValues' => [ 'shape' => 'entityValueList', ], 'eventTypeCategories' => [ 'shape' => 'eventTypeCategoryList', ], 'eventStatusCodes' => [ 'shape' => 'eventStatusCodeList', ], ], ], 'OrganizationEventList' => [ 'type' => 'list', 'member' => [ 'shape' => 'OrganizationEvent', ], ], 'UnsupportedLocale' => [ 'type' => 'structure', 'members' => [ 'message' => [ 'shape' => 'string', ], ], 'exception' => true, ], 'accountId' => [ 'type' => 'string', 'max' => 12, 'pattern' => '^\\S+$', ], 'affectedAccountsList' => [ 'type' => 'list', 'member' => [ 'shape' => 'accountId', ], ], 'aggregateValue' => [ 'type' => 'string', ], 'availabilityZone' => [ 'type' => 'string', 'max' => 18, 'min' => 6, 'pattern' => '[a-z]{2}\\-[0-9a-z\\-]{4,16}', ], 'availabilityZones' => [ 'type' => 'list', 'member' => [ 'shape' => 'availabilityZone', ], ], 'awsAccountIdsList' => [ 'type' => 'list', 'member' => [ 'shape' => 'accountId', ], 'max' => 50, 'min' => 1, ], 'count' => [ 'type' => 'integer', ], 'dateTimeRangeList' => [ 'type' => 'list', 'member' => [ 'shape' => 'DateTimeRange', ], 'max' => 10, 'min' => 1, ], 'entityArn' => [ 'type' => 'string', 'max' => 1600, 'pattern' => '.{0,1600}', ], 'entityArnList' => [ 'type' => 'list', 'member' => [ 'shape' => 'entityArn', ], 'max' => 100, 'min' => 1, ], 'entityStatusCode' => [ 'type' => 'string', 'enum' => [ 'IMPAIRED', 'UNIMPAIRED', 'UNKNOWN', ], ], 'entityStatusCodeList' => [ 'type' => 'list', 'member' => [ 'shape' => 'entityStatusCode', ], 'max' => 3, 'min' => 1, ], 'entityUrl' => [ 'type' => 'string', ], 'entityValue' => [ 'type' => 'string', 'max' => 1224, 'pattern' => '.{0,1224}', ], 'entityValueList' => [ 'type' => 'list', 'member' => [ 'shape' => 'entityValue', ], 'max' => 100, 'min' => 1, ], 'eventAggregateField' => [ 'type' => 'string', 'enum' => [ 'eventTypeCategory', ], ], 'eventArn' => [ 'type' => 'string', 'max' => 1600, 'pattern' => 'arn:aws(-[a-z]+(-[a-z]+)?)?:health:[^:]*:[^:]*:event(?:/[\\w-]+){3}', ], 'eventArnList' => [ 'type' => 'list', 'member' => [ 'shape' => 'eventArn', ], 'max' => 10, 'min' => 1, ], 'eventDescription' => [ 'type' => 'string', ], 'eventMetadata' => [ 'type' => 'map', 'key' => [ 'shape' => 'metadataKey', ], 'value' => [ 'shape' => 'metadataValue', ], ], 'eventScopeCode' => [ 'type' => 'string', 'enum' => [ 'PUBLIC', 'ACCOUNT_SPECIFIC', 'NONE', ], ], 'eventStatusCode' => [ 'type' => 'string', 'enum' => [ 'open', 'closed', 'upcoming', ], ], 'eventStatusCodeList' => [ 'type' => 'list', 'member' => [ 'shape' => 'eventStatusCode', ], 'max' => 6, 'min' => 1, ], 'eventType' => [ 'type' => 'string', 'max' => 100, 'min' => 3, 'pattern' => '[^:/]{3,100}', ], 'eventTypeCategory' => [ 'type' => 'string', 'enum' => [ 'issue', 'accountNotification', 'scheduledChange', 'investigation', ], 'max' => 255, 'min' => 3, ], 'eventTypeCategoryList' => [ 'type' => 'list', 'member' => [ 'shape' => 'eventTypeCategory', ], 'max' => 10, 'min' => 1, ], 'eventTypeCode' => [ 'type' => 'string', 'max' => 100, 'min' => 3, 'pattern' => '[a-zA-Z0-9\\_\\-]{3,100}', ], 'eventTypeList' => [ 'type' => 'list', 'member' => [ 'shape' => 'eventType', ], 'max' => 10, 'min' => 1, ], 'healthServiceAccessStatusForOrganization' => [ 'type' => 'string', ], 'locale' => [ 'type' => 'string', 'max' => 256, 'min' => 2, 'pattern' => '.{2,256}', ], 'maxResults' => [ 'type' => 'integer', 'max' => 100, 'min' => 10, ], 'metadataKey' => [ 'type' => 'string', 'max' => 32766, ], 'metadataValue' => [ 'type' => 'string', 'max' => 32766, ], 'nextToken' => [ 'type' => 'string', 'max' => 10000, 'min' => 4, 'pattern' => '[a-zA-Z0-9=/+_.-]{4,10000}', ], 'region' => [ 'type' => 'string', 'max' => 25, 'min' => 2, 'pattern' => '[^:/]{2,25}', ], 'regionList' => [ 'type' => 'list', 'member' => [ 'shape' => 'region', ], 'max' => 10, 'min' => 1, ], 'service' => [ 'type' => 'string', 'max' => 30, 'min' => 2, 'pattern' => '[^:/]{2,30}', ], 'serviceList' => [ 'type' => 'list', 'member' => [ 'shape' => 'service', ], 'max' => 10, 'min' => 1, ], 'string' => [ 'type' => 'string', ], 'tagFilter' => [ 'type' => 'list', 'member' => [ 'shape' => 'tagSet', ], 'max' => 50, ], 'tagKey' => [ 'type' => 'string', 'max' => 127, 'pattern' => '.{0,127}', ], 'tagSet' => [ 'type' => 'map', 'key' => [ 'shape' => 'tagKey', ], 'value' => [ 'shape' => 'tagValue', ], 'max' => 50, ], 'tagValue' => [ 'type' => 'string', 'max' => 255, 'pattern' => '.{0,255}', ], 'timestamp' => [ 'type' => 'timestamp', ], ],];
