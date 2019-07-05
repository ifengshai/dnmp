<?php

namespace app\admin\model\saleAfterManage;

use think\Model;
use think\Db;


class SaleAfterTask extends Model
{

    

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'sale_after_task';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
    ];
    //关联模型
    public function saleAfterIssue()
    {
        return $this->belongsTo('sale_after_issue', 'problem_id')->setEagerlyType(0);
    }
    public function getOrderPlatformList()
    {
        //return config('site.order_platform');
        return [0=>'请选择',1=>'zeelool站',2=>'Voogueme站',3=>'nihao',4=>'amazon',5=>'App'];
    }

    public function getOrderStatusList()
    {
        //return config('site.order_status');
        return [0=>'未付款',1=>'已付款'];
    }
    //优先级返回数据
    public function getPrtyIdList()
    {
        return [1=>'高',2=>'中',3=>'低'];
    }
    //获取选项卡列表
    public function getTabList()
    {
        return [
            ['name'=>'我创建的任务','field'=>'create_person','value'=>session('admin.username')],
            ['name'=>'我的任务','field'=>'rep_id','value'=>session('admin.id')],
        ];
    }
    //获取解决方案列表
    public function getSolveScheme()
    {
      return  [
          0=>"请选择",
          1=>"部分退款",
          2=>"退全款",
          3=>"补发",
          4=>"加钱补发",
          5=>"退款+补发",
          6=>"折扣买新",
          7=>"退货"
      ];
    }
    /***
     * 根据订单平台和订单号获取订单和订单购买的商品信息
     * @param $ordertype
     * @param $order_number
     * @return array|bool|false|\PDOStatement|string|Model
     */
    public function getOrderInfo($ordertype,$order_number)
    {
        switch ($ordertype){
            case 1:
                $db = 'database.db_zeelool';
                break;
            case 2:
                $db = 'database.db_voogueme';
                break;
            default:
                return false;
                break;
        }
        $result = Db::connect($db)->table('sales_flat_order')->where('increment_id','=',$order_number)->field('entity_id,status,store_id,increment_id,customer_email,customer_firstname,customer_lastname,total_item_count')->find();
        if(!$result){
            return false;
        }
        $item = Db::connect($db)->table('sales_flat_order_item')->where('order_id','=',$result['entity_id'])->field('item_id,name,sku,qty_ordered,product_options')->select();
        if(!$item){
            return false;
        }
        $arr = [];
        foreach($item as $key=> $val){
            $arr[$key]['item_id'] = $val['item_id'];
            $arr[$key]['name']    = $val['name'];
            $arr[$key]['sku']     = $val['sku'];
            $arr[$key]['qty_ordered']     = $val['qty_ordered'];
            $tmp_product_options = unserialize($val['product_options']);
            $arr[$key]['index_type'] = $tmp_product_options['info_buyRequest']['tmplens']['index_type'];
            $arr[$key]['coatiing_name'] = isset($tmp_product_options['info_buyRequest']['tmplens']['coatiing_name']) ? $tmp_product_options['info_buyRequest']['tmplens']['coatiing_name'] : "";
            $tmp_prescription_params = $tmp_product_options['info_buyRequest']['tmplens']['prescription'];
            if(!empty($tmp_prescription_params)){
                $tmp_prescription_params = explode("&", $tmp_prescription_params);
                $tmp_lens_params = array();
                foreach ($tmp_prescription_params as $tmp_key => $tmp_value) {
                    $arr_value = explode("=", $tmp_value);
                    $tmp_lens_params[$arr_value[0]] = $arr_value[1];
                }
                $arr[$key]['prescription_type'] = $tmp_lens_params['prescription_type'];
                $arr[$key]['od_sph']   = isset($tmp_lens_params['od_sph']) ? $tmp_lens_params['od_sph'] : '';
                $arr[$key]['od_cyl']   = isset($tmp_lens_params['od_cyl']) ? $tmp_lens_params['od_cyl'] : '';
                $arr[$key]['od_axis']  = isset($tmp_lens_params['od_axis']) ? $tmp_lens_params['od_axis'] : '';
                if($ordertype<=2){
                    $arr[$key]['od_add']   = isset($tmp_lens_params['os_add']) ? $tmp_lens_params['os_add'] : '';
                    $arr[$key]['os_add']   = isset($tmp_lens_params['od_add']) ? $tmp_lens_params['od_add'] : '';
                }else{
                    $arr[$key]['od_add']   = isset($tmp_lens_params['od_add']) ? $tmp_lens_params['od_add'] : '';
                    $arr[$key]['os_add']   = isset($tmp_lens_params['os_add']) ? $tmp_lens_params['os_add'] : '';
                }

                $arr[$key]['os_sph']   = isset($tmp_lens_params['os_sph']) ? $tmp_lens_params['os_sph'] : '';
                $arr[$key]['os_cyl']   = isset($tmp_lens_params['os_cyl']) ? $tmp_lens_params['os_cyl'] : '';
                $arr[$key]['os_axis']  = isset($tmp_lens_params['os_axis']) ? $tmp_lens_params['os_axis'] : '';
                if(isset($tmp_lens_params['pdcheck']) && $tmp_lens_params['pdcheck'] == 'on'){  //双pd值
                    $arr[$key]['pd_r'] = isset($tmp_lens_params['pd_r']) ? $tmp_lens_params['pd_r'] : '';
                    $arr[$key]['pd_l'] = isset($tmp_lens_params['pd_l']) ? $tmp_lens_params['pd_l'] : '';
                }else{
                    $arr[$key]['pd_r'] = $arr[$key]['pd_l'] = isset($tmp_lens_params['pd']) ? $tmp_lens_params['pd'] : '';
                }
                if(isset($tmp_lens_params['prismcheck']) && $tmp_lens_params['prismcheck'] == 'on'){ //存在斜视
                    $arr[$key]['od_bd'] = isset($tmp_lens_params['od_bd']) ? $tmp_lens_params['od_bd'] : '';
                    $arr[$key]['od_pv'] = isset($tmp_lens_params['od_pv']) ? $tmp_lens_params['od_pv'] : '';
                    $arr[$key]['os_pv'] = isset($tmp_lens_params['os_pv']) ? $tmp_lens_params['os_pv'] : '';
                    $arr[$key]['os_bd'] = isset($tmp_lens_params['os_bd']) ? $tmp_lens_params['os_bd'] : '';
                    $arr[$key]['od_pv_r'] = isset($tmp_lens_params['od_pv_r']) ? $tmp_lens_params['od_pv_r'] : '';
                    $arr[$key]['od_bd_r'] = isset($tmp_lens_params['od_bd_r']) ? $tmp_lens_params['od_bd_r'] : '';
                    $arr[$key]['os_pv_r'] = isset($tmp_lens_params['os_pv_r']) ? $tmp_lens_params['os_pv_r'] : '';
                    $arr[$key]['os_bd_r'] = isset($tmp_lens_params['os_bd_r']) ? $tmp_lens_params['os_bd_r'] : '';
                }else{
                    $arr[$key]['od_bd'] = "";
                    $arr[$key]['od_pv'] = "";
                    $arr[$key]['os_pv'] = "";
                    $arr[$key]['os_bd'] = "";
                    $arr[$key]['od_pv_r'] = "";
                    $arr[$key]['od_bd_r'] = "";
                    $arr[$key]['os_pv_r'] = "";
                    $arr[$key]['os_bd_r'] = "";
                }
            }else{
                $arr[$key]['prescription_type'] = "";
                $arr[$key]['od_sph']   = "";
                $arr[$key]['od_cyl']   = "";
                $arr[$key]['od_axis']   = "";
                $arr[$key]['od_add']   = "";
                $arr[$key]['os_sph']   = "";
                $arr[$key]['os_cyl']   = "";
                $arr[$key]['os_axis']   = "";
                $arr[$key]['os_add']   = "";
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





}
