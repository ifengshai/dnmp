<?php

namespace app\admin\controller\supplydatacenter;

use app\admin\model\OrderStatistics;
use app\common\controller\Backend;
use think\Cache;
use think\Controller;
use think\Db;
use think\Request;

class DataMarket extends Backend
{
    public function _initialize()
    {
        parent::_initialize();
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
        $this->model = new \app\admin\model\itemmanage\Item;
        $this->skuSalesNum = new \app\admin\model\SkuSalesNum();
        $this->outstock = new \app\admin\model\warehouse\Outstock;
        $this->instock = new \app\admin\model\warehouse\Instock;
        $this->productGrade = new \app\admin\model\ProductGrade();
        $this->purchase = new \app\admin\model\purchase\PurchaseOrder();
        $this->warehouse_model = new \app\admin\model\WarehouseData();



    }
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $params = $this->request->param();
        if(!$params['time_str']){
            $start = date('Y-m-d 00:00:00', strtotime('-6 day'));
            $end   = date('Y-m-d 23:59:59');
            $time_str = $start.' - '.$end;
        }else{
            $time_str = $params['time_str'];
        }
        //库存总览
        $stock_overview = $this->stock_overview();
        //库存分级概况
        $stock_level_overview = $this->stock_level_overview();
        //采购概况
        $purchase_overview = $this->purchase_overview($time_str);
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        $this->view->assign(compact('stock_overview','stock_level_overview','purchase_overview','magentoplatformarr'));
        return $this->view->fetch();
    }
    //库存总览
    public function stock_overview(){
        $where['is_open'] = 1;
        $where['is_del'] = 1;
        $where['category_id'] = ['<>',43]; //排除补差价商品
        //库存总数量
        $arr['stock_num'] = $this->model->where($where)->sum('stock');
        //库存总金额
        $arr['stock_amount'] = $this->model->where($where)->sum('stock*purchase_price');
        //库存单价
        $arr['stock_price'] = $arr['stock_num'] ? round($arr['stock_amount']/$arr['stock_num'],2) : 0;
        //在途库存数量
        $arr['onway_stock_num'] = $this->model->where($where)->sum('on_way_stock');
        //在途库存总金额
        $arr['onway_stock_amount'] = $this->model->where($where)->sum('on_way_stock*purchase_price');
        //在途库存单价
        $arr['onway_stock_price'] = $arr['onway_stock_num'] ? round($arr['onway_stock_amount']/$arr['onway_stock_num'],2) : 0;
        //待入库数量
        $arr['wait_stock_num'] = $this->model->where($where)->sum('wait_instock_num');
        //待入库金额
        $arr['wait_stock_amount'] = $this->model->where($where)->sum('wait_instock_num*purchase_price');
        return $arr;
    }
    //仓库指标总览
    public function stock_measure_overview($time_str){
        $createat = explode(' ', $time_str);
        $where['createtime'] = ['between', [$createat[0], $createat[3]]];
        /*
         * 库存周转率：所选时间内库存消耗数量/[（期初实时库存+期末实时库存）/2];
         * 库存消耗数量: 库存消耗数量+出库单出库数量
        */
        //库存消耗数量
        $stock_consume_num = $this->skuSalesNum->where($where)->sum('sales_num');
        //出库单出库数量
        $out_stock_num = $this->outstock->alias('o')->join('fa_out_stock_item i','o.id=i.out_stock_id')->where($where)->where('status',2)->sum('out_stock_num');
        $consume_sum_num = $stock_consume_num+$out_stock_num;
        //期初实时库存
        $start_stock_where = [];
        $start_stock_where[] = ['exp', Db::raw("DATE_FORMAT(createtime, '%Y-%m-%d') = '" . $createat[0] . "'")];
        $start_stock = Db::table('fa_product_allstock_log')->where($start_stock_where)->value('allnum');
        //期末实时库存
        $end_stock_where = [];
        $end_stock_where[] = ['exp', Db::raw("DATE_FORMAT(createtime, '%Y-%m-%d') = '" . $createat[3] . "'")];
        $end_stock = Db::table('fa_product_allstock_log')->where($start_stock_where)->value('allnum');
        $sum = $start_stock+$end_stock;
        //库存周转率
        $arr['turnover_rate'] = $sum ? round($consume_sum_num/$sum/2,2) : 0;
        //库存精度

    }
    //库存分级概况
    public function stock_level_overview(){
        $gradeSkuStock = $this->productGrade->getSkuStock();
        //计算产品等级的数量
        $arr = array(
            array(
                'grade'=>'A+',
                'count'=>$this->productGrade->where('grade','A+')->count(),
                'stock_num'=>$gradeSkuStock['aa_stock_num'],
                'stock_price'=>$gradeSkuStock['aa_stock_price'],
            ),
            array(
                'grade'=>'A',
                'count'=>$this->productGrade->where('grade','A')->count(),
                'stock_num'=>$gradeSkuStock['a_stock_num'],
                'stock_price'=>$gradeSkuStock['a_stock_price'],
            ),
            array(
                'grade'=>'B',
                'count'=>$this->productGrade->where('grade','B')->count(),
                'stock_num'=>$gradeSkuStock['b_stock_num'],
                'stock_price'=>$gradeSkuStock['b_stock_price'],
            ),
            array(
                'grade'=>'C+',
                'count'=>$this->productGrade->where('grade','C+')->count(),
                'stock_num'=>$gradeSkuStock['ca_stock_num'],
                'stock_price'=>$gradeSkuStock['ca_stock_price'],
            ),
            array(
                'grade'=>'C',
                'count'=>$this->productGrade->where('grade','C')->count(),
                'stock_num'=>$gradeSkuStock['c_stock_num'],
                'stock_price'=>$gradeSkuStock['c_stock_price'],
            ),
            array(
                'grade'=>'D',
                'count'=>$this->productGrade->where('grade','D')->count(),
                'stock_num'=>$gradeSkuStock['d_stock_num'],
                'stock_price'=>$gradeSkuStock['d_stock_price'],
            ),
            array(
                'grade'=>'E',
                'count'=>$this->productGrade->where('grade','E')->count(),
                'stock_num'=>$gradeSkuStock['e_stock_num'],
                'stock_price'=>$gradeSkuStock['e_stock_price'],
            ),
            array(
                'grade'=>'F',
                'count'=>$this->productGrade->where('grade','F')->count(),
                'stock_num'=>$gradeSkuStock['f_stock_num'],
                'stock_price'=>$gradeSkuStock['f_stock_price'],
            ),
        );
        $all_num = 0;
        $all_stock_num = 0;
        foreach ($arr as $value){
            //总数
            $all_num += $value['count'];
            //总库存
            $all_stock_num += $value['stock_num'];
        }
        foreach ($arr as $key=>$val){
            $arr[$key]['percent'] = $all_num ? round($val['count']/$all_num,2).'%':0;
            $arr[$key]['stock_percent'] = $all_stock_num ? round($val['stock_num']/$all_stock_num,2).'%':0;

        }
        return $arr;
    }
    //采购数据总览
    public function purchase_overview($time_str){
        $createat = explode(' ', $time_str);
        $where['p.createtime'] = ['between', [$createat[0], $createat[3]]];
        $where['p.is_del'] = 1;
        $status_where['p.purchase_status'] = ['in', [2, 5, 6, 7]];
        $arrive_where['p.purchase_status'] = 7;
        //采购总数
        $arr['purchase_num'] = $this->purchase->alias('p')->where($where)->where($status_where)->join(['fa_purchase_order_item' => 'b'], 'p.id=b.purchase_id')->sum('b.purchase_num');
        //采购总金额
        $arr['purchase_amount'] = $this->purchase->alias('p')->where($where)->where($status_where)->join(['fa_purchase_order_item' => 'b'], 'p.id=b.purchase_id')->sum('purchase_num*purchase_price');
        //采购总SKU数
        $arr['purchase_sku_num'] = $this->purchase->alias('p')->where($where)->where($status_where)->join(['fa_purchase_order_item' => 'b'], 'p.id=b.purchase_id')->group('sku')->count(1);
        //所选时间短内到货总批次
        $sum_batch = $this->purchase->alias('p')->join('fa_purchase_batch b','p.id=b.purchase_id','left')->where($where)->where($arrive_where)->count();
        //所选时间内到货的采购单延迟的批次
        $delay_batch = $this->purchase->alias('p')->join('fa_purchase_batch b','p.id=b.purchase_id','left')->join('fa_logistics_info l','p.id=l.purchase_id','left')->where($where)->where($arrive_where)->where('p.arrival_time<l.sign_time')->count();
        //采购批次到货延时率
        $arr['purchase_delay_rate'] = $sum_batch ? round($delay_batch/$sum_batch,2).'%' : 0;
        //所选时间内到货的采购单合格率90%以上的批次
        $qualified_num = $this->purchase->alias('p')->join('fa_check_order o','p.id = o.purchase_id','left')->join('fa_check_order_item i','o.id = i.check_id','left')->where($where)->where($arrive_where)->group('p.id')->having('sum( quantity_num )/ sum( arrivals_num )>= 0.9')->count();
        //采购批次到货合格率
        $arr['purchase_qualified_rate'] = $sum_batch ? round($qualified_num/$sum_batch,2).'%' : 0;
        //采购单价
        $arr['purchase_price'] = $arr['purchase_num'] ? round($arr['purchase_amount']/$arr['purchase_num'],2) : 0;
        return $arr;
    }
    //采购概况中的折线图柱状图
    public function purchase_histogram_line(){
        if ($this->request->isAjax()) {
            $time_str = input('time_str');
            if (!$time_str) {
                $start = date('Y-m-d 00:00:00', strtotime('-6 day'));
                $end = date('Y-m-d 23:59:59');
                $time_str = $start . ' - ' . $end;
            }
            $createat = explode(' ', $time_str);
            $where['create_time'] = ['between', [$createat[0], $createat[3]]];
            $list = $this->warehouse_model->where($where)
                ->field('all_purchase_num,create_date,all_purchase_price')
                ->order('create_date asc')
                ->select();
            $warehouse_data = collection($list)->toArray();
            //全部采购单
            $barcloumndata = array_column($warehouse_data, 'all_purchase_num');
            $linecloumndata = array_column($warehouse_data, 'all_purchase_price');

            $json['xColumnName'] = array_column($warehouse_data, 'create_date');
            $json['columnData'] = [
                [
                    'type' => 'bar',
                    'data' => $barcloumndata,
                    'name' => '采购数量'
                ],
                [
                    'type' => 'line',
                    'data' => $linecloumndata,
                    'name' => '采购金额',
                    'yAxisIndex' => 1,
                    'smooth' => true //平滑曲线
                ],

            ];
            return json(['code' => 1, 'data' => $json]);
        }
    }
}
