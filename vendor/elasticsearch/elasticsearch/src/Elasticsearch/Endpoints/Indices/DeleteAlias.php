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
 * Class DeleteAlias
 * Elasticsearch API name indices.delete_alias
 *
 * NOTE: this file is autogenerated using util/GenerateEndpoints.php
 * and Elasticsearch 7.11.0 (8ced7813d6f16d2ef30792e2fcde3e755795ee04)
 */
class DeleteAlias extends AbstractEndpoint
{
    protected $name;

    public function getURI(): string
    {
        if (isset($this->index) !== true) {
            throw new RuntimeException(
                'index is required for delete_alias'
            );
        }
        $index = $this->index;
        if (isset($this->name) !== true) {
            throw new RuntimeException(
                'name is required for delete_alias'
            );
        }
        $name = $this->name;

        return "/$index/_alias/$name";
    }

    public function getParamWhitelist(): array
    {
        return [
            'timeout',
            'master_timeout'
        ];
    }

    public function getMethod(): string
    {
        return 'DELETE';
    }

    public function setName($name): DeleteAlias
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
