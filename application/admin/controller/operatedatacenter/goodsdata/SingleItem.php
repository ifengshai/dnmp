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
        $sku = 'FP0180-01';
        $map['sku'] = ['=', $sku];
        $map['a.status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $total = $this->zeelool
            ->where($map)
            ->alias('a')
            ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
            ->count();
        $list = $this->zeelool
            ->where($map)
            ->alias('a')
            ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
            ->select();
        // dump(collection($list)->toArray());die;
        if ($this->request->isAjax()) {
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        //设置过滤方法
        // if ($this->request->isAjax()) {
        //     $params = $this->request->param();
        //     $sku = input('sku');
        //     $platform = input('order_platform');
        // list($where, $sort, $order, $offset, $limit) = $this->buildparams();
        // $total = $order_model
        //     ->where($where)
        //     ->order($sort, $order)
        //     ->count();

        // $result = array("total" => $total, "rows" => $list);

        // return json($result);
        // }

        // $this->assignconfig('platform', $platform);
        //整站订单量
        $maps['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $whole_platform_order_num = $this->zeelool->where($maps)->count();

        //订单占比
        $order_rate = round($total / $whole_platform_order_num * 100, 2);

        //平均订单副数
        $whole_glass = Db::connect('database.db_zeelool')
            ->table('sales_flat_order_item')
            ->where('sku', $sku)
            ->count();//sku总副数
        $avg_order_glass = round($whole_glass / $total, 0);

        //付费镜片订单数
        $pay_jingpian_glass = Db::connect('database.db_zeelool')
            ->table('sales_flat_order_item_prescription')
            ->where('sku', $sku)
            ->where('coatiing_price', '>', 0)
            // ->count();
            ->select();
        // dump($pay_jingpian_glass);
        $pay_jingpian_glass = count($pay_jingpian_glass);

        //付费镜片订单数占比
        $pay_jingpian_glass_rate = round($pay_jingpian_glass / $total * 100, 2);

        //只买一副的订单
        $only_one_glass_num = Db::connect('database.db_zeelool')->table('sales_flat_order_item')
            ->where('sku', $sku)
            // ->distinct(true)
            ->field('order_id')
            ->group('order_id')
            ->select();
        $only_one_glass_num = count($only_one_glass_num);

        //只买一副的订单占比
        $only_one_glass_rate = round($only_one_glass_num / $total * 100, 2);

        //订单总金额
        $whole_price = $this->zeelool
            ->where($map)
            ->alias('a')
            ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
            ->sum('base_grand_total');

        //订单客单价
        $every_price = round($whole_price /$total * 100,2);
        //关联购买
        $connect_buy = Db::connect('database.db_zeelool')->table('sales_flat_order_item')
            // ->where('sku', $sku)
            // ->distinct('order_id')
            // ->field('order_id')
            // ->select();
        ->where('sku', $sku)
        // ->distinct(true)
        ->field('order_id')
        ->group('order_id')
        ->select();
        $sku = array();
        // foreach ($connect_buy as $value){
        //     $sku[] = Db::connect('database.db_zeelool')->table('sales_flat_order_item')
        //         ->where('order_id',$value['order_id'])
        //         ->field('sku')
        //         ->group('sku')
        //         ->select();
        // }

        dump($connect_buy);

        $connect_buy = [['sku'=>'aaaaa','num'=>111],['sku'=>'aaaaa1','num'=>2222],['sku'=>'aaaaa22','num'=>3333]];
        $this->assignconfig('sku', $sku);
        $this->assign('orderPlatformList', $orderPlatform);
        $this->assign('connect_buy', $connect_buy);
        $this->view->assign(compact('total', 'orderPlatformList', 'whole_platform_order_num', 'order_rate', 'avg_order_glass', 'pay_jingpian_glass','pay_jingpian_glass_rate','only_one_glass_num','only_one_glass_rate','every_price','whole_price'));
        return $this->view->fetch();
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
            $json['xColumnName'] = ['2020-07-01', '2020-07-02', '2020-07-03', '2020-07-04', '2020-07-05', '2020-07-06', '2020-07-07', '2020-07-08'];
            $json['columnData'] = [
                [
                    'type' => 'line',
                    'data' => [430, 550, 800, 650, 410, 520, 430, 870],
                    'name' => '商品销量',
                    'yAxisIndex' => 0,
                    'smooth' => true //平滑曲线
                ],
                [
                    'type' => 'line',
                    'data' => [10, 26, 45, 40, 40, 65, 73, 80],
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


            $json['xColumnName'] = ['2020-07-01', '2020-07-02', '2020-07-03', '2020-07-04', '2020-07-05', '2020-07-06', '2020-07-07', '2020-07-08'];
            $json['columnData'] = [
                'type' => 'bar',
                'data' => [430, 550, 800, 650, 410, 520, 430, 870],
                'name' => '最近30天销量'
            ];

            return json(['code' => 1, 'data' => $json]);
        }
    }

}
