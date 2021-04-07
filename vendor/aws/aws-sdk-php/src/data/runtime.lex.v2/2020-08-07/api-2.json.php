<?php
// This file was auto-generated from sdk-root/src/data/runtime.lex.v2/2020-08-07/api-2.json
return [ 'version' => '2.0', 'metadata' => [ 'apiVersion' => '2020-08-07', 'endpointPrefix' => 'runtime-v2-lex', 'jsonVersion' => '1.1', 'protocol' => 'rest-json', 'protocolSettings' => [ 'h2' => 'eventstream', ], 'serviceAbbreviation' => 'Lex Runtime V2', 'serviceFullName' => 'Amazon Lex Runtime V2', 'serviceId' => 'Lex Runtime V2', 'signatureVersion' => 'v4', 'signingName' => 'lex', 'uid' => 'runtime.lex.v2-2020-08-07', ], 'operations' => [ 'DeleteSession' => [ 'name' => 'DeleteSession', 'http' => [ 'method' => 'DELETE', 'requestUri' => '/bots/{botId}/botAliases/{botAliasId}/botLocales/{localeId}/sessions/{sessionId}', ], 'input' => [ 'shape' => 'DeleteSessionRequest', ], 'output' => [ 'shape' => 'DeleteSessionResponse', ], 'errors' => [ [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'ResourceNotFoundException', ], [ 'shape' => 'ValidationException', ], [ 'shape' => 'ThrottlingException', ], [ 'shape' => 'InternalServerException', ], [ 'shape' => 'ConflictException', ], ], ], 'GetSession' => [ 'name' => 'GetSession', 'http' => [ 'method' => 'GET', 'requestUri' => '/bots/{botId}/botAliases/{botAliasId}/botLocales/{localeId}/sessions/{sessionId}', ], 'input' => [ 'shape' => 'GetSessionRequest', ], 'output' => [ 'shape' => 'GetSessionResponse', ], 'errors' => [ [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'ResourceNotFoundException', ], [ 'shape' => 'ValidationException', ], [ 'shape' => 'ThrottlingException', ], [ 'shape' => 'InternalServerException', ], ], ], 'PutSession' => [ 'name' => 'PutSession', 'http' => [ 'method' => 'POST', 'requestUri' => '/bots/{botId}/botAliases/{botAliasId}/botLocales/{localeId}/sessions/{sessionId}', ], 'input' => [ 'shape' => 'PutSessionRequest', ], 'output' => [ 'shape' => 'PutSessionResponse', ], 'errors' => [ [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'ResourceNotFoundException', ], [ 'shape' => 'ValidationException', ], [ 'shape' => 'ThrottlingException', ], [ 'shape' => 'InternalServerException', ], [ 'shape' => 'ConflictException', ], [ 'shape' => 'DependencyFailedException', ], [ 'shape' => 'BadGatewayException', ], ], ], 'RecognizeText' => [ 'name' => 'RecognizeText', 'http' => [ 'method' => 'POST', 'requestUri' => '/bots/{botId}/botAliases/{botAliasId}/botLocales/{localeId}/sessions/{sessionId}/text', ], 'input' => [ 'shape' => 'RecognizeTextRequest', ], 'output' => [ 'shape' => 'RecognizeTextResponse', ], 'errors' => [ [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'ResourceNotFoundException', ], [ 'shape' => 'ValidationException', ], [ 'shape' => 'ThrottlingException', ], [ 'shape' => 'InternalServerException', ], [ 'shape' => 'ConflictException', ], [ 'shape' => 'DependencyFailedException', ], [ 'shape' => 'BadGatewayException', ], ], ], 'RecognizeUtterance' => [ 'name' => 'RecognizeUtterance', 'http' => [ 'method' => 'POST', 'requestUri' => '/bots/{botId}/botAliases/{botAliasId}/botLocales/{localeId}/sessions/{sessionId}/utterance', ], 'input' => [ 'shape' => 'RecognizeUtteranceRequest', ], 'output' => [ 'shape' => 'RecognizeUtteranceResponse', ], 'errors' => [ [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'ResourceNotFoundException', ], [ 'shape' => 'ValidationException', ], [ 'shape' => 'ThrottlingException', ], [ 'shape' => 'InternalServerException', ], [ 'shape' => 'ConflictException', ], [ 'shape' => 'DependencyFailedException', ], [ 'shape' => 'BadGatewayException', ], ], 'authtype' => 'v4-unsigned-body', ], ], 'shapes' => [ 'AccessDeniedException' => [ 'type' => 'structure', 'required' => [ 'message', ], 'members' => [ 'message' => [ 'shape' => 'String', ], ], 'error' => [ 'httpStatusCode' => 403, ], 'exception' => true, ], 'ActiveContext' => [ 'type' => 'structure', 'required' => [ 'name', 'timeToLive', ], 'members' => [ 'name' => [ 'shape' => 'ActiveContextName', ], 'timeToLive' => [ 'shape' => 'ActiveContextTimeToLive', ], 'contextAttributes' => [ 'shape' => 'ActiveContextParametersMap', ], ], ], 'ActiveContextName' => [ 'type' => 'string', 'max' => 100, 'min' => 1, 'pattern' => '^([A-Za-z]_?)+$', ], 'ActiveContextParametersMap' => [ 'type' => 'map', 'key' => [ 'shape' => 'ParameterName', ], 'value' => [ 'shape' => 'Text', ], 'max' => 10, 'min' => 0, ], 'ActiveContextTimeToLive' => [ 'type' => 'structure', 'required' => [ 'timeToLiveInSeconds', 'turnsToLive', ], 'members' => [ 'timeToLiveInSeconds' => [ 'shape' => 'ActiveContextTimeToLiveInSeconds', ], 'turnsToLive' => [ 'shape' => 'ActiveContextTurnsToLive', ], ], ], 'ActiveContextTimeToLiveInSeconds' => [ 'type' => 'integer', 'max' => 86400, 'min' => 5, ], 'ActiveContextTurnsToLive' => [ 'type' => 'integer', 'max' => 20, 'min' => 1, ], 'ActiveContextsList' => [ 'type' => 'list', 'member' => [ 'shape' => 'ActiveContext', ], 'max' => 20, 'min' => 0, ], 'AttachmentTitle' => [ 'type' => 'string', 'max' => 250, 'min' => 1, ], 'AttachmentUrl' => [ 'type' => 'string', 'max' => 250, 'min' => 1, ], 'AudioChunk' => [ 'type' => 'blob', ], 'AudioInputEvent' => [ 'type' => 'structure', 'required' => [ 'contentType', ], 'members' => [ 'audioChunk' => [ 'shape' => 'AudioChunk', ], 'contentType' => [ 'shape' => 'NonEmptyString', ], 'eventId' => [ 'shape' => 'EventId', ], 'clientTimestampMillis' => [ 'shape' => 'EpochMillis', ], ], 'event' => true, ], 'AudioResponseEvent' => [ 'type' => 'structure', 'members' => [ 'audioChunk' => [ 'shape' => 'AudioChunk', ], 'contentType' => [ 'shape' => 'NonEmptyString', ], 'eventId' => [ 'shape' => 'EventId', ], ], 'event' => true, ], 'BadGatewayException' => [ 'type' => 'structure', 'required' => [ 'message', ], 'members' => [ 'message' => [ 'shape' => 'String', ], ], 'error' => [ 'httpStatusCode' => 502, ], 'exception' => true, ], 'BlobStream' => [ 'type' => 'blob', 'streaming' => true, ], 'Boolean' => [ 'type' => 'boolean', ], 'BotAliasIdentifier' => [ 'type' => 'string', ], 'BotIdentifier' => [ 'type' => 'string', 'max' => 10, 'min' => 10, 'pattern' => '^[0-9a-zA-Z]+$', ], 'Button' => [ 'type' => 'structure', 'required' => [ 'text', 'value', ], 'members' => [ 'text' => [ 'shape' => 'ButtonText', ], 'value' => [ 'shape' => 'ButtonValue', ], ], ], 'ButtonText' => [ 'type' => 'string', 'max' => 50, 'min' => 1, ], 'ButtonValue' => [ 'type' => 'string', 'max' => 50, 'min' => 1, ], 'ButtonsList' => [ 'type' => 'list', 'member' => [ 'shape' => 'Button', ], 'max' => 5, 'min' => 0, ], 'ConfidenceScore' => [ 'type' => 'structure', 'members' => [ 'score' => [ 'shape' => 'Double', ], ], ], 'ConfigurationEvent' => [ 'type' => 'structure', 'required' => [ 'responseContentType', ], 'members' => [ 'requestAttributes' => [ 'shape' => 'StringMap', ], 'responseContentType' => [ 'shape' => 'NonEmptyString', ], 'sessionState' => [ 'shape' => 'SessionState', ], 'welcomeMessages' => [ 'shape' => 'Messages', ], 'disablePlayback' => [ 'shape' => 'Boolean', ], 'eventId' => [ 'shape' => 'EventId', ], 'clientTimestampMillis' => [ 'shape' => 'EpochMillis', ], ], 'event' => true, ], 'ConfirmationState' => [ 'type' => 'string', 'enum' => [ 'Confirmed', 'Denied', 'None', ], ], 'ConflictException' => [ 'type' => 'structure', 'required' => [ 'message', ], 'members' => [ 'message' => [ 'shape' => 'String', ], ], 'error' => [ 'httpStatusCode' => 409, ], 'exception' => true, ], 'ConversationMode' => [ 'type' => 'string', 'enum' => [ 'AUDIO', 'TEXT', ], ], 'DTMFInputEvent' => [ 'type' => 'structure', 'required' => [ 'inputCharacter', ], 'members' => [ 'inputCharacter' => [ 'shape' => 'DTMFRegex', ], 'eventId' => [ 'shape' => 'EventId', ], 'clientTimestampMillis' => [ 'shape' => 'EpochMillis', ], ], 'event' => true, ], 'DTMFRegex' => [ 'type' => 'string', 'max' => 1, 'min' => 1, 'pattern' => '^[A-D0-9#*]{1}$', 'sensitive' => true, ], 'DeleteSessionRequest' => [ 'type' => 'structure', 'required' => [ 'botId', 'botAliasId', 'sessionId', 'localeId', ], 'members' => [ 'botId' => [ 'shape' => 'BotIdentifier', 'location' => 'uri', 'locationName' => 'botId', ], 'botAliasId' => [ 'shape' => 'BotAliasIdentifier', 'location' => 'uri', 'locationName' => 'botAliasId', ], 'localeId' => [ 'shape' => 'LocaleId', 'location' => 'uri', 'locationName' => 'localeId', ], 'sessionId' => [ 'shape' => 'SessionId', 'location' => 'uri', 'locationName' => 'sessionId', ], ], ], 'DeleteSessionResponse' => [ 'type' => 'structure', 'members' => [ 'botId' => [ 'shape' => 'BotIdentifier', ], 'botAliasId' => [ 'shape' => 'BotAliasIdentifier', ], 'localeId' => [ 'shape' => 'LocaleId', ], 'sessionId' => [ 'shape' => 'SessionId', ], ], ], 'DependencyFailedException' => [ 'type' => 'structure', 'required' => [ 'message', ], 'members' => [ 'message' => [ 'shape' => 'String', ], ], 'error' => [ 'httpStatusCode' => 424, ], 'exception' => true, ], 'DialogAction' => [ 'type' => 'structure', 'required' => [ 'type', ], 'members' => [ 'type' => [ 'shape' => 'DialogActionType', ], 'slotToElicit' => [ 'shape' => 'NonEmptyString', ], ], ], 'DialogActionType' => [ 'type' => 'string', 'enum' => [ 'Close', 'ConfirmIntent', 'Delegate', 'ElicitIntent', 'ElicitSlot', ], ], 'DisconnectionEvent' => [ 'type' => 'structure', 'members' => [ 'eventId' => [ 'shape' => 'EventId', ], 'clientTimestampMillis' => [ 'shape' => 'EpochMillis', ], ], 'event' => true, ], 'Double' => [ 'type' => 'double', ], 'EpochMillis' => [ 'type' => 'long', ], 'EventId' => [ 'type' => 'string', 'max' => 100, 'min' => 2, 'pattern' => '[0-9a-zA-Z._:-]+', ], 'GetSessionRequest' => [ 'type' => 'structure', 'required' => [ 'botId', 'botAliasId', 'localeId', 'sessionId', ], 'members' => [ 'botId' => [ 'shape' => 'BotIdentifier', 'location' => 'uri', 'locationName' => 'botId', ], 'botAliasId' => [ 'shape' => 'BotAliasIdentifier', 'location' => 'uri', 'locationName' => 'botAliasId', ], 'localeId' => [ 'shape' => 'LocaleId', 'location' => 'uri', 'locationName' => 'localeId', ], 'sessionId' => [ 'shape' => 'SessionId', 'location' => 'uri', 'locationName' => 'sessionId', ], ], ], 'GetSessionResponse' => [ 'type' => 'structure', 'members' => [ 'sessionId' => [ 'shape' => 'NonEmptyString', ], 'messages' => [ 'shape' => 'Messages', ], 'interpretations' => [ 'shape' => 'Interpretations', ], 'sessionState' => [ 'shape' => 'SessionState', ], ], ], 'HeartbeatEvent' => [ 'type' => 'structure', 'members' => [ 'eventId' => [ 'shape' => 'EventId', ], ], 'event' => true, ], 'ImageResponseCard' => [ 'type' => 'structure', 'required' => [ 'title', ], 'members' => [ 'title' => [ 'shape' => 'AttachmentTitle', ], 'subtitle' => [ 'shape' => 'AttachmentTitle', ], 'imageUrl' => [ 'shape' => 'AttachmentUrl', ], 'buttons' => [ 'shape' => 'ButtonsList', ], ], ], 'InputMode' => [ 'type' => 'string', 'enum' => [ 'Text', 'Speech', 'DTMF', ], ], 'Intent' => [ 'type' => 'structure', 'required' => [ 'name', ], 'members' => [ 'name' => [ 'shape' => 'NonEmptyString', ], 'slots' => [ 'shape' => 'Slots', ], 'state' => [ 'shape' => 'IntentState', ], 'confirmationState' => [ 'shape' => 'ConfirmationState', ], ], ], 'IntentResultEvent' => [ 'type' => 'structure', 'members' => [ 'inputMode' => [ 'shape' => 'InputMode', ], 'interpretations' => [ 'shape' => 'Interpretations', ], 'sessionState' => [ 'shape' => 'SessionState', ], 'requestAttributes' => [ 'shape' => 'StringMap', ], 'sessionId' => [ 'shape' => 'SessionId', ], 'eventId' => [ 'shape' => 'EventId', ], ], 'event' => true, ], 'IntentState' => [ 'type' => 'string', 'enum' => [ 'Failed', 'Fulfilled', 'InProgress', 'ReadyForFulfillment', 'Waiting', ], ], 'InternalServerException' => [ 'type' => 'structure', 'required' => [ 'message', ], 'members' => [ 'message' => [ 'shape' => 'String', ], ], 'error' => [ 'httpStatusCode' => 500, ], 'exception' => true, 'fault' => true, ], 'Interpretation' => [ 'type' => 'structure', 'members' => [ 'nluConfidence' => [ 'shape' => 'ConfidenceScore', ], 'sentimentResponse' => [ 'shape' => 'SentimentResponse', ], 'intent' => [ 'shape' => 'Intent', ], ], ], 'Interpretations' => [ 'type' => 'list', 'member' => [ 'shape' => 'Interpretation', ], 'max' => 5, ], 'LocaleId' => [ 'type' => 'string', 'min' => 1, ], 'Message' => [ 'type' => 'structure', 'members' => [ 'content' => [ 'shape' => 'Text', ], 'contentType' => [ 'shape' => 'MessageContentType', ], 'imageResponseCard' => [ 'shape' => 'ImageResponseCard', ], ], ], 'MessageContentType' => [ 'type' => 'string', 'enum' => [ 'CustomPayload', 'ImageResponseCard', 'PlainText', 'SSML', ], ], 'Messages' => [ 'type' => 'list', 'member' => [ 'shape' => 'Message', ], 'max' => 10, ], 'NonEmptyString' => [ 'type' => 'string', 'min' => 1, ], 'ParameterName' => [ 'type' => 'string', 'max' => 100, 'min' => 1, ], 'PlaybackCompletionEvent' => [ 'type' => 'structure', 'members' => [ 'eventId' => [ 'shape' => 'EventId', ], 'clientTimestampMillis' => [ 'shape' => 'EpochMillis', ], ], 'event' => true, ], 'PlaybackInterruptionEvent' => [ 'type' => 'structure', 'members' => [ 'eventReason' => [ 'shape' => 'PlaybackInterruptionReason', ], 'causedByEventId' => [ 'shape' => 'EventId', ], 'eventId' => [ 'shape' => 'EventId', ], ], 'event' => true, ], 'PlaybackInterruptionReason' => [ 'type' => 'string', 'enum' => [ 'DTMF_START_DETECTED', 'TEXT_DETECTED', 'VOICE_START_DETECTED', ], ], 'PutSessionRequest' => [ 'type' => 'structure', 'required' => [ 'botId', 'botAliasId', 'localeId', 'sessionState', 'sessionId', ], 'members' => [ 'botId' => [ 'shape' => 'BotIdentifier', 'location' => 'uri', 'locationName' => 'botId', ], 'botAliasId' => [ 'shape' => 'BotAliasIdentifier', 'location' => 'uri', 'locationName' => 'botAliasId', ], 'localeId' => [ 'shape' => 'LocaleId', 'location' => 'uri', 'locationName' => 'localeId', ], 'sessionId' => [ 'shape' => 'SessionId', 'location' => 'uri', 'locationName' => 'sessionId', ], 'messages' => [ 'shape' => 'Messages', ], 'sessionState' => [ 'shape' => 'SessionState', ], 'requestAttributes' => [ 'shape' => 'StringMap', ], 'responseContentType' => [ 'shape' => 'NonEmptyString', 'location' => 'header', 'locationName' => 'ResponseContentType', ], ], ], 'PutSessionResponse' => [ 'type' => 'structure', 'members' => [ 'contentType' => [ 'shape' => 'NonEmptyString', 'location' => 'header', 'locationName' => 'Content-Type', ], 'messages' => [ 'shape' => 'NonEmptyString', 'location' => 'header', 'locationName' => 'x-amz-lex-messages', ], 'sessionState' => [ 'shape' => 'NonEmptyString', 'location' => 'header', 'locationName' => 'x-amz-lex-session-state', ], 'requestAttributes' => [ 'shape' => 'NonEmptyString', 'location' => 'header', 'locationName' => 'x-amz-lex-request-attributes', ], 'sessionId' => [ 'shape' => 'SessionId', 'location' => 'header', 'locationName' => 'x-amz-lex-session-id', ], 'audioStream' => [ 'shape' => 'BlobStream', ], ], 'payload' => 'audioStream', ], 'RecognizeTextRequest' => [ 'type' => 'structure', 'required' => [ 'botId', 'botAliasId', 'localeId', 'text', 'sessionId', ], 'members' => [ 'botId' => [ 'shape' => 'BotIdentifier', 'location' => 'uri', 'locationName' => 'botId', ], 'botAliasId' => [ 'shape' => 'BotAliasIdentifier', 'location' => 'uri', 'locationName' => 'botAliasId', ], 'localeId' => [ 'shape' => 'LocaleId', 'location' => 'uri', 'locationName' => 'localeId', ], 'sessionId' => [ 'shape' => 'SessionId', 'location' => 'uri', 'locationName' => 'sessionId', ], 'text' => [ 'shape' => 'Text', ], 'sessionState' => [ 'shape' => 'SessionState', ], 'requestAttributes' => [ 'shape' => 'StringMap', ], ], ], 'RecognizeTextResponse' => [ 'type' => 'structure', 'members' => [ 'messages' => [ 'shape' => 'Messages', ], 'sessionState' => [ 'shape' => 'SessionState', ], 'interpretations' => [ 'shape' => 'Interpretations', ], 'requestAttributes' => [ 'shape' => 'StringMap', ], 'sessionId' => [ 'shape' => 'SessionId', ], ], ], 'RecognizeUtteranceRequest' => [ 'type' => 'structure', 'required' => [ 'botId', 'botAliasId', 'localeId', 'requestContentType', 'sessionId', ], 'members' => [ 'botId' => [ 'shape' => 'BotIdentifier', 'location' => 'uri', 'locationName' => 'botId', ], 'botAliasId' => [ 'shape' => 'BotAliasIdentifier', 'location' => 'uri', 'locationName' => 'botAliasId', ], 'localeId' => [ 'shape' => 'LocaleId', 'location' => 'uri', 'locationName' => 'localeId', ], 'sessionId' => [ 'shape' => 'SessionId', 'location' => 'uri', 'locationName' => 'sessionId', ], 'sessionState' => [ 'shape' => 'SensitiveNonEmptyString', 'location' => 'header', 'locationName' => 'x-amz-lex-session-state', ], 'requestAttributes' => [ 'shape' => 'SensitiveNonEmptyString', 'location' => 'header', 'locationName' => 'x-amz-lex-request-attributes', ], 'requestContentType' => [ 'shape' => 'NonEmptyString', 'location' => 'header', 'locationName' => 'Content-Type', ], 'responseContentType' => [ 'shape' => 'NonEmptyString', 'location' => 'header', 'locationName' => 'Response-Content-Type', ], 'inputStream' => [ 'shape' => 'BlobStream', ], ], 'payload' => 'inputStream', ], 'RecognizeUtteranceResponse' => [ 'type' => 'structure', 'members' => [ 'inputMode' => [ 'shape' => 'NonEmptyString', 'location' => 'header', 'locationName' => 'x-amz-lex-input-mode', ], 'contentType' => [ 'shape' => 'NonEmptyString', 'location' => 'header', 'locationName' => 'Content-Type', ], 'messages' => [ 'shape' => 'NonEmptyString', 'location' => 'header', 'locationName' => 'x-amz-lex-messages', ], 'interpretations' => [ 'shape' => 'NonEmptyString', 'location' => 'header', 'locationName' => 'x-amz-lex-interpretations', ], 'sessionState' => [ 'shape' => 'NonEmptyString', 'location' => 'header', 'locationName' => 'x-amz-lex-session-state', ], 'requestAttributes' => [ 'shape' => 'NonEmptyString', 'location' => 'header', 'locationName' => 'x-amz-lex-request-attributes', ], 'sessionId' => [ 'shape' => 'SessionId', 'location' => 'header', 'locationName' => 'x-amz-lex-session-id', ], 'inputTranscript' => [ 'shape' => 'NonEmptyString', 'location' => 'header', 'locationName' => 'x-amz-lex-input-transcript', ], 'audioStream' => [ 'shape' => 'BlobStream', ], ], 'payload' => 'audioStream', ], 'ResourceNotFoundException' => [ 'type' => 'structure', 'required' => [ 'message', ], 'members' => [ 'message' => [ 'shape' => 'String', ], ], 'error' => [ 'httpStatusCode' => 404, ], 'exception' => true, ], 'SensitiveNonEmptyString' => [ 'type' => 'string', 'sensitive' => true, ], 'SentimentResponse' => [ 'type' => 'structure', 'members' => [ 'sentiment' => [ 'shape' => 'SentimentType', ], 'sentimentScore' => [ 'shape' => 'SentimentScore', ], ], ], 'SentimentScore' => [ 'type' => 'structure', 'members' => [ 'positive' => [ 'shape' => 'Double', ], 'negative' => [ 'shape' => 'Double', ], 'neutral' => [ 'shape' => 'Double', ], 'mixed' => [ 'shape' => 'Double', ], ], ], 'SentimentType' => [ 'type' => 'string', 'enum' => [ 'MIXED', 'NEGATIVE', 'NEUTRAL', 'POSITIVE', ], ], 'SessionId' => [ 'type' => 'string', 'max' => 100, 'min' => 2, 'pattern' => '[0-9a-zA-Z._:-]+', ], 'SessionState' => [ 'type' => 'structure', 'members' => [ 'dialogAction' => [ 'shape' => 'DialogAction', ], 'intent' => [ 'shape' => 'Intent', ], 'activeContexts' => [ 'shape' => 'ActiveContextsList', ], 'sessionAttributes' => [ 'shape' => 'StringMap', ], 'originatingRequestId' => [ 'shape' => 'NonEmptyString', ], ], ], 'Slot' => [ 'type' => 'structure', 'members' => [ 'value' => [ 'shape' => 'Value', ], ], ], 'Slots' => [ 'type' => 'map', 'key' => [ 'shape' => 'NonEmptyString', ], 'value' => [ 'shape' => 'Slot', ], ], 'StartConversationRequest' => [ 'type' => 'structure', 'required' => [ 'botId', 'botAliasId', 'localeId', 'requestEventStream', 'sessionId', ], 'members' => [ 'botId' => [ 'shape' => 'BotIdentifier', 'location' => 'uri', 'locationName' => 'botId', ], 'botAliasId' => [ 'shape' => 'BotAliasIdentifier', 'location' => 'uri', 'locationName' => 'botAliasId', ], 'localeId' => [ 'shape' => 'LocaleId', 'location' => 'uri', 'locationName' => 'localeId', ], 'sessionId' => [ 'shape' => 'SessionId', 'location' => 'uri', 'locationName' => 'sessionId', ], 'conversationMode' => [ 'shape' => 'ConversationMode', 'location' => 'header', 'locationName' => 'x-amz-lex-conversation-mode', ], 'requestEventStream' => [ 'shape' => 'StartConversationRequestEventStream', ], ], 'payload' => 'requestEventStream', ], 'StartConversationRequestEventStream' => [ 'type' => 'structure', 'members' => [ 'ConfigurationEvent' => [ 'shape' => 'ConfigurationEvent', ], 'AudioInputEvent' => [ 'shape' => 'AudioInputEvent', ], 'DTMFInputEvent' => [ 'shape' => 'DTMFInputEvent', ], 'TextInputEvent' => [ 'shape' => 'TextInputEvent', ], 'PlaybackCompletionEvent' => [ 'shape' => 'PlaybackCompletionEvent', ], 'DisconnectionEvent' => [ 'shape' => 'DisconnectionEvent', ], ], 'eventstream' => true, ], 'StartConversationResponse' => [ 'type' => 'structure', 'members' => [ 'responseEventStream' => [ 'shape' => 'StartConversationResponseEventStream', ], ], 'payload' => 'responseEventStream', ], 'StartConversationResponseEventStream' => [ 'type' => 'structure', 'members' => [ 'PlaybackInterruptionEvent' => [ 'shape' => 'PlaybackInterruptionEvent', ], 'TranscriptEvent' => [ 'shape' => 'TranscriptEvent', ], 'IntentResultEvent' => [ 'shape' => 'IntentResultEvent', ], 'TextResponseEvent' => [ 'shape' => 'TextResponseEvent', ], 'AudioResponseEvent' => [ 'shape' => 'AudioResponseEvent', ], 'HeartbeatEvent' => [ 'shape' => 'HeartbeatEvent', ], 'AccessDeniedException' => [ 'shape' => 'AccessDeniedException', ], 'ResourceNotFoundException' => [ 'shape' => 'ResourceNotFoundException', ], 'ValidationException' => [ 'shape' => 'ValidationException', ], 'ThrottlingException' => [ 'shape' => 'ThrottlingException', ], 'InternalServerException' => [ 'shape' => 'InternalServerException', ], 'ConflictException' => [ 'shape' => 'ConflictException', ], 'DependencyFailedException' => [ 'shape' => 'DependencyFailedException', ], 'BadGatewayException' => [ 'shape' => 'BadGatewayException', ], ], 'eventstream' => true, ], 'String' => [ 'type' => 'string', ], 'StringList' => [ 'type' => 'list', 'member' => [ 'shape' => 'NonEmptyString', ], ], 'StringMap' => [ 'type' => 'map', 'key' => [ 'shape' => 'NonEmptyString', ], 'value' => [ 'shape' => 'String', ], ], 'Text' => [ 'type' => 'string', 'max' => 1024, 'min' => 1, 'sensitive' => true, ], 'TextInputEvent' => [ 'type' => 'structure', 'required' => [ 'text', ], 'members' => [ 'text' => [ 'shape' => 'Text', ], 'eventId' => [ 'shape' => 'EventId', ], 'clientTimestampMillis' => [ 'shape' => 'EpochMillis', ], ], 'event' => true, ], 'TextResponseEvent' => [ 'type' => 'structure', 'members' => [ 'messages' => [ 'shape' => 'Messages', ], 'eventId' => [ 'shape' => 'EventId', ], ], 'event' => true, ], 'ThrottlingException' => [ 'type' => 'structure', 'required' => [ 'message', ], 'members' => [ 'message' => [ 'shape' => 'String', ], ], 'error' => [ 'httpStatusCode' => 429, ], 'exception' => true, ], 'TranscriptEvent' => [ 'type' => 'structure', 'members' => [ 'transcript' => [ 'shape' => 'String', ], 'eventId' => [ 'shape' => 'EventId', ], ], 'event' => true, ], 'ValidationException' => [ 'type' => 'structure', 'required' => [ 'message', ], 'members' => [ 'message' => [ 'shape' => 'String', ], ], 'error' => [ 'httpStatusCode' => 400, ], 'exception' => true, ], 'Value' => [ 'type' => 'structure', 'required' => [ 'interpretedValue', ], 'members' => [ 'originalValue' => [ 'shape' => 'NonEmptyString', ], 'interpretedValue' => [ 'shape' => 'NonEmptyString', ], 'resolvedValues' => [ 'shape' => 'StringList', ], ], ], ],];
