<?php

namespace Elasticsearch\Namespaces;

/**
 * Class NodesNamespace
 *
 * @category Elasticsearch
 * @package  Elasticsearch\Namespaces\NodesNamespace
 * @author   Zachary Tong <zachary.tong@elasticsearch.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache2
 * @link     http://elasticsearch.org
 */
class NodesNamespace extends AbstractNamespace
{
    /**
     * $params['metric']            = (list) Limit the information returned to the specified metrics
     *        ['index_metric']      = (list) Limit the information returned for `indices` metric to the specific index
     * metrics. Isn't used if `indices` (or `all`) metric isn't specified.
     *        ['node_id']           = (list) A comma-separated list of node IDs or names to limit the returned
     * information; use `_local` to return information from the node you're connecting to, leave empty to get
     * information from all nodes
     *        ['completion_fields'] = (list) A comma-separated list of fields for `fielddata` and `suggest` index
     * metric (supports wildcards)
     *        ['fielddata_fields']  = (list) A comma-separated list of fields for `fielddata` index metric (supports
     * wildcards)
     *        ['fields']            = (list) A comma-separated list of fields for `fielddata` and `completion` index
     * metric (supports wildcards)
     *        ['groups']            = (boolean) A comma-separated list of search groups for `search` index metric
     *        ['human']             = (boolean) Whether to return time and byte values in human-readable format.
     * (default: false)
     *        ['level']             = (enum) Return indices stats aggregated at node, index or shard level
     * (node,indices,shards) (default: node)
     *        ['types']             = (list) A comma-separated list of document types for the `indexing` index metric
     *        ['timeout']           = (time) Explicit operation timeout
     *
     * @param $params array Associative array of parameters
     *
     * @return array
     */
    public function stats($params = [])
    {
        $nodeID = $this->extractArgument($params, 'node_id');

        $metric = $this->extractArgument($params, 'metric');

        $index_metric = $this->extractArgument($params, 'index_metric');

        /** @var callback $endpointBuilder */
        $endpointBuilder = $this->endpoints;

        /** @var \Elasticsearch\Endpoints\Nodes\Stats $endpoint */
        $endpoint = $endpointBuilder('Nodes\Stats');
        $endpoint->setNodeID($nodeID)
                 ->setMetric($metric)
                 ->setIndexMetric($index_metric)
                 ->setParams($params);
        $response = $endpoint->performRequest();

        return $endpoint->resultOrFuture($response);
    }

    /**
     * $params['node_id']       = (list) A comma-separated list of node IDs or names to limit the returned information;
     * use `_local` to return information from the node you're connecting to, leave empty to get information from all
     * nodes
     *        ['metric']        = (list) A comma-separated list of metrics you wish returned. Leave empty to return
     * all.
     *        ['flat_settings'] = (boolean) Return settings in flat format (default: false)
     *        ['human']         = (boolean) Whether to return time and byte values in human-readable format. (default:
     * false)
     *        ['timeout']       = (time) Explicit operation timeout
     *
     * @param $params array Associative array of parameters
     *
     * @return array
     */
    public function info($params = [])
    {
        $nodeID = $this->extractArgument($params, 'node_id');
        $metric = $this->extractArgument($params, 'metric');

        /** @var callback $endpointBuilder */
        $endpointBuilder = $this->endpoints;

        /** @var \Elasticsearch\Endpoints\Nodes\Info $endpoint */
        $endpoint = $endpointBuilder('Nodes\Info');
        $endpoint->setNodeID($nodeID)->setMetric($metric);
        $endpoint->setParams($params);
        $response = $endpoint->performRequest();

        return $endpoint->resultOrFuture($response);
    }

    /**
     * $params['node_id']             = (list) A comma-separated list of node IDs or names to limit the returned
     * information; use `_local` to return information from the node you're connecting to, leave empty to get
     * information from all nodes
     *        ['interval']            = (time) The interval for the second sampling of threads
     *        ['snapshots']           = (number) Number of samples of thread stacktrace (default: 10)
     *        ['threads']             = (number) Specify the number of threads to provide information for (default: 3)
     *        ['ignore_idle_threads'] = (boolean) Don't show threads that are in known-idle places, such as waiting on
     * a socket select or pulling from an empty task queue (default: true)
     *        ['type']                = (enum) The type to sample (default: cpu) (cpu,wait,block)
     *        ['timeout']             = (time) Explicit operation timeout
     *
     * @param $params array Associative array of parameters
     *
     * @return array
     */
    public function hotThreads($params = [])
    {
        $nodeID = $this->extractArgument($params, 'node_id');

        /** @var callback $endpointBuilder */
        $endpointBuilder = $this->endpoints;

        /** @var \Elasticsearch\Endpoints\Nodes\HotThreads $endpoint */
        $endpoint = $endpointBuilder('Nodes\HotThreads');
        $endpoint->setNodeID($nodeID);
        $endpoint->setParams($params);
        $response = $endpoint->performRequest();

        return $endpoint->resultOrFuture($response);
    }

    /**
     * @deprecated
     * $params['node_id'] = (list) A comma-separated list of node IDs or names to perform the operation on; use
     *     `_local` to perform the operation on the node you're connected to, leave empty to perform the operation on
     *     all nodes
     *        ['delay']   = (time) Set the delay for the operation (default: 1s)
     *        ['exit']    = (boolean) Exit the JVM as well (default: true)
     *
     * @param array $params
     *
     * @return array
     */
    public function shutdown($params = [])
    {
        $nodeID = $this->extractArgument($params, 'node_id');

        /** @var callback $endpointBuilder */
        $endpointBuilder = $this->endpoints;

        /** @var \Elasticsearch\Endpoints\Nodes\Shutdown $endpoint */
        $endpoint = $endpointBuilder('Nodes\Shutdown');
        $endpoint->setNodeID($nodeID);
        $endpoint->setParams($params);
        $response = $endpoint->performRequest();

        return $endpoint->resultOrFuture($response);
    }
}
