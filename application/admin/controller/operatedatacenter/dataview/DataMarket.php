<?php

namespace app\admin\controller\operatedatacenter\dataview;

use app\admin\model\OrderStatistics;
use app\admin\model\platformmanage\MagentoPlatform;
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
        $this->model = new \app\admin\model\OperationAnalysis;
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
    }
    /**
     *定义时间日志
     */
    public function date()
    {
        $date = [
            1 => '过去30天',
            2 => '过去14天',
            3 => '过去7天',
            4 => '昨天',
            5 => '今天'
        ];
        return $date;
    }
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $platform = $this->magentoplatform->getNewAuthSite();
        foreach ($platform as $k=>$v){
            if(in_array($k,[5,8,13,14])){
                unset($platform[$k]);
            }
        }
        if(empty($platform)){
            $this->error('您没有权限访问','general/profile?ref=addtabs');
        }
        $result = (new \app\admin\controller\elasticsearch\operate\DataMarket())->getCharts();
        $xData = $result['xData'];
        $yData = $result['yData'];
        $this->view->assign(compact('web_site', 'time_str', 'platform', 'yData', 'xData'));

        return $this->view->fetch('elasticsearch/operate/data_market/index');
    }
    /***
     * 异步获取仪表盘首页上部分数据
     */
    public function async_data($order_platform = null)
    {
        if ($this->request->isAjax()) {
            if (!$order_platform) {
                return   $this->error('参数不存在，请重新尝试');
            }
            if (100 != $order_platform) {
                $data = $this->model->getList($order_platform);
            } else {
                $data = $this->model->getAllList();
            }
            if (false == $data) {
                return $this->error('没有该平台数据,请重新选择');
            }

            return $this->success('', '', $data, 0);
        }
    }
    /**
     * 异步获取仪表盘首页下部分数据
     *
     * @Description created by lsw
     * @author lsw
     * @since 2020/03/12 15:37:42
     * @param [type] $id
     * @return void
     */
    public function async_bottom_data($create_time=null)
    {
        if ($this->request->isAjax()) {
            if ($create_time) {
                $time = explode(' ', $create_time);
                $map['created_at'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
            } else {
                $map['created_at'] = ['between', [date('Y-m-d 00:00:00', strtotime('-7 day')), date('Y-m-d H:i:s', time())]];
            }
            $data = $this->get_platform_data($map);
            if (false == $data) {
                return $this->error('没有对应的时间数据，请重新尝试');
            }
            return $this->success('', '', $data, 0);
        }
    }
    public function get_platform_data($map)
    {
        $arr = Cache::get('Dashboard_get_platform_data_'.md5(serialize($map)));
        if ($arr) {
            return $arr;
        }
        $zeelool_model 	= Db::connect('database.db_zeelool');
        $voogueme_model = Db::connect('database.db_voogueme');
        $nihao_model	= Db::connect('database.db_nihao');
        $meeloog_model	= Db::connect('database.db_meeloog');
        $zeelool_es_model = Db::connect('database.db_zeelool_es');
        $zeelool_de_model = Db::connect('database.db_zeelool_de');
        $zeelool_jp_model = Db::connect('database.db_zeelool_jp');
        $zeelool_model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $voogueme_model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $nihao_model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $meeloog_model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $zeelool_es_model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $zeelool_de_model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $zeelool_jp_model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $status['status']  = ['in', ['processing', 'complete', 'free_processing','paypal_canceled_reversal','paypal_reversed','delivered']];
        $status['order_type'] = 1;
        $pc['store_id']    = 1;
        $wap['store_id']   = ['in',[2,4]];
        $app['store_id']   = 5;
        $android['store_id']   = 6;
        //zeelool中pc销售额
        $zeelool_pc_sales_money  	= $zeelool_model->table('sales_flat_order')->where($pc)->where($status)->where($map)->sum('base_grand_total');
        //zeelool中wap销售额
        $zeelool_wap_sales_money 	= $zeelool_model->table('sales_flat_order')->where($wap)->where($status)->where($map)->sum('base_grand_total');
        //zeelool中IOS销售额
        $zeelool_app_sales_money 	= $zeelool_model->table('sales_flat_order')->where($app)->where($status)->where($map)->sum('base_grand_total');
        //zeelool中Android销售额
        $zeelool_android_sales_money 	= $zeelool_model->table('sales_flat_order')->where($android)->where($status)->where($map)->sum('base_grand_total');

        //zeelool中pc支付成功数
        $zeelool_pc_sales_num	 	= $zeelool_model->table('sales_flat_order')->where($pc)->where($status)->where($map)->count('*');
        //zeelool中wap支付成功数
        $zeelool_wap_sales_num	 	= $zeelool_model->table('sales_flat_order')->where($wap)->where($status)->where($map)->count('*');
        //zeelool中IOS支付成功数
        $zeelool_app_sales_num	 	= $zeelool_model->table('sales_flat_order')->where($app)->where($status)->where($map)->count('*');
        //zeelool中Android支付成功数
        $zeelool_android_sales_num	 	= $zeelool_model->table('sales_flat_order')->where($android)->where($status)->where($map)->count('*');

        if($zeelool_pc_sales_num>0){
            //zeelool pc端客单价
            $zeelool_pc_unit_price   	= round(($zeelool_pc_sales_money/$zeelool_pc_sales_num), 2);
        }else{
            $zeelool_pc_unit_price  	= 0;
        }
        //zeelool wap客单价
        if($zeelool_wap_sales_num>0){
            $zeelool_wap_unit_price  	= round(($zeelool_wap_sales_money/$zeelool_wap_sales_num), 2);
        }else{
            $zeelool_wap_unit_price     = 0;
        }
        //zeelool IOS端客单价
        if($zeelool_app_sales_num>0){
            $zeelool_app_unit_price 	= round(($zeelool_app_sales_money/$zeelool_app_sales_num), 2);
        }else{
            $zeelool_app_unit_price		= 0;
        }
        //zeelool Android端客单价
        if($zeelool_android_sales_num>0){
            $zeelool_android_unit_price 	= round(($zeelool_android_sales_money/$zeelool_android_sales_num), 2);
        }else{
            $zeelool_android_unit_price		= 0;
        }
        //voogueme中pc销售额
        $voogueme_pc_sales_money 	= $voogueme_model->table('sales_flat_order')->where($pc)->where($status)->where($map)->sum('base_grand_total');
        //voogueme中wap销售额
        $voogueme_wap_sales_money	= $voogueme_model->table('sales_flat_order')->where($wap)->where($status)->where($map)->sum('base_grand_total');
        //voogueme中pc支付成功数
        $voogueme_pc_sales_num		= $voogueme_model->table('sales_flat_order')->where($pc)->where($status)->where($map)->count('*');
        //voogueme中wap支付成功数
        $voogueme_wap_sales_num	 	= $voogueme_model->table('sales_flat_order')->where($wap)->where($status)->where($map)->count('*');
        //voogueme pc端客单价
        if($voogueme_pc_sales_num>0){
            $voogueme_pc_unit_price   	= round(($voogueme_pc_sales_money/$voogueme_pc_sales_num), 2);
        }else{
            $voogueme_pc_unit_price   	= 0;
        }
        //voogueme wap客单价
        if($voogueme_wap_sales_num>0){
            $voogueme_wap_unit_price  	= round(($voogueme_wap_sales_money/$voogueme_wap_sales_num), 2);
        }else{
            $voogueme_wap_unit_price  	= 0;
        }

        //nihao中pc销售额
        $nihao_pc_sales_money 		= $nihao_model->table('sales_flat_order')->where($pc)->where($status)->where($map)->sum('base_grand_total');
        //nihao中wap销售额
        $nihao_wap_sales_money		= $nihao_model->table('sales_flat_order')->where($wap)->where($status)->where($map)->sum('base_grand_total');
        //nihao中pc支付成功数
        $nihao_pc_sales_num			= $nihao_model->table('sales_flat_order')->where($pc)->where($status)->where($map)->count('*');
        //nihao中wap支付成功数
        $nihao_wap_sales_num	 	= $nihao_model->table('sales_flat_order')->where($wap)->where($status)->where($map)->count('*');
        //nihao pc端客单价
        if($nihao_pc_sales_num>0){
            $nihao_pc_unit_price   	= round(($nihao_pc_sales_money/$nihao_pc_sales_num), 2);
        }else{
            $nihao_pc_unit_price    = 0;
        }
        //nihao wap客单价
        if($nihao_wap_sales_num>0){
            $nihao_wap_unit_price   = round(($nihao_wap_sales_money/$nihao_wap_sales_num), 2);
        }else{
            $nihao_wap_unit_price   = 0;
        }


        //meeloog中pc销售额
        $meeloog_pc_sales_money 	= $meeloog_model->table('sales_flat_order')->where($pc)->where($status)->where($map)->sum('base_grand_total');
        //meeloog中wap销售额
        $meeloog_wap_sales_money	= $meeloog_model->table('sales_flat_order')->where($wap)->where($status)->where($map)->sum('base_grand_total');
        //meeloog中pc支付成功数
        $meeloog_pc_sales_num		= $meeloog_model->table('sales_flat_order')->where($pc)->where($status)->where($map)->count('*');
        //meeloog中wap支付成功数
        $meeloog_wap_sales_num	 	= $meeloog_model->table('sales_flat_order')->where($wap)->where($status)->where($map)->count('*');
        //meeloog pc端客单价
        if($meeloog_pc_sales_num>0){
            $meeloog_pc_unit_price  = round(($meeloog_pc_sales_money/$meeloog_pc_sales_num), 2);
        }else{
            $meeloog_pc_unit_price  = 0;
        }
        //meeloog wap客单价
        if($meeloog_wap_sales_num>0){
            $meeloog_wap_unit_price = round(($meeloog_wap_sales_money/$meeloog_wap_sales_num), 2);
        }else{
            $meeloog_wap_unit_price = 0;
        }

        //zeelool_es中pc销售额
        $zeelool_es_pc_sales_money 	= $zeelool_es_model->table('sales_flat_order')->where($pc)->where($status)->where($map)->sum('base_grand_total');
        //zeelool_es中wap销售额
        $zeelool_es_wap_sales_money	= $zeelool_es_model->table('sales_flat_order')->where($wap)->where($status)->where($map)->sum('base_grand_total');
        //zeelool_es中pc支付成功数
        $zeelool_es_pc_sales_num    = $zeelool_es_model->table('sales_flat_order')->where($pc)->where($status)->where($map)->count('*');
        //zeelool_es中wap支付成功数
        $zeelool_es_wap_sales_num	= $zeelool_es_model->table('sales_flat_order')->where($wap)->where($status)->where($map)->count('*');
        //meeloog pc端客单价
        if($zeelool_es_pc_sales_num>0){
            $zeelool_es_pc_unit_price  = round(($zeelool_es_pc_sales_money/$zeelool_es_pc_sales_num), 2);
        }else{
            $zeelool_es_pc_unit_price  = 0;
        }
        //zeelool_es wap客单价
        if($zeelool_es_wap_sales_num>0){
            $zeelool_es_wap_unit_price = round(($zeelool_es_wap_sales_money/$zeelool_es_wap_sales_num), 2);
        }else{
            $zeelool_es_wap_unit_price = 0;
        }

        //zeelool_de中pc销售额
        $zeelool_de_pc_sales_money 	= $zeelool_de_model->table('sales_flat_order')->where($pc)->where($status)->where($map)->sum('base_grand_total');
        //zeelool_de中wap销售额
        $zeelool_de_wap_sales_money	= $zeelool_de_model->table('sales_flat_order')->where($wap)->where($status)->where($map)->sum('base_grand_total');
        //zeelool_de中pc支付成功数
        $zeelool_de_pc_sales_num    = $zeelool_de_model->table('sales_flat_order')->where($pc)->where($status)->where($map)->count('*');
        //zeelool_de中wap支付成功数
        $zeelool_de_wap_sales_num	= $zeelool_de_model->table('sales_flat_order')->where($wap)->where($status)->where($map)->count('*');
        //meeloog pc端客单价
        if($zeelool_de_pc_sales_num>0){
            $zeelool_de_pc_unit_price  = round(($zeelool_de_pc_sales_money/$zeelool_de_pc_sales_num), 2);
        }else{
            $zeelool_de_pc_unit_price  = 0;
        }
        //zeelool_de wap客单价
        if($zeelool_de_wap_sales_num>0){
            $zeelool_de_wap_unit_price = round(($zeelool_de_wap_sales_money/$zeelool_de_wap_sales_num), 2);
        }else{
            $zeelool_de_wap_unit_price = 0;
        }

        //zeelool_jp中pc销售额
        $zeelool_jp_pc_sales_money 	= $zeelool_jp_model->table('sales_flat_order')->where($pc)->where($status)->where($map)->sum('base_grand_total');
        //zeelool_jp中wap销售额
        $zeelool_jp_wap_sales_money	= $zeelool_jp_model->table('sales_flat_order')->where($wap)->where($status)->where($map)->sum('base_grand_total');
        //zeelool_jp中pc支付成功数
        $zeelool_jp_pc_sales_num    = $zeelool_jp_model->table('sales_flat_order')->where($pc)->where($status)->where($map)->count('*');
        //zeelool_jp中wap支付成功数
        $zeelool_jp_wap_sales_num	= $zeelool_jp_model->table('sales_flat_order')->where($wap)->where($status)->where($map)->count('*');
        //zeelool_jp中pc端客单价
        if($zeelool_jp_pc_sales_num>0){
            $zeelool_jp_pc_unit_price  = round(($zeelool_jp_pc_sales_money/$zeelool_jp_pc_sales_num), 2);
        }else{
            $zeelool_jp_pc_unit_price  = 0;
        }
        //zeelool_jp wap客单价
        if($zeelool_jp_wap_sales_num>0){
            $zeelool_jp_wap_unit_price = round(($zeelool_jp_wap_sales_money/$zeelool_jp_wap_sales_num), 2);
        }else{
            $zeelool_jp_wap_unit_price = 0;
        }
        $arr = [
            'zeelool_pc_sales_money' 	=> $zeelool_pc_sales_money ?:0,
            'zeelool_wap_sales_money' 	=> $zeelool_wap_sales_money ?:0,
            'zeelool_app_sales_money' 	=> $zeelool_app_sales_money ?:0,
            'zeelool_android_sales_money' 	=> $zeelool_android_sales_money ?:0,
            'zeelool_pc_sales_num' 		=> $zeelool_pc_sales_num ?:0,
            'zeelool_wap_sales_num'		=> $zeelool_wap_sales_num ?:0,
            'zeelool_app_sales_num' 	=> $zeelool_app_sales_num ?:0,
            'zeelool_android_sales_num' 	=> $zeelool_android_sales_num ?:0,
            'zeelool_pc_unit_price' 	=> $zeelool_pc_unit_price ?:0,
            'zeelool_wap_unit_price' 	=> $zeelool_wap_unit_price ?:0,
            'zeelool_app_unit_price' 	=> $zeelool_app_unit_price ?:0,
            'zeelool_android_unit_price' 	=> $zeelool_android_unit_price ?:0,
            'voogueme_pc_sales_money' 	=> $voogueme_pc_sales_money ?:0,
            'voogueme_wap_sales_money' 	=> $voogueme_wap_sales_money ?:0,
            'voogueme_pc_sales_num' 	=> $voogueme_pc_sales_num ?:0,
            'voogueme_wap_sales_num' 	=> $voogueme_wap_sales_num ?:0,
            'voogueme_pc_unit_price' 	=> $voogueme_pc_unit_price ?:0,
            'voogueme_wap_unit_price' 	=> $voogueme_wap_unit_price ?:0,
            'nihao_pc_sales_money' 		=> $nihao_pc_sales_money ?:0,
            'nihao_wap_sales_money' 	=> $nihao_wap_sales_money ?:0,
            'nihao_pc_sales_num' 		=> $nihao_pc_sales_num ?:0,
            'nihao_wap_sales_num' 		=> $nihao_wap_sales_num ?:0,
            'nihao_pc_unit_price' 		=> $nihao_pc_unit_price ?:0,
            'nihao_wap_unit_price' 		=> $nihao_wap_unit_price ?:0,
            'meeloog_pc_sales_money' 	=> $meeloog_pc_sales_money ?:0,
            'meeloog_wap_sales_money' 	=> $meeloog_wap_sales_money ?:0,
            'meeloog_pc_sales_num' 		=> $meeloog_pc_sales_num ?:0,
            'meeloog_wap_sales_num' 	=> $meeloog_wap_sales_num ?:0,
            'meeloog_pc_unit_price' 	=> $meeloog_pc_unit_price ?:0,
            'meeloog_wap_unit_price' 	=> $meeloog_wap_unit_price ?:0,
            'zeelool_es_pc_sales_money' => $zeelool_es_pc_sales_money ?:0,
            'zeelool_es_wap_sales_money' =>$zeelool_es_wap_sales_money ?:0,
            'zeelool_es_pc_sales_num' 	=> $zeelool_es_pc_sales_num ?:0,
            'zeelool_es_wap_sales_num' 	=> $zeelool_es_wap_sales_num ?:0,
            'zeelool_es_pc_unit_price' 	=> $zeelool_es_pc_unit_price ?:0,
            'zeelool_es_wap_unit_price' => $zeelool_es_wap_unit_price ?:0,
            'zeelool_de_pc_sales_money' => $zeelool_de_pc_sales_money ?:0,
            'zeelool_de_wap_sales_money' =>$zeelool_de_wap_sales_money ?:0,
            'zeelool_de_pc_sales_num' 	=> $zeelool_de_pc_sales_num ?:0,
            'zeelool_de_wap_sales_num' 	=> $zeelool_de_wap_sales_num ?:0,
            'zeelool_de_pc_unit_price' 	=> $zeelool_de_pc_unit_price ?:0,
            'zeelool_de_wap_unit_price' => $zeelool_de_wap_unit_price ?:0,
            'zeelool_jp_pc_sales_money' => $zeelool_jp_pc_sales_money ?:0,
            'zeelool_jp_wap_sales_money' =>$zeelool_jp_wap_sales_money ?:0,
            'zeelool_jp_pc_sales_num' 	=> $zeelool_jp_pc_sales_num ?:0,
            'zeelool_jp_wap_sales_num' 	=> $zeelool_jp_wap_sales_num ?:0,
            'zeelool_jp_pc_unit_price' 	=> $zeelool_jp_pc_unit_price ?:0,
            'zeelool_jp_wap_unit_price' => $zeelool_jp_wap_unit_price ?:0,
        ];
        Cache::set('Dashboard_get_platform_data_'.md5(serialize($map)), $arr, 7200);
        return $arr;
    }
}
