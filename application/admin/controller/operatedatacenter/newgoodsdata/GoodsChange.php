<?php

namespace app\admin\controller\operatedatacenter\NewGoodsData;

use app\admin\model\order\order\Zeelool;
use app\admin\model\platformManage\MagentoPlatform;
use app\common\controller\Backend;
use think\Controller;
use think\Db;
use think\Request;

class GoodsChange extends Backend
{
    /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    protected $noNeedRight = ['export'];

    public function _initialize()
    {
        parent::_initialize();
        $this->zeeloolOperate = new \app\admin\model\operatedatacenter\Zeelool;
        $this->vooguemeOperate = new \app\admin\model\operatedatacenter\Voogueme();
        $this->nihaoOperate = new \app\admin\model\operatedatacenter\Nihao();
        $this->item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
    }

    public function index()
    {
        $lastDay = strtotime(date('Y-m-d', strtotime('-1 day')));
        $start = date('Y-m-d', strtotime('-6 day'));
        $end = date('Y-m-d 23:59:59');
        $seven_days = $start . ' 00:00:00 - ' . $end . ' 00:00:00';
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key => $val) {
            if (!in_array($val['name'], ['zeelool', 'voogueme', 'nihao','wesee','zeelool_de','zeelool_jp'])) {
                unset($magentoplatformarr[$key]);
            }
        }
        if ($this->request->isAjax()) {
            $filter = json_decode($this->request->get('filter'), true);
            // dump($filter);
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            if ($filter['time_str']) {
                //时间段总和
                $createat = explode(' ', $filter['time_str']);
            } else {
                $createat = explode(' ', $seven_days);
            }
            if ($filter['sku']) {
                $map['platform_sku'] = ['like', '%' . $filter['sku'] . '%'];
            }
            $order_platform = $filter['order_platform'] ? $filter['order_platform'] : 1;
            $map['site'] = $order_platform;
            $map['day_date'] = ['between', [$createat[0], $createat[3]]];
            $orderWhere['o.site'] = $order_platform;
            $orderWhere['o.order_type'] = $order_platform;
            $orderWhere['o.status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete', 'delivered']];
            $orderWhere['o.payment_time'] = ['between', [strtotime($createat[0]), $lastDay]];
            unset($filter['time_str']);
            unset($filter['create_time-operate']);
            unset($filter['sku']);
            unset($filter['order_platform']);
            $this->request->get(['filter' => json_encode($filter)]);
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = Db::name('datacenter_sku_day')
                ->where($where)
                ->where($map)
                ->group('platform_sku')
                ->count();
            $list = Db::name('datacenter_sku_day')
                ->where($where)
                ->where($map)
                ->group('platform_sku')
                ->field('platform_sku,sku,sum(cart_num) as cart_num,sum(update_cart_num) as update_cart_num,sum(pay_lens_num) as pay_lens_num,day_stock,sum(sales_num) as sales_num,sum(order_num) as order_num,sum(glass_num) as glass_num,sum(sku_row_total) as sku_row_total')
                ->order('id', 'desc')
                ->limit($offset, $limit)
                ->select();
            $nowDate = date('Y-m-d H:i:s');
            foreach ($list as $k => $v) {
                $skuStockInfo = $this->item_platform_sku
                    ->where(['sku' => $v['sku'], 'platform_type' => $order_platform])
                    ->field('stock,outer_sku_status,presell_status,presell_start_time,presell_end_time,presell_num')
                    ->find();
                $list[$k]['single_price'] = $v['glass_num'] != 0 ? round($v['sku_row_total'] / $v['glass_num'], 2) : 0;
                //订单金额
                $orderIds = $this->order
                    ->alias('o')
                    ->join('fa_order_item_option p','o.entity_id=p.magento_order_id')
                    ->where($orderWhere)
                    ->where('i.sku',$v['platform_sku'])
                    ->column('distinct entity_id');
                $orderTotal = $this->order
                    ->where('entity_id','in',$orderIds)
                    ->sum('base_grand_total');
                $list[$k]['sku_grand_total'] = $orderTotal ?? 0;
                //商品现价
                $list[$k]['now_pricce'] = Db::name('datacenter_sku_day')
                    ->where(['sku'=>$v['sku'],'site'=>$order_platform])
                    ->order('id desc')
                    ->value('now_pricce');
                //上下架状态
                if($skuStockInfo['outer_sku_status'] == 1){
                    if($skuStockInfo['stock'] > 0){
                        $status = 1; //上架
                    }else{
                        if($skuStockInfo['presell_status'] == 1 && $nowDate >= $skuStockInfo['presell_start_time'] && $nowDate <= $skuStockInfo['presell_end_time']){
                            if($skuStockInfo['presell_num'] > 0){
                                $status = 1; //上架
                            }else{
                                $status = 2; //售罄
                            }
                        }else{
                            $status = 2; //售罄
                        }
                    }
                }else{
                    $status = 3;  //下架
                }
                $list[$k]['status'] = $status;
                $list[$k]['stock'] = $skuStockInfo['stock'];
                $list[$k]['cart_change'] = $v['cart_num'] == 0 ? '0%' : round($v['order_num'] / $v['cart_num'] * 100, 2) . '%';
                $list[$k]['update_cart_rate'] = $v['update_cart_num'] == 0 ? '0%' : round($v['order_num'] / $v['update_cart_num'] * 100, 2) . '%';
                $list[$k]['pay_lens_rate'] = $v['glass_num'] == 0 ? '0%' : round($v['pay_lens_num'] / $v['glass_num'] * 100, 2) . '%';
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->assign('magentoplatformarr', $magentoplatformarr);
        return $this->view->fetch();
    }
    //导出
    public function export(){
        set_time_limit(0);
        header ( "Content-type:application/vnd.ms-excel" );
        header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", date('Y-m-d-His',time()) ) . ".csv" );//导出文件名
        // 打开PHP文件句柄，php://output 表示直接输出到浏览器
        $fp = fopen('php://output', 'a');
        $time_str = input('time_str');
        $sku = input('sku');
        $order_platform = input('order_platform') ? input('order_platform') : 1;

        // 将中文标题转换编码，否则乱码
        if($order_platform == 5){
            $field_arr = array(
                'SKU','订单成功数','订单金额','付费镜片占比','售价','在售状态（实时）','销售副数','实际支付的销售额','副单价','虚拟库存'
            );
        }else{
            $field_arr = array(
                'SKU','购物车数量','订单成功数','订单金额','更新购物车转化率','新增购物车转化率','付费镜片占比','售价','在售状态（实时）','销售副数','实际支付的销售额','副单价','虚拟库存'
            );
        }

        foreach ($field_arr as $i => $v) {
            $field_arr[$i] = iconv('utf-8', 'GB18030', $v);
        }
        // 将标题名称通过fputcsv写到文件句柄
        fputcsv($fp, $field_arr);

        if (!$time_str) {
            //时间段总和
            $start = date('Y-m-d', strtotime('-6 day'));
            $end = date('Y-m-d 23:59:59');
            $time_str = $start . ' 00:00:00 - ' . $end . ' 00:00:00';
        }
        $createat = explode(' ', $time_str);
        if ($sku) {
            $map['sku'] = ['like', '%' . $sku . '%'];
        }
        $map['site'] = $order_platform;
        $map['day_date'] = ['between', [$createat[0], $createat[3]]];
        $total_export_count = Db::name('datacenter_sku_day')
            ->where($map)
            ->group('platform_sku')
            ->count();
        $nowDate = date('Y-m-d H:i:s');
        $pre_count = 5000;
        for ($i=0;$i<intval($total_export_count/$pre_count)+1;$i++){
            $start = $i*$pre_count;
            //切割每份数据
            $list = Db::name('datacenter_sku_day')
                ->where($map)
                ->group('platform_sku')
                ->field('platform_sku,sku,sum(cart_num) as cart_num,sum(update_cart_num) as update_cart_num,sum(pay_lens_num) as pay_lens_num,day_stock,sum(sales_num) as sales_num,sum(order_num) as order_num,sum(glass_num) as glass_num,sum(sku_row_total) as sku_row_total,sum(sku_grand_total) as sku_grand_total')
                ->order('id', 'desc')
                ->limit($start,$pre_count)
                ->select();
            $list = collection($list)->toArray();
            //整理数据
            foreach ($list as &$v) {
                $tmpRow = [];
                $tmpRow['platform_sku'] =$v['platform_sku'];//sku
                if($order_platform != 5){
                    $tmpRow['cart_num'] =$v['cart_num'];//购物车数量
                }
                $tmpRow['order_num'] =$v['order_num'];//订单成功数
                $tmpRow['sku_grand_total'] =$v['sku_grand_total'];//订单金额
                if($order_platform != 5){
                    $tmpRow['update_cart_rate'] =$v['update_cart_num'] ? round($v['order_num']/$v['update_cart_num']*100,2).'%' : '0%';//更新购物车转化率
                    $tmpRow['cart_change'] = $v['cart_num'] ? round($v['order_num'] / $v['cart_num'] * 100, 2) . '%' : '0%';//新增购物车转化率
                }
                $tmpRow['pay_lens_rate'] = $v['glass_num'] ? round($v['pay_lens_num'] / $v['glass_num'] * 100, 2) . '%' : '0%';//付费镜片占比
                $tmpRow['now_pricce'] =Db::name('datacenter_sku_day')
                    ->where(['sku'=>$v['sku'],'site'=>$order_platform])
                    ->order('id desc')
                    ->value('now_pricce');//售价
                $skuStockInfo = $this->item_platform_sku
                    ->where(['sku' => $v['sku'], 'platform_type' => $order_platform])
                    ->field('stock,outer_sku_status,presell_status,presell_start_time,presell_end_time,presell_num')
                    ->find();
                if($skuStockInfo['outer_sku_status'] == 1){
                    if($skuStockInfo['stock'] > 0){
                        $status = 1; //上架
                    }else{
                        if($skuStockInfo['presell_status'] == 1 && $nowDate >= $skuStockInfo['presell_start_time'] && $nowDate <= $skuStockInfo['presell_end_time']){
                            if($skuStockInfo['presell_num'] > 0){
                                $status = 1; //上架
                            }else{
                                $status = 2; //售罄
                            }
                        }else{
                            $status = 2; //售罄
                        }
                    }
                }else{
                    $status = 3;  //下架
                }
                $tmpRow['status'] = $status;
                $tmpRow['glass_num'] =$v['glass_num'];//销售副数
                $tmpRow['sku_row_total'] =$v['sku_row_total'];//实际支付的销售额
                $tmpRow['single_price'] = $v['glass_num'] ? round($v['sku_row_total'] / $v['glass_num'], 2) : 0;//副单价
                $tmpRow['stock'] = $skuStockInfo['stock'];//虚拟库存
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
