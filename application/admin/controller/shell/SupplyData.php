<?php

/**
 * 执行时间：每天一次
 */

namespace app\admin\controller\shell;

use app\common\controller\Backend;
use think\Db;

class SupplyData extends Backend
{
    protected $noNeedLogin = ['*'];

    public function _initialize()
    {
        parent::_initialize();
    }
    /**
     * 呆滞数据
     */
    public function dull_stock(){
        $count = 0;   //总数量
        $count1 = 0;   //低
        $count2 = 0;   //中
        $count3 = 0;    //高
        $total = 0;     //总金额
        $total1 = 0;     //低
        $total2 = 0;     //中
        $total3 = 0;     //高
        $arr1 = array();   //A+
        $arr2 = array();   //A
        $arr3 = array();
        $arr4 = array();
        $arr5 = array();
        $arr6 = array();
        $arr7 = array();
        $arr8 = array();
        $grades = Db::name('product_grade')->field('true_sku,grade')->select();
        foreach ($grades as $key=>$value){
            $this->model = new \app\admin\model\itemmanage\Item;
            $this->item = new \app\admin\model\warehouse\ProductBarCodeItem;
            //该品实时库存
            $real_time_stock = $this->model->where('sku',$value['true_sku'])->where('is_del',1)->where('is_open',1)->value('sum(stock)-sum(distribution_occupy_stock) as result');
            //该品库存金额
            $sku_amount = $this->item->alias('i')->join('fa_purchase_order_item o','i.purchase_id=o.purchase_id and i.sku=o.sku')->where('i.sku',$value['true_sku'])->where('i.library_status',1)->value('SUM(IF(o.actual_purchase_price != 0,o.actual_purchase_price,o.purchase_price)) as result');
            //实际周转天数
            $sku_info  = $this->getSkuSales($value['true_sku']);
            $actual_day = $sku_info['days']!=0 && $sku_info['count']!=0 ? round($real_time_stock/($sku_info['count']/$sku_info['days']),2) : 0;
            if($actual_day >120 && $actual_day<=144){
                $count += $real_time_stock;
                $total += $sku_amount;
                $count1 += $real_time_stock;
                $total1 += $sku_amount;
                if($value['grade'] == 'A+'){
                    $arr1['stock'] += $real_time_stock;
                    $arr1['total'] += $sku_amount;
                    $arr1['low_stock'] += $real_time_stock;
                    $arr1['low_total'] += $sku_amount;
                }elseif($value['grade'] == 'A'){
                    $arr2['stock'] += $real_time_stock;
                    $arr2['total'] += $sku_amount;
                    $arr2['low_stock'] += $real_time_stock;
                    $arr2['low_total'] += $sku_amount;
                }elseif($value['grade'] == 'B'){
                    $arr3['stock'] += $real_time_stock;
                    $arr3['total'] += $sku_amount;
                    $arr3['low_stock'] += $real_time_stock;
                    $arr3['low_total'] += $sku_amount;
                }elseif($value['grade'] == 'C+'){
                    $arr4['stock'] += $real_time_stock;
                    $arr4['total'] += $sku_amount;
                    $arr4['low_stock'] += $real_time_stock;
                    $arr4['low_total'] += $sku_amount;
                }elseif($value['grade'] == 'C'){
                    $arr5['stock'] += $real_time_stock;
                    $arr5['total'] += $sku_amount;
                    $arr5['low_stock'] += $real_time_stock;
                    $arr5['low_total'] += $sku_amount;
                }elseif($value['grade'] == 'D'){
                    $arr6['stock'] += $real_time_stock;
                    $arr6['total'] += $sku_amount;
                    $arr6['low_stock'] += $real_time_stock;
                    $arr6['low_total'] += $sku_amount;
                }elseif($value['grade'] == 'E'){
                    $arr7['stock'] += $real_time_stock;
                    $arr7['total'] += $sku_amount;
                    $arr7['low_stock'] += $real_time_stock;
                    $arr7['low_total'] += $sku_amount;
                }else{
                    $arr8['stock'] += $real_time_stock;
                    $arr8['total'] += $sku_amount;
                    $arr8['low_stock'] += $real_time_stock;
                    $arr8['low_total'] += $sku_amount;
                }
            }elseif($actual_day > 144 && $actual_day<=168){
                $count += $real_time_stock;
                $total += $sku_amount;
                $count2 += $real_time_stock;
                $total2 += $sku_amount;
                if($value['grade'] == 'A+'){
                    $arr1['stock'] += $real_time_stock;
                    $arr1['total'] += $sku_amount;
                    $arr1['center_stock'] += $real_time_stock;
                    $arr1['center_total'] += $sku_amount;
                }elseif($value['grade'] == 'A'){
                    $arr2['stock'] += $real_time_stock;
                    $arr2['total'] += $sku_amount;
                    $arr2['center_stock'] += $real_time_stock;
                    $arr2['center_total'] += $sku_amount;
                }elseif($value['grade'] == 'B'){
                    $arr3['stock'] += $real_time_stock;
                    $arr3['total'] += $sku_amount;
                    $arr3['center_stock'] += $real_time_stock;
                    $arr3['center_total'] += $sku_amount;
                }elseif($value['grade'] == 'C+'){
                    $arr4['stock'] += $real_time_stock;
                    $arr4['total'] += $sku_amount;
                    $arr4['center_stock'] += $real_time_stock;
                    $arr4['center_total'] += $sku_amount;
                }elseif($value['grade'] == 'C'){
                    $arr5['stock'] += $real_time_stock;
                    $arr5['total'] += $sku_amount;
                    $arr5['center_stock'] += $real_time_stock;
                    $arr5['center_total'] += $sku_amount;
                }elseif($value['grade'] == 'D'){
                    $arr6['stock'] += $real_time_stock;
                    $arr6['total'] += $sku_amount;
                    $arr6['center_stock'] += $real_time_stock;
                    $arr6['center_total'] += $sku_amount;
                }elseif($value['grade'] == 'E'){
                    $arr7['stock'] += $real_time_stock;
                    $arr7['total'] += $sku_amount;
                    $arr7['center_stock'] += $real_time_stock;
                    $arr7['center_total'] += $sku_amount;
                }else{
                    $arr8['stock'] += $real_time_stock;
                    $arr8['total'] += $sku_amount;
                    $arr8['center_stock'] += $real_time_stock;
                    $arr8['center_total'] += $sku_amount;
                }
            }elseif($actual_day>168){
                $count += $real_time_stock;
                $total += $sku_amount;
                $count3 += $real_time_stock;
                $total3 += $sku_amount;
                if($value['grade'] == 'A+'){
                    $arr1['stock'] += $real_time_stock;
                    $arr1['total'] += $sku_amount;
                    $arr1['high_stock'] += $real_time_stock;
                    $arr1['high_total'] += $sku_amount;
                }elseif($value['grade'] == 'A'){
                    $arr2['stock'] += $real_time_stock;
                    $arr2['total'] += $sku_amount;
                    $arr2['high_stock'] += $real_time_stock;
                    $arr2['high_total'] += $sku_amount;
                }elseif($value['grade'] == 'B'){
                    $arr3['stock'] += $real_time_stock;
                    $arr3['total'] += $sku_amount;
                    $arr3['high_stock'] += $real_time_stock;
                    $arr3['high_total'] += $sku_amount;
                }elseif($value['grade'] == 'C+'){
                    $arr4['stock'] += $real_time_stock;
                    $arr4['total'] += $sku_amount;
                    $arr4['high_stock'] += $real_time_stock;
                    $arr4['high_total'] += $sku_amount;
                }elseif($value['grade'] == 'C'){
                    $arr5['stock'] += $real_time_stock;
                    $arr5['total'] += $sku_amount;
                    $arr5['high_stock'] += $real_time_stock;
                    $arr5['high_total'] += $sku_amount;
                }elseif($value['grade'] == 'D'){
                    $arr6['stock'] += $real_time_stock;
                    $arr6['total'] += $sku_amount;
                    $arr6['high_stock'] += $real_time_stock;
                    $arr6['high_total'] += $sku_amount;
                }elseif($value['grade'] == 'E'){
                    $arr7['stock'] += $real_time_stock;
                    $arr7['total'] += $sku_amount;
                    $arr7['high_stock'] += $real_time_stock;
                    $arr7['high_total'] += $sku_amount;
                }else{
                    $arr8['stock'] += $real_time_stock;
                    $arr8['total'] += $sku_amount;
                    $arr8['high_stock'] += $real_time_stock;
                    $arr8['high_total'] += $sku_amount;
                }
            }
        }
        $this->productGrade = new \app\admin\model\ProductGrade();
        $gradeSkuStock = $this->productGrade->getSkuStock();
        //计算产品等级的数量
        $a1_stock_num = $gradeSkuStock['aa_stock_num'];
        $a_stock_num = $gradeSkuStock['a_stock_num'];
        $b_stock_num = $gradeSkuStock['b_stock_num'];
        $c1_stock_num = $gradeSkuStock['ca_stock_num'];
        $c_stock_num = $gradeSkuStock['c_stock_num'];
        $d_stock_num = $gradeSkuStock['d_stock_num'];
        $e_stock_num = $gradeSkuStock['e_stock_num'];
        $f_stock_num = $gradeSkuStock['f_stock_num'];

        $date_time = date('Y-m-d', strtotime("-1 day"));
        $arr1['day_date'] = $arr2['day_date'] = $arr3['day_date'] = $arr4['day_date'] = $arr5['day_date'] = $arr6['day_date'] = $arr7['day_date'] = $arr8['day_date'] = $sum['day_date'] = $date_time;
        $arr1['grade'] = 'A+';
        $arr1['stock_rate'] = $a1_stock_num ? round($arr1['stock']/$a1_stock_num*100,2) : 0;
        $arr2['grade'] = 'A';
        $arr2['stock_rate'] = $a_stock_num ? round($arr2['stock']/$a_stock_num*100,2) : 0;
        $arr3['grade'] = 'B';
        $arr3['stock_rate'] = $b_stock_num ? round($arr3['stock']/$b_stock_num*100,2) : 0;
        $arr4['grade'] = 'C+';
        $arr4['stock_rate'] = $c1_stock_num ? round($arr4['stock']/$c1_stock_num*100,2) : 0;
        $arr5['grade'] = 'C';
        $arr5['stock_rate'] = $c_stock_num ? round($arr5['stock']/$c_stock_num*100,2) : 0;
        $arr6['grade'] = 'D';
        $arr6['stock_rate'] = $d_stock_num ? round($arr6['stock']/$d_stock_num*100,2) : 0;
        $arr7['grade'] = 'E';
        $arr7['stock_rate'] = $e_stock_num ? round($arr7['stock']/$e_stock_num*100,2) : 0;
        $arr8['grade'] = 'F';
        $arr8['stock_rate'] = $f_stock_num ? round($arr8['stock']/$f_stock_num*100,2) : 0;
        Db::name('supply_dull_stock')->insert($arr1);
        Db::name('supply_dull_stock')->insert($arr2);
        Db::name('supply_dull_stock')->insert($arr3);
        Db::name('supply_dull_stock')->insert($arr4);
        Db::name('supply_dull_stock')->insert($arr5);
        Db::name('supply_dull_stock')->insert($arr6);
        Db::name('supply_dull_stock')->insert($arr7);
        Db::name('supply_dull_stock')->insert($arr8);
        $sum['grade'] = 'Z';
        $sum['stock'] = $count;
        $sum['total'] = round($total,2);
        $sum['low_stock'] = $count1;
        $sum['low_total'] = round($total1,2);
        $sum['center_stock'] = $count2;
        $sum['center_total'] = round($total2,2);
        $sum['high_stock'] = $count3;
        $sum['high_total'] = round($total3,2);
        Db::name('supply_dull_stock')->insert($sum);
        echo 'ALL IS OK';
    }
    //获取sku总销量
    public function getSkuSales($sku)
    {
        $days = array();
        //zeelool
        $z_info = $this->getDullStock($sku,1);
        $sales_num1 = $z_info['sales_num'];
        $days[] = $z_info['days'];
        //voogueme
        $v_info = $this->getDullStock($sku,2);
        $sales_num2 = $v_info['sales_num'];
        $days[] = $v_info['days'];
        //nihao
        $n_info = $this->getDullStock($sku,3);
        $sales_num3 = $n_info['sales_num'];
        $days[] = $n_info['days'];
        //meeloog
        $m_info = $this->getDullStock($sku,4);
        $sales_num4 = $m_info['sales_num'];
        $days[] = $m_info['days'];
        //wesee
        $w_info = $this->getDullStock($sku, 5);
        $sales_num5 = $w_info['sales_num'];
        $days[] = $w_info['days'];
        //amazon
        $a_info = $this->getDullStock($sku, 8);
        $sales_num6 = $a_info['sales_num'];
        $days[] = $a_info['days'];
        //zeelool_es
        $e_sku = $this->getDullStock($sku, 9);
        $sales_num7 = $e_sku['sales_num'];
        $days[] = $e_sku['days'];
        //zeelool_de
        $d_info = $this->getDullStock($sku, 10);
        $sales_num8 = $d_info['sales_num'];
        $days[] = $d_info['days'];
        //zeelool_jp
        $j_info = $this->getDullStock($sku, 11);
        $sales_num9 = $j_info['sales_num'];
        $days[] = $j_info['days'];
        //voogmechic
        $c_info = $this->getDullStock($sku, 12);
        $sales_num10 = $c_info['sales_num'];
        $days[] = $j_info['days'];
        $count = $sales_num1+$sales_num2+$sales_num3+$sales_num4+$sales_num5+$sales_num6+$sales_num7+$sales_num8+$sales_num9+$sales_num10;
        $days = max($days);
        $data = array(
            'count'=>$count,
            'days'=>$days,
        );
        return $data;
    }
    //查询sku的有效天数的销量和有效天数
    public function getDullStock($sku,$site)
    {
        $skuSalesNum = new \app\admin\model\SkuSalesNum();
        $date = date('Y-m-d');
        $map['createtime'] = ['<', $date];
        $map['sku'] = $sku;
        $map['site'] = $site;
        $sql = $skuSalesNum->field('sales_num')->where($map)->limit(30)->order('createtime desc')->buildSql();
        $data['sales_num'] = Db::table($sql.' a')->sum('a.sales_num');
        $days = Db::name('sku_sales_num')->where($map)->count();
        $data['days'] = $days > 30 ? 30 : $days;
        return $data;
    }
}
