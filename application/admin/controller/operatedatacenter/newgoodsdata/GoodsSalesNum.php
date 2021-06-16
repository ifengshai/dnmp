<?php

namespace app\admin\controller\operatedatacenter\NewGoodsData;

use app\common\controller\Backend;
use think\Db;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use fast\Excel;

/**
 * 数据中心
 *
 * @icon fa fa-circle-o
 */
class GoodsSalesNum extends Backend
{

    /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    protected $noNeedRight = ['*'];


    /**
     * Index模型对象
     * @var
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->item = new \app\admin\model\itemmanage\Item;
        $this->order = new \app\admin\model\order\order\NewOrder();
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
        $this->itemPlatformSku = new \app\admin\model\itemmanage\ItemPlatformSku();
    }
    /**
     * 销量排行榜
     *
     * @Description
     * @author wpl
     * @since 2020/03/11 16:14:50 
     * @return void
     */
    public function index()
    {
        $create_time = input('create_time');
        if ($this->request->isAjax()) {
            $filter = json_decode($this->request->get('filter'), true);
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $params['site'] = $filter['order_platform'] ? $filter['order_platform'] : 1;
            //默认当天
            if ($filter['create_time']) {
                $time = explode(' ', $filter['create_time']);
                $start = strtotime($time[0] . ' ' . $time[1]);
                $end = strtotime($time[3] . ' ' . $time[4]);
            } else {
                $start = strtotime(date('Y-m-d 00:00:00', strtotime('-6 day')));
                $end = strtotime(date('Y-m-d H:i:s', time()));
            }
            $map['payment_time'] = ['between', [$start, $end]];
            unset($filter['create_time-operate']);
            unset($filter['create_time']);
            unset($filter['order_platform']);
            $this->request->get(['filter' => json_encode($filter)]);
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $pageArr = array(
                'limit'=>$limit,
                'offset'=>$offset
            );
            //列表
            $result = [];
            //查询对应平台销量
            $info = $this->getOrderSalesNum($params['site'],$map,$pageArr);
            $total = $info['count'];
            $list = $info['data'];
            //查询对应平台商品SKU
            $skus = $this->itemPlatformSku->getWebSkuAll($params['site']);
            $productInfo = $this->item->getSkuInfo();
            $list = $list ?? [];
            $i = 0;
            $nowDate = date('Y-m-d H:i:s');
            foreach ($list as $k => $v) {
                $result[$i]['platformsku'] = $k;
                $result[$i]['sku'] = $skus[trim($k)]['sku'];
                //上架时间
                $shelvesTime = Db::name('sku_shelves_time')
                    ->where(['site'=>$params['site'],'platform_sku'=>$k])
                    ->value('shelves_time');
                $result[$i]['shelves_date'] = date('Y-m-d H:i:s',$shelvesTime);
                $result[$i]['type_name'] = $productInfo[$skus[trim($k)]['sku']]['type_name'];
                $result[$i]['available_stock'] = $skus[trim($k)]['stock'];  //虚拟仓库存
                $result[$i]['sales_num'] = $v;
                //在线天数
                $result[$i]['online_day'] = Db::name('sku_status_dataday')
                    ->where(['site'=>$params['site'],'platform_sku'=>$k,'status'=>1])
                    ->count();
                //日均销量
                $result[$i]['sales_num_day'] = $result[$i]['online_day'] ? round($v/$result[$i]['online_day'],2) : 0;
                //在线状态（实时）
                $stockInfo = $this->itemPlatformSku
                    ->where(['platform_type'=>$params['site'],'platform_sku'=>$k])
                    ->field('stock,outer_sku_status,presell_status,presell_start_time,presell_end_time,presell_num')
                    ->find();
                if($stockInfo['outer_sku_status'] == 1){
                    if($stockInfo['stock'] > 0){
                        $result[$i]['online_status'] = 1;  //在线
                    }else{
                        if($stockInfo['presell_status'] == 1 && $nowDate >= $stockInfo['presell_start_time'] && $nowDate <= $stockInfo['presell_end_time']){
                            if($stockInfo['presell_num'] > 0){
                                $result[$i]['online_status'] = 1;  //在线
                            }else{
                                $result[$i]['online_status'] = 2;  //售罄
                            }
                        }else{
                            $result[$i]['online_status'] = 2;  //售罄
                        }
                    }
                }else{
                    $result[$i]['online_status'] = 3;  //下架
                }
                $i++;
            }
            $data = array("total" => $total, "rows" => $result);
            return json($data);
        }

        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key => $val) {
            if (!in_array($val['name'], ['zeelool', 'voogueme', 'meeloog','wesee','zeelool_de','zeelool_jp','zeelool_fr'])) {
                unset($magentoplatformarr[$key]);
            }
        }
        $this->view->assign('magentoplatformarr', $magentoplatformarr);
        $this->assign('create_time', $create_time);
        return $this->view->fetch();
    }
    public function index1()
    {
        $create_time = input('create_time');
        if ($this->request->isAjax()) {
            $filter = json_decode($this->request->get('filter'), true);
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $params['site'] = $filter['order_platform'] ? $filter['order_platform'] : 1;
            unset($filter['order_platform']);
            $this->request->get(['filter' => json_encode($filter)]);
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $startTime = strtotime(date('Y-m-d 00:00:00', strtotime('-30 day')));  //过去30天时间
            $total = Db::name('sku_shelves_time')
                ->where('shelves_time','>=',$startTime)
                ->where('site',$params['site'])
                ->count();
            $skus = Db::name('sku_shelves_time')
                ->where('shelves_time','>=',$startTime)
                ->where('site',$params['site'])
                ->field('platform_sku,shelves_time,sku')
                ->limit($offset,$limit)
                ->select();
            $list = [];
            $i = 0;
            $nowDate = date('Y-m-d H:i:s');
            foreach($skus as $k=>$value){
                $skuInfo = $this->itemPlatformSku->getSkuInfo($params['site'],$value['platform_sku']);
                $skuTimeWhere['payment_time'] = ['between',[$value['shelves_time'],time()]];
                $skuSalesNum = $this->getOrderSalesNum($params['site'],$skuTimeWhere,[],$value['platform_sku']);

                $list[$i]['platformsku'] = $value['platform_sku'];
                $list[$i]['sku'] = $value['sku'];
                //上架时间
                $list[$i]['shelves_date'] = date('Y-m-d H:i:s',$value['shelves_time']);
                //分类名称
                $list[$i]['type_name'] = $skuInfo['name'];
                $list[$i]['available_stock'] = $skuInfo['stock'];  //虚拟仓库存
                $list[$i]['sales_num'] = $skuSalesNum['data'][$value['platform_sku']];
                //在线天数
                $list[$i]['online_day'] = Db::name('sku_status_dataday')
                    ->where(['site'=>$params['site'],'platform_sku'=>$value['platform_sku'],'status'=>1])
                    ->count();
                //日均销量
                $list[$i]['sales_num_day'] = $list[$i]['online_day'] ? round($list[$i]['sales_num']/$list[$i]['online_day'],2) : 0;
                //在线状态（实时）
                if($skuInfo['outer_sku_status'] == 1){
                    if($skuInfo['stock'] > 0){
                        $list[$i]['online_status'] = 1;  //在线
                    }else{
                        if($skuInfo['presell_status'] == 1 && $nowDate >= $skuInfo['presell_start_time'] && $nowDate <= $skuInfo['presell_end_time']){
                            if($skuInfo['presell_num'] > 0){
                                $list[$i]['online_status'] = 1;  //在线
                            }else{
                                $list[$i]['online_status'] = 2;  //售罄
                            }
                        }else{
                            $list[$i]['online_status'] = 2;  //售罄
                        }
                    }
                }else{
                    $list[$i]['online_status'] = 3;  //下架
                }
                $i++;
            }
            $data = array("total" => $total, "rows" => $list);
            return json($data);
        }

        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key => $val) {
            if (!in_array($val['name'], ['zeelool', 'voogueme', 'meeloog','wesee','zeelool_de','zeelool_jp','zeelool_fr'])) {
                unset($magentoplatformarr[$key]);
            }
        }
        $this->view->assign('magentoplatformarr', $magentoplatformarr);
        $this->assign('create_time', $create_time);
        return $this->view->fetch('operatedatacenter/newgoodsdata/goods_sales_num/index1');
    }

    public function export()
    {
        set_time_limit(0);
        header ( "Content-type:application/vnd.ms-excel" );
        header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", date('Y-m-d-His',time()) ) . ".csv" );//导出文件名
        // 打开PHP文件句柄，php://output 表示直接输出到浏览器
        $fp = fopen('php://output', 'a');
        $time_str = input('time_str');
        $order_platform = input('order_platform') ? input('order_platform') : 1;

        // 将中文标题转换编码，否则乱码
        $field_arr = array(
            '平台SKU','SKU','上架时间','分类','虚拟仓库存','销量','在线天数','日均销量','在售状态（实时）'
        );
        foreach ($field_arr as $i => $v) {
            $field_arr[$i] = iconv('utf-8', 'GB18030', $v);
        }
        // 将标题名称通过fputcsv写到文件句柄
        fputcsv($fp, $field_arr);

        if (!$time_str) {
            //时间段总和
            $start = date('Y-m-d', strtotime('-6 day'));
            $end = date('Y-m-d 23:59:59');
            $time_str = $start . ' 00:00:00 - ' . $end;
        }
        $createat = explode(' ', $time_str);
        $startTime = strtotime($createat[0] . ' ' . $createat[1]);
        $endTime = strtotime($createat[3] . ' ' . $createat[4]);
        $map['payment_time'] = ['between', [$startTime, $endTime]];

        $info = $this->getOrderSalesNum($order_platform,$map);
        //切割每份数据
        $list = $info['data'];
        $skus = $this->itemPlatformSku->getWebSkuAll($order_platform);
        $productInfo = $this->item->getSkuInfo();
        $nowDate = date('Y-m-d H:i:s');
        //整理数据
        foreach ($list as $k=>$v) {
            $tmpRow = [];
            $tmpRow['platformsku'] =$k;
            $tmpRow['sku'] = $skus[trim($k)]['sku'];
            //上架时间
            $shelvesTime = Db::name('sku_shelves_time')
                ->where(['site'=>$order_platform,'platform_sku'=>$k])
                ->value('shelves_time');
            $tmpRow['shelves_date'] = date('Y-m-d H:i:s',$shelvesTime);
            $tmpRow['type_name'] = $productInfo[$skus[trim($k)]['sku']]['type_name'];
            $tmpRow['available_stock'] = $skus[trim($k)]['stock'];  //虚拟仓库存
            $tmpRow['sales_num'] = $v;
            //在线天数
            $tmpRow['online_day'] = Db::name('sku_status_dataday')
                ->where(['site'=>$order_platform,'platform_sku'=>$k,'status'=>1])
                ->count();
            //日均销量
            $tmpRow['sales_num_day'] = $tmpRow['online_day'] ? round($v/$tmpRow['online_day'],2) : 0;
            //在线状态（实时）
            $stockInfo = $this->itemPlatformSku
                ->where(['platform_type'=>$order_platform,'platform_sku'=>$k])
                ->field('stock,outer_sku_status,presell_status,presell_start_time,presell_end_time,presell_num')
                ->find();
            if($stockInfo['outer_sku_status'] == 1){
                if($stockInfo['stock'] > 0){
                    $tmpRow['online_status'] = 1;  //在线
                }else{
                    if($stockInfo['presell_status'] == 1 && $nowDate >= $stockInfo['presell_start_time'] && $nowDate <= $stockInfo['presell_end_time']){
                        if($stockInfo['presell_num'] > 0){
                            $tmpRow['online_status'] = 1;  //在线
                        }else{
                            $tmpRow['online_status'] = 2;  //售罄
                        }
                    }else{
                        $tmpRow['online_status'] = 2;  //售罄
                    }
                }
            }else{
                $tmpRow['online_status'] = 3;  //下架
            }
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

        fclose($fp);
    }

    /**
     * 销量排行折线图
     * @author mjj
     * @date   2021/5/13 15:31:50
     */
    public function sales_num_line()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $params['site'] = $params['site'] ? $params['site'] : 1;
            /***********图表*************/
            //$cachename = 'goodsSalesNum_line_' . md5(serialize($map)) . '_' . $params['site'].$params['type'];
            //$cacheData = cache($cachename);
            //if (!$cacheData) {
                if($params['type'] == 1){
                    //默认当天
                    if ($params['time']) {
                        $time = explode(' ', $params['time']);
                        $start = strtotime($time[0] . ' ' . $time[1]);
                        $end = strtotime($time[3] . ' ' . $time[4]);
                    } else {
                        $start = strtotime(date('Y-m-d 00:00:00', strtotime('-6 day')));
                        $end = strtotime(date('Y-m-d H:i:s', time()));
                    }
                    $map['payment_time'] = ['between', [$start, $end]];
                    //总体销量排行榜
                    $pageArr['limit'] = 50;
                    $res = $this->getOrderSalesNum($params['site'],$map,$pageArr);
                    $res = $res['data'];
                    $cacheData['data'] = $res;
                    $cacheData['name'] = '销售排行榜';
                }else{
                    //新品销量排行榜
                    $startTime = strtotime(date('Y-m-d 00:00:00', strtotime('-30 day')));  //过去30天时间
                    $skus = Db::name('sku_shelves_time')
                        ->where('shelves_time','>=',$startTime)
                        ->where('site',$params['site'])
                        ->column('platform_sku','shelves_time');
                    $res = [];
                    $i = 0;
                    foreach($skus as $k=>$sku){
                        $skuTimeWhere['payment_time'] = ['between',[$k,time()]];
                        $skuSalesNum = $this->getOrderSalesNum($params['site'],$skuTimeWhere,[],$sku);
                        $salesNum = $skuSalesNum['data'][$sku] ?? 0;
                        if($salesNum != 0){
                            if($i<50){
                                $res[$sku] = $salesNum;
                                $i++;
                            }
                        }

                    }
                    $res = array_diff($res, [0]);
                    arsort($res);
                    $cacheData['data'] = $res;
                    $cacheData['name'] = '新品销售排行榜';
                }
                //cache($cachename, $cacheData, 7200);
//            }
            $json['xColumnName'] = $cacheData['data'] ? array_keys($cacheData['data']) : [];
            $json['columnData'] = [
                'type' => 'bar',
                'data' => $cacheData['data'] ? array_values($cacheData['data']) : [],
                'name' => $cacheData['name']
            ];
            return json(['code' => 1, 'data' => $json]);
            /***********END*************/
        }
    }

    //新的信息都从mojing_base获取
    public function getOrderSalesNum($site,$timeWhere,$pages=[],$sku = '')
    {
        if($sku){
            $map['p.sku'] = $sku;
        }else{
            $map['p.sku'] = ['not like', '%Price%'];
        }
        $map['o.site'] = $site;
        $map['o.status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete', 'delivered','delivery']];
        if($pages['limit']){
            if(isset($pages['offset'])){
                $res['data'] = $this->order
                    ->alias('o')
                    ->join('fa_order_item_option p','o.entity_id=p.magento_order_id and o.site=p.site')
                    ->where($map)
                    ->where($timeWhere)
                    ->group('sku')
                    ->order('num desc')
                    ->limit($pages['offset'],$pages['limit'])
                    ->column('sum(p.qty) as num', 'p.sku');
                $res['count'] = $this->order
                    ->alias('o')
                    ->join('fa_order_item_option p','o.entity_id=p.magento_order_id and o.site=p.site')
                    ->where($map)
                    ->where($timeWhere)
                    ->count('distinct sku');
            }else{
                $res['data'] = $this->order
                    ->alias('o')
                    ->join('fa_order_item_option p','o.entity_id=p.magento_order_id and o.site=p.site')
                    ->where($map)
                    ->where($timeWhere)
                    ->group('sku')
                    ->order('num desc')
                    ->limit($pages['limit'])
                    ->column('sum(p.qty) as num', 'p.sku');
                $res['count'] = $this->order
                    ->alias('o')
                    ->join('fa_order_item_option p','o.entity_id=p.magento_order_id and o.site=p.site')
                    ->where($map)
                    ->where($timeWhere)
                    ->count('distinct sku');
            }
        }else{
            $res['data'] = $this->order
                ->alias('o')
                ->join('fa_order_item_option p','o.entity_id=p.magento_order_id and o.site=p.site')
                ->where($map)
                ->where($timeWhere)
                ->group('sku')
                ->order('num desc')
                ->column('sum(p.qty) as num', 'p.sku');
        }
        return $res;
    }

}
