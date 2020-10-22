<?php

namespace app\admin\controller\operatedatacenter\GoodsData;

use app\admin\model\platformManage\MagentoPlatform;
use app\common\controller\Backend;
use think\Controller;
use think\Db;
use think\Request;

class SingleItem extends Backend
{
    public function _initialize()
    {
        parent::_initialize();
        $this->zeelool = new \app\admin\model\order\order\Zeelool();
        $this->voogueme = new \app\admin\model\order\order\Voogueme();
        $this->nihao = new \app\admin\model\order\order\Nihao();
    }

    /**
     * 商品数据-单品查询
     *
     * @return \think\Response
     */
    public function index()
    {
        $orderPlatform = (new MagentoPlatform())->getNewAuthSite();
        if (empty($orderPlatform)) {
            $this->error('您没有权限访问', 'general/profile?ref=addtabs');
        }
        //设置过滤方法
        if ($this->request->isAjax()) {
            $sku = input('sku');
            $platform = input('order_platform') ? input('order_platform') : 1;
            $time_str = input('time_str');
            $createat = explode(' ', $time_str);
            $map = [];
            if ($sku && $platform && $time_str){
                // dump(111);
                $map['a.created_at'] = ['between', [$createat[0], $createat[3]]];
                $map['sku'] = ['like', $sku.'%'];
                $map['a.status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
            }
            switch ($platform) {
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
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $order_model
                ->where($map)
                ->alias('a')
                ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
                // ->order($order)
                ->count();
            $list = $order_model
                ->where($map)
                ->alias('a')
                ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
                // ->order($order)
                ->limit($offset, $limit)
                ->select();
            // if (!$total){
            //     $total = 0;
            // }
            // if (!$list){
            //     $list = [];
            // }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }

        // $this->assignconfig('platform', $platform);
        // $this->assignconfig('sku', $sku);
        // $this->view->assign(compact('sku','array','total', 'orderPlatformList', 'whole_platform_order_num', 'order_rate', 'avg_order_glass', 'pay_jingpian_glass', 'pay_jingpian_glass_rate', 'only_one_glass_num', 'only_one_glass_rate', 'every_price', 'whole_price'));
        $this->assign('orderPlatformList', $orderPlatform);
        // $this->assign('array', $array);
        return $this->view->fetch();
    }

    public function ajax_top_data()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            //站点
            $order_platform = $params['order_platform'] ? $params['order_platform'] : 1;
            //时间
            $time_str = $params['time_str'] ? $params['time_str'] : '';
            $sku = input('sku');
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
            //此sku的总订单量
            $map['sku'] = ['like', $sku.'%'];
            $map['a.status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
            $total = $order_model
                ->where($map)
                ->alias('a')
                ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
                ->count();
            //整站订单量
            $maps['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
            $whole_platform_order_num = $this->zeelool->where($maps)->count();

            //订单占比
            $order_rate = round($total / $whole_platform_order_num * 100, 2);

            //平均订单副数
            $whole_glass = $model
                ->table('sales_flat_order_item')
                ->where('sku', $sku)
                ->count();//sku总副数
            $avg_order_glass = $total == 0 ? 0 : round($whole_glass / $total, 0);

            //付费镜片订单数
            $pay_jingpian_glass = $this->zeelool
                ->alias('a')
                ->join(['sales_flat_order_item_prescription' => 'b'], 'a.entity_id=b.order_id')
                ->where('sku', $sku)
                ->where('b.coatiing_price', '>', 0)
                ->count();

            //付费镜片订单数占比
            $pay_jingpian_glass_rate = $total == 0 ? 0 : round($pay_jingpian_glass / $total * 100, 2);

            //只买一副的订单
            $only_one_glass_num = $model->table('sales_flat_order_item')
                ->where('sku', $sku)
                ->alias('a')
                ->join(['sales_flat_order' => 'b'], 'a.order_id=b.entity_id')
                ->field('order_id')
                ->select();
            $arr = array_count_values(array_column($only_one_glass_num, 'order_id'));//统计每个订单购买的副数
            $only_one_glass_num = 0;
            foreach ($arr as $v) {
                if ($v == 1) {
                    $only_one_glass_num += 1;
                }
            }

            //只买一副的订单占比
            $only_one_glass_rate = $total == 0 ? 0 : round($only_one_glass_num / $total * 100, 2);

            //订单总金额
            $whole_price = $order_model
                ->where($map)
                ->alias('a')
                ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
                ->sum('base_grand_total');

            //订单客单价
            $every_price = $total == 0 ? 0 : round($whole_price / $total * 100, 2);
            //关联购买
            $andWhere = "FIND_IN_SET({$sku},sku)";
            $connect_buy = $model->table('sales_flat_order_item')
                ->where('sku', $sku)
                ->distinct('order_id')
                ->field('order_id')
                ->select();//包含此sku的所有订单好
            $connect_buy = array_column($connect_buy, 'order_id');
            $skus = array();
            foreach ($connect_buy as $value) {
                $arr = $model->table('sales_flat_order_item')
                    ->where('order_id', $value)
                    ->field('sku')
                    ->select();//这些订单号内的所有sku
                $skus[] = array_column($arr, 'sku');
            }
            $array = [];
            //获取关联购买的数量
            foreach ($skus as $k => $v) {
                foreach ($v as $vv) {
                    if ($vv != $sku) {
                        $array[$vv] += 1;
                    }
                }
            }
            $data = compact('sku', 'array', 'total', 'orderPlatformList', 'whole_platform_order_num', 'order_rate', 'avg_order_glass', 'pay_jingpian_glass', 'pay_jingpian_glass_rate', 'only_one_glass_num', 'only_one_glass_rate', 'every_price', 'whole_price');
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
            $same_where['sku'] = ['like', $sku.'%'];
            $recent_day_num = Db::name('datacenter_sku_day')->where($same_where)->order('day_date','asc')->column('order_num', 'day_date');
            $recent_day_now = Db::name('datacenter_sku_day')->where($same_where)->order('day_date','asc')->column('now_pricce', 'day_date');


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
            $same_where['sku'] = ['like', $sku.'%'];
            $recent_30_day = Db::name('datacenter_sku_day')->where($same_where)->order('day_date','asc')->column('order_num', 'day_date');
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
