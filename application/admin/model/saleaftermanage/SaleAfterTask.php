<?php

namespace app\admin\model\saleaftermanage;

use app\admin\model\itemmanage\ItemPlatformSku;
use app\admin\model\order\order\NewOrderItemProcess;
use app\admin\model\web\WebUsers;
use app\admin\model\web\WebVipOrder;
use Think\Log;
use think\Model;
use think\Db;
use Util\NihaoPrescriptionDetailHelper;
use Util\ZeeloolPrescriptionDetailHelper;
use Util\ZeeloolDePrescriptionDetailHelper;
use Util\ZeeloolEsPrescriptionDetailHelper;
use Util\ZeeloolJpPrescriptionDetailHelper;
use Util\VooguemePrescriptionDetailHelper;
use Util\MeeloogPrescriptionDetailHelper;
use app\admin\model\order\order\NewOrder;

class SaleAfterTask extends Model
{
    //数据库
    protected $connection = 'database';

    // 表名
    protected $name = 'sale_after_task';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;
    //定义任务记录属性
    protected $task_remark = '';
    // 追加属性
    protected $append = [];

    //关联模型
    public function saleAfterIssue()
    {
        return $this->belongsTo('sale_after_issue', 'problem_id')->setEagerlyType(0);
    }

    public function getOrderPlatformList()
    {
        //return config('site.order_platform');
        return [0 => '请选择', 1 => 'zeelool站', 2 => 'Voogueme站', 3 => 'nihao', 4 => 'amazon', 5 => 'App'];
    }

    public function getOrderStatusList()
    {
        //return config('site.order_status');
        return [0 => '未付款', 1 => '已付款'];
    }

    //优先级返回数据
    public function getPrtyIdList()
    {
        //return [1=>'高',2=>'中',3=>'低'];
        return [3 => '低', 2 => '中', 1 => '高'];
    }

    //获取选项卡列表
    public function getTabList()
    {
        return [
            ['name' => '我创建的任务', 'field' => 'create_person', 'value' => session('admin.nickname')],
            ['name' => '我的任务', 'field' => 'rep_id', 'value' => session('admin.id')],
        ];
    }

    //获取解决方案列表
    public function getSolveScheme()
    {
        return [
            0 => "请选择",
            1 => "部分退款",
            2 => "退全款",
            3 => "补发",
            4 => "加钱补发",
            5 => "退款+补发",
            6 => "折扣买新",
            7 => "发放积分",
            8 => "安抚",
            9 => "长时间未回复",
        ];
    }

    /***
     * 根据订单平台和订单号获取订单和订单购买的商品信息
     *
     * @param $ordertype
     * @param $order_number
     *
     * @return array|bool|false|\PDOStatement|string|Model
     */
    public function getOrderInfo($ordertype, $order_number)
    {
        switch ($ordertype) {
            case 1:
                $db = 'database.db_zeelool';
                break;
            case 2:
                $db = 'database.db_voogueme';
                break;
            case 3:
                $db = 'database.db_nihao';
                break;
            default:
                return false;
                break;
        }
        // switch ($ordertype) {
        //     case 1:
        //         $db = 'database.db_zeelool_online';
        //         break;
        //     case 2:
        //         $db = 'database.db_voogueme_online';
        //         break;
        //     case 3:
        //         $db = 'database.db_nihao_online';
        //         break;
        //     default:
        //         return false;
        //         break;
        // }
        $result = Db::connect($db)->table('sales_flat_order')->where('increment_id', '=', $order_number)->field('entity_id,status,store_id,increment_id,customer_email,customer_firstname,customer_lastname,total_item_count')->find();
        if (!$result) {
            return false;
        }
        $item = Db::connect($db)->table('sales_flat_order_item')->where('order_id', '=', $result['entity_id'])->field('item_id,name,sku,qty_ordered,product_options')->select();
        if (!$item) {
            return false;
        }
        $arr = [];
        foreach ($item as $key => $val) {
            $arr[$key]['item_id'] = $val['item_id'];
            $arr[$key]['name'] = $val['name'];
            $arr[$key]['sku'] = $val['sku'];
            $arr[$key]['qty_ordered'] = $val['qty_ordered'];
            $tmp_product_options = unserialize($val['product_options']);
            $arr[$key]['index_type'] = isset($tmp_product_options['info_buyRequest']['tmplens']['index_type']) ? $tmp_product_options['info_buyRequest']['tmplens']['index_type'] : '';
            $arr[$key]['coatiing_name'] = isset($tmp_product_options['info_buyRequest']['tmplens']['coatiing_name']) ? $tmp_product_options['info_buyRequest']['tmplens']['coatiing_name'] : "";
            $tmp_prescription_params = isset($tmp_product_options['info_buyRequest']['tmplens']['prescription']) ? $tmp_product_options['info_buyRequest']['tmplens']['prescription'] : '';
            if (!empty($tmp_prescription_params)) {
                if ($ordertype <= 2) {
                    $tmp_prescription_params = explode("&", $tmp_prescription_params);
                    $tmp_lens_params = [];
                    foreach ($tmp_prescription_params as $tmp_key => $tmp_value) {
                        $arr_value = explode("=", $tmp_value);
                        $tmp_lens_params[$arr_value[0]] = $arr_value[1];
                    }
                } elseif ($ordertype == 3) {
                    $tmp_lens_params = json_decode($tmp_prescription_params, true);
                }

                $arr[$key]['prescription_type'] = $tmp_lens_params['prescription_type'];
                $arr[$key]['od_sph'] = isset($tmp_lens_params['od_sph']) ? $tmp_lens_params['od_sph'] : '';
                $arr[$key]['od_cyl'] = isset($tmp_lens_params['od_cyl']) ? $tmp_lens_params['od_cyl'] : '';
                $arr[$key]['od_axis'] = isset($tmp_lens_params['od_axis']) ? $tmp_lens_params['od_axis'] : '';
                if ($ordertype <= 2) {
                    $arr[$key]['od_add'] = isset($tmp_lens_params['os_add']) ? $tmp_lens_params['os_add'] : '';
                    $arr[$key]['os_add'] = isset($tmp_lens_params['od_add']) ? $tmp_lens_params['od_add'] : '';
                } else {
                    $arr[$key]['od_add'] = isset($tmp_lens_params['od_add']) ? $tmp_lens_params['od_add'] : '';
                    $arr[$key]['os_add'] = isset($tmp_lens_params['os_add']) ? $tmp_lens_params['os_add'] : '';
                }

                $arr[$key]['os_sph'] = isset($tmp_lens_params['os_sph']) ? $tmp_lens_params['os_sph'] : '';
                $arr[$key]['os_cyl'] = isset($tmp_lens_params['os_cyl']) ? $tmp_lens_params['os_cyl'] : '';
                $arr[$key]['os_axis'] = isset($tmp_lens_params['os_axis']) ? $tmp_lens_params['os_axis'] : '';
                if (isset($tmp_lens_params['pdcheck']) && $tmp_lens_params['pdcheck'] == 'on') {  //双pd值
                    $arr[$key]['pd_r'] = isset($tmp_lens_params['pd_r']) ? $tmp_lens_params['pd_r'] : '';
                    $arr[$key]['pd_l'] = isset($tmp_lens_params['pd_l']) ? $tmp_lens_params['pd_l'] : '';
                } else {
                    $arr[$key]['pd_r'] = $arr[$key]['pd_l'] = isset($tmp_lens_params['pd']) ? $tmp_lens_params['pd'] : '';
                }
                if (isset($tmp_lens_params['prismcheck']) && $tmp_lens_params['prismcheck'] == 'on') { //存在斜视
                    $arr[$key]['od_bd'] = isset($tmp_lens_params['od_bd']) ? $tmp_lens_params['od_bd'] : '';
                    $arr[$key]['od_pv'] = isset($tmp_lens_params['od_pv']) ? $tmp_lens_params['od_pv'] : '';
                    $arr[$key]['os_pv'] = isset($tmp_lens_params['os_pv']) ? $tmp_lens_params['os_pv'] : '';
                    $arr[$key]['os_bd'] = isset($tmp_lens_params['os_bd']) ? $tmp_lens_params['os_bd'] : '';
                    $arr[$key]['od_pv_r'] = isset($tmp_lens_params['od_pv_r']) ? $tmp_lens_params['od_pv_r'] : '';
                    $arr[$key]['od_bd_r'] = isset($tmp_lens_params['od_bd_r']) ? $tmp_lens_params['od_bd_r'] : '';
                    $arr[$key]['os_pv_r'] = isset($tmp_lens_params['os_pv_r']) ? $tmp_lens_params['os_pv_r'] : '';
                    $arr[$key]['os_bd_r'] = isset($tmp_lens_params['os_bd_r']) ? $tmp_lens_params['os_bd_r'] : '';
                } else {
                    $arr[$key]['od_bd'] = "";
                    $arr[$key]['od_pv'] = "";
                    $arr[$key]['os_pv'] = "";
                    $arr[$key]['os_bd'] = "";
                    $arr[$key]['od_pv_r'] = "";
                    $arr[$key]['od_bd_r'] = "";
                    $arr[$key]['os_pv_r'] = "";
                    $arr[$key]['os_bd_r'] = "";
                }
            } else {
                $arr[$key]['prescription_type'] = "";
                $arr[$key]['od_sph'] = "";
                $arr[$key]['od_cyl'] = "";
                $arr[$key]['od_axis'] = "";
                $arr[$key]['od_add'] = "";
                $arr[$key]['os_sph'] = "";
                $arr[$key]['os_cyl'] = "";
                $arr[$key]['os_axis'] = "";
                $arr[$key]['os_add'] = "";
                $arr[$key]['pd_r'] = "";
                $arr[$key]['pd_l'] = "";
                $arr[$key]['od_bd'] = "";
                $arr[$key]['od_pv'] = "";
                $arr[$key]['os_pv'] = "";
                $arr[$key]['os_bd'] = "";
                $arr[$key]['od_pv_r'] = "";
                $arr[$key]['od_bd_r'] = "";
                $arr[$key]['os_pv_r'] = "";
                $arr[$key]['os_bd_r'] = "";
            }
        }
        $result['item'] = $arr;

        return $result ? $result : false;
    }

    /***
     * 任务详情信息
     *
     * @param id  任务id
     */
    public function getTaskDetail($id)
    {
        $result = $this->alias('t')->join(' sale_after_issue s', 't.problem_id = s.id')->where('t.id', '=', $id)->field('t.id,task_status,task_number,order_platform,
        order_number,order_status,order_skus,order_source,dept_id,rep_id,prty_id,problem_id,problem_desc,upload_photos,create_person,customer_name,handle_scheme,
        customer_email,refund_money,refund_way,give_coupon,tariff,make_up_price_order,replacement_order,integral,t.create_time,s.name')->find();
        if (!$result) {
            return false;
        }
        //$result['problem_desc'] = strip_tags($result['problem_desc']);
        $result['task_remark'] = (new SaleAfterTaskRemark())->getRelevanceRecord($id);

        //$result['orderInfo'] = $this->getOrderInfo($result['order_platform'],$result['order_number']);
        return $result ? $result : false;
    }

    /***
     * 模糊查询订单
     *
     * @param $order_platform
     * @param $increment_id
     */
    public function getLikeOrder($order_platform, $increment_id)
    {
        switch ($order_platform) {
            case 1:
                $db = 'database.db_zeelool';
                break;
            case 2:
                $db = 'database.db_voogueme';
                break;
            case 3:
                $db = 'database.db_nihao';
                break;
            case 4:
                $db = 'database.db_meeloog';
                break;
            case 5:
                $db = 'database.db_weseeoptical';
                break;
            case 9:
                $db = 'database.db_zeelool_es';
                break;
            case 10:
                $db = 'database.db_zeelool_de';
                break;
            case 11:
                $db = 'database.db_zeelool_jp';
                break;
            default:
                return false;
                break;
        }
        $result = Db::connect($db)->table('sales_flat_order')->where('increment_id', 'like', "%{$increment_id}%")->field('increment_id')->limit(10)->select();
        if (!$result) {
            return [];
        }
        $arr = [];
        foreach ($result as $k => $v) {
            $arr[] = $v['increment_id'];
        }

        return $arr;
    }

    /**
     * 模糊查询订单-新
     *
     * @param string $increment_id 订单号
     *
     * @return array
     * @author lzh
     */
    public function getLikeOrderNew($increment_id)
    {
        $_new_order = new NewOrder();
        $result = $_new_order
            ->where('increment_id', 'like', "%{$increment_id}%")
            ->limit(10)
            ->column('increment_id');

        return $result;
    }

    /***
     * @param $order_platform 订单平台
     * @param $email          用户邮箱
     */
    public function getLikeEmail($order_platform, $email)
    {
        switch ($order_platform) {
            case 1:
                $db = 'database.db_zeelool';
                break;
            case 2:
                $db = 'database.db_voogueme';
                break;
            case 3:
                $db = 'database.db_nihao';
                break;
            case 4:
                $db = 'database.db_meeloog';
                break;
            case 9:
                $db = 'database.db_zeelool_es';
                break;
            case 10:
                $db = 'database.db_zeelool_de';
                break;
            case 11:
                $db = 'database.db_zeelool_jp';
                break;
            default:
                return false;
                break;
        }
        $result = Db::connect($db)->table('sales_flat_order')->where('customer_email', 'like', "%{$email}%")->field('customer_email')->group('customer_email')->limit(10)->select();
        if (!$result) {
            return false;
        }
        $arr = [];
        foreach ($result as $k => $v) {
            $arr[] = $v['customer_email'];
        }

        return $arr;
    }

    /***
     * @param $order_platform  订单平台
     * @param $customer_phone  用户电话
     */
    public function getLikePhone($order_platform, $customer_phone)
    {
        switch ($order_platform) {
            case 1:
                $db = 'database.db_zeelool';
                break;
            case 2:
                $db = 'database.db_voogueme';
                break;
            case 3:
                $db = 'database.db_nihao';
                break;
            case 4:
                $db = 'database.db_meeloog';
                break;
            case 9:
                $db = 'database.db_zeelool_es';
                break;
            case 10:
                $db = 'database.db_zeelool_de';
                break;
            case 11:
                $db = 'database.db_zeelool_jp';
                break;
            default:
                return false;
                break;
        }
        $map[] = ['exp', Db::raw("replace(telephone,'-','') like '%{$customer_phone}%'")];
        $result = Db::connect($db)->table('sales_flat_order_address')->where($map)->field('telephone')->limit(10)->select();
        if (!$result) {
            return false;
        }
        $arr = [];
        foreach ($result as $k => $v) {
            $arr[] = $v['telephone'];
        }

        return $arr;
    }

    /***
     * 模糊查询用户姓名
     *
     * @param $orderType
     * @param $customer_name
     */
    public function getLikeName($order_platform, $customer_name)
    {
        switch ($order_platform) {
            case 1:
                $db = 'database.db_zeelool';
                break;
            case 2:
                $db = 'database.db_voogueme';
                break;
            case 3:
                $db = 'database.db_nihao';
                break;
            case 4:
                $db = 'database.db_meeloog';
                break;
            case 9:
                $db = 'database.db_zeelool_es';
                break;
            case 10:
                $db = 'database.db_zeelool_de';
                break;
            case 11:
                $db = 'database.db_zeelool_jp';
                break;
            default:
                return false;
                break;
        }
        $result = Db::connect($db)->table('sales_flat_order')->where('customer_firstname', 'like', "%{$customer_name}%")->whereOr('customer_lastname', 'like', "%{$customer_name}%")->field('customer_firstname,customer_lastname')->limit(10)->select();
        if (!$result) {
            return false;
        }
        $arr = [];
        foreach ($result as $k => $v) {
            $arr[] = $v['customer_firstname'] . ' ' . $v['customer_lastname'];
        }

        return $arr;
    }

    /***
     * 模糊查询运单号
     *
     * @param $orderType
     * @param $track_number
     */
    public function getLikeTrackNumber($order_platform, $track_number)
    {
        switch ($order_platform) {
            case 1:
                $db = 'database.db_zeelool';
                break;
            case 2:
                $db = 'database.db_voogueme';
                break;
            case 3:
                $db = 'database.db_nihao';
                break;
            case 4:
                $db = 'database.db_meeloog';
                break;
            case 9:
                $db = 'database.db_zeelool_es';
                break;
            case 10:
                $db = 'database.db_zeelool_de';
                break;
            case 11:
                $db = 'database.db_zeelool_jp';
                break;
            default:
                return false;
                break;
        }
        $result = Db::connect($db)->table('sales_flat_shipment_track')->where('track_number', 'like', "%{$track_number}%")->field('track_number')->limit(10)->select();
        if (!$result) {
            return false;
        }
        $arr = [];
        foreach ($result as $k => $v) {
            $arr[] = $v['track_number'];
        }

        return $arr;
    }

    /***
     * 模糊查询交易号
     *
     * @param $order_platform
     * @param $transaction_id
     */
    public function getLikeTransaction($order_platform, $transaction_id)
    {
        switch ($order_platform) {
            case 1:
                $db = 'database.db_zeelool';
                break;
            case 2:
                $db = 'database.db_voogueme';
                break;
            case 3:
                $db = 'database.db_nihao';
                break;
            case 4:
                $db = 'database.db_meeloog';
                break;
            case 9:
                $db = 'database.db_zeelool_es';
                break;
            case 10:
                $db = 'database.db_zeelool_de';
                break;
            case 11:
                $db = 'database.db_zeelool_jp';
                break;
            default:
                return false;
                break;
        }
        $result = Db::connect($db)->table('sales_flat_order_payment')->where('last_trans_id', 'like', "%{$transaction_id}%")->field('last_trans_id')->limit(10)->select();
        if (!$result) {
            return false;
        }
        $arr = [];
        foreach ($result as $k => $v) {
            $arr[] = $v['last_trans_id'];
        }

        return $arr;
    }

    /****
     * @param int    $order_platform 订单平台
     * @param string $increment_id   订单号
     * @param array  $customer_name  用户名
     * @param string $customer_phone 用户电话
     * @param string $track_number   运单号
     * @param string $transaction_id 交易号
     * @param        $email
     */
    public function getCustomerEmailBak(int $order_platform, $increment_id = '', $customer_name = [], $customer_phone = '', $track_number = '', $transaction_id = '', $email)
    {
        switch ($order_platform) {
            case 1:
                $db = 'database.db_zeelool';
                $db_online = 'database.db_zeelool_online';
                break;
            case 2:
                $db = 'database.db_voogueme';
                $db_online = 'database.db_voogueme_online';
                break;
            case 3:
                $db = 'database.db_nihao';
                $db_online = '';
                break;
            case 4:
                $db = 'database.db_meeloog';
                $db_online = '';
                break;
            case 9:
                $db = 'database.db_zeelool_es';
                break;
            case 10:
                $db = 'database.db_zeelool_de';
                break;
            case 11:
                $db = 'database.db_zeelool_jp';
                break;
            default:
                return false;
                break;
        }
        //求出用户的邮箱
        $customer_email = '';
        //根据订单号搜索
        if ($increment_id) {
            //如果输入的是订单号
            $customer_email = Db::connect($db)->table('sales_flat_order')->where('increment_id', $increment_id)->value('customer_email');
            //如果输入的是vip订单号
            if (!$customer_email && $order_platform < 3) {
                $customer_email = Db::connect($db_online)->table('oc_vip_order')->where('order_number', $increment_id)->value('customer_email');
            }
        }
        //根据客户姓名搜索
        if (!empty($customer_name)) {
            $customer_email = Db::connect($db)->table('sales_flat_order')->where('customer_firstname', $customer_name[0])
                ->where('customer_lastname', $customer_name[1])->value('customer_email');
        }
        //根据客户电话搜索
        if ($customer_phone) {
            $customer_email = Db::connect($db)->table('sales_flat_order_address')->where('telephone', $customer_phone)
                ->value('email');
        }
        //根据物流单号搜索
        if ($track_number) {
            $customer_email = Db::connect($db)->table('sales_flat_shipment_track s')->join('sales_flat_order o ', ' s.order_id = o.entity_id', 'left')
                ->where('s.track_number', $track_number)->value('o.customer_email');
        }

        //根据交易号搜索
        if ($transaction_id) {
            $customer_email = Db::connect($db)->table('sales_flat_order_payment p')->join('sales_flat_order o ', ' p.parent_id = o.entity_id', 'left')
                ->where('p.last_trans_id', $transaction_id)->value('o.customer_email');
        }

        //根据用户邮箱求出用户的所有订单
        if (!empty($email)) {
            $customer_email = $email;
        }
        if (!empty($customer_email)) {
            //求出用户的等级
            $customer_group_code = Db::connect($db)->table('customer_entity c')->join('customer_group g', ' c.group_id = g.customer_group_id')->where(['c.email' => $customer_email])->value('g.customer_group_code');
            //如果是z站或者v站的话求出是否存在VIP订单
            if ($db_online) {
                $order_vip = Db::connect($db_online)->table('oc_vip_order')->where(['customer_email' => $customer_email])->field('id,customer_email,order_number,order_amount,order_status,order_type,start_time,end_time,is_active_status,admin_name')->select();
            }
            $result = Db::connect($db)->table('sales_flat_order o')
                // ->join('sales_flat_shipment_track s', 'o.entity_id=s.order_id', 'left')
                ->join('sales_flat_order_payment p', 'o.entity_id=p.parent_id', 'left')
                ->where('customer_email', $customer_email)
                ->field('o.is_modify_address,o.base_to_order_rate,o.base_total_paid,o.base_total_due,o.entity_id,o.mw_rewardpoint,o.mw_rewardpoint_discount_show,o.status,o.coupon_code,o.coupon_rule_name,o.store_id,o.increment_id,o.customer_email,o.customer_firstname,o.customer_lastname,o.order_currency_code,o.total_item_count,o.grand_total,o.base_grand_total,o.base_shipping_amount,o.shipping_description,o.base_total_paid,o.base_total_due,o.created_at,round(o.total_qty_ordered,0) total_qty_ordered,o.order_type,p.base_amount_paid,p.base_amount_ordered,p.base_amount_authorized,p.method,p.last_trans_id,p.additional_information')
                ->group('o.entity_id')
                ->order('o.entity_id desc')->select();
            if (!$result) {
                return false;
            }
//            Log::write("搜索订单数据");
//            Log::write("$result");
//            Log::write("$order_platform");
            foreach ($result as $k => $v) {
                $ship = Db::connect($db)->table('sales_flat_shipment_track')->where(['order_id' => $v['entity_id']])->order('entity_id desc')->find();
                $result[$k]['track_number'] = $ship['track_number'];
                $result[$k]['titel'] = $ship['titel'];

                //$result[$k]['item'] = Db::connect($db)->table('sales_flat_order_item')->where('order_id','=',$v['entity_id'])->field('item_id,name,sku,qty_ordered,product_options')->select();
                if ($order_platform == 1) {
                    $result[$k]['item'] = ZeeloolPrescriptionDetailHelper::get_one_by_increment_id($v['increment_id']);
                } elseif ($order_platform == 2) {
                    $result[$k]['item'] = VooguemePrescriptionDetailHelper::get_one_by_increment_id($v['increment_id']);
                } elseif ($order_platform == 3) {
                    $result[$k]['item'] = NihaoPrescriptionDetailHelper::get_one_by_increment_id($v['increment_id']);
                } elseif ($order_platform == 4) {
                    $result[$k]['item'] = MeeloogPrescriptionDetailHelper::get_one_by_increment_id($v['increment_id']);
                } elseif ($order_platform == 9) {
                    $result[$k]['item'] = ZeeloolEsPrescriptionDetailHelper::get_one_by_increment_id($v['increment_id']);
                } elseif ($order_platform == 10) {
                    $result[$k]['item'] = ZeeloolDePrescriptionDetailHelper::get_one_by_increment_id($v['increment_id']);
                } elseif ($order_platform == 11) {
                    $result[$k]['item'] = ZeeloolJpPrescriptionDetailHelper::get_one_by_increment_id($v['increment_id']);
                }


//                if (!empty($result[$k]['item'][$k]['sku'])){
                foreach ($result[$k]['item'] as $key => $value) {
//                    Log::write("输出每次子订单信息");
//                    Log::write($value['order_id']);
//                    Log::write($value['item_id']);
                    $result[$k]['item'][$key]['order_number'] = Db::connect('database.db_mojing_order')->table('fa_order')->where('id', $value['order_id'])->value('increment_id');
                    //子订单号
                    $result[$k]['item'][$key]['item_order_number'] = Db::connect('database.db_mojing_order')->table('fa_order_item_process')
                        ->where('magento_order_id', $value['order_id'])->where('site', $order_platform)->where('item_id', $value['item_id'])->value('item_order_number');

                    //虚拟库存
                    $result[$k]['item'][$key]['stock'] = Db::connect('database.db_stock')->table('fa_item_platform_sku')
                        ->where('platform_sku', $value['sku'])->where('platform_type', $order_platform)->value('stock');
                }
//                }

                //订单地址表
                $address = Db::connect($db)->table('sales_flat_order_address')->where(['parent_id' => $v['entity_id']])->field('address_type,telephone,postcode,street,city,region,country_id,firstname,lastname')->select();
                $result[$k]['address'] = $address;

                //工单列表
                $workOrderListResult = WorkOrderList::workOrderListInfo($v['increment_id']);

                //补差价列表
                $differencePriceList = Db::connect($db)->table('oc_difference_price_order')->where(['origin_order_number' => $v['increment_id']])->select();

                $result[$k]['workOrderList'] = $workOrderListResult['list'];
                $result[$k]['differencePriceList'] = $differencePriceList;
                switch ($v['order_type']) {
                    case 2:
                        $result[$k]['order_type'] = '<span style="color:#f39c12">批发</span>';
                        break;
                    case 3:
                        $result[$k]['order_type'] = '<span style="color:#18bc9c">网红</span>';
                        break;
                    case 4:
                        $result[$k]['order_type'] = '<span style="color:#e74c3c">补发</span>';
                        break;
                    default:
                        $result[$k]['order_type'] = '<span style="color:#0073b7">普通</span>';
                        break;
                }
                $result[$k]['real_papid'] = round(($v['base_total_paid'] + $v['base_total_due']) * $v['base_to_order_rate'], 3);
            }
            //用户的等级
            if ($customer_group_code) {
                $customer['customer_group_code'] = $customer_group_code;
            } else {
                $customer['customer_group_code'] = '';
            }
            //用户的vip订单
            if ($order_vip) {
                //把vip订单查询出来放到数组当中
                $arr_order_vip = [];
                foreach ($order_vip as $v) {
                    $arr_order_vip[] = $v['order_number'];
                }
                $customer['order_vip'] = $order_vip;
                $customer['arr_order_vip'] = $arr_order_vip;
            } else {
                $customer['order_vip'] = '';
                $customer['arr_order_vip'] = '';
            }
            $customer['customer_email'] = $customer_email;
            $customer['customer_name'] = $result[0]['customer_firstname'] . ' ' . $result[0]['customer_lastname'];
            $customer['success_counter'] = $customer['success_total'] = $customer['failed_counter'] = $customer['failed_total'] = 0;
            $orderStatus = ['complete', 'processing', 'free_processing', 'delivered'];
            foreach ($result as $key => $val) {
                //计算支付成功和失败次数
                if (in_array($val['status'], $orderStatus)) {
                    $customer['success_counter']++;
                    $customer['success_total'] += $val['base_grand_total'];
                } else {
                    $customer['failed_counter']++;
                    $customer['failed_total'] += $val['base_grand_total'];
                }
                //求出所有的订单号
                $result['increment_id'][] = $val['increment_id'];
                $result[$key]['additional_information'] = unserialize($val['additional_information']);
                $result[$key]['additional_information']['paypal_payer_email'] = isset($result[$key]['additional_information']['paypal_payer_email']) ? $result[$key]['additional_information']['paypal_payer_email'] : '';
                $result[$key]['additional_information']['paypal_payer_id'] = isset($result[$key]['additional_information']['paypal_payer_id']) ? $result[$key]['additional_information']['paypal_payer_id'] : '';
                $result[$key]['additional_information']['paypal_payer_status'] = isset($result[$key]['additional_information']['paypal_payer_status']) ? $result[$key]['additional_information']['paypal_payer_status'] : '';
                $result[$key]['additional_information']['paypal_payment_status'] = isset($result[$key]['additional_information']['paypal_payment_status']) ? $result[$key]['additional_information']['paypal_payment_status'] : '';
                //求出订单下的商品信息
                $result[$key]['arr'] = [];
            }
            $result['info'] = $customer;
        } else {
            $result = false;
        }

        return $result;
    }


    /****
     * @param int    $order_platform 订单平台
     * @param string $increment_id   订单号
     * @param array  $customer_name  用户名
     * @param string $customer_phone 用户电话
     * @param string $track_number   运单号
     * @param string $transaction_id 交易号
     * @param        $email
     */
    public function getCustomerEmail(int $order_platform, $increment_id = '', $customer_name = [], $customer_phone = '', $track_number = '', $transaction_id = '', $customer_email)
    {
        switch ($order_platform) {
            case 1:
                $db = 'database.db_zeelool';
                $db_online = 'database.db_zeelool_online';
                break;
            case 2:
                $db = 'database.db_voogueme';
                $db_online = 'database.db_voogueme_online';
                break;
            case 3:
                $db = 'database.db_nihao';
                $db_online = '';
                break;
            case 4:
                $db = 'database.db_meeloog';
                $db_online = '';
                break;
            case 9:
                $db = 'database.db_zeelool_es';
                break;
            case 10:
                $db = 'database.db_zeelool_de';
                break;
            case 11:
                $db = 'database.db_zeelool_jp';
                break;
            default:
                return false;
                break;
        }
        $order = new NewOrder();
        $vip = new WebVipOrder();
        $user = new WebUsers();
        $orderItem = new NewOrderItemProcess();
        $itemPlatFormSku = new ItemPlatformSku();

        //根据订单号搜索
        if ($increment_id) {
            $customer_email = $order->where('site', $order_platform)->where('increment_id', $increment_id)->value('customer_email');
            //如果输入的是vip订单号
            if (!$customer_email && $order_platform < 3) {
                $customer_email = $vip->where('order_number', $increment_id)->value('customer_email');
            }
        }

        //根据客户姓名搜索
        if (!empty($customer_name)) {
            $customer_email = $order->where('site', $order_platform)->where('customer_firstname', $customer_name[0])
                ->where('customer_lastname', $customer_name[1])->value('customer_email');
        }

        //根据客户电话搜索
        if ($customer_phone) {
            $customer_email = $order->where('site', $order_platform)->where('telephone', $customer_phone)
                ->value('customer_email');
        }

        //根据物流单号搜索
        if ($track_number) {
            $customer_email = $order->alias('a')
                ->where('a.site', $order_platform)
                ->where('b.track_number', $track_number)
                ->join(['fa_order_process' => 'b'], 'a.id=b.order_id')
                ->value('a.customer_email');
        }

        //根据交易号搜索
        if ($transaction_id) {
            $customer_email = $order->where('site', $order_platform)->where('last_trans_id', $transaction_id)->value('customer_email');
        }

        if (empty($customer_email)) {
            return [];
        }

        //求出用户的等级
        $customer_group_code = $user->alias('a')->join(['fa_web_group' => 'b'], 'a.group_id=b.id')->where(['a.email' => $customer_email])->where('a.site', $order_platform)->where('b.site', 0)->value('b.customer_group_code');
        //如果是z站或者v站的话求出是否存在VIP订单
        if ($order_platform < 3) {
            $order_vip = $vip->where(['customer_email' => $customer_email])->field('web_id as id,customer_email,order_number,order_amount,order_status,order_type,start_time,end_time,is_active_status')->select();
        }
        $result = $order->alias('a')
            ->where(['b.is_repeat' => 0, 'b.is_split' => 0])
            ->where('a.site', $order_platform)
            ->where('a.customer_email', $customer_email)
            ->join(['fa_order_process' => 'b'], 'a.id=b.order_id')
            ->field('a.site,a.id,a.stock_id,a.shipping_title,a.entity_id,a.mw_rewardpoint,a.mw_rewardpoint_discount,a.status,a.coupon_code,a.coupon_rule_name,a.store_id,
                a.increment_id,a.customer_email,a.customer_firstname,a.customer_lastname,a.order_currency_code,a.total_item_count,a.grand_total,
                a.base_grand_total,a.base_shipping_amount,a.created_at,a.total_qty_ordered,a.order_type,a.payment_method,a.last_trans_id,b.track_number,b.agent_way_title,b.shipment_num')
            ->order('a.entity_id desc')
            ->select();

        if (!$result) {
            return [];
        }

        foreach ($result as $k => $v) {
            $item = $orderItem->alias('a')
                ->where(['a.order_id' => $v['id']])
                ->join(['fa_order_item_option' => 'b'], 'a.option_id=b.id')
                ->select();
            $item = collection($item)->toArray();

            foreach ($item as $key => $value) {
                //虚拟库存
                $item[$key]['stock'] = $itemPlatFormSku->where('platform_sku', $value['sku'])->where('platform_type', $order_platform)->value('stock');
            }

            $result[$k]['item'] = $item;
            if ($order_platform == 3) {
                //查询交易信息
                $paypalLog = Db::connect($db)
                    ->table('paypal_logs')
                    ->where(['order_no' => $v['increment_id']])
                    ->value('response');
                $paypalLog = $paypalLog ? json_decode($paypalLog, true) : [];
                $result[$k]['additional_information'] = [];
                $result[$k]['additional_information']['paypal_payer_email'] = $paypalLog['result']['purchase_units']['payee']['email_address'];
                $result[$k]['additional_information']['paypal_payer_id'] = $paypalLog['result']['id'];
                $result[$k]['additional_information']['paypal_payment_status'] = $paypalLog['result']['status'];
                $result[$k]['additional_information']['paypal_payer_status'] = $paypalLog['result']['status'];

            } else {
                //查询交易信息
                $result[$k]['additional_information'] = Db::connect($db)
                    ->table('sales_flat_order_payment')
                    ->where(['parent_id' => $v['entity_id']])
                    ->value('additional_information');
            }
            //订单地址表
            if ($order_platform == 3) {
                $address = Db::connect($db)->table('order_addresses')
                    ->where(['order_id' => $v['entity_id']])
                    ->field('type as address_type,telephone,postcode,street,city,region,country_id,firstname,lastname')
                    ->select();
            } else {
                $address = Db::connect($db)->table('sales_flat_order_address')
                    ->where(['parent_id' => $v['entity_id']])
                    ->field('address_type,telephone,postcode,street,city,region,country_id,firstname,lastname')
                    ->select();
            }

            $result[$k]['address'] = $address;
            $result[$k]['created_at'] = date('Y-m-d H:i:s', $v['created_at']);

            //工单列表
            $workOrderListResult = WorkOrderList::workOrderListInfo($v['increment_id']);

            //补差价列表
            if ($order_platform == 3) {
                $differencePriceList = Db::connect($db)
                    ->table('attach_orders')
                    ->field('order_no as order_number,status as order_status,actual_payment as order_amount,currency as order_currency,created_at as start_time')
                    ->where(['order_id' => $v['entity_id']])
                    ->select();
            } else {
                $differencePriceList = Db::connect($db)->table('oc_difference_price_order')->where(['origin_order_number' => $v['increment_id']])->select();
            }

            $result[$k]['workOrderList'] = $workOrderListResult['list'];
            $result[$k]['differencePriceList'] = $differencePriceList;
            $result[$k]['order_type_id'] = $v['order_type'];
            switch ($v['order_type']) {
                case 2:
                    $result[$k]['order_type'] = '<span style="color:#f39c12">批发</span>';
                    break;
                case 3:
                    $result[$k]['order_type'] = '<span style="color:#18bc9c">网红</span>';
                    break;
                case 4:
                    $result[$k]['order_type'] = '<span style="color:#e74c3c">补发</span>';
                    break;
                default:
                    $result[$k]['order_type'] = '<span style="color:#0073b7">普通</span>';
                    break;
            }

        }
        //用户的等级
        if ($customer_group_code) {
            $customer['customer_group_code'] = $customer_group_code;
        } else {
            $customer['customer_group_code'] = '';
        }

        //用户的vip订单
        if ($order_vip) {
            //把vip订单查询出来放到数组当中
            $arr_order_vip = [];
            foreach ($order_vip as $v) {
                $arr_order_vip[] = $v['order_number'];
            }
            $customer['order_vip'] = $order_vip;
            $customer['arr_order_vip'] = $arr_order_vip;
        } else {
            $customer['order_vip'] = '';
            $customer['arr_order_vip'] = '';
        }

        $customer['customer_email'] = $customer_email;
        $customer['customer_name'] = $result[0]['customer_firstname'] . ' ' . $result[0]['customer_lastname'];
        $customer['success_counter'] = $customer['success_total'] = $customer['failed_counter'] = $customer['failed_total'] = 0;
        $orderStatus = ['complete', 'processing', 'delivered'];
        foreach ($result as $key => $val) {
            //计算支付成功和失败次数
            if (in_array($val['status'], $orderStatus) && $val['order_type_id'] != 4) {
                $customer['success_counter']++;
                $customer['success_total'] += $val['base_grand_total'];
            } elseif ($val['order_type_id'] != 4) {
                $customer['failed_counter']++;
                $customer['failed_total'] += $val['base_grand_total'];
            }

            //求出所有的订单号
            $result['increment_id'][] = $val['increment_id'];
            if ($order_platform != 3) {
                $result[$key]['additional_information'] = unserialize($val['additional_information']);
                $result[$key]['additional_information']['paypal_payer_email'] = isset($result[$key]['additional_information']['paypal_payer_email']) ? $result[$key]['additional_information']['paypal_payer_email'] : '';
                $result[$key]['additional_information']['paypal_payer_id'] = isset($result[$key]['additional_information']['paypal_payer_id']) ? $result[$key]['additional_information']['paypal_payer_id'] : '';
                $result[$key]['additional_information']['paypal_payer_status'] = isset($result[$key]['additional_information']['paypal_payer_status']) ? $result[$key]['additional_information']['paypal_payer_status'] : '';
                $result[$key]['additional_information']['paypal_payment_status'] = isset($result[$key]['additional_information']['paypal_payment_status']) ? $result[$key]['additional_information']['paypal_payment_status'] : '';
            }
            //求出订单下的商品信息
            $result[$key]['arr'] = [];
        }
        $result['info'] = $customer;

        return $result;
    }


    /***
     *检查订单是否重复
     */
    public function checkOrderInfo($order_number, $problem_id)
    {
        $where['order_number'] = $order_number;
        $where['problem_id'] = $problem_id;
        $where['task_status'] = ['in', [0, 1]];
        $result = $this->where($where)->field('id,order_number')->find();

        return $result ? $result : false;
    }

    /**
     * 获取未处理售后事件数量
     *
     * @Description
     * @return void
     * @since  2020/03/02 14:27:30
     * @author wpl
     */
    public function getTaskNum()
    {
        $map['is_del'] = 1;
        $map['task_status'] = 0;

        return $this->where($map)->count(1);
    }
}
