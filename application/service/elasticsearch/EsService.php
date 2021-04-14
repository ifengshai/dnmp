<?php
/**
 * Class EsService.php
 * @package app\service\elasticsearch
 * @author  crasphb
 * @date    2021/4/12 11:38
 */

namespace app\service\elasticsearch;


use Elasticsearch\Client;

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
    public function createIndex(string $index = '', array $selfProperties = [])
    {

        $properties = array_merge($selfProperties, $this->commonProperties);
        $this->createEsIndex($index, $properties);
    }

    /**
     * 创建索引
     *
     * @param string $indexName  索引名称
     * @param array  $properties mapping数组
     *
     * @return array|mixed
     * @author crasphb
     * @date   2021/4/1 14:14
     */
    public function createEsIndex(string $indexName = '', array $properties = [])
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
     * @param $mergeData
     *
     * @return mixed
     * @author crasphb
     * @date   2021/4/1 15:20
     */
    public function addToEs($indexName, $view, $mergeData)
    {
        $view = array_merge($view, $this->formatDate($mergeData));
        $params = [
            'index' => $indexName,
            'type'  => '_doc',
            'id'    => $view['id'],
            'body'  => $view,
        ];

        return $this->esClient->index($params);
    }

    /**
     * 格式化时间字段，方便后续查询聚合
     *
     * @param $date
     *
     * @return array
     * @author crasphb
     * @date   2021/4/1 15:21
     */
    public function formatDate($date)
    {
        return [
            'year'       => date('Y', $date),
            'month'      => date('m', $date),
            'month_date' => date('Ym', $date),
            'day'        => date('d', $date),
            'day_date'   => date('Ymd', $date),
            'hour'       => date('H', $date),
            'hour_date'  => date('YmdH', $date),
        ];
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
        $results = $this->esClient->search($params);

        return $results['aggregations'];
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