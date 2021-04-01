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

namespace Elasticsearch\Endpoints\Security;

use Elasticsearch\Endpoints\AbstractEndpoint;

/**
 * Class HasPrivileges
 * Elasticsearch API name security.has_privileges
 *
 * NOTE: this file is autogenerated using util/GenerateEndpoints.php
 * and Elasticsearch 7.11.0 (8ced7813d6f16d2ef30792e2fcde3e755795ee04)
 */
class HasPrivileges extends AbstractEndpoint
{
    protected $user;

    public function getURI(): string
    {
        $user = $this->user ?? null;

        if (isset($user)) {
            return "/_security/user/$user/_has_privileges";
        }
        return "/_security/user/_has_privileges";
    }

    public function getParamWhitelist(): array
    {
        return [
            
        ];
    }

    public function getMethod(): string
    {
        return isset($this->body) ? 'POST' : 'GET';
    }

    public function setBody($body): HasPrivileges
    {
        if (isset($body) !== true) {
            return $this;
        }
        $this->body = $body;

        return $this;
    }

    public function setUser($user): HasPrivileges
    {
        if (isset($user) !== true) {
            return $this;
        }
        $this->user = $user;

        return $this;
    }
}
