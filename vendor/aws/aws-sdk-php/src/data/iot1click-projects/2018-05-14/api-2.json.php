<?php
// This file was auto-generated from sdk-root/src/data/iot1click-projects/2018-05-14/api-2.json
return [ 'version' => '2.0', 'metadata' => [ 'apiVersion' => '2018-05-14', 'endpointPrefix' => 'projects.iot1click', 'jsonVersion' => '1.1', 'protocol' => 'rest-json', 'serviceAbbreviation' => 'AWS IoT 1-Click Projects', 'serviceFullName' => 'AWS IoT 1-Click Projects Service', 'serviceId' => 'IoT 1Click Projects', 'signatureVersion' => 'v4', 'signingName' => 'iot1click', 'uid' => 'iot1click-projects-2018-05-14', ], 'operations' => [ 'AssociateDeviceWithPlacement' => [ 'name' => 'AssociateDeviceWithPlacement', 'http' => [ 'method' => 'PUT', 'requestUri' => '/projects/{projectName}/placements/{placementName}/devices/{deviceTemplateName}', ], 'input' => [ 'shape' => 'AssociateDeviceWithPlacementRequest', ], 'output' => [ 'shape' => 'AssociateDeviceWithPlacementResponse', ], 'errors' => [ [ 'shape' => 'InternalFailureException', ], [ 'shape' => 'InvalidRequestException', ], [ 'shape' => 'ResourceConflictException', ], [ 'shape' => 'ResourceNotFoundException', ], ], ], 'CreatePlacement' => [ 'name' => 'CreatePlacement', 'http' => [ 'method' => 'POST', 'requestUri' => '/projects/{projectName}/placements', ], 'input' => [ 'shape' => 'CreatePlacementRequest', ], 'output' => [ 'shape' => 'CreatePlacementResponse', ], 'errors' => [ [ 'shape' => 'InternalFailureException', ], [ 'shape' => 'InvalidRequestException', ], [ 'shape' => 'ResourceConflictException', ], [ 'shape' => 'ResourceNotFoundException', ], ], ], 'CreateProject' => [ 'name' => 'CreateProject', 'http' => [ 'method' => 'POST', 'requestUri' => '/projects', ], 'input' => [ 'shape' => 'CreateProjectRequest', ], 'output' => [ 'shape' => 'CreateProjectResponse', ], 'errors' => [ [ 'shape' => 'InternalFailureException', ], [ 'shape' => 'InvalidRequestException', ], [ 'shape' => 'ResourceConflictException', ], ], ], 'DeletePlacement' => [ 'name' => 'DeletePlacement', 'http' => [ 'method' => 'DELETE', 'requestUri' => '/projects/{projectName}/placements/{placementName}', ], 'input' => [ 'shape' => 'DeletePlacementRequest', ], 'output' => [ 'shape' => 'DeletePlacementResponse', ], 'errors' => [ [ 'shape' => 'InternalFailureException', ], [ 'shape' => 'InvalidRequestException', ], [ 'shape' => 'ResourceNotFoundException', ], [ 'shape' => 'TooManyRequestsException', ], ], ], 'DeleteProject' => [ 'name' => 'DeleteProject', 'http' => [ 'method' => 'DELETE', 'requestUri' => '/projects/{projectName}', ], 'input' => [ 'shape' => 'DeleteProjectRequest', ], 'output' => [ 'shape' => 'DeleteProjectResponse', ], 'errors' => [ [ 'shape' => 'InternalFailureException', ], [ 'shape' => 'InvalidRequestException', ], [ 'shape' => 'ResourceNotFoundException', ], [ 'shape' => 'TooManyRequestsException', ], ], ], 'DescribePlacement' => [ 'name' => 'DescribePlacement', 'http' => [ 'method' => 'GET', 'requestUri' => '/projects/{projectName}/placements/{placementName}', ], 'input' => [ 'shape' => 'DescribePlacementRequest', ], 'output' => [ 'shape' => 'DescribePlacementResponse', ], 'errors' => [ [ 'shape' => 'InternalFailureException', ], [ 'shape' => 'InvalidRequestException', ], [ 'shape' => 'ResourceNotFoundException', ], ], ], 'DescribeProject' => [ 'name' => 'DescribeProject', 'http' => [ 'method' => 'GET', 'requestUri' => '/projects/{projectName}', ], 'input' => [ 'shape' => 'DescribeProjectRequest', ], 'output' => [ 'shape' => 'DescribeProjectResponse', ], 'errors' => [ [ 'shape' => 'InternalFailureException', ], [ 'shape' => 'InvalidRequestException', ], [ 'shape' => 'ResourceNotFoundException', ], ], ], 'DisassociateDeviceFromPlacement' => [ 'name' => 'DisassociateDeviceFromPlacement', 'http' => [ 'method' => 'DELETE', 'requestUri' => '/projects/{projectName}/placements/{placementName}/devices/{deviceTemplateName}', ], 'input' => [ 'shape' => 'DisassociateDeviceFromPlacementRequest', ], 'output' => [ 'shape' => 'DisassociateDeviceFromPlacementResponse', ], 'errors' => [ [ 'shape' => 'InternalFailureException', ], [ 'shape' => 'InvalidRequestException', ], [ 'shape' => 'ResourceNotFoundException', ], [ 'shape' => 'TooManyRequestsException', ], ], ], 'GetDevicesInPlacement' => [ 'name' => 'GetDevicesInPlacement', 'http' => [ 'method' => 'GET', 'requestUri' => '/projects/{projectName}/placements/{placementName}/devices', ], 'input' => [ 'shape' => 'GetDevicesInPlacementRequest', ], 'output' => [ 'shape' => 'GetDevicesInPlacementResponse', ], 'errors' => [ [ 'shape' => 'InternalFailureException', ], [ 'shape' => 'InvalidRequestException', ], [ 'shape' => 'ResourceNotFoundException', ], ], ], 'ListPlacements' => [ 'name' => 'ListPlacements', 'http' => [ 'method' => 'GET', 'requestUri' => '/projects/{projectName}/placements', ], 'input' => [ 'shape' => 'ListPlacementsRequest', ], 'output' => [ 'shape' => 'ListPlacementsResponse', ], 'errors' => [ [ 'shape' => 'InternalFailureException', ], [ 'shape' => 'InvalidRequestException', ], [ 'shape' => 'ResourceNotFoundException', ], ], ], 'ListProjects' => [ 'name' => 'ListProjects', 'http' => [ 'method' => 'GET', 'requestUri' => '/projects', ], 'input' => [ 'shape' => 'ListProjectsRequest', ], 'output' => [ 'shape' => 'ListProjectsResponse', ], 'errors' => [ [ 'shape' => 'InternalFailureException', ], [ 'shape' => 'InvalidRequestException', ], ], ], 'ListTagsForResource' => [ 'name' => 'ListTagsForResource', 'http' => [ 'method' => 'GET', 'requestUri' => '/tags/{resourceArn}', ], 'input' => [ 'shape' => 'ListTagsForResourceRequest', ], 'output' => [ 'shape' => 'ListTagsForResourceResponse', ], 'errors' => [ [ 'shape' => 'InternalFailureException', ], [ 'shape' => 'InvalidRequestException', ], [ 'shape' => 'ResourceNotFoundException', ], ], ], 'TagResource' => [ 'name' => 'TagResource', 'http' => [ 'method' => 'POST', 'requestUri' => '/tags/{resourceArn}', ], 'input' => [ 'shape' => 'TagResourceRequest', ], 'output' => [ 'shape' => 'TagResourceResponse', ], 'errors' => [ [ 'shape' => 'InternalFailureException', ], [ 'shape' => 'InvalidRequestException', ], [ 'shape' => 'ResourceNotFoundException', ], ], ], 'UntagResource' => [ 'name' => 'UntagResource', 'http' => [ 'method' => 'DELETE', 'requestUri' => '/tags/{resourceArn}', ], 'input' => [ 'shape' => 'UntagResourceRequest', ], 'output' => [ 'shape' => 'UntagResourceResponse', ], 'errors' => [ [ 'shape' => 'InternalFailureException', ], [ 'shape' => 'InvalidRequestException', ], [ 'shape' => 'ResourceNotFoundException', ], ], ], 'UpdatePlacement' => [ 'name' => 'UpdatePlacement', 'http' => [ 'method' => 'PUT', 'requestUri' => '/projects/{projectName}/placements/{placementName}', ], 'input' => [ 'shape' => 'UpdatePlacementRequest', ], 'output' => [ 'shape' => 'UpdatePlacementResponse', ], 'errors' => [ [ 'shape' => 'InternalFailureException', ], [ 'shape' => 'InvalidRequestException', ], [ 'shape' => 'ResourceNotFoundException', ], [ 'shape' => 'TooManyRequestsException', ], ], ], 'UpdateProject' => [ 'name' => 'UpdateProject', 'http' => [ 'method' => 'PUT', 'requestUri' => '/projects/{projectName}', ], 'input' => [ 'shape' => 'UpdateProjectRequest', ], 'output' => [ 'shape' => 'UpdateProjectResponse', ], 'errors' => [ [ 'shape' => 'InternalFailureException', ], [ 'shape' => 'InvalidRequestException', ], [ 'shape' => 'ResourceNotFoundException', ], [ 'shape' => 'TooManyRequestsException', ], ], ], ], 'shapes' => [ 'AssociateDeviceWithPlacementRequest' => [ 'type' => 'structure', 'required' => [ 'projectName', 'placementName', 'deviceId', 'deviceTemplateName', ], 'members' => [ 'projectName' => [ 'shape' => 'ProjectName', 'location' => 'uri', 'locationName' => 'projectName', ], 'placementName' => [ 'shape' => 'PlacementName', 'location' => 'uri', 'locationName' => 'placementName', ], 'deviceId' => [ 'shape' => 'DeviceId', ], 'deviceTemplateName' => [ 'shape' => 'DeviceTemplateName', 'location' => 'uri', 'locationName' => 'deviceTemplateName', ], ], ], 'AssociateDeviceWithPlacementResponse' => [ 'type' => 'structure', 'members' => [], ], 'AttributeDefaultValue' => [ 'type' => 'string', 'max' => 800, ], 'AttributeName' => [ 'type' => 'string', 'max' => 128, 'min' => 1, ], 'AttributeValue' => [ 'type' => 'string', 'max' => 800, ], 'Code' => [ 'type' => 'string', ], 'CreatePlacementRequest' => [ 'type' => 'structure', 'required' => [ 'placementName', 'projectName', ], 'members' => [ 'placementName' => [ 'shape' => 'PlacementName', ], 'projectName' => [ 'shape' => 'ProjectName', 'location' => 'uri', 'locationName' => 'projectName', ], 'attributes' => [ 'shape' => 'PlacementAttributeMap', ], ], ], 'CreatePlacementResponse' => [ 'type' => 'structure', 'members' => [], ], 'CreateProjectRequest' => [ 'type' => 'structure', 'required' => [ 'projectName', ], 'members' => [ 'projectName' => [ 'shape' => 'ProjectName', ], 'description' => [ 'shape' => 'Description', ], 'placementTemplate' => [ 'shape' => 'PlacementTemplate', ], 'tags' => [ 'shape' => 'TagMap', ], ], ], 'CreateProjectResponse' => [ 'type' => 'structure', 'members' => [], ], 'DefaultPlacementAttributeMap' => [ 'type' => 'map', 'key' => [ 'shape' => 'AttributeName', ], 'value' => [ 'shape' => 'AttributeDefaultValue', ], ], 'DeletePlacementRequest' => [ 'type' => 'structure', 'required' => [ 'placementName', 'projectName', ], 'members' => [ 'placementName' => [ 'shape' => 'PlacementName', 'location' => 'uri', 'locationName' => 'placementName', ], 'projectName' => [ 'shape' => 'ProjectName', 'location' => 'uri', 'locationName' => 'projectName', ], ], ], 'DeletePlacementResponse' => [ 'type' => 'structure', 'members' => [], ], 'DeleteProjectRequest' => [ 'type' => 'structure', 'required' => [ 'projectName', ], 'members' => [ 'projectName' => [ 'shape' => 'ProjectName', 'location' => 'uri', 'locationName' => 'projectName', ], ], ], 'DeleteProjectResponse' => [ 'type' => 'structure', 'members' => [], ], 'DescribePlacementRequest' => [ 'type' => 'structure', 'required' => [ 'placementName', 'projectName', ], 'members' => [ 'placementName' => [ 'shape' => 'PlacementName', 'location' => 'uri', 'locationName' => 'placementName', ], 'projectName' => [ 'shape' => 'ProjectName', 'location' => 'uri', 'locationName' => 'projectName', ], ], ], 'DescribePlacementResponse' => [ 'type' => 'structure', 'required' => [ 'placement', ], 'members' => [ 'placement' => [ 'shape' => 'PlacementDescription', ], ], ], 'DescribeProjectRequest' => [ 'type' => 'structure', 'required' => [ 'projectName', ], 'members' => [ 'projectName' => [ 'shape' => 'ProjectName', 'location' => 'uri', 'locationName' => 'projectName', ], ], ], 'DescribeProjectResponse' => [ 'type' => 'structure', 'required' => [ 'project', ], 'members' => [ 'project' => [ 'shape' => 'ProjectDescription', ], ], ], 'Description' => [ 'type' => 'string', 'max' => 500, 'min' => 0, ], 'DeviceCallbackKey' => [ 'type' => 'string', 'max' => 128, 'min' => 1, ], 'DeviceCallbackOverrideMap' => [ 'type' => 'map', 'key' => [ 'shape' => 'DeviceCallbackKey', ], 'value' => [ 'shape' => 'DeviceCallbackValue', ], ], 'DeviceCallbackValue' => [ 'type' => 'string', 'max' => 200, ], 'DeviceId' => [ 'type' => 'string', 'max' => 32, 'min' => 1, ], 'DeviceMap' => [ 'type' => 'map', 'key' => [ 'shape' => 'DeviceTemplateName', ], 'value' => [ 'shape' => 'DeviceId', ], ], 'DeviceTemplate' => [ 'type' => 'structure', 'members' => [ 'deviceType' => [ 'shape' => 'DeviceType', ], 'callbackOverrides' => [ 'shape' => 'DeviceCallbackOverrideMap', ], ], ], 'DeviceTemplateMap' => [ 'type' => 'map', 'key' => [ 'shape' => 'DeviceTemplateName', ], 'value' => [ 'shape' => 'DeviceTemplate', ], ], 'DeviceTemplateName' => [ 'type' => 'string', 'max' => 128, 'min' => 1, 'pattern' => '^[a-zA-Z0-9_-]+$', ], 'DeviceType' => [ 'type' => 'string', 'max' => 128, ], 'DisassociateDeviceFromPlacementRequest' => [ 'type' => 'structure', 'required' => [ 'projectName', 'placementName', 'deviceTemplateName', ], 'members' => [ 'projectName' => [ 'shape' => 'ProjectName', 'location' => 'uri', 'locationName' => 'projectName', ], 'placementName' => [ 'shape' => 'PlacementName', 'location' => 'uri', 'locationName' => 'placementName', ], 'deviceTemplateName' => [ 'shape' => 'DeviceTemplateName', 'location' => 'uri', 'locationName' => 'deviceTemplateName', ], ], ], 'DisassociateDeviceFromPlacementResponse' => [ 'type' => 'structure', 'members' => [], ], 'GetDevicesInPlacementRequest' => [ 'type' => 'structure', 'required' => [ 'projectName', 'placementName', ], 'members' => [ 'projectName' => [ 'shape' => 'ProjectName', 'location' => 'uri', 'locationName' => 'projectName', ], 'placementName' => [ 'shape' => 'PlacementName', 'location' => 'uri', 'locationName' => 'placementName', ], ], ], 'GetDevicesInPlacementResponse' => [ 'type' => 'structure', 'required' => [ 'devices', ], 'members' => [ 'devices' => [ 'shape' => 'DeviceMap', ], ], ], 'InternalFailureException' => [ 'type' => 'structure', 'required' => [ 'code', 'message', ], 'members' => [ 'code' => [ 'shape' => 'Code', ], 'message' => [ 'shape' => 'Message', ], ], 'error' => [ 'httpStatusCode' => 500, ], 'exception' => true, ], 'InvalidRequestException' => [ 'type' => 'structure', 'required' => [ 'code', 'message', ], 'members' => [ 'code' => [ 'shape' => 'Code', ], 'message' => [ 'shape' => 'Message', ], ], 'error' => [ 'httpStatusCode' => 400, ], 'exception' => true, ], 'ListPlacementsRequest' => [ 'type' => 'structure', 'required' => [ 'projectName', ], 'members' => [ 'projectName' => [ 'shape' => 'ProjectName', 'location' => 'uri', 'locationName' => 'projectName', ], 'nextToken' => [ 'shape' => 'NextToken', 'location' => 'querystring', 'locationName' => 'nextToken', ], 'maxResults' => [ 'shape' => 'MaxResults', 'location' => 'querystring', 'locationName' => 'maxResults', ], ], ], 'ListPlacementsResponse' => [ 'type' => 'structure', 'required' => [ 'placements', ], 'members' => [ 'placements' => [ 'shape' => 'PlacementSummaryList', ], 'nextToken' => [ 'shape' => 'NextToken', ], ], ], 'ListProjectsRequest' => [ 'type' => 'structure', 'members' => [ 'nextToken' => [ 'shape' => 'NextToken', 'location' => 'querystring', 'locationName' => 'nextToken', ], 'maxResults' => [ 'shape' => 'MaxResults', 'location' => 'querystring', 'locationName' => 'maxResults', ], ], ], 'ListProjectsResponse' => [ 'type' => 'structure', 'required' => [ 'projects', ], 'members' => [ 'projects' => [ 'shape' => 'ProjectSummaryList', ], 'nextToken' => [ 'shape' => 'NextToken', ], ], ], 'ListTagsForResourceRequest' => [ 'type' => 'structure', 'required' => [ 'resourceArn', ], 'members' => [ 'resourceArn' => [ 'shape' => 'ProjectArn', 'location' => 'uri', 'locationName' => 'resourceArn', ], ], ], 'ListTagsForResourceResponse' => [ 'type' => 'structure', 'members' => [ 'tags' => [ 'shape' => 'TagMap', ], ], ], 'MaxResults' => [ 'type' => 'integer', 'max' => 250, 'min' => 1, ], 'Message' => [ 'type' => 'string', ], 'NextToken' => [ 'type' => 'string', 'max' => 1024, 'min' => 1, ], 'PlacementAttributeMap' => [ 'type' => 'map', 'key' => [ 'shape' => 'AttributeName', ], 'value' => [ 'shape' => 'AttributeValue', ], ], 'PlacementDescription' => [ 'type' => 'structure', 'required' => [ 'projectName', 'placementName', 'attributes', 'createdDate', 'updatedDate', ], 'members' => [ 'projectName' => [ 'shape' => 'ProjectName', ], 'placementName' => [ 'shape' => 'PlacementName', ], 'attributes' => [ 'shape' => 'PlacementAttributeMap', ], 'createdDate' => [ 'shape' => 'Time', ], 'updatedDate' => [ 'shape' => 'Time', ], ], ], 'PlacementName' => [ 'type' => 'string', 'max' => 128, 'min' => 1, 'pattern' => '^[a-zA-Z0-9_-]+$', ], 'PlacementSummary' => [ 'type' => 'structure', 'required' => [ 'projectName', 'placementName', 'createdDate', 'updatedDate', ], 'members' => [ 'projectName' => [ 'shape' => 'ProjectName', ], 'placementName' => [ 'shape' => 'PlacementName', ], 'createdDate' => [ 'shape' => 'Time', ], 'updatedDate' => [ 'shape' => 'Time', ], ], ], 'PlacementSummaryList' => [ 'type' => 'list', 'member' => [ 'shape' => 'PlacementSummary', ], ], 'PlacementTemplate' => [ 'type' => 'structure', 'members' => [ 'defaultAttributes' => [ 'shape' => 'DefaultPlacementAttributeMap', ], 'deviceTemplates' => [ 'shape' => 'DeviceTemplateMap', ], ], ], 'ProjectArn' => [ 'type' => 'string', 'pattern' => '^arn:aws:iot1click:[A-Za-z0-9_/.-]{0,63}:\\d+:projects/[0-9A-Za-z_-]{1,128}$', ], 'ProjectDescription' => [ 'type' => 'structure', 'required' => [ 'projectName', 'createdDate', 'updatedDate', ], 'members' => [ 'arn' => [ 'shape' => 'ProjectArn', ], 'projectName' => [ 'shape' => 'ProjectName', ], 'description' => [ 'shape' => 'Description', ], 'createdDate' => [ 'shape' => 'Time', ], 'updatedDate' => [ 'shape' => 'Time', ], 'placementTemplate' => [ 'shape' => 'PlacementTemplate', ], 'tags' => [ 'shape' => 'TagMap', ], ], ], 'ProjectName' => [ 'type' => 'string', 'max' => 128, 'min' => 1, 'pattern' => '^[0-9A-Za-z_-]+$', ], 'ProjectSummary' => [ 'type' => 'structure', 'required' => [ 'projectName', 'createdDate', 'updatedDate', ], 'members' => [ 'arn' => [ 'shape' => 'ProjectArn', ], 'projectName' => [ 'shape' => 'ProjectName', ], 'createdDate' => [ 'shape' => 'Time', ], 'updatedDate' => [ 'shape' => 'Time', ], 'tags' => [ 'shape' => 'TagMap', ], ], ], 'ProjectSummaryList' => [ 'type' => 'list', 'member' => [ 'shape' => 'ProjectSummary', ], ], 'ResourceConflictException' => [ 'type' => 'structure', 'required' => [ 'code', 'message', ], 'members' => [ 'code' => [ 'shape' => 'Code', ], 'message' => [ 'shape' => 'Message', ], ], 'error' => [ 'httpStatusCode' => 409, ], 'exception' => true, ], 'ResourceNotFoundException' => [ 'type' => 'structure', 'required' => [ 'code', 'message', ], 'members' => [ 'code' => [ 'shape' => 'Code', ], 'message' => [ 'shape' => 'Message', ], ], 'error' => [ 'httpStatusCode' => 404, ], 'exception' => true, ], 'TagKey' => [ 'type' => 'string', 'max' => 128, 'min' => 1, 'pattern' => '^(?!aws:)[a-zA-Z+-=._:/]+$', ], 'TagKeyList' => [ 'type' => 'list', 'member' => [ 'shape' => 'TagKey', ], 'max' => 50, 'min' => 1, ], 'TagMap' => [ 'type' => 'map', 'key' => [ 'shape' => 'TagKey', ], 'value' => [ 'shape' => 'TagValue', ], 'max' => 50, 'min' => 1, ], 'TagResourceRequest' => [ 'type' => 'structure', 'required' => [ 'resourceArn', 'tags', ], 'members' => [ 'resourceArn' => [ 'shape' => 'ProjectArn', 'location' => 'uri', 'locationName' => 'resourceArn', ], 'tags' => [ 'shape' => 'TagMap', ], ], ], 'TagResourceResponse' => [ 'type' => 'structure', 'members' => [], ], 'TagValue' => [ 'type' => 'string', 'max' => 256, ], 'Time' => [ 'type' => 'timestamp', ], 'TooManyRequestsException' => [ 'type' => 'structure', 'required' => [ 'code', 'message', ], 'members' => [ 'code' => [ 'shape' => 'Code', ], 'message' => [ 'shape' => 'Message', ], ], 'error' => [ 'httpStatusCode' => 429, ], 'exception' => true, ], 'UntagResourceRequest' => [ 'type' => 'structure', 'required' => [ 'resourceArn', 'tagKeys', ], 'members' => [ 'resourceArn' => [ 'shape' => 'ProjectArn', 'location' => 'uri', 'locationName' => 'resourceArn', ], 'tagKeys' => [ 'shape' => 'TagKeyList', 'location' => 'querystring', 'locationName' => 'tagKeys', ], ], ], 'UntagResourceResponse' => [ 'type' => 'structure', 'members' => [], ], 'UpdatePlacementRequest' => [ 'type' => 'structure', 'required' => [ 'placementName', 'projectName', ], 'members' => [ 'placementName' => [ 'shape' => 'PlacementName', 'location' => 'uri', 'locationName' => 'placementName', ], 'projectName' => [ 'shape' => 'ProjectName', 'location' => 'uri', 'locationName' => 'projectName', ], 'attributes' => [ 'shape' => 'PlacementAttributeMap', ], ], ], 'UpdatePlacementResponse' => [ 'type' => 'structure', 'members' => [], ], 'UpdateProjectRequest' => [ 'type' => 'structure', 'required' => [ 'projectName', ], 'members' => [ 'projectName' => [ 'shape' => 'ProjectName', 'location' => 'uri', 'locationName' => 'projectName', ], 'description' => [ 'shape' => 'Description', ], 'placementTemplate' => [ 'shape' => 'PlacementTemplate', ], ], ], 'UpdateProjectResponse' => [ 'type' => 'structure', 'members' => [], ], ],];
