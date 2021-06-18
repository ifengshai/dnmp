<?php
/**
 * Class EsService.php
 * @package app\service\elasticsearch
 * @author  crasphb
 * @date    2021/4/12 11:38
 */

namespace app\service\elasticsearch;


use Elasticsearch\Client;
use think\Log;

class EsService
{
    public $commonProperties = [
        'created_at' => [
            'type' => 'date',
        ],
        'updated_at' => [
            'type' => 'date',
        ],
        'year'       => [
            'type' => 'keyword',
        ],
        'month'      => [
            'type' => 'keyword',
        ],
        'month_date' => [
            'type' => 'date',
        ],
        'day'        => [
            'type' => 'keyword',
        ],
        'day_date'   => [
            'type' => 'date',
        ],
        'hour'       => [
            'type' => 'keyword',
        ],
        'hour_date'  => [
            'type' => 'date',
        ],
    ];
    protected $esClient = null;

    public function __construct(Client $esClient)
    {
        $this->esClient = $esClient;
    }

    /**
     * 订单的索引
     *
     * @param string $index
     * @param array  $selfProperties
     *
     * @author crasphb
     * @date   2021/4/1 14:46
     */
    public function createIndex($index = '', array $selfProperties = [])
    {

        $properties = array_merge($selfProperties, $this->commonProperties);
        $this->createEsIndex($index, $properties);
    }

    /**
     * 创建索引
     *
     * @param  $indexName  索引名称
     * @param   $properties mapping数组
     *
     * @return array|mixed
     * @author crasphb
     * @date   2021/4/1 14:14
     */
    public function createEsIndex($indexName = '', array $properties = [])
    {
        $params = [
            'index' => $indexName,
            'body'  => [
                'settings' => [
                    'number_of_shards'   => 3,
                    'number_of_replicas' => 0,
                ],
                'mappings' => [
                    '_source'    => [
                        'enabled' => true,
                    ],
                    'properties' => $properties,
                ],
            ],
        ];
        try {
            return $this->esClient->indices()->create($params);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $msg = json_decode($msg, true);

            return $msg;
        }
    }

    /**
     * 添加数据
     *
     * @param $indexName
     * @param $view
     *
     * @return mixed
     * @author crasphb
     * @date   2021/4/1 15:20
     */
    public function addToEs($indexName, $view)
    {
        $params = [
            'index' => $indexName,
            'type'  => '_doc',
            'id'    => $view['id'],
            'body'  => $view,
        ];

        return $this->esClient->index($params);
    }

    public function addMutilToEs($indexName, $view)
    {
        $params = [
            'index' => $indexName,
            'type'  => '_doc',
        ];
        foreach ($view as $key => $val) {
            $params['body'][] = [
                'create' => [    #注意create也可换成index
                    '_id' => $val['id'],
                ],
            ];

            $params['body'][] = $val;
        }
        try {
            return $this->esClient->bulk($params);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

    }

    /**
     * 批量更新
     * @param $indexName
     * @param $view
     *
     * @return array|callable
     * @author huangbinbin
     * @date   2021/6/18 18:20
     */
    public function updateMutilToEs($indexName, $view)
    {
        foreach ($view as $key => $val) {
            $params['body'][] = [
                'index' => [
                    '_index' => $indexName,
                    '_type'  => '_doc',
                    '_id' => $val['id'],
                ],
            ];

            $params['body'][] = $val;
        }
        try {
            return $this->esClient->bulk($params);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * 更新数据
     * @author mjj
     * @date   2021/4/22 15:35:50
     */
    public function updateEs($indexName, $view)
    {
        $params = [
            'index' => $indexName,
            'type'  => '_doc',
            'id'    => $view['id'],
            'body'  => [
                'doc' => $view,
            ],
        ];
        try {
            return $this->esClient->update($params);
        } catch (\Exception $e) {
            Log::error('es:' . $e->getMessage());
        }

    }
    /**
     * es查询
     *
     * @param $params
     *
     * @return mixed
     * @author crasphb
     * @date   2021/4/12 11:54
     */
    public function search($params)
    {
        try {

            $results = $this->esClient->search($params);

            return $results['aggregations'];
        } catch (\Exception $e) {
            Log::error('es:' . $e->getMessage());
        }
    }

    /**
     * 删除索引
     *
     * @param $indexName
     *
     * @return array
     * @author crasphb
     * @date   2021/4/1 15:23
     */
    public function deleteIndex(string $indexName)
    {
        $params = ['index' => $indexName];

        return $this->esClient->indices()->delete($params);
    }
}