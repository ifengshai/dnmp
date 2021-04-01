<?php
// This file was auto-generated from sdk-root/src/data/codeguru-reviewer/2019-09-19/api-2.json
return [ 'version' => '2.0', 'metadata' => [ 'apiVersion' => '2019-09-19', 'endpointPrefix' => 'codeguru-reviewer', 'jsonVersion' => '1.1', 'protocol' => 'rest-json', 'serviceAbbreviation' => 'CodeGuruReviewer', 'serviceFullName' => 'Amazon CodeGuru Reviewer', 'serviceId' => 'CodeGuru Reviewer', 'signatureVersion' => 'v4', 'signingName' => 'codeguru-reviewer', 'uid' => 'codeguru-reviewer-2019-09-19', ], 'operations' => [ 'AssociateRepository' => [ 'name' => 'AssociateRepository', 'http' => [ 'method' => 'POST', 'requestUri' => '/associations', ], 'input' => [ 'shape' => 'AssociateRepositoryRequest', ], 'output' => [ 'shape' => 'AssociateRepositoryResponse', ], 'errors' => [ [ 'shape' => 'InternalServerException', ], [ 'shape' => 'ValidationException', ], [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'ConflictException', ], [ 'shape' => 'ThrottlingException', ], ], ], 'CreateCodeReview' => [ 'name' => 'CreateCodeReview', 'http' => [ 'method' => 'POST', 'requestUri' => '/codereviews', ], 'input' => [ 'shape' => 'CreateCodeReviewRequest', ], 'output' => [ 'shape' => 'CreateCodeReviewResponse', ], 'errors' => [ [ 'shape' => 'ResourceNotFoundException', ], [ 'shape' => 'InternalServerException', ], [ 'shape' => 'ValidationException', ], [ 'shape' => 'ConflictException', ], [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'ThrottlingException', ], ], ], 'DescribeCodeReview' => [ 'name' => 'DescribeCodeReview', 'http' => [ 'method' => 'GET', 'requestUri' => '/codereviews/{CodeReviewArn}', ], 'input' => [ 'shape' => 'DescribeCodeReviewRequest', ], 'output' => [ 'shape' => 'DescribeCodeReviewResponse', ], 'errors' => [ [ 'shape' => 'ResourceNotFoundException', ], [ 'shape' => 'InternalServerException', ], [ 'shape' => 'ValidationException', ], [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'ThrottlingException', ], ], ], 'DescribeRecommendationFeedback' => [ 'name' => 'DescribeRecommendationFeedback', 'http' => [ 'method' => 'GET', 'requestUri' => '/feedback/{CodeReviewArn}', ], 'input' => [ 'shape' => 'DescribeRecommendationFeedbackRequest', ], 'output' => [ 'shape' => 'DescribeRecommendationFeedbackResponse', ], 'errors' => [ [ 'shape' => 'ResourceNotFoundException', ], [ 'shape' => 'InternalServerException', ], [ 'shape' => 'ValidationException', ], [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'ThrottlingException', ], ], ], 'DescribeRepositoryAssociation' => [ 'name' => 'DescribeRepositoryAssociation', 'http' => [ 'method' => 'GET', 'requestUri' => '/associations/{AssociationArn}', ], 'input' => [ 'shape' => 'DescribeRepositoryAssociationRequest', ], 'output' => [ 'shape' => 'DescribeRepositoryAssociationResponse', ], 'errors' => [ [ 'shape' => 'NotFoundException', ], [ 'shape' => 'InternalServerException', ], [ 'shape' => 'ValidationException', ], [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'ThrottlingException', ], ], ], 'DisassociateRepository' => [ 'name' => 'DisassociateRepository', 'http' => [ 'method' => 'DELETE', 'requestUri' => '/associations/{AssociationArn}', ], 'input' => [ 'shape' => 'DisassociateRepositoryRequest', ], 'output' => [ 'shape' => 'DisassociateRepositoryResponse', ], 'errors' => [ [ 'shape' => 'NotFoundException', ], [ 'shape' => 'InternalServerException', ], [ 'shape' => 'ValidationException', ], [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'ConflictException', ], [ 'shape' => 'ThrottlingException', ], ], ], 'ListCodeReviews' => [ 'name' => 'ListCodeReviews', 'http' => [ 'method' => 'GET', 'requestUri' => '/codereviews', ], 'input' => [ 'shape' => 'ListCodeReviewsRequest', ], 'output' => [ 'shape' => 'ListCodeReviewsResponse', ], 'errors' => [ [ 'shape' => 'InternalServerException', ], [ 'shape' => 'ValidationException', ], [ 'shape' => 'ThrottlingException', ], [ 'shape' => 'AccessDeniedException', ], ], ], 'ListRecommendationFeedback' => [ 'name' => 'ListRecommendationFeedback', 'http' => [ 'method' => 'GET', 'requestUri' => '/feedback/{CodeReviewArn}/RecommendationFeedback', ], 'input' => [ 'shape' => 'ListRecommendationFeedbackRequest', ], 'output' => [ 'shape' => 'ListRecommendationFeedbackResponse', ], 'errors' => [ [ 'shape' => 'ResourceNotFoundException', ], [ 'shape' => 'InternalServerException', ], [ 'shape' => 'ValidationException', ], [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'ThrottlingException', ], ], ], 'ListRecommendations' => [ 'name' => 'ListRecommendations', 'http' => [ 'method' => 'GET', 'requestUri' => '/codereviews/{CodeReviewArn}/Recommendations', ], 'input' => [ 'shape' => 'ListRecommendationsRequest', ], 'output' => [ 'shape' => 'ListRecommendationsResponse', ], 'errors' => [ [ 'shape' => 'ResourceNotFoundException', ], [ 'shape' => 'InternalServerException', ], [ 'shape' => 'ValidationException', ], [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'ThrottlingException', ], ], ], 'ListRepositoryAssociations' => [ 'name' => 'ListRepositoryAssociations', 'http' => [ 'method' => 'GET', 'requestUri' => '/associations', ], 'input' => [ 'shape' => 'ListRepositoryAssociationsRequest', ], 'output' => [ 'shape' => 'ListRepositoryAssociationsResponse', ], 'errors' => [ [ 'shape' => 'InternalServerException', ], [ 'shape' => 'ValidationException', ], [ 'shape' => 'ThrottlingException', ], ], ], 'ListTagsForResource' => [ 'name' => 'ListTagsForResource', 'http' => [ 'method' => 'GET', 'requestUri' => '/tags/{resourceArn}', ], 'input' => [ 'shape' => 'ListTagsForResourceRequest', ], 'output' => [ 'shape' => 'ListTagsForResourceResponse', ], 'errors' => [ [ 'shape' => 'InternalServerException', ], [ 'shape' => 'ValidationException', ], [ 'shape' => 'ResourceNotFoundException', ], ], ], 'PutRecommendationFeedback' => [ 'name' => 'PutRecommendationFeedback', 'http' => [ 'method' => 'PUT', 'requestUri' => '/feedback', ], 'input' => [ 'shape' => 'PutRecommendationFeedbackRequest', ], 'output' => [ 'shape' => 'PutRecommendationFeedbackResponse', ], 'errors' => [ [ 'shape' => 'ResourceNotFoundException', ], [ 'shape' => 'InternalServerException', ], [ 'shape' => 'ValidationException', ], [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'ThrottlingException', ], ], ], 'TagResource' => [ 'name' => 'TagResource', 'http' => [ 'method' => 'POST', 'requestUri' => '/tags/{resourceArn}', ], 'input' => [ 'shape' => 'TagResourceRequest', ], 'output' => [ 'shape' => 'TagResourceResponse', ], 'errors' => [ [ 'shape' => 'InternalServerException', ], [ 'shape' => 'ValidationException', ], [ 'shape' => 'ResourceNotFoundException', ], ], ], 'UntagResource' => [ 'name' => 'UntagResource', 'http' => [ 'method' => 'DELETE', 'requestUri' => '/tags/{resourceArn}', ], 'input' => [ 'shape' => 'UntagResourceRequest', ], 'output' => [ 'shape' => 'UntagResourceResponse', ], 'errors' => [ [ 'shape' => 'InternalServerException', ], [ 'shape' => 'ValidationException', ], [ 'shape' => 'ResourceNotFoundException', ], ], ], ], 'shapes' => [ 'AccessDeniedException' => [ 'type' => 'structure', 'members' => [ 'Message' => [ 'shape' => 'ErrorMessage', ], ], 'error' => [ 'httpStatusCode' => 403, ], 'exception' => true, ], 'Arn' => [ 'type' => 'string', 'max' => 1600, 'min' => 1, 'pattern' => '^arn:aws[^:\\s]*:codeguru-reviewer:[^:\\s]+:[\\d]{12}:[a-z-]+:[\\w-]+$', ], 'AssociateRepositoryRequest' => [ 'type' => 'structure', 'required' => [ 'Repository', ], 'members' => [ 'Repository' => [ 'shape' => 'Repository', ], 'ClientRequestToken' => [ 'shape' => 'ClientRequestToken', 'idempotencyToken' => true, ], 'Tags' => [ 'shape' => 'TagMap', ], ], ], 'AssociateRepositoryResponse' => [ 'type' => 'structure', 'members' => [ 'RepositoryAssociation' => [ 'shape' => 'RepositoryAssociation', ], 'Tags' => [ 'shape' => 'TagMap', ], ], ], 'AssociationArn' => [ 'type' => 'string', 'max' => 1600, 'min' => 1, 'pattern' => '^arn:aws[^:\\s]*:codeguru-reviewer:[^:\\s]+:[\\d]{12}:association:[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$', ], 'AssociationId' => [ 'type' => 'string', 'max' => 64, 'min' => 1, ], 'BranchName' => [ 'type' => 'string', 'max' => 256, 'min' => 1, ], 'ClientRequestToken' => [ 'type' => 'string', 'max' => 64, 'min' => 1, 'pattern' => '^[\\w-]+$', ], 'CodeCommitRepository' => [ 'type' => 'structure', 'required' => [ 'Name', ], 'members' => [ 'Name' => [ 'shape' => 'Name', ], ], ], 'CodeReview' => [ 'type' => 'structure', 'members' => [ 'Name' => [ 'shape' => 'Name', ], 'CodeReviewArn' => [ 'shape' => 'Arn', ], 'RepositoryName' => [ 'shape' => 'Name', ], 'Owner' => [ 'shape' => 'Owner', ], 'ProviderType' => [ 'shape' => 'ProviderType', ], 'State' => [ 'shape' => 'JobState', ], 'StateReason' => [ 'shape' => 'StateReason', ], 'CreatedTimeStamp' => [ 'shape' => 'TimeStamp', ], 'LastUpdatedTimeStamp' => [ 'shape' => 'TimeStamp', ], 'Type' => [ 'shape' => 'Type', ], 'PullRequestId' => [ 'shape' => 'PullRequestId', ], 'SourceCodeType' => [ 'shape' => 'SourceCodeType', ], 'AssociationArn' => [ 'shape' => 'AssociationArn', ], 'Metrics' => [ 'shape' => 'Metrics', ], ], ], 'CodeReviewName' => [ 'type' => 'string', 'max' => 100, 'min' => 1, 'pattern' => '[a-zA-Z0-9-_]*', ], 'CodeReviewSummaries' => [ 'type' => 'list', 'member' => [ 'shape' => 'CodeReviewSummary', ], ], 'CodeReviewSummary' => [ 'type' => 'structure', 'members' => [ 'Name' => [ 'shape' => 'Name', ], 'CodeReviewArn' => [ 'shape' => 'Arn', ], 'RepositoryName' => [ 'shape' => 'Name', ], 'Owner' => [ 'shape' => 'Owner', ], 'ProviderType' => [ 'shape' => 'ProviderType', ], 'State' => [ 'shape' => 'JobState', ], 'CreatedTimeStamp' => [ 'shape' => 'TimeStamp', ], 'LastUpdatedTimeStamp' => [ 'shape' => 'TimeStamp', ], 'Type' => [ 'shape' => 'Type', ], 'PullRequestId' => [ 'shape' => 'PullRequestId', ], 'MetricsSummary' => [ 'shape' => 'MetricsSummary', ], ], ], 'CodeReviewType' => [ 'type' => 'structure', 'required' => [ 'RepositoryAnalysis', ], 'members' => [ 'RepositoryAnalysis' => [ 'shape' => 'RepositoryAnalysis', ], ], ], 'CommitDiffSourceCodeType' => [ 'type' => 'structure', 'members' => [ 'SourceCommit' => [ 'shape' => 'CommitId', ], 'DestinationCommit' => [ 'shape' => 'CommitId', ], ], ], 'CommitId' => [ 'type' => 'string', 'max' => 64, 'min' => 6, ], 'ConflictException' => [ 'type' => 'structure', 'members' => [ 'Message' => [ 'shape' => 'ErrorMessage', ], ], 'error' => [ 'httpStatusCode' => 409, ], 'exception' => true, ], 'ConnectionArn' => [ 'type' => 'string', 'max' => 256, 'min' => 0, 'pattern' => 'arn:aws(-[\\w]+)*:.+:.+:[0-9]{12}:.+', ], 'CreateCodeReviewRequest' => [ 'type' => 'structure', 'required' => [ 'Name', 'RepositoryAssociationArn', 'Type', ], 'members' => [ 'Name' => [ 'shape' => 'CodeReviewName', ], 'RepositoryAssociationArn' => [ 'shape' => 'AssociationArn', ], 'Type' => [ 'shape' => 'CodeReviewType', ], 'ClientRequestToken' => [ 'shape' => 'ClientRequestToken', 'idempotencyToken' => true, ], ], ], 'CreateCodeReviewResponse' => [ 'type' => 'structure', 'members' => [ 'CodeReview' => [ 'shape' => 'CodeReview', ], ], ], 'DescribeCodeReviewRequest' => [ 'type' => 'structure', 'required' => [ 'CodeReviewArn', ], 'members' => [ 'CodeReviewArn' => [ 'shape' => 'Arn', 'location' => 'uri', 'locationName' => 'CodeReviewArn', ], ], ], 'DescribeCodeReviewResponse' => [ 'type' => 'structure', 'members' => [ 'CodeReview' => [ 'shape' => 'CodeReview', ], ], ], 'DescribeRecommendationFeedbackRequest' => [ 'type' => 'structure', 'required' => [ 'CodeReviewArn', 'RecommendationId', ], 'members' => [ 'CodeReviewArn' => [ 'shape' => 'Arn', 'location' => 'uri', 'locationName' => 'CodeReviewArn', ], 'RecommendationId' => [ 'shape' => 'RecommendationId', 'location' => 'querystring', 'locationName' => 'RecommendationId', ], 'UserId' => [ 'shape' => 'UserId', 'location' => 'querystring', 'locationName' => 'UserId', ], ], ], 'DescribeRecommendationFeedbackResponse' => [ 'type' => 'structure', 'members' => [ 'RecommendationFeedback' => [ 'shape' => 'RecommendationFeedback', ], ], ], 'DescribeRepositoryAssociationRequest' => [ 'type' => 'structure', 'required' => [ 'AssociationArn', ], 'members' => [ 'AssociationArn' => [ 'shape' => 'AssociationArn', 'location' => 'uri', 'locationName' => 'AssociationArn', ], ], ], 'DescribeRepositoryAssociationResponse' => [ 'type' => 'structure', 'members' => [ 'RepositoryAssociation' => [ 'shape' => 'RepositoryAssociation', ], 'Tags' => [ 'shape' => 'TagMap', ], ], ], 'DisassociateRepositoryRequest' => [ 'type' => 'structure', 'required' => [ 'AssociationArn', ], 'members' => [ 'AssociationArn' => [ 'shape' => 'AssociationArn', 'location' => 'uri', 'locationName' => 'AssociationArn', ], ], ], 'DisassociateRepositoryResponse' => [ 'type' => 'structure', 'members' => [ 'RepositoryAssociation' => [ 'shape' => 'RepositoryAssociation', ], 'Tags' => [ 'shape' => 'TagMap', ], ], ], 'ErrorMessage' => [ 'type' => 'string', ], 'FilePath' => [ 'type' => 'string', 'max' => 1024, 'min' => 1, ], 'FindingsCount' => [ 'type' => 'long', ], 'InternalServerException' => [ 'type' => 'structure', 'members' => [ 'Message' => [ 'shape' => 'ErrorMessage', ], ], 'error' => [ 'httpStatusCode' => 500, ], 'exception' => true, 'fault' => true, ], 'JobState' => [ 'type' => 'string', 'enum' => [ 'Completed', 'Pending', 'Failed', 'Deleting', ], ], 'JobStates' => [ 'type' => 'list', 'member' => [ 'shape' => 'JobState', ], 'max' => 3, 'min' => 1, ], 'LineNumber' => [ 'type' => 'integer', ], 'ListCodeReviewsMaxResults' => [ 'type' => 'integer', 'max' => 100, 'min' => 1, ], 'ListCodeReviewsRequest' => [ 'type' => 'structure', 'required' => [ 'Type', ], 'members' => [ 'ProviderTypes' => [ 'shape' => 'ProviderTypes', 'location' => 'querystring', 'locationName' => 'ProviderTypes', ], 'States' => [ 'shape' => 'JobStates', 'location' => 'querystring', 'locationName' => 'States', ], 'RepositoryNames' => [ 'shape' => 'RepositoryNames', 'location' => 'querystring', 'locationName' => 'RepositoryNames', ], 'Type' => [ 'shape' => 'Type', 'location' => 'querystring', 'locationName' => 'Type', ], 'MaxResults' => [ 'shape' => 'ListCodeReviewsMaxResults', 'location' => 'querystring', 'locationName' => 'MaxResults', ], 'NextToken' => [ 'shape' => 'NextToken', 'location' => 'querystring', 'locationName' => 'NextToken', ], ], ], 'ListCodeReviewsResponse' => [ 'type' => 'structure', 'members' => [ 'CodeReviewSummaries' => [ 'shape' => 'CodeReviewSummaries', ], 'NextToken' => [ 'shape' => 'NextToken', ], ], ], 'ListRecommendationFeedbackRequest' => [ 'type' => 'structure', 'required' => [ 'CodeReviewArn', ], 'members' => [ 'NextToken' => [ 'shape' => 'NextToken', 'location' => 'querystring', 'locationName' => 'NextToken', ], 'MaxResults' => [ 'shape' => 'MaxResults', 'location' => 'querystring', 'locationName' => 'MaxResults', ], 'CodeReviewArn' => [ 'shape' => 'Arn', 'location' => 'uri', 'locationName' => 'CodeReviewArn', ], 'UserIds' => [ 'shape' => 'UserIds', 'location' => 'querystring', 'locationName' => 'UserIds', ], 'RecommendationIds' => [ 'shape' => 'RecommendationIds', 'location' => 'querystring', 'locationName' => 'RecommendationIds', ], ], ], 'ListRecommendationFeedbackResponse' => [ 'type' => 'structure', 'members' => [ 'RecommendationFeedbackSummaries' => [ 'shape' => 'RecommendationFeedbackSummaries', ], 'NextToken' => [ 'shape' => 'NextToken', ], ], ], 'ListRecommendationsRequest' => [ 'type' => 'structure', 'required' => [ 'CodeReviewArn', ], 'members' => [ 'NextToken' => [ 'shape' => 'NextToken', 'location' => 'querystring', 'locationName' => 'NextToken', ], 'MaxResults' => [ 'shape' => 'MaxResults', 'location' => 'querystring', 'locationName' => 'MaxResults', ], 'CodeReviewArn' => [ 'shape' => 'Arn', 'location' => 'uri', 'locationName' => 'CodeReviewArn', ], ], ], 'ListRecommendationsResponse' => [ 'type' => 'structure', 'members' => [ 'RecommendationSummaries' => [ 'shape' => 'RecommendationSummaries', ], 'NextToken' => [ 'shape' => 'NextToken', ], ], ], 'ListRepositoryAssociationsRequest' => [ 'type' => 'structure', 'members' => [ 'ProviderTypes' => [ 'shape' => 'ProviderTypes', 'location' => 'querystring', 'locationName' => 'ProviderType', ], 'States' => [ 'shape' => 'RepositoryAssociationStates', 'location' => 'querystring', 'locationName' => 'State', ], 'Names' => [ 'shape' => 'Names', 'location' => 'querystring', 'locationName' => 'Name', ], 'Owners' => [ 'shape' => 'Owners', 'location' => 'querystring', 'locationName' => 'Owner', ], 'MaxResults' => [ 'shape' => 'MaxResults', 'location' => 'querystring', 'locationName' => 'MaxResults', ], 'NextToken' => [ 'shape' => 'NextToken', 'location' => 'querystring', 'locationName' => 'NextToken', ], ], ], 'ListRepositoryAssociationsResponse' => [ 'type' => 'structure', 'members' => [ 'RepositoryAssociationSummaries' => [ 'shape' => 'RepositoryAssociationSummaries', ], 'NextToken' => [ 'shape' => 'NextToken', ], ], ], 'ListTagsForResourceRequest' => [ 'type' => 'structure', 'required' => [ 'resourceArn', ], 'members' => [ 'resourceArn' => [ 'shape' => 'AssociationArn', 'location' => 'uri', 'locationName' => 'resourceArn', ], ], ], 'ListTagsForResourceResponse' => [ 'type' => 'structure', 'members' => [ 'Tags' => [ 'shape' => 'TagMap', ], ], ], 'MaxResults' => [ 'type' => 'integer', 'max' => 100, 'min' => 1, ], 'MeteredLinesOfCodeCount' => [ 'type' => 'long', ], 'Metrics' => [ 'type' => 'structure', 'members' => [ 'MeteredLinesOfCodeCount' => [ 'shape' => 'MeteredLinesOfCodeCount', ], 'FindingsCount' => [ 'shape' => 'FindingsCount', ], ], ], 'MetricsSummary' => [ 'type' => 'structure', 'members' => [ 'MeteredLinesOfCodeCount' => [ 'shape' => 'MeteredLinesOfCodeCount', ], 'FindingsCount' => [ 'shape' => 'FindingsCount', ], ], ], 'Name' => [ 'type' => 'string', 'max' => 100, 'min' => 1, 'pattern' => '^\\S[\\w.-]*$', ], 'Names' => [ 'type' => 'list', 'member' => [ 'shape' => 'Name', ], 'max' => 3, 'min' => 1, ], 'NextToken' => [ 'type' => 'string', 'max' => 2048, 'min' => 1, ], 'NotFoundException' => [ 'type' => 'structure', 'members' => [ 'Message' => [ 'shape' => 'ErrorMessage', ], ], 'error' => [ 'httpStatusCode' => 404, ], 'exception' => true, ], 'Owner' => [ 'type' => 'string', 'max' => 100, 'min' => 1, 'pattern' => '^\\S(.*\\S)?$', ], 'Owners' => [ 'type' => 'list', 'member' => [ 'shape' => 'Owner', ], 'max' => 3, 'min' => 1, ], 'ProviderType' => [ 'type' => 'string', 'enum' => [ 'CodeCommit', 'GitHub', 'Bitbucket', 'GitHubEnterpriseServer', ], ], 'ProviderTypes' => [ 'type' => 'list', 'member' => [ 'shape' => 'ProviderType', ], 'max' => 3, 'min' => 1, ], 'PullRequestId' => [ 'type' => 'string', 'max' => 64, 'min' => 1, ], 'PutRecommendationFeedbackRequest' => [ 'type' => 'structure', 'required' => [ 'CodeReviewArn', 'RecommendationId', 'Reactions', ], 'members' => [ 'CodeReviewArn' => [ 'shape' => 'Arn', ], 'RecommendationId' => [ 'shape' => 'RecommendationId', ], 'Reactions' => [ 'shape' => 'Reactions', ], ], ], 'PutRecommendationFeedbackResponse' => [ 'type' => 'structure', 'members' => [], ], 'Reaction' => [ 'type' => 'string', 'enum' => [ 'ThumbsUp', 'ThumbsDown', ], ], 'Reactions' => [ 'type' => 'list', 'member' => [ 'shape' => 'Reaction', ], 'max' => 1, 'min' => 0, ], 'RecommendationFeedback' => [ 'type' => 'structure', 'members' => [ 'CodeReviewArn' => [ 'shape' => 'Arn', ], 'RecommendationId' => [ 'shape' => 'RecommendationId', ], 'Reactions' => [ 'shape' => 'Reactions', ], 'UserId' => [ 'shape' => 'UserId', ], 'CreatedTimeStamp' => [ 'shape' => 'TimeStamp', ], 'LastUpdatedTimeStamp' => [ 'shape' => 'TimeStamp', ], ], ], 'RecommendationFeedbackSummaries' => [ 'type' => 'list', 'member' => [ 'shape' => 'RecommendationFeedbackSummary', ], ], 'RecommendationFeedbackSummary' => [ 'type' => 'structure', 'members' => [ 'RecommendationId' => [ 'shape' => 'RecommendationId', ], 'Reactions' => [ 'shape' => 'Reactions', ], 'UserId' => [ 'shape' => 'UserId', ], ], ], 'RecommendationId' => [ 'type' => 'string', 'max' => 64, 'min' => 1, ], 'RecommendationIds' => [ 'type' => 'list', 'member' => [ 'shape' => 'RecommendationId', ], 'max' => 100, 'min' => 1, ], 'RecommendationSummaries' => [ 'type' => 'list', 'member' => [ 'shape' => 'RecommendationSummary', ], ], 'RecommendationSummary' => [ 'type' => 'structure', 'members' => [ 'FilePath' => [ 'shape' => 'FilePath', ], 'RecommendationId' => [ 'shape' => 'RecommendationId', ], 'StartLine' => [ 'shape' => 'LineNumber', ], 'EndLine' => [ 'shape' => 'LineNumber', ], 'Description' => [ 'shape' => 'Text', ], ], ], 'Repository' => [ 'type' => 'structure', 'members' => [ 'CodeCommit' => [ 'shape' => 'CodeCommitRepository', ], 'Bitbucket' => [ 'shape' => 'ThirdPartySourceRepository', ], 'GitHubEnterpriseServer' => [ 'shape' => 'ThirdPartySourceRepository', ], ], ], 'RepositoryAnalysis' => [ 'type' => 'structure', 'required' => [ 'RepositoryHead', ], 'members' => [ 'RepositoryHead' => [ 'shape' => 'RepositoryHeadSourceCodeType', ], ], ], 'RepositoryAssociation' => [ 'type' => 'structure', 'members' => [ 'AssociationId' => [ 'shape' => 'AssociationId', ], 'AssociationArn' => [ 'shape' => 'Arn', ], 'ConnectionArn' => [ 'shape' => 'ConnectionArn', ], 'Name' => [ 'shape' => 'Name', ], 'Owner' => [ 'shape' => 'Owner', ], 'ProviderType' => [ 'shape' => 'ProviderType', ], 'State' => [ 'shape' => 'RepositoryAssociationState', ], 'StateReason' => [ 'shape' => 'StateReason', ], 'LastUpdatedTimeStamp' => [ 'shape' => 'TimeStamp', ], 'CreatedTimeStamp' => [ 'shape' => 'TimeStamp', ], ], ], 'RepositoryAssociationState' => [ 'type' => 'string', 'enum' => [ 'Associated', 'Associating', 'Failed', 'Disassociating', 'Disassociated', ], ], 'RepositoryAssociationStates' => [ 'type' => 'list', 'member' => [ 'shape' => 'RepositoryAssociationState', ], 'max' => 5, 'min' => 1, ], 'RepositoryAssociationSummaries' => [ 'type' => 'list', 'member' => [ 'shape' => 'RepositoryAssociationSummary', ], ], 'RepositoryAssociationSummary' => [ 'type' => 'structure', 'members' => [ 'AssociationArn' => [ 'shape' => 'Arn', ], 'ConnectionArn' => [ 'shape' => 'ConnectionArn', ], 'LastUpdatedTimeStamp' => [ 'shape' => 'TimeStamp', ], 'AssociationId' => [ 'shape' => 'AssociationId', ], 'Name' => [ 'shape' => 'Name', ], 'Owner' => [ 'shape' => 'Owner', ], 'ProviderType' => [ 'shape' => 'ProviderType', ], 'State' => [ 'shape' => 'RepositoryAssociationState', ], ], ], 'RepositoryHeadSourceCodeType' => [ 'type' => 'structure', 'required' => [ 'BranchName', ], 'members' => [ 'BranchName' => [ 'shape' => 'BranchName', ], ], ], 'RepositoryNames' => [ 'type' => 'list', 'member' => [ 'shape' => 'Name', ], 'max' => 100, 'min' => 1, ], 'ResourceNotFoundException' => [ 'type' => 'structure', 'members' => [ 'Message' => [ 'shape' => 'ErrorMessage', ], ], 'error' => [ 'httpStatusCode' => 404, ], 'exception' => true, ], 'SourceCodeType' => [ 'type' => 'structure', 'members' => [ 'CommitDiff' => [ 'shape' => 'CommitDiffSourceCodeType', ], 'RepositoryHead' => [ 'shape' => 'RepositoryHeadSourceCodeType', ], ], ], 'StateReason' => [ 'type' => 'string', 'max' => 256, 'min' => 0, ], 'TagKey' => [ 'type' => 'string', 'max' => 128, 'min' => 1, ], 'TagKeyList' => [ 'type' => 'list', 'member' => [ 'shape' => 'TagKey', ], 'max' => 50, 'min' => 1, ], 'TagMap' => [ 'type' => 'map', 'key' => [ 'shape' => 'TagKey', ], 'value' => [ 'shape' => 'TagValue', ], 'max' => 50, 'min' => 1, ], 'TagResourceRequest' => [ 'type' => 'structure', 'required' => [ 'resourceArn', 'Tags', ], 'members' => [ 'resourceArn' => [ 'shape' => 'AssociationArn', 'location' => 'uri', 'locationName' => 'resourceArn', ], 'Tags' => [ 'shape' => 'TagMap', ], ], ], 'TagResourceResponse' => [ 'type' => 'structure', 'members' => [], ], 'TagValue' => [ 'type' => 'string', 'max' => 256, ], 'Text' => [ 'type' => 'string', 'max' => 2048, 'min' => 1, ], 'ThirdPartySourceRepository' => [ 'type' => 'structure', 'required' => [ 'Name', 'ConnectionArn', 'Owner', ], 'members' => [ 'Name' => [ 'shape' => 'Name', ], 'ConnectionArn' => [ 'shape' => 'ConnectionArn', ], 'Owner' => [ 'shape' => 'Owner', ], ], ], 'ThrottlingException' => [ 'type' => 'structure', 'members' => [ 'Message' => [ 'shape' => 'ErrorMessage', ], ], 'error' => [ 'httpStatusCode' => 429, ], 'exception' => true, ], 'TimeStamp' => [ 'type' => 'timestamp', ], 'Type' => [ 'type' => 'string', 'enum' => [ 'PullRequest', 'RepositoryAnalysis', ], ], 'UntagResourceRequest' => [ 'type' => 'structure', 'required' => [ 'resourceArn', 'TagKeys', ], 'members' => [ 'resourceArn' => [ 'shape' => 'AssociationArn', 'location' => 'uri', 'locationName' => 'resourceArn', ], 'TagKeys' => [ 'shape' => 'TagKeyList', 'location' => 'querystring', 'locationName' => 'tagKeys', ], ], ], 'UntagResourceResponse' => [ 'type' => 'structure', 'members' => [], ], 'UserId' => [ 'type' => 'string', 'max' => 256, 'min' => 1, ], 'UserIds' => [ 'type' => 'list', 'member' => [ 'shape' => 'UserId', ], 'max' => 100, 'min' => 1, ], 'ValidationException' => [ 'type' => 'structure', 'members' => [ 'Message' => [ 'shape' => 'ErrorMessage', ], ], 'error' => [ 'httpStatusCode' => 400, ], 'exception' => true, ], ],];
