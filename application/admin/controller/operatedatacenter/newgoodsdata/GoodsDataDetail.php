<?php

namespace app\admin\controller\operatedatacenter\NewGoodsData;

use app\admin\model\platformManage\MagentoPlatform;
use app\common\controller\Backend;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\Controller;
use think\Db;
use think\Request;

class GoodsDataDetail extends Backend
{
    public function _initialize()
    {
        parent::_initialize();
        $this->itemPlatformSku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $this->orderitemoption = new \app\admin\model\order\order\NewOrderItemOption();
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
    }
    /**
     * 订单数据明细页面展示
     *
     * @return \think\Response
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $filter = json_decode($this->request->get('filter'), true);
            $site = $filter['order_platform'] ? $filter['order_platform'] : 1;
            $map['p.platform_type'] = $site;
            if($filter['sku']){
                $map['p.sku'] = $filter['sku'];
            }
            if($filter['type']){
                $map['c.attribute_group_id'] = $filter['type'];
            }
            if($filter['goods_grade']){
                $map['p.grade'] = $filter['goods_grade'];
            }
            unset($filter['order_platform']);
            unset($filter['sku']);
            unset($filter['type']);
            unset($filter['goods_grade']);
            $this->request->get(['filter' => json_encode($filter)]);
            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            $sort = 'p.id';
            $total = $this->itemPlatformSku
                ->alias('p')
                ->join('fa_item i','i.sku=p.sku','left')
                ->join('fa_item_category c','c.id=i.category_id','left')
                ->where($where)
                ->where($map)
                ->count();
            $list = $this->itemPlatformSku
                ->alias('p')
                ->join('fa_item i','i.sku=p.sku','left')
                ->join('fa_item_category c','c.id=i.category_id','left')
                ->alias('p')
                ->where($where)
                ->where($map)
                ->field('c.name,p.sku,c.attribute_group_id,p.outer_sku_status,p.presell_status,p.stock,i.is_spot,p.platform_type,p.platform_sku,p.grade,p.stock_health_status,p.presell_start_time,p.presell_end_time,p.presell_num')
                ->limit($offset,$limit)
                ->order($sort, $order)
                ->select();
            $list = collection($list)->toArray();
            $start = strtotime(date('Y-m-d 00:00:00', strtotime('-30 day')));
            $end = time();
            $nowDate = date('Y-m-d H:i:s');
            foreach ($list as $key=>$value){
                $list[$key]['sku'] = $value['sku'];
                $list[$key]['type'] = $value['attribute_group_id'];
                $list[$key]['goods_type'] = $value['name'];
                if($value['outer_sku_status'] == 1){
                    if($value['stock'] > 0){
                        $list[$key]['status'] = 1;
                    }else{
                        if($value['presell_status'] == 1 && $nowDate >= $value['presell_start_time'] && $nowDate <= $value['presell_end_time']){
                            if($value['presell_num'] > 0){
                                $list[$key]['status'] = 1;
                            }else{
                                $list[$key]['status'] = 2;
                            }
                        }else{
                            $list[$key]['status'] = 2;
                        }
                    }
                }else{
                    $list[$key]['status'] = 3;
                }
                switch ($value['stock_health_status']){
                    case 1:
                        $list[$key]['stock_status'] = '正常';
                        break;
                    case 2:
                        $list[$key]['stock_status'] = '高风险';
                        break;
                    case 3:
                        $list[$key]['stock_status'] = '中风险';
                        break;
                    case 4:
                        $list[$key]['stock_status'] = '低风险';
                        break;
                    case 5:
                        $list[$key]['stock_status'] = '运营新品';
                        break;
                }
                //最近30天的销量
                $orderWhere['payment_time'] = ['between',[$start,$end]];
                $orderWhere['order_type'] = 1;
                $orderWhere['o.status'] = ['in',['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered']];
                $orderWhere['sku'] = ['like',$value['platform_sku'].'%'];
                $orderWhere['o.site'] = $value['platform_type'];
                $list[$key]['sales_num'] = $this->orderitemoption
                    ->alias('i')
                    ->join('fa_order o','i.magento_order_id=o.entity_id')
                    ->where($orderWhere)
                    ->sum('i.qty');
                //获取sku的等级
                $list[$key]['goods_grade'] = $value['grade'];
                $list[$key]['stock'] = $value['stock'];
                $list[$key]['turn_days'] = $list[$key]['sales_num'] ? round($value['stock']/$list[$key]['sales_num'],2) : 0;
                $list[$key]['big_spot_goods'] = $value['is_spot'];
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key=>$val){
            if(!in_array($val['name'],['zeelool','voogueme','nihao','wesee','zeelool_de','zeelool_jp'])){
                unset($magentoplatformarr[$key]);
            }
        }
        $this->view->assign('magentoplatformarr',$magentoplatformarr);
        return $this->view->fetch();
    }

    function filter_by_value ($array, $index, $value){
        if(is_array($array) && count($array)>0)
        {
            foreach(array_keys($array) as $key){
                $temp[$key] = $array[$key][$index];
                if ($temp[$key] == $value){
                    $newarray = $array[$key];
                }
            }
        }
        return $newarray;
    }
    public function export(){
        set_time_limit(0);
        header ( "Content-type:application/vnd.ms-excel" );
        header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", date('Y-m-d-His',time()) ) . ".csv" );//导出文件名

        // 打开PHP文件句柄，php://output 表示直接输出到浏览器
        $fp = fopen('php://output', 'a');
        $site = input('order_platform') ? input('order_platform') : 1;
        $sku = input('sku');
        $type = input('type');
        $goods_grade = input('goods_grade');
        $field = input('field');
        $field_arr = explode(',',$field);
        $field_info = array(
            array(
                'name'  => 'SKU',
                'field' => 'sku',
            ),
            array(
                'name'  => '类别',
                'field' => 'type',
            ),
            array(
                'name'  => '商品类别',
                'field' => 'goods_type',
            ),
            array(
                'name'  => '状态',
                'field' => 'status',
            ),
            array(
                'name'  => '库存状态',
                'field' => 'stock_status',
            ),
            array(
                'name'=>'最近30天销量',
                'field'=>'sales_num',
            ),
            array(
                'name'=>'产品等级',
                'field'=>'goods_grade',
            ),
            array(
                'name'=>'库存量',
                'field'=>'stock',
            ),
            array(
                'name'=>'周转月数',
                'field'=>'turn_days',
            ),
            array(
                'name'=>'大货/现货',
                'field'=>'big_spot_goods',
            )
        );
        $column_name = [];
        // 将中文标题转换编码，否则乱码
        foreach ($field_arr as $i => $v) {
            $title_name = $this->filter_by_value($field_info,'field',$v);
            $field_arr[$i] = iconv('utf-8', 'GB18030', $title_name['name']);
            $column_name[$i] = $v;
        }
        // 将标题名称通过fputcsv写到文件句柄
        fputcsv($fp, $field_arr);
        $map['p.platform_type'] = $site;
        if($sku){
            $map['p.sku'] = $sku;
        }
        if($type){
            $map['c.attribute_group_id'] = $type;
        }
        if($goods_grade){
            $map['p.grade'] = $goods_grade;
        }
        $total_export_count = $this->itemPlatformSku
            ->alias('p')
            ->join('fa_item_category c','c.id=p.category_id','left')
            ->join('fa_item i','i.sku=p.sku','left')
            ->where($map)
            ->count();
        $startTime = strtotime(date('Y-m-d 00:00:00', strtotime('-30 day')));
        $endTime = time();
        $pre_count = 5000;
        $nowDate = date('Y-m-d H:i:s');
        for ($i=0;$i<intval($total_export_count/$pre_count)+1;$i++){
            $start = $i*$pre_count;
            //切割每份数据
            $list = $this->itemPlatformSku
                ->alias('p')
                ->join('fa_item_category c','c.id=p.category_id')
                ->join('fa_item i','i.sku=p.sku')
                ->where($map)
                ->field('c.name,p.sku,c.attribute_group_id,p.outer_sku_status,p.presell_status,p.stock,i.is_spot,p.platform_type,p.platform_sku,p.grade,p.stock_health_status,p.presell_start_time,p.presell_end_time,p.presell_num')
                ->limit($start,$pre_count)
                ->select();
            $list = collection($list)->toArray();
            //整理数据
            foreach ( $list as &$val ) {
                $tmpRow = [];
                if (in_array('sku', $column_name)) {
                    $index = array_keys($column_name, 'sku');
                    $tmpRow[$index[0]] = $val['sku'];
                }
                if (in_array('type', $column_name)) {
                    switch ($val['attribute_group_id']){
                        case 1:
                            $type = '眼镜';
                            break;
                        case 2:
                            $type = '饰品';
                            break;
                    }
                    $index = array_keys($column_name, 'type');
                    $tmpRow[$index[0]] = $type;
                }
                if (in_array('goods_type', $column_name)) {
                    $index = array_keys($column_name, 'goods_type');
                    $tmpRow[$index[0]] = $val['name'];
                }
                if (in_array('status', $column_name)) {
                    if($val['outer_sku_status'] == 1){
                        if($val['stock'] > 0){
                            $status = '上架';
                        }else{
                            if($val['presell_status'] == 1 && $nowDate >= $val['presell_start_time'] && $nowDate <= $val['presell_end_time']){
                                if($val['presell_num'] > 0){
                                    $status = '上架';
                                }else{
                                    $status = '售罄';
                                }
                            }else{
                                $status = '售罄';
                            }
                        }
                    }else{
                        $status = '下架';
                    }
                    $index = array_keys($column_name,'status');
                    $tmpRow[$index[0]] =$status;
                }
                if(in_array('stock_status',$column_name)) {
                    switch ($val['stock_health_status']){
                        case 1:
                            $stock_status = '正常';
                            break;
                        case 2:
                            $stock_status = '高风险';
                            break;
                        case 3:
                            $stock_status = '中风险';
                            break;
                        case 4:
                            $stock_status = '低风险';
                            break;
                        case 5:
                            $stock_status = '运营新品';
                            break;
                    }
                    $index = array_keys($column_name,'stock_status');
                    $tmpRow[$index[0]] =$stock_status;
                }
                if(in_array('sales_num',$column_name) || in_array('turn_days',$column_name)){
                    $orderWhere['payment_time'] = ['between',[$startTime,$endTime]];
                    $orderWhere['order_type'] = 1;
                    $orderWhere['o.status'] = ['in',['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered']];
                    $orderWhere['sku'] = ['like',$val['platform_sku'].'%'];
                    $orderWhere['o.site'] = $val['platform_type'];
                    $sales_num = $this->orderitemoption
                        ->alias('i')
                        ->join('fa_order o','i.magento_order_id=o.entity_id')
                        ->where($orderWhere)
                        ->sum('i.qty');
                    $index1 = array_keys($column_name,'sales_num');
                    if($index1){
                        $tmpRow[$index1[0]] =$sales_num;
                    }
                    $index2 = array_keys($column_name,'turn_days');
                    if($index2){
                        $tmpRow[$index2[0]] =$sales_num ? round($val['stock']/$sales_num,2) : 0;
                    }
                }
                if(in_array('goods_grade',$column_name)){
                    $index = array_keys($column_name,'goods_grade');
                    $tmpRow[$index[0]] =$val['grade'];
                }
                if(in_array('stock',$column_name)){
                    $index = array_keys($column_name,'stock');
                    $tmpRow[$index[0]] =$val['stock'];
                }
                if(in_array('big_spot_goods',$column_name)){
                    switch ($val['is_spot']){
                        case 1:
                            $isSpot = '大货';
                            break;
                        case 2:
                            $isSpot = '现货';
                            break;
                    }
                    $index = array_keys($column_name,'big_spot_goods');
                    $tmpRow[$index[0]] =$isSpot;
                }
                ksort($tmpRow);
                $rows = array();
                foreach ( $tmpRow as $export_obj){
                    $rows[] = iconv('utf-8', 'GB18030', $export_obj);
                }
                fputcsv($fp, $rows);
            }

            // 将已经写到csv中的数据存储变量销毁，释放内存占用
            unset($list);
            ob_flush();
            flush();
        }
        fclose($fp);
    }
}
