<?php
namespace app\admin\controller\datacenter\operationanalysis\operationkanban;
use think\Db;
use app\common\controller\Backend;
use app\admin\model\platformmanage\MagentoPlatform;
class Operationalreport extends Backend{
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
                $map['created_at'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
            } else {
                $map['created_at'] = ['between', [date('Y-m-d 00:00:00', strtotime('-7 day')), date('Y-m-d H:i:s', time())]];
            }
            $order_platform = $params['platform'];
            if(4<=$order_platform){
                return $this->error('该平台暂时没有数据');
            }
            $result = $this->platformOrderInfo(2,$map);
            return $this->success('ok','',$result);
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
    public function platformOrderInfo($platform,$map)
    {

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
        $where = " status in ('processing','complete','creditcard_proccessing','free_processing')";
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
        $usd_order_average_amount   = @round($usd_order_money/$usd_order_num,2);
        //CAD订单数量
        $cad_order_num              = $model->table('sales_flat_order')->where($where)->where(['order_currency_code'=>'CAD'])->where($map)->count('*');
        //CAD订单金额
        $cad_order_money            = $model->table('sales_flat_order')->where($where)->where(['order_currency_code'=>'CAD'])->where($map)->sum('base_grand_total');
        //CAD订单的平均金额
        $cad_order_average_amount   = @round($cad_order_money/$cad_order_num,2);
        //AUD订单数量
        $aud_order_num              = $model->table('sales_flat_order')->where($where)->where(['order_currency_code'=>'AUD'])->where($map)->count('*');
        //AUD订单金额
        $aud_order_money            = $model->table('sales_flat_order')->where($where)->where(['order_currency_code'=>'AUD'])->where($map)->sum('base_grand_total');
        //AUD订单平均金额
        $aud_order_average_amount   = @round($aud_order_money/$aud_order_num,2);
        //EUR订单数量
        $eur_order_num              = $model->table('sales_flat_order')->where($where)->where(['order_currency_code'=>'EUR'])->where($map)->count('*');
        //EUR订单金额
        $eur_order_money            = $model->table('sales_flat_order')->where($where)->where(['order_currency_code'=>'EUR'])->where($map)->sum('base_grand_total');
        //EUR订单平均金额
        $eur_order_average_amount   = @round($eur_order_money/$eur_order_num,2);
        //GBP订单数量
        $gbp_order_num              = $model->table('sales_flat_order')->where($where)->where(['order_currency_code'=>'GBP'])->where($map)->count('*');
        //GBP订单金额
        $gbp_order_money            = $model->table('sales_flat_order')->where($where)->where(['order_currency_code'=>'GBP'])->where($map)->sum('base_grand_total');
        //GBP订单平均金额
        $gbp_order_average_amount   = @round($gbp_order_money/$gbp_order_num,2);
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
        ];
    }
}