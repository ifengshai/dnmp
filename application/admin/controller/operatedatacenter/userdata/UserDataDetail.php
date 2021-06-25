<?php

namespace app\admin\controller\operatedatacenter\userdata;

use app\admin\model\platformManage\MagentoPlatform;
use app\common\controller\Backend;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\Controller;
use think\Db;
use think\Request;

class UserDataDetail extends Backend
{
    protected $noNeedRight = ['*'];
    public function _initialize()
    {
        parent::_initialize();
        $this->zeelool = new \app\admin\model\order\order\Zeelool();
        $this->voogueme = new \app\admin\model\order\order\Voogueme();
        $this->nihao = new \app\admin\model\order\order\Nihao();
        $this->zeeloolde = new \app\admin\model\order\order\ZeeloolDe();
        $this->zeelooljp = new \app\admin\model\order\order\ZeeloolJp();
        $this->zeeloolfr = new \app\admin\model\order\order\ZeeloolFr();
        $this->weseeoptical= new \app\admin\model\order\order\Weseeoptical();
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
    }
    /**
     * 订单数据明细页面展示
     *
     * @return \think\Response
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $filter = json_decode($this->request->get('filter'), true);
            if ($filter['order_platform'] == 2) {
                $order_model = $this->voogueme;
                $web_model = Db::connect('database.db_voogueme');
                $site = 2;
            } elseif ($filter['order_platform'] == 3) {
                $order_model = $this->nihao;
                $web_model = Db::connect('database.db_nihao');
                $site = 3;
            } elseif ($filter['order_platform'] == 10) {
                $order_model = $this->zeeloolde;
                $web_model = Db::connect('database.db_zeelool_de');
                $site = 10;
            } elseif ($filter['order_platform'] == 11) {
                $order_model = $this->zeelooljp;
                $web_model = Db::connect('database.db_zeelool_jp');
                $site = 11;
            } elseif ($filter['order_platform'] == 15) {
                $order_model = $this->zeeloolfr;
                $web_model = Db::connect('database.db_zeelool_fr');
                $site = 15;
            } elseif ($filter['order_platform'] == 5) {
                $web_model = Db::connect('database.db_weseeoptical');
                $site = 5;
            } else {
                $order_model = $this->zeelool;
                $web_model = Db::connect('database.db_zeelool');
                $site = 1;
            }

            //批发站数据 处理
            if ($site==5){

                if ($filter['customer_type']) {
                    $map['c.group_id'] = $filter['customer_type'];
                }
                if ($filter['time_str']) {
                    $createat = explode(' ', $filter['time_str']);
                    $map['o.created_at'] = ['between', [$createat[0].' '.$createat[1], $createat[3].' '.$createat[4]]];
                } else {
                    $start = date('Y-m-d', strtotime('-6 day'));
                    $end = date('Y-m-d 23:59:59');
                    $map['o.created_at'] = ['between', [$start, $end]];
                }
                $map['o.order_status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered','delivery']];
                unset($filter['one_time-operate']);
                unset($filter['time_str']);
                unset($filter['order_platform']);
                unset($filter['customer_type']);
                $this->request->get(['filter' => json_encode($filter)]);
                [$where, $sort, $order, $offset, $limit] = $this->buildparams();
                $total = $web_model
                    ->table('orders')
                    ->alias('o')
                    ->join('users c', 'o.user_id=c.id')
                    ->where($where)
                    ->where($map)
                    ->group('o.user_id')
                    ->count();

                $list = $web_model
                    ->table('orders')
                    ->alias('o')
                    ->join('users c', 'o.user_id=c.id')
                    ->where($where)
                    ->where($map)
                    ->order("user_id", $order)
                    ->limit($offset, $limit)
                    ->field('c.id,c.created_at,c.email')
                    ->group('c.id')
                    ->select();
                $list = collection($list)->toArray();
                foreach ($list as $key=>$value){
                    $list[$key]['entity_id'] = $value['id'];  //用户id
                    $list[$key]['email'] = $value['email'];          //注册邮箱
                    $list[$key]['created_at'] = $value['created_at'];  //注册时间
                    $order_where['user_id'] = $value['id'];
                    $order_status_where['order_status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered','delivery']];
                    $order = $web_model->table('orders')->where($order_where)->where($order_status_where)->field('count(*) count,sum(base_actual_amount_paid) total')->select();
                    $list[$key]['order_num'] = $order[0]['count'];  //总支付订单数
                    $list[$key]['order_amount'] = $order[0]['total'];//总订单金额
                    $list[$key]['point'] = 0;  //积分
                    $recommend_order_num = 0;   //推荐订单数
                    $recommend_register_num = 0;   //推荐注册量

                    $order_coupon = $web_model->table('orders')->where($order_where)->where($order_status_where)->where("discount_coupon_id >0 ")->field('count(*) count,sum(base_coupon_discounts_price) total')->select();
                    $list[$key]['coupon_order_num'] = $order_coupon[0]['count'];//使用优惠券订单数
                    $list[$key]['coupon_order_amount'] = $order_coupon[0]['total'];//使用优惠券订单金额
                    $list[$key]['first_order_time'] = $web_model->table('orders')->where($order_where)->where($order_status_where)->order('created_at asc')->value('created_at');//首次下单时间
                    $list[$key]['last_order_time'] = $web_model->table('orders')->where($order_where)->where($order_status_where)->order('created_at desc')->value('created_at');//最后一次下单时间
                    $list[$key]['recommend_order_num'] = $recommend_order_num;   //推荐订单数
                    $list[$key]['recommend_register_num'] = $recommend_register_num;   //推荐注册量
                }
                $result = array("total" => $total, "rows" => $list);

                return json($result);
            }

            if ($filter['customer_type']) {
                $map['c.group_id'] = $filter['customer_type'];
            }
            if ($filter['time_str']) {
                $createat = explode(' ', $filter['time_str']);
                $map['o.created_at'] = ['between', [$createat[0].' '.$createat[1], $createat[3].' '.$createat[4]]];
            } else {
                $start = date('Y-m-d', strtotime('-6 day'));
                $end = date('Y-m-d 23:59:59');
                $map['o.created_at'] = ['between', [$start, $end]];
            }
            $map['o.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered','delivery']];
            $map['o.customer_id'] = ['>',0];
            $web_model->table('sales_flat_order')->query("set time_zone='+8:00'");
            unset($filter['one_time-operate']);
            unset($filter['time_str']);
            unset($filter['order_platform']);
            unset($filter['customer_type']);
            $this->request->get(['filter' => json_encode($filter)]);
            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            $total = $web_model
                ->table('sales_flat_order')
                ->alias('o')
                ->join('customer_entity c', 'o.customer_id=c.entity_id')
                ->where($where)
                ->where($map)
                ->group('c.entity_id')
                ->count();

            $list = $web_model
                ->table('sales_flat_order')
                ->alias('o')
                ->join('customer_entity c','o.customer_id=c.entity_id')
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->field('c.entity_id,c.created_at,c.email')
                ->group('c.entity_id')
                ->select();
            $list = collection($list)->toArray();
            foreach ($list as $key=>$value){
                $list[$key]['entity_id'] = $value['entity_id'];  //用户id
                $list[$key]['email'] = $value['email'];          //注册邮箱
                $list[$key]['created_at'] = $value['created_at'];  //注册时间
                $order_where['customer_id'] = $value['entity_id'];
                $order_status_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered','delivery']];
                $order = $order_model->where($order_where)->where($order_status_where)->field('count(*) count,sum(base_grand_total) total')->select();
                $list[$key]['order_num'] = $order[0]['count'];  //总支付订单数
                $list[$key]['order_amount'] = $order[0]['total'];//总订单金额
                if($site != 3){
                    $list[$key]['point'] = $web_model->table('mw_reward_point_customer')->where('customer_id',$value['entity_id'])->value('mw_reward_point');  //积分
                    $recommend_userids = $web_model->table('mw_reward_point_customer')->where('mw_friend_id',$value['entity_id'])->count();
                    if($recommend_userids){
                        $sql1 = $web_model->table('mw_reward_point_customer')->where('mw_friend_id',$value['entity_id'])->field('customer_id')->buildSql();
                        $arr_where = [];
                        $arr_where[] = ['exp', Db::raw("customer_id in " . $sql1)];
                        $recommend_order_num = $order_model->where($order_status_where)->where($arr_where)->count();   //推荐订单数
                    }else{
                        $recommend_order_num = 0;
                    }
                    $recommend_register_num = $recommend_userids;   //推荐注册量
                }else{
                    $list[$key]['point'] = 0;  //积分
                    $recommend_order_num = 0;   //推荐订单数
                    $recommend_register_num = 0;   //推荐注册量
                }
                $order_coupon = $order_model->where($order_where)->where($order_status_where)->where("coupon_code is not null")->field('count(*) count,sum(base_grand_total) total')->select();
                $list[$key]['coupon_order_num'] = $order_coupon[0]['count'];//使用优惠券订单数
                $list[$key]['coupon_order_amount'] = $order_coupon[0]['total'];//使用优惠券订单金额
                $list[$key]['first_order_time'] = $order_model->where($order_where)->where($order_status_where)->order('created_at asc')->value('created_at');//首次下单时间
                $list[$key]['last_order_time'] = $order_model->where($order_where)->where($order_status_where)->order('created_at desc')->value('created_at');//最后一次下单时间
                $list[$key]['recommend_order_num'] = $recommend_order_num;   //推荐订单数
                $list[$key]['recommend_register_num'] = $recommend_register_num;   //推荐注册量
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key=>$val){
            if (!in_array($val['name'], ['zeelool', 'voogueme', 'meeloog', 'zeelool_de', 'zeelool_jp','wesee','zeelool_fr'])) {
                unset($magentoplatformarr[$key]);
            }
        }
        $this->view->assign('magentoplatformarr',$magentoplatformarr);
        return $this->view->fetch();
    }
    function filter_by_value ($array, $index, $value){
        if(is_array($array) && count($array)>0)
        {
            foreach(array_keys($array) as $key){
                $temp[$key] = $array[$key][$index];
                if ($temp[$key] == $value){
                    $newarray = $array[$key];
                }
            }
        }
        return $newarray;
    }

    public function export()
    {
        set_time_limit(0);
        header("Content-type:application/vnd.ms-excel");
        header("Content-Disposition:filename=".iconv("UTF-8", "GB18030", date('Y-m-d-His', time())).".csv");//导出文件名
        // 打开PHP文件句柄，php://output 表示直接输出到浏览器
        $fp = fopen('php://output', 'a');
        $order_platform = input('order_platform');
        $time_str = input('time_str');
        $customer_type = input('customer_type');
        $field = input('field');
        $field_arr = explode(',', $field);
        $field_info = [
            [
                'name'  => '用户ID',
                'field' => 'entity_id',
            ],
            [
                'name'  => '注册邮箱',
                'field' => 'email',
            ],
            [
                'name'  => '注册时间',
                'field' => 'created_at',
            ],
            [
                'name'  => '总支付订单数',
                'field' => 'order_num',
            ],
            [
                'name'  => '总订单金额',
                'field' => 'order_amount',
            ],
            [
                'name'  => '积分余额',
                'field' => 'point',
            ],
            [
                'name'  => '使用优惠券订单数',
                'field' => 'coupon_order_num',
            ],
            [
                'name'  => '使用优惠券订单金额',
                'field' => 'coupon_order_amount',
            ],
            [
                'name'  => '首次下单时间',
                'field' => 'first_order_time',
            ],
            [
                'name'  => '最后一次下单时间',
                'field' => 'last_order_time',
            ],
            [
                'name'  => '推荐订单数',
                'field' => 'recommend_order_num',
            ],
            [
                'name'  => '推荐注册量',
                'field' => 'recommend_register_num',
            ],
        ];
        $column_name = [];
        // 将中文标题转换编码，否则乱码
        foreach ($field_arr as $i => $v) {
            $title_name = $this->filter_by_value($field_info, 'field', $v);
            $field_arr[$i] = iconv('utf-8', 'GB18030', $title_name['name']);
            $column_name[$i] = $v;
        }
        // 将标题名称通过fputcsv写到文件句柄
        fputcsv($fp, $field_arr);

        if ($order_platform == 2) {
            $order_model = $this->voogueme;
            $web_model = Db::connect('database.db_voogueme');
            $site = 2;
        } elseif ($order_platform == 3) {
            $order_model = $this->nihao;
            $web_model = Db::connect('database.db_nihao');
            $site = 3;
        } elseif ($order_platform == 10) {
            $order_model = $this->zeeloolde;
            $web_model = Db::connect('database.db_zeelool_de');
            $site = 10;
        } elseif ($order_platform == 11) {
            $order_model = $this->zeelooljp;
            $web_model = Db::connect('database.db_zeelool_jp');
            $site = 11;
        } elseif ($order_platform == 15) {
            $order_model = $this->zeeloolfr;
            $web_model = Db::connect('database.db_zeelool_fr');
            $site = 15;
        } elseif ($order_platform == 5) {
            $web_model = Db::connect('database.db_weseeoptical');
            $site = 5;
        } else {
            $order_model = $this->zeelool;
            $web_model = Db::connect('database.db_zeelool');
            $site = 1;
        }
        if ($site == 5) {

            if ($time_str) {
                $createat = explode(' ', $time_str);
                $map['o.created_at'] = ['between', [$createat[0].' '.$createat[1], $createat[3].' '.$createat[4]]];
            } else {
                $start = date('Y-m-d', strtotime('-6 day'));
                $end = date('Y-m-d 23:59:59');
                $map['o.created_at'] = ['between', [$start, $end]];
            }
            if ($customer_type) {
                $map['c.group_id'] = $customer_type;
            }
            $map['o.order_status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered','delivery']];
            $total_export_count = $web_model
                ->table('orders')
                ->alias('o')
                ->join('users c', 'o.user_id=c.id')
                ->where($map)
                ->group('o.user_id')
                ->count();
            $pre_count = 5000;
            for ($i = 0; $i < intval($total_export_count / $pre_count) + 1; $i++) {
                $start = $i * $pre_count;
                //切割每份数据
                $list = $web_model
                    ->table('orders')
                    ->alias('o')
                    ->join('users c', 'o.user_id=c.id')
                    ->where($map)
                    ->field('c.id,c.created_at,c.email')
                    ->group('c.id')
                    ->limit($start, $pre_count)
                    ->order('o.user_id  desc')
                    ->select();
                $list = collection($list)->toArray();
                //整理数据
                foreach ($list as &$val) {
                    $tmpRow = [];
                    $entity_id_index = array_keys($column_name, 'entity_id');
                    $tmpRow[$entity_id_index[0]] = $val['id'];
                    $email_index = array_keys($column_name, 'email');
                    $tmpRow[$email_index[0]] = $val['email'];
                    $created_at_index = array_keys($column_name, 'created_at');
                    $tmpRow[$created_at_index[0]] = $val['created_at'];
                    $order_where['user_id'] = $val['id'];
                    $order_status_where['order_status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered','delivery']];
                    $orderInfoArr=array();
                    if (in_array('order_num', $column_name)||in_array('order_amount', $column_name)){
                        $orderInfoArr = $web_model->table('orders')->where($order_where)->where($order_status_where)->field('count(*) count,sum(base_actual_amount_paid) total')->select();
                    }

                    if (in_array('order_num', $column_name)) {
                        //总支付订单数
                        $index = array_keys($column_name, 'order_num');
                        $tmpRow[$index[0]] = $orderInfoArr[0]['count'];
                    }
                    if (in_array('order_amount', $column_name)) {
                        $index = array_keys($column_name, 'order_amount');
                        $tmpRow[$index[0]] = $orderInfoArr[0]['total'];//总订单金额
                    }
                    if (in_array('point', $column_name)) {
                        $index = array_keys($column_name, 'point');
                        $tmpRow[$index[0]] = 0; //积分
                    }

                    $order_coupon=array();
                    if (in_array('coupon_order_num', $column_name)||in_array('coupon_order_amount', $column_name)){
                        $order_coupon = $web_model->table('orders')->where($order_where)->where($order_status_where)->where("discount_coupon_id >0 ")->field('count(*) count,sum(base_coupon_discounts_price) total')->select();
                    }
                    if (in_array('coupon_order_num', $column_name)) {
                        $index = array_keys($column_name, 'coupon_order_num');
                        $tmpRow[$index[0]] =$order_coupon[0]['count'];//使用优惠券订单数
                    }
                    if (in_array('coupon_order_amount', $column_name)) {
                        $index = array_keys($column_name, 'coupon_order_amount');
                        $tmpRow[$index[0]] = $order_coupon[0]['total'];//使用优惠券订单金额
                    }
                    if (in_array('first_order_time', $column_name)) {
                        $index = array_keys($column_name, 'first_order_time');
                        $tmpRow[$index[0]] = $web_model->table('orders')->where($order_where)->where($order_status_where)->order('created_at asc')->value('created_at');//首次下单时间
                    }
                    if (in_array('last_order_time', $column_name)) {
                        $index = array_keys($column_name, 'last_order_time');
                        $tmpRow[$index[0]] = $web_model->table('orders')->where($order_where)->where($order_status_where)->order('created_at desc')->value('created_at');//最后一次下单时间
                    }
                    if (in_array('recommend_order_num', $column_name)) {
                        $index = array_keys($column_name, 'recommend_order_num');
                        $tmpRow[$index[0]] = 0;   //推荐订单数
                    }
                    if (in_array('recommend_register_num', $column_name)) {
                        $index = array_keys($column_name, 'recommend_register_num');
                        $tmpRow[$index[0]] = 0;   //推荐注册量
                    }
                    ksort($tmpRow);
                    $rows = [];
                    foreach ($tmpRow as $export_obj) {
                        $rows[] = iconv('utf-8', 'GB18030', $export_obj);
                    }
                    fputcsv($fp, $rows);
                }

                // 将已经写到csv中的数据存储变量销毁，释放内存占用
                unset($list);
                ob_flush();
                flush();
            }
        } else {
            $web_model->table('sales_flat_order')->query("set time_zone='+8:00'");
            if ($time_str) {
                $createat = explode(' ', $time_str);
                $map['o.created_at'] = ['between', [$createat[0].' '.$createat[1], $createat[3].' '.$createat[4]]];
            } else {
                $start = date('Y-m-d', strtotime('-6 day'));
                $end = date('Y-m-d 23:59:59');
                $map['o.created_at'] = ['between', [$start, $end]];
            }
            if ($customer_type) {
                $map['c.group_id'] = $customer_type;
            }
            $map['o.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered','delivery']];
            $map['o.customer_id'] = ['>', 0];
            $total_export_count = $web_model
                ->table('sales_flat_order')
                ->alias('o')
                ->join('customer_entity c', 'o.customer_id=c.entity_id')
                ->where($map)
                ->group('c.entity_id')
                ->count();
            $pre_count = 5000;
            for ($i = 0; $i < intval($total_export_count / $pre_count) + 1; $i++) {
                $start = $i * $pre_count;
                //切割每份数据
                $list = $web_model
                    ->table('sales_flat_order')
                    ->alias('o')
                    ->join('customer_entity c', 'o.customer_id=c.entity_id')
                    ->where($map)
                    ->field('c.entity_id,c.created_at,c.email')
                    ->group('c.entity_id')
                    ->limit($start, $pre_count)
                    ->order('entity_id desc')
                    ->select();
                $list = collection($list)->toArray();
                //整理数据
                foreach ($list as &$val) {
                    $tmpRow = [];
                    $entity_id_index = array_keys($column_name, 'entity_id');
                    $tmpRow[$entity_id_index[0]] = $val['entity_id'];
                    $email_index = array_keys($column_name, 'email');
                    $tmpRow[$email_index[0]] = $val['email'];
                    $created_at_index = array_keys($column_name, 'created_at');
                    $tmpRow[$created_at_index[0]] = $val['created_at'];
                    $order_where['customer_id'] = $val['entity_id'];
                    $order_status_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered','delivery']];
                    if (in_array('order_num', $column_name)) {
                        //总支付订单数
                        $index = array_keys($column_name, 'order_num');
                        $tmpRow[$index[0]] = $order_model->where($order_where)->where($order_status_where)->count();
                    }
                    if (in_array('order_amount', $column_name)) {
                        $index = array_keys($column_name, 'order_amount');
                        $tmpRow[$index[0]] = $order_model->where($order_where)->where($order_status_where)->sum('base_grand_total');//总订单金额
                    }
                    if (in_array('point', $column_name)) {
                        $index = array_keys($column_name, 'point');
                        if ($site != 3) {
                            $tmpRow[$index[0]] = $web_model->table('mw_reward_point_customer')->where('customer_id', $val['entity_id'])->value('mw_reward_point');  //积分
                            $tmpRow[$index[0]] = $tmpRow[$index[0]] ? $tmpRow[$index[0]] : 0;
                        } else {
                            $tmpRow[$index[0]] = 0; //积分
                        }
                    }
                    if (in_array('coupon_order_num', $column_name)) {
                        $index = array_keys($column_name, 'coupon_order_num');
                        $tmpRow[$index[0]] = $order_model->where($order_where)->where($order_status_where)->where("coupon_code is not null")->count();//使用优惠券订单数
                    }
                    if (in_array('coupon_order_amount', $column_name)) {
                        $index = array_keys($column_name, 'coupon_order_amount');
                        $tmpRow[$index[0]] = $order_model->where($order_where)->where($order_status_where)->where("coupon_code is not null")->sum('base_grand_total');//使用优惠券订单金额
                    }
                    if (in_array('first_order_time', $column_name)) {
                        $index = array_keys($column_name, 'first_order_time');
                        $tmpRow[$index[0]] = $order_model->where($order_where)->where($order_status_where)->order('created_at asc')->value('created_at');//首次下单时间
                    }
                    if (in_array('last_order_time', $column_name)) {
                        $index = array_keys($column_name, 'last_order_time');
                        $tmpRow[$index[0]] = $order_model->where($order_where)->where($order_status_where)->order('created_at desc')->value('created_at');//最后一次下单时间
                    }
                    if (in_array('recommend_order_num', $column_name)) {
                        $index = array_keys($column_name, 'recommend_order_num');
                        if ($site != 3) {
                            $sql1 = $web_model->table('mw_reward_point_customer')->where('mw_friend_id', $val['entity_id'])->field('customer_id')->buildSql();
                            $arr_where = [];
                            $arr_where[] = ['exp', Db::raw("customer_id in ".$sql1)];
                            $tmpRow[$index[0]] = $order_model->where($order_status_where)->where($arr_where)->count();   //推荐订单数
                        } else {
                            $tmpRow[$index[0]] = 0;   //推荐订单数
                        }
                    }
                    if (in_array('recommend_register_num', $column_name)) {
                        $index = array_keys($column_name, 'recommend_register_num');
                        if ($site != 3) {
                            $tmpRow[$index[0]] = $web_model->table('mw_reward_point_customer')->where('mw_friend_id', $val['entity_id'])->count();   //推荐注册量
                        } else {
                            $tmpRow[$index[0]] = 0;   //推荐注册量
                        }
                    }
                    ksort($tmpRow);
                    $rows = [];
                    foreach ($tmpRow as $export_obj) {
                        $rows[] = iconv('utf-8', 'GB18030', $export_obj);
                    }
                    fputcsv($fp, $rows);
                }

                // 将已经写到csv中的数据存储变量销毁，释放内存占用
                unset($list);
                ob_flush();
                flush();
            }
        }
        fclose($fp);
    }
}
