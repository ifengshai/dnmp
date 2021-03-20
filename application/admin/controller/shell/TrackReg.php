<?php

/**
 * 执行时间：每天一次
 */

namespace app\admin\controller\shell;

use app\admin\model\operatedatacenter\Zeelool;
use app\common\controller\Backend;
use GuzzleHttp\Client;
use think\Db;
use SchGroup\SeventeenTrack\Connectors\TrackingConnector;
use think\Hook;

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
            $order_ids = array();
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
                $order_ids = array();

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
            $shipment_data_type = Db::connect('database.db_mojing_order')->table('fa_order_process')->where(['track_number' => $v['track_number'], 'increment_id' => $v['order_number']])->value('agent_way_title');
            $params[$k]['id'] = $v['id'];
            $params[$k]['shipment_data_type'] = $shipment_data_type;
            $this->ordernodedetail->where(['order_number' => $v['order_number'], 'track_number' => $v['track_number']])->update(['shipment_data_type' => $shipment_data_type]);
        }
        if ($params) $this->ordernode->saveAll($params);
        echo "ok";
    }

    /**
     * 获取快递号
     * @param $title
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
        }

        $carrier = [
            'dhl' => '100001',
            'chinapost' => '03011',
            'chinaems' => '03013',
            'cpc' =>  '03041',
            'fedex' => '100003',
            'usps' => '21051',
            'yanwen' => '190012',
            'sua' => '190111',
            'cod' => '100040'
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
        $response = $client->request('POST', $url, array('form_params' => $params));
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
        // file_put_contents('/www/wwwroot/mojing/runtime/log/zendesk.log', 'starttime:' . date('Y-m-d H:i:s') . "\r\n", FILE_APPEND);

        $this->model = new \app\admin\model\zendesk\Zendesk;
        $ticketIds = (new \app\admin\controller\zendesk\Notice(request(), ['type' => $siteType]))->autoAsyncUpdate($siteType);

        //判断是否存在
        $nowTicketsIds = $this->model->where("type", $type)->column('ticket_id');

        //求交集的更新
        $intersects = array_intersect($ticketIds, $nowTicketsIds);
        //求差集新增
        $diffs = array_diff($ticketIds, $nowTicketsIds);
        //更新
        foreach ($intersects as $intersect) {
            (new \app\admin\controller\zendesk\Notice(request(), ['type' => $siteType, 'id' => $intersect]))->auto_update();
            echo $intersect . 'is ok' . "\n";
        }
        //新增
        foreach ($diffs as $diff) {
            (new \app\admin\controller\zendesk\Notice(request(), ['type' => $siteType, 'id' => $diff]))->auto_create();
            echo $diff . 'ok' . "\n";
        }
        echo 'all ok';
        // file_put_contents('/www/wwwroot/mojing/runtime/log/zendesk.log', 'endtime:' . date('Y-m-d H:i:s') . "\r\n", FILE_APPEND);
        exit;
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
        $list = $itemPlatformSku->field('sku,platform_sku,platform_type as site')->where(['outer_sku_status' => 1, 'platform_type' => ['<>', 8]])->select();
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
     *
     * @Description
     * @author wpl
     * @since 2020/08/01 15:29:23 
     * @return void
     */
    public function get_days_sales_num()
    {
        $itemPlatformSku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $skuSalesNum = new \app\admin\model\SkuSalesNum();
        $date = date('Y-m-d 00:00:00');
        $list = $itemPlatformSku->field('id,sku,platform_type as site')->where(['outer_sku_status' => 1, 'platform_type' => ['<>', 8]])->select();
        $list = collection($list)->toArray();

        foreach ($list as $k => $v) {
            //15天日均销量
            $days15_data = $skuSalesNum->where(['sku' => $v['sku'], 'site' => $v['site'], 'createtime' => ['<', $date]])->field("sum(sales_num) as sales_num,count(*) as num")->limit(15)->order('createtime desc')->select();
            $params['sales_num_15days'] = $days15_data[0]->num > 0 ? round($days15_data[0]->sales_num / $days15_data[0]->num) : 0;

            $days90_data = $skuSalesNum->where(['sku' => $v['sku'], 'site' => $v['site'], 'createtime' => ['<', $date]])->field("sum(sales_num) as sales_num,count(*) as num")->limit(90)->order('createtime desc')->select();
            //90天总销量
            $params['sales_num_90days'] = $days90_data[0]->sales_num;
            //90天日均销量
            $sales_num_90days = $days90_data[0]->num > 0 ? round($days90_data[0]->sales_num / $days90_data[0]->num) : 0;
            //90天日均销量
            $params['average_90days_sales_num'] = $sales_num_90days;
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
            $itemPlatformSku->where('id', $v['id'])->update($params);
        }

        echo "ok";
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
     */
    public function get_days_sales_num_all()
    {
        $item = new \app\admin\model\itemmanage\Item();
        $this->itemplatformsku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $order = new \app\admin\model\order\order\NewOrder();
        $skuSalesNum = new \app\admin\model\SkuSalesNum();
        $platform = new \app\admin\model\platformmanage\MagentoPlatform();
        //查询所有站点
        $siteList = $platform->select();

        $list = $item->where(['is_open' => 1, 'is_del' => 1, 'category_id' => ['<>', 43]])->column('sku');
        $params = [];
        $date = date('Y-m-d 00:00:00');
        foreach ($list as $k => $v) {
            $allnum = 0;
            foreach ($siteList as $val) {

                $sql = $skuSalesNum->field('sales_num')->where(['sku' => $v, 'createtime' => ['<', $date], 'site' => $val['id']])->limit(30)->order('createtime desc')->buildSql();
                $num = Db::table($sql . ' a')->sum('a.sales_num');
                //统计30天有效天数销量
                $allnum += $num;
            }
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
            $params[$k]['counter'] = $allnum;
            $params[$k]['days'] = 30;
            $params[$k]['true_sku'] = $v;
            $params[$k]['num'] = $allnum;
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
            echo ('暂时没有紧急补货单需要处理');
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
            $sku_whole_num = array_sum(array_map(function ($val) {
                return $val['replenish_num'];
            }, $v));
            //求出比例赋予新数组
            foreach ($v as $ko => $vo) {
                $date[$int]['id'] = $vo['id'];
                $date[$int]['rate'] = $vo['replenish_num'] / $sku_whole_num;
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
     * @return [type]      [新的二维数组]
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/7/22
     * Time: 11:37
     */
    function array_group_by($arr, $key)
    {
        $grouped = array();
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
            echo ('暂时没有紧急补货单需要处理');
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
            $sku_whole_num = array_sum(array_map(function ($val) {
                return $val['replenish_num'];
            }, $v));
            //求出比例赋予新数组
            foreach ($v as $ko => $vo) {
                $date[$int]['id'] = $vo['id'];
                $date[$int]['rate'] = $vo['replenish_num'] / $sku_whole_num;
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
        } elseif ($site == 10) {
            $VIEW_ID = config('ZEELOOLDE_GOOGLE_ANALYTICS_VIEW_ID');
        } elseif ($site == 11) {
            $VIEW_ID = config('ZEELOOLJP_GOOGLE_ANALYTICS_VIEW_ID');
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
        $request->setMetrics(array($adCostMetric));
        $request->setDimensions(array($sessionDayDimension));

        $body = new \Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests(array($request));
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
        } elseif ($site == 10) {
            $VIEW_ID = config('ZEELOOLDE_GOOGLE_ANALYTICS_VIEW_ID');
        } elseif ($site == 11) {
            $VIEW_ID = config('ZEELOOLJP_GOOGLE_ANALYTICS_VIEW_ID');
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
        $request->setMetrics(array($adCostMetric));
        $request->setDimensions(array($sessionDayDimension));

        $body = new \Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests(array($request));
        return $analytics->reports->batchGet($body);
    }

    /**
     * Parses and prints the Analytics Reporting API V4 response.
     *
     * @param An Analytics Reporting API V4 response.
     */
    protected function printResults($reports)
    {
        $finalResult = array();
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
     * @param unknown type $avg
     * @param Array $list
     * @param Boolen $isSwatch
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
        $zeelool_data = new \app\admin\model\operatedatacenter\Zeelool();
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
        $order_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $order_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered']];
        $arr['order_num'] = $this->zeelool->where($order_where)->where('order_type', 1)->count();
        //销售额
        $arr['sales_total_money'] = $this->zeelool->where($order_where)->where('order_type', 1)->sum('base_grand_total');
        //邮费
        $arr['shipping_total_money'] = $this->zeelool->where($order_where)->where('order_type', 1)->sum('base_shipping_amount');
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
        $arr['replacement_order_total'] = $this->zeelool->where($order_where)->where('order_type', 4)->sum('base_grand_total');
        //网红订单数
        $arr['online_celebrity_order_num'] = $this->zeelool->where($order_where)->where('order_type', 3)->count();
        //补发销售额
        $arr['online_celebrity_order_total'] = $this->zeelool->where($order_where)->where('order_type', 3)->sum('base_grand_total');
        //会话
        // $arr['sessions'] = $this->google_session(1, $date_time);
        //会话转化率
        // $arr['session_rate'] = $arr['sessions'] != 0 ? round($arr['order_num'] / $arr['sessions'] * 100, 2) : 0;
        //新建购物车数量
        $cart_where1 = [];
        $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['new_cart_num'] = $zeelool_model->table('sales_flat_quote')->where($cart_where1)->where('base_grand_total', 'gt', 0)->count();
        //更新购物车数量
        $cart_where2 = [];
        $cart_where2[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['update_cart_num'] = $zeelool_model->table('sales_flat_quote')->where($cart_where2)->where('base_grand_total', 'gt', 0)->count();
        //新增加购率
        // $arr['add_cart_rate'] = $arr['sessions'] ? round($arr['new_cart_num'] / $arr['sessions'] * 100, 2) : 0;
        //更新加购率
        // $arr['update_add_cart_rate'] = $arr['sessions'] ? round($arr['update_cart_num'] / $arr['sessions'] * 100, 2) : 0;
        //新增购物车转化率
        $arr['cart_rate'] = $arr['new_cart_num'] ? round($arr['order_num'] / $arr['new_cart_num'] * 100, 2) : 0;
        //更新购物车转化率
        $arr['update_cart_cart'] = $arr['update_cart_num'] ? round($arr['order_num'] / $arr['update_cart_num'] * 100, 2) : 0;
        //当天创建的用户当天产生订单的转化率
        $status_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered']];
        $status_where['order_type'] = 1;
        //当天注册用户数
        $register_userids = $zeelool_model->table('customer_entity')->where($cart_where1)->column('entity_id');
        $register_num = count($register_userids);
        //当天注册用户在当天下单的用户数
        $order_user_count1 = 0;
        foreach ($register_userids as $register_userid) {
            //判断当前用户在当天是否下单
            $order = $zeelool_model->table('sales_flat_order')->where($cart_where1)->where($status_where)->where('customer_id', $register_userid)->value('entity_id');
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
            $order = $zeelool_model->table('sales_flat_order')->where($cart_where1)->where($status_where)->where('customer_id', $update_userid)->value('entity_id');
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
        $arr['virtual_stock'] = $model->alias('p')->join('fa_item i', 'p.sku=i.sku')->where($virtual_where)->sum('p.stock');
        //在售，预售，下架
        $item = new \app\admin\model\itemmanage\Item();
        $item_platform = new \app\admin\model\itemmanage\ItemPlatformSku;
        $site_where['platform_type'] = $arr['site'];
        $skus = $item->getFrameSku();
        $map_where['sku'] = ['in', $skus];
        $arr['glass_in_sale_num'] = $item_platform->where($map_where)->where($site_where)->where('outer_sku_status', 1)->count();
        $arr['glass_shelves_num'] = $item_platform->where($map_where)->where($site_where)->where('outer_sku_status', 2)->count();
        $arr['glass_presell_num'] = $item_platform->where($map_where)->where($site_where)->where('presell_status', 1)->count();
        $skus1 = $item->getOrnamentsSku();
        $map_where1['sku'] = ['in', $skus1];
        $arr['box_in_sale_num'] = $item_platform->where($map_where1)->where($site_where)->where('outer_sku_status', 1)->count();
        $arr['box_shelves_num'] = $item_platform->where($map_where1)->where($site_where)->where('outer_sku_status', 2)->count();
        $arr['box_presell_num'] = $item_platform->where($map_where1)->where($site_where)->where('presell_status', 1)->count();
        Db::name('datacenter_day')->insert($arr);
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
        $zeelool_data = new \app\admin\model\operatedatacenter\Zeelool();
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
        $order_where = [];
        $order_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $order_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered']];
        $arr['order_num'] = $this->zeelool->where($order_where)->where('order_type', 1)->count();
        //销售额
        $arr['sales_total_money'] = $this->zeelool->where($order_where)->where('order_type', 1)->sum('base_grand_total');
        //邮费
        $arr['shipping_total_money'] = $this->zeelool->where($order_where)->where('order_type', 1)->sum('base_shipping_amount');
        $arr['order_unit_price'] = $arr['order_num'] == 0 ? 0 : round($arr['sales_total_money'] / $arr['order_num'], 2);
        //中位数
        $sales_total_money = $this->zeelool->where($order_where)->where('order_type', 1)->column('base_grand_total');
        $arr['order_total_midnum'] = $this->median($sales_total_money);
        //标准差
        $arr['order_total_standard'] = $this->getVariance($sales_total_money);
        //补发订单数
        $arr['replacement_order_num'] = $this->zeelool->where($order_where)->where('order_type', 4)->count();
        //补发销售额
        $arr['replacement_order_total'] = $this->zeelool->where($order_where)->where('order_type', 4)->sum('base_grand_total');
        //网红订单数
        $arr['online_celebrity_order_num'] = $this->zeelool->where($order_where)->where('order_type', 3)->count();
        //补发销售额
        $arr['online_celebrity_order_total'] = $this->zeelool->where($order_where)->where('order_type', 3)->sum('base_grand_total');
        //会话
        // $arr['sessions'] = $this->google_session(2, $date_time);
        //会话转化率
        // $arr['session_rate'] = $arr['sessions'] != 0 ? round($arr['order_num'] / $arr['sessions'] * 100, 2) : 0;
        //新建购物车数量
        $cart_where1 = [];
        $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['new_cart_num'] = $zeelool_model->table('sales_flat_quote')->where($cart_where1)->where('base_grand_total', 'gt', 0)->count();
        //更新购物车数量
        $cart_where2 = [];
        $cart_where2[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['update_cart_num'] = $zeelool_model->table('sales_flat_quote')->where($cart_where2)->where('base_grand_total', 'gt', 0)->count();
        //新增加购率
        // $arr['add_cart_rate'] = $arr['sessions'] ? round($arr['new_cart_num'] / $arr['sessions'] * 100, 2) : 0;
        //更新加购率
        // $arr['update_add_cart_rate'] = $arr['sessions'] ? round($arr['update_cart_num'] / $arr['sessions'] * 100, 2) : 0;
        //新增购物车转化率
        $arr['cart_rate'] = $arr['new_cart_num'] ? round($arr['order_num'] / $arr['new_cart_num'] * 100, 2) : 0;
        //更新购物车转化率
        $arr['update_cart_cart'] = $arr['update_cart_num'] ? round($arr['order_num'] / $arr['update_cart_num'] * 100, 2) : 0;
        //当天创建的用户当天产生订单的转化率
        $status_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered']];
        $status_where['order_type'] = 1;
        //当天注册用户数
        $register_userids = $zeelool_model->table('customer_entity')->where($cart_where1)->column('entity_id');
        $register_num = count($register_userids);
        //当天注册用户在当天下单的用户数
        $order_user_count1 = 0;
        foreach ($register_userids as $register_userid) {
            //判断当前用户在当天是否下单
            $order = $zeelool_model->table('sales_flat_order')->where($cart_where1)->where($status_where)->where('customer_id', $register_userid)->value('entity_id');
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
            $order = $zeelool_model->table('sales_flat_order')->where($cart_where1)->where($status_where)->where('customer_id', $update_userid)->value('entity_id');
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
        $arr['virtual_stock'] = $model->alias('p')->join('fa_item i', 'p.sku=i.sku')->where($virtual_where)->sum('p.stock');
        //在售，预售，下架
        //在售，预售，下架
        $item = new \app\admin\model\itemmanage\Item();
        $item_platform = new \app\admin\model\itemmanage\ItemPlatformSku;
        $site_where['platform_type'] = $arr['site'];
        $skus = $item->getFrameSku();
        $map_where['sku'] = ['in', $skus];
        $arr['glass_in_sale_num'] = $item_platform->where($map_where)->where($site_where)->where('outer_sku_status', 1)->count();
        $arr['glass_shelves_num'] = $item_platform->where($map_where)->where($site_where)->where('outer_sku_status', 2)->count();
        $arr['glass_presell_num'] = $item_platform->where($map_where)->where($site_where)->where('presell_status', 1)->count();
        $skus1 = $item->getOrnamentsSku();
        $map_where1['sku'] = ['in', $skus1];
        $arr['box_in_sale_num'] = $item_platform->where($map_where1)->where($site_where)->where('outer_sku_status', 1)->count();
        $arr['box_shelves_num'] = $item_platform->where($map_where1)->where($site_where)->where('outer_sku_status', 2)->count();
        $arr['box_presell_num'] = $item_platform->where($map_where1)->where($site_where)->where('presell_status', 1)->count();
        //插入数据
        Db::name('datacenter_day')->insert($arr);
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
        $zeelool_data = new \app\admin\model\operatedatacenter\Zeelool();
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
        $arr['sum_order_num'] = $zeelool_model->table('sales_flat_order')->where($order_where)->where('order_type', 1)->count();
        //登录用户数
        $customer_where = [];
        $customer_where[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['login_user_num'] = $zeelool_model->table('customer_entity')->where($customer_where)->count();

        //支付成功的订单数
        $order_where = [];
        $order_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $order_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered']];
        $arr['order_num'] = $this->zeelool->where($order_where)->where('order_type', 1)->count();
        //销售额
        $arr['sales_total_money'] = $this->zeelool->where($order_where)->where('order_type', 1)->sum('base_grand_total');
        //邮费
        $arr['shipping_total_money'] = $this->zeelool->where($order_where)->where('order_type', 1)->sum('base_shipping_amount');
        $arr['order_unit_price'] = $arr['order_num'] == 0 ? 0 : round($arr['sales_total_money'] / $arr['order_num'], 2);
        //中位数
        $sales_total_money = $this->zeelool->where($order_where)->where('order_type', 1)->column('base_grand_total');
        $arr['order_total_midnum'] = $this->median($sales_total_money);
        //标准差
        $arr['order_total_standard'] = $this->getVariance($sales_total_money);
        //补发订单数
        $arr['replacement_order_num'] = $this->zeelool->where($order_where)->where('order_type', 4)->count();
        //补发销售额
        $arr['replacement_order_total'] = $this->zeelool->where($order_where)->where('order_type', 4)->sum('base_grand_total');
        //网红订单数
        $arr['online_celebrity_order_num'] = $this->zeelool->where($order_where)->where('order_type', 3)->count();
        //补发销售额
        $arr['online_celebrity_order_total'] = $this->zeelool->where($order_where)->where('order_type', 3)->sum('base_grand_total');

        //会话
        // $arr['sessions'] = $this->google_session(3, $date_time);
        //会话转化率
        // $arr['session_rate'] = $arr['sessions'] != 0 ? round($arr['order_num'] / $arr['sessions'] * 100, 2) : 0;
        //新建购物车数量
        $cart_where1 = [];
        $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['new_cart_num'] = $zeelool_model->table('sales_flat_quote')->where($cart_where1)->where('base_grand_total', 'gt', 0)->count();
        //更新购物车数量
        $cart_where2 = [];
        $cart_where2[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['update_cart_num'] = $zeelool_model->table('sales_flat_quote')->where($cart_where2)->where('base_grand_total', 'gt', 0)->count();
        //新增加购率
        // $arr['add_cart_rate'] = $arr['sessions'] ? round($arr['new_cart_num'] / $arr['sessions'] * 100, 2) : 0;
        //更新加购率
        // $arr['update_add_cart_rate'] = $arr['sessions'] ? round($arr['update_cart_num'] / $arr['sessions'] * 100, 2) : 0;
        //新增购物车转化率
        $arr['cart_rate'] = $arr['new_cart_num'] ? round($arr['order_num'] / $arr['new_cart_num'] * 100, 2) : 0;
        //更新购物车转化率
        $arr['update_cart_cart'] = $arr['update_cart_num'] ? round($arr['order_num'] / $arr['update_cart_num'] * 100, 2) : 0;
        //着陆页数据
        // $arr['landing_num'] = $zeelool_data->google_landing(3, $date_time);
        //产品详情页
        // $arr['detail_num'] = $zeelool_data->google_target13(3, $date_time);
        //加购
        // $arr['cart_num'] = $zeelool_data->google_target1(3, $date_time);
        //交易次数
        // $arr['complete_num'] = $zeelool_data->google_target_end(3, $date_time);
        //当天创建的用户当天产生订单的转化率
        $status_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $status_where['order_type'] = 1;
        //当天注册用户数
        $register_userids = $zeelool_model->table('customer_entity')->where($cart_where1)->column('entity_id');
        $register_num = count($register_userids);
        //当天注册用户在当天下单的用户数
        $order_user_count1 = 0;
        foreach ($register_userids as $register_userid) {
            //判断当前用户在当天是否下单
            $order = $zeelool_model->table('sales_flat_order')->where($cart_where1)->where($status_where)->where('customer_id', $register_userid)->value('entity_id');
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
            $order = $zeelool_model->table('sales_flat_order')->where($cart_where1)->where($status_where)->where('customer_id', $update_userid)->value('entity_id');
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
        $arr['virtual_stock'] = $model->alias('p')->join('fa_item i', 'p.sku=i.sku')->where($virtual_where)->sum('p.stock');
        //在售，预售，下架
        $item = new \app\admin\model\itemmanage\Item();
        $item_platform = new \app\admin\model\itemmanage\ItemPlatformSku;
        $site_where['platform_type'] = $arr['site'];
        $skus = $item->getFrameSku();
        $map_where['sku'] = ['in', $skus];
        $arr['glass_in_sale_num'] = $item_platform->where($map_where)->where($site_where)->where('outer_sku_status', 1)->count();
        $arr['glass_shelves_num'] = $item_platform->where($map_where)->where($site_where)->where('outer_sku_status', 2)->count();
        $arr['glass_presell_num'] = $item_platform->where($map_where)->where($site_where)->where('presell_status', 1)->count();
        $skus1 = $item->getOrnamentsSku();
        $map_where1['sku'] = ['in', $skus1];
        $arr['box_in_sale_num'] = $item_platform->where($map_where1)->where($site_where)->where('outer_sku_status', 1)->count();
        $arr['box_shelves_num'] = $item_platform->where($map_where1)->where($site_where)->where('outer_sku_status', 2)->count();
        $arr['box_presell_num'] = $item_platform->where($map_where1)->where($site_where)->where('presell_status', 1)->count();
        //插入数据
        Db::name('datacenter_day')->insert($arr);
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
        $arr['sum_order_num'] = $operate_model->table('sales_flat_order')->where($order_where)->where('order_type', 1)->count();
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
        $order_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered']];
        $arr['order_num'] = $this->order->where($order_where)->where('order_type', 1)->count();
        //销售额
        $arr['sales_total_money'] = $this->order->where($order_where)->where('order_type', 1)->sum('base_grand_total');
        //邮费
        $arr['shipping_total_money'] = $this->order->where($order_where)->where('order_type', 1)->sum('base_shipping_amount');
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
        $arr['replacement_order_total'] = $this->order->where($order_where)->where('order_type', 4)->sum('base_grand_total');
        //网红订单数
        $arr['online_celebrity_order_num'] = $this->order->where($order_where)->where('order_type', 3)->count();
        //补发销售额
        $arr['online_celebrity_order_total'] = $this->order->where($order_where)->where('order_type', 3)->sum('base_grand_total');
        //新建购物车数量
        $cart_where1 = [];
        $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['new_cart_num'] = $operate_model->table('sales_flat_quote')->where($cart_where1)->where('base_grand_total', 'gt', 0)->count();
        //更新购物车数量
        $cart_where2 = [];
        $cart_where2[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['update_cart_num'] = $operate_model->table('sales_flat_quote')->where($cart_where2)->where('base_grand_total', 'gt', 0)->count();
        //新增购物车转化率
        $arr['cart_rate'] = $arr['new_cart_num'] ? round($arr['order_num'] / $arr['new_cart_num'] * 100, 2) : 0;
        //更新购物车转化率
        $arr['update_cart_cart'] = $arr['update_cart_num'] ? round($arr['order_num'] / $arr['update_cart_num'] * 100, 2) : 0;
        //当天创建的用户当天产生订单的转化率
        $status_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered']];
        $status_where['order_type'] = 1;
        //当天注册用户数
        $register_userids = $operate_model->table('customer_entity')->where($cart_where1)->column('entity_id');
        $register_num = count($register_userids);
        //当天注册用户在当天下单的用户数
        $order_user_count1 = 0;
        foreach ($register_userids as $register_userid) {
            //判断当前用户在当天是否下单
            $order = $operate_model->table('sales_flat_order')->where($cart_where1)->where($status_where)->where('customer_id', $register_userid)->value('entity_id');
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
            $order = $operate_model->table('sales_flat_order')->where($cart_where1)->where($status_where)->where('customer_id', $update_userid)->value('entity_id');
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
        $arr['virtual_stock'] = $model->alias('p')->join('fa_item i', 'p.sku=i.sku')->where($virtual_where)->sum('p.stock');
        //在售，预售，下架
        $item = new \app\admin\model\itemmanage\Item();
        $item_platform = new \app\admin\model\itemmanage\ItemPlatformSku;
        $site_where['platform_type'] = $arr['site'];
        $skus = $item->getFrameSku();
        $map_where['sku'] = ['in', $skus];
        $arr['glass_in_sale_num'] = $item_platform->where($map_where)->where($site_where)->where('outer_sku_status', 1)->count();
        $arr['glass_shelves_num'] = $item_platform->where($map_where)->where($site_where)->where('outer_sku_status', 2)->count();
        $arr['glass_presell_num'] = $item_platform->where($map_where)->where($site_where)->where('presell_status', 1)->count();
        $skus1 = $item->getOrnamentsSku();
        $map_where1['sku'] = ['in', $skus1];
        $arr['box_in_sale_num'] = $item_platform->where($map_where1)->where($site_where)->where('outer_sku_status', 1)->count();
        $arr['box_shelves_num'] = $item_platform->where($map_where1)->where($site_where)->where('outer_sku_status', 2)->count();
        $arr['box_presell_num'] = $item_platform->where($map_where1)->where($site_where)->where('presell_status', 1)->count();
        Db::name('datacenter_day')->insert($arr);
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
        $arr['sum_order_num'] = $operate_model->table('sales_flat_order')->where($order_where)->where('order_type', 1)->count();
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
        $order_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered']];
        $arr['order_num'] = $this->order->where($order_where)->where('order_type', 1)->count();
        //销售额
        $arr['sales_total_money'] = $this->order->where($order_where)->where('order_type', 1)->sum('base_grand_total');
        //邮费
        $arr['shipping_total_money'] = $this->order->where($order_where)->where('order_type', 1)->sum('base_shipping_amount');
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
        $arr['replacement_order_total'] = $this->order->where($order_where)->where('order_type', 4)->sum('base_grand_total');
        //网红订单数
        $arr['online_celebrity_order_num'] = $this->order->where($order_where)->where('order_type', 3)->count();
        //补发销售额
        $arr['online_celebrity_order_total'] = $this->order->where($order_where)->where('order_type', 3)->sum('base_grand_total');
        //新建购物车数量
        $cart_where1 = [];
        $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['new_cart_num'] = $operate_model->table('sales_flat_quote')->where($cart_where1)->where('base_grand_total', 'gt', 0)->count();
        //更新购物车数量
        $cart_where2 = [];
        $cart_where2[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['update_cart_num'] = $operate_model->table('sales_flat_quote')->where($cart_where2)->where('base_grand_total', 'gt', 0)->count();
        //新增购物车转化率
        $arr['cart_rate'] = $arr['new_cart_num'] ? round($arr['order_num'] / $arr['new_cart_num'] * 100, 2) : 0;
        //更新购物车转化率
        $arr['update_cart_cart'] = $arr['update_cart_num'] ? round($arr['order_num'] / $arr['update_cart_num'] * 100, 2) : 0;
        //当天创建的用户当天产生订单的转化率
        $status_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered']];
        $status_where['order_type'] = 1;
        //当天注册用户数
        $register_userids = $operate_model->table('customer_entity')->where($cart_where1)->column('entity_id');
        $register_num = count($register_userids);
        //当天注册用户在当天下单的用户数
        $order_user_count1 = 0;
        foreach ($register_userids as $register_userid) {
            //判断当前用户在当天是否下单
            $order = $operate_model->table('sales_flat_order')->where($cart_where1)->where($status_where)->where('customer_id', $register_userid)->value('entity_id');
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
            $order = $operate_model->table('sales_flat_order')->where($cart_where1)->where($status_where)->where('customer_id', $update_userid)->value('entity_id');
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
        $arr['virtual_stock'] = $model->alias('p')->join('fa_item i', 'p.sku=i.sku')->where($virtual_where)->sum('p.stock');
        //在售，预售，下架
        $item = new \app\admin\model\itemmanage\Item();
        $item_platform = new \app\admin\model\itemmanage\ItemPlatformSku;
        $site_where['platform_type'] = $arr['site'];
        $skus = $item->getFrameSku();
        $map_where['sku'] = ['in', $skus];
        $arr['glass_in_sale_num'] = $item_platform->where($map_where)->where($site_where)->where('outer_sku_status', 1)->count();
        $arr['glass_shelves_num'] = $item_platform->where($map_where)->where($site_where)->where('outer_sku_status', 2)->count();
        $arr['glass_presell_num'] = $item_platform->where($map_where)->where($site_where)->where('presell_status', 1)->count();
        $skus1 = $item->getOrnamentsSku();
        $map_where1['sku'] = ['in', $skus1];
        $arr['box_in_sale_num'] = $item_platform->where($map_where1)->where($site_where)->where('outer_sku_status', 1)->count();
        $arr['box_shelves_num'] = $item_platform->where($map_where1)->where($site_where)->where('outer_sku_status', 2)->count();
        $arr['box_presell_num'] = $item_platform->where($map_where1)->where($site_where)->where('presell_status', 1)->count();
        Db::name('datacenter_day')->insert($arr);
        echo $date_time . "\n";
        echo date("Y-m-d H:i:s") . "\n";
        usleep(100000);
    }
    //运营数据中心  小站
    public function other_day_data()
    {
        $model = new \app\admin\model\itemmanage\ItemPlatformSku();
        $item = new \app\admin\model\itemmanage\Item();
        $item_platform = new \app\admin\model\itemmanage\ItemPlatformSku;
        $skus = $item->getFrameSku();
        $map_where['sku'] = ['in', $skus];
        $skus1 = $item->getOrnamentsSku();
        $map_where1['sku'] = ['in', $skus1];

        $map_where = [];

        $date_time = date('Y-m-d', strtotime("-1 day"));
        //meeloog
        $site_where['platform_type'] = $platform_where['platform_type'] = 4;
        $where['i.category_id'] = ['<>', 43];
        $where['i.is_del'] = 1;
        $where['i.is_open'] = 1;

        $arr = [];
        $arr['site'] = 4;
        $arr['day_date'] = $date_time;
        $arr['virtual_stock'] = $model->alias('p')->join('fa_item i', 'p.sku=i.sku')->where($where)->where($platform_where)->sum('p.stock');
        //在售，预售，下架
        $arr['glass_in_sale_num'] = $item_platform->where($map_where)->where($site_where)->where('outer_sku_status', 1)->count();
        $arr['glass_shelves_num'] = $item_platform->where($map_where)->where($site_where)->where('outer_sku_status', 2)->count();
        $arr['glass_presell_num'] = $item_platform->where($map_where)->where($site_where)->where('presell_status', 1)->count();
        $arr['box_in_sale_num'] = $item_platform->where($map_where1)->where($site_where)->where('outer_sku_status', 1)->count();
        $arr['box_shelves_num'] = $item_platform->where($map_where1)->where($site_where)->where('outer_sku_status', 2)->count();
        $arr['box_presell_num'] = $item_platform->where($map_where1)->where($site_where)->where('presell_status', 1)->count();
        Db::name('datacenter_day')->insert($arr);

        //批发站
        $site_where['platform_type'] = $platform_where['platform_type'] = 5;
        $arr['site'] = 5;
        $arr['virtual_stock'] = $model->alias('p')->join('fa_item i', 'p.sku=i.sku')->where($where)->where($platform_where)->sum('p.stock');
        //在售，预售，下架
        $arr['glass_in_sale_num'] = $item_platform->where($map_where)->where($site_where)->where('outer_sku_status', 1)->count();
        $arr['glass_shelves_num'] = $item_platform->where($map_where)->where($site_where)->where('outer_sku_status', 2)->count();
        $arr['glass_presell_num'] = $item_platform->where($map_where)->where($site_where)->where('presell_status', 1)->count();
        $arr['box_in_sale_num'] = $item_platform->where($map_where1)->where($site_where)->where('outer_sku_status', 1)->count();
        $arr['box_shelves_num'] = $item_platform->where($map_where1)->where($site_where)->where('outer_sku_status', 2)->count();
        $arr['box_presell_num'] = $item_platform->where($map_where1)->where($site_where)->where('presell_status', 1)->count();
        Db::name('datacenter_day')->insert($arr);

        //亚马逊
        $site_where['platform_type'] = $platform_where['platform_type'] = 8;
        $arr['site'] = 8;
        $arr['virtual_stock'] = $model->alias('p')->join('fa_item i', 'p.sku=i.sku')->where($where)->where($platform_where)->sum('p.stock');
        //在售，预售，下架
        $arr['glass_in_sale_num'] = $item_platform->where($map_where)->where($site_where)->where('outer_sku_status', 1)->count();
        $arr['glass_shelves_num'] = $item_platform->where($map_where)->where($site_where)->where('outer_sku_status', 2)->count();
        $arr['glass_presell_num'] = $item_platform->where($map_where)->where($site_where)->where('presell_status', 1)->count();
        $arr['box_in_sale_num'] = $item_platform->where($map_where1)->where($site_where)->where('outer_sku_status', 1)->count();
        $arr['box_shelves_num'] = $item_platform->where($map_where1)->where($site_where)->where('outer_sku_status', 2)->count();
        $arr['box_presell_num'] = $item_platform->where($map_where1)->where($site_where)->where('presell_status', 1)->count();
        Db::name('datacenter_day')->insert($arr);

        //zeelool_es
        $site_where['platform_type'] = $platform_where['platform_type'] = 9;
        $arr['site'] = 9;
        $arr['virtual_stock'] = $model->alias('p')->join('fa_item i', 'p.sku=i.sku')->where($where)->where($platform_where)->sum('p.stock');
        //在售，预售，下架
        $arr['glass_in_sale_num'] = $item_platform->where($map_where)->where($site_where)->where('outer_sku_status', 1)->count();
        $arr['glass_shelves_num'] = $item_platform->where($map_where)->where($site_where)->where('outer_sku_status', 2)->count();
        $arr['glass_presell_num'] = $item_platform->where($map_where)->where($site_where)->where('presell_status', 1)->count();
        $arr['box_in_sale_num'] = $item_platform->where($map_where1)->where($site_where)->where('outer_sku_status', 1)->count();
        $arr['box_shelves_num'] = $item_platform->where($map_where1)->where($site_where)->where('outer_sku_status', 2)->count();
        $arr['box_presell_num'] = $item_platform->where($map_where1)->where($site_where)->where('presell_status', 1)->count();
        Db::name('datacenter_day')->insert($arr);

        echo date("Y-m-d H:i:s") . "\n";
        echo "all is ok" . "\n";
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
        $where['o.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered']];
        $where['o.site'] = $site;
        $order = $this->order->alias('o')->join('fa_order_item_option i', 'o.entity_id=i.order_id')->field('i.sku,count(*) as count')->where($where)->group('i.sku')->select();
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
            $grade = $this->productGrade->where('true_sku',$sku)->value('grade');
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
        $arr = array(
            $grade1, $grade2, $grade3, $grade4, $grade5, $grade6, $grade7, $grade8
        );
        return $arr;
    }
    //ga的数据单独摘出来跑 防止ga接口数据报错 2020.11.2防止了ga的数据报错
    public function only_ga_data()
    {
        $date_time = date('Y-m-d', strtotime("-1 day"));
        $date_time_behind = date('Y-m-d', strtotime("-2 day"));

        //z站
        $data = Db::name('datacenter_day')->where(['day_date' => $date_time, 'site' => 1])->field('order_num,new_cart_num,update_cart_num,active_user_num')->find();

        //活跃用户数
        $arr['active_user_num'] = $this->google_active_user(1, $date_time);
        //会话
        $arr['sessions'] = $this->google_session(1, $date_time);
        //前天的ga会话数
        $date_time_behind_sessions_z = $this->google_session(1, $date_time_behind);
        //会话转化率
        $arr['session_rate'] = $arr['sessions'] != 0 ? round($data['order_num'] / $arr['sessions'] * 100, 2) : 0;
        //新增加购率
        $arr['add_cart_rate'] = $arr['sessions'] ? round($data['new_cart_num'] / $arr['sessions'] * 100, 2) : 0;
        //更新加购率
        $arr['update_add_cart_rate'] = $arr['sessions'] ? round($data['update_cart_num'] / $arr['sessions'] * 100, 2) : 0;
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
        //更新前天的会话数 防止ga数据误差
        Db::name('datacenter_day')->where(['day_date' => $date_time_behind, 'site' => 1])->update(['sessions'=>$date_time_behind_sessions_z]);
        usleep(100000);

        //v站
        $data = Db::name('datacenter_day')->where(['day_date' => $date_time, 'site' => 2])->field('order_num,new_cart_num,update_cart_num,active_user_num')->find();
        //活跃用户数
        $arr['active_user_num'] = $this->google_active_user(2, $date_time);
        //会话
        $arr['sessions'] = $this->google_session(2, $date_time);
        //前天的ga会话数
        $date_time_behind_sessions_v = $this->google_session(2, $date_time_behind);
        //会话转化率
        $arr['session_rate'] = $arr['sessions'] != 0 ? round($data['order_num'] / $arr['sessions'] * 100, 2) : 0;
        //新增加购率
        $arr['add_cart_rate'] = $arr['sessions'] ? round($data['new_cart_num'] / $arr['sessions'] * 100, 2) : 0;
        //更新加购率
        $arr['update_add_cart_rate'] = $arr['sessions'] ? round($data['update_cart_num'] / $arr['sessions'] * 100, 2) : 0;
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
        Db::name('datacenter_day')->where(['day_date' => $date_time_behind, 'site' => 2])->update(['sessions'=>$date_time_behind_sessions_v]);
        usleep(100000);

        //nihao站
        $data = Db::name('datacenter_day')->where(['day_date' => $date_time, 'site' => 3])->field('order_num,new_cart_num,update_cart_num,active_user_num')->find();
        //活跃用户数
        $arr['active_user_num'] = $this->google_active_user(3, $date_time);
        //会话
        $arr['sessions'] = $this->google_session(3, $date_time);
        //前天的ga会话数
        $date_time_behind_sessions_n = $this->google_session(3, $date_time_behind);
        //会话转化率
        $arr['session_rate'] = $arr['sessions'] != 0 ? round($data['order_num'] / $arr['sessions'] * 100, 2) : 0;
        //新增加购率
        $arr['add_cart_rate'] = $arr['sessions'] ? round($data['new_cart_num'] / $arr['sessions'] * 100, 2) : 0;
        //更新加购率
        $arr['update_add_cart_rate'] = $arr['sessions'] ? round($data['update_cart_num'] / $arr['sessions'] * 100, 2) : 0;
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
        Db::name('datacenter_day')->where(['day_date' => $date_time_behind, 'site' => 3])->update(['sessions'=>$date_time_behind_sessions_n]);
        usleep(100000);

        //de站
        $data = Db::name('datacenter_day')->where(['day_date' => $date_time, 'site' => 10])->field('order_num,new_cart_num,update_cart_num,active_user_num')->find();

        //活跃用户数
        $arr['active_user_num'] = $this->google_active_user(10, $date_time);
        //会话
        $arr['sessions'] = $this->google_session(10, $date_time);
        //前天的ga会话数
        $date_time_behind_sessions_z = $this->google_session(10, $date_time_behind);
        //会话转化率
        $arr['session_rate'] = $arr['sessions'] != 0 ? round($data['order_num'] / $arr['sessions'] * 100, 2) : 0;
        //新增加购率
        $arr['add_cart_rate'] = $arr['sessions'] ? round($data['new_cart_num'] / $arr['sessions'] * 100, 2) : 0;
        //更新加购率
        $arr['update_add_cart_rate'] = $arr['sessions'] ? round($data['update_cart_num'] / $arr['sessions'] * 100, 2) : 0;
        $zeeloolde_data = new \app\admin\model\operatedatacenter\ZeeloolDe();
        //着陆页数据
        $arr['landing_num'] = $zeeloolde_data->google_landing(10, $date_time);
        //产品详情页
        $arr['detail_num'] = $zeeloolde_data->google_target13(10, $date_time);
        //加购
        $arr['cart_num'] = $zeeloolde_data->google_target1(10, $date_time);
        //交易次数
        $arr['complete_num'] = $zeeloolde_data->google_target_end(10, $date_time);
        $update = Db::name('datacenter_day')->where(['day_date' => $date_time, 'site' => 10])->update($arr);
        //更新前天的会话数 防止ga数据误差
        Db::name('datacenter_day')->where(['day_date' => $date_time_behind, 'site' => 10])->update(['sessions'=>$date_time_behind_sessions_z]);
        usleep(100000);

        //jp站
        $data = Db::name('datacenter_day')->where(['day_date' => $date_time, 'site' => 11])->field('order_num,new_cart_num,update_cart_num,active_user_num')->find();

        //活跃用户数
        $arr['active_user_num'] = $this->google_active_user(11, $date_time);
        //会话
        $arr['sessions'] = $this->google_session(11, $date_time);
        //前天的ga会话数
        $date_time_behind_sessions_z = $this->google_session(11, $date_time_behind);
        //会话转化率
        $arr['session_rate'] = $arr['sessions'] != 0 ? round($data['order_num'] / $arr['sessions'] * 100, 2) : 0;
        //新增加购率
        $arr['add_cart_rate'] = $arr['sessions'] ? round($data['new_cart_num'] / $arr['sessions'] * 100, 2) : 0;
        //更新加购率
        $arr['update_add_cart_rate'] = $arr['sessions'] ? round($data['update_cart_num'] / $arr['sessions'] * 100, 2) : 0;
        $zeelooljp_data = new \app\admin\model\operatedatacenter\ZeeloolJp();
        //着陆页数据
        $arr['landing_num'] = $zeelooljp_data->google_landing(11, $date_time);
        //产品详情页
        $arr['detail_num'] = $zeelooljp_data->google_target13(11, $date_time);
        //加购
        $arr['cart_num'] = $zeelooljp_data->google_target1(11, $date_time);
        //交易次数
        $arr['complete_num'] = $zeelooljp_data->google_target_end(11, $date_time);
        $update = Db::name('datacenter_day')->where(['day_date' => $date_time, 'site' => 11])->update($arr);
        //更新前天的会话数 防止ga数据误差
        Db::name('datacenter_day')->where(['day_date' => $date_time_behind, 'site' => 11])->update(['sessions'=>$date_time_behind_sessions_z]);
        usleep(100000);

        if ($data['active_user_num'] == 0) {
            $this->only_ga_data();
        }
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
    }
    //统计昨天各品类镜框的销量
    public function goods_type_day_center($plat, $goods_type)
    {
        $start = date('Y-m-d', strtotime('-1 day'));
        $seven_days = $start . ' 00:00:00 - ' . $start . ' 23:59:59';
        $createat = explode(' ', $seven_days);
        $itemMap['m.created_at'] = ['between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]]];
        //判断站点
        switch ($plat) {
            case 1:
                $model = Db::connect('database.db_zeelool');
                break;
            case 2:
                $model = Db::connect('database.db_voogueme');
                break;
            case 3:
                $model = Db::connect('database.db_nihao');
                break;
            default:
                $model = false;
                break;
        }
        $model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $model->table('sales_flat_order_item')->query("set time_zone='+8:00'");
        $model->table('sales_flat_order_item_prescription')->query("set time_zone='+8:00'");
        //$whereItem = " o.status in ('processing','complete','creditcard_proccessing','free_processing')";
        $whereItem = " o.status in ('free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered')";
        //某个品类眼镜的销售副数
        $frame_sales_num = $model->table('sales_flat_order_item m')
            ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
            ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
            ->where('p.goods_type', '=', $goods_type)
            ->where($whereItem)
            ->where($itemMap)
            ->sum('m.qty_ordered');
        //求出眼镜的销售额 base_price  base_discount_amount
        $frame_money_price = $model->table('sales_flat_order_item m')
            ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
            ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
            ->where($whereItem)
            ->where('p.goods_type', '=', $goods_type)
            ->where($itemMap)
            ->sum('m.base_price');
        //眼镜的折扣价格
        $frame_money_discount = $model->table('sales_flat_order_item m')
            ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
            ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
            ->where($whereItem)
            ->where('p.goods_type', '=', $goods_type)
            ->where($itemMap)
            ->sum('m.base_discount_amount');
        //眼镜的实际销售额
        $frame_money = round(($frame_money_price - $frame_money_discount), 2);

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
            $res_item = $_item->where(['sku' => $val['sku']])->update(['on_way_stock' => $val['all_on_way'], 'wait_instock_num' => $val['all_instock']]);
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
                    $_item_platform->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->setInc('plat_on_way_stock', $stock_num);
                } else {
                    $num = round($v['purchase_num'] * $val['rate']);
                    $stock_num -= $num;
                    $_item_platform->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->setInc('plat_on_way_stock', $num);
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
                            $_item_platform->where(['sku' => $val['sku'], 'platform_type' => $vall['website_type']])->setDec('plat_on_way_stock', $stock_num);
                            //更新站点待入库数量
                            $_item_platform->where(['sku' => $val['sku'], 'platform_type' => $vall['website_type']])->setInc('wait_instock_num', $stock_num);
                        } else {
                            $num = round($val['purchase_num'] * $vall['rate']);
                            $stock_num -= $num;
                            $_item_platform->where(['sku' => $val['sku'], 'platform_type' => $vall['website_type']])->setDec('plat_on_way_stock', $num);
                            //更新站点待入库数量
                            $_item_platform->where(['sku' => $val['sku'], 'platform_type' => $vall['website_type']])->setInc('wait_instock_num', $num);
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
        $zeeloolOperate = new \app\admin\model\operatedatacenter\Zeelool;
        set_time_limit(0);
        //统计昨天的数据
        $data = date('Y-m-d', strtotime('-1 day'));
        // $data = '2020-11-11';
        $_item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $sku_data = $_item_platform_sku
            ->field('sku,grade,platform_sku,stock,plat_on_way_stock')
            ->where(['platform_type' => 1, 'outer_sku_status' => 1])
            ->select();

        //当前站点的所有sku映射关系
        $sku_data = collection($sku_data)->toArray();
        //ga所有的sku唯一身份浏览量的数据
        $ga_skus = $zeeloolOperate->google_sku_detail(1, $data);
        $ga_skus = array_column($ga_skus, 'uniquePageviews', 'ga:pagePath');
        foreach ($sku_data as $k => $v) {
            $sku_data[$k]['unique_pageviews'] = 0;
            $sku_data[$k]['goods_grade'] = $sku_data[$k]['grade'];
            $sku_data[$k]['day_date'] = $data;
            $sku_data[$k]['site'] = 1;
            $sku_data[$k]['day_stock'] = $sku_data[$k]['stock'];
            $sku_data[$k]['day_onway_stock'] = $sku_data[$k]['plat_on_way_stock'];
            unset($sku_data[$k]['stock']);
            unset($sku_data[$k]['grade']);
            unset($sku_data[$k]['plat_on_way_stock']);
            foreach ($ga_skus as $kk => $vv) {
                if (strpos($kk, $v['sku']) != false) {
                    $sku_data[$k]['unique_pageviews'] += $vv;
                }
            }
            Db::name('datacenter_sku_day')->insert($sku_data[$k]);
        }


        $sku_data = $_item_platform_sku
            ->field('sku,grade,platform_sku,stock,plat_on_way_stock')
            ->where(['platform_type' => 2, 'outer_sku_status' => 1])
            ->select();
        //当前站点的所有sku映射关系
        $sku_data = collection($sku_data)->toArray();
        //ga所有的sku唯一身份浏览量的数据
        $ga_skus = $zeeloolOperate->google_sku_detail(2, $data);
        $ga_skus = array_column($ga_skus, 'uniquePageviews', 'ga:pagePath');

        foreach ($sku_data as $k => $v) {
            $sku_data[$k]['unique_pageviews'] = 0;
            $sku_data[$k]['goods_grade'] = $sku_data[$k]['grade'];
            $sku_data[$k]['day_date'] = $data;
            $sku_data[$k]['site'] = 2;
            $sku_data[$k]['day_stock'] = $sku_data[$k]['stock'];
            $sku_data[$k]['day_onway_stock'] = $sku_data[$k]['plat_on_way_stock'];
            unset($sku_data[$k]['stock']);
            unset($sku_data[$k]['grade']);
            unset($sku_data[$k]['plat_on_way_stock']);
            foreach ($ga_skus as $kk => $vv) {
                if (strpos($kk, $v['sku']) != false) {
                    $sku_data[$k]['unique_pageviews'] += $vv;
                }
            }
            Db::name('datacenter_sku_day')->insert($sku_data[$k]);
        }

        $sku_data = $_item_platform_sku
            ->field('sku,grade,platform_sku,stock,plat_on_way_stock')
            ->where(['platform_type' => 3, 'outer_sku_status' => 1])
            ->select();
        //当前站点的所有sku映射关系
        $sku_data = collection($sku_data)->toArray();
        //ga所有的sku唯一身份浏览量的数据
        $ga_skus = $zeeloolOperate->google_sku_detail(3, $data);
        $ga_skus = array_column($ga_skus, 'uniquePageviews', 'ga:pagePath');

        foreach ($sku_data as $k => $v) {
            $sku_data[$k]['unique_pageviews'] = 0;
            $sku_data[$k]['goods_grade'] = $sku_data[$k]['grade'];
            $sku_data[$k]['day_date'] = $data;
            $sku_data[$k]['site'] = 3;
            $sku_data[$k]['day_stock'] = $sku_data[$k]['stock'];
            $sku_data[$k]['day_onway_stock'] = $sku_data[$k]['plat_on_way_stock'];
            unset($sku_data[$k]['stock']);
            unset($sku_data[$k]['grade']);
            unset($sku_data[$k]['plat_on_way_stock']);
            foreach ($ga_skus as $kk => $vv) {
                if (strpos($kk, $v['sku']) != false) {
                    $sku_data[$k]['unique_pageviews'] += $vv;
                }
            }
            Db::name('datacenter_sku_day')->insert($sku_data[$k]);
        }
    }
    public function sku_day_data_order()
    {
        set_time_limit(0);
        Db::connect('database.db_zeelool')->table('sales_flat_order_item_prescription')->query("set time_zone='+8:00'");
        Db::connect('database.db_zeelool')->table('sales_flat_order_item')->query("set time_zone='+8:00'");
        Db::connect('database.db_zeelool')->table('sales_flat_order')->query("set time_zone='+8:00'");
        //统计昨天的数据
        $data = date('Y-m-d', strtotime('-1 day'));
        // $data = '2020-11-11';
        $z_sku_list = Db::name('datacenter_sku_day')->where(['day_date' => $data, 'site' => 1])->select();
        foreach ($z_sku_list as $k => $v) {
            $map['sku'] = ['like', $v['platform_sku'] . '%'];
            $map['a.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered']];
            $map['a.order_type'] = ['=', 1];
            $time_where[] = ['exp', Db::raw("DATE_FORMAT(a.created_at, '%Y-%m-%d') = '" . $data . "'")];
            //某个sku当天的订单数
            $z_sku_list[$k]['order_num'] = Db::connect('database.db_zeelool')->table('sales_flat_order')
                ->where($map)
                ->where($time_where)
                ->alias('a')
                ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
                ->group('order_id')
                ->field('entity_id,sku,a.created_at,a.order_type,a.status')
                ->count();
            //sku销售总副数
            $time_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $data . "'")];

            //统计错误 弃用此方法
            // $z_sku_list[$k]['glass_num'] = Db::connect('database.db_zeelool')
            //     ->table('sales_flat_order_item')
            //     ->where('sku', 'like', $v['platform_sku'] . '%')
            //     ->where($time_where1)
            //     ->sum('qty_ordered');
            $z_sku_list[$k]['glass_num'] = Db::connect('database.db_zeelool')->table('sales_flat_order')
                ->where($map)
                ->where($time_where)
                ->alias('a')
                ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
                ->sum('qty_ordered');

            $whereItem = " o.status in ('free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered')";
            $whereItem1 = " o.order_type = 1";
            $itemMap[] = ['exp', Db::raw("DATE_FORMAT(m.created_at, '%Y-%m-%d') = '" . $data . "'")];
            //求出眼镜的销售额 base_price  base_discount_amount
            $frame_money_price = Db::connect('database.db_zeelool')->table('sales_flat_order_item m')
                ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
                ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
                ->where($whereItem)
                ->where($whereItem1)
                ->where($itemMap)
                ->where('p.sku', 'like', $v['platform_sku'] . '%')
                ->sum('m.base_price');
            //眼镜的折扣价格
            $frame_money_discount = Db::connect('database.db_zeelool')->table('sales_flat_order_item m')
                ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
                ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
                ->where($whereItem)
                ->where($whereItem1)
                ->where($itemMap)
                ->where('p.sku', 'like', $v['platform_sku'] . '%')
                ->sum('m.base_discount_amount');
            //眼镜的实际销售额
            $frame_money = round(($frame_money_price - $frame_money_discount), 2);
            $z_sku_list[$k]['sku_grand_total'] = $frame_money_price;
            $z_sku_list[$k]['sku_row_total'] = $frame_money;
            Db::name('datacenter_sku_day')->update($z_sku_list[$k]);
            echo $z_sku_list[$k]['sku'] . "\n";
            echo '<br>';
        }

        //v站
        Db::connect('database.db_voogueme')->table('sales_flat_order_item_prescription')->query("set time_zone='+8:00'");
        Db::connect('database.db_voogueme')->table('sales_flat_order_item')->query("set time_zone='+8:00'");
        Db::connect('database.db_voogueme')->table('sales_flat_order')->query("set time_zone='+8:00'");
        //统计昨天的数据
        $z_sku_list = Db::name('datacenter_sku_day')->where(['day_date' => $data, 'site' => 2])->select();
        foreach ($z_sku_list as $k => $v) {
            $map['sku'] = ['like', $v['platform_sku'] . '%'];
            $map['a.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered']];
            $map['a.order_type'] = ['=', 1];
            $time_where[] = ['exp', Db::raw("DATE_FORMAT(a.created_at, '%Y-%m-%d') = '" . $data . "'")];
            //某个sku当天的订单数
            $z_sku_list[$k]['order_num'] = Db::connect('database.db_voogueme')->table('sales_flat_order')
                ->where($map)
                ->where($time_where)
                ->alias('a')
                ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
                ->group('order_id')
                ->field('entity_id,sku,a.created_at,a.order_type,a.status')
                ->count();
            //sku销售总副数
            $time_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $data . "'")];
            // $z_sku_list[$k]['glass_num'] = Db::connect('database.db_voogueme')
            //     ->table('sales_flat_order_item')
            //     ->where('sku', 'like', $v['platform_sku'] . '%')
            //     ->where($time_where1)
            //     ->sum('qty_ordered');
            $z_sku_list[$k]['glass_num'] = Db::connect('database.db_voogueme')->table('sales_flat_order')
                ->where($map)
                ->where($time_where)
                ->alias('a')
                ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
                ->sum('qty_ordered');
            $whereItem = " o.status in ('free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered')";
            $whereItem1 = " o.order_type = 1";
            $itemMap[] = ['exp', Db::raw("DATE_FORMAT(m.created_at, '%Y-%m-%d') = '" . $data . "'")];
            //求出眼镜的销售额 base_price  base_discount_amount
            $frame_money_price = Db::connect('database.db_voogueme')->table('sales_flat_order_item m')
                ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
                ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
                ->where($whereItem)
                ->where($whereItem1)
                ->where($itemMap)
                ->where('p.sku', 'like', $v['platform_sku'] . '%')
                ->sum('m.base_price');
            //眼镜的折扣价格
            $frame_money_discount = Db::connect('database.db_voogueme')->table('sales_flat_order_item m')
                ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
                ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
                ->where($whereItem)
                ->where($whereItem1)
                ->where($itemMap)
                ->where('p.sku', 'like', $v['platform_sku'] . '%')
                ->sum('m.base_discount_amount');
            //眼镜的实际销售额
            $frame_money = round(($frame_money_price - $frame_money_discount), 2);
            $z_sku_list[$k]['sku_grand_total'] = $frame_money_price;
            $z_sku_list[$k]['sku_row_total'] = $frame_money;
            Db::name('datacenter_sku_day')->update($z_sku_list[$k]);
            echo $z_sku_list[$k]['sku'] . "\n";
            echo '<br>';
        }

        //nihao站
        Db::connect('database.db_nihao')->table('sales_flat_order_item_prescription')->query("set time_zone='+8:00'");
        Db::connect('database.db_nihao')->table('sales_flat_order_item')->query("set time_zone='+8:00'");
        Db::connect('database.db_nihao')->table('sales_flat_order')->query("set time_zone='+8:00'");
        //统计昨天的数据
        $z_sku_list = Db::name('datacenter_sku_day')->where(['day_date' => $data, 'site' => 3])->select();
        foreach ($z_sku_list as $k => $v) {
            $map['sku'] = ['like', $v['platform_sku'] . '%'];
            $map['a.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered']];
            $map['a.order_type'] = ['=', 1];
            $time_where[] = ['exp', Db::raw("DATE_FORMAT(a.created_at, '%Y-%m-%d') = '" . $data . "'")];
            //某个sku当天的订单数
            $z_sku_list[$k]['order_num'] = Db::connect('database.db_nihao')->table('sales_flat_order')
                ->where($map)
                ->where($time_where)
                ->alias('a')
                ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
                ->group('order_id')
                ->field('entity_id,sku,a.created_at,a.order_type,a.status')
                ->count();
            //sku销售总副数
            $time_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $data . "'")];
            // $z_sku_list[$k]['glass_num'] = Db::connect('database.db_nihao')
            //     ->table('sales_flat_order_item')
            //     ->where('sku', 'like', $v['platform_sku'] . '%')
            //     ->where($time_where1)
            //     ->sum('qty_ordered');
            $z_sku_list[$k]['glass_num'] = Db::connect('database.db_voogueme')->table('sales_flat_order')
                ->where($map)
                ->where($time_where)
                ->alias('a')
                ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
                ->sum('qty_ordered');
            $whereItem = " o.status in ('free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered')";
            $whereItem1 = " o.order_type = 1";
            $itemMap[] = ['exp', Db::raw("DATE_FORMAT(m.created_at, '%Y-%m-%d') = '" . $data . "'")];
            //求出眼镜的销售额 base_price  base_discount_amount
            $frame_money_price = Db::connect('database.db_nihao')->table('sales_flat_order_item m')
                ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
                ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
                ->where($whereItem)
                ->where($whereItem1)
                ->where($itemMap)
                ->where('p.sku', 'like', $v['platform_sku'] . '%')
                ->sum('m.base_price');
            //眼镜的折扣价格
            $frame_money_discount = Db::connect('database.db_nihao')->table('sales_flat_order_item m')
                ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
                ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
                ->where($whereItem)
                ->where($whereItem1)
                ->where($itemMap)
                ->where('p.sku', 'like', $v['platform_sku'] . '%')
                ->sum('m.base_discount_amount');
            //眼镜的实际销售额
            $frame_money = round(($frame_money_price - $frame_money_discount), 2);
            $z_sku_list[$k]['sku_grand_total'] = $frame_money_price;
            $z_sku_list[$k]['sku_row_total'] = $frame_money;
            Db::name('datacenter_sku_day')->update($z_sku_list[$k]);
            echo $z_sku_list[$k]['sku'] . "\n";
            echo '<br>';
        }
    }
    public function sku_day_data_other()
    {
        //z站
        set_time_limit(0);
        //购物车数量
        $zeelool_model = Db::connect('database.db_zeelool_online');
        $zeelool_model->table('sales_flat_quote')->query("set time_zone='+8:00'");
        //统计昨天的数据
        $data = date('Y-m-d', strtotime('-1 day'));
        $z_sku_list = Db::name('datacenter_sku_day')->where(['day_date' => $data, 'site' => 1])->select();
        foreach ($z_sku_list as $k => $v) {
            $cart_where1 = [];
            $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(a.created_at, '%Y-%m-%d') = '" . $data . "'")];
            $cart_where1['b.sku'] = ['like', $v['platform_sku'] . '%'];
            $z_sku_list[$k]['cart_num'] = $zeelool_model->table('sales_flat_quote')
                ->alias('a')
                ->join(['sales_flat_quote_item' => 'b'], 'a.entity_id=b.quote_id')
                ->where($cart_where1)
                ->where('base_grand_total', 'gt', 0)
                ->field('b.sku,a.base_grand_total,a.created_at')
                ->count();
            $z_sku_list[$k]['now_pricce'] = Db::connect('database.db_zeelool_online')
                ->table('catalog_product_index_price') //为了获取现价找的表
                ->alias('a')
                ->join(['catalog_product_entity' => 'b'], 'a.entity_id=b.entity_id') //商品主表
                ->where('b.sku', 'like', $v['platform_sku'] . '%')
                ->value('a.final_price');
            Db::name('datacenter_sku_day')->update($z_sku_list[$k]);
            echo $z_sku_list[$k]['sku'] . "\n";
            echo '<br>';
        }
        //v站
        //购物车数量
        $zeelool_model = Db::connect('database.db_voogueme_online');
        $zeelool_model->table('sales_flat_quote')->query("set time_zone='+8:00'");
        //统计昨天的数据
        $z_sku_list = Db::name('datacenter_sku_day')->where(['day_date' => $data, 'site' => 2])->select();
        foreach ($z_sku_list as $k => $v) {
            $cart_where1 = [];
            $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(a.created_at, '%Y-%m-%d') = '" . $data . "'")];
            $cart_where1['b.sku'] = ['like', $v['platform_sku'] . '%'];
            $z_sku_list[$k]['cart_num'] = $zeelool_model->table('sales_flat_quote')
                ->alias('a')
                ->join(['sales_flat_quote_item' => 'b'], 'a.entity_id=b.quote_id')
                ->where($cart_where1)
                ->where('base_grand_total', 'gt', 0)
                ->field('b.sku,a.base_grand_total,a.created_at')
                ->count();
            $z_sku_list[$k]['now_pricce'] = Db::connect('database.db_voogueme_online')
                ->table('catalog_product_index_price') //为了获取现价找的表
                ->alias('a')
                ->join(['catalog_product_entity' => 'b'], 'a.entity_id=b.entity_id') //商品主表
                ->where('b.sku', 'like', $v['platform_sku'] . '%')
                ->value('a.final_price');
            Db::name('datacenter_sku_day')->update($z_sku_list[$k]);
            echo $z_sku_list[$k]['sku'] . "\n";
            echo '<br>';
        }
        //nihao站
        //购物车数量
        $zeelool_model = Db::connect('database.db_nihao_online');
        $zeelool_model->table('sales_flat_quote')->query("set time_zone='+8:00'");
        //统计昨天的数据
        $z_sku_list = Db::name('datacenter_sku_day')->where(['day_date' => $data, 'site' => 3])->select();
        foreach ($z_sku_list as $k => $v) {
            $cart_where1 = [];
            $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(a.created_at, '%Y-%m-%d') = '" . $data . "'")];
            $cart_where1['b.sku'] = ['like', $v['platform_sku'] . '%'];
            $z_sku_list[$k]['cart_num'] = $zeelool_model->table('sales_flat_quote')
                ->alias('a')
                ->join(['sales_flat_quote_item' => 'b'], 'a.entity_id=b.quote_id')
                ->where($cart_where1)
                ->where('base_grand_total', 'gt', 0)
                ->field('b.sku,a.base_grand_total,a.created_at')
                ->count();
            $z_sku_list[$k]['now_pricce'] = Db::connect('database.db_nihao_online')
                ->table('catalog_product_index_price') //为了获取现价找的表
                ->alias('a')
                ->join(['catalog_product_entity' => 'b'], 'a.entity_id=b.entity_id') //商品主表
                ->where('b.sku', 'like', $v['platform_sku'] . '%')
                ->value('a.final_price');
            Db::name('datacenter_sku_day')->update($z_sku_list[$k]);
            echo $z_sku_list[$k]['sku'] . "\n";
            echo '<br>';
        }
    }
    //ga的数据单独摘出来跑 防止ga接口数据报错 2020.11.2防止了ga的数据报错
    public function only_ga_data_01_09()
    {
        $date_time = date('Y-m-d', strtotime("-1 day"));
        $date_time = '2021-01-09';

        //z站
        $data = Db::name('datacenter_day')->where(['day_date' => $date_time, 'site' => 1])->field('order_num,new_cart_num,update_cart_num')->find();

        //活跃用户数
        $arr['active_user_num'] = $this->google_active_user(1, $date_time);
        //会话
        $arr['sessions'] = $this->google_session(1, $date_time);
        //会话转化率
        $arr['session_rate'] = $arr['sessions'] != 0 ? round($data['order_num'] / $arr['sessions'] * 100, 2) : 0;
        //新增加购率
        $arr['add_cart_rate'] = $arr['sessions'] ? round($data['new_cart_num'] / $arr['sessions'] * 100, 2) : 0;
        //更新加购率
        $arr['update_add_cart_rate'] = $arr['sessions'] ? round($data['update_cart_num'] / $arr['sessions'] * 100, 2) : 0;
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
        $data = Db::name('datacenter_day')->where(['day_date' => $date_time, 'site' => 2])->field('order_num,new_cart_num,update_cart_num')->find();
        //活跃用户数
        $arr['active_user_num'] = $this->google_active_user(2, $date_time);
        //会话
        $arr['sessions'] = $this->google_session(2, $date_time);
        //会话转化率
        $arr['session_rate'] = $arr['sessions'] != 0 ? round($data['order_num'] / $arr['sessions'] * 100, 2) : 0;
        //新增加购率
        $arr['add_cart_rate'] = $arr['sessions'] ? round($data['new_cart_num'] / $arr['sessions'] * 100, 2) : 0;
        //更新加购率
        $arr['update_add_cart_rate'] = $arr['sessions'] ? round($data['update_cart_num'] / $arr['sessions'] * 100, 2) : 0;
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
        $data = Db::name('datacenter_day')->where(['day_date' => $date_time, 'site' => 3])->field('order_num,new_cart_num,update_cart_num')->find();
        //活跃用户数
        $arr['active_user_num'] = $this->google_active_user(3, $date_time);
        //会话
        $arr['sessions'] = $this->google_session(3, $date_time);
        //会话转化率
        $arr['session_rate'] = $arr['sessions'] != 0 ? round($data['order_num'] / $arr['sessions'] * 100, 2) : 0;
        //新增加购率
        $arr['add_cart_rate'] = $arr['sessions'] ? round($data['new_cart_num'] / $arr['sessions'] * 100, 2) : 0;
        //更新加购率
        $arr['update_add_cart_rate'] = $arr['sessions'] ? round($data['update_cart_num'] / $arr['sessions'] * 100, 2) : 0;
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
        $arr = Db::name('datacenter_day')->where('day_date','>=','2021-03-01')->where('site','in',[1,2,3])->field('id,day_date,site,sessions')->select();
        foreach ($arr as $k=>$v){
            $arr[$k]['new_sessions'] = $this->google_session($v['site'],$v['day_date']);
            $res = Db::name('datacenter_day')->where('id',$v['id'])->update(['sessions'=>$arr[$k]['new_sessions']]);
            dump($res);
        }
    }
    public function only_ga_data_01_08()
    {
        $date_time = date('Y-m-d', strtotime("-1 day"));
        $date_time = '2021-01-08';

        //z站
        $data = Db::name('datacenter_day')->where(['day_date' => $date_time, 'site' => 1])->field('order_num,new_cart_num,update_cart_num')->find();

        //活跃用户数
        $arr['active_user_num'] = $this->google_active_user(1, $date_time);
        //会话
        $arr['sessions'] = $this->google_session(1, $date_time);
        //会话转化率
        $arr['session_rate'] = $arr['sessions'] != 0 ? round($data['order_num'] / $arr['sessions'] * 100, 2) : 0;
        //新增加购率
        $arr['add_cart_rate'] = $arr['sessions'] ? round($data['new_cart_num'] / $arr['sessions'] * 100, 2) : 0;
        //更新加购率
        $arr['update_add_cart_rate'] = $arr['sessions'] ? round($data['update_cart_num'] / $arr['sessions'] * 100, 2) : 0;
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
        $data = Db::name('datacenter_day')->where(['day_date' => $date_time, 'site' => 2])->field('order_num,new_cart_num,update_cart_num')->find();
        //活跃用户数
        $arr['active_user_num'] = $this->google_active_user(2, $date_time);
        //会话
        $arr['sessions'] = $this->google_session(2, $date_time);
        //会话转化率
        $arr['session_rate'] = $arr['sessions'] != 0 ? round($data['order_num'] / $arr['sessions'] * 100, 2) : 0;
        //新增加购率
        $arr['add_cart_rate'] = $arr['sessions'] ? round($data['new_cart_num'] / $arr['sessions'] * 100, 2) : 0;
        //更新加购率
        $arr['update_add_cart_rate'] = $arr['sessions'] ? round($data['update_cart_num'] / $arr['sessions'] * 100, 2) : 0;
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
        $data = Db::name('datacenter_day')->where(['day_date' => $date_time, 'site' => 3])->field('order_num,new_cart_num,update_cart_num')->find();
        //活跃用户数
        $arr['active_user_num'] = $this->google_active_user(3, $date_time);
        //会话
        $arr['sessions'] = $this->google_session(3, $date_time);
        //会话转化率
        $arr['session_rate'] = $arr['sessions'] != 0 ? round($data['order_num'] / $arr['sessions'] * 100, 2) : 0;
        //新增加购率
        $arr['add_cart_rate'] = $arr['sessions'] ? round($data['new_cart_num'] / $arr['sessions'] * 100, 2) : 0;
        //更新加购率
        $arr['update_add_cart_rate'] = $arr['sessions'] ? round($data['update_cart_num'] / $arr['sessions'] * 100, 2) : 0;
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
        $instocks = $this->instock->alias('s')->join('fa_check_order c', 'c.id=s.check_id')->join('fa_purchase_order_item oi', 'c.purchase_id=oi.purchase_id')->join('fa_purchase_order o','oi.purchase_id=o.id')->where($instock_where)->field('s.id,round(o.purchase_total/oi.purchase_num,2) purchase_price')->select();
        $instock_total = 0; //入库总金额
        foreach ($instocks as $key => $instock) {
            $arr = array();
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
            $bar_items = $this->item->alias('i')->join('fa_purchase_order_item p','i.purchase_id=p.purchase_id and i.sku=p.sku')->join('fa_purchase_order o','p.purchase_id=o.id')->field('i.out_stock_id,i.purchase_id,i.out_stock_time,p.actual_purchase_price,round(o.purchase_total/p.purchase_num,2) purchase_price')->where($bar_where)->where('barcode_id', $bar)->select();
            $sum_count = 0;
            $sum_total = 0;
            foreach ($bar_items as $item) {
                if(count(array_unique($is_exist)) != 0){
                    foreach($is_exist as $value){
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
                }else{
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
        $bars1 = $this->item->alias('i')->join('fa_purchase_order_item p','i.purchase_id=p.purchase_id and i.sku=p.sku')->join('fa_purchase_order o','p.purchase_id=o.id')->where($bar_where1)->field('i.out_stock_id,i.purchase_id,i.out_stock_time,p.actual_purchase_price,round(o.purchase_total/p.purchase_num,2) purchase_price')->select();
        if (count($bars1) != 0) {
            $flag1 = [];
            $flag1['stock_id'] = $stockId;
            $flag1['type'] = 3;
            foreach ($bars1 as $bar1) {
                if(count(array_unique($is_exist)) != 0){
                    foreach($is_exist as $value){
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
                            if ($bar1['actual_purchase_price']  != 0) {
                                $total1 = $bar1['actual_purchase_price'];   //有成本价拿成本价计算
                            } else {
                                $total1 = $bar1['purchase_price'];   //没有成本价拿预估价计算
                            }
                        }
                    }
                }else{
                    //没有冲减数据，直接拿预估成本计算
                    if ($bar1['actual_purchase_price']  != 0) {
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
        $rest_total = $this->stockparameter->order('id', 'desc')->field('rest_total')->limit(1,1)->select();
        $cha_amount = 0;  //冲减金额
        foreach ($is_exist as $k=>$v){
            $cha_amount += $v['total'];
        }
        $end_rest = round($cha_amount + $rest_total[0]['rest_total'] + $instock_total - $outstock_total1 - $outstock_total2, 2);
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
        $count = $financecost->where(['createtime' => ['between', [$fisrttime, $endtime]], 'is_carry_forward' => 0, 'bill_type' => ['<>', 9]])->count();
        if ($count < 1) {
            echo "无结果";
            die;
        }
        //生成周期结转单
        $financecycle = new \app\admin\model\finance\FinanceCycle();
        $res = $financecycle->allowField(true)->save([
            'cycle_number' => 'JZ' . date('YmdHis') . rand(100, 999) . rand(100, 999),
            'createtime' => time()
        ]);

        if (false !== $res) {

            $financecost->where(['createtime' => ['between', [$fisrttime, $endtime]], 'is_carry_forward' => 0])->update(['is_carry_forward' => 1, 'cycle_id' => $financecycle->id]);
        }

        echo "ok";
    }

    //计划任务定时跑物流数据 得到揽收时间存入物流信息表 如果没有揽收时间 就以录入物流单号的时间作为揽收时间为了供应商待结算列表的结算周期使用
    public function logistics_info()
    {
        //采购单物流单详情
        $rows = Db::name('logistics_info')
            ->where('createtime','>',date("Y-m-d H:i:s", strtotime("-12 hour")))
            ->where('createtime','<',date("Y-m-d H:i:s", time()))
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
                $collect_time = date("Y-m-d H:i:s",strtotime(array_slice($data[0]['data'], -1, 1)[0]['time']));
            }else{
                $collect_time = $v['createtime'];
            }
            // dump($collect_time);
            $res = Db::name('logistics_info')->where('id',$v['id'])->update(['collect_time'=>$collect_time]);
            // dump($res);
        }
        if ($res){
            echo "ok". "\n";;
        }else{
            echo 'fail'. "\n";;
        }
        // die;
    }



     /**
     * 处理待入库数量 - 计划任务
     */
    public function process_wait_stock()
    {
        $item = new \app\admin\model\itemmanage\Item();
        $purchase = new \app\admin\model\purchase\PurchaseOrder();

        $list = $item->where(['is_open' => 1, 'is_del' => 1, 'wait_instock_num' => ['<', 0]])->select();
        $params = [];
        foreach ($list as $k => $v) {
            $purchase_num = $purchase->alias('a')->where(['purchase_status' => 7, 'stock_status' => 0, 'b.sku' => $v['sku']])->join(['fa_purchase_order_item' => 'b'], 'a.id=b.purchase_id')->sum('purchase_num');
            $params[$k]['id'] = $v['id'];
            $params[$k]['wait_instock_num'] = $purchase_num;
        }
        $item->saveAll($params);
    }
}
