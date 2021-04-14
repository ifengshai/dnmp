<?php
/**
 * Class BaseElasticsearch.php
 * @author crasphb
 * @date   2021/4/1 14:08
 */

namespace app\admin\controller\elasticsearch;

use app\common\controller\Backend;
use app\service\elasticsearch\EsFormatData;
use app\service\elasticsearch\EsService;
use Elasticsearch\ClientBuilder;
use think\Request;

class BaseElasticsearch extends Backend
{


    public $esService = null;
    public $esFormatData = null;
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
        $this->esService = new EsService($this->esClient);
        $this->esFormatData = new EsFormatData();
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
            'total_item_count'        => [
                'type' => 'integer',
            ],
            'order_type'              => [
                'type' => 'integer',
            ],
            'order_prescription_type' => [
                'type' => 'integer',
            ],
            'base_currency_code'      => [
                'type' => 'keyword',
            ],
            'shipping_method'         => [
                'type' => 'keyword',
            ],
            'shipping_title'          => [
                'type' => 'keyword',
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
            'city'                    => [
                'type' => 'keyword',
            ],
            'street'                  => [
                'type' => 'keyword',
            ],
            'postcode'                => [
                'type' => 'keyword',
            ],
            'telephone'               => [
                'type' => 'keyword',
            ],
            'customer_email'          => [
                'type' => 'keyword',
            ],
            'customer_firstname'      => [
                'type' => 'keyword',
            ],
            'taxno'                   => [
                'type' => 'keyword',
            ],
            'base_to_order_rate'      => [
                'type' => 'keyword',
            ],
            'payment_method'          => [
                'type' => 'keyword',
            ],
            'mw_rewardpoint_discount' => [
                'type'           => 'scaled_float',
                'scaling_factor' => 10000,
            ],
            'last_trans_id'           => [
                'type' => 'keyword',
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
            ],
            'order_currency_code'     => [
                'type' => 'keyword',
            ],
            'firstname'               => [
                'type' => 'keyword',
            ],
            'lastname'                => [
                'type' => 'keyword',
            ],
            'area'                    => [
                'type' => 'keyword',
            ],
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
                'type'           => 'integer',
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
                'type'           => 'integer',
            ],
            'complete_num'                 => [
                'type'           => 'integer',
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
            'id'                      => [
                'type' => 'integer',
            ],
            'site'                    => [
                'type' => 'integer',
            ],
            'status'                  => [
                'type' => 'keyword',
            ],
            'create_time'            => [
                'type' => 'date',
            ],
            'update_time'            => [
                'type' => 'date',
            ],
            'update_time_day'            => [
                'type' => 'date',
            ],

        ];

        $this->esService->createIndex('mojing_cart', $selfProperties);
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
}