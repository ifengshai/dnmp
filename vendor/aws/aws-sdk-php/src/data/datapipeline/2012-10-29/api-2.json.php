<?php
// This file was auto-generated from sdk-root/src/data/datapipeline/2012-10-29/api-2.json
return [ 'version' => '2.0', 'metadata' => [ 'apiVersion' => '2012-10-29', 'endpointPrefix' => 'datapipeline', 'jsonVersion' => '1.1', 'serviceFullName' => 'AWS Data Pipeline', 'signatureVersion' => 'v4', 'targetPrefix' => 'DataPipeline', 'protocol' => 'json', 'uid' => 'datapipeline-2012-10-29', ], 'operations' => [ 'ActivatePipeline' => [ 'name' => 'ActivatePipeline', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'ActivatePipelineInput', ], 'output' => [ 'shape' => 'ActivatePipelineOutput', ], 'errors' => [ [ 'shape' => 'PipelineNotFoundException', 'exception' => true, ], [ 'shape' => 'PipelineDeletedException', 'exception' => true, ], [ 'shape' => 'InternalServiceError', 'exception' => true, 'fault' => true, ], [ 'shape' => 'InvalidRequestException', 'exception' => true, ], ], ], 'AddTags' => [ 'name' => 'AddTags', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'AddTagsInput', ], 'output' => [ 'shape' => 'AddTagsOutput', ], 'errors' => [ [ 'shape' => 'InternalServiceError', 'exception' => true, 'fault' => true, ], [ 'shape' => 'InvalidRequestException', 'exception' => true, ], [ 'shape' => 'PipelineNotFoundException', 'exception' => true, ], [ 'shape' => 'PipelineDeletedException', 'exception' => true, ], ], ], 'CreatePipeline' => [ 'name' => 'CreatePipeline', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'CreatePipelineInput', ], 'output' => [ 'shape' => 'CreatePipelineOutput', ], 'errors' => [ [ 'shape' => 'InternalServiceError', 'exception' => true, 'fault' => true, ], [ 'shape' => 'InvalidRequestException', 'exception' => true, ], ], ], 'DeactivatePipeline' => [ 'name' => 'DeactivatePipeline', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'DeactivatePipelineInput', ], 'output' => [ 'shape' => 'DeactivatePipelineOutput', ], 'errors' => [ [ 'shape' => 'PipelineNotFoundException', 'exception' => true, ], [ 'shape' => 'PipelineDeletedException', 'exception' => true, ], [ 'shape' => 'InternalServiceError', 'exception' => true, 'fault' => true, ], [ 'shape' => 'InvalidRequestException', 'exception' => true, ], ], ], 'DeletePipeline' => [ 'name' => 'DeletePipeline', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'DeletePipelineInput', ], 'errors' => [ [ 'shape' => 'PipelineNotFoundException', 'exception' => true, ], [ 'shape' => 'InternalServiceError', 'exception' => true, 'fault' => true, ], [ 'shape' => 'InvalidRequestException', 'exception' => true, ], ], ], 'DescribeObjects' => [ 'name' => 'DescribeObjects', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'DescribeObjectsInput', ], 'output' => [ 'shape' => 'DescribeObjectsOutput', ], 'errors' => [ [ 'shape' => 'InternalServiceError', 'exception' => true, 'fault' => true, ], [ 'shape' => 'InvalidRequestException', 'exception' => true, ], [ 'shape' => 'PipelineNotFoundException', 'exception' => true, ], [ 'shape' => 'PipelineDeletedException', 'exception' => true, ], ], ], 'DescribePipelines' => [ 'name' => 'DescribePipelines', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'DescribePipelinesInput', ], 'output' => [ 'shape' => 'DescribePipelinesOutput', ], 'errors' => [ [ 'shape' => 'PipelineNotFoundException', 'exception' => true, ], [ 'shape' => 'PipelineDeletedException', 'exception' => true, ], [ 'shape' => 'InternalServiceError', 'exception' => true, 'fault' => true, ], [ 'shape' => 'InvalidRequestException', 'exception' => true, ], ], ], 'EvaluateExpression' => [ 'name' => 'EvaluateExpression', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'EvaluateExpressionInput', ], 'output' => [ 'shape' => 'EvaluateExpressionOutput', ], 'errors' => [ [ 'shape' => 'InternalServiceError', 'exception' => true, 'fault' => true, ], [ 'shape' => 'TaskNotFoundException', 'exception' => true, ], [ 'shape' => 'InvalidRequestException', 'exception' => true, ], [ 'shape' => 'PipelineNotFoundException', 'exception' => true, ], [ 'shape' => 'PipelineDeletedException', 'exception' => true, ], ], ], 'GetPipelineDefinition' => [ 'name' => 'GetPipelineDefinition', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'GetPipelineDefinitionInput', ], 'output' => [ 'shape' => 'GetPipelineDefinitionOutput', ], 'errors' => [ [ 'shape' => 'InternalServiceError', 'exception' => true, 'fault' => true, ], [ 'shape' => 'InvalidRequestException', 'exception' => true, ], [ 'shape' => 'PipelineNotFoundException', 'exception' => true, ], [ 'shape' => 'PipelineDeletedException', 'exception' => true, ], ], ], 'ListPipelines' => [ 'name' => 'ListPipelines', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'ListPipelinesInput', ], 'output' => [ 'shape' => 'ListPipelinesOutput', ], 'errors' => [ [ 'shape' => 'InternalServiceError', 'exception' => true, 'fault' => true, ], [ 'shape' => 'InvalidRequestException', 'exception' => true, ], ], ], 'PollForTask' => [ 'name' => 'PollForTask', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'PollForTaskInput', ], 'output' => [ 'shape' => 'PollForTaskOutput', ], 'errors' => [ [ 'shape' => 'InternalServiceError', 'exception' => true, 'fault' => true, ], [ 'shape' => 'InvalidRequestException', 'exception' => true, ], [ 'shape' => 'TaskNotFoundException', 'exception' => true, ], ], ], 'PutPipelineDefinition' => [ 'name' => 'PutPipelineDefinition', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'PutPipelineDefinitionInput', ], 'output' => [ 'shape' => 'PutPipelineDefinitionOutput', ], 'errors' => [ [ 'shape' => 'InternalServiceError', 'exception' => true, 'fault' => true, ], [ 'shape' => 'InvalidRequestException', 'exception' => true, ], [ 'shape' => 'PipelineNotFoundException', 'exception' => true, ], [ 'shape' => 'PipelineDeletedException', 'exception' => true, ], ], ], 'QueryObjects' => [ 'name' => 'QueryObjects', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'QueryObjectsInput', ], 'output' => [ 'shape' => 'QueryObjectsOutput', ], 'errors' => [ [ 'shape' => 'PipelineNotFoundException', 'exception' => true, ], [ 'shape' => 'PipelineDeletedException', 'exception' => true, ], [ 'shape' => 'InternalServiceError', 'exception' => true, 'fault' => true, ], [ 'shape' => 'InvalidRequestException', 'exception' => true, ], ], ], 'RemoveTags' => [ 'name' => 'RemoveTags', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'RemoveTagsInput', ], 'output' => [ 'shape' => 'RemoveTagsOutput', ], 'errors' => [ [ 'shape' => 'InternalServiceError', 'exception' => true, 'fault' => true, ], [ 'shape' => 'InvalidRequestException', 'exception' => true, ], [ 'shape' => 'PipelineNotFoundException', 'exception' => true, ], [ 'shape' => 'PipelineDeletedException', 'exception' => true, ], ], ], 'ReportTaskProgress' => [ 'name' => 'ReportTaskProgress', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'ReportTaskProgressInput', ], 'output' => [ 'shape' => 'ReportTaskProgressOutput', ], 'errors' => [ [ 'shape' => 'InternalServiceError', 'exception' => true, 'fault' => true, ], [ 'shape' => 'InvalidRequestException', 'exception' => true, ], [ 'shape' => 'TaskNotFoundException', 'exception' => true, ], [ 'shape' => 'PipelineNotFoundException', 'exception' => true, ], [ 'shape' => 'PipelineDeletedException', 'exception' => true, ], ], ], 'ReportTaskRunnerHeartbeat' => [ 'name' => 'ReportTaskRunnerHeartbeat', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'ReportTaskRunnerHeartbeatInput', ], 'output' => [ 'shape' => 'ReportTaskRunnerHeartbeatOutput', ], 'errors' => [ [ 'shape' => 'InternalServiceError', 'exception' => true, 'fault' => true, ], [ 'shape' => 'InvalidRequestException', 'exception' => true, ], ], ], 'SetStatus' => [ 'name' => 'SetStatus', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'SetStatusInput', ], 'errors' => [ [ 'shape' => 'PipelineNotFoundException', 'exception' => true, ], [ 'shape' => 'PipelineDeletedException', 'exception' => true, ], [ 'shape' => 'InternalServiceError', 'exception' => true, 'fault' => true, ], [ 'shape' => 'InvalidRequestException', 'exception' => true, ], ], ], 'SetTaskStatus' => [ 'name' => 'SetTaskStatus', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'SetTaskStatusInput', ], 'output' => [ 'shape' => 'SetTaskStatusOutput', ], 'errors' => [ [ 'shape' => 'InternalServiceError', 'exception' => true, 'fault' => true, ], [ 'shape' => 'TaskNotFoundException', 'exception' => true, ], [ 'shape' => 'InvalidRequestException', 'exception' => true, ], [ 'shape' => 'PipelineNotFoundException', 'exception' => true, ], [ 'shape' => 'PipelineDeletedException', 'exception' => true, ], ], ], 'ValidatePipelineDefinition' => [ 'name' => 'ValidatePipelineDefinition', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'ValidatePipelineDefinitionInput', ], 'output' => [ 'shape' => 'ValidatePipelineDefinitionOutput', ], 'errors' => [ [ 'shape' => 'InternalServiceError', 'exception' => true, 'fault' => true, ], [ 'shape' => 'InvalidRequestException', 'exception' => true, ], [ 'shape' => 'PipelineNotFoundException', 'exception' => true, ], [ 'shape' => 'PipelineDeletedException', 'exception' => true, ], ], ], ], 'shapes' => [ 'ActivatePipelineInput' => [ 'type' => 'structure', 'required' => [ 'pipelineId', ], 'members' => [ 'pipelineId' => [ 'shape' => 'id', ], 'parameterValues' => [ 'shape' => 'ParameterValueList', ], 'startTimestamp' => [ 'shape' => 'timestamp', ], ], ], 'ActivatePipelineOutput' => [ 'type' => 'structure', 'members' => [], ], 'AddTagsInput' => [ 'type' => 'structure', 'required' => [ 'pipelineId', 'tags', ], 'members' => [ 'pipelineId' => [ 'shape' => 'id', ], 'tags' => [ 'shape' => 'tagList', ], ], ], 'AddTagsOutput' => [ 'type' => 'structure', 'members' => [], ], 'CreatePipelineInput' => [ 'type' => 'structure', 'required' => [ 'name', 'uniqueId', ], 'members' => [ 'name' => [ 'shape' => 'id', ], 'uniqueId' => [ 'shape' => 'id', ], 'description' => [ 'shape' => 'string', ], 'tags' => [ 'shape' => 'tagList', ], ], ], 'CreatePipelineOutput' => [ 'type' => 'structure', 'required' => [ 'pipelineId', ], 'members' => [ 'pipelineId' => [ 'shape' => 'id', ], ], ], 'DeactivatePipelineInput' => [ 'type' => 'structure', 'required' => [ 'pipelineId', ], 'members' => [ 'pipelineId' => [ 'shape' => 'id', ], 'cancelActive' => [ 'shape' => 'cancelActive', ], ], ], 'DeactivatePipelineOutput' => [ 'type' => 'structure', 'members' => [], ], 'DeletePipelineInput' => [ 'type' => 'structure', 'required' => [ 'pipelineId', ], 'members' => [ 'pipelineId' => [ 'shape' => 'id', ], ], ], 'DescribeObjectsInput' => [ 'type' => 'structure', 'required' => [ 'pipelineId', 'objectIds', ], 'members' => [ 'pipelineId' => [ 'shape' => 'id', ], 'objectIds' => [ 'shape' => 'idList', ], 'evaluateExpressions' => [ 'shape' => 'boolean', ], 'marker' => [ 'shape' => 'string', ], ], ], 'DescribeObjectsOutput' => [ 'type' => 'structure', 'required' => [ 'pipelineObjects', ], 'members' => [ 'pipelineObjects' => [ 'shape' => 'PipelineObjectList', ], 'marker' => [ 'shape' => 'string', ], 'hasMoreResults' => [ 'shape' => 'boolean', ], ], ], 'DescribePipelinesInput' => [ 'type' => 'structure', 'required' => [ 'pipelineIds', ], 'members' => [ 'pipelineIds' => [ 'shape' => 'idList', ], ], ], 'DescribePipelinesOutput' => [ 'type' => 'structure', 'required' => [ 'pipelineDescriptionList', ], 'members' => [ 'pipelineDescriptionList' => [ 'shape' => 'PipelineDescriptionList', ], ], ], 'EvaluateExpressionInput' => [ 'type' => 'structure', 'required' => [ 'pipelineId', 'objectId', 'expression', ], 'members' => [ 'pipelineId' => [ 'shape' => 'id', ], 'objectId' => [ 'shape' => 'id', ], 'expression' => [ 'shape' => 'longString', ], ], ], 'EvaluateExpressionOutput' => [ 'type' => 'structure', 'required' => [ 'evaluatedExpression', ], 'members' => [ 'evaluatedExpression' => [ 'shape' => 'longString', ], ], ], 'Field' => [ 'type' => 'structure', 'required' => [ 'key', ], 'members' => [ 'key' => [ 'shape' => 'fieldNameString', ], 'stringValue' => [ 'shape' => 'fieldStringValue', ], 'refValue' => [ 'shape' => 'fieldNameString', ], ], ], 'GetPipelineDefinitionInput' => [ 'type' => 'structure', 'required' => [ 'pipelineId', ], 'members' => [ 'pipelineId' => [ 'shape' => 'id', ], 'version' => [ 'shape' => 'string', ], ], ], 'GetPipelineDefinitionOutput' => [ 'type' => 'structure', 'members' => [ 'pipelineObjects' => [ 'shape' => 'PipelineObjectList', ], 'parameterObjects' => [ 'shape' => 'ParameterObjectList', ], 'parameterValues' => [ 'shape' => 'ParameterValueList', ], ], ], 'InstanceIdentity' => [ 'type' => 'structure', 'members' => [ 'document' => [ 'shape' => 'string', ], 'signature' => [ 'shape' => 'string', ], ], ], 'InternalServiceError' => [ 'type' => 'structure', 'members' => [ 'message' => [ 'shape' => 'errorMessage', ], ], 'exception' => true, 'fault' => true, ], 'InvalidRequestException' => [ 'type' => 'structure', 'members' => [ 'message' => [ 'shape' => 'errorMessage', ], ], 'exception' => true, ], 'ListPipelinesInput' => [ 'type' => 'structure', 'members' => [ 'marker' => [ 'shape' => 'string', ], ], ], 'ListPipelinesOutput' => [ 'type' => 'structure', 'required' => [ 'pipelineIdList', ], 'members' => [ 'pipelineIdList' => [ 'shape' => 'pipelineList', ], 'marker' => [ 'shape' => 'string', ], 'hasMoreResults' => [ 'shape' => 'boolean', ], ], ], 'Operator' => [ 'type' => 'structure', 'members' => [ 'type' => [ 'shape' => 'OperatorType', ], 'values' => [ 'shape' => 'stringList', ], ], ], 'OperatorType' => [ 'type' => 'string', 'enum' => [ 'EQ', 'REF_EQ', 'LE', 'GE', 'BETWEEN', ], ], 'ParameterAttribute' => [ 'type' => 'structure', 'required' => [ 'key', 'stringValue', ], 'members' => [ 'key' => [ 'shape' => 'attributeNameString', ], 'stringValue' => [ 'shape' => 'attributeValueString', ], ], ], 'ParameterAttributeList' => [ 'type' => 'list', 'member' => [ 'shape' => 'ParameterAttribute', ], ], 'ParameterObject' => [ 'type' => 'structure', 'required' => [ 'id', 'attributes', ], 'members' => [ 'id' => [ 'shape' => 'fieldNameString', ], 'attributes' => [ 'shape' => 'ParameterAttributeList', ], ], ], 'ParameterObjectList' => [ 'type' => 'list', 'member' => [ 'shape' => 'ParameterObject', ], ], 'ParameterValue' => [ 'type' => 'structure', 'required' => [ 'id', 'stringValue', ], 'members' => [ 'id' => [ 'shape' => 'fieldNameString', ], 'stringValue' => [ 'shape' => 'fieldStringValue', ], ], ], 'ParameterValueList' => [ 'type' => 'list', 'member' => [ 'shape' => 'ParameterValue', ], ], 'PipelineDeletedException' => [ 'type' => 'structure', 'members' => [ 'message' => [ 'shape' => 'errorMessage', ], ], 'exception' => true, ], 'PipelineDescription' => [ 'type' => 'structure', 'required' => [ 'pipelineId', 'name', 'fields', ], 'members' => [ 'pipelineId' => [ 'shape' => 'id', ], 'name' => [ 'shape' => 'id', ], 'fields' => [ 'shape' => 'fieldList', ], 'description' => [ 'shape' => 'string', ], 'tags' => [ 'shape' => 'tagList', ], ], ], 'PipelineDescriptionList' => [ 'type' => 'list', 'member' => [ 'shape' => 'PipelineDescription', ], ], 'PipelineIdName' => [ 'type' => 'structure', 'members' => [ 'id' => [ 'shape' => 'id', ], 'name' => [ 'shape' => 'id', ], ], ], 'PipelineNotFoundException' => [ 'type' => 'structure', 'members' => [ 'message' => [ 'shape' => 'errorMessage', ], ], 'exception' => true, ], 'PipelineObject' => [ 'type' => 'structure', 'required' => [ 'id', 'name', 'fields', ], 'members' => [ 'id' => [ 'shape' => 'id', ], 'name' => [ 'shape' => 'id', ], 'fields' => [ 'shape' => 'fieldList', ], ], ], 'PipelineObjectList' => [ 'type' => 'list', 'member' => [ 'shape' => 'PipelineObject', ], ], 'PipelineObjectMap' => [ 'type' => 'map', 'key' => [ 'shape' => 'id', ], 'value' => [ 'shape' => 'PipelineObject', ], ], 'PollForTaskInput' => [ 'type' => 'structure', 'required' => [ 'workerGroup', ], 'members' => [ 'workerGroup' => [ 'shape' => 'string', ], 'hostname' => [ 'shape' => 'id', ], 'instanceIdentity' => [ 'shape' => 'InstanceIdentity', ], ], ], 'PollForTaskOutput' => [ 'type' => 'structure', 'members' => [ 'taskObject' => [ 'shape' => 'TaskObject', ], ], ], 'PutPipelineDefinitionInput' => [ 'type' => 'structure', 'required' => [ 'pipelineId', 'pipelineObjects', ], 'members' => [ 'pipelineId' => [ 'shape' => 'id', ], 'pipelineObjects' => [ 'shape' => 'PipelineObjectList', ], 'parameterObjects' => [ 'shape' => 'ParameterObjectList', ], 'parameterValues' => [ 'shape' => 'ParameterValueList', ], ], ], 'PutPipelineDefinitionOutput' => [ 'type' => 'structure', 'required' => [ 'errored', ], 'members' => [ 'validationErrors' => [ 'shape' => 'ValidationErrors', ], 'validationWarnings' => [ 'shape' => 'ValidationWarnings', ], 'errored' => [ 'shape' => 'boolean', ], ], ], 'Query' => [ 'type' => 'structure', 'members' => [ 'selectors' => [ 'shape' => 'SelectorList', ], ], ], 'QueryObjectsInput' => [ 'type' => 'structure', 'required' => [ 'pipelineId', 'sphere', ], 'members' => [ 'pipelineId' => [ 'shape' => 'id', ], 'query' => [ 'shape' => 'Query', ], 'sphere' => [ 'shape' => 'string', ], 'marker' => [ 'shape' => 'string', ], 'limit' => [ 'shape' => 'int', ], ], ], 'QueryObjectsOutput' => [ 'type' => 'structure', 'members' => [ 'ids' => [ 'shape' => 'idList', ], 'marker' => [ 'shape' => 'string', ], 'hasMoreResults' => [ 'shape' => 'boolean', ], ], ], 'RemoveTagsInput' => [ 'type' => 'structure', 'required' => [ 'pipelineId', 'tagKeys', ], 'members' => [ 'pipelineId' => [ 'shape' => 'id', ], 'tagKeys' => [ 'shape' => 'stringList', ], ], ], 'RemoveTagsOutput' => [ 'type' => 'structure', 'members' => [], ], 'ReportTaskProgressInput' => [ 'type' => 'structure', 'required' => [ 'taskId', ], 'members' => [ 'taskId' => [ 'shape' => 'taskId', ], 'fields' => [ 'shape' => 'fieldList', ], ], ], 'ReportTaskProgressOutput' => [ 'type' => 'structure', 'required' => [ 'canceled', ], 'members' => [ 'canceled' => [ 'shape' => 'boolean', ], ], ], 'ReportTaskRunnerHeartbeatInput' => [ 'type' => 'structure', 'required' => [ 'taskrunnerId', ], 'members' => [ 'taskrunnerId' => [ 'shape' => 'id', ], 'workerGroup' => [ 'shape' => 'string', ], 'hostname' => [ 'shape' => 'id', ], ], ], 'ReportTaskRunnerHeartbeatOutput' => [ 'type' => 'structure', 'required' => [ 'terminate', ], 'members' => [ 'terminate' => [ 'shape' => 'boolean', ], ], ], 'Selector' => [ 'type' => 'structure', 'members' => [ 'fieldName' => [ 'shape' => 'string', ], 'operator' => [ 'shape' => 'Operator', ], ], ], 'SelectorList' => [ 'type' => 'list', 'member' => [ 'shape' => 'Selector', ], ], 'SetStatusInput' => [ 'type' => 'structure', 'required' => [ 'pipelineId', 'objectIds', 'status', ], 'members' => [ 'pipelineId' => [ 'shape' => 'id', ], 'objectIds' => [ 'shape' => 'idList', ], 'status' => [ 'shape' => 'string', ], ], ], 'SetTaskStatusInput' => [ 'type' => 'structure', 'required' => [ 'taskId', 'taskStatus', ], 'members' => [ 'taskId' => [ 'shape' => 'taskId', ], 'taskStatus' => [ 'shape' => 'TaskStatus', ], 'errorId' => [ 'shape' => 'string', ], 'errorMessage' => [ 'shape' => 'errorMessage', ], 'errorStackTrace' => [ 'shape' => 'string', ], ], ], 'SetTaskStatusOutput' => [ 'type' => 'structure', 'members' => [], ], 'Tag' => [ 'type' => 'structure', 'required' => [ 'key', 'value', ], 'members' => [ 'key' => [ 'shape' => 'tagKey', ], 'value' => [ 'shape' => 'tagValue', ], ], ], 'TaskNotFoundException' => [ 'type' => 'structure', 'members' => [ 'message' => [ 'shape' => 'errorMessage', ], ], 'exception' => true, ], 'TaskObject' => [ 'type' => 'structure', 'members' => [ 'taskId' => [ 'shape' => 'taskId', ], 'pipelineId' => [ 'shape' => 'id', ], 'attemptId' => [ 'shape' => 'id', ], 'objects' => [ 'shape' => 'PipelineObjectMap', ], ], ], 'TaskStatus' => [ 'type' => 'string', 'enum' => [ 'FINISHED', 'FAILED', 'FALSE', ], ], 'ValidatePipelineDefinitionInput' => [ 'type' => 'structure', 'required' => [ 'pipelineId', 'pipelineObjects', ], 'members' => [ 'pipelineId' => [ 'shape' => 'id', ], 'pipelineObjects' => [ 'shape' => 'PipelineObjectList', ], 'parameterObjects' => [ 'shape' => 'ParameterObjectList', ], 'parameterValues' => [ 'shape' => 'ParameterValueList', ], ], ], 'ValidatePipelineDefinitionOutput' => [ 'type' => 'structure', 'required' => [ 'errored', ], 'members' => [ 'validationErrors' => [ 'shape' => 'ValidationErrors', ], 'validationWarnings' => [ 'shape' => 'ValidationWarnings', ], 'errored' => [ 'shape' => 'boolean', ], ], ], 'ValidationError' => [ 'type' => 'structure', 'members' => [ 'id' => [ 'shape' => 'id', ], 'errors' => [ 'shape' => 'validationMessages', ], ], ], 'ValidationErrors' => [ 'type' => 'list', 'member' => [ 'shape' => 'ValidationError', ], ], 'ValidationWarning' => [ 'type' => 'structure', 'members' => [ 'id' => [ 'shape' => 'id', ], 'warnings' => [ 'shape' => 'validationMessages', ], ], ], 'ValidationWarnings' => [ 'type' => 'list', 'member' => [ 'shape' => 'ValidationWarning', ], ], 'attributeNameString' => [ 'type' => 'string', 'min' => 1, 'max' => 256, 'pattern' => '[\\u0020-\\uD7FF\\uE000-\\uFFFD\\uD800\\uDC00-\\uDBFF\\uDFFF\\r\\n\\t]*', ], 'attributeValueString' => [ 'type' => 'string', 'min' => 0, 'max' => 10240, 'pattern' => '[\\u0020-\\uD7FF\\uE000-\\uFFFD\\uD800\\uDC00-\\uDBFF\\uDFFF\\r\\n\\t]*', ], 'boolean' => [ 'type' => 'boolean', ], 'cancelActive' => [ 'type' => 'boolean', ], 'errorMessage' => [ 'type' => 'string', ], 'fieldList' => [ 'type' => 'list', 'member' => [ 'shape' => 'Field', ], ], 'fieldNameString' => [ 'type' => 'string', 'min' => 1, 'max' => 256, 'pattern' => '[\\u0020-\\uD7FF\\uE000-\\uFFFD\\uD800\\uDC00-\\uDBFF\\uDFFF\\r\\n\\t]*', ], 'fieldStringValue' => [ 'type' => 'string', 'min' => 0, 'max' => 10240, 'pattern' => '[\\u0020-\\uD7FF\\uE000-\\uFFFD\\uD800\\uDC00-\\uDBFF\\uDFFF\\r\\n\\t]*', ], 'id' => [ 'type' => 'string', 'min' => 1, 'max' => 1024, 'pattern' => '[\\u0020-\\uD7FF\\uE000-\\uFFFD\\uD800\\uDC00-\\uDBFF\\uDFFF\\r\\n\\t]*', ], 'idList' => [ 'type' => 'list', 'member' => [ 'shape' => 'id', ], ], 'int' => [ 'type' => 'integer', ], 'longString' => [ 'type' => 'string', 'min' => 0, 'max' => 20971520, 'pattern' => '[\\u0020-\\uD7FF\\uE000-\\uFFFD\\uD800\\uDC00-\\uDBFF\\uDFFF\\r\\n\\t]*', ], 'pipelineList' => [ 'type' => 'list', 'member' => [ 'shape' => 'PipelineIdName', ], ], 'string' => [ 'type' => 'string', 'min' => 0, 'max' => 1024, 'pattern' => '[\\u0020-\\uD7FF\\uE000-\\uFFFD\\uD800\\uDC00-\\uDBFF\\uDFFF\\r\\n\\t]*', ], 'stringList' => [ 'type' => 'list', 'member' => [ 'shape' => 'string', ], ], 'tagKey' => [ 'type' => 'string', 'min' => 1, 'max' => 128, ], 'tagList' => [ 'type' => 'list', 'member' => [ 'shape' => 'Tag', ], 'min' => 0, 'max' => 10, ], 'tagValue' => [ 'type' => 'string', 'min' => 0, 'max' => 256, ], 'taskId' => [ 'type' => 'string', 'min' => 1, 'max' => 2048, 'pattern' => '[\\u0020-\\uD7FF\\uE000-\\uFFFD\\uD800\\uDC00-\\uDBFF\\uDFFF\\r\\n\\t]*', ], 'timestamp' => [ 'type' => 'timestamp', ], 'validationMessage' => [ 'type' => 'string', 'min' => 0, 'max' => 10000, 'pattern' => '[\\u0020-\\uD7FF\\uE000-\\uFFFD\\uD800\\uDC00-\\uDBFF\\uDFFF\\r\\n\\t]*', ], 'validationMessages' => [ 'type' => 'list', 'member' => [ 'shape' => 'validationMessage', ], ], ],];
