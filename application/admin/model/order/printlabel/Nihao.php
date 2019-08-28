<?php

namespace app\admin\model\order\printlabel;

use think\Model;
use think\Db;


class Nihao extends Model
{

    

    //数据库
    // protected $connection = 'database';
    protected $connection = 'database.db_nihao_online';

    
    // 表名
    protected $table = 'sales_flat_order';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    
    /**
     * 获取订单详情 nihao站
     * @param $ordertype 站点
     * @param $entity_id 订单id
     * @return array
     */
    public function getOrderDetail($ordertype, $entity_id)
    {
        switch ($ordertype) {
            case 1:
                $db = 'database.db_zeelool_online';
                break;
            case 2:
                $db = 'database.db_voogueme_online';
                break;
            case 3:
                $db = 'database.db_nihao_online';
                break;
            default:
                return false;
                break;
        }
        $map['order_id'] = $entity_id;
        $result = Db::connect($db)
            ->field('sku,name,qty_ordered,custom_prescription,original_price,price,discount_amount,product_options')
            ->table('sales_flat_order_item')
            ->where($map)
            ->select();
        foreach ($result as $k => &$v) {
            $v['product_options'] = unserialize($v['product_options']);
        }
        unset($v);
        dump($result);die;

        if (!$result) {
            return false;
        }
        return $result;
    }

    
    //eaysUI 订单详情  弹出框
    public function by_increment_id()
    {
        // echo 'by_increment_id';
        $increment_id = trim(I('increment_id'));
        if ($increment_id) {
            $Model = new \Think\Model();

            $querySql = "select sfo.increment_id,sfoi.item_id,sfoi.order_id ,sfoi.sku,sfoi.name,sfoi.qty_ordered,sfoi.product_options 
from sales_flat_order_item sfoi 
LEFT JOIN sales_flat_order sfo on sfo.entity_id=sfoi.order_id
where sfo.increment_id='$increment_id';";
            // dump($querySql);
            $order_item_list = $Model->query($querySql);
            
            foreach ($order_item_list as $key => $processing_value) {
                $final_print = array();
                $product_options = unserialize($processing_value['product_options']);
               
                $final_print['second_name'] = $product_options['info_buyRequest']['tmplens']['second_name'];
                $final_print['third_name'] = $product_options['info_buyRequest']['tmplens']['third_name'];
                $final_print['four_name'] = $product_options['info_buyRequest']['tmplens']['four_name'];
                $final_print['zsl'] = $product_options['info_buyRequest']['tmplens']['zsl'];

                $prescription_params = json_decode($product_options['info_buyRequest']['tmplens']['prescription'], true);

              
                $final_print = array_merge($prescription_params, $final_print);
                // dump($final_print);
                //最外层表格
                $output .= "<tr><td>";
                $output .= "<table class='altrowstable'  style='margin:0 auto;margin:30px;width:90%;'>";
                $output .= "<tr>";
                $output .= "<tr><td>订单号：</td><td>" . $processing_value['increment_id'] . "</td></tr>";
                $output .= "<tr><td>产品名称：</td><td>" . $processing_value['name'] . "</td></tr>";
                $output .= "<tr><td>SKU：</td><td>" . $processing_value['sku'] . ' -> ' . SKUHelper::sku_conversion($processing_value['sku']) . "</td></tr>";
                $output .= "<tr><td>数量：</td><td>" . (int)$processing_value['qty_ordered'] . "</td></tr>";
                $output .= "<tr><td>处方类型：</td><td>" . $final_print['prescription_type'] . "</td></tr>";
                $output .= "<tr><td>基片类型：</td><td>" . $final_print['second_name'] . "</td></tr>";
                $output .= "<tr><td>镜片类型：</td><td>" . $final_print['third_name'] . "</td></tr>";
                $output .= "<tr><td>折射率：</td><td>" . $final_print['zsl'] . "</td></tr>";
                $output .= "<tr><td>镀膜类型：</td><td>" . $final_print['four_name'] . "</td></tr>";
                if ($final_print['information']) {
                    $final_print['information'] = urldecode($final_print['information']);
                    $final_print['information'] = str_replace('+', ' ', $final_print['information']);
                    $output .= "<tr><td>客户留言：</td><td style='width:500px;max-width:500px;'><span style='color:#C23531;font-weight:bold;'>" . $final_print['information'] . "</span></td></tr>";
                }
                $output .= "</table>";
                $output .= "</td><td>";


                //处理ADD  当ReadingGlasses时 是 双PD值
                if ($final_print['prescription_type'] == 'Reading Glasses' && strlen($final_print['os_add']) > 0 && strlen($final_print['od_add']) > 0) {
                    // echo '双PD值';
                    $od_add = "<td>" . $final_print['od_add'] . "</td> ";
                    $os_add = "<td>" . $final_print['os_add'] . "</td> ";
                } else {
                    // echo '单ADD值';
                    $od_add = "<td rowspan='2'>" . $final_print['od_add'] . "</td>";
                    $os_add = "";
                }


                if ($final_print['pd_r'] && $final_print['pd_l']) {
                    $output .= "<table class='altrowstable'  border='0' cellspacing='0' cellpadding='0' class='addpro' style='margin:0px auto;margin-top:18px;width:90%;' >
                        <tbody cellpadding='0'> 
                            <tr class='title'>      
                                <td colspan='10'>处方参数</td>                                 
                            </tr>                            
                            <tr class='title'>      
                                <td></td>  
                                <td>SPH</td>
                                <td>CYL</td>
                                <td>AXI</td>
                                <td>ADD</td>
                                <td>PD</td> 
                            </tr>   
                            <tr>  
                                <td>Right(OD)</td>      
                                <td>" . $final_print['od_sph'] . "</td> 
                                <td>" . $final_print['od_cyl'] . "</td>
                                <td>" . $final_print['od_axis'] . "</td>        
                                $od_add
                                <td >" . $final_print['pd_r'] . "</td>   
                            </tr>
                            <tr>
                                <td>Left(OS)</td> 
                                <td>" . $final_print['os_sph'] . "</td>    
                                <td>" . $final_print['os_cyl'] . "</td>  
                                <td>" . $final_print['os_axis'] . "</td> 
                                $os_add
                                <td>" . $final_print['pd_l'] . "</td>   

                            </tr>
                   
                            </tbody></table>";
                } else {
                    $output .= "<table class='altrowstable' border='0' cellspacing='0' cellpadding='0' class='addpro' style='margin:0 auto;margin-top:18px;width:90%;' >
                        <tbody cellpadding='0'> 
                            <tr class='title'>      
                                <td colspan='10'>处方参数</td>                                 
                            </tr>    
                            <tr class='title'>      
                                <td></td>  
                                <td>SPH</td>
                                <td>CYL</td>
                                <td>AXI</td>
                                <td>ADD</td>
                                <td>PD</td> 
                            </tr>   
                            <tr>  
                                <td>Right(OD)</td>      
                                <td>" . $final_print['od_sph'] . "</td> 
                                <td>" . $final_print['od_cyl'] . "</td>
                                <td>" . $final_print['od_axis'] . "</td>         
                                 $od_add
                                <td rowspan='2'>" . $final_print['pd'] . "</td>   
                            </tr>
                            <tr>
                                <td>Left(OS)</td> 
                                <td>" . $final_print['os_sph'] . "</td>    
                                <td>" . $final_print['os_cyl'] . "</td>  
                                <td>" . $final_print['os_axis'] . "</td> 
                                $os_add

                            </tr>                      
                            </tbody></table>";
                }
                $output .= "</td>";
                $output .= "</tr>";
            }
            $output .= "</table>";

            echo $output;
            // return $output;
            // dump($resultList);
        } else {
            // echo '没有相关记录项';
            return '没有相关记录项';
        }
    }







}
