<?php

namespace Elasticsearch\Endpoints;

use Elasticsearch\Common\Exceptions;

/**
 * Class Search
 *
 * @category Elasticsearch
 * @package  Elasticsearch\Endpoints
 * @author   Zachary Tong <zachary.tong@elasticsearch.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache2
 * @link     http://elasticsearch.org
 */
class Search extends AbstractEndpoint
{
    /**
     * @param array $body
     *
     * @throws \Elasticsearch\Common\Exceptions\InvalidArgumentException
     * @return $this
     */
    public function setBody($body)
    {
        if (isset($body) !== true) {
            return $this;
        }

        $this->body = $body;

        return $this;
    }

    /**
     * @return string
     */
    protected function getURI()
    {
        $index = $this->index;
        $type = $this->type;
        $uri = "/_search";

        if (isset($index) === true && isset($type) === true) {
            $uri = "/$index/$type/_search";
        } elseif (isset($index) === true) {
            $uri = "/$index/_search";
        } elseif (isset($type) === true) {
            $uri = "/_all/$type/_search";
        }

        return $uri;
    }

    /**
     * @return string[]
     */
    protected function getParamWhitelist()
    {
        return [
            'analyzer',
            'analyze_wildcard',
            'default_operator',
            'df',
            'explain',
            'fields',
            'fielddata_fields',
            'filter_path',
            'from',
            'ignore_unavailable',
            'allow_no_indices',
            'expand_wildcards',
            'indices_boost',
            'lenient',
            'lowercase_expanded_terms',
            'preference',
            'q',
            'query_cache',
            'request_cache',
            'routing',
            'scroll',
            'search_type',
            'size',
            'sort',
            'source',
            '_source',
            '_source_exclude',
            '_source_include',
            'terminate_after',
            'stats',
            'suggest_field',
            'suggest_mode',
            'suggest_size',
            'suggest_text',
            'timeout',
            'track_scores',
            'version',
            'request_cache',
        ];
    }

    /**
     * @return string
     */
    protected function getMethod()
    {
        return 'GET';
    }
}
