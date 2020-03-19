<?php
namespace app\admin\controller\datacenter\operationanalysis\operationkanban;
use think\Db;
use app\common\controller\Backend;
use app\admin\model\platformmanage\MagentoPlatform;
class Operationalreport extends Backend{
    //订单类型数据统计
    protected $item = null;
    protected $itemPlatformSku = null;
    /**
     * 运营报告首页数据
     *
     * @Description created by lsw
     * @author lsw
     * @since 2020/03/12 17:51:03 
     * @return void
     */
    public function index ()
    {
        $orderPlatform = (new MagentoPlatform())->getOrderPlatformList();
        $create_time = input('create_time');
        $platform    = input('order_platform', 1);
        if($this->request->isAjax()){
            $params = $this->request->param();
            //默认当天
            if ($params['time']) {
                $time = explode(' ', $params['time']);
                $map['created_at'] = $itemMap['m.created_at'] =  ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
            } else {
                $map['created_at'] = $itemMap['m.created_at'] =  ['between', [date('Y-m-d 00:00:00', strtotime('-7 day')), date('Y-m-d H:i:s', time())]];
            }
            $order_platform = $params['platform'];
            if(4<=$order_platform){
                return $this->error('该平台暂时没有数据');
            }
            $result = $this->platformOrderInfo($order_platform,$map,$itemMap);
            if(!$result){
                return $this->error('暂无数据');
            }
            return json(['code' => 1, 'data'=>[1,2,3],'rows' => $result]);

        }	
        $this->view->assign(
            [
                'orderPlatformList'	=> $orderPlatform,
                'create_time'       => $create_time,
                'platform'          => $platform,
            ]
        );
        return  $this->view->fetch();
    }
    /**
     * 获取订单信息 运费数据统计、未成功订单状态统计、币种数据统计、订单类型数据统计
     *
     * @Description created by lsw
     * @author lsw
     * @since 2020/03/16 17:16:16 
     * @param [type] $platform
     * @param [type] $map
     * @return void
     */
    public function platformOrderInfo($platform,$map,$itemMap)
    {
        $this->item = new \app\admin\model\itemmanage\Item;
        $this->itemPlatformSku = new \app\admin\model\itemmanage\ItemPlatformSku;
        switch($platform){
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
        if(false == $model){
            return false;
        }
        $model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $model->table('sales_flat_order_item')->query("set time_zone='+8:00'");
        $where = " status in ('processing','complete','creditcard_proccessing','free_processing')";
        $whereItem = " o.status in ('processing','complete','creditcard_proccessing','free_processing')";
        //订单类型数据统计
        //1.普通订单数量
        $general_order              = $model->table('sales_flat_order')->where($where)->where(['order_type'=>1])->where($map)->count('*');
        //2.普通订单金额
        $general_money              = $model->table('sales_flat_order')->where($where)->where(['order_type'=>1])->where($map)->sum('base_grand_total');
        //3.批发订单数量
        $wholesale_order            = $model->table('sales_flat_order')->where($where)->where(['order_type'=>2])->where($map)->count('*');
        //4.批发订单金额
        $wholesale_money            = $model->table('sales_flat_order')->where($where)->where(['order_type'=>2])->where($map)->sum('base_grand_total');
        //5.网红订单数量
        $celebrity_order            = $model->table('sales_flat_order')->where($where)->where(['order_type'=>3])->where($map)->count('*');
        //6.网红订单金额
        $celebrity_money            = $model->table('sales_flat_order')->where($where)->where(['order_type'=>3])->where($map)->sum('base_grand_total');
        //补发订单数量
        $reissue_order              = $model->table('sales_flat_order')->where($where)->where(['order_type'=>4])->where($map)->count('*');
        //补发订单金额
        $reissue_money              = $model->table('sales_flat_order')->where($where)->where(['order_type'=>4])->where($map)->sum('base_grand_total');
        //补差价订单数量
        $fill_post_order            = $model->table('sales_flat_order')->where($where)->where(['order_type'=>5])->where($map)->count('*');
        //补差价订单金额
        $fill_post_money            = $model->table('sales_flat_order')->where($where)->where(['order_type'=>5])->where($map)->sum('base_grand_total');
        //普通订单占比
        $general_order_percent      = @round(($general_order/($general_order + $wholesale_order + $celebrity_order + $reissue_order + $fill_post_order))*100,2);
        //批发订单占比
        $wholesale_order_percent    = @round(($wholesale_order/($general_order + $wholesale_order + $celebrity_order + $reissue_order + $fill_post_order))*100,2);
        //网红订单占比
        $celebrity_order_percent    = @round(($celebrity_order/($general_order + $wholesale_order + $celebrity_order + $reissue_order + $fill_post_order))*100,2);
        //补发订单占比
        $reissue_order_percent      = @round(($reissue_order/($general_order + $wholesale_order + $celebrity_order + $reissue_order + $fill_post_order))*100,2);
        //补差价订单占比
        $fill_post_order_percent    = @round(($fill_post_order/($general_order + $wholesale_order + $celebrity_order + $reissue_order + $fill_post_order))*100,2);
        //美元订单数量
        $usd_order_num              = $model->table('sales_flat_order')->where($where)->where(['order_currency_code'=>'USD'])->where($map)->count('*');
        //美元订单金额
        $usd_order_money            = $model->table('sales_flat_order')->where($where)->where(['order_currency_code'=>'USD'])->where($map)->sum('base_grand_total');
        //美元订单的平均金额
        if(0<$usd_order_num){
            $usd_order_average_amount = round($usd_order_money/$usd_order_num,2);
        }else{
            $usd_order_average_amount = 0;
        }
        
        //CAD订单数量
        $cad_order_num              = $model->table('sales_flat_order')->where($where)->where(['order_currency_code'=>'CAD'])->where($map)->count('*');
        //CAD订单金额
        $cad_order_money            = $model->table('sales_flat_order')->where($where)->where(['order_currency_code'=>'CAD'])->where($map)->sum('base_grand_total');
        //CAD订单的平均金额
        if(0<$cad_order_num){
            $cad_order_average_amount = round($cad_order_money/$cad_order_num,2);
        }else{
            $cad_order_average_amount = 0;
        }
        
        //AUD订单数量
        $aud_order_num              = $model->table('sales_flat_order')->where($where)->where(['order_currency_code'=>'AUD'])->where($map)->count('*');
        //AUD订单金额
        $aud_order_money            = $model->table('sales_flat_order')->where($where)->where(['order_currency_code'=>'AUD'])->where($map)->sum('base_grand_total');
        //AUD订单平均金额
        if(0<$aud_order_num){
            $aud_order_average_amount = round($aud_order_money/$aud_order_num,2);
        }else{
            $aud_order_average_amount = 0;
        }
        
        //EUR订单数量
        $eur_order_num              = $model->table('sales_flat_order')->where($where)->where(['order_currency_code'=>'EUR'])->where($map)->count('*');
        //EUR订单金额
        $eur_order_money            = $model->table('sales_flat_order')->where($where)->where(['order_currency_code'=>'EUR'])->where($map)->sum('base_grand_total');
        //EUR订单平均金额
        if(0<$eur_order_num){
            $eur_order_average_amount = round($eur_order_money/$eur_order_num,2);
        }else{
            $eur_order_average_amount = 0;
        }
        
        //GBP订单数量
        $gbp_order_num              = $model->table('sales_flat_order')->where($where)->where(['order_currency_code'=>'GBP'])->where($map)->count('*');
        //GBP订单金额
        $gbp_order_money            = $model->table('sales_flat_order')->where($where)->where(['order_currency_code'=>'GBP'])->where($map)->sum('base_grand_total');
        //GBP订单平均金额
        if(0<$gbp_order_num){
            $gbp_order_average_amount   = round($gbp_order_money/$gbp_order_num,2);
        }else{
            $gbp_order_average_amount   = 0;
        }
        
        //usd订单百分比
        $usd_order_percent          = @round(($usd_order_num/($usd_order_num + $cad_order_num + $aud_order_num + $eur_order_num + $gbp_order_num))*100,2);                 
        //cad订单百分比
        $cad_order_percent          = @round(($cad_order_num/($usd_order_num + $cad_order_num + $aud_order_num + $eur_order_num + $gbp_order_num))*100,2);
        //aud订单百分比
        $aud_order_percent          = @round(($aud_order_num/($usd_order_num + $cad_order_num + $aud_order_num + $eur_order_num + $gbp_order_num))*100,2);
        //eur订单百分比
        $eur_order_percent          = @round(($eur_order_num/($usd_order_num + $cad_order_num + $aud_order_num + $eur_order_num + $gbp_order_num))*100,2);
        //gbp订单百分比
        $gbp_order_percent          = @round(($gbp_order_num/($usd_order_num + $cad_order_num + $aud_order_num + $eur_order_num + $gbp_order_num))*100,2);
        //所有的订单状态
        $order_status               = $model->table('sales_flat_order')->distinct(true)->field('status')->select();
        $order_status_arr           = $all_shipping_amount_arr = [];
        if($order_status){
            foreach($order_status as $v){
                $order_status_arr['status'][] = $v['status'];
                $order_status_arr['money'][]  = $model->table('sales_flat_order')->where($map)->where(['status'=>$v['status']])->sum('base_grand_total');
                $order_status_arr['num'][]    = $model->table('sales_flat_order')->where($map)->where(['status'=>$v['status']])->count('*');   
            }
        }
        //所有的运费
        $all_shipping_amount      = $model->table('sales_flat_order')->distinct(true)->field('base_shipping_amount')->order('base_shipping_amount')->select();
        if($all_shipping_amount){
            foreach($all_shipping_amount as $av){
                $all_shipping_amount_arr['shipping_amount'][] = $av['base_shipping_amount'];
                $all_shipping_amount_arr['num'][]             = $num = $model->table('sales_flat_order')->where($map)->where(['base_shipping_amount'=>$av['base_shipping_amount']])->count('*');
                $all_shipping_amount_arr['money'][]           = round($av['base_shipping_amount']*$num,2);         
            }
        }
        //求出眼镜所有sku
        $frame_sku  = $this->item->getDifferenceSku(1);
        //求出饰品的所有sku
        $decoration_sku = $this->item->getDifferenceSku(3);
        //求出眼镜的销售额 base_price  base_discount_amount
        $frame_money_price    = $model->table('sales_flat_order_item m')->join('sales_flat_order o','m.order_id=o.entity_id','left')->where($whereItem)->where($itemMap)->where('m.sku','in',$frame_sku)->sum('base_price');
        //眼镜的折扣价格
        $frame_money_discount = $model->table('sales_flat_order_item m')->join('sales_flat_order o','m.order_id=o.entity_id','left')->where($whereItem)->where($itemMap)->where('m.sku','in',$frame_sku)->sum('base_discount_amount');
        //眼镜的实际销售额
        $frame_money          = round(($frame_money_price - $frame_money_discount),2);
        //眼镜的销售副数
        $frame_sales_num      = $model->table('sales_flat_order_item m')->join('sales_flat_order o','m.order_id=o.entity_id','left')->where($whereItem)->where($itemMap)->where('m.sku','in',$frame_sku)->count('*');
        //眼镜平均副金额
        if( 0 <$frame_sales_num){
            $frame_avg_money  = round(($frame_money/$frame_sales_num),2);
        }else{
            $frame_avg_money  = 0;
        }
        //求出配饰的销售额
        $decoration_money_price    = $model->table('sales_flat_order_item m')->join('sales_flat_order o','m.order_id=o.entity_id','left')->where($whereItem)->where($itemMap)->where('m.sku','in',$decoration_sku)->sum('base_price');
        //配饰的折扣价格
        $decoration_money_discount = $model->table('sales_flat_order_item m')->join('sales_flat_order o','m.order_id=o.entity_id','left')->where($whereItem)->where($itemMap)->where('m.sku','in',$decoration_sku)->sum('base_discount_amount');
        //配饰的实际销售额
        $decoration_money          = round(($decoration_money_price - $decoration_money_discount),2);
        //配饰的销售副数
        $decoration_sales_num      = $model->table('sales_flat_order_item m')->join('sales_flat_order o','m.order_id=o.entity_id','left')->where($whereItem)->where($itemMap)->where('m.sku','in',$decoration_sku)->count('*');
        //配饰平均副金额
        if(0< $decoration_sales_num){
            $decoration_avg_money  = round(($decoration_money/$decoration_sales_num),2);
        }else{
            $decoration_avg_money  = 0; 
        }
        //眼镜正常售卖数
        $frame_onsales_num         = $this->itemPlatformSku->putawayDifferenceSku(1,$platform);
        //配饰正常售卖数
        $decoration_onsales_num    = $this->itemPlatformSku->putawayDifferenceSku(3,$platform); 
        return [
            'general_order'                     => $general_order,
            'general_money'                     => $general_money,
            'wholesale_order'                   => $wholesale_order,
            'wholesale_money'                   => $wholesale_money,
            'celebrity_order'                   => $celebrity_order,
            'celebrity_money'                   => $celebrity_money,
            'reissue_order'                     => $reissue_order,
            'reissue_money'                     => $reissue_money,
            'fill_post_order'                   => $fill_post_order,
            'fill_post_money'                   => $fill_post_money,
            'general_order_percent'             => $general_order_percent,
            'wholesale_order_percent'           => $wholesale_order_percent,
            'celebrity_order_percent'           => $celebrity_order_percent,
            'reissue_order_percent'             => $reissue_order_percent,
            'fill_post_order_percent'           => $fill_post_order_percent,
            'usd_order_num'                     => $usd_order_num,
            'usd_order_money'                   => $usd_order_money,
            'usd_order_average_amount'          => $usd_order_average_amount,
            'usd_order_percent'                 => $usd_order_percent,
            'cad_order_num'                     => $cad_order_num,
            'cad_order_money'                   => $cad_order_money,
            'cad_order_average_amount'          => $cad_order_average_amount,
            'cad_order_percent'                 => $cad_order_percent,
            'aud_order_num'                     => $aud_order_num,
            'aud_order_money'                   => $aud_order_money,
            'aud_order_average_amount'          => $aud_order_average_amount,
            'aud_order_percent'                 => $aud_order_percent,
            'eur_order_num'                     => $eur_order_num,
            'eur_order_money'                   => $eur_order_money,
            'eur_order_average_amount'          => $eur_order_average_amount,
            'eur_order_percent'                 => $eur_order_percent,
            'gbp_order_num'                     => $gbp_order_num,
            'gbp_order_money'                   => $gbp_order_money,
            'gbp_order_average_amount'          => $gbp_order_average_amount,
            'gbp_order_percent'                 => $gbp_order_percent,
            'order_status'                      => $order_status_arr,
            'base_shipping_amount'              => $all_shipping_amount_arr,
            'frame_sku_money'                   => $frame_money,
            'frame_sales_num'                   => $frame_sales_num,
            'frame_avg_money'                   => $frame_avg_money,
            'decoration_money'                  => $decoration_money,
            'decoration_sales_num'              => $decoration_sales_num,
            'decoration_avg_money'              => $decoration_avg_money,
            'frame_onsales_num'                 => $frame_onsales_num,
            'decoration_onsales_num'            => $decoration_onsales_num
        ];
    }
}