<?php

namespace app\admin\controller\operatedatacenter\goodsdata;

use app\admin\model\itemmanage\ItemPlatformSku;
use app\common\controller\Backend;
use think\Controller;
use think\Db;
use think\Request;

class SingleItems extends Backend
{
    /**
     * 单品查询
     *
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            $filter = json_decode($this->request->get('filter'), true);
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            if ($filter['create_time-operate']) {
                unset($filter['create_time-operate']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            if ($filter['time_str']) {
                $createat = explode(' ', $filter['time_str']);
                $map['p.created_at'] = ['between', [$createat[0], $createat[3] . ' 23:59:59']];
                unset($filter['time_str']);
                $this->request->get(['filter' => json_encode($filter)]);
            } else {
                if (isset($filter['time_str'])) {
                    unset($filter['time_str']);
                    $this->request->get(['filter' => json_encode($filter)]);
                }
                $start = date('Y-m-d', strtotime('-6 day'));
                $end = date('Y-m-d 23:59:59');
                $map['p.created_at'] = ['between', [$start, $end]];
            }

            if ($filter['sku']) {
                $map['p.sku'] = $filter['sku'];
                // $mapss['sku'] = ['like',$filter['sku'].'%'];
                $sku = $filter['sku'];
                unset($filter['sku']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            if ($filter['order_platform']) {
                $site = $filter['order_platform'];
                unset($filter['order_platform']);
                $this->request->get(['filter' => json_encode($filter)]);
            } else {
                $site = 1;
            }
            $field = 'p.id,o.increment_id,o.created_at,o.customer_email,p.prescription_type,p.coatiing_name,p.frame_price,p.index_price';
            if ($site == 2) {
                $order_model = Db::connect('database.db_voogueme');

            } elseif ($site == 3) {
                $order_model = Db::connect('database.db_nihao');
                $field = 'p.id,o.increment_id,o.created_at,o.customer_email,p.prescription_type,p.frame_price,p.index_price';
            } else {
                $order_model = Db::connect('database.db_zeelool');
            }
            $order_model->table('sales_flat_order_item_prescription')->query("set time_zone='+8:00'");
            $map['o.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
            $map['o.order_type'] = 1;
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $order_model->table('sales_flat_order_item_prescription')
                ->alias('p')
                ->join('sales_flat_order o', 'p.order_id=o.entity_id')
                ->field($field)
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();

            $list = $order_model->table('sales_flat_order_item_prescription')
                ->alias('p')
                ->join('sales_flat_order o', 'p.order_id=o.entity_id')
                ->field($field)
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $total = $order_model
                ->table('sales_flat_order')
                ->where($map)
                ->alias('o')
                ->join(['sales_flat_order_item' => 'p'], 'o.entity_id=p.order_id')
                ->group('o.entity_id')
                // ->order($order)
                ->count();
            $list = $order_model
                ->table('sales_flat_order')
                ->where($map)
                ->alias('o')
                ->join(['sales_flat_order_item' => 'p'], 'o.entity_id=p.order_id')
                ->group('o.entity_id')
                // ->order($order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);
            //关联购买
            $connect_buy = $order_model->table('sales_flat_order_item')
                // ->where('sku', 'like', $sku . '%')
                ->where('sku',$sku)
                ->where('created_at', 'between', [$createat[0], $createat[3]])
                ->distinct('order_id')
                ->field('order_id')
                ->select();//包含此sku的所有订单好
            $connect_buy = array_column($connect_buy, 'order_id');
            $skus = array();
            foreach ($connect_buy as $value) {
                $arr = $order_model->table('sales_flat_order_item')
                    ->where('order_id', $value)
                    // ->where('created_at','between', [$createat[0], $createat[3]])
                    ->field('sku')
                    ->select();//这些订单号内的所有sku
                $skus[] = array_column($arr, 'sku');
            }
            $array_sku = [];
            //获取关联购买的数量
            foreach ($skus as $k => $v) {
                foreach ($v as $vv) {
                    if ($vv != $sku) {
                        $array_sku[$vv] += 1;
                    }
                }
            }
            $this->assign('array_sku', $array_sku);

            return json($result);
        }
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key => $val) {
            if (!in_array($val['name'], ['zeelool', 'voogueme', 'nihao'])) {
                unset($magentoplatformarr[$key]);
            }
        }
        $this->view->assign('magentoplatformarr', $magentoplatformarr);
        return $this->view->fetch();
    }

    public function ajax_top_data()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            // dump($params);
            //站点
            $order_platform = $params['order_platform'] ? $params['order_platform'] : 1;
            //时间
            $time_str = $params['time_str'] ? $params['time_str'] : '';
            $createat = explode(' ', $time_str);
            $same_where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $same_where['site'] = ['=', $order_platform];
            $sku = input('sku');
            // dump($sku);
            $item_platform = new ItemPlatformSku();
            $sku = $item_platform->where('sku', $sku)->where('platform_sku', $order_platform)->value('platform_sku') ? $item_platform->where('sku', $sku)->where('platform_sku', $order_platform)->value('platform_sku') : $sku;
            // dump($sku);
            // $sku = 'FP08';
            switch ($order_platform) {
                case 1:
                    $order_model = $this->zeelool;
                    $model = Db::connect('database.db_zeelool');
                    break;
                case 2:
                    $order_model = $this->voogueme;
                    $model = Db::connect('database.db_voogueme');
                    break;
                case 3:
                    $order_model = $this->nihao;
                    $model = Db::connect('database.db_nihao');
                    break;
            }
            $model->table('sales_flat_order')->query("set time_zone='+8:00'");
            $model->table('sales_flat_order_item')->query("set time_zone='+8:00'");
            $model->table('sales_flat_order_item_prescription')->query("set time_zone='+8:00'");
            //此sku的总订单量
            $map['sku'] = ['like', $sku . '%'];
            $map['a.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
            $map['a.created_at'] = ['between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]]];
            $map['a.order_type'] = ['=', 1];
            $total = $model->table('sales_flat_order')
                ->where($map)
                ->alias('a')
                ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
                ->group('order_id')
                ->field('entity_id,sku,a.created_at,a.order_type,a.status')
                ->count();

            //整站订单量
            $whole_platform_order_num = Db::name('datacenter_day')->where($same_where)->value('order_num');

            //订单占比
            $order_rate = $whole_platform_order_num == 0 ? 0 : round($total / $whole_platform_order_num * 100, 2) . '%';

            //平均订单副数
            $whole_glass = $model
                ->table('sales_flat_order_item')
                ->where('sku', 'like', $sku . '%')
                ->where('created_at', 'between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]])
                ->sum('qty_ordered');//sku总副数
            $avg_order_glass = $total == 0 ? 0 : round($whole_glass / $total, 0);

            if ($order_platform != 3) {
                //付费镜片订单数
                $nopay_jingpian_glass = $model
                    ->table('sales_flat_order')
                    ->alias('a')
                    ->join(['sales_flat_order_item_prescription' => 'b'], 'a.entity_id=b.order_id')
                    ->where('a.created_at', 'between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]])
                    ->where('sku', 'like', $sku . '%')
                    ->where('b.coatiing_price', '=', 0)
                    ->where('b.index_price', '=', 0)
                    ->group('order_id')
                    ->count();
            } else {
                //付费镜片订单数
                $nopay_jingpian_glass = $model
                    ->table('sales_flat_order')
                    ->alias('a')
                    ->join(['sales_flat_order_item_prescription' => 'b'], 'a.entity_id=b.order_id')
                    ->where('a.created_at', 'between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]])
                    ->where('sku', 'like', $sku . '%')
                    ->where('b.coatiing_price', '=', 0)
                    ->where('b.index_price', '=', 0)
                    ->group('order_id')
                    ->count();
            }
            $pay_jingpian_glass = $total - $nopay_jingpian_glass;

            //付费镜片订单数占比
            $pay_jingpian_glass_rate = $total == 0 ? 0 : round($pay_jingpian_glass / $total * 100, 2) . '%';

            //只买一副的订单
            $only_one_glass_order_list = $model
                ->table('sales_flat_order_item')
                ->where('sku', 'like', $sku . '%')
                ->where('b.created_at', 'between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]])
                ->alias('a')
                ->join(['sales_flat_order' => 'b'], 'a.order_id=b.entity_id')
                ->field('order_id,sum(qty_ordered) as all_qty_ordered')
                ->group('a.order_id')
                ->select();
            $only_one_glass_num = 0;
            foreach ($only_one_glass_order_list as $k=>$v) {
                $one = $model->table('sales_flat_order_item')->where('order_id',$v['order_id'])->sum('qty_ordered');
                if ($one == 1){
                    $only_one_glass_num += 1;
                }
            }
            //只买一副的订单占比
            $only_one_glass_rate = $total == 0 ? 0 : round($only_one_glass_num / $total * 100, 2) . '%';

            //订单总金额
            $whole_price = $model
                ->table('sales_flat_order')
                ->where($map)
                ->where('a.created_at', 'between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]])
                ->alias('a')
                ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
                ->field('base_grand_total')
                ->sum('base_grand_total');
            // ->select();
            // $whole_price = round(array_sum(array_map(function($val){return $val['base_grand_total'];}, $whole_price)),2);

            //订单客单价
            $every_price = $total == 0 ? 0 : round($whole_price / $total, 2);
            // //关联购买
            $andWhere = "FIND_IN_SET({$sku},sku)";
            $connect_buy = $model->table('sales_flat_order_item')
                ->where('sku', 'like', $sku . '%')
                ->where('created_at', 'between', [$createat[0], $createat[3]])
                ->distinct('order_id')
                ->field('order_id')
                ->select();//包含此sku的所有订单好
            // dump($connect_buy);
            $connect_buy = array_column($connect_buy, 'order_id');
            // dump($connect_buy);
            $skus = array();
            foreach ($connect_buy as $value) {
                $arr = $model->table('sales_flat_order_item')
                    ->where('order_id', $value)
                    // ->where('created_at','between', [$createat[0], $createat[3]])
                    ->field('sku')
                    ->select();//这些订单号内的所有sku
                $skus[] = array_column($arr, 'sku');
            }
            // dump($skus);
            $array_sku = [];
            //获取关联购买的数量
            foreach ($skus as $k => $v) {
                foreach ($v as $vv) {
                    if ($vv != $sku) {
                        $array_sku[$vv] += 1;
                    }
                }
            }
            // dump($array_sku);
            $data = compact('sku', 'array_sku', 'total', 'orderPlatformList', 'whole_platform_order_num', 'order_rate', 'avg_order_glass', 'pay_jingpian_glass', 'pay_jingpian_glass_rate', 'only_one_glass_num', 'only_one_glass_rate', 'every_price', 'whole_price');
            $this->success('', '', $data);
        }
    }

    /**
     * 商品销量/现价
     *
     * @Description
     * @author wpl
     * @since 2020/10/14 15:02:23 
     * @return void
     */
    public function sku_sales_data_line()
    {
        if ($this->request->isAjax()) {
            $sku = input('sku');
            $site = input('order_platform');
            $time_str = input('time_str');
            $createat = explode(' ', $time_str);
            $same_where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $same_where['site'] = ['=', $site];
            $same_where['sku'] = ['like', $sku . '%'];
            $recent_day_num = Db::name('datacenter_sku_day')->where($same_where)->order('day_date', 'asc')->column('order_num', 'day_date');
            $recent_day_now = Db::name('datacenter_sku_day')->where($same_where)->order('day_date', 'asc')->column('now_pricce', 'day_date');


            $json['xColumnName'] = array_keys($recent_day_num);
            $json['columnData'] = [
                [
                    'type' => 'line',
                    'data' => array_values($recent_day_num),
                    'name' => '商品销量',
                    'yAxisIndex' => 0,
                    'smooth' => true //平滑曲线
                ],
                [
                    'type' => 'line',
                    'data' => array_values($recent_day_now),
                    'name' => '现价',
                    'yAxisIndex' => 1,
                    'smooth' => true //平滑曲线
                ],

            ];

            return json(['code' => 1, 'data' => $json]);
        }
    }

    /**
     * 最近30天销量
     *
     * @Description
     * @author wpl
     * @since 2020/10/14 15:02:23 
     * @return void
     */
    public function sku_sales_data_bar()
    {
        if ($this->request->isAjax()) {
            $sku = input('sku');
            $site = input('order_platform');
            // dump($sku);
            // dump($site);
            $end = date('Y-m-d');
            $start = date('Y-m-d', strtotime("-30 days", strtotime($end)));

            $same_where['day_date'] = ['between', [$start, $end]];
            $same_where['site'] = ['=', $site];
            $same_where['sku'] = ['like', $sku . '%'];
            $recent_30_day = Db::name('datacenter_sku_day')->where($same_where)->order('day_date', 'asc')->column('order_num', 'day_date');
            $json['xColumnName'] = array_keys($recent_30_day);

            $json['columnData'] = [
                'type' => 'bar',
                'data' => array_values($recent_30_day),
                'name' => '最近30天销量'
            ];

            return json(['code' => 1, 'data' => $json]);
        }
    }
}










