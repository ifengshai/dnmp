<?php

/**
 * 执行时间：每天一次
 */

namespace app\admin\controller\shell;

use app\admin\controller\elasticsearch\async\AsyncDatacenterDay;
use app\admin\model\order\order\NewOrder;
use app\common\controller\Backend;
use app\enum\Site;
use fast\Excel;
use GuzzleHttp\Client;
use think\Db;
use SchGroup\SeventeenTrack\Connectors\TrackingConnector;
use think\Hook;
use app\admin\model\purchase\SupplierSku;

class TrackReg extends Backend
{
    protected $noNeedLogin = ['*'];
    protected $apiKey = 'F26A807B685D794C676FA3CC76567035';


    public function _initialize()
    {
        parent::_initialize();
        $this->ordernodedetail = new \app\admin\model\OrderNodeDetail();
        $this->ordernode = new \app\admin\model\OrderNode();
    }

    public function site_reg()
    {
        $this->reg_shipment('database.db_zeelool', 1);
        $this->reg_shipment('database.db_voogueme', 2);
        $this->reg_shipment('database.db_nihao', 3);
        $this->reg_shipment('database.db_meeloog', 4);
        $this->reg_shipment('database.db_zeelool_es', 9);
        $this->reg_shipment('database.db_zeelool_de', 10);
        $this->reg_shipment('database.db_zeelool_jp', 11);
    }

    /**
     * 批量 注册物流
     * 每天跑一次，查找遗漏注册的物流单号，进行注册操作
     */
    public function reg_shipment($site_str, $site_type)
    {
        $order_shipment = Db::connect($site_str)
            ->table('sales_flat_shipment_track')->alias('a')
            ->join(['sales_flat_order' => 'b'], 'a.order_id=b.entity_id')
            ->field('a.entity_id,a.order_id,a.track_number,a.title,a.updated_at,a.created_at,b.increment_id')
            ->where('a.created_at', '>=', '2020-03-31 00:00:00')
            ->where('a.handle', '=', '0')
            ->group('a.order_id')
            ->select();
        foreach ($order_shipment as $k => $v) {
            $title = strtolower(str_replace(' ', '-', $v['title']));
            //根据物流单号查询发货物流渠道
            // $shipment_data_type = Db::connect('database.db_delivery')->table('ld_deliver_order')->where(['track_number' => $v['track_number'], 'increment_id' => $v['increment_id']])->value('agent_way_title');

            $carrier = $this->getCarrier($title);
            $shipment_reg[$k]['number'] = $v['track_number'];
            $shipment_reg[$k]['carrier'] = $carrier['carrierId'];
            $shipment_reg[$k]['order_id'] = $v['order_id'];


            // $list[$k]['order_node'] = 2;
            // $list[$k]['node_type'] = 7; //出库
            // $list[$k]['create_time'] = $v['created_at'];
            // $list[$k]['site'] = $site_type;
            // $list[$k]['order_id'] = $v['order_id'];
            // $list[$k]['order_number'] = $v['increment_id'];
            // $list[$k]['shipment_type'] = $v['title'];
            // $list[$k]['shipment_data_type'] = $shipment_data_type;
            // $list[$k]['track_number'] = $v['track_number'];
            // $list[$k]['content'] = 'Leave warehouse, Waiting for being picked up.';

            // $data['order_node'] = 2;
            // $data['node_type'] = 7;
            // $data['update_time'] = $v['created_at'];
            // $data['shipment_type'] = $v['title'];
            // $data['shipment_data_type'] = $shipment_data_type;
            // $data['track_number'] = $v['track_number'];
            // $data['delivery_time'] = $v['created_at'];
            // Db::name('order_node')->where(['order_id' => $v['order_id'], 'site' => $site_type])->update($data);
        }
        // if ($list) {
        //     $this->ordernodedetail->saveAll($list);
        // }
        if ($shipment_reg) {
            $order_group = array_chunk($shipment_reg, 40);
            $trackingConnector = new TrackingConnector($this->apiKey);
            $order_ids = [];
            foreach ($order_group as $key => $val) {
                $aa = $trackingConnector->registerMulti($val);

                //请求接口更改物流表状态
                $order_ids = implode(',', array_column($val, 'order_id'));
                $params['ids'] = $order_ids;
                $params['site'] = $site_type;
                $res = $this->setLogisticsStatus($params);
                if ($res->status !== 200) {
                    echo $site_str . '更新失败:' . $order_ids . "\n";
                }
                $order_ids = [];

                usleep(500000);
            }
        }

        echo $site_str . ' is ok' . "\n";
    }

    /**
     * 处理物流商类型为空的数据
     *
     * @Description
     * @author wpl
     * @since 2020/11/13 15:05:24 
     * @return void
     */
    public function process_shipment_type()
    {
        $list = $this->ordernode->where('shipment_data_type is null and shipment_type is not null')->select();
        $list = collection($list)->toArray();
        $params = [];
        foreach ($list as $k => $v) {
            //根据物流单号查询发货物流渠道
            $shipment_data_type = Db::connect('database.db_mojing_order')->table('fa_order_process')->where([
                'track_number' => $v['track_number'],
                'increment_id' => $v['order_number'],
            ])->value('agent_way_title');
            $params[$k]['id'] = $v['id'];
            $params[$k]['shipment_data_type'] = $shipment_data_type;
            $this->ordernodedetail->where([
                'order_number' => $v['order_number'],
                'track_number' => $v['track_number'],
            ])->update(['shipment_data_type' => $shipment_data_type]);
        }
        if ($params) {
            $this->ordernode->saveAll($params);
        }
        echo "ok";
    }

    /**
     * 获取快递号
     *
     * @param $title
     *
     * @return mixed|string
     */
    protected function getCarrier($title)
    {
        $carrierId = '';
        if (stripos($title, 'post') !== false) {
            $carrierId = 'chinapost';
            $title = 'China Post';
        } elseif (stripos($title, 'ems') !== false) {
            $carrierId = 'chinaems';
            $title = 'China Ems';
        } elseif (stripos($title, 'dhl') !== false) {
            $carrierId = 'dhl';
            $title = 'DHL';
        } elseif (stripos($title, 'fede') !== false) {
            $carrierId = 'fedex';
            $title = 'Fedex';
        } elseif (stripos($title, 'usps') !== false) {
            $carrierId = 'usps';
            $title = 'Usps';
        } elseif (stripos($title, 'yanwen') !== false) {
            $carrierId = 'yanwen';
            $title = 'YANWEN';
        } elseif (stripos($title, 'cpc') !== false) {
            $carrierId = 'cpc';
            $title = 'Canada Post';
        } elseif (stripos($title, 'sua') !== false) {
            $carrierId = 'sua';
            $title = 'SUA';
        } elseif (stripos($title, 'cod') !== false) {
            $carrierId = 'cod';
            $title = 'COD';
        } elseif (stripos($title, 'tnt') !== false) {
            $carrierId = 'tnt';
            $title = 'TNT';
        }

        $carrier = [
            'dhl'       => '100001',
            'chinapost' => '03011',
            'chinaems'  => '03013',
            'cpc'       => '03041',
            'fedex'     => '100003',
            'usps'      => '21051',
            'yanwen'    => '190012',
            'sua'       => '190111',
            'cod'       => '100040',
            'tnt'       => '100004',
        ];
        if ($carrierId) {
            return ['title' => $title, 'carrierId' => $carrier[$carrierId]];
        }

        return ['title' => $title, 'carrierId' => $carrierId];
    }

    /**
     * 更新物流表状态 handle 改为1
     *
     * @Description
     * @author wpl
     * @since 2020/05/18 18:16:48 
     * @return void
     */
    protected function setLogisticsStatus($params)
    {
        switch ($params['site']) {
            case 1:
                $url = config('url.zeelool_url');
                break;
            case 2:
                $url = config('url.voogueme_url');
                break;
            case 3:
                $url = config('url.nihao_url');
                break;
            case 4:
                $url = config('url.meeloog_url');
                break;
            case 9:
                $url = config('url.zeelooles_url');
                break;
            case 10:
                $url = config('url.zeeloolde_url');
                break;
            case 11:
                $url = config('url.zeelooljp_url');
                break;
            default:
                return false;
                break;
        }

        if ($params['site'] == 4) {
            $url = $url . 'rest/mj/update_order_handle';
        } else {
            $url = $url . 'magic/order/logistics';
        }
        unset($params['site']);
        $client = new Client(['verify' => false]);
        //请求URL
        $response = $client->request('POST', $url, ['form_params' => $params]);
        $body = $response->getBody();
        $stringBody = (string)$body;
        $res = json_decode($stringBody);

        return $res;
    }

    /**
     * zendesk10分钟更新前20分钟的数据
     * @return [type] [description]
     */
    public function zeelool_zendesk()
    {
        $this->zendeskUpateData('zeelool', 1);
        echo 'all ok';
        exit;
    }

    public function voogueme_zendesk()
    {
        $this->zendeskUpateData('voogueme', 2);
        echo 'all ok';
        exit;
    }

    public function nihao_zendesk()
    {
        $this->zendeskUpateData('nihaooptical', 3);
        echo 'all ok';
        exit;
    }

    /**
     * zendesk10分钟更新前20分钟的数据方法
     * @return [type] [description]
     */
    public function zendeskUpateData($siteType, $type)
    {

        try {
            $this->model = new \app\admin\model\zendesk\Zendesk;
            $ticketIds = (new \app\admin\controller\zendesk\Notice(request(),
                ['type' => $siteType]))->autoAsyncUpdate($siteType);

            //判断是否存在
            $nowTicketsIds = $this->model->where("type", $type)->column('ticket_id');

            //求交集的更新
            $intersects = array_intersect($ticketIds, $nowTicketsIds);
            //求差集新增
            $diffs = array_diff($ticketIds, $nowTicketsIds);
            //更新
            foreach ($intersects as $intersect) {
                (new \app\admin\controller\zendesk\Notice(request(),
                    ['type' => $siteType, 'id' => $intersect]))->auto_update();
                echo $intersect . 'is ok' . "\n";
            }
            //新增
            foreach ($diffs as $diff) {
                (new \app\admin\controller\zendesk\Notice(request(), ['type' => $siteType, 'id' => $diff]))->auto_create();
                echo $diff . 'ok' . "\n";
            }
            echo 'all ok';
            exit;
        } catch (\Exception $e) {
            file_put_contents('/var/www/mojing/runtime/log/zendesk.log', 'zendeskUpateData:站点：' . $type . ' 失败:' . $e->getMessage() . "\r\n", FILE_APPEND);
            echo 'error';
        }

    }

    /**
     * 获取前一天有效SKU销量
     * 记录当天有效SKU
     *
     * @Description
     * @author wpl
     * @since 2020/07/31 16:52:46 
     * @return void
     */
    public function get_sku_sales_num()
    {
        //记录当天上架的SKU 
        $itemPlatformSku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $skuSalesNum = new \app\admin\model\SkuSalesNum();
        $order = new \app\admin\model\order\order\NewOrder();
        $list = $itemPlatformSku->field('sku,platform_sku,platform_type as site')->where([
            'outer_sku_status' => 1,
            'platform_type'    => ['<>', 8],
        ])->select();
        $list = collection($list)->toArray();
        //批量插入当天各站点上架sku
        $skuSalesNum->saveAll($list);

        //查询昨天上架SKU 并统计当天销量
        $data = $skuSalesNum->whereTime('createtime', 'yesterday')->where('site<>8')->select();
        $data = collection($data)->toArray();
        if ($data) {
            foreach ($data as $k => $v) {
                if ($v['platform_sku']) {
                    $params[$k]['sales_num'] = $order->getSkuSalesNum($v['platform_sku'], $v['site']);
                    $params[$k]['id'] = $v['id'];
                }
            }
            if ($params) {
                $skuSalesNum->saveAll($params);
            }
        }

        echo "ok";
    }

    /**
     * 统计有效天数日均销量 并按30天预估销量分级 - 按站点区分
     * 统计SKU库存健康状态
     *
     * @Description
     * @return void
     * @since  2020/08/01 15:29:23
     * @author wpl
     */
    public function get_days_sales_num()
    {
        try {
            $itemPlatformSku = new \app\admin\model\itemmanage\ItemPlatformSku();
            $skuSalesNum = new \app\admin\model\SkuSalesNum();
            $date = date('Y-m-d 00:00:00');
            $list = $itemPlatformSku->field('id,sku,platform_type as site,stock,platform_sku,grade')->where([
                'outer_sku_status' => 1,
                'platform_type'    => ['<>', 8],
            ])->select();
            $list = collection($list)->toArray();

            foreach ($list as $k => $v) {
                //15天日均销量
                $days15_data = $skuSalesNum->where([
                    'sku'        => $v['sku'],
                    'site'       => $v['site'],
                    'createtime' => ['<', $date],
                ])->field("sum(sales_num) as sales_num,count(*) as num")->limit(15)->order('createtime desc')->select();
                $params['sales_num_15days'] = $days15_data[0]->num > 0 ? round($days15_data[0]->sales_num / $days15_data[0]->num) : 0;

                $days90_data = $skuSalesNum->where([
                    'sku'        => $v['sku'],
                    'site'       => $v['site'],
                    'createtime' => ['<', $date],
                ])->field("sum(sales_num) as sales_num,count(*) as num")->limit(90)->order('createtime desc')->select();
                //90天总销量
                $params['sales_num_90days'] = $days90_data[0]->sales_num ?: 0;
                //90天日均销量
                $sales_num_90days = $days90_data[0]->num > 0 ? round($days90_data[0]->sales_num / $days90_data[0]->num) : 0;
                //90天日均销量
                $params['average_90days_sales_num'] = $sales_num_90days ?: 0;
                //计算等级 30天预估销量
                $num = round($sales_num_90days * 1 * 30);
                if ($num >= 300) {
                    $params['grade'] = 'A+';
                } elseif ($num >= 150 && $num < 300) {
                    $params['grade'] = 'A';
                } elseif ($num >= 90 && $num < 150) {
                    $params['grade'] = 'B';
                } elseif ($num >= 60 && $num < 90) {
                    $params['grade'] = 'C+';
                } elseif ($num >= 30 && $num < 60) {
                    $params['grade'] = 'C';
                } elseif ($num >= 15 && $num < 30) {
                    $params['grade'] = 'D';
                } elseif ($num >= 1 && $num < 15) {
                    $params['grade'] = 'E';
                } else {
                    $params['grade'] = 'F';
                }
                //自然日120天销量
                $days120SalesNum = (new NewOrder())->getSkuSalesNum120days($v['platform_sku'], $v['site']);

                $status = 0;
                if ($v['grade'] == 'F') {
                    $status = 2;
                } elseif ($v['stock'] > 0 && $days120SalesNum > 0) {
                    $stateHealth = floatval(bcdiv($v['stock'], $days120SalesNum, 1));
                    /**
                     * 正常            [0,1)
                     * 高风险         [1,1.2)   F级的放入高风险中
                     * 中风险         [1.2,1.4)
                     * 低风险         [1.4+)
                     * 运营新品：  入库但是未上架的sku
                     */

                    $isStockNew = 0;
                    switch ($v['site']) {
                        case Site::ZEELOOL:
                            $isStockNew = Db::connect('database.db_zeelool_online')
                                ->name('catalog_product_entity')
                                ->where('sku', '=', $v['platform_sku'])
                                ->value('is_stock_new');
                            break;
                        case Site::VOOGUEME:
                            $isStockNew = Db::connect('database.db_voogueme_online')
                                ->name('catalog_product_entity')
                                ->where('sku', '=', $v['platform_sku'])
                                ->value('is_stock_new');;
                            break;
                        case Site::NIHAO:
                            $isStockNew = Db::connect('database.db_nihao_online')
                                ->name('catalog_product_entity')
                                ->where('sku', '=', $v['platform_sku'])
                                ->value('is_stock_new');;
                            break;
                        case Site::ZEELOOL_DE:
                            $isStockNew = Db::connect('database.db_zeelool_de_online')
                                ->name('catalog_product_entity')
                                ->where('sku', '=', $v['platform_sku'])
                                ->value('is_stock_new');;
                            break;
                        case Site::ZEELOOL_JP:
                            $isStockNew = Db::connect('database.db_zeelool_jp_online')
                                ->name('catalog_product_entity')
                                ->where('sku', '=', $v['platform_sku'])
                                ->value('is_stock_new');;
                            break;
                        case Site::WESEEOPTICAL:
                            $isStockNew = Db::connect('database.db_weseeoptical')
                                ->name('goods')
                                ->where('sku', '=', $v['platform_sku'])
                                ->value('is_stock_new');
                            break;
                    }
//                判断运营新品
                    if ($isStockNew == 2) {
                        $status = 5;
                    } else {
//                    正常
                        if ($stateHealth >= 0 && $stateHealth < 1) {
                            $status = 1;
                        } elseif ($stateHealth >= 1 && $stateHealth < 1.2) {
//                        高风险
                            $status = 2;
                        } elseif ($stateHealth >= 1.2 && $stateHealth < 1.4) {
//                        中风险
                            $status = 3;
                        } elseif ($stateHealth >= 1.4) {
//                        低风险
                            $status = 4;
                        }
                    }
                }

                $params['stock_health_status'] = $status;
                $itemPlatformSku->where('id', $v['id'])->update($params);

                echo $v['sku'] . "\n";
            }

            echo "ok";
        } catch (\Throwable $exception) {
            echo "Error Message：" . $exception->getMessage() . PHP_EOL . "Error Line：" . $exception->getLine() . PHP_EOL;
        }
    }


    public function export_get_days_num()
    {
        $item = new \app\admin\model\itemmanage\Item();
        $this->itemplatformsku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $order = new \app\admin\model\order\order\NewOrder();
        $platform = new \app\admin\model\platformmanage\MagentoPlatform();
        $itemPlatformSku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $skuSalesNum = new \app\admin\model\SkuSalesNum();
        $date = date('Y-m-d 00:00:00');
        //获取所有符合条件的sku
        $list = $item->where(['is_open' => 1, 'is_del' => 1, 'category_id' => ['<>', 43]])->column('sku');
        //获取对应供应商关系
        foreach ($list as $key => $value) {

            $where['a.sku'] = ['eq', $value];
            $whe['true_sku'] = ['eq', $value];
            $data[] = Db::name('supplier_sku')->alias('a')
                ->join(['fa_supplier' => 'b'], 'a.supplier_id=b.id')
                ->where($where)
                ->field('a.sku,b.supplier_name')->find();
            $data[$key]['grade'] = Db::name('product_grade')->where($whe)->value('grade');

        }
        foreach ($data as $k => $v) {
            //7天日均销量
            $days7s_data = $skuSalesNum->where([
                'sku'        => $v['sku'],
                'createtime' => ['<', $date],
            ])->limit(7)->order('createtime desc')->column('sales_num');
            $data[$k]['days7s_data'] = array_sum($days7s_data);

            $days30_data = $skuSalesNum->where([
                'sku'        => $v['sku'],
                'createtime' => ['<', $date],
            ])->limit(30)->order('createtime desc')->column('sales_num');
            $data[$k]['days30_data'] = array_sum($days30_data);

            $days90_data = $skuSalesNum->where([
                'sku'        => $v['sku'],
                'createtime' => ['<', $date],
            ])->limit(90)->order('createtime desc')->column('sales_num');
            $data[$k]['days90_data'] = array_sum($days90_data);
        }

        $header = ['SKU', '供应商', '等级', '7天销量', '1个月销量近3个月的销量'];
        $filename = 'sku数据导出.csv';
        Excel::writeCsv($data, $header, $filename);
    }

    //导出1-3月份所有SKU的销量数据
    public function export_get_days_num_copy()
    {
        $item = new \app\admin\model\itemmanage\Item();
        $this->itemplatformsku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $skuSalesNum = new \app\admin\model\SkuSalesNum();
        $startime = '2021-01-01 00:00:00';
        $endtime = '2021-03-31 23:59:59';
        //获取所有符合条件的sku
        $list = $item->where(['is_open' => 1, 'is_del' => 1, 'category_id' => ['<>', 43]])->column('sku');
        //获取对应供应商关系
        foreach ($list as $key => $value) {
            $data[$key]['sku'] = $value; //sku
            $whe['true_sku'] = ['eq', $value];
            $data[$key]['grade'] = Db::name('product_grade')->where($whe)->value('grade');//等级
        }
        foreach ($data as $k => $v) {
            //7天日均销量
            $where['createtime'] = ['between', [$startime, $endtime]];
            $where['sku'] = $v['sku'];
            $sales = $skuSalesNum->where($where)->order('createtime desc')->column('sales_num');
            $data[$k]['sales'] = array_sum($sales);
        }
        $header = ['SKU', '等级', '销量'];
        $filename = '1-3月份所有SKU的销量数据';
        Excel::writeCsv($data, $header, $filename);
    }

    /**
     * 每天9点 根据销量计算产品分级
     *
     * 30天有效销量计算产品等级 - 按sku分等级
     *
     * @Description
     * @author wpl
     * @since 2020/08/01 15:29:23 
     * @return void
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_days_sales_num_all()
    {
        $item = new \app\admin\model\itemmanage\Item();
        $skuSalesNum = new \app\admin\model\SkuSalesNum();
        $platform = new \app\admin\model\platformmanage\MagentoPlatform();
        //查询所有站点
        $siteList = $platform->select();

        $list = $item->where(['is_open' => 1, 'is_del' => 1, 'category_id' => ['<>', 43]])->column('sku');
        $params = [];
        $date = date('Y-m-d 00:00:00');

        //查询SKU绑定关系
        $supplierSku = (new SupplierSku)->getSupplierName();
        foreach ($list as $k => $v) {
            $allnum = 0;
            $dayNum = [];
            foreach ($siteList as $val) {

                $sql = $skuSalesNum->field('sales_num')->where([
                    'sku'        => $v,
                    'createtime' => ['<', $date],
                    'site'       => $val['id'],
                ])->limit(30)->order('createtime desc')->buildSql();
                $num = Db::table($sql . ' a')->sum('a.sales_num');

                $dayNum[] = $skuSalesNum->where([
                    'sku'        => $v,
                    'createtime' => ['<', $date],
                    'site'       => $val['id'],
                ])->count();
                //统计30天有效天数销量
                $allnum += $num;
            }
            $params[$k]['supplier_name'] = $supplierSku[$v]['supplier_name'];
            $params[$k]['purchase_person'] = $supplierSku[$v]['purchase_person'];
            if ($allnum >= 300) {
                $params[$k]['grade'] = 'A+';
            } elseif ($allnum >= 150 && $allnum < 300) {
                $params[$k]['grade'] = 'A';
            } elseif ($allnum >= 90 && $allnum < 150) {
                $params[$k]['grade'] = 'B';
            } elseif ($allnum >= 60 && $allnum < 90) {
                $params[$k]['grade'] = 'C+';
            } elseif ($allnum >= 30 && $allnum < 60) {
                $params[$k]['grade'] = 'C';
            } elseif ($allnum >= 15 && $allnum < 30) {
                $params[$k]['grade'] = 'D';
            } elseif ($allnum >= 1 && $allnum < 15) {
                $params[$k]['grade'] = 'E';
            } else {
                $params[$k]['grade'] = 'F';
            }
            $day = max($dayNum) >= 30 ? 30 : max($dayNum);
            $params[$k]['counter'] = $allnum;
            $params[$k]['days'] = $day;
            $params[$k]['true_sku'] = $v;
            $params[$k]['num'] = $allnum;
            $params[$k]['days_sales_num'] = $day > 0 ? round($allnum / $day) : 0;
            $params[$k]['createtime'] = date('Y-m-d H:i:s');

            echo $v . "\n";
            usleep(20000);
        }
        if ($params) {
            //清空表
            Db::execute("truncate table fa_product_grade;");
            //批量添加
            Db::table('fa_product_grade')->insertAll($params);
        }
        echo "ok";
    }


    /**
     * 计划任务 计划补货 每月7号执行一次 汇总各个平台原始sku相同的品的补货需求数量 加入补货需求单以供采购分配处理 汇总过后更新字段 is_show 的值 列表不显示
     * 2020.09.07 改为每月9号执行一次
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/7/16
     * Time: 15:46
     */
    public function plan_replenishment()
    {
        //补货需求清单表
        $this->model = new \app\admin\model\NewProductMapping();
        //补货需求单子表
        $this->order = new \app\admin\model\purchase\NewProductReplenishOrder();
        //补货需求单主表
        $this->replenish = new \app\admin\model\purchase\NewProductReplenish();
        //统计计划补货数据
        $list = $this->model
            ->where(['is_show' => 1, 'type' => 1])
            ->whereTime('create_time', 'between', [date('Y-m-d H:i:s', strtotime("-1 month")), date('Y-m-d H:i:s')])
            ->group('sku')
            ->column("sku,sum(replenish_num) as sum");
        if (empty($list)) {
            echo('暂时没有紧急补货单需要处理');
            die;
        }
        //统计各个站计划某个sku计划补货的总数 以及比例 用于回写平台sku映射表中
        $sku_list = $this->model
            ->where(['is_show' => 1, 'type' => 1])
            ->whereTime('create_time', 'between', [date('Y-m-d H:i:s', strtotime("-1 month")), date('Y-m-d H:i:s')])
            ->field('id,sku,website_type,replenish_num')
            ->select();
        //根据sku对数组进行重新分配
        $sku_list = $this->array_group_by($sku_list, 'sku');

        //首先插入主表 获取主表id new_product_replenish
        $data['type'] = 1;
        $data['create_person'] = 'Admin';
        $data['create_time'] = date('Y-m-d H:i:s');
        $res = $this->replenish->insertGetId($data);

        //遍历以更新平台sku映射表的 关联补货需求单id 以及各站虚拟仓占比
        $int = 0;
        foreach ($sku_list as $k => $v) {
            //求出此sku在此补货单中的总数量
            $skuWholeNum = array_sum(array_map(function ($val) {
                return $val['replenish_num'];
            }, $v));
            //求出比例赋予新数组
            foreach ($v as $ko => $vo) {
                $date[$int]['id'] = $vo['id'];
                $date[$int]['rate'] = $vo['replenish_num'] / $skuWholeNum;
                $date[$int]['replenish_id'] = $res;
                $int += 1;
            }
        }
        //批量更新补货需求清单 中的补货需求单id以及虚拟仓比例
        $res1 = $this->model->allowField(true)->saveAll($date);

        $number = 0;
        foreach ($list as $k => $v) {
            $arr[$number]['sku'] = $k;
            $arr[$number]['replenishment_num'] = $v;
            $arr[$number]['create_person'] = 'Admin';
            $arr[$number]['create_time'] = date('Y-m-d H:i:s');
            $arr[$number]['type'] = 1;
            $arr[$number]['replenish_id'] = $res;
            $number += 1;
        }
        //插入补货需求单子表 关联主表 new_product_replenish_order 关联字段replenish_id
        $result = $this->order->allowField(true)->saveAll($arr);
        //更新计划补货列表
        $ids = $this->model
            ->where(['is_show' => 1, 'type' => 1])
            ->whereTime('create_time', 'between', [date('Y-m-d H:i:s', strtotime("-1 month")), date('Y-m-d H:i:s')])
            ->setField('is_show', 0);
    }

    /**
     * *@param  [type] $arr [二维数组]
     * @param  [type] $key [键名]
     *
     * @return [type]      [新的二维数组]
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/7/22
     * Time: 11:37
     */
    function array_group_by($arr, $key)
    {
        $grouped = [];
        foreach ($arr as $value) {
            $grouped[$value[$key]][] = $value;
        }
        if (func_num_args() > 2) {
            $args = func_get_args();
            foreach ($grouped as $key => $value) {
                $parms = array_merge($value, array_slice($args, 2, func_num_args()));
                $grouped[$key] = call_user_func_array('array_group_by', $parms);
            }
        }

        return $grouped;
    }

    /**
     * 紧急补货  2020.09.07改为计划任务 周计划执行时间为每周三的24点，汇总各站提报的SKU及数量
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/7/17
     * Time: 9:22
     */
    public function emergency_replenishment()
    {
        $this->model = new \app\admin\model\NewProductMapping();
        $this->order = new \app\admin\model\purchase\NewProductReplenishOrder();
        //紧急补货分站点
        $platform_type = input('label');
        //统计计划补货数据
        $list = $this->model
            ->where(['is_show' => 1, 'type' => 2])
            // ->where(['is_show' => 1, 'type' => 2,'website_type'=>$platform_type]) //分站点统计补货需求 2020.9.4改为计划补货 不分站点

            ->whereTime('create_time', 'between', [date('Y-m-d H:i:s', strtotime("-1 month")), date('Y-m-d H:i:s')])
            ->group('sku')
            ->column("sku,sum(replenish_num) as sum");

        if (empty($list)) {
            echo('暂时没有紧急补货单需要处理');
            die;
        }

        //统计各个站计划某个sku计划补货的总数 以及比例 用于回写平台sku映射表中
        $sku_list = $this->model
            ->where(['is_show' => 1, 'type' => 2])
            ->whereTime('create_time', 'between', [date('Y-m-d H:i:s', strtotime("-1 month")), date('Y-m-d H:i:s')])
            ->field('id,sku,website_type,replenish_num')
            ->select();
        //根据sku对数组进行重新分配
        $sku_list = $this->array_group_by($sku_list, 'sku');

        $result = false;
        //首先插入主表 获取主表id new_product_replenish
        $data['type'] = 2;
        $data['create_person'] = 'Admin';
        $data['create_time'] = date('Y-m-d H:i:s');
        $res = Db::name('new_product_replenish')->insertGetId($data);

        //遍历以更新平台sku映射表的 关联补货需求单id 以及各站虚拟仓占比
        $int = 0;
        foreach ($sku_list as $k => $v) {
            //求出此sku在此补货单中的总数量
            $skuWholeNum = array_sum(array_map(function ($val) {
                return $val['replenish_num'];
            }, $v));
            //求出比例赋予新数组
            foreach ($v as $ko => $vo) {
                $date[$int]['id'] = $vo['id'];
                $date[$int]['rate'] = $vo['replenish_num'] / $skuWholeNum;
                $date[$int]['replenish_id'] = $res;
                $int += 1;
            }
        }
        //批量更新补货需求清单 中的补货需求单id以及虚拟仓比例
        $res1 = $this->model->allowField(true)->saveAll($date);

        $number = 0;
        foreach ($list as $k => $v) {
            $arr[$number]['sku'] = $k;
            $arr[$number]['replenishment_num'] = $v;
            $arr[$number]['create_person'] = 'Admin';
            // $arr[$number]['create_person'] = session('admin.nickname');
            $arr[$number]['create_time'] = date('Y-m-d H:i:s');
            $arr[$number]['type'] = 2;
            $arr[$number]['replenish_id'] = $res;
            $number += 1;
        }
        //插入补货需求单表
        $result = $this->order->allowField(true)->saveAll($arr);
        //更新计划补货列表
        $ids = $this->model
            ->where(['is_show' => 1, 'type' => 2])
            ->whereTime('create_time', 'between', [date('Y-m-d H:i:s', strtotime("-1 month")), date('Y-m-d H:i:s')])
            ->setField('is_show', 0);
    }

    /**
     * 日度补货计划计划任务 每日晚上11点整 汇总所有站当天提报的【日度计划】
     * Interface day_replenishment
     * @package app\admin\controller\shell
     * @author  jhh
     * @date    2021/4/12 13:41:18
     */
    public function day_replenishment()
    {
        $this->model = new \app\admin\model\NewProductMapping();
        $this->order = new \app\admin\model\purchase\NewProductReplenishOrder();
        //统计计划补货数据
        $list = $this->model
            ->where(['is_show' => 1, 'type' => 3])
            ->whereTime('create_time', 'between', [date('Y-m-d H:i:s', strtotime("-1 day")), date('Y-m-d H:i:s')])
            ->group('sku')
            ->column("sku,sum(replenish_num) as sum");
        if (empty($list)) {
            echo('暂时没有紧急补货单需要处理');
            die;
        }
        //统计各个站计划某个sku计划补货的总数 以及比例 用于回写平台sku映射表中
        $skuList = $this->model
            ->where(['is_show' => 1, 'type' => 3])
            ->whereTime('create_time', 'between', [date('Y-m-d H:i:s', strtotime("-1 day")), date('Y-m-d H:i:s')])
            ->field('id,sku,website_type,replenish_num')
            ->select();
        //根据sku对数组进行重新分配
        $skuList = $this->array_group_by($skuList, 'sku');
        $result = false;
        //首先插入主表 获取主表id new_product_replenish
        $data['type'] = 3;
        $data['create_person'] = 'Admin';
        $data['create_time'] = date('Y-m-d H:i:s');
        $res = Db::name('new_product_replenish')->insertGetId($data);
        //遍历以更新平台sku映射表的 关联补货需求单id 以及各站虚拟仓占比
        $int = 0;
        foreach ($skuList as $k => $v) {
            //求出此sku在此补货单中的总数量
            $skuWholeNum = array_sum(array_map(function ($val) {
                return $val['replenish_num'];
            }, $v));
            //求出比例赋予新数组
            foreach ($v as $ko => $vo) {
                $date[$int]['id'] = $vo['id'];
                $date[$int]['rate'] = $vo['replenish_num'] / $skuWholeNum;
                $date[$int]['replenish_id'] = $res;
                $int += 1;
            }
        }
        //批量更新补货需求清单 中的补货需求单id以及虚拟仓比例
        $res1 = $this->model->allowField(true)->saveAll($date);
        $number = 0;
        foreach ($list as $k => $v) {
            $arr[$number]['sku'] = $k;
            $arr[$number]['replenishment_num'] = $v;
            $arr[$number]['create_person'] = 'Admin';
            $arr[$number]['create_time'] = date('Y-m-d H:i:s');
            $arr[$number]['type'] = 3;
            $arr[$number]['replenish_id'] = $res;
            $number += 1;
        }
        //插入补货需求单表
        $result = $this->order->allowField(true)->saveAll($arr);
        //更新计划补货列表
        $ids = $this->model
            ->where(['is_show' => 1, 'type' => 3])
            ->whereTime('create_time', 'between', [date('Y-m-d H:i:s', strtotime("-1 day")), date('Y-m-d H:i:s')])
            ->setField('is_show', 0);

        echo "ok";
    }

    //活跃用户数
    public function google_active_user($site, $start_time)
    {
        // dump();die;
        $end_time = $start_time;
        $client = new \Google_Client();
        $client->setAuthConfig('./oauth/oauth-credentials.json');
        $client->addScope(\Google_Service_Analytics::ANALYTICS_READONLY);
        // Create an authorized analytics service object.
        $analytics = new \Google_Service_AnalyticsReporting($client);
        // $analytics = $this->initializeAnalytics();
        // Call the Analytics Reporting API V4.
        $response = $this->getReport_active_user($site, $analytics, $start_time, $end_time);
        // Print the response.
        $result = $this->printResults($response);

        return $result[0]['ga:1dayUsers'] ? round($result[0]['ga:1dayUsers'], 2) : 0;
    }

    protected function getReport_active_user($site, $analytics, $startDate, $endDate)
    {

        // Replace with your view ID, for example XXXX.
        // $VIEW_ID = "168154683";
        // $VIEW_ID = "172731925";
        if ($site == 1) {
            $VIEW_ID = config('ZEELOOL_GOOGLE_ANALYTICS_VIEW_ID');
        } elseif ($site == 2) {
            $VIEW_ID = config('VOOGUEME_GOOGLE_ANALYTICS_VIEW_ID');
        } elseif ($site == 3) {
            $VIEW_ID = config('NIHAO_GOOGLE_ANALYTICS_VIEW_ID');
        } elseif ($site == 5) {
            $VIEW_ID = config('WESEE_GOOGLE_ANALYTICS_VIEW_ID');
        } elseif ($site == 10) {
            $VIEW_ID = config('ZEELOOLDE_GOOGLE_ANALYTICS_VIEW_ID');
        } elseif ($site == 11) {
            $VIEW_ID = config('ZEELOOLJP_GOOGLE_ANALYTICS_VIEW_ID');
        } elseif ($site == 15) {
            $VIEW_ID = config('ZEELOOLFR_GOOGLE_ANALYTICS_VIEW_ID');
        }

        // Replace with your view ID, for example XXXX.
        // $VIEW_ID = "<REPLACE_WITH_VIEW_ID>";

        $dateRange = new \Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($startDate);
        $dateRange->setEndDate($endDate);

        $adCostMetric = new \Google_Service_AnalyticsReporting_Metric();
        $adCostMetric->setExpression("ga:1dayUsers");
        $adCostMetric->setAlias("ga:1dayUsers");
        // $adCostMetric->setExpression("ga:adCost");
        // $adCostMetric->setAlias("ga:adCost");

        $sessionDayDimension = new \Google_Service_AnalyticsReporting_Dimension();
        $sessionDayDimension->setName("ga:day");
        $sessionDayDimension->setName("ga:date");

        // Create the ReportRequest object.
        $request = new \Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($VIEW_ID);
        $request->setDateRanges($dateRange);
        $request->setMetrics([$adCostMetric]);
        $request->setDimensions([$sessionDayDimension]);

        $body = new \Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests([$request]);

        return $analytics->reports->batchGet($body);
    }

    //session
    public function google_session($site, $start_time)
    {
        // dump();die;
        $end_time = $start_time;
        $client = new \Google_Client();
        $client->setAuthConfig('./oauth/oauth-credentials.json');
        $client->addScope(\Google_Service_Analytics::ANALYTICS_READONLY);
        // Create an authorized analytics service object.
        $analytics = new \Google_Service_AnalyticsReporting($client);
        // $analytics = $this->initializeAnalytics();
        // Call the Analytics Reporting API V4.
        $response = $this->getReport_session($site, $analytics, $start_time, $end_time);

        // dump($response);die;

        // Print the response.
        $result = $this->printResults($response);

        return $result[0]['ga:sessions'] ? round($result[0]['ga:sessions'], 2) : 0;
    }

    protected function getReport_session($site, $analytics, $startDate, $endDate)
    {

        // Replace with your view ID, for example XXXX.
        // $VIEW_ID = "168154683";
        // $VIEW_ID = "172731925";
        if ($site == 1) {
            $VIEW_ID = config('ZEELOOL_GOOGLE_ANALYTICS_VIEW_ID');
        } elseif ($site == 2) {
            $VIEW_ID = config('VOOGUEME_GOOGLE_ANALYTICS_VIEW_ID');
        } elseif ($site == 3) {
            $VIEW_ID = config('NIHAO_GOOGLE_ANALYTICS_VIEW_ID');
        } elseif ($site == 5) {
            $VIEW_ID = config('WESEE_GOOGLE_ANALYTICS_VIEW_ID');
        } elseif ($site == 10) {
            $VIEW_ID = config('ZEELOOLDE_GOOGLE_ANALYTICS_VIEW_ID');
        } elseif ($site == 11) {
            $VIEW_ID = config('ZEELOOLJP_GOOGLE_ANALYTICS_VIEW_ID');
        } elseif ($site == 15) {
            $VIEW_ID = config('ZEELOOLFR_GOOGLE_ANALYTICS_VIEW_ID');
        }

        // Replace with your view ID, for example XXXX.
        // $VIEW_ID = "<REPLACE_WITH_VIEW_ID>";

        $dateRange = new \Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($startDate);
        $dateRange->setEndDate($endDate);

        $adCostMetric = new \Google_Service_AnalyticsReporting_Metric();
        $adCostMetric->setExpression("ga:sessions");
        $adCostMetric->setAlias("ga:sessions");
        // $adCostMetric->setExpression("ga:adCost");
        // $adCostMetric->setAlias("ga:adCost");
        $sessionDayDimension = new \Google_Service_AnalyticsReporting_Dimension();
        $sessionDayDimension->setName("ga:day");
        $sessionDayDimension->setName("ga:date");

        // Create the ReportRequest object.
        $request = new \Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($VIEW_ID);
        $request->setDateRanges($dateRange);
        $request->setMetrics([$adCostMetric]);
        $request->setDimensions([$sessionDayDimension]);

        $body = new \Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests([$request]);

        return $analytics->reports->batchGet($body);
    }

    public function asynData()
    {
        $this->getData(10);
        $this->getData(11);
    }

    public function getData($site)
    {
        //获取datacenter表中德语站和日本站的数据
        $data = Db::name('datacenter_day')
            ->where('site', $site)
            ->where('sessions', 0)
            ->select();
        foreach ($data as $value) {
            //会话
            $arr['sessions'] = $this->google_session($site, $value['day_date']);
            //计算加购率
            $arr['add_cart_rate'] = $arr['sessions'] ? round($value['new_cart_num'] / $arr['sessions'] * 100, 2) : 0;
            //计算会话转化率
            $arr['session_rate'] = $arr['sessions'] ? round($value['order_num'] / $arr['sessions'] * 100, 2) : 0;
            Db::name('datacenter_day')
                ->where('id', $value['id'])
                ->update($arr);
            echo $value['id'] . "---" . $value['day_date'] . " is ok" . "\n";
            usleep(10000);
        }
    }

    /**
     * Parses and prints the Analytics Reporting API V4 response.
     *
     * @param An Analytics Reporting API V4 response.
     */
    protected function printResults($reports)
    {
        $finalResult = [];
        for ($reportIndex = 0; $reportIndex < count($reports); $reportIndex++) {
            $report = $reports[$reportIndex];
            $header = $report->getColumnHeader();
            $dimensionHeaders = $header->getDimensions();
            $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
            $rows = $report->getData()->getRows();
            for ($rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
                $row = $rows[$rowIndex];
                $dimensions = $row->getDimensions();
                $metrics = $row->getMetrics();
                for ($i = 0; $i < count($dimensionHeaders) && $i < count($dimensions); $i++) {
                    $finalResult[$rowIndex][$dimensionHeaders[$i]] = $dimensions[$i];
                }

                for ($j = 0; $j < count($metrics); $j++) {
                    $values = $metrics[$j]->getValues();
                    for ($k = 0; $k < count($values); $k++) {
                        $entry = $metricHeaders[$k];
                        $finalResult[$rowIndex][$entry->getName()] = $values[$k];
                    }
                }
            }

            return $finalResult;
        }
    }

    /**
     *计算中位数 中位数：是指一组数据从小到大排列，位于中间的那个数。可以是一个（数据为奇数），也可以是2个的平均（数据为偶数）
     */
    function median($numbers)
    {
        sort($numbers);
        $totalNumbers = count($numbers);
        $mid = floor($totalNumbers / 2);

        return ($totalNumbers % 2) === 0 ? ($numbers[$mid - 1] + $numbers[$mid]) / 2 : $numbers[$mid];
    }

    /**
     * 得到数组的标准差
     *
     * @param unknown type $avg
     * @param Array  $list
     * @param Boolen $isSwatch
     *
     * @return unknown type
     */
    function getVariance($arr)
    {
        $length = count($arr);
        if ($length == 0) {
            return 0;
        }
        $average = array_sum($arr) / $length;
        $count = 0;
        foreach ($arr as $v) {
            $count += pow($average - $v, 2);
        }
        $variance = $count / $length;

        return sqrt($variance);
    }

    //运营数据中心 zeelool
    public function zeelool_day_data()
    {
        $model = new \app\admin\model\itemmanage\ItemPlatformSku();
        $this->zeelool = new \app\admin\model\order\order\Zeelool();
        $zeelool_model = Db::connect('database.db_zeelool_online');
        $zeelool_model->table('customer_entity')->query("set time_zone='+8:00'");
        $zeelool_model->table('oc_vip_order')->query("set time_zone='+8:00'");
        $zeelool_model->table('sales_flat_quote')->query("set time_zone='+8:00'");
        $zeelool_model->table('sales_flat_order')->query("set time_zone='+8:00'");

        $date_time = date('Y-m-d', strtotime("-1 day"));
        //查询时间
        $arr = [];
        $arr['site'] = 1;
        $arr['day_date'] = $date_time;
        //活跃用户数
        // $arr['active_user_num'] = $this->google_active_user(1, $date_time);
        //注册用户数
        $register_where = [];
        $register_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['register_num'] = $zeelool_model->table('customer_entity')->where($register_where)->count();

        //总的订单数
        $order_where = [];
        $order_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['sum_order_num'] = $zeelool_model->table('sales_flat_order')->where($order_where)->where('order_type', 1)->count();
        //登录用户数
        $customer_where = [];
        $customer_where[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['login_user_num'] = $zeelool_model->table('customer_entity')->where($customer_where)->count();

        //新增vip用户数
        $vip_where = [];
        $vip_where[] = ['exp', Db::raw("DATE_FORMAT(start_time, '%Y-%m-%d') = '" . $date_time . "'")];
        $vip_where['order_status'] = 'Success';
        $arr['vip_user_num'] = $zeelool_model->table('oc_vip_order')->where($vip_where)->count();
        //支付成功的订单数
        $order_where = [];
        $order_where[] = ['exp', Db::raw("DATE_FORMAT(payment_time, '%Y-%m-%d') = '" . $date_time . "'")];
        $order_where['status'] = [
            'in',
            [
                'free_processing',
                'processing',
                'complete',
                'paypal_reversed',
                'payment_review',
                'paypal_canceled_reversal',
                'delivered',
                'delivery',
            ],
        ];
        $arr['order_num'] = $this->zeelool->where($order_where)->where('order_type', 1)->count();
        //销售额
        $arr['sales_total_money'] = $this->zeelool->where($order_where)->where('order_type',
            1)->sum('base_grand_total');
        //邮费
        $arr['shipping_total_money'] = $this->zeelool->where($order_where)->where('order_type',
            1)->sum('base_shipping_amount');
        //客单价
        $arr['order_unit_price'] = $arr['order_num'] == 0 ? 0 : round($arr['sales_total_money'] / $arr['order_num'], 2);
        //中位数
        $sales_total_money = $this->zeelool->where($order_where)->where('order_type', 1)->column('base_grand_total');
        $arr['order_total_midnum'] = $this->median($sales_total_money);
        //标准差
        $arr['order_total_standard'] = $this->getVariance($sales_total_money);
        //补发订单数
        $arr['replacement_order_num'] = $this->zeelool->where($order_where)->where('order_type', 4)->count();
        //补发销售额
        $arr['replacement_order_total'] = $this->zeelool->where($order_where)->where('order_type',
            4)->sum('base_grand_total');
        //网红订单数
        $arr['online_celebrity_order_num'] = $this->zeelool->where($order_where)->where('order_type', 3)->count();
        //补发销售额
        $arr['online_celebrity_order_total'] = $this->zeelool->where($order_where)->where('order_type',
            3)->sum('base_grand_total');
        //新建购物车数量
        $cart_where1 = [];
        $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['new_cart_num'] = $zeelool_model->table('sales_flat_quote')->where($cart_where1)->where('base_grand_total',
            'gt', 0)->count();
        //更新购物车数量
        $cart_where2 = [];
        $cart_where2[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['update_cart_num'] = $zeelool_model->table('sales_flat_quote')->where($cart_where2)->where('base_grand_total',
            'gt', 0)->count();
        //新增购物车转化率
        $arr['cart_rate'] = $arr['new_cart_num'] ? round($arr['order_num'] / $arr['new_cart_num'] * 100, 2) : 0;
        //更新购物车转化率
        $arr['update_cart_cart'] = $arr['update_cart_num'] ? round($arr['order_num'] / $arr['update_cart_num'] * 100,
            2) : 0;
        //当天创建的用户当天产生订单的转化率
        $status_where['status'] = [
            'in',
            [
                'free_processing',
                'processing',
                'complete',
                'paypal_reversed',
                'payment_review',
                'paypal_canceled_reversal',
                'delivered',
                'delivery',
            ],
        ];
        $status_where['order_type'] = 1;
        //当天注册用户数
        $register_userids = $zeelool_model->table('customer_entity')->where($cart_where1)->column('entity_id');
        $register_num = count($register_userids);
        //当天注册用户在当天下单的用户数
        $order_user_count1 = 0;
        foreach ($register_userids as $register_userid) {
            //判断当前用户在当天是否下单
            $order = $zeelool_model->table('sales_flat_order')->where($cart_where1)->where($status_where)->where('customer_id',
                $register_userid)->value('entity_id');
            if ($order) {
                $order_user_count1++;
            }
        }
        $arr['create_user_change_rate'] = $register_num ? round($order_user_count1 / $register_num * 100, 2) : 0;
        //当天更新用户当天产生订单的转化率
        $update_userids = $zeelool_model->table('customer_entity')->where($cart_where2)->column('entity_id');
        $update_num = count($update_userids);
        //当天活跃更新用户数在当天是否下单
        $order_user_count2 = 0;
        foreach ($update_userids as $update_userid) {
            //判断活跃用户在当天下单的用户数
            $order = $zeelool_model->table('sales_flat_order')->where($cart_where1)->where($status_where)->where('customer_id',
                $update_userid)->value('entity_id');
            if ($order) {
                $order_user_count2++;
            }
        }
        $arr['update_user_change_rate'] = $update_num ? round($order_user_count2 / $update_num * 100, 0) : 0;
        //虚拟库存
        $virtual_where['platform_type'] = 1;
        $virtual_where['i.category_id'] = ['<>', 43];
        $virtual_where['i.is_del'] = 1;
        $virtual_where['i.is_open'] = 1;
        $arr['virtual_stock'] = $model->alias('p')->join('fa_item i',
            'p.sku=i.sku')->where($virtual_where)->sum('p.stock');
        //在售，预售，下架
        $item = new \app\admin\model\itemmanage\Item();
        $site_where['platform_type'] = $arr['site'];
        $skus = $item->getFrameSku();
        $map_where['sku'] = ['in', $skus];
        $webSkus = $model
            ->where($map_where)
            ->where($site_where)
            ->select();
        $onSalesNum = 0;  //在售
        $SalesOutNum = 0;  //售罄
        $downShelvesNum = 0;  //下架
        $nowDate = date('Y-m-d H:i:s');
        foreach ($webSkus as $value) {
            if ($value['outer_sku_status'] == 1) {
                //上架
                if ($value['stock'] > 0) {
                    //在售
                    $onSalesNum++;
                } else {
                    if ($value['presell_status'] == 1 && $nowDate >= $value['presell_start_time'] && $nowDate <= $value['presell_end_time']) {
                        //开预售
                        if ($value['presell_num'] > 0) {
                            $onSalesNum++;
                        } else {
                            $SalesOutNum++;
                        }
                    } else {
                        //未开预售
                        $SalesOutNum++;
                    }
                }
            } else {
                //下架
                $downShelvesNum++;
            }
        }
        $arr['glass_in_sale_num'] = $onSalesNum;  //在售
        $arr['glass_shelves_num'] = $downShelvesNum;  //下架
        $arr['glass_presell_num'] = $SalesOutNum;  //售罄
        $skus1 = $item->getOrnamentsSku();
        $map_where1['sku'] = ['in', $skus1];
        $webSkus1 = $model
            ->where($map_where1)
            ->where($site_where)
            ->select();
        $onSalesNum1 = 0;  //在售
        $SalesOutNum1 = 0;  //售罄
        $downShelvesNum1 = 0;  //下架
        foreach ($webSkus1 as $value) {
            if ($value['outer_sku_status'] == 1) {
                //上架
                if ($value['stock'] > 0) {
                    //在售
                    $onSalesNum1++;
                } else {
                    if ($value['presell_status'] == 1 && $nowDate >= $value['presell_start_time'] && $nowDate <= $value['presell_end_time']) {
                        //开预售
                        if ($value['presell_num'] > 0) {
                            $onSalesNum1++;
                        } else {
                            $SalesOutNum1++;
                        }
                    } else {
                        //未开预售
                        $SalesOutNum1++;
                    }
                }
            } else {
                //下架
                $downShelvesNum1++;
            }
        }
        $arr['box_in_sale_num'] = $onSalesNum1;
        $arr['box_shelves_num'] = $downShelvesNum1;
        $arr['box_presell_num'] = $SalesOutNum1;
        $datacenterDayId = Db::name('datacenter_day')->insertGetId($arr);
        //同步es数据
        (new AsyncDatacenterDay())->runInsert($datacenterDayId);
        echo $date_time . "\n";
        echo date("Y-m-d H:i:s") . "\n";
        usleep(100000);
    }

    //运营数据中心 voogueme
    public function voogueme_day_data()
    {
        $model = new \app\admin\model\itemmanage\ItemPlatformSku();
        $this->zeelool = new \app\admin\model\order\order\Voogueme();
        $zeelool_model = Db::connect('database.db_voogueme_online');
        $zeelool_model->table('customer_entity')->query("set time_zone='+8:00'");
        $zeelool_model->table('oc_vip_order')->query("set time_zone='+8:00'");
        $zeelool_model->table('sales_flat_quote')->query("set time_zone='+8:00'");
        $zeelool_model->table('sales_flat_order')->query("set time_zone='+8:00'");

        $date_time = date('Y-m-d', strtotime("-1 day"));

        //查询时间
        $arr = [];
        $arr['site'] = 2;
        $arr['day_date'] = $date_time;
        //活跃用户数
        // $arr['active_user_num'] = $this->google_active_user(2, $date_time);
        //注册用户数
        $register_where = [];
        $register_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['register_num'] = $zeelool_model->table('customer_entity')->where($register_where)->count();

        //总的订单数
        $order_where = [];
        $order_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['sum_order_num'] = $zeelool_model->table('sales_flat_order')->where($order_where)->where('order_type',
            1)->count();
        //登录用户数
        $customer_where = [];
        $customer_where[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['login_user_num'] = $zeelool_model->table('customer_entity')->where($customer_where)->count();

        //新增vip用户数
        $vip_where = [];
        $vip_where[] = ['exp', Db::raw("DATE_FORMAT(start_time, '%Y-%m-%d') = '" . $date_time . "'")];
        $vip_where['order_status'] = 'Success';
        $arr['vip_user_num'] = $zeelool_model->table('oc_vip_order')->where($vip_where)->count();
        //支付成功的订单数
        $order_where = [];
        $order_where = [];
        $order_where[] = ['exp', Db::raw("DATE_FORMAT(payment_time, '%Y-%m-%d') = '" . $date_time . "'")];
        $order_where['status'] = [
            'in',
            [
                'free_processing',
                'processing',
                'complete',
                'paypal_reversed',
                'payment_review',
                'paypal_canceled_reversal',
                'delivered',
                'delivery',
            ],
        ];
        $arr['order_num'] = $this->zeelool->where($order_where)->where('order_type', 1)->count();
        //销售额
        $arr['sales_total_money'] = $this->zeelool->where($order_where)->where('order_type',
            1)->sum('base_grand_total');
        //邮费
        $arr['shipping_total_money'] = $this->zeelool->where($order_where)->where('order_type',
            1)->sum('base_shipping_amount');
        $arr['order_unit_price'] = $arr['order_num'] == 0 ? 0 : round($arr['sales_total_money'] / $arr['order_num'], 2);
        //中位数
        $sales_total_money = $this->zeelool->where($order_where)->where('order_type', 1)->column('base_grand_total');
        $arr['order_total_midnum'] = $this->median($sales_total_money);
        //标准差
        $arr['order_total_standard'] = $this->getVariance($sales_total_money);
        //补发订单数
        $arr['replacement_order_num'] = $this->zeelool->where($order_where)->where('order_type', 4)->count();
        //补发销售额
        $arr['replacement_order_total'] = $this->zeelool->where($order_where)->where('order_type',
            4)->sum('base_grand_total');
        //网红订单数
        $arr['online_celebrity_order_num'] = $this->zeelool->where($order_where)->where('order_type', 3)->count();
        //补发销售额
        $arr['online_celebrity_order_total'] = $this->zeelool->where($order_where)->where('order_type',
            3)->sum('base_grand_total');
        //会话
        // $arr['sessions'] = $this->google_session(2, $date_time);
        //会话转化率
        // $arr['session_rate'] = $arr['sessions'] != 0 ? round($arr['order_num'] / $arr['sessions'] * 100, 2) : 0;
        //新建购物车数量
        $cart_where1 = [];
        $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['new_cart_num'] = $zeelool_model->table('sales_flat_quote')->where($cart_where1)->where('base_grand_total',
            'gt', 0)->count();
        //更新购物车数量
        $cart_where2 = [];
        $cart_where2[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['update_cart_num'] = $zeelool_model->table('sales_flat_quote')->where($cart_where2)->where('base_grand_total',
            'gt', 0)->count();
        //新增加购率
        // $arr['add_cart_rate'] = $arr['sessions'] ? round($arr['new_cart_num'] / $arr['sessions'] * 100, 2) : 0;
        //更新加购率
        // $arr['update_add_cart_rate'] = $arr['sessions'] ? round($arr['update_cart_num'] / $arr['sessions'] * 100, 2) : 0;
        //新增购物车转化率
        $arr['cart_rate'] = $arr['new_cart_num'] ? round($arr['order_num'] / $arr['new_cart_num'] * 100, 2) : 0;
        //更新购物车转化率
        $arr['update_cart_cart'] = $arr['update_cart_num'] ? round($arr['order_num'] / $arr['update_cart_num'] * 100,
            2) : 0;
        //当天创建的用户当天产生订单的转化率
        $status_where['status'] = [
            'in',
            [
                'free_processing',
                'processing',
                'complete',
                'paypal_reversed',
                'payment_review',
                'paypal_canceled_reversal',
                'delivered',
                'delivery',
            ],
        ];
        $status_where['order_type'] = 1;
        //当天注册用户数
        $register_userids = $zeelool_model->table('customer_entity')->where($cart_where1)->column('entity_id');
        $register_num = count($register_userids);
        //当天注册用户在当天下单的用户数
        $order_user_count1 = 0;
        foreach ($register_userids as $register_userid) {
            //判断当前用户在当天是否下单
            $order = $zeelool_model->table('sales_flat_order')->where($cart_where1)->where($status_where)->where('customer_id',
                $register_userid)->value('entity_id');
            if ($order) {
                $order_user_count1++;
            }
        }
        $arr['create_user_change_rate'] = $register_num ? round($order_user_count1 / $register_num * 100, 2) : 0;
        //当天更新用户当天产生订单的转化率
        $update_userids = $zeelool_model->table('customer_entity')->where($cart_where2)->column('entity_id');
        $update_num = count($update_userids);
        //当天活跃更新用户数在当天是否下单
        $order_user_count2 = 0;
        foreach ($update_userids as $update_userid) {
            //判断活跃用户在当天下单的用户数
            $order = $zeelool_model->table('sales_flat_order')->where($cart_where1)->where($status_where)->where('customer_id',
                $update_userid)->value('entity_id');
            if ($order) {
                $order_user_count2++;
            }
        }
        $arr['update_user_change_rate'] = $update_num ? round($order_user_count2 / $update_num * 100, 0) : 0;
        //虚拟库存
        $virtual_where['platform_type'] = 2;
        $virtual_where['i.category_id'] = ['<>', 43];
        $virtual_where['i.is_del'] = 1;
        $virtual_where['i.is_open'] = 1;
        $arr['virtual_stock'] = $model->alias('p')->join('fa_item i',
            'p.sku=i.sku')->where($virtual_where)->sum('p.stock');
        //在售，预售，下架
        //在售，预售，下架
        $item = new \app\admin\model\itemmanage\Item();
        $site_where['platform_type'] = $arr['site'];
        $skus = $item->getFrameSku();
        $map_where['sku'] = ['in', $skus];
        $webSkus = $model
            ->where($map_where)
            ->where($site_where)
            ->select();
        $onSalesNum = 0;  //在售
        $SalesOutNum = 0;  //售罄
        $downShelvesNum = 0;  //下架
        $nowDate = date('Y-m-d H:i:s');
        foreach ($webSkus as $value) {
            if ($value['outer_sku_status'] == 1) {
                //上架
                if ($value['stock'] > 0) {
                    //在售
                    $onSalesNum++;
                } else {
                    if ($value['presell_status'] == 1 && $nowDate >= $value['presell_start_time'] && $nowDate <= $value['presell_end_time']) {
                        //开预售
                        if ($value['presell_num'] > 0) {
                            $onSalesNum++;
                        } else {
                            $SalesOutNum++;
                        }
                    } else {
                        //未开预售
                        $SalesOutNum++;
                    }
                }
            } else {
                //下架
                $downShelvesNum++;
            }
        }
        $arr['glass_in_sale_num'] = $onSalesNum;  //在售
        $arr['glass_shelves_num'] = $downShelvesNum;  //下架
        $arr['glass_presell_num'] = $SalesOutNum;  //售罄
        $skus1 = $item->getOrnamentsSku();
        $map_where1['sku'] = ['in', $skus1];
        $webSkus1 = $model
            ->where($map_where1)
            ->where($site_where)
            ->select();
        $onSalesNum1 = 0;  //在售
        $SalesOutNum1 = 0;  //售罄
        $downShelvesNum1 = 0;  //下架
        foreach ($webSkus1 as $value) {
            if ($value['outer_sku_status'] == 1) {
                //上架
                if ($value['stock'] > 0) {
                    //在售
                    $onSalesNum1++;
                } else {
                    if ($value['presell_status'] == 1 && $nowDate >= $value['presell_start_time'] && $nowDate <= $value['presell_end_time']) {
                        //开预售
                        if ($value['presell_num'] > 0) {
                            $onSalesNum1++;
                        } else {
                            $SalesOutNum1++;
                        }
                    } else {
                        //未开预售
                        $SalesOutNum1++;
                    }
                }
            } else {
                //下架
                $downShelvesNum1++;
            }
        }
        $arr['box_in_sale_num'] = $onSalesNum1;
        $arr['box_shelves_num'] = $downShelvesNum1;
        $arr['box_presell_num'] = $SalesOutNum1;
        //插入数据
        $datacenterDayId = Db::name('datacenter_day')->insertGetId($arr);
        //同步es数据
        (new AsyncDatacenterDay())->runInsert($datacenterDayId);
        echo $date_time . "\n";
        echo date("Y-m-d H:i:s") . "\n";
        usleep(100000);
    }

    //运营数据中心 nihao
    public function nihao_day_data()
    {
        $model = new \app\admin\model\itemmanage\ItemPlatformSku();
        $this->zeelool = new \app\admin\model\order\order\Nihao();
        $zeelool_model = Db::connect('database.db_nihao_online');
        $zeelool_model->table('customer_entity')->query("set time_zone='+8:00'");
        $zeelool_model->table('oc_vip_order')->query("set time_zone='+8:00'");
        $zeelool_model->table('sales_flat_quote')->query("set time_zone='+8:00'");
        $zeelool_model->table('sales_flat_order')->query("set time_zone='+8:00'");

        $date_time = date('Y-m-d', strtotime("-1 day"));

        //查询时间
        $arr = [];
        $arr['site'] = 3;
        $arr['day_date'] = $date_time;
        //活跃用户数
        // $arr['active_user_num'] = $this->google_active_user(3, $date_time);
        //注册用户数
        $register_where = [];
        $register_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['register_num'] = $zeelool_model->table('customer_entity')->where($register_where)->count();

        //总的订单数
        $order_where = [];
        $order_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['sum_order_num'] = $zeelool_model->table('sales_flat_order')->where($order_where)->where('order_type',
            1)->count();
        //登录用户数
        $customer_where = [];
        $customer_where[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['login_user_num'] = $zeelool_model->table('customer_entity')->where($customer_where)->count();

        //支付成功的订单数
        $order_where = [];
        $order_where[] = ['exp', Db::raw("DATE_FORMAT(payment_time, '%Y-%m-%d') = '" . $date_time . "'")];
        $order_where['status'] = [
            'in',
            [
                'free_processing',
                'processing',
                'complete',
                'paypal_reversed',
                'payment_review',
                'paypal_canceled_reversal',
                'delivered',
                'delivery',
            ],
        ];

        $arr['order_num'] = $this->zeelool->where($order_where)->where('order_type', 1)->count();
        //销售额
        $arr['sales_total_money'] = $this->zeelool->where($order_where)->where('order_type',
            1)->sum('base_grand_total');
        //邮费
        $arr['shipping_total_money'] = $this->zeelool->where($order_where)->where('order_type',
            1)->sum('base_shipping_amount');
        $arr['order_unit_price'] = $arr['order_num'] == 0 ? 0 : round($arr['sales_total_money'] / $arr['order_num'], 2);
        //中位数
        $sales_total_money = $this->zeelool->where($order_where)->where('order_type', 1)->column('base_grand_total');
        $arr['order_total_midnum'] = $this->median($sales_total_money);
        //标准差
        $arr['order_total_standard'] = $this->getVariance($sales_total_money);
        //补发订单数
        $arr['replacement_order_num'] = $this->zeelool->where($order_where)->where('order_type', 4)->count();
        //补发销售额
        $arr['replacement_order_total'] = $this->zeelool->where($order_where)->where('order_type',
            4)->sum('base_grand_total');
        //网红订单数
        $arr['online_celebrity_order_num'] = $this->zeelool->where($order_where)->where('order_type', 3)->count();
        //补发销售额
        $arr['online_celebrity_order_total'] = $this->zeelool->where($order_where)->where('order_type',
            3)->sum('base_grand_total');

        //会话
        // $arr['sessions'] = $this->google_session(3, $date_time);
        //会话转化率
        // $arr['session_rate'] = $arr['sessions'] != 0 ? round($arr['order_num'] / $arr['sessions'] * 100, 2) : 0;
        //新建购物车数量
        $cart_where1 = [];
        $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['new_cart_num'] = $zeelool_model->table('sales_flat_quote')->where($cart_where1)->where('base_grand_total',
            'gt', 0)->count();
        //更新购物车数量
        $cart_where2 = [];
        $cart_where2[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['update_cart_num'] = $zeelool_model->table('sales_flat_quote')->where($cart_where2)->where('base_grand_total',
            'gt', 0)->count();
        //新增加购率
        // $arr['add_cart_rate'] = $arr['sessions'] ? round($arr['new_cart_num'] / $arr['sessions'] * 100, 2) : 0;
        //更新加购率
        // $arr['update_add_cart_rate'] = $arr['sessions'] ? round($arr['update_cart_num'] / $arr['sessions'] * 100, 2) : 0;
        //新增购物车转化率
        $arr['cart_rate'] = $arr['new_cart_num'] ? round($arr['order_num'] / $arr['new_cart_num'] * 100, 2) : 0;
        //更新购物车转化率
        $arr['update_cart_cart'] = $arr['update_cart_num'] ? round($arr['order_num'] / $arr['update_cart_num'] * 100,
            2) : 0;
        //当天创建的用户当天产生订单的转化率
        $status_where['status'] = [
            'in',
            [
                'free_processing',
                'processing',
                'complete',
                'paypal_reversed',
                'payment_review',
                'paypal_canceled_reversal',
            ],
        ];
        $status_where['order_type'] = 1;
        //当天注册用户数
        $register_userids = $zeelool_model->table('customer_entity')->where($cart_where1)->column('entity_id');
        $register_num = count($register_userids);
        //当天注册用户在当天下单的用户数
        $order_user_count1 = 0;
        foreach ($register_userids as $register_userid) {
            //判断当前用户在当天是否下单
            $order = $zeelool_model->table('sales_flat_order')->where($cart_where1)->where($status_where)->where('customer_id',
                $register_userid)->value('entity_id');
            if ($order) {
                $order_user_count1++;
            }
        }
        $arr['create_user_change_rate'] = $register_num ? round($order_user_count1 / $register_num * 100, 2) : 0;
        //当天更新用户当天产生订单的转化率
        $update_userids = $zeelool_model->table('customer_entity')->where($cart_where2)->column('entity_id');
        $update_num = count($update_userids);
        //当天活跃更新用户数在当天是否下单
        $order_user_count2 = 0;
        foreach ($update_userids as $update_userid) {
            //判断活跃用户在当天下单的用户数
            $order = $zeelool_model->table('sales_flat_order')->where($cart_where1)->where($status_where)->where('customer_id',
                $update_userid)->value('entity_id');
            if ($order) {
                $order_user_count2++;
            }
        }
        $arr['update_user_change_rate'] = $update_num ? round($order_user_count2 / $update_num * 100, 0) : 0;
        //虚拟库存
        $virtual_where['platform_type'] = 3;
        $virtual_where['i.category_id'] = ['<>', 43];
        $virtual_where['i.is_del'] = 1;
        $virtual_where['i.is_open'] = 1;
        $arr['virtual_stock'] = $model->alias('p')->join('fa_item i',
            'p.sku=i.sku')->where($virtual_where)->sum('p.stock');
        //在售，预售，下架
        $item = new \app\admin\model\itemmanage\Item();
        $site_where['platform_type'] = $arr['site'];
        $skus = $item->getFrameSku();
        $map_where['sku'] = ['in', $skus];
        $webSkus = $model
            ->where($map_where)
            ->where($site_where)
            ->select();
        $onSalesNum = 0;  //在售
        $SalesOutNum = 0;  //售罄
        $downShelvesNum = 0;  //下架
        $nowDate = date('Y-m-d H:i:s');
        foreach ($webSkus as $value) {
            if ($value['outer_sku_status'] == 1) {
                //上架
                if ($value['stock'] > 0) {
                    //在售
                    $onSalesNum++;
                } else {
                    if ($value['presell_status'] == 1 && $nowDate >= $value['presell_start_time'] && $nowDate <= $value['presell_end_time']) {
                        //开预售
                        if ($value['presell_num'] > 0) {
                            $onSalesNum++;
                        } else {
                            $SalesOutNum++;
                        }
                    } else {
                        //未开预售
                        $SalesOutNum++;
                    }
                }
            } else {
                //下架
                $downShelvesNum++;
            }
        }
        $arr['glass_in_sale_num'] = $onSalesNum;  //在售
        $arr['glass_shelves_num'] = $downShelvesNum;  //下架
        $arr['glass_presell_num'] = $SalesOutNum;  //售罄
        $skus1 = $item->getOrnamentsSku();
        $map_where1['sku'] = ['in', $skus1];
        $webSkus1 = $model
            ->where($map_where1)
            ->where($site_where)
            ->select();
        $onSalesNum1 = 0;  //在售
        $SalesOutNum1 = 0;  //售罄
        $downShelvesNum1 = 0;  //下架
        foreach ($webSkus1 as $value) {
            if ($value['outer_sku_status'] == 1) {
                //上架
                if ($value['stock'] > 0) {
                    //在售
                    $onSalesNum1++;
                } else {
                    if ($value['presell_status'] == 1 && $nowDate >= $value['presell_start_time'] && $nowDate <= $value['presell_end_time']) {
                        //开预售
                        if ($value['presell_num'] > 0) {
                            $onSalesNum1++;
                        } else {
                            $SalesOutNum1++;
                        }
                    } else {
                        //未开预售
                        $SalesOutNum1++;
                    }
                }
            } else {
                //下架
                $downShelvesNum1++;
            }
        }
        $arr['box_in_sale_num'] = $onSalesNum1;
        $arr['box_shelves_num'] = $downShelvesNum1;
        $arr['box_presell_num'] = $SalesOutNum1;
        //插入数据
        $datacenterDayId = Db::name('datacenter_day')->insertGetId($arr);
        //同步es数据
        (new AsyncDatacenterDay())->runInsert($datacenterDayId);
        echo $date_time . "\n";
        echo date("Y-m-d H:i:s") . "\n";
        usleep(100000);
    }

    //运营数据中心 zeeloolde
    public function zeeloolde_day_data()
    {
        $model = new \app\admin\model\itemmanage\ItemPlatformSku();
        $this->order = new \app\admin\model\order\order\ZeeloolDe();
        $operate_model = Db::connect('database.db_zeelool_de');
        $operate_model->table('customer_entity')->query("set time_zone='+8:00'");
        $operate_model->table('oc_vip_order')->query("set time_zone='+8:00'");
        $operate_model->table('sales_flat_quote')->query("set time_zone='+8:00'");
        $operate_model->table('sales_flat_order')->query("set time_zone='+8:00'");

        $date_time = date('Y-m-d', strtotime("-1 day"));
        //查询时间
        $arr = [];
        $arr['site'] = 10;
        $arr['day_date'] = $date_time;
        //注册用户数
        $register_where = [];
        $register_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['register_num'] = $operate_model->table('customer_entity')->where($register_where)->count();
        //总的订单数
        $order_where = [];
        $order_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['sum_order_num'] = $operate_model->table('sales_flat_order')->where($order_where)->where('order_type',
            1)->count();
        //登录用户数
        $customer_where = [];
        $customer_where[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['login_user_num'] = $operate_model->table('customer_entity')->where($customer_where)->count();
        //新增vip用户数
        $vip_where = [];
        $vip_where[] = ['exp', Db::raw("DATE_FORMAT(start_time, '%Y-%m-%d') = '" . $date_time . "'")];
        $vip_where['order_status'] = 'Success';
        $arr['vip_user_num'] = $operate_model->table('oc_vip_order')->where($vip_where)->count();
        //支付成功的订单数
        $order_where = [];
        $order_where[] = ['exp', Db::raw("DATE_FORMAT(payment_time, '%Y-%m-%d') = '" . $date_time . "'")];
        $order_where['status'] = [
            'in',
            [
                'free_processing',
                'processing',
                'complete',
                'paypal_reversed',
                'payment_review',
                'paypal_canceled_reversal',
                'delivered',
                'delivery',
            ],
        ];
        $arr['order_num'] = $this->order->where($order_where)->where('order_type', 1)->count();
        //销售额
        $arr['sales_total_money'] = $this->order->where($order_where)->where('order_type', 1)->sum('base_grand_total');
        //邮费
        $arr['shipping_total_money'] = $this->order->where($order_where)->where('order_type',
            1)->sum('base_shipping_amount');
        //客单价
        $arr['order_unit_price'] = $arr['order_num'] == 0 ? 0 : round($arr['sales_total_money'] / $arr['order_num'], 2);
        //中位数
        $sales_total_money = $this->order->where($order_where)->where('order_type', 1)->column('base_grand_total');
        $arr['order_total_midnum'] = $this->median($sales_total_money);
        //标准差
        $arr['order_total_standard'] = $this->getVariance($sales_total_money);
        //补发订单数
        $arr['replacement_order_num'] = $this->order->where($order_where)->where('order_type', 4)->count();
        //补发销售额
        $arr['replacement_order_total'] = $this->order->where($order_where)->where('order_type',
            4)->sum('base_grand_total');
        //网红订单数
        $arr['online_celebrity_order_num'] = $this->order->where($order_where)->where('order_type', 3)->count();
        //补发销售额
        $arr['online_celebrity_order_total'] = $this->order->where($order_where)->where('order_type',
            3)->sum('base_grand_total');
        //新建购物车数量
        $cart_where1 = [];
        $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['new_cart_num'] = $operate_model->table('sales_flat_quote')->where($cart_where1)->where('base_grand_total',
            'gt', 0)->count();
        //更新购物车数量
        $cart_where2 = [];
        $cart_where2[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['update_cart_num'] = $operate_model->table('sales_flat_quote')->where($cart_where2)->where('base_grand_total',
            'gt', 0)->count();
        //新增购物车转化率
        $arr['cart_rate'] = $arr['new_cart_num'] ? round($arr['order_num'] / $arr['new_cart_num'] * 100, 2) : 0;
        //更新购物车转化率
        $arr['update_cart_cart'] = $arr['update_cart_num'] ? round($arr['order_num'] / $arr['update_cart_num'] * 100,
            2) : 0;
        //当天创建的用户当天产生订单的转化率
        $status_where['status'] = [
            'in',
            [
                'free_processing',
                'processing',
                'complete',
                'paypal_reversed',
                'payment_review',
                'paypal_canceled_reversal',
                'delivered',
                'delivery',
            ],
        ];
        $status_where['order_type'] = 1;
        //当天注册用户数
        $register_userids = $operate_model->table('customer_entity')->where($cart_where1)->column('entity_id');
        $register_num = count($register_userids);
        //当天注册用户在当天下单的用户数
        $order_user_count1 = 0;
        foreach ($register_userids as $register_userid) {
            //判断当前用户在当天是否下单
            $order = $operate_model->table('sales_flat_order')->where($cart_where1)->where($status_where)->where('customer_id',
                $register_userid)->value('entity_id');
            if ($order) {
                $order_user_count1++;
            }
        }
        $arr['create_user_change_rate'] = $register_num ? round($order_user_count1 / $register_num * 100, 2) : 0;
        //当天更新用户当天产生订单的转化率
        $update_userids = $operate_model->table('customer_entity')->where($cart_where2)->column('entity_id');
        $update_num = count($update_userids);
        //当天活跃更新用户数在当天是否下单
        $order_user_count2 = 0;
        foreach ($update_userids as $update_userid) {
            //判断活跃用户在当天下单的用户数
            $order = $operate_model->table('sales_flat_order')->where($cart_where1)->where($status_where)->where('customer_id',
                $update_userid)->value('entity_id');
            if ($order) {
                $order_user_count2++;
            }
        }
        $arr['update_user_change_rate'] = $update_num ? round($order_user_count2 / $update_num * 100, 0) : 0;
        //虚拟库存
        $virtual_where['platform_type'] = 10;
        $virtual_where['i.category_id'] = ['<>', 43];
        $virtual_where['i.is_del'] = 1;
        $virtual_where['i.is_open'] = 1;
        $arr['virtual_stock'] = $model->alias('p')->join('fa_item i',
            'p.sku=i.sku')->where($virtual_where)->sum('p.stock');
        //在售，预售，下架
        $item = new \app\admin\model\itemmanage\Item();
        $site_where['platform_type'] = $arr['site'];
        $skus = $item->getFrameSku();
        $map_where['sku'] = ['in', $skus];
        $webSkus = $model
            ->where($map_where)
            ->where($site_where)
            ->select();
        $onSalesNum = 0;  //在售
        $SalesOutNum = 0;  //售罄
        $downShelvesNum = 0;  //下架
        $nowDate = date('Y-m-d H:i:s');
        foreach ($webSkus as $value) {
            if ($value['outer_sku_status'] == 1) {
                //上架
                if ($value['stock'] > 0) {
                    //在售
                    $onSalesNum++;
                } else {
                    if ($value['presell_status'] == 1 && $nowDate >= $value['presell_start_time'] && $nowDate <= $value['presell_end_time']) {
                        //开预售
                        if ($value['presell_num'] > 0) {
                            $onSalesNum++;
                        } else {
                            $SalesOutNum++;
                        }
                    } else {
                        //未开预售
                        $SalesOutNum++;
                    }
                }
            } else {
                //下架
                $downShelvesNum++;
            }
        }
        $arr['glass_in_sale_num'] = $onSalesNum;  //在售
        $arr['glass_shelves_num'] = $downShelvesNum;  //下架
        $arr['glass_presell_num'] = $SalesOutNum;  //售罄
        $skus1 = $item->getOrnamentsSku();
        $map_where1['sku'] = ['in', $skus1];
        $webSkus1 = $model
            ->where($map_where1)
            ->where($site_where)
            ->select();
        $onSalesNum1 = 0;  //在售
        $SalesOutNum1 = 0;  //售罄
        $downShelvesNum1 = 0;  //下架
        foreach ($webSkus1 as $value) {
            if ($value['outer_sku_status'] == 1) {
                //上架
                if ($value['stock'] > 0) {
                    //在售
                    $onSalesNum1++;
                } else {
                    if ($value['presell_status'] == 1 && $nowDate >= $value['presell_start_time'] && $nowDate <= $value['presell_end_time']) {
                        //开预售
                        if ($value['presell_num'] > 0) {
                            $onSalesNum1++;
                        } else {
                            $SalesOutNum1++;
                        }
                    } else {
                        //未开预售
                        $SalesOutNum1++;
                    }
                }
            } else {
                //下架
                $downShelvesNum1++;
            }
        }
        $arr['box_in_sale_num'] = $onSalesNum1;
        $arr['box_shelves_num'] = $downShelvesNum1;
        $arr['box_presell_num'] = $SalesOutNum1;
        $datacenterDayId = Db::name('datacenter_day')->insertGetId($arr);
        //同步es数据
        (new AsyncDatacenterDay())->runInsert($datacenterDayId);
        echo $date_time . "\n";
        echo date("Y-m-d H:i:s") . "\n";
        usleep(100000);
    }

    //运营数据中心 zeelooljp
    public function zeelooljp_day_data()
    {
        $model = new \app\admin\model\itemmanage\ItemPlatformSku();
        $this->order = new \app\admin\model\order\order\ZeeloolJp();
        $operate_model = Db::connect('database.db_zeelool_jp');
        $operate_model->table('customer_entity')->query("set time_zone='+8:00'");
        $operate_model->table('oc_vip_order')->query("set time_zone='+8:00'");
        $operate_model->table('sales_flat_quote')->query("set time_zone='+8:00'");
        $operate_model->table('sales_flat_order')->query("set time_zone='+8:00'");

        $date_time = date('Y-m-d', strtotime("-1 day"));
        //查询时间
        $arr = [];
        $arr['site'] = 11;
        $arr['day_date'] = $date_time;
        //注册用户数
        $register_where = [];
        $register_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['register_num'] = $operate_model->table('customer_entity')->where($register_where)->count();
        //总的订单数
        $order_where = [];
        $order_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['sum_order_num'] = $operate_model->table('sales_flat_order')->where($order_where)->where('order_type',
            1)->count();
        //登录用户数
        $customer_where = [];
        $customer_where[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['login_user_num'] = $operate_model->table('customer_entity')->where($customer_where)->count();
        //新增vip用户数
        $vip_where = [];
        $vip_where[] = ['exp', Db::raw("DATE_FORMAT(start_time, '%Y-%m-%d') = '" . $date_time . "'")];
        $vip_where['order_status'] = 'Success';
        $arr['vip_user_num'] = $operate_model->table('oc_vip_order')->where($vip_where)->count();
        //支付成功的订单数
        $order_where = [];
        $order_where[] = ['exp', Db::raw("DATE_FORMAT(payment_time, '%Y-%m-%d') = '" . $date_time . "'")];
        $order_where['status'] = [
            'in',
            [
                'free_processing',
                'processing',
                'complete',
                'paypal_reversed',
                'payment_review',
                'paypal_canceled_reversal',
                'delivered',
                'delivery',
            ],
        ];
        $arr['order_num'] = $this->order->where($order_where)->where('order_type', 1)->count();
        //销售额
        $arr['sales_total_money'] = $this->order->where($order_where)->where('order_type', 1)->sum('base_grand_total');
        //邮费
        $arr['shipping_total_money'] = $this->order->where($order_where)->where('order_type',
            1)->sum('base_shipping_amount');
        //客单价
        $arr['order_unit_price'] = $arr['order_num'] == 0 ? 0 : round($arr['sales_total_money'] / $arr['order_num'], 2);
        //中位数
        $sales_total_money = $this->order->where($order_where)->where('order_type', 1)->column('base_grand_total');
        $arr['order_total_midnum'] = $this->median($sales_total_money);
        //标准差
        $arr['order_total_standard'] = $this->getVariance($sales_total_money);
        //补发订单数
        $arr['replacement_order_num'] = $this->order->where($order_where)->where('order_type', 4)->count();
        //补发销售额
        $arr['replacement_order_total'] = $this->order->where($order_where)->where('order_type',
            4)->sum('base_grand_total');
        //网红订单数
        $arr['online_celebrity_order_num'] = $this->order->where($order_where)->where('order_type', 3)->count();
        //补发销售额
        $arr['online_celebrity_order_total'] = $this->order->where($order_where)->where('order_type',
            3)->sum('base_grand_total');
        //新建购物车数量
        $cart_where1 = [];
        $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['new_cart_num'] = $operate_model->table('sales_flat_quote')->where($cart_where1)->where('base_grand_total',
            'gt', 0)->count();
        //更新购物车数量
        $cart_where2 = [];
        $cart_where2[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['update_cart_num'] = $operate_model->table('sales_flat_quote')->where($cart_where2)->where('base_grand_total',
            'gt', 0)->count();
        //新增购物车转化率
        $arr['cart_rate'] = $arr['new_cart_num'] ? round($arr['order_num'] / $arr['new_cart_num'] * 100, 2) : 0;
        //更新购物车转化率
        $arr['update_cart_cart'] = $arr['update_cart_num'] ? round($arr['order_num'] / $arr['update_cart_num'] * 100,
            2) : 0;
        //当天创建的用户当天产生订单的转化率
        $status_where['status'] = [
            'in',
            [
                'free_processing',
                'processing',
                'complete',
                'paypal_reversed',
                'payment_review',
                'paypal_canceled_reversal',
                'delivered',
                'delivery',
            ],
        ];
        $status_where['order_type'] = 1;
        //当天注册用户数
        $register_userids = $operate_model->table('customer_entity')->where($cart_where1)->column('entity_id');
        $register_num = count($register_userids);
        //当天注册用户在当天下单的用户数
        $order_user_count1 = 0;
        foreach ($register_userids as $register_userid) {
            //判断当前用户在当天是否下单
            $order = $operate_model->table('sales_flat_order')->where($cart_where1)->where($status_where)->where('customer_id',
                $register_userid)->value('entity_id');
            if ($order) {
                $order_user_count1++;
            }
        }
        $arr['create_user_change_rate'] = $register_num ? round($order_user_count1 / $register_num * 100, 2) : 0;
        //当天更新用户当天产生订单的转化率
        $update_userids = $operate_model->table('customer_entity')->where($cart_where2)->column('entity_id');
        $update_num = count($update_userids);
        //当天活跃更新用户数在当天是否下单
        $order_user_count2 = 0;
        foreach ($update_userids as $update_userid) {
            //判断活跃用户在当天下单的用户数
            $order = $operate_model->table('sales_flat_order')->where($cart_where1)->where($status_where)->where('customer_id',
                $update_userid)->value('entity_id');
            if ($order) {
                $order_user_count2++;
            }
        }
        $arr['update_user_change_rate'] = $update_num ? round($order_user_count2 / $update_num * 100, 0) : 0;
        //虚拟库存
        $virtual_where['platform_type'] = 11;
        $virtual_where['i.category_id'] = ['<>', 43];
        $virtual_where['i.is_del'] = 1;
        $virtual_where['i.is_open'] = 1;
        $arr['virtual_stock'] = $model->alias('p')->join('fa_item i',
            'p.sku=i.sku')->where($virtual_where)->sum('p.stock');
        //在售，预售，下架
        $item = new \app\admin\model\itemmanage\Item();
        $site_where['platform_type'] = $arr['site'];
        $skus = $item->getFrameSku();
        $map_where['sku'] = ['in', $skus];
        $webSkus = $model
            ->where($map_where)
            ->where($site_where)
            ->select();
        $onSalesNum = 0;  //在售
        $SalesOutNum = 0;  //售罄
        $downShelvesNum = 0;  //下架
        $nowDate = date('Y-m-d H:i:s');
        foreach ($webSkus as $value) {
            if ($value['outer_sku_status'] == 1) {
                //上架
                if ($value['stock'] > 0) {
                    //在售
                    $onSalesNum++;
                } else {
                    if ($value['presell_status'] == 1 && $nowDate >= $value['presell_start_time'] && $nowDate <= $value['presell_end_time']) {
                        //开预售
                        if ($value['presell_num'] > 0) {
                            $onSalesNum++;
                        } else {
                            $SalesOutNum++;
                        }
                    } else {
                        //未开预售
                        $SalesOutNum++;
                    }
                }
            } else {
                //下架
                $downShelvesNum++;
            }
        }
        $arr['glass_in_sale_num'] = $onSalesNum;  //在售
        $arr['glass_shelves_num'] = $downShelvesNum;  //下架
        $arr['glass_presell_num'] = $SalesOutNum;  //售罄
        $skus1 = $item->getOrnamentsSku();
        $map_where1['sku'] = ['in', $skus1];
        $webSkus1 = $model
            ->where($map_where1)
            ->where($site_where)
            ->select();
        $onSalesNum1 = 0;  //在售
        $SalesOutNum1 = 0;  //售罄
        $downShelvesNum1 = 0;  //下架
        foreach ($webSkus1 as $value) {
            if ($value['outer_sku_status'] == 1) {
                //上架
                if ($value['stock'] > 0) {
                    //在售
                    $onSalesNum1++;
                } else {
                    if ($value['presell_status'] == 1 && $nowDate >= $value['presell_start_time'] && $nowDate <= $value['presell_end_time']) {
                        //开预售
                        if ($value['presell_num'] > 0) {
                            $onSalesNum1++;
                        } else {
                            $SalesOutNum1++;
                        }
                    } else {
                        //未开预售
                        $SalesOutNum1++;
                    }
                }
            } else {
                //下架
                $downShelvesNum1++;
            }
        }
        $arr['box_in_sale_num'] = $onSalesNum1;
        $arr['box_shelves_num'] = $downShelvesNum1;
        $arr['box_presell_num'] = $SalesOutNum1;
        $datacenterDayId = Db::name('datacenter_day')->insertGetId($arr);
        //同步es数据
        (new AsyncDatacenterDay())->runInsert($datacenterDayId);
        echo $date_time . "\n";
        echo date("Y-m-d H:i:s") . "\n";
        usleep(100000);
    }

    //运营数据中心 zeeloolfr
    public function zeeloolfr_day_data()
    {
        $model = new \app\admin\model\itemmanage\ItemPlatformSku();
        $this->order = new \app\admin\model\order\order\ZeeloolFr();
        $operate_model = Db::connect('database.db_zeelool_fr');
        $operate_model->table('customer_entity')->query("set time_zone='+8:00'");
        $operate_model->table('oc_vip_order')->query("set time_zone='+8:00'");
        $operate_model->table('sales_flat_quote')->query("set time_zone='+8:00'");
        $operate_model->table('sales_flat_order')->query("set time_zone='+8:00'");

        $date_time = date('Y-m-d', strtotime("-1 day"));
        //查询时间
        $arr = [];
        $arr['site'] = 15;
        $arr['day_date'] = $date_time;
        //注册用户数
        $register_where = [];
        $register_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['register_num'] = $operate_model->table('customer_entity')->where($register_where)->count();
        //总的订单数
        $order_where = [];
        $order_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['sum_order_num'] = $operate_model->table('sales_flat_order')->where($order_where)->where('order_type',
            1)->count();
        //登录用户数
        $customer_where = [];
        $customer_where[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['login_user_num'] = $operate_model->table('customer_entity')->where($customer_where)->count();
        //新增vip用户数
        $vip_where = [];
        $vip_where[] = ['exp', Db::raw("DATE_FORMAT(start_time, '%Y-%m-%d') = '" . $date_time . "'")];
        $vip_where['order_status'] = 'Success';
        $arr['vip_user_num'] = $operate_model->table('oc_vip_order')->where($vip_where)->count();
        //支付成功的订单数
        $order_where = [];
        $order_where[] = ['exp', Db::raw("DATE_FORMAT(payment_time, '%Y-%m-%d') = '" . $date_time . "'")];
        $order_where['status'] = [
            'in',
            [
                'free_processing',
                'processing',
                'complete',
                'paypal_reversed',
                'payment_review',
                'paypal_canceled_reversal',
                'delivered',
                'delivery',
            ],
        ];
        $arr['order_num'] = $this->order->where($order_where)->where('order_type', 1)->count();
        //销售额
        $arr['sales_total_money'] = $this->order->where($order_where)->where('order_type', 1)->sum('base_grand_total');
        //邮费
        $arr['shipping_total_money'] = $this->order->where($order_where)->where('order_type',
            1)->sum('base_shipping_amount');
        //客单价
        $arr['order_unit_price'] = $arr['order_num'] == 0 ? 0 : round($arr['sales_total_money'] / $arr['order_num'], 2);
        //中位数
        $sales_total_money = $this->order->where($order_where)->where('order_type', 1)->column('base_grand_total');
        $arr['order_total_midnum'] = $this->median($sales_total_money);
        //标准差
        $arr['order_total_standard'] = $this->getVariance($sales_total_money);
        //补发订单数
        $arr['replacement_order_num'] = $this->order->where($order_where)->where('order_type', 4)->count();
        //补发销售额
        $arr['replacement_order_total'] = $this->order->where($order_where)->where('order_type',
            4)->sum('base_grand_total');
        //网红订单数
        $arr['online_celebrity_order_num'] = $this->order->where($order_where)->where('order_type', 3)->count();
        //补发销售额
        $arr['online_celebrity_order_total'] = $this->order->where($order_where)->where('order_type',
            3)->sum('base_grand_total');
        //新建购物车数量
        $cart_where1 = [];
        $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['new_cart_num'] = $operate_model->table('sales_flat_quote')->where($cart_where1)->where('base_grand_total',
            'gt', 0)->count();
        //更新购物车数量
        $cart_where2 = [];
        $cart_where2[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['update_cart_num'] = $operate_model->table('sales_flat_quote')->where($cart_where2)->where('base_grand_total',
            'gt', 0)->count();
        //新增购物车转化率
        $arr['cart_rate'] = $arr['new_cart_num'] ? round($arr['order_num'] / $arr['new_cart_num'] * 100, 2) : 0;
        //更新购物车转化率
        $arr['update_cart_cart'] = $arr['update_cart_num'] ? round($arr['order_num'] / $arr['update_cart_num'] * 100,
            2) : 0;
        //当天创建的用户当天产生订单的转化率
        $status_where['status'] = [
            'in',
            [
                'free_processing',
                'processing',
                'complete',
                'paypal_reversed',
                'payment_review',
                'paypal_canceled_reversal',
                'delivered',
                'delivery',
            ],
        ];
        $status_where['order_type'] = 1;
        //当天注册用户数
        $register_userids = $operate_model->table('customer_entity')->where($cart_where1)->column('entity_id');
        $register_num = count($register_userids);
        //当天注册用户在当天下单的用户数
        $order_user_count1 = 0;
        foreach ($register_userids as $register_userid) {
            //判断当前用户在当天是否下单
            $order = $operate_model->table('sales_flat_order')->where($cart_where1)->where($status_where)->where('customer_id',
                $register_userid)->value('entity_id');
            if ($order) {
                $order_user_count1++;
            }
        }
        $arr['create_user_change_rate'] = $register_num ? round($order_user_count1 / $register_num * 100, 2) : 0;
        //当天更新用户当天产生订单的转化率
        $update_userids = $operate_model->table('customer_entity')->where($cart_where2)->column('entity_id');
        $update_num = count($update_userids);
        //当天活跃更新用户数在当天是否下单
        $order_user_count2 = 0;
        foreach ($update_userids as $update_userid) {
            //判断活跃用户在当天下单的用户数
            $order = $operate_model->table('sales_flat_order')->where($cart_where1)->where($status_where)->where('customer_id',
                $update_userid)->value('entity_id');
            if ($order) {
                $order_user_count2++;
            }
        }
        $arr['update_user_change_rate'] = $update_num ? round($order_user_count2 / $update_num * 100, 0) : 0;
        //虚拟库存
        $virtual_where['platform_type'] = 15;
        $virtual_where['i.category_id'] = ['<>', 43];
        $virtual_where['i.is_del'] = 1;
        $virtual_where['i.is_open'] = 1;
        $arr['virtual_stock'] = $model->alias('p')->join('fa_item i',
            'p.sku=i.sku')->where($virtual_where)->sum('p.stock');
        //在售，预售，下架
        $item = new \app\admin\model\itemmanage\Item();
        $site_where['platform_type'] = $arr['site'];
        $skus = $item->getFrameSku();
        $map_where['sku'] = ['in', $skus];
        $webSkus = $model
            ->where($map_where)
            ->where($site_where)
            ->select();
        $onSalesNum = 0;  //在售
        $SalesOutNum = 0;  //售罄
        $downShelvesNum = 0;  //下架
        $nowDate = date('Y-m-d H:i:s');
        foreach ($webSkus as $value) {
            if ($value['outer_sku_status'] == 1) {
                //上架
                if ($value['stock'] > 0) {
                    //在售
                    $onSalesNum++;
                } else {
                    if ($value['presell_status'] == 1 && $nowDate >= $value['presell_start_time'] && $nowDate <= $value['presell_end_time']) {
                        //开预售
                        if ($value['presell_num'] > 0) {
                            $onSalesNum++;
                        } else {
                            $SalesOutNum++;
                        }
                    } else {
                        //未开预售
                        $SalesOutNum++;
                    }
                }
            } else {
                //下架
                $downShelvesNum++;
            }
        }
        $arr['glass_in_sale_num'] = $onSalesNum;  //在售
        $arr['glass_shelves_num'] = $downShelvesNum;  //下架
        $arr['glass_presell_num'] = $SalesOutNum;  //售罄
        $skus1 = $item->getOrnamentsSku();
        $map_where1['sku'] = ['in', $skus1];
        $webSkus1 = $model
            ->where($map_where1)
            ->where($site_where)
            ->select();
        $onSalesNum1 = 0;  //在售
        $SalesOutNum1 = 0;  //售罄
        $downShelvesNum1 = 0;  //下架
        foreach ($webSkus1 as $value) {
            if ($value['outer_sku_status'] == 1) {
                //上架
                if ($value['stock'] > 0) {
                    //在售
                    $onSalesNum1++;
                } else {
                    if ($value['presell_status'] == 1 && $nowDate >= $value['presell_start_time'] && $nowDate <= $value['presell_end_time']) {
                        //开预售
                        if ($value['presell_num'] > 0) {
                            $onSalesNum1++;
                        } else {
                            $SalesOutNum1++;
                        }
                    } else {
                        //未开预售
                        $SalesOutNum1++;
                    }
                }
            } else {
                //下架
                $downShelvesNum1++;
            }
        }
        $arr['box_in_sale_num'] = $onSalesNum1;
        $arr['box_shelves_num'] = $downShelvesNum1;
        $arr['box_presell_num'] = $SalesOutNum1;
        $datacenterDayId = Db::name('datacenter_day')->insertGetId($arr);
        //同步es数据
        (new AsyncDatacenterDay())->runInsert($datacenterDayId);
        echo $date_time . "\n";
        echo date("Y-m-d H:i:s") . "\n";
        usleep(100000);
    }

    //运营数据中心  小站
    public function other_day_data()
    {
        //亚马逊
        $arr = $this->getGoodsStatus(8);
        $datacenterDayId = Db::name('datacenter_day')->insertGetId($arr);
        //同步es数据
        (new AsyncDatacenterDay())->runInsert($datacenterDayId);

        //zeelool_es
        $arr = $this->getGoodsStatus(9);
        $datacenterDayId = Db::name('datacenter_day')->insertGetId($arr);
        //同步es数据
        (new AsyncDatacenterDay())->runInsert($datacenterDayId);

        echo date("Y-m-d H:i:s") . "\n";
        echo "all is ok" . "\n";
    }

    /**
     * 获取商品各状态的数量
     *
     * @param $site
     *
     * @author mjj
     * @date   2021/5/25 17:35:23
     */
    public function getGoodsStatus($site)
    {
        $model = new \app\admin\model\itemmanage\ItemPlatformSku();
        $item = new \app\admin\model\itemmanage\Item();
        $date_time = date('Y-m-d', strtotime("-1 day"));
        $skus = $item->getFrameSku();
        $map_where['sku'] = ['in', $skus];
        $skus1 = $item->getOrnamentsSku();
        $map_where1['sku'] = ['in', $skus1];
        $site_where['platform_type'] = $platform_where['platform_type'] = $site;
        $where['i.category_id'] = ['<>', 43];
        $where['i.is_del'] = 1;
        $where['i.is_open'] = 1;
        $arr['site'] = $site;
        $arr['day_date'] = $date_time;
        $arr['virtual_stock'] = $model
            ->alias('p')
            ->join('fa_item i', 'p.sku=i.sku')
            ->where($where)
            ->where($platform_where)
            ->sum('p.stock');
        $webSkus = $model
            ->where($map_where)
            ->where($site_where)
            ->select();
        $onSalesNum = 0;  //在售
        $SalesOutNum = 0;  //售罄
        $downShelvesNum = 0;  //下架
        $nowDate = date('Y-m-d H:i:s');
        foreach ($webSkus as $value) {
            if ($value['outer_sku_status'] == 1) {
                //上架
                if ($value['stock'] > 0) {
                    //在售
                    $onSalesNum++;
                } else {
                    if ($value['presell_status'] == 1 && $nowDate >= $value['presell_start_time'] && $nowDate <= $value['presell_end_time']) {
                        //开预售
                        if ($value['presell_num'] > 0) {
                            $onSalesNum++;
                        } else {
                            $SalesOutNum++;
                        }
                    } else {
                        //未开预售
                        $SalesOutNum++;
                    }
                }
            } else {
                //下架
                $downShelvesNum++;
            }
        }
        $arr['glass_in_sale_num'] = $onSalesNum;  //在售
        $arr['glass_shelves_num'] = $downShelvesNum;  //下架
        $arr['glass_presell_num'] = $SalesOutNum;  //售罄
        $skus1 = $item->getOrnamentsSku();
        $map_where1['sku'] = ['in', $skus1];
        $webSkus1 = $model
            ->where($map_where1)
            ->where($site_where)
            ->select();
        $onSalesNum1 = 0;  //在售
        $SalesOutNum1 = 0;  //售罄
        $downShelvesNum1 = 0;  //下架
        foreach ($webSkus1 as $value) {
            if ($value['outer_sku_status'] == 1) {
                //上架
                if ($value['stock'] > 0) {
                    //在售
                    $onSalesNum1++;
                } else {
                    if ($value['presell_status'] == 1 && $nowDate >= $value['presell_start_time'] && $nowDate <= $value['presell_end_time']) {
                        //开预售
                        if ($value['presell_num'] > 0) {
                            $onSalesNum1++;
                        } else {
                            $SalesOutNum1++;
                        }
                    } else {
                        //未开预售
                        $SalesOutNum1++;
                    }
                }
            } else {
                //下架
                $downShelvesNum1++;
            }
        }
        $arr['box_in_sale_num'] = $onSalesNum1;
        $arr['box_shelves_num'] = $downShelvesNum1;
        $arr['box_presell_num'] = $SalesOutNum1;

        return $arr;
    }

    //运营数据中心 wesee
    public function wesee_day_data()
    {
        $model = new \app\admin\model\itemmanage\ItemPlatformSku();
        $wesee = new \app\admin\model\order\order\NewWeseeoptical();
        $wesee_data = new \app\admin\model\operatedatacenter\Weseeoptical();
        $wesee_model = Db::connect('database.db_weseeoptical');
        $wesee_model->table('users')->query("set time_zone='+8:00'");
        $wesee_model->table('orders')->query("set time_zone='+8:00'");

        $date_time = date('Y-m-d', strtotime("-1 day"));
        $date_time_start = date('Y-m-d 00:00:00', strtotime("-1 day"));
        $date_time_end = date('Y-m-d 23:59:59', strtotime("-1 day"));
        //查询时间
        $arr = [];
        $arr['site'] = 5;
        $arr['day_date'] = $date_time;
        //活跃用户数
        // $arr['active_user_num'] = $this->google_active_user(1, $date_time);
        //注册用户数
        $register_where = [];
        $arr['register_num'] = $wesee_model->table('users')->where('created_at', 'between', [$date_time_start, $date_time_end])->count();

        //总的订单数
        $order_where = [];
        $arr['sum_order_num'] = $wesee_model->table('orders')->where('created_at', 'between', [$date_time_start, $date_time_end])->where('order_type',
            1)->count();
        //登录用户数
        $customer_where = [];
        $arr['login_user_num'] = $wesee_model->table('users')->where('updated_at', 'between', [$date_time_start, $date_time_end])->count();

        //新增vip用户数
        $arr['vip_user_num'] = 0;
        //支付成功的订单数

        $arr['order_num'] = $wesee->where('status', 'in', [2, 3, 4, 9, 10])->where('updated_at', 'between', [$date_time_start, $date_time_end])->where('order_type', 1)->count();
        //销售额
        $arr['sales_total_money'] = $wesee->where('status', 'in', [2, 3, 4, 9, 10])->where('updated_at', 'between', [$date_time_start, $date_time_end])->where('order_type',
            1)->sum('base_actual_amount_paid');
        //邮费
        $arr['shipping_total_money'] = $wesee->where('status', 'in', [2, 3, 4, 9, 10])->where('updated_at', 'between', [$date_time_start, $date_time_end])->where('order_type',
            1)->sum('base_freight_price');
        //客单价
        $arr['order_unit_price'] = $arr['order_num'] == 0 ? 0 : round($arr['sales_total_money'] / $arr['order_num'], 2);
        //中位数
        $sales_total_money = $wesee->where('status', 'in', [2, 3, 4, 9, 10])->where('updated_at', 'between', [$date_time_start, $date_time_end])->where('order_type', 1)->column('base_actual_amount_paid');
        $arr['order_total_midnum'] = $this->median($sales_total_money);
        //标准差
        $arr['order_total_standard'] = $this->getVariance($sales_total_money);
        //补发订单数
        $arr['replacement_order_num'] = $wesee->where('status', 'in', [2, 3, 4, 9, 10])->where('updated_at', 'between', [$date_time_start, $date_time_end])->where('order_type', 4)->count();
        //补发销售额
        $arr['replacement_order_total'] = $wesee->where('status', 'in', [2, 3, 4, 9, 10])->where('updated_at', 'between', [$date_time_start, $date_time_end])->where('order_type',
            4)->sum('base_actual_amount_paid');
        //网红订单数
        $arr['online_celebrity_order_num'] = 0;
        //补发销售额
        $arr['online_celebrity_order_total'] = 0;
        //新建购物车数量
        $arr['new_cart_num'] = 0;
        //更新购物车数量
        $arr['update_cart_num'] = 0;
        //新增购物车转化率
        $arr['cart_rate'] = 0;
        //更新购物车转化率
        $arr['update_cart_cart'] = 0;
        //当天创建的用户当天产生订单的转化率
        $status_where['status'] = [
            'in',
            [
                2,
                3,
                4,
                9,
                10,
            ],
        ];
        $status_where['order_type'] = 1;
        //当天注册用户数
        $register_userids = $wesee_model->table('users')->where('created_at', 'between', [$date_time_start, $date_time_end])->column('id');
        $register_num = count($register_userids);
        //当天注册用户在当天下单的用户数
        $order_user_count1 = 0;
        foreach ($register_userids as $register_userid) {
            //判断当前用户在当天是否下单
            $order = $wesee->where('created_at', 'between', [$date_time_start, $date_time_end])->where($status_where)->where('user_id',
                $register_userid)->value('id');
            if ($order) {
                $order_user_count1++;
            }
        }
        $arr['create_user_change_rate'] = $register_num ? round($order_user_count1 / $register_num * 100, 2) : 0;
        //当天更新用户当天产生订单的转化率
        $update_userids = $wesee_model->table('users')->where('updated_at', 'between', [$date_time_start, $date_time_end])->column('id');
        $update_num = count($update_userids);
        //当天活跃更新用户数在当天是否下单
        $order_user_count2 = 0;
        foreach ($update_userids as $update_userid) {
            //判断活跃用户在当天下单的用户数
            $order = $wesee->where('created_at', 'between', [$date_time_start, $date_time_end])->where($status_where)->where('user_id',
                $update_userid)->value('id');
            if ($order) {
                $order_user_count2++;
            }
        }
        $arr['update_user_change_rate'] = $update_num ? round($order_user_count2 / $update_num * 100, 0) : 0;
        //虚拟库存
        $virtual_where['platform_type'] = 5;
        $virtual_where['i.category_id'] = ['<>', 43];
        $virtual_where['i.is_del'] = 1;
        $virtual_where['i.is_open'] = 1;
        $arr['virtual_stock'] = $model->alias('p')->join('fa_item i',
            'p.sku=i.sku')->where($virtual_where)->sum('p.stock');
        //在售，预售，下架
        $item = new \app\admin\model\itemmanage\Item();
        $site_where['platform_type'] = $arr['site'];
        $skus = $item->getFrameSku();
        $map_where['sku'] = ['in', $skus];
        $webSkus = $model
            ->where($map_where)
            ->where($site_where)
            ->select();
        $onSalesNum = 0;  //在售
        $SalesOutNum = 0;  //售罄
        $downShelvesNum = 0;  //下架
        $nowDate = date('Y-m-d H:i:s');
        foreach ($webSkus as $value) {
            if ($value['outer_sku_status'] == 1) {
                //上架
                if ($value['stock'] > 0) {
                    //在售
                    $onSalesNum++;
                } else {
                    if ($value['presell_status'] == 1 && $nowDate >= $value['presell_start_time'] && $nowDate <= $value['presell_end_time']) {
                        //开预售
                        if ($value['presell_num'] > 0) {
                            $onSalesNum++;
                        } else {
                            $SalesOutNum++;
                        }
                    } else {
                        //未开预售
                        $SalesOutNum++;
                    }
                }
            } else {
                //下架
                $downShelvesNum++;
            }
        }
        $arr['glass_in_sale_num'] = $onSalesNum;  //在售
        $arr['glass_shelves_num'] = $downShelvesNum;  //下架
        $arr['glass_presell_num'] = $SalesOutNum;  //售罄
        $skus1 = $item->getOrnamentsSku();
        $map_where1['sku'] = ['in', $skus1];
        $webSkus1 = $model
            ->where($map_where1)
            ->where($site_where)
            ->select();
        $onSalesNum1 = 0;  //在售
        $SalesOutNum1 = 0;  //售罄
        $downShelvesNum1 = 0;  //下架
        foreach ($webSkus1 as $value) {
            if ($value['outer_sku_status'] == 1) {
                //上架
                if ($value['stock'] > 0) {
                    //在售
                    $onSalesNum1++;
                } else {
                    if ($value['presell_status'] == 1 && $nowDate >= $value['presell_start_time'] && $nowDate <= $value['presell_end_time']) {
                        //开预售
                        if ($value['presell_num'] > 0) {
                            $onSalesNum1++;
                        } else {
                            $SalesOutNum1++;
                        }
                    } else {
                        //未开预售
                        $SalesOutNum1++;
                    }
                }
            } else {
                //下架
                $downShelvesNum1++;
            }
        }
        $arr['box_in_sale_num'] = $onSalesNum1;
        $arr['box_shelves_num'] = $downShelvesNum1;
        $arr['box_presell_num'] = $SalesOutNum1;
        $datacenterDayId = Db::name('datacenter_day')->insertGetId($arr);
        //同步es数据
        (new AsyncDatacenterDay())->runInsert($datacenterDayId);
        echo $date_time . "\n";
        echo date("Y-m-d H:i:s") . "\n";
        usleep(100000);
    }

    //订单发出时间计划任务
    public function order_send_time()
    {
        $process = new \app\admin\model\order\order\NewOrderProcess;
        $orderitemprocess = new \app\admin\model\order\order\NewOrderItemProcess();
        //查询所有订单
        $order = $process->where('order_prescription_type', 0)->column('order_id');
        foreach ($order as $key => $value) {
            $order_type = $orderitemprocess->where('order_id', $value)->column('order_prescription_type');
            //查不到结果跳过 防止子单表延迟两分钟查不到数据
            if (!$order_type) {
                continue;
            }

            if (in_array(3, $order_type)) {
                $type = 3;
            } elseif (in_array(2, $order_type)) {
                $type = 2;
            } else {
                $type = 1;
            }
            $process->where('order_id', $value)->update(['order_prescription_type' => $type]);
            echo $value . ' is ok' . "\n";
            usleep(100000);
        }
    }

    //产品等级销量数据计划任务
    public function product_level_salesnum()
    {
        $now = date('Y-m-d', strtotime("-1 day"));
        $arr['day_date'] = $now;
        $zeelool = $this->getSalesnum(1);
        $voogueme = $this->getSalesnum(2);
        $nihao = $this->getSalesnum(3);
        $arr['sales_num_a1'] = $zeelool[0] + $voogueme[0] + $nihao[0];
        $arr['sales_num_a'] = $zeelool[1] + $voogueme[1] + $nihao[1];
        $arr['sales_num_b'] = $zeelool[2] + $voogueme[2] + $nihao[2];
        $arr['sales_num_c1'] = $zeelool[3] + $voogueme[3] + $nihao[3];
        $arr['sales_num_c'] = $zeelool[4] + $voogueme[4] + $nihao[4];
        $arr['sales_num_d'] = $zeelool[5] + $voogueme[5] + $nihao[5];
        $arr['sales_num_e'] = $zeelool[6] + $voogueme[6] + $nihao[6];
        $arr['sales_num_f'] = $zeelool[7] + $voogueme[7] + $nihao[7];
        Db::name('datacenter_day_supply')->insert($arr);
        echo "ok";
    }

    public function getSalesnum($site)
    {
        $this->order = new \app\admin\model\order\order\NewOrder();
        $this->productGrade = new \app\admin\model\ProductGrade();
        $this->itemplatformsku = new \app\admin\model\itemmanage\ItemPlatformSku;
        //所选时间段内有销量的平台sku
        $start = date('Y-m-d', strtotime("-1 day"));
        $end = date('Y-m-d 23:59:59', strtotime("-1 day"));
        $start_time = strtotime($start);
        $end_time = strtotime($end);
        $where['o.payment_time'] = ['between', [$start_time, $end_time]];
        $where['o.status'] = [
            'in',
            [
                'free_processing',
                'processing',
                'complete',
                'paypal_reversed',
                'payment_review',
                'paypal_canceled_reversal',
                'delivered',
                'delivery',
            ],
        ];
        $where['o.site'] = $site;
        $order = $this->order
            ->alias('o')
            ->join('fa_order_item_option i', 'o.entity_id=i.magento_order_id and o.site=i.site')
            ->field('i.sku,count(*) as count')
            ->where($where)
            ->group('i.sku')
            ->select();
        $grade1 = 0;
        $grade2 = 0;
        $grade3 = 0;
        $grade4 = 0;
        $grade5 = 0;
        $grade6 = 0;
        $grade7 = 0;
        $grade8 = 0;
        foreach ($order as $key => $value) {
            $sku = $this->itemplatformsku->getTrueSku($value['sku'], $site);
            //查询该品的等级
            $grade = $this->productGrade->where('true_sku', $sku)->value('grade');
            switch ($grade) {
                case 'A+':
                    $grade1 += $value['count'];
                    break;
                case 'A':
                    $grade2 += $value['count'];
                    break;
                case 'B':
                    $grade3 += $value['count'];
                    break;
                case 'C+':
                    $grade4 += $value['count'];
                    break;
                case 'C':
                    $grade5 += $value['count'];
                    break;
                case 'D':
                    $grade6 += $value['count'];
                    break;
                case 'E':
                    $grade7 += $value['count'];
                    break;
                case 'F':
                    $grade8 += $value['count'];
                    break;
                default:
                    break;
            }
        }
        $arr = [
            $grade1,
            $grade2,
            $grade3,
            $grade4,
            $grade5,
            $grade6,
            $grade7,
            $grade8,
        ];

        return $arr;
    }

    //ga的数据单独摘出来跑 防止ga接口数据报错 2020.11.2防止了ga的数据报错
    public function only_ga_data()
    {
        $this->getGaData(1);   //zeelool
        $this->getGaData(2);   //voogueme
        $this->getGaData(3);   //nihao
        $this->getGaData(5);   //批发站
        $this->getGaData(10);  //de站
        $this->getGaData(11);  //jp站
        $this->getGaData(15);  //fr站
        echo "ok";
    }

    public function getGaData($site)
    {
        $date_time = date('Y-m-d', strtotime("-1 day"));
        $date_time_behind = date('Y-m-d', strtotime("-2 day"));
        $lastData = Db::name('datacenter_day')
            ->where('day_date', $date_time_behind)
            ->where('site', $site)
            ->field('new_cart_num,order_num')
            ->find();
        $data = Db::name('datacenter_day')->where([
            'day_date' => $date_time,
            'site'     => $site,
        ])->field('order_num,new_cart_num,update_cart_num,active_user_num')->find();

        //活跃用户数
        $arr['active_user_num'] = $this->google_active_user($site, $date_time);
        //会话
        $arr['sessions'] = $this->google_session($site, $date_time);
        //前天的ga会话数
        $date_time_behind_sessions_z = $this->google_session($site, $date_time_behind);
        //会话转化率
        $arr['session_rate'] = $arr['sessions'] != 0 ? round($data['order_num'] / $arr['sessions'] * 100, 2) : 0;
        //新增加购率
        $arr['add_cart_rate'] = $arr['sessions'] ? round($data['new_cart_num'] / $arr['sessions'] * 100, 2) : 0;
        //更新加购率
        $arr['update_add_cart_rate'] = $arr['sessions'] ? round($data['update_cart_num'] / $arr['sessions'] * 100,
            2) : 0;
        $zeelool_data = new \app\admin\model\operatedatacenter\Zeelool();
        //着陆页数据
        $arr['landing_num'] = $zeelool_data->google_landing($site, $date_time);
        //产品详情页
        $arr['detail_num'] = $zeelool_data->google_target13($site, $date_time);
        //加购
        $arr['cart_num'] = $zeelool_data->google_target1($site, $date_time);
        //交易次数
        $arr['complete_num'] = $zeelool_data->google_target_end($site, $date_time);
        Db::name('datacenter_day')->where(['day_date' => $date_time, 'site' => $site])->update($arr);
        //同步es数据
        $updateData = ['day_date' => $date_time, 'site' => $site];
        (new AsyncDatacenterDay())->runUpdate($updateData);

        //更新前天的会话数 防止ga数据误差
        $lastArr['sessions'] = $date_time_behind_sessions_z;
        $lastArr['add_cart_rate'] = $lastArr['sessions'] ? round(($lastData['new_cart_num'] / $lastArr['sessions']) * 100, 2) : 0;
        $lastArr['session_rate'] = $lastArr['sessions'] ? round(($lastData['order_num'] / $lastArr['sessions']) * 100, 2) : 0;
        Db::name('datacenter_day')->where([
            'day_date' => $date_time_behind,
            'site'     => $site,
        ])->update($lastArr);
        //同步es数据
        $updateData = ['day_date' => $date_time_behind, 'site' => $site];
        (new AsyncDatacenterDay())->runUpdate($updateData);
        usleep(100000);
    }

    //计划任务跑每天的分类销量的数据
    public function day_data_goods_type()
    {
        $res1 = Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(1, 1));
        if ($res1) {
            echo 'z站平光镜ok';
        } else {
            echo 'z站平光镜不ok';
        }
        $res2 = Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(1, 2));
        if ($res2) {
            echo 'z站太阳镜ok';
        } else {
            echo 'z站太阳镜不ok';
        }
        $res3 = Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(1, 3));
        if ($res3) {
            echo 'z站老花镜ok';
        } else {
            echo 'z站老花镜不ok';
        }
        $res4 = Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(1, 4));
        if ($res4) {
            echo 'z站儿童镜ok';
        } else {
            echo 'z站儿童镜不ok';
        }
        $res5 = Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(1, 5));
        if ($res5) {
            echo 'z站运动镜ok';
        } else {
            echo 'z站运动镜不ok';
        }
        $res6 = Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(1, 6));
        if ($res6) {
            echo 'z站配饰ok';
        } else {
            echo 'z站配饰不ok';
        }
        $res7 = Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(2, 1));
        if ($res7) {
            echo 'v站平光镜ok';
        } else {
            echo 'v站平光镜不ok';
        }
        $res8 = Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(2, 2));
        if ($res8) {
            echo 'v站太阳镜ok';
        } else {
            echo 'v站太阳镜不ok';
        }
        $res9 = Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(2, 6));
        if ($res9) {
            echo 'v站配饰ok';
        } else {
            echo 'v站配饰不ok';
        }
        $res10 = Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(3, 1));
        if ($res10) {
            echo 'nihao站平光镜ok';
        } else {
            echo 'nihao站平光镜不ok';
        }
        $res11 = Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(3, 2));
        if ($res11) {
            echo 'nihao站配饰ok';
        } else {
            echo 'nihao站配饰不ok';
        }

        $res51 = Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(5, 1));
        if ($res51) {
            echo 'wesee站平光镜ok';
        } else {
            echo 'wesee站平光镜不ok';
        }
        $res52 = Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(5, 2));
        if ($res52) {
            echo 'wesee站太阳镜ok';
        } else {
            echo 'wesee站太阳镜不ok';
        }
        $res53 = Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(5, 3));
        if ($res53) {
            echo 'wesee站老花镜ok';
        } else {
            echo 'wesee站老花镜不ok';
        }
        $res54 = Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(5, 4));
        if ($res54) {
            echo 'wesee站儿童镜ok';
        } else {
            echo 'wesee站儿童镜不ok';
        }
        $res55 = Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(5, 5));
        if ($res55) {
            echo 'wesee站运动镜ok';
        } else {
            echo 'wesee站运动镜不ok';
        }
        $res56 = Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(5, 6));
        if ($res56) {
            echo 'wesee站配饰ok';
        } else {
            echo 'wesee站配饰不ok';
        }
        $res12 = Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(10, 1));
        if ($res12) {
            echo 'de站平光镜ok';
        } else {
            echo 'de站平光镜不ok';
        }
        $res13 = Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(10, 2));
        if ($res13) {
            echo 'de站太阳镜ok';
        } else {
            echo 'de站太阳镜不ok';
        }
        $res14 = Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(10, 6));
        if ($res14) {
            echo 'de站配饰ok';
        } else {
            echo 'de站配饰不ok';
        }
        $res15 = Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(11, 1));
        if ($res15) {
            echo 'jp站平光镜ok';
        } else {
            echo 'jp站平光镜不ok';
        }
        $res16 = Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(11, 2));
        if ($res16) {
            echo 'jp站太阳镜ok';
        } else {
            echo 'jp站太阳镜不ok';
        }
        $res17 = Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(11, 6));
        if ($res17) {
            echo 'jp站配饰ok';
        } else {
            echo 'jp站配饰不ok';
        }

        $res18 = Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(15, 1));
        if ($res18) {
            echo 'fr站平光镜ok';
        } else {
            echo 'fr站平光镜不ok';
        }
        $res19 = Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(15, 2));
        if ($res19) {
            echo 'fr站太阳镜ok';
        } else {
            echo 'fr站太阳镜不ok';
        }
        $res20 = Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(15, 6));
        if ($res20) {
            echo 'fr站配饰ok';
        } else {
            echo 'fr站配饰不ok';
        }
    }

    //统计昨天各品类镜框的销量
    public function goods_type_day_center($plat, $goods_type)
    {
        $this->order = new \app\admin\model\order\order\NewOrder();
        $this->orderitemoption = new \app\admin\model\order\order\NewOrderItemOption();
        $start = date('Y-m-d', strtotime('-1 day'));
        $seven_days = $start . ' 00:00:00 - ' . $start . ' 23:59:59';
        $createat = explode(' ', $seven_days);
        $where['o.payment_time'] = ['between', [strtotime($createat[0] . ' ' . $createat[1]), strtotime($createat[3] . ' ' . $createat[4])]];
        $where['o.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered', 'delivery']];
        $where['order_type'] = 1;
        $where['o.site'] = $plat;
        $where['goods_type'] = $goods_type;

        //某个品类眼镜的销售副数
        $frame_sales_num = $this->orderitemoption
            ->alias('i')
            ->join('fa_order o', 'i.magento_order_id=o.entity_id', 'left')
            ->where($where)
            ->sum('i.qty');
        //眼镜的折扣价格
        $frame_money = $this->orderitemoption
            ->alias('i')
            ->join('fa_order o', 'i.magento_order_id=o.entity_id', 'left')
            ->where($where)
            ->value('sum(base_original_price-i.base_discount_amount) as price');
        $frame_money = $frame_money ? round($frame_money, 2) : 0;
        $arr['day_date'] = $start;
        $arr['site'] = $plat;
        $arr['goods_type'] = $goods_type;
        $arr['glass_num'] = $frame_sales_num;
        $arr['sales_total_money'] = $frame_money;

        return $arr;
    }

    /**
     * 更新在途库存、待入库数量
     */
    public function change_stock()
    {
        //所有状态下的在途和待入库清零
        $_item = new \app\admin\model\itemmanage\Item;
        $_item_platform = new \app\admin\model\itemmanage\ItemPlatformSku;
        $list = $_item_platform
            ->alias('a')
            ->field('sku,sum(plat_on_way_stock) as all_on_way,sum(wait_instock_num) as all_instock')
            ->whereOr('plat_on_way_stock > 0')
            ->whereOr('wait_instock_num > 0')
            ->group('sku')
            ->select();
        foreach ($list as $val) {
            $res_item = $_item->where(['sku' => $val['sku']])->update([
                'on_way_stock'     => $val['all_on_way'],
                'wait_instock_num' => $val['all_instock'],
            ]);
            if ($res_item) {
                echo $val['sku'] . ":success\n";
            } else {
                echo $val['sku'] . ":false\n";
            }
        }
        exit;

        //update fa_item set on_way_stock=0,wait_instock_num=0 where id > 0;
        /*$res_item = $_item->allowField(true)->isUpdate(true, ['id'=>['gt',0]])->save(['on_way_stock'=>0,'wait_instock_num'=>0]);
        if(!$res_item){
            echo '全部清零失败';exit;
        }*/
        //update fa_item_platform_sku set plat_on_way_stock=0,wait_instock_num=0 where id > 0;
        /*$res_item_platform = $_item_platform->allowField(true)->isUpdate(true, ['id'=>['gt',0]])->save(['plat_on_way_stock'=>0,'wait_instock_num'=>0]);
        if(!$res_item_platform){
            echo '站点清零失败';exit;
        }*/

        //审核通过、录入物流单、签收状态下的加在途
        $_purchase_order_item = new \app\admin\model\purchase\PurchaseOrderItem;
        $_new_product_mapping = new \app\admin\model\NewProductMapping;
        $list = $_purchase_order_item
            ->alias('a')
            ->join(['fa_purchase_order' => 'b'], 'a.purchase_id=b.id')
            ->field('a.sku,a.replenish_list_id,a.purchase_num,b.replenish_id')
            ->where(['b.purchase_status' => ['in', [2, 6, 7, 9]]])
            ->where(['b.stock_status' => ['in', [0, 1]]])
            ->where(['b.replenish_id' => ['gt', 0]])
            ->select();

        foreach ($list as $v) {
            //在途库存数量
            $stock_num = $v['purchase_num'];

            //更新全部在途
            $_item->where(['sku' => $v['sku']])->setInc('on_way_stock', $stock_num);

            //获取各站点比例
            $rate_arr = $_new_product_mapping
                ->where(['sku' => $v['sku'], 'replenish_id' => $v['replenish_id']])
                ->field('website_type,rate')
                ->select();

            //在途库存分站 更新映射关系表
            foreach ($rate_arr as $key => $val) {
                if (1 == (count($rate_arr) - $key)) { //剩余数量分给最后一个站
                    $_item_platform->where([
                        'sku'           => $v['sku'],
                        'platform_type' => $val['website_type'],
                    ])->setInc('plat_on_way_stock', $stock_num);
                } else {
                    $num = round($v['purchase_num'] * $val['rate']);
                    $stock_num -= $num;
                    $_item_platform->where([
                        'sku'           => $v['sku'],
                        'platform_type' => $val['website_type'],
                    ])->setInc('plat_on_way_stock', $num);
                }
            }
        }

        //签收状态下的加待入库数量、减在途
        $_logistics_info = new \app\admin\model\warehouse\LogisticsInfo;
        //        $_batch_item = new \app\admin\model\purchase\PurchaseBatchItem;
        $row = $_logistics_info
            ->alias('a')
            ->join(['fa_purchase_order' => 'b'], 'a.purchase_id=b.id')
            ->field('a.batch_id,a.purchase_id,b.replenish_id')
            ->where(['b.stock_status' => ['in', [0, 1]]])
            ->where(['b.purchase_status' => ['in', [7, 9]]])
            ->select();

        foreach ($row as $v) {
            //            if ($v['batch_id']) {
            //                $list = $_batch_item
            //                    ->where(['purchase_batch_id' => $v['batch_id']])
            //                    ->field('website_type,rate')
            //                    ->select();
            //                foreach ($list as $val) {
            //                    //获取各站点比例
            //                    $rate_arr = $_new_product_mapping
            //                        ->where(['sku'=>$val['sku'],'replenish_id'=>$v['replenish_id']])
            //                        ->field('arrival_num,sku')
            //                        ->select();
            //
            //                    //在途库存数量
            //                    $stock_num = $val['arrival_num'];
            //
            //                    //在途库存分站 更新映射关系表
            //                    foreach ($rate_arr as $key => $vall) {
            //                        if ((1 == count($rate_arr) - $key)) {//剩余数量分给最后一个站
            //                            $_item_platform->where(['sku'=>$val['sku'],'platform_type'=>$vall['website_type']])->setDec('plat_on_way_stock',$stock_num);
            //                            //更新站点待入库数量
            //                            $_item_platform->where(['sku'=>$val['sku'],'platform_type'=>$vall['website_type']])->setInc('wait_instock_num',$stock_num);
            //                        } else {
            //                            $num = round($val['arrival_num'] * $vall['rate']);
            //                            $stock_num -= $num;
            //                            $_item_platform->where(['sku' => $val['sku'], 'platform_type' => $vall['website_type']])->setDec('plat_on_way_stock', $num);
            //                            //更新站点待入库数量
            //                            $_item_platform->where(['sku'=>$val['sku'],'platform_type'=>$vall['website_type']])->setInc('wait_instock_num',$num);
            //                        }
            //                    }
            //                    //减全部的在途库存
            //                    $_item->where(['sku' => $val['sku']])->setDec('on_way_stock', $val['arrival_num']);
            //                    //加全部的待入库数量
            //                    $_item->where(['sku' => $val['sku']])->setInc('wait_instock_num', $val['arrival_num']);
            //                }
            //            } else {
            if ($v['purchase_id']) {
                $list = $_purchase_order_item
                    ->where(['purchase_id' => $v['purchase_id']])
                    ->field('purchase_num,sku')
                    ->select();
                foreach ($list as $val) {
                    //获取各站点比例
                    $rate_arr = $_new_product_mapping
                        ->where(['sku' => $val['sku'], 'replenish_id' => $v['replenish_id']])
                        ->field('website_type,rate')
                        ->select();

                    //在途库存数量
                    $stock_num = $val['purchase_num'];

                    //在途库存分站 更新映射关系表
                    foreach ($rate_arr as $key => $vall) {
                        if ((count($rate_arr) - $key) == 1) { //剩余数量分给最后一个站
                            $_item_platform->where([
                                'sku'           => $val['sku'],
                                'platform_type' => $vall['website_type'],
                            ])->setDec('plat_on_way_stock', $stock_num);
                            //更新站点待入库数量
                            $_item_platform->where([
                                'sku'           => $val['sku'],
                                'platform_type' => $vall['website_type'],
                            ])->setInc('wait_instock_num', $stock_num);
                        } else {
                            $num = round($val['purchase_num'] * $vall['rate']);
                            $stock_num -= $num;
                            $_item_platform->where([
                                'sku'           => $val['sku'],
                                'platform_type' => $vall['website_type'],
                            ])->setDec('plat_on_way_stock', $num);
                            //更新站点待入库数量
                            $_item_platform->where([
                                'sku'           => $val['sku'],
                                'platform_type' => $vall['website_type'],
                            ])->setInc('wait_instock_num', $num);
                        }
                    }
                    //减全部的在途库存
                    $_item->where(['sku' => $val['sku']])->setDec('on_way_stock', $val['purchase_num']);
                    //加全部的待入库数量
                    $_item->where(['sku' => $val['sku']])->setInc('wait_instock_num', $val['purchase_num']);
                }
            }
            //            }
        }
    }

    //跑sku每天的数据 ga的数据
    public function sku_day_data_ga()
    {
        set_time_limit(0);
        $this->getSkuDayData(1);
        $this->getSkuDayData(2);
        $this->getSkuDayData(3);
        $this->getSkuDayData(5);
        $this->getSkuDayData(10);
        $this->getSkuDayData(11);
        $this->getSkuDayData(15);

        echo "ok";
    }

    /**
     * 获取sku每日数据中的基础数据
     *
     * @param $site   站点
     *
     * @author mjj
     * @date   2021/5/17 09:26:25
     */
    public function getSkuDayData($site)
    {
        //统计昨天的数据
        $data = date('Y-m-d', strtotime('-1 day'));
        //$data = '2020-11-01';
        $_item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $sku_data = $_item_platform_sku
            ->field('sku,grade,platform_sku,stock,plat_on_way_stock')
            ->where(['platform_type' => $site, 'outer_sku_status' => 1])
            ->select();

        //当前站点的所有sku映射关系
        $sku_data = collection($sku_data)->toArray();
        foreach ($sku_data as $k => $v) {
            $sku_data[$k]['unique_pageviews'] = 0;
            $sku_data[$k]['goods_grade'] = $v['grade'];
            $sku_data[$k]['day_date'] = $data;
            $sku_data[$k]['site'] = $site;
            $sku_data[$k]['day_stock'] = $v['stock'];
            $sku_data[$k]['day_onway_stock'] = $v['plat_on_way_stock'];
            unset($sku_data[$k]['stock']);
            unset($sku_data[$k]['grade']);
            unset($sku_data[$k]['plat_on_way_stock']);
            Db::name('datacenter_sku_day')->insert($sku_data[$k]);
        }
    }

    //跑sku每天唯一身份浏览量
    public function sku_day_unique_pageviews()
    {
        $zeeloolOperate = new \app\admin\model\operatedatacenter\Zeelool;
        set_time_limit(0);
        //统计昨天的数据
        $data = date('Y-m-d', strtotime('-1 day'));
        $sku_data = Db::name('datacenter_sku_day')->where('day_date', $data)->field('id,platform_sku,site')->select();
        foreach ($sku_data as $key => $value) {
            $ga_skus = $zeeloolOperate->google_sku_detail($value['site'], $data);
            $ga_skus = array_column($ga_skus, 'uniquePageviews', 'ga:pagePath');
            if ($value['site'] == 2) {
                $model = Db::connect('database.db_voogueme_online');
            } elseif ($value['site'] == 5) {
                $model = Db::connect('database.db_weseeoptical');
            } elseif ($value['site'] == 10) {
                $model = Db::connect('database.db_zeelool_de_online');
            } elseif ($value['site'] == 11) {
                $model = Db::connect('database.db_zeelool_jp_online');
            } else {
                $model = Db::connect('database.db_zeelool_fr');
            }
            $unique_pageviews = 0;

            if ($value['site'] == 1 || $value['site'] == 3) {
                foreach ($ga_skus as $kk => $vv) {
                    if (strpos($kk, $value['sku']) != false) {
                        $unique_pageviews += $vv;
                    }
                }
            } else {
                $sku_id = $model->table('catalog_product_entity')->where('sku',
                    $value['platform_sku'])->value('entity_id');
                foreach ($ga_skus as $kk => $vv) {
                    if ($kk == '/goods-detail/' . $sku_id) {
                        $unique_pageviews += $vv;
                    }
                }
            }
            Db::name('datacenter_sku_day')->where('id',
                $value['id'])->update(['unique_pageviews' => $unique_pageviews]);
            echo $value['id'] . '--' . $unique_pageviews . ' is ok' . "\n";
            usleep(10000);
        }
    }

    public function sku_day_data_order()
    {
        set_time_limit(0);
        $this->getSkuDayDataOrder(1);
        $this->getSkuDayDataOrder(2);
        $this->getSkuDayDataOrder(3);
        $this->getSkuDayDataOrder(5);
        $this->getSkuDayDataOrder(10);
        $this->getSkuDayDataOrder(11);
        $this->getSkuDayDataOrder(15);
    }

    /**
     * 获取sku每日数据中的订单数据
     *
     * @param $site  站点
     *
     * @author mjj
     * @date   2021/5/17 10:03:58
     */
    public function getSkuDayDataOrder($site)
    {
        $this->order = new \app\admin\model\order\order\NewOrder();
        $this->orderitemoption = new \app\admin\model\order\order\NewOrderItemOption();
        $data = date('Y-m-d', strtotime('-1 day'));
        $start = strtotime($data);
        $end = strtotime(date('Y-m-d 23:59:59', strtotime('-1 day')));
        $orderWhere['o.payment_time'] = ['between', [$start, $end]];
        $orderWhere['o.site'] = $site;
        $orderWhere['o.order_type'] = 1;
        $orderWhere['o.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered']];
        //统计昨天的数据
        $list = Db::name('datacenter_sku_day')
            ->where(['day_date' => $data, 'site' => $site])
            ->field('id,platform_sku')
            ->select();
        $list = collection($list)->toArray();
        $skuDatas = $this->order
            ->alias('o')
            ->join(['fa_order_item_option' => 'i'], 'o.entity_id=i.magento_order_id and o.site=i.site')
            ->where($orderWhere)
            ->whereIn('sku', array_column($list, 'platform_sku'))
            ->field('sku,magento_order_id,qty,base_row_total,mw_rewardpoint_discount,total_qty_ordered,i.base_discount_amount,i.lens_price,i.coating_price')
            ->select();
        $skuArr = [];
        foreach ($skuDatas as $key => $value) {
            $skuArr[$value['sku']]['order_num'][] = $value['magento_order_id'];
            $skuArr[$value['sku']]['glass_num'] += $value['qty'];
            if ($value['lens_price'] > 0 || $value['coating_price'] > 0) {
                $skuArr[$value['sku']]['pay_lens_num'] += $value['qty'];
            }
            $skuArr[$value['sku']]['sku_grand_total'] += $value['base_row_total']-$value['mw_rewardpoint_discount']/$value['total_qty_ordered']-$value['base_discount_amount'];
            $skuArr[$value['sku']]['sku_row_total'] += $value['base_row_total'];
        }
        foreach ($list as $k => $v) {
            if (!empty($skuArr[$v['platform_sku']]['order_num'])) {
                $orderNum = array_unique($skuArr[$v['platform_sku']]['order_num']);
                //某个sku当天的订单数
                $list[$k]['order_num'] = count($orderNum);
            } else {
                $list[$k]['order_num'] = 0;
            }
            //sku销售总副数
            $list[$k]['glass_num'] = $skuArr[$v['platform_sku']]['glass_num'] ? $skuArr[$v['platform_sku']]['glass_num'] : 0;
            //求出眼镜的销售额
            $frame_money_price = $skuArr[$v['platform_sku']]['sku_grand_total'] ? $skuArr[$v['platform_sku']]['sku_grand_total'] : 0;
            //sku付费镜片的数量
            $list[$k]['pay_lens_num'] = $skuArr[$v['platform_sku']]['pay_lens_num'] ? $skuArr[$v['platform_sku']]['pay_lens_num'] : 0;
            //眼镜的实际支付金额
            $frame_money = $skuArr[$v['platform_sku']]['sku_row_total'];
            //眼镜的实际销售额
            $frame_money = $frame_money ? round($frame_money, 2) : 0;
            $list[$k]['sku_grand_total'] = $frame_money_price;
            $list[$k]['sku_row_total'] = $frame_money;
            Db::name('datacenter_sku_day')->update($list[$k]);
            echo $list[$k]['id'] . "\n";
            echo '<br>';
        }
    }

    public function sku_day_data_other()
    {
        set_time_limit(0);
        $this->getSkuDayDataOther(1);  //z站
        $this->getSkuDayDataOther(2);  //v站
        $this->getSkuDayDataOther(3);  //nihao站
        $this->getSkuDayDataOther(10); //de站
        $this->getSkuDayDataOther(11); //jp站
        $this->getSkuDayDataOther(15); //fr站

        //wesee站
        //统计昨天的数据
        $data = date('Y-m-d', strtotime('-1 day'));
        $skuList = Db::name('datacenter_sku_day')
            ->where(['day_date' => $data, 'site' => 5])
            ->field('id,platform_sku')
            ->select();
        $nowPrice = Db::connect('database.db_weseeoptical')
            ->table('goods') //为了获取现价找的表
            ->whereIn('sku', array_column($skuList, 'platform_sku'))
            ->column('IF(special_price,special_price,price) price', 'sku');
        foreach ($skuList as $k => $v) {
            $now_pricce = $nowPrice[$v['platform_sku']] ?? 0;
            Db::name('datacenter_sku_day')
                ->where('id', $v['id'])
                ->update(['now_pricce' => $now_pricce]);
            echo $v['id'] . "\n";
            echo '<br>';
        }
    }

    /**
     * 获取sku每日数据中的其他数据
     *
     * @param $site
     *
     * @author mjj
     * @date   2021/5/17 10:05:18
     */
    public function getSkuDayDataOther($site)
    {
        switch ($site) {
            case 1:
                $model = Db::connect('database.db_zeelool_online');
                break;
            case 2:
                $model = Db::connect('database.db_voogueme_online');
                break;
            case 3:
                $model = Db::connect('database.db_nihao_online');
                break;
            case 10:
                $model = Db::connect('database.db_zeelool_de_online');
                break;
            case 11:
                $model = Db::connect('database.db_zeelool_jp_online');
                break;
            case 15:
                $model = Db::connect('database.db_zeelool_fr');
                break;
        }
        //购物车数量
        $model->table('sales_flat_quote')->query("set time_zone='+8:00'");
        //统计昨天的数据
        $data = date('Y-m-d', strtotime('-1 day'));
        $start = $data;
        $end = date('Y-m-d 23:59:59', strtotime('-1 day'));
        $createTimeWhere['a.created_at'] = $updateTimeWhere['a.updated_at'] = ['between', [$start, $end]];
        $skuList = Db::name('datacenter_sku_day')
            ->where(['day_date' => $data, 'site' => $site])
            ->select();
        foreach ($skuList as $k => $v) {
            $cartWhere['b.sku'] = ['like', $v['platform_sku'] . '%'];
            $cartWhere['a.base_grand_total'] = ['>', 0];
            $skuList[$k]['cart_num'] = $model->table('sales_flat_quote')
                ->alias('a')
                ->join(['sales_flat_quote_item' => 'b'], 'a.entity_id=b.quote_id')
                ->where($createTimeWhere)
                ->where($cartWhere)
                ->count();
            $skuList[$k]['update_cart_num'] = $model->table('sales_flat_quote')
                ->alias('a')
                ->join(['sales_flat_quote_item' => 'b'], 'a.entity_id=b.quote_id')
                ->where($updateTimeWhere)
                ->where($cartWhere)
                ->count();
            $now_pricce = $model->table('catalog_product_index_price') //为了获取现价找的表
            ->alias('a')
                ->join(['catalog_product_entity' => 'b'], 'a.entity_id=b.entity_id') //商品主表
                ->where('b.sku', 'like', $v['platform_sku'] . '%')
                ->value('a.final_price');
            $skuList[$k]['now_pricce'] = $now_pricce ? $now_pricce : 0;
            Db::name('datacenter_sku_day')->update($skuList[$k]);
            echo $skuList[$k]['sku'] . "\n";
            echo '<br>';
        }
    }

    //ga的数据单独摘出来跑 防止ga接口数据报错 2020.11.2防止了ga的数据报错
    public function only_ga_data_01_09()
    {
        $date_time = date('Y-m-d', strtotime("-1 day"));
        $date_time = '2021-01-09';

        //z站
        $data = Db::name('datacenter_day')->where([
            'day_date' => $date_time,
            'site'     => 1,
        ])->field('order_num,new_cart_num,update_cart_num')->find();

        //活跃用户数
        $arr['active_user_num'] = $this->google_active_user(1, $date_time);
        //会话
        $arr['sessions'] = $this->google_session(1, $date_time);
        //会话转化率
        $arr['session_rate'] = $arr['sessions'] != 0 ? round($data['order_num'] / $arr['sessions'] * 100, 2) : 0;
        //新增加购率
        $arr['add_cart_rate'] = $arr['sessions'] ? round($data['new_cart_num'] / $arr['sessions'] * 100, 2) : 0;
        //更新加购率
        $arr['update_add_cart_rate'] = $arr['sessions'] ? round($data['update_cart_num'] / $arr['sessions'] * 100,
            2) : 0;
        $zeelool_data = new \app\admin\model\operatedatacenter\Zeelool();
        //着陆页数据
        $arr['landing_num'] = $zeelool_data->google_landing(1, $date_time);
        //产品详情页
        $arr['detail_num'] = $zeelool_data->google_target13(1, $date_time);
        //加购
        $arr['cart_num'] = $zeelool_data->google_target1(1, $date_time);
        //交易次数
        $arr['complete_num'] = $zeelool_data->google_target_end(1, $date_time);
        $update = Db::name('datacenter_day')->where(['day_date' => $date_time, 'site' => 1])->update($arr);
        usleep(100000);

        //v站
        $data = Db::name('datacenter_day')->where([
            'day_date' => $date_time,
            'site'     => 2,
        ])->field('order_num,new_cart_num,update_cart_num')->find();
        //活跃用户数
        $arr['active_user_num'] = $this->google_active_user(2, $date_time);
        //会话
        $arr['sessions'] = $this->google_session(2, $date_time);
        //会话转化率
        $arr['session_rate'] = $arr['sessions'] != 0 ? round($data['order_num'] / $arr['sessions'] * 100, 2) : 0;
        //新增加购率
        $arr['add_cart_rate'] = $arr['sessions'] ? round($data['new_cart_num'] / $arr['sessions'] * 100, 2) : 0;
        //更新加购率
        $arr['update_add_cart_rate'] = $arr['sessions'] ? round($data['update_cart_num'] / $arr['sessions'] * 100,
            2) : 0;
        $zeelool_data = new \app\admin\model\operatedatacenter\Zeelool();
        //着陆页数据
        $arr['landing_num'] = $zeelool_data->google_landing(2, $date_time);
        //产品详情页
        $arr['detail_num'] = $zeelool_data->google_target20(2, $date_time);
        //加购
        $arr['cart_num'] = $zeelool_data->google_target2(2, $date_time);
        //交易次数
        $arr['complete_num'] = $zeelool_data->google_target_end(2, $date_time);
        $update = Db::name('datacenter_day')->where(['day_date' => $date_time, 'site' => 2])->update($arr);
        usleep(100000);

        //nihao站
        $data = Db::name('datacenter_day')->where([
            'day_date' => $date_time,
            'site'     => 3,
        ])->field('order_num,new_cart_num,update_cart_num')->find();
        //活跃用户数
        $arr['active_user_num'] = $this->google_active_user(3, $date_time);
        //会话
        $arr['sessions'] = $this->google_session(3, $date_time);
        //会话转化率
        $arr['session_rate'] = $arr['sessions'] != 0 ? round($data['order_num'] / $arr['sessions'] * 100, 2) : 0;
        //新增加购率
        $arr['add_cart_rate'] = $arr['sessions'] ? round($data['new_cart_num'] / $arr['sessions'] * 100, 2) : 0;
        //更新加购率
        $arr['update_add_cart_rate'] = $arr['sessions'] ? round($data['update_cart_num'] / $arr['sessions'] * 100,
            2) : 0;
        $zeelool_data = new \app\admin\model\operatedatacenter\Zeelool();
        //着陆页数据
        $arr['landing_num'] = $zeelool_data->google_landing(3, $date_time);
        //产品详情页
        $arr['detail_num'] = $zeelool_data->google_target13(3, $date_time);
        //加购
        $arr['cart_num'] = $zeelool_data->google_target1(3, $date_time);
        //交易次数
        $arr['complete_num'] = $zeelool_data->google_target_end(3, $date_time);
        $update = Db::name('datacenter_day')->where(['day_date' => $date_time, 'site' => 3])->update($arr);
        usleep(100000);
    }

    public function update_ga_sessions()
    {
        $arr = Db::name('datacenter_day')->where('day_date', '>=', '2021-03-01')->where('site', 'in',
            [1, 2, 3])->field('id,day_date,site,sessions')->select();
        foreach ($arr as $k => $v) {
            $arr[$k]['new_sessions'] = $this->google_session($v['site'], $v['day_date']);
            $res = Db::name('datacenter_day')->where('id', $v['id'])->update(['sessions' => $arr[$k]['new_sessions']]);
            dump($res);
        }
    }

    public function only_ga_data_01_08()
    {
        $date_time = date('Y-m-d', strtotime("-1 day"));
        $date_time = '2021-01-08';

        //z站
        $data = Db::name('datacenter_day')->where([
            'day_date' => $date_time,
            'site'     => 1,
        ])->field('order_num,new_cart_num,update_cart_num')->find();

        //活跃用户数
        $arr['active_user_num'] = $this->google_active_user(1, $date_time);
        //会话
        $arr['sessions'] = $this->google_session(1, $date_time);
        //会话转化率
        $arr['session_rate'] = $arr['sessions'] != 0 ? round($data['order_num'] / $arr['sessions'] * 100, 2) : 0;
        //新增加购率
        $arr['add_cart_rate'] = $arr['sessions'] ? round($data['new_cart_num'] / $arr['sessions'] * 100, 2) : 0;
        //更新加购率
        $arr['update_add_cart_rate'] = $arr['sessions'] ? round($data['update_cart_num'] / $arr['sessions'] * 100,
            2) : 0;
        $zeelool_data = new \app\admin\model\operatedatacenter\Zeelool();
        //着陆页数据
        $arr['landing_num'] = $zeelool_data->google_landing(1, $date_time);
        //产品详情页
        $arr['detail_num'] = $zeelool_data->google_target13(1, $date_time);
        //加购
        $arr['cart_num'] = $zeelool_data->google_target1(1, $date_time);
        //交易次数
        $arr['complete_num'] = $zeelool_data->google_target_end(1, $date_time);
        $update = Db::name('datacenter_day')->where(['day_date' => $date_time, 'site' => 1])->update($arr);
        usleep(100000);

        //v站
        $data = Db::name('datacenter_day')->where([
            'day_date' => $date_time,
            'site'     => 2,
        ])->field('order_num,new_cart_num,update_cart_num')->find();
        //活跃用户数
        $arr['active_user_num'] = $this->google_active_user(2, $date_time);
        //会话
        $arr['sessions'] = $this->google_session(2, $date_time);
        //会话转化率
        $arr['session_rate'] = $arr['sessions'] != 0 ? round($data['order_num'] / $arr['sessions'] * 100, 2) : 0;
        //新增加购率
        $arr['add_cart_rate'] = $arr['sessions'] ? round($data['new_cart_num'] / $arr['sessions'] * 100, 2) : 0;
        //更新加购率
        $arr['update_add_cart_rate'] = $arr['sessions'] ? round($data['update_cart_num'] / $arr['sessions'] * 100,
            2) : 0;
        $zeelool_data = new \app\admin\model\operatedatacenter\Zeelool();
        //着陆页数据
        $arr['landing_num'] = $zeelool_data->google_landing(2, $date_time);
        //产品详情页
        $arr['detail_num'] = $zeelool_data->google_target20(2, $date_time);
        //加购
        $arr['cart_num'] = $zeelool_data->google_target2(2, $date_time);
        //交易次数
        $arr['complete_num'] = $zeelool_data->google_target_end(2, $date_time);
        $update = Db::name('datacenter_day')->where(['day_date' => $date_time, 'site' => 2])->update($arr);
        usleep(100000);

        //nihao站
        $data = Db::name('datacenter_day')->where([
            'day_date' => $date_time,
            'site'     => 3,
        ])->field('order_num,new_cart_num,update_cart_num')->find();
        //活跃用户数
        $arr['active_user_num'] = $this->google_active_user(3, $date_time);
        //会话
        $arr['sessions'] = $this->google_session(3, $date_time);
        //会话转化率
        $arr['session_rate'] = $arr['sessions'] != 0 ? round($data['order_num'] / $arr['sessions'] * 100, 2) : 0;
        //新增加购率
        $arr['add_cart_rate'] = $arr['sessions'] ? round($data['new_cart_num'] / $arr['sessions'] * 100, 2) : 0;
        //更新加购率
        $arr['update_add_cart_rate'] = $arr['sessions'] ? round($data['update_cart_num'] / $arr['sessions'] * 100,
            2) : 0;
        $zeelool_data = new \app\admin\model\operatedatacenter\Zeelool();
        //着陆页数据
        $arr['landing_num'] = $zeelool_data->google_landing(3, $date_time);
        //产品详情页
        $arr['detail_num'] = $zeelool_data->google_target13(3, $date_time);
        //加购
        $arr['cart_num'] = $zeelool_data->google_target1(3, $date_time);
        //交易次数
        $arr['complete_num'] = $zeelool_data->google_target_end(3, $date_time);
        $update = Db::name('datacenter_day')->where(['day_date' => $date_time, 'site' => 3])->update($arr);
        usleep(100000);
    }

    /*
     * 库存台账数据
     * */
    public function stock_parameter()
    {
        $this->instock = new \app\admin\model\warehouse\Instock;
        $this->outstock = new \app\admin\model\warehouse\Outstock;
        $this->stockparameter = new \app\admin\model\financepurchase\StockParameter;
        $this->item = new \app\admin\model\warehouse\ProductBarCodeItem;
        $this->model = new \app\admin\model\itemmanage\Item;
        $start = date('Y-m-d', strtotime("-1 day"));
        $end = $start . ' 23:59:59';
        //库存主表插入数据
        $stock_data['day_date'] = $start;
        $stockId = $this->stockparameter->insertGetId($stock_data);
        //采购入库数量
        $instock_where['s.status'] = 2;
        $instock_where['s.type_id'] = 1;
        $instock_where['s.check_time'] = ['between', [$start, $end]];
        $instocks = $this->instock->alias('s')->join('fa_check_order c',
            'c.id=s.check_id')->join('fa_purchase_order_item oi',
            'c.purchase_id=oi.purchase_id')->join('fa_purchase_order o',
            'oi.purchase_id=o.id')->where($instock_where)->field('s.id,round(o.purchase_total/oi.purchase_num,2) purchase_price')->select();
        $instock_total = 0; //入库总金额
        foreach ($instocks as $key => $instock) {
            $arr = [];
            $arr['stock_id'] = $stockId;
            $arr['instock_id'] = $instock['id'];
            $arr['type'] = 1;
            $instock_num = Db::name('in_stock_item')->where('in_stock_id', $instock['id'])->sum('in_stock_num');
            $arr['instock_num'] = $instock_num;
            $arr['instock_total'] = round($instock['purchase_price'] * $instock_num, 2);
            $instock_total += $arr['instock_total'];
            Db::name('finance_stock_parameter_item')->insert($arr);
        }
        //判断今天是否有冲减数据
        $start_time = strtotime($start);
        $end_time = strtotime($end);
        $exist_where['create_time'] = ['between', [$start_time, $end_time]];
        $is_exist = Db::name('finance_cost_error')->where($exist_where)->field('id,create_time,purchase_id,total')->select();

        $outstock_total1 = 0;   //出库单出库
        $outstock_total2 = 0;   //订单出库
        /*************出库单出库start**************/
        $bar_where['out_stock_time'] = ['between', [$start, $end]];
        $bar_where['out_stock_id'] = ['<>', 0];
        $bar_where['library_status'] = 2;
        //判断冲减前的出库单出库数量和金额
        $bars = $this->item->where($bar_where)->group('barcode_id')->column('barcode_id');
        foreach ($bars as $bar) {
            $flag = [];
            $flag['stock_id'] = $stockId;
            $flag['bar_id'] = $bar;
            $flag['type'] = 2;
            $bar_items = $this->item->alias('i')->join('fa_purchase_order_item p',
                'i.purchase_id=p.purchase_id and i.sku=p.sku')->join('fa_purchase_order o',
                'p.purchase_id=o.id')->field('i.out_stock_id,i.purchase_id,i.out_stock_time,p.actual_purchase_price,round(o.purchase_total/p.purchase_num,2) purchase_price')->where($bar_where)->where('barcode_id',
                $bar)->select();
            $sum_count = 0;
            $sum_total = 0;
            foreach ($bar_items as $item) {
                if (count(array_unique($is_exist)) != 0) {
                    foreach ($is_exist as $value) {
                        if ($item['purchase_id'] == $value['purchase_id']) {
                            $end_date = date('Y-m-d H:i:s', $value['create_time']);
                            if ($item['out_stock_time'] >= $end_date) {
                                //使用成本计算
                                $total = $item['actual_purchase_price'];
                            } else {
                                //使用预估计算
                                $total = $item['purchase_price'];
                            }
                        } else {
                            //没有冲减数据，直接拿预估成本计算
                            if ($item['actual_purchase_price'] != 0) {
                                $total = $item['actual_purchase_price'];   //有成本价拿成本价计算
                            } else {
                                $total = $item['purchase_price'];   //没有成本价拿预估价计算
                            }
                        }
                    }
                } else {
                    //没有冲减数据，直接拿预估成本计算
                    if ($item['actual_purchase_price'] != 0) {
                        $total = $item['actual_purchase_price'];   //有成本价拿成本价计算
                    } else {
                        $total = $item['purchase_price'];   //没有成本价拿预估价计算
                    }
                }
                $sum_total += $total;
                $sum_count++;
            }
            $flag['outstock_count'] = $sum_count;
            $flag['outstock_total'] = $sum_total;
            $outstock_total1 += $sum_total;
            Db::name('finance_stock_parameter_item')->insert($flag);
        }
        /*************出库单出库end**************/
        /*************订单出库start**************/
        $bar_where1['out_stock_time'] = ['between', [$start, $end]];
        $bar_where1['out_stock_id'] = 0;
        $bar_where1['item_order_number'] = ['<>', ''];
        $bar_where1['i.library_status'] = 2;
        //判断冲减前的出库单出库数量和金额
        $bars1 = $this->item->alias('i')->join('fa_purchase_order_item p',
            'i.purchase_id=p.purchase_id and i.sku=p.sku')->join('fa_purchase_order o',
            'p.purchase_id=o.id')->where($bar_where1)->field('i.out_stock_id,i.purchase_id,i.out_stock_time,p.actual_purchase_price,round(o.purchase_total/p.purchase_num,2) purchase_price')->select();
        if (count($bars1) != 0) {
            $flag1 = [];
            $flag1['stock_id'] = $stockId;
            $flag1['type'] = 3;
            foreach ($bars1 as $bar1) {
                if (count(array_unique($is_exist)) != 0) {
                    foreach ($is_exist as $value) {
                        if ($bar1['purchase_id'] == $value['purchase_id']) {
                            $end_date = date('Y-m-d H:i:s', $value['create_time']);
                            if ($bar1['out_stock_time'] >= $end_date) {
                                //使用成本计算
                                $total1 = $bar1['actual_purchase_price'];
                            } else {
                                //使用预估计算
                                $total1 = $bar1['purchase_price'];
                            }
                        } else {
                            //没有冲减数据，直接拿预估成本计算
                            if ($bar1['actual_purchase_price'] != 0) {
                                $total1 = $bar1['actual_purchase_price'];   //有成本价拿成本价计算
                            } else {
                                $total1 = $bar1['purchase_price'];   //没有成本价拿预估价计算
                            }
                        }
                    }
                } else {
                    //没有冲减数据，直接拿预估成本计算
                    if ($bar1['actual_purchase_price'] != 0) {
                        $total1 = $bar1['actual_purchase_price'];   //有成本价拿成本价计算
                    } else {
                        $total1 = $bar1['purchase_price'];   //没有成本价拿预估价计算
                    }
                }
                $outstock_total2 += $total1;
            }
            $flag1['outstock_count'] = count($bars1);
            $flag1['outstock_total'] = $outstock_total2;
            Db::name('finance_stock_parameter_item')->insert($flag1);
        }
        /*************订单出库end**************/
        //查询最新一条的余额
        $rest_total = $this->stockparameter->order('id', 'desc')->field('rest_total')->limit(1, 1)->select();
        $cha_amount = 0;  //冲减金额
        foreach ($is_exist as $k => $v) {
            $cha_amount += $v['total'];
        }
        $end_rest = round($cha_amount + $rest_total[0]['rest_total'] + $instock_total - $outstock_total1 - $outstock_total2,
            2);
        $info['instock_total'] = $instock_total;
        $info['outstock_total'] = round($outstock_total1 + $outstock_total2, 2);
        $info['rest_total'] = $end_rest;
        $this->stockparameter->where('id', $stockId)->update($info);
        echo "all is ok";
    }

    /**
     * 周期结转单脚本
     *  每月1号早9点自动生成结转单；一条订单成本记录只能存在一个结转单内
     * @Description
     * @author wpl
     * @since 2021/01/21 16:05:48 
     * @return void
     */
    public function cycle_order()
    {
        //查询上个月成本核算
        $month = date('Y-m-01');
        $fisrttime = strtotime("$month -1 month");
        $endtime = strtotime("$month") - 1;
        $financecost = new \app\admin\model\finance\FinanceCost();
        $count = $financecost->where([
            'createtime'       => ['between', [$fisrttime, $endtime]],
            'is_carry_forward' => 0,
            'bill_type'        => ['<>', 9],
        ])->count();
        if ($count < 1) {
            echo "无结果";
            die;
        }
        //生成周期结转单
        $financecycle = new \app\admin\model\finance\FinanceCycle();
        $res = $financecycle->allowField(true)->save([
            'cycle_number' => 'JZ' . date('YmdHis') . rand(100, 999) . rand(100, 999),
            'createtime'   => time(),
        ]);

        if (false !== $res) {

            $financecost->where([
                'createtime'       => ['between', [$fisrttime, $endtime]],
                'is_carry_forward' => 0,
            ])->update(['is_carry_forward' => 1, 'cycle_id' => $financecycle->id]);
        }

        echo "ok";
    }

    //计划任务定时跑物流数据 得到揽收时间存入物流信息表 如果没有揽收时间 就以录入物流单号的时间作为揽收时间为了供应商待结算列表的结算周期使用
    public function logistics_info()
    {
        //采购单物流单详情
        $rows = Db::name('logistics_info')
            ->where('createtime', '>', date("Y-m-d H:i:s", strtotime("-12 hour")))
            ->where('createtime', '<', date("Y-m-d H:i:s", time()))
            ->select();
        // dump($rows);
        // die;
        foreach ($rows as $k => $v) {
            //物流单快递100接口
            if ($v['logistics_number']) {
                $arr = explode(',', $v['logistics_number']);
                //物流公司编码
                $company = explode(',', $v['logistics_company_no']);
                foreach ($arr as $kk => $vv) {
                    try {
                        //快递单号
                        $param['express_id'] = trim($vv);
                        $param['code'] = trim($company[$kk]);
                        $data[$kk] = Hook::listen('express_query', $param)[0];
                    } catch (\Exception $e) {
                        $this->error($e->getMessage());
                    }
                }
            }
            if (!empty($data[0]['data'])) {
                //拿物流单接口返回的倒数第二条数据的时间作为揽件的时间 更新物流单的详情
                $collect_time = date("Y-m-d H:i:s", strtotime(array_slice($data[0]['data'], -1, 1)[0]['time']));
            } else {
                $collect_time = $v['createtime'];
            }
            // dump($collect_time);
            $res = Db::name('logistics_info')->where('id', $v['id'])->update(['collect_time' => $collect_time]);
            // dump($res);
        }
        if ($res) {
            echo "ok" . "\n";;
        } else {
            echo 'fail' . "\n";;
        }
        // die;
    }


    /**
     * 处理待入库数量 - 计划任务
     */
    public function process_wait_stock()
    {
        $item = new \app\admin\model\itemmanage\Item();
        $list = $item
            ->where(['is_open' => 1, 'is_del' => 1])
            ->field('id,sku')
            ->select();
        foreach ($list as $k => $v) {
            $params = [];
            $num = 0;
            //查询sku对应的采购单id
            $purchaseIds = Db::name('purchase_order_item')
                ->where('sku', $v['sku'])
                ->column('purchase_id');
            if (!empty($purchaseIds)) {
                foreach ($purchaseIds as $purchaseId) {
                    //判断采购单是否签收
                    $isSign = Db::name('logistics_info')
                        ->where('type', 1)
                        ->where('status', 1)
                        ->where('purchase_id', $purchaseId)
                        ->value('id');
                    if ($isSign) {
                        //判断是否入库
                        $isInStock = Db::name('in_stock')
                            ->alias('i')
                            ->join('check_order c', 'c.id=i.check_id')
                            ->where('c.purchase_id', $purchaseId)
                            ->where('i.status', 2)
                            ->value('i.id');
                        //没有入库
                        if (is_null($isInStock) && (!$isInStock)) {
                            //查询是否有批次
                            $batchIds = Db::name('logistics_info')
                                ->where('type', 1)
                                ->where('status', 1)
                                ->where('purchase_id', $purchaseId)
                                ->group('purchase_id,batch_id')
                                ->column('batch_id');
                            $batchIds = array_filter($batchIds);
                            if (empty($batchIds)) {
                                $num += Db::name('purchase_order_item')
                                    ->where('purchase_id', $purchaseId)
                                    ->value('purchase_num');
                            } else {
                                foreach ($batchIds as $batchId) {
                                    $num += Db::name('purchase_batch_item')
                                        ->where('purchase_batch_id', $batchId)
                                        ->value('arrival_num');
                                }
                            }
                        }
                    }
                }
                $params['wait_instock_num'] = $num;
                $item->where('id', $v['id'])
                    ->update($params);
                echo $v['sku'] . " is ok" . "\n";
                usleep(10000);
            }
        }
        echo "ok";
    }

    /**
     * 处理待入库数量 - 计划任务
     */
    public function process_wait_stock1()
    {
        $item = new \app\admin\model\itemmanage\Item();
        $list = $item
            ->where(['is_open' => 1, 'is_del' => 1])
            ->field('id,sku')
            ->select();
        foreach ($list as $k => $v) {
            $params = [];
            $num = 0;
            //查询sku对应的采购单id
            $purchaseIds = Db::name('purchase_order_item')
                ->where('sku', $v['sku'])
                ->column('purchase_id');
            if (!empty($purchaseIds)) {
                foreach ($purchaseIds as $purchaseId) {
                    //判断采购单是否签收
                    $isSign = Db::name('logistics_info')
                        ->where('type', 1)
                        ->where('status', 1)
                        ->where('purchase_id', $purchaseId)
                        ->value('id');
                    if ($isSign) {
                        //判断是否入库
                        $isInStock = Db::name('in_stock')
                            ->alias('i')
                            ->join('check_order c', 'c.id=i.check_id')
                            ->where('c.purchase_id', $purchaseId)
                            ->where('i.status', 2)
                            ->value('i.id');
                        //没有入库
                        if (is_null($isInStock) && (!$isInStock)) {
                            $params['sku'] = $v['sku'];
                            $params['purchase_id'] = $purchaseId;
                            $info = Db::name('purchase_order')
                                ->alias('o')
                                ->join('fa_purchase_order_item i', 'o.id=i.purchase_id')
                                ->where('o.id', $purchaseId)
                                ->where('i.sku', $v['sku'])
                                ->field('createtime,purchase_num,o.purchase_status,o.purchase_number,o.check_status,o.stock_status')
                                ->find();
                            $params['num'] = $info['purchase_num'];
                            $params['create_time'] = $info['createtime'];
                            switch ($info['purchase_status']) {
                                case 0:
                                    $purchase_status = '新建';
                                    break;
                                case 1:
                                    $purchase_status = '审核中';
                                    break;
                                case 2:
                                    $purchase_status = '已审核';
                                    break;
                                case 3:
                                    $purchase_status = '已拒绝';
                                    break;
                                case 4:
                                    $purchase_status = '已取消';
                                    break;
                                case 5:
                                    $purchase_status = '待发货';
                                    break;
                                case 6:
                                    $purchase_status = '待收货';
                                    break;
                                case 7:
                                    $purchase_status = '已签收';
                                    break;
                                case 8:
                                    $purchase_status = '已退款';
                                    break;
                                case 9:
                                    $purchase_status = '部分签收';
                                    break;
                                case 10:
                                    $purchase_status = '已完成';
                                    break;
                            }
                            switch ($info['check_status']) {
                                case 0:
                                    $check_status = '未质检';
                                    break;
                                case 1:
                                    $check_status = '部分质检';
                                    break;
                                case 2:
                                    $check_status = '已质检';
                                    break;
                            }
                            switch ($info['stock_status']) {
                                case 0:
                                    $instock_status = '未入库';
                                    break;
                                case 1:
                                    $instock_status = '部分入库';
                                    break;
                                case 2:
                                    $instock_status = '已入库';
                                    break;
                            }
                            $params['purchase_status'] = $purchase_status;
                            $params['purchase_number'] = $info['purchase_number'];
                            $params['check_status'] = $check_status;
                            $params['instock_status'] = $instock_status;
                            Db::name('wait_linshi')->insert($params);
                            echo $v['sku'] . " is ok" . "\n";
                            usleep(10000);
                        }
                    }
                }
            }
        }
        echo "ok";
    }

    /**
     * 所有上架商品的上架时间
     * @author mjj
     * @date   2021/5/13 15:11:02
     */
    public function batch_goods_shelves_time()
    {
        $platform = new \app\admin\model\itemmanage\ItemPlatformSku();
        $skus = $platform
            ->where('platform_type', 15)
            ->where('outer_sku_status', 1)
            ->field('sku,platform_sku,platform_type')
            ->select();
        foreach ($skus as $value) {
            switch ($value['platform_type']) {
                case 1:
                    $model = Db::connect('database.db_zeelool_online');
                    break;
                case 2:
                    $model = Db::connect('database.db_voogueme_online');
                    break;
                case 3:
                    $model = Db::connect('database.db_nihao_online');
                    break;
                case 5:
                    $model = Db::connect('database.db_weseeoptical');
                    break;
                case 10:
                    $model = Db::connect('database.db_zeelool_de_online');
                    break;
                case 11:
                    $model = Db::connect('database.db_zeelool_jp_online');
                    break;
                case 15:
                    $model = Db::connect('database.db_zeelool_fr');
                    break;
            }
            if ($value['platform_type'] == 5) {
                $createdAt = $model->table('goods')
                    ->where('sku', $value['platform_sku'])
                    ->value('created_at');
            } else {
                $createdAt = $model->table('catalog_product_entity')
                    ->where('sku', $value['platform_sku'])
                    ->value('created_at');
            }
            $arr = [
                'site'         => $value['platform_type'],
                'sku'          => $value['sku'],
                'platform_sku' => $value['platform_sku'],
                'shelves_time' => strtotime($createdAt) + 8 * 3600,
                'created_at'   => time(),
            ];
            Db::name('sku_shelves_time')->insert($arr);
            echo '站点' . $value['platform_type'] . '--sku:' . $value['sku'] . "\n";
            usleep(10000);
        }

    }
}






