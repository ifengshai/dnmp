<?php

return [
    'interfaces' => [
        'google.ads.googleads.v3.services.KeywordPlanAdGroupService' => [
            'GetKeywordPlanAdGroup' => [
                'method' => 'get',
                'uriTemplate' => '/v3/{resource_name=customers/*/keywordPlanAdGroups/*}',
                'placeholders' => [
                    'resource_name' => [
                        'getters' => [
                            'getResourceName',
                        ],
                    ],
                ],
            ],
            'MutateKeywordPlanAdGroups' => [
                'method' => 'post',
                'uriTemplate' => '/v3/customers/{customer_id=*}/keywordPlanAdGroups:mutate',
                'body' => '*',
                'placeholders' => [
                    'customer_id' => [
                        'getters' => [
                            'getCustomerId',
                        ],
                    ],
                ],
            ],
        ],
    ],
];
