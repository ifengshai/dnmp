<?php
/**
 * Class BaseElasticsearch.php
 * @author crasphb
 * @date   2021/4/1 14:08
 */

namespace app\admin\controller\elasticsearch;

use app\common\controller\Backend;
use Elasticsearch\ClientBuilder;
use think\Request;

class BaseElasticsearch extends Backend
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

    protected $esClient;

    protected $noNeedLogin = ['*'];

    public function __construct(Request $request = null)
    {
        parent::__construct($request);

        //es配置
        $params = [
            '127.0.0.1:9200',
        ];
        //获取es的实例
        $this->esClient = ClientBuilder::create()->setHosts($params)->build();
    }

    /**
     * 订单的索引
     *
     * @author crasphb
     * @date   2021/4/1 14:46
     */
    public function createOrderIndex()
    {
        $selfProperties = [
            'id' => [
                'type' => 'integer',
            ],
            'site' => [
                'type' => 'integer',
            ],
            'increment_id'       => [
                'type' => 'keyword',
            ],
            'status'  => [
                'type' => 'keyword',
            ],
            'store_id'      => [
                'type' => 'integer',
            ],
            'base_grand_total' => [
                'type' => 'scaled_float',
                'scaling_factor' => 10000
            ],
            'total_qty_ordered'        => [
                'type' => 'integer',
            ],
            'total_item_count'   => [
                'type' => 'integer',
            ],
            'order_type'       => [
                'type' => 'integer',
            ],
            'order_prescription_type'  => [
                'type' => 'integer',
            ],
            'base_currency_code'      => [
                'type' => 'keyword',
            ],
            'shipping_method' => [
                'type' => 'keyword',
            ],
            'shipping_title'        => [
                'type' => 'keyword',
            ],
            'country_id'   => [
                'type' => 'keyword',
            ],
            'region'       => [
                'type' => 'keyword',
            ],
            'region_id'  => [
                'type' => 'keyword',
            ],
            'city'      => [
                'type' => 'keyword',
            ],
            'street' => [
                'type' => 'keyword',
            ],
            'postcode'        => [
                'type' => 'keyword',
            ],
            'telephone'   => [
                'type' => 'keyword',
            ],
            'customer_email'       => [
                'type' => 'keyword',
            ],
            'customer_firstname'  => [
                'type' => 'keyword',
            ],
            'taxno'   => [
                'type' => 'keyword',
            ],
            'base_to_order_rate'       => [
                'type' => 'keyword',
            ],
            'payment_method'  => [
                'type' => 'keyword',
            ],
            'mw_rewardpoint_discount'   => [
                'type' => 'scaled_float',
                'scaling_factor' => 10000
            ],
            'last_trans_id'       => [
                'type' => 'keyword',
            ],
            'mw_rewardpoint'  => [
                'type' => 'scaled_float',
                'scaling_factor' => 10000
            ],
            'base_shipping_amount'   => [
                'type' => 'scaled_float',
                'scaling_factor' => 10000
            ],
            'payment_time'       => [
                'type' => 'date',
            ],
            'order_currency_code'  => [
                'type' => 'keyword',
            ],
            'firstname'   => [
                'type' => 'keyword',
            ],
            'lastname'       => [
                'type' => 'keyword',
            ],
            'area'  => [
                'type' => 'keyword',
            ],
        ];
        $properties = array_merge($selfProperties, $this->commonProperties);
        $this->createIndex('mojing_order', $properties);
    }

    /**
     * 创建索引
     *
     * @param string $indexName 索引名称
     * @param array  $properties mapping数组
     *
     * @return array|mixed
     * @author crasphb
     * @date   2021/4/1 14:14
     */
    public function createIndex(string $indexName = '', array $properties = [])
    {
        $params = [
            'index' => $indexName,
            'body'  => [
                'settings' => [
                    'number_of_shards'   => 3,
                    'number_of_replicas' => 5,
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
     * 删除索引
     * @param $indexName
     *
     * @return array
     * @author crasphb
     * @date   2021/4/1 15:23
     */
    public function deleteIndex() {
        $indexName = 'mojing_order';
        $params = ['index' => $indexName];
        return $this->esClient->indices()->delete($params);
    }
}