<?php
// This file was auto-generated from sdk-root/src/data/runtime.lex/2016-11-28/api-2.json
return [ 'version' => '2.0', 'metadata' => [ 'apiVersion' => '2016-11-28', 'endpointPrefix' => 'runtime.lex', 'jsonVersion' => '1.1', 'protocol' => 'rest-json', 'serviceFullName' => 'Amazon Lex Runtime Service', 'serviceId' => 'Lex Runtime Service', 'signatureVersion' => 'v4', 'signingName' => 'lex', 'uid' => 'runtime.lex-2016-11-28', ], 'operations' => [ 'DeleteSession' => [ 'name' => 'DeleteSession', 'http' => [ 'method' => 'DELETE', 'requestUri' => '/bot/{botName}/alias/{botAlias}/user/{userId}/session', ], 'input' => [ 'shape' => 'DeleteSessionRequest', ], 'output' => [ 'shape' => 'DeleteSessionResponse', ], 'errors' => [ [ 'shape' => 'NotFoundException', ], [ 'shape' => 'BadRequestException', ], [ 'shape' => 'LimitExceededException', ], [ 'shape' => 'InternalFailureException', ], [ 'shape' => 'ConflictException', ], ], ], 'GetSession' => [ 'name' => 'GetSession', 'http' => [ 'method' => 'GET', 'requestUri' => '/bot/{botName}/alias/{botAlias}/user/{userId}/session/', ], 'input' => [ 'shape' => 'GetSessionRequest', ], 'output' => [ 'shape' => 'GetSessionResponse', ], 'errors' => [ [ 'shape' => 'NotFoundException', ], [ 'shape' => 'BadRequestException', ], [ 'shape' => 'LimitExceededException', ], [ 'shape' => 'InternalFailureException', ], ], ], 'PostContent' => [ 'name' => 'PostContent', 'http' => [ 'method' => 'POST', 'requestUri' => '/bot/{botName}/alias/{botAlias}/user/{userId}/content', ], 'input' => [ 'shape' => 'PostContentRequest', ], 'output' => [ 'shape' => 'PostContentResponse', ], 'errors' => [ [ 'shape' => 'NotFoundException', ], [ 'shape' => 'BadRequestException', ], [ 'shape' => 'LimitExceededException', ], [ 'shape' => 'InternalFailureException', ], [ 'shape' => 'ConflictException', ], [ 'shape' => 'UnsupportedMediaTypeException', ], [ 'shape' => 'NotAcceptableException', ], [ 'shape' => 'RequestTimeoutException', ], [ 'shape' => 'DependencyFailedException', ], [ 'shape' => 'BadGatewayException', ], [ 'shape' => 'LoopDetectedException', ], ], 'authtype' => 'v4-unsigned-body', ], 'PostText' => [ 'name' => 'PostText', 'http' => [ 'method' => 'POST', 'requestUri' => '/bot/{botName}/alias/{botAlias}/user/{userId}/text', ], 'input' => [ 'shape' => 'PostTextRequest', ], 'output' => [ 'shape' => 'PostTextResponse', ], 'errors' => [ [ 'shape' => 'NotFoundException', ], [ 'shape' => 'BadRequestException', ], [ 'shape' => 'LimitExceededException', ], [ 'shape' => 'InternalFailureException', ], [ 'shape' => 'ConflictException', ], [ 'shape' => 'DependencyFailedException', ], [ 'shape' => 'BadGatewayException', ], [ 'shape' => 'LoopDetectedException', ], ], ], 'PutSession' => [ 'name' => 'PutSession', 'http' => [ 'method' => 'POST', 'requestUri' => '/bot/{botName}/alias/{botAlias}/user/{userId}/session', ], 'input' => [ 'shape' => 'PutSessionRequest', ], 'output' => [ 'shape' => 'PutSessionResponse', ], 'errors' => [ [ 'shape' => 'NotFoundException', ], [ 'shape' => 'BadRequestException', ], [ 'shape' => 'LimitExceededException', ], [ 'shape' => 'InternalFailureException', ], [ 'shape' => 'ConflictException', ], [ 'shape' => 'NotAcceptableException', ], [ 'shape' => 'DependencyFailedException', ], [ 'shape' => 'BadGatewayException', ], ], ], ], 'shapes' => [ 'Accept' => [ 'type' => 'string', ], 'ActiveContext' => [ 'type' => 'structure', 'required' => [ 'name', 'timeToLive', 'parameters', ], 'members' => [ 'name' => [ 'shape' => 'ActiveContextName', ], 'timeToLive' => [ 'shape' => 'ActiveContextTimeToLive', ], 'parameters' => [ 'shape' => 'ActiveContextParametersMap', ], ], ], 'ActiveContextName' => [ 'type' => 'string', 'max' => 100, 'min' => 1, 'pattern' => '^([A-Za-z]_?)+$', ], 'ActiveContextParametersMap' => [ 'type' => 'map', 'key' => [ 'shape' => 'ParameterName', ], 'value' => [ 'shape' => 'Text', ], 'max' => 10, 'min' => 0, ], 'ActiveContextTimeToLive' => [ 'type' => 'structure', 'members' => [ 'timeToLiveInSeconds' => [ 'shape' => 'ActiveContextTimeToLiveInSeconds', ], 'turnsToLive' => [ 'shape' => 'ActiveContextTurnsToLive', ], ], ], 'ActiveContextTimeToLiveInSeconds' => [ 'type' => 'integer', 'max' => 86400, 'min' => 5, ], 'ActiveContextTurnsToLive' => [ 'type' => 'integer', 'max' => 20, 'min' => 1, ], 'ActiveContextsList' => [ 'type' => 'list', 'member' => [ 'shape' => 'ActiveContext', ], 'max' => 20, 'min' => 0, 'sensitive' => true, ], 'ActiveContextsString' => [ 'type' => 'string', 'sensitive' => true, ], 'AttributesString' => [ 'type' => 'string', 'sensitive' => true, ], 'BadGatewayException' => [ 'type' => 'structure', 'members' => [ 'Message' => [ 'shape' => 'ErrorMessage', ], ], 'error' => [ 'httpStatusCode' => 502, ], 'exception' => true, ], 'BadRequestException' => [ 'type' => 'structure', 'members' => [ 'message' => [ 'shape' => 'String', ], ], 'error' => [ 'httpStatusCode' => 400, ], 'exception' => true, ], 'BlobStream' => [ 'type' => 'blob', 'streaming' => true, ], 'BotAlias' => [ 'type' => 'string', ], 'BotName' => [ 'type' => 'string', ], 'BotVersion' => [ 'type' => 'string', 'max' => 64, 'min' => 1, 'pattern' => '[0-9]+|\\$LATEST', ], 'Button' => [ 'type' => 'structure', 'required' => [ 'text', 'value', ], 'members' => [ 'text' => [ 'shape' => 'ButtonTextStringWithLength', ], 'value' => [ 'shape' => 'ButtonValueStringWithLength', ], ], ], 'ButtonTextStringWithLength' => [ 'type' => 'string', 'max' => 15, 'min' => 1, ], 'ButtonValueStringWithLength' => [ 'type' => 'string', 'max' => 1000, 'min' => 1, ], 'ConfirmationStatus' => [ 'type' => 'string', 'enum' => [ 'None', 'Confirmed', 'Denied', ], ], 'ConflictException' => [ 'type' => 'structure', 'members' => [ 'message' => [ 'shape' => 'String', ], ], 'error' => [ 'httpStatusCode' => 409, ], 'exception' => true, ], 'ContentType' => [ 'type' => 'string', 'enum' => [ 'application/vnd.amazonaws.card.generic', ], ], 'DeleteSessionRequest' => [ 'type' => 'structure', 'required' => [ 'botName', 'botAlias', 'userId', ], 'members' => [ 'botName' => [ 'shape' => 'BotName', 'location' => 'uri', 'locationName' => 'botName', ], 'botAlias' => [ 'shape' => 'BotAlias', 'location' => 'uri', 'locationName' => 'botAlias', ], 'userId' => [ 'shape' => 'UserId', 'location' => 'uri', 'locationName' => 'userId', ], ], ], 'DeleteSessionResponse' => [ 'type' => 'structure', 'members' => [ 'botName' => [ 'shape' => 'BotName', ], 'botAlias' => [ 'shape' => 'BotAlias', ], 'userId' => [ 'shape' => 'UserId', ], 'sessionId' => [ 'shape' => 'String', ], ], ], 'DependencyFailedException' => [ 'type' => 'structure', 'members' => [ 'Message' => [ 'shape' => 'ErrorMessage', ], ], 'error' => [ 'httpStatusCode' => 424, ], 'exception' => true, ], 'DialogAction' => [ 'type' => 'structure', 'required' => [ 'type', ], 'members' => [ 'type' => [ 'shape' => 'DialogActionType', ], 'intentName' => [ 'shape' => 'IntentName', ], 'slots' => [ 'shape' => 'StringMap', ], 'slotToElicit' => [ 'shape' => 'String', ], 'fulfillmentState' => [ 'shape' => 'FulfillmentState', ], 'message' => [ 'shape' => 'Text', ], 'messageFormat' => [ 'shape' => 'MessageFormatType', ], ], ], 'DialogActionType' => [ 'type' => 'string', 'enum' => [ 'ElicitIntent', 'ConfirmIntent', 'ElicitSlot', 'Close', 'Delegate', ], ], 'DialogState' => [ 'type' => 'string', 'enum' => [ 'ElicitIntent', 'ConfirmIntent', 'ElicitSlot', 'Fulfilled', 'ReadyForFulfillment', 'Failed', ], ], 'Double' => [ 'type' => 'double', ], 'ErrorMessage' => [ 'type' => 'string', ], 'FulfillmentState' => [ 'type' => 'string', 'enum' => [ 'Fulfilled', 'Failed', 'ReadyForFulfillment', ], ], 'GenericAttachment' => [ 'type' => 'structure', 'members' => [ 'title' => [ 'shape' => 'StringWithLength', ], 'subTitle' => [ 'shape' => 'StringWithLength', ], 'attachmentLinkUrl' => [ 'shape' => 'StringUrlWithLength', ], 'imageUrl' => [ 'shape' => 'StringUrlWithLength', ], 'buttons' => [ 'shape' => 'listOfButtons', ], ], ], 'GetSessionRequest' => [ 'type' => 'structure', 'required' => [ 'botName', 'botAlias', 'userId', ], 'members' => [ 'botName' => [ 'shape' => 'BotName', 'location' => 'uri', 'locationName' => 'botName', ], 'botAlias' => [ 'shape' => 'BotAlias', 'location' => 'uri', 'locationName' => 'botAlias', ], 'userId' => [ 'shape' => 'UserId', 'location' => 'uri', 'locationName' => 'userId', ], 'checkpointLabelFilter' => [ 'shape' => 'IntentSummaryCheckpointLabel', 'location' => 'querystring', 'locationName' => 'checkpointLabelFilter', ], ], ], 'GetSessionResponse' => [ 'type' => 'structure', 'members' => [ 'recentIntentSummaryView' => [ 'shape' => 'IntentSummaryList', ], 'sessionAttributes' => [ 'shape' => 'StringMap', ], 'sessionId' => [ 'shape' => 'String', ], 'dialogAction' => [ 'shape' => 'DialogAction', ], 'activeContexts' => [ 'shape' => 'ActiveContextsList', ], ], ], 'HttpContentType' => [ 'type' => 'string', ], 'IntentConfidence' => [ 'type' => 'structure', 'members' => [ 'score' => [ 'shape' => 'Double', ], ], ], 'IntentList' => [ 'type' => 'list', 'member' => [ 'shape' => 'PredictedIntent', ], 'max' => 4, ], 'IntentName' => [ 'type' => 'string', ], 'IntentSummary' => [ 'type' => 'structure', 'required' => [ 'dialogActionType', ], 'members' => [ 'intentName' => [ 'shape' => 'IntentName', ], 'checkpointLabel' => [ 'shape' => 'IntentSummaryCheckpointLabel', ], 'slots' => [ 'shape' => 'StringMap', ], 'confirmationStatus' => [ 'shape' => 'ConfirmationStatus', ], 'dialogActionType' => [ 'shape' => 'DialogActionType', ], 'fulfillmentState' => [ 'shape' => 'FulfillmentState', ], 'slotToElicit' => [ 'shape' => 'String', ], ], ], 'IntentSummaryCheckpointLabel' => [ 'type' => 'string', 'max' => 255, 'min' => 1, 'pattern' => '[a-zA-Z0-9-]+', ], 'IntentSummaryList' => [ 'type' => 'list', 'member' => [ 'shape' => 'IntentSummary', ], 'max' => 3, 'min' => 0, ], 'InternalFailureException' => [ 'type' => 'structure', 'members' => [ 'message' => [ 'shape' => 'String', ], ], 'error' => [ 'httpStatusCode' => 500, ], 'exception' => true, 'fault' => true, ], 'LimitExceededException' => [ 'type' => 'structure', 'members' => [ 'retryAfterSeconds' => [ 'shape' => 'String', 'location' => 'header', 'locationName' => 'Retry-After', ], 'message' => [ 'shape' => 'String', ], ], 'error' => [ 'httpStatusCode' => 429, ], 'exception' => true, ], 'LoopDetectedException' => [ 'type' => 'structure', 'members' => [ 'Message' => [ 'shape' => 'ErrorMessage', ], ], 'error' => [ 'httpStatusCode' => 508, ], 'exception' => true, ], 'MessageFormatType' => [ 'type' => 'string', 'enum' => [ 'PlainText', 'CustomPayload', 'SSML', 'Composite', ], ], 'NotAcceptableException' => [ 'type' => 'structure', 'members' => [ 'message' => [ 'shape' => 'String', ], ], 'error' => [ 'httpStatusCode' => 406, ], 'exception' => true, ], 'NotFoundException' => [ 'type' => 'structure', 'members' => [ 'message' => [ 'shape' => 'String', ], ], 'error' => [ 'httpStatusCode' => 404, ], 'exception' => true, ], 'ParameterName' => [ 'type' => 'string', 'max' => 100, 'min' => 1, ], 'PostContentRequest' => [ 'type' => 'structure', 'required' => [ 'botName', 'botAlias', 'userId', 'contentType', 'inputStream', ], 'members' => [ 'botName' => [ 'shape' => 'BotName', 'location' => 'uri', 'locationName' => 'botName', ], 'botAlias' => [ 'shape' => 'BotAlias', 'location' => 'uri', 'locationName' => 'botAlias', ], 'userId' => [ 'shape' => 'UserId', 'location' => 'uri', 'locationName' => 'userId', ], 'sessionAttributes' => [ 'shape' => 'AttributesString', 'jsonvalue' => true, 'location' => 'header', 'locationName' => 'x-amz-lex-session-attributes', ], 'requestAttributes' => [ 'shape' => 'AttributesString', 'jsonvalue' => true, 'location' => 'header', 'locationName' => 'x-amz-lex-request-attributes', ], 'contentType' => [ 'shape' => 'HttpContentType', 'location' => 'header', 'locationName' => 'Content-Type', ], 'accept' => [ 'shape' => 'Accept', 'location' => 'header', 'locationName' => 'Accept', ], 'inputStream' => [ 'shape' => 'BlobStream', ], 'activeContexts' => [ 'shape' => 'ActiveContextsString', 'jsonvalue' => true, 'location' => 'header', 'locationName' => 'x-amz-lex-active-contexts', ], ], 'payload' => 'inputStream', ], 'PostContentResponse' => [ 'type' => 'structure', 'members' => [ 'contentType' => [ 'shape' => 'HttpContentType', 'location' => 'header', 'locationName' => 'Content-Type', ], 'intentName' => [ 'shape' => 'IntentName', 'location' => 'header', 'locationName' => 'x-amz-lex-intent-name', ], 'nluIntentConfidence' => [ 'shape' => 'String', 'jsonvalue' => true, 'location' => 'header', 'locationName' => 'x-amz-lex-nlu-intent-confidence', ], 'alternativeIntents' => [ 'shape' => 'String', 'jsonvalue' => true, 'location' => 'header', 'locationName' => 'x-amz-lex-alternative-intents', ], 'slots' => [ 'shape' => 'String', 'jsonvalue' => true, 'location' => 'header', 'locationName' => 'x-amz-lex-slots', ], 'sessionAttributes' => [ 'shape' => 'String', 'jsonvalue' => true, 'location' => 'header', 'locationName' => 'x-amz-lex-session-attributes', ], 'sentimentResponse' => [ 'shape' => 'String', 'location' => 'header', 'locationName' => 'x-amz-lex-sentiment', ], 'message' => [ 'shape' => 'Text', 'deprecated' => true, 'deprecatedMessage' => 'The message field is deprecated, use the encodedMessage field instead. The message field is available only in the de-DE, en-AU, en-GB, en-US, es-419, es-ES, es-US, fr-CA, fr-FR and it-IT locales.', 'location' => 'header', 'locationName' => 'x-amz-lex-message', ], 'encodedMessage' => [ 'shape' => 'SensitiveString', 'location' => 'header', 'locationName' => 'x-amz-lex-encoded-message', ], 'messageFormat' => [ 'shape' => 'MessageFormatType', 'location' => 'header', 'locationName' => 'x-amz-lex-message-format', ], 'dialogState' => [ 'shape' => 'DialogState', 'location' => 'header', 'locationName' => 'x-amz-lex-dialog-state', ], 'slotToElicit' => [ 'shape' => 'String', 'location' => 'header', 'locationName' => 'x-amz-lex-slot-to-elicit', ], 'inputTranscript' => [ 'shape' => 'String', 'deprecated' => true, 'deprecatedMessage' => 'The inputTranscript field is deprecated, use the encodedInputTranscript field instead. The inputTranscript field is available only in the de-DE, en-AU, en-GB, en-US, es-419, es-ES, es-US, fr-CA, fr-FR and it-IT locales.', 'location' => 'header', 'locationName' => 'x-amz-lex-input-transcript', ], 'encodedInputTranscript' => [ 'shape' => 'SensitiveStringUnbounded', 'location' => 'header', 'locationName' => 'x-amz-lex-encoded-input-transcript', ], 'audioStream' => [ 'shape' => 'BlobStream', ], 'botVersion' => [ 'shape' => 'BotVersion', 'location' => 'header', 'locationName' => 'x-amz-lex-bot-version', ], 'sessionId' => [ 'shape' => 'String', 'location' => 'header', 'locationName' => 'x-amz-lex-session-id', ], 'activeContexts' => [ 'shape' => 'ActiveContextsString', 'jsonvalue' => true, 'location' => 'header', 'locationName' => 'x-amz-lex-active-contexts', ], ], 'payload' => 'audioStream', ], 'PostTextRequest' => [ 'type' => 'structure', 'required' => [ 'botName', 'botAlias', 'userId', 'inputText', ], 'members' => [ 'botName' => [ 'shape' => 'BotName', 'location' => 'uri', 'locationName' => 'botName', ], 'botAlias' => [ 'shape' => 'BotAlias', 'location' => 'uri', 'locationName' => 'botAlias', ], 'userId' => [ 'shape' => 'UserId', 'location' => 'uri', 'locationName' => 'userId', ], 'sessionAttributes' => [ 'shape' => 'StringMap', ], 'requestAttributes' => [ 'shape' => 'StringMap', ], 'inputText' => [ 'shape' => 'Text', ], 'activeContexts' => [ 'shape' => 'ActiveContextsList', ], ], ], 'PostTextResponse' => [ 'type' => 'structure', 'members' => [ 'intentName' => [ 'shape' => 'IntentName', ], 'nluIntentConfidence' => [ 'shape' => 'IntentConfidence', ], 'alternativeIntents' => [ 'shape' => 'IntentList', ], 'slots' => [ 'shape' => 'StringMap', ], 'sessionAttributes' => [ 'shape' => 'StringMap', ], 'message' => [ 'shape' => 'Text', ], 'sentimentResponse' => [ 'shape' => 'SentimentResponse', ], 'messageFormat' => [ 'shape' => 'MessageFormatType', ], 'dialogState' => [ 'shape' => 'DialogState', ], 'slotToElicit' => [ 'shape' => 'String', ], 'responseCard' => [ 'shape' => 'ResponseCard', ], 'sessionId' => [ 'shape' => 'String', ], 'botVersion' => [ 'shape' => 'BotVersion', ], 'activeContexts' => [ 'shape' => 'ActiveContextsList', ], ], ], 'PredictedIntent' => [ 'type' => 'structure', 'members' => [ 'intentName' => [ 'shape' => 'IntentName', ], 'nluIntentConfidence' => [ 'shape' => 'IntentConfidence', ], 'slots' => [ 'shape' => 'StringMap', ], ], ], 'PutSessionRequest' => [ 'type' => 'structure', 'required' => [ 'botName', 'botAlias', 'userId', ], 'members' => [ 'botName' => [ 'shape' => 'BotName', 'location' => 'uri', 'locationName' => 'botName', ], 'botAlias' => [ 'shape' => 'BotAlias', 'location' => 'uri', 'locationName' => 'botAlias', ], 'userId' => [ 'shape' => 'UserId', 'location' => 'uri', 'locationName' => 'userId', ], 'sessionAttributes' => [ 'shape' => 'StringMap', ], 'dialogAction' => [ 'shape' => 'DialogAction', ], 'recentIntentSummaryView' => [ 'shape' => 'IntentSummaryList', ], 'accept' => [ 'shape' => 'Accept', 'location' => 'header', 'locationName' => 'Accept', ], 'activeContexts' => [ 'shape' => 'ActiveContextsList', ], ], ], 'PutSessionResponse' => [ 'type' => 'structure', 'members' => [ 'contentType' => [ 'shape' => 'HttpContentType', 'location' => 'header', 'locationName' => 'Content-Type', ], 'intentName' => [ 'shape' => 'IntentName', 'location' => 'header', 'locationName' => 'x-amz-lex-intent-name', ], 'slots' => [ 'shape' => 'String', 'jsonvalue' => true, 'location' => 'header', 'locationName' => 'x-amz-lex-slots', ], 'sessionAttributes' => [ 'shape' => 'String', 'jsonvalue' => true, 'location' => 'header', 'locationName' => 'x-amz-lex-session-attributes', ], 'message' => [ 'shape' => 'Text', 'deprecated' => true, 'deprecatedMessage' => 'The message field is deprecated, use the encodedMessage field instead. The message field is available only in the de-DE, en-AU, en-GB, en-US, es-419, es-ES, es-US, fr-CA, fr-FR and it-IT locales.', 'location' => 'header', 'locationName' => 'x-amz-lex-message', ], 'encodedMessage' => [ 'shape' => 'SensitiveString', 'location' => 'header', 'locationName' => 'x-amz-lex-encoded-message', ], 'messageFormat' => [ 'shape' => 'MessageFormatType', 'location' => 'header', 'locationName' => 'x-amz-lex-message-format', ], 'dialogState' => [ 'shape' => 'DialogState', 'location' => 'header', 'locationName' => 'x-amz-lex-dialog-state', ], 'slotToElicit' => [ 'shape' => 'String', 'location' => 'header', 'locationName' => 'x-amz-lex-slot-to-elicit', ], 'audioStream' => [ 'shape' => 'BlobStream', ], 'sessionId' => [ 'shape' => 'String', 'location' => 'header', 'locationName' => 'x-amz-lex-session-id', ], 'activeContexts' => [ 'shape' => 'ActiveContextsString', 'jsonvalue' => true, 'location' => 'header', 'locationName' => 'x-amz-lex-active-contexts', ], ], 'payload' => 'audioStream', ], 'RequestTimeoutException' => [ 'type' => 'structure', 'members' => [ 'message' => [ 'shape' => 'String', ], ], 'error' => [ 'httpStatusCode' => 408, ], 'exception' => true, ], 'ResponseCard' => [ 'type' => 'structure', 'members' => [ 'version' => [ 'shape' => 'String', ], 'contentType' => [ 'shape' => 'ContentType', ], 'genericAttachments' => [ 'shape' => 'genericAttachmentList', ], ], ], 'SensitiveString' => [ 'type' => 'string', 'max' => 1366, 'min' => 1, 'sensitive' => true, ], 'SensitiveStringUnbounded' => [ 'type' => 'string', 'sensitive' => true, ], 'SentimentLabel' => [ 'type' => 'string', ], 'SentimentResponse' => [ 'type' => 'structure', 'members' => [ 'sentimentLabel' => [ 'shape' => 'SentimentLabel', ], 'sentimentScore' => [ 'shape' => 'SentimentScore', ], ], ], 'SentimentScore' => [ 'type' => 'string', ], 'String' => [ 'type' => 'string', ], 'StringMap' => [ 'type' => 'map', 'key' => [ 'shape' => 'String', ], 'value' => [ 'shape' => 'String', ], 'sensitive' => true, ], 'StringUrlWithLength' => [ 'type' => 'string', 'max' => 2048, 'min' => 1, ], 'StringWithLength' => [ 'type' => 'string', 'max' => 80, 'min' => 1, ], 'Text' => [ 'type' => 'string', 'max' => 1024, 'min' => 1, 'sensitive' => true, ], 'UnsupportedMediaTypeException' => [ 'type' => 'structure', 'members' => [ 'message' => [ 'shape' => 'String', ], ], 'error' => [ 'httpStatusCode' => 415, ], 'exception' => true, ], 'UserId' => [ 'type' => 'string', 'max' => 100, 'min' => 2, 'pattern' => '[0-9a-zA-Z._:-]+', ], 'genericAttachmentList' => [ 'type' => 'list', 'member' => [ 'shape' => 'GenericAttachment', ], 'max' => 10, 'min' => 0, ], 'listOfButtons' => [ 'type' => 'list', 'member' => [ 'shape' => 'Button', ], 'max' => 5, 'min' => 0, ], ],];
