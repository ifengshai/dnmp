<?php
/**
 * Class BaseElasticsearch.php
 * @author crasphb
 * @date   2021/4/1 14:08
 */

namespace app\admin\controller\elasticsearch;

use app\common\controller\Backend;
use app\enum\Site;
use app\service\elasticsearch\EsFormatData;
use app\service\elasticsearch\EsService;
use Elasticsearch\ClientBuilder;
use think\Env;
use think\Log;
use think\Request;

class BaseElasticsearch extends Backend
{


    public $esService = null;
    public $esFormatData = null;
    protected $esClient;
    protected $noNeedLogin = ['*'];
    public $site = [
        Site::ZEELOOL,
        Site::VOOGUEME,
        Site::NIHAO,
        Site::ZEELOOL_DE,
        Site::ZEELOOL_JP,
        Site::ZEELOOL_ES,
        Site::VOOGUEME_ACC,
    ];
    public $status = [
        'free_processing',
        'processing',
        'complete',
        'paypal_reversed',
        'payment_review',
        'paypal_canceled_reversal',
        'delivered',
        'delivery'
    ];

    public function __construct(Request $request = null)
    {
        parent::__construct($request);

        try{
            //es配置
            $params = [
                Env::get('es.es_host','127.0.0.1:9200')
            ];
            //获取es的实例
            $this->esClient = ClientBuilder::create()->setHosts($params)->build();
            $this->esService = new EsService($this->esClient);
            $this->esFormatData = new EsFormatData();
        }catch (\Exception $e){
            Log::error('es:' . $e->getMessage());
        }

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
            'id'                      => [
                'type' => 'integer',
            ],
            'site'                    => [
                'type' => 'integer',
            ],
            'increment_id'            => [
                'type' => 'keyword',
            ],
            'customer_id'            => [
                'type' => 'keyword',
            ],
            'quote_id'            => [
                'type' => 'keyword',
            ],
            'status'                  => [
                'type' => 'keyword',
            ],
            'store_id'                => [
                'type' => 'integer',
            ],
            'base_grand_total'        => [
                'type'           => 'scaled_float',
                'scaling_factor' => 10000,
            ],
            'total_qty_ordered'       => [
                'type' => 'integer',
            ],
            'order_type'              => [
                'type' => 'integer',
            ],
            'order_prescription_type' => [
                'type' => 'integer',
            ],
            'shipping_method'         => [
                'type' => 'keyword',
            ],
            'shipping_title'          => [
                'type' => 'keyword',
            ],
            'shipping_method_type'    => [
                'type' => 'integer',
            ],
            'country_id'              => [
                'type' => 'keyword',
            ],
            'region'                  => [
                'type' => 'keyword',
            ],
            'region_id'               => [
                'type' => 'keyword',
            ],
            'payment_method'          => [
                'type' => 'keyword',
            ],
            'mw_rewardpoint_discount' => [
                'type'           => 'scaled_float',
                'scaling_factor' => 10000,
            ],
            'mw_rewardpoint'          => [
                'type'           => 'scaled_float',
                'scaling_factor' => 10000,
            ],
            'base_shipping_amount'    => [
                'type'           => 'scaled_float',
                'scaling_factor' => 10000,
            ],
            'payment_time'            => [
                'type' => 'date',
            ]
        ];

        $this->esService->createIndex('mojing_order', $selfProperties);
    }

    /**
     * 每日情况统计的索引
     *
     * @author crasphb
     * @date   2021/4/14 10:14
     */
    public function createDatacenterDayIndex()
    {
        $selfProperties = [
            'id'                           => [
                'type' => 'integer',
            ],
            'site'                         => [
                'type' => 'integer',
            ],
            'active_user_num'              => [
                'type' => 'integer',
            ],
            'register_num'                 => [
                'type' => 'integer',
            ],
            'login_user_num'               => [
                'type' => 'integer',
            ],
            'vip_user_num'                 => [
                'type' => 'integer',
            ],
            'sum_order_num'                => [
                'type' => 'integer',
            ],
            'order_num'                    => [
                'type' => 'integer',
            ],
            'sales_total_money'            => [
                'type'           => 'scaled_float',
                'scaling_factor' => 100,
            ],
            'shipping_total_money'         => [
                'type'           => 'scaled_float',
                'scaling_factor' => 100,
            ],
            'order_unit_price'             => [
                'type'           => 'scaled_float',
                'scaling_factor' => 100,
            ],
            'sessions'                     => [
                'type' => 'integer',
            ],
            'update_add_cart_rate'         => [
                'type'           => 'scaled_float',
                'scaling_factor' => 100,
            ],
            'add_cart_rate'                => [
                'type'           => 'scaled_float',
                'scaling_factor' => 100,
            ],
            'session_rate'                 => [
                'type'           => 'scaled_float',
                'scaling_factor' => 100,
            ],
            'new_cart_num'                 => [
                'type' => 'integer',
            ],
            'update_cart_num'              => [
                'type' => 'integer',
            ],
            'cart_rate'                    => [
                'type'           => 'scaled_float',
                'scaling_factor' => 100,
            ],
            'update_cart_cart'             => [
                'type'           => 'scaled_float',
                'scaling_factor' => 100,
            ],
            'replacement_order_num'        => [
                'type' => 'integer',
            ],
            'online_celebrity_order_num'   => [
                'type' => 'integer',
            ],
            'replacement_order_total'      => [
                'type' => 'integer',
            ],
            'online_celebrity_order_total' => [
                'type'           => 'scaled_float',
                'scaling_factor' => 100,
            ],
            'order_total_midnum'           => [
                'type'           => 'scaled_float',
                'scaling_factor' => 100,
            ],
            'order_total_standard'         => [
                'type'           => 'scaled_float',
                'scaling_factor' => 100,
            ],
            'landing_num'                  => [
                'type'           => 'scaled_float',
                'scaling_factor' => 100,
            ],
            'detail_num'                   => [
                'type' => 'integer',
            ],
            'cart_num'                     => [
                'type' => 'integer',
            ],
            'complete_num'                 => [
                'type' => 'integer',
            ],
            'create_user_change_rate'      => [
                'type'           => 'scaled_float',
                'scaling_factor' => 100,
            ],
            'update_user_change_rate'      => [
                'type'           => 'scaled_float',
                'scaling_factor' => 100,
            ],
            'virtual_stock'                => [
                'type' => 'integer',
            ],
            'glass_in_sale_num'            => [
                'type' => 'integer',
            ],
            'glass_presell_num'            => [
                'type' => 'integer',
            ],
            'glass_shelves_num'            => [
                'type' => 'integer',
            ],
            'box_in_sale_num'              => [
                'type' => 'integer',
            ],
            'box_presell_num'              => [
                'type' => 'integer',
            ],
            'box_shelves_num'              => [
                'type' => 'integer',
            ],
        ];

        $this->esService->createIndex('mojing_datacenterday', $selfProperties);
    }

    /**
     * 购物车的索引
     *
     * @author crasphb
     * @date   2021/4/14 10:15
     */
    public function createCartIndex()
    {
        $selfProperties = [
            'id'              => [
                'type' => 'integer',
            ],
            'entity_id'            => [
                'type' => 'keyword',
            ],
            'site'            => [
                'type' => 'integer',
            ],
            'base_grand_total'        => [
                'type'           => 'scaled_float',
                'scaling_factor' => 10000,
            ],
            'status'          => [
                'type' => 'keyword',
            ],
            'create_time'     => [
                'type' => 'date',
            ],
            'update_time'     => [
                'type' => 'date',
            ],
            'update_time_day' => [
                'type' => 'date',
            ],
            'update_time_hour' => [
                'type' => 'keyword',
            ],

        ];

        $this->esService->createIndex('mojing_cart', $selfProperties);
    }

    /**
     * 创建网站用户的索引
     * @author crasphb
     * @date   2021/4/21 10:22
     */
    public function createCustomerIndex()
    {
        $selfProperties = [
            'id'              => [
                'type' => 'integer',
            ],
            'site'            => [
                'type' => 'integer',
            ],
            'email'          => [
                'type' => 'keyword',
            ],
            'group_id'          => [
                'type' => 'integer',
            ],
            'resouce'          => [
                'type' => 'integer',
            ],
            'is_vip'          => [
                'type' => 'integer',
            ],
            'store_id'          => [
                'type' => 'integer',
            ],
            'create_time'     => [
                'type' => 'date',
            ],
            'update_time'     => [
                'type' => 'date',
            ],
            'update_time_day' => [
                'type' => 'date',
            ],

        ];

        $this->esService->createIndex('mojing_customer', $selfProperties);
    }

    /**
     * 物流的索引
     *
     * @author mjj
     * @date   2021/4/14 15:34:52
     */
    public function createTrackIndex()
    {
        $selfProperties = [
            'id'                 => [
                'type' => 'integer',
            ],
            'order_node' => [
                'type' => 'integer',
            ],
            'node_type'          => [
                'type' => 'integer',
            ],
            'site' => [
                'type' => 'integer',
            ],
            'order_id' => [
                'type' => 'integer',
            ],
            'order_number' => [
                'type' => 'keyword',
            ],
            'shipment_type' => [
                'type' => 'keyword',
            ],
            'shipment_data_type' => [
                'type' => 'keyword',
            ],
            'track_number' => [
                'type' => 'keyword',
            ],
            'delivery_time'      => [
                'type' => 'keyword',
            ],
            'delivery_error_flag'      => [
                'type' => 'integer',
            ],
            'signing_time'       => [
                'type' => 'date',
            ],
            'shipment_last_msg' => [
                'type' => 'keyword',
            ],
            'delievered_days' => [
                'type' => 'integer',
            ],
            'wait_time' => [
                'type' => 'integer',
            ],
        ];

        $this->esService->createIndex('mojing_track', $selfProperties);
    }

    /**
     * 删除索引
     *
     * @return array
     * @author crasphb
     * @date   2021/4/1 15:23
     */
    public function deleteIndex()
    {
        return $this->esService->deleteIndex('mojing_order');
    }
    /**
     * 通过id更新order_node表中es条目
     * @param $index  索引
     * @param $data   数组数据
     * @author mjj
     * @date   2021/4/22 16:16:09
     */
    public function updateEsById($index,$data){
        $this->esService->updateEs($index,$data);
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
    public function formatDate($value,$date)
    {
        $format =  [
            'year'       => date('Y', $date),
            'month'      => date('m', $date),
            'month_date' => date('Ym', $date),
            'day'        => date('d', $date),
            'day_date'   => date('Ymd', $date),
            'hour'       => date('H', $date),
            'hour_date'  => date('YmdH', $date),
        ];
        return array_merge($value, $format);
    }
}