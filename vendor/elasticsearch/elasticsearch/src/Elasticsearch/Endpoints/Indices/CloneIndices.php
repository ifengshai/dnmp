<?php
/**
 * Elasticsearch PHP client
 *
 * @link      https://github.com/elastic/elasticsearch-php/
 * @copyright Copyright (c) Elasticsearch B.V (https://www.elastic.co)
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @license   https://www.gnu.org/licenses/lgpl-2.1.html GNU Lesser General Public License, Version 2.1 
 * 
 * Licensed to Elasticsearch B.V under one or more agreements.
 * Elasticsearch B.V licenses this file to you under the Apache 2.0 License or
 * the GNU Lesser General Public License, Version 2.1, at your option.
 * See the LICENSE file in the project root for more information.
 */
declare(strict_types = 1);

namespace Elasticsearch\Endpoints\Indices;

use Elasticsearch\Common\Exceptions\RuntimeException;
use Elasticsearch\Endpoints\AbstractEndpoint;

/**
 * Class CloneIndices
 * Elasticsearch API name indices.clone
 *
 * NOTE: this file is autogenerated using util/GenerateEndpoints.php
 * and Elasticsearch 7.12.0-SNAPSHOT (e1f8563f995198c160f7d84a6365bddc6ba0cdf3)
 */
class CloneIndices extends AbstractEndpoint
{
    protected $target;

    public function getURI(): string
    {
        $index = $this->index ?? null;
        $target = $this->target ?? null;

        if (isset($index) && isset($target)) {
            return "/$index/_clone/$target";
        }
        throw new RuntimeException('Missing parameter for the endpoint indices.clone');
    }

    public function getParamWhitelist(): array
    {
        return [
            'timeout',
            'master_timeout',
            'wait_for_active_shards'
        ];
    }

    public function getMethod(): string
    {
        return 'PUT';
    }

    public function setBody($body): CloneIndices
    {
        if (isset($body) !== true) {
            return $this;
        }
        $this->body = $body;

        return $this;
    }

    public function setTarget($target): CloneIndices
    {
        if (isset($target) !== true) {
            return $this;
        }
        $this->target = $target;

        return $this;
    }
}
