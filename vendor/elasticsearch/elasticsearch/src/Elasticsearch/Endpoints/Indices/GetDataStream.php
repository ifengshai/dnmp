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

use Elasticsearch\Endpoints\AbstractEndpoint;

/**
 * Class GetDataStream
 * Elasticsearch API name indices.get_data_stream
 *
 * NOTE: this file is autogenerated using util/GenerateEndpoints.php
 * and Elasticsearch 7.11.0 (8ced7813d6f16d2ef30792e2fcde3e755795ee04)
 */
class GetDataStream extends AbstractEndpoint
{
    protected $name;

    public function getURI(): string
    {
        $name = $this->name ?? null;

        if (isset($name)) {
            return "/_data_stream/$name";
        }
        return "/_data_stream";
    }

    public function getParamWhitelist(): array
    {
        return [
            'expand_wildcards'
        ];
    }

    public function getMethod(): string
    {
        return 'GET';
    }

    public function setName($name): GetDataStream
    {
        if (isset($name) !== true) {
            return $this;
        }
        if (is_array($name) === true) {
            $name = implode(",", $name);
        }
        $this->name = $name;

        return $this;
    }
}
