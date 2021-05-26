<?php

namespace app\admin\controller\operatedatacenter\dataview;

use app\admin\model\OrderStatistics;
use app\admin\model\platformmanage\MagentoPlatform;
use app\common\controller\Backend;
use think\Cache;
use think\Controller;
use think\Db;
use think\Request;

class NewDataMarket extends Backend
{
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\OperationAnalysis;
        $this->itemplatformsku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
        $this->orderitemoption = new \app\admin\model\order\order\NewOrderItemOption();
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
            if(!in_array($k,[1,2,3,10,11,5])){
                unset($platform[$k]);
            }
        }
        if(empty($platform)){
            $this->error('您没有权限访问','general/profile?ref=addtabs');
        }
        $arr = [];
        foreach($platform as $pkey => $pv){
            $arr[] = $pkey;
        }
        //库存数据,默认zeelool站数据
        $stock = $this->getStockData(1);
//        $zeelool_data = $this->model->getList(key($platform));
//        //z站今天的销售额($) 订单数	订单支付成功数	客单价($)	购物车总数	购物车总转化率(%)	新增购物车数	新增购物车转化率	新增注册用户数
//        //z站的历史数据  昨天、过去7天、过去30天、当月、上月、今年、总计
//        $zeelool_data = collection($zeelool_data)->toArray();
        //下边部分数据 默认30天数据
        $this->view->assign([
            'magentoplatformarr' => $platform,
            'date' => $this->date(),
            'result' => $arr,
            'arr' => $arr,
            'stock'=>$stock
        ]);
        return $this->view->fetch('operatedatacenter/new_statistical/all_data/index');
    }
    public function ajaxGetData()
    {
        $platform = input('order_platform') ? input('order_platform') : 1;
        $zeelool_data = $this->model->getList($platform);
        //z站今天的销售额($) 订单数	订单支付成功数	客单价($)	购物车总数	购物车总转化率(%)	新增购物车数	新增购物车转化率	新增注册用户数
        //z站的历史数据  昨天、过去7天、过去30天、当月、上月、今年、总计
        $zeelool_data = collection($zeelool_data)->toArray();
        $str = " <tr>
                        <td style='text-align: center; vertical-align: middle;'>今天</td>
                        <td id='today_sales_money'         style='text-align: center; vertical-align: middle;'>{$zeelool_data['today_sales_money']}</td>
                        <td id='today_order_num'           style='text-align: center; vertical-align: middle;'>{$zeelool_data['today_order_num']}</td>
                        <td id='today_order_success'       style='text-align: center; vertical-align: middle;'>{$zeelool_data['today_order_success']}</td>
                        <td id='today_unit_price'          style='text-align: center; vertical-align: middle;'>{$zeelool_data['today_unit_price']}</td>
                        <td id='today_shoppingcart_total'  style='text-align: center; vertical-align: middle;'>{$zeelool_data['today_shoppingcart_total']}</td>
                        <td id='today_shoppingcart_conversion' style='text-align: center; vertical-align: middle;'>{$zeelool_data['today_shoppingcart_conversion']}</td>
                        <td id='today_shoppingcart_new'        style='text-align: center; vertical-align: middle;'>{$zeelool_data['today_shoppingcart_new']}</td>
                        <td id='today_shoppingcart_newconversion' style='text-align: center; vertical-align: middle;'>{$zeelool_data['today_shoppingcart_newconversion']}</td>
                        <td id='today_register_customer'     style='text-align: center; vertical-align: middle;'>{$zeelool_data['today_register_customer']}</td>
                        <td id='today_sign_customer' style='text-align: center; vertical-align: middle;'>{$zeelool_data['today_sign_customer']}</td>
                    </tr>
                    <tr>
                        <td style='text-align: center; vertical-align: middle;'>昨天</td>
                        <td id='yesterday_sales_money' style='text-align: center; vertical-align: middle;'>{$zeelool_data['yesterday_sales_money']}</td>
                        <td id='yesterday_order_num' style='text-align: center; vertical-align: middle;'>{$zeelool_data['yesterday_order_num']}</td>
                        <td id='yesterday_order_success' style='text-align: center; vertical-align: middle;'>{$zeelool_data['yesterday_order_success']}</td>
                        <td id='yesterday_unit_price' style='text-align: center; vertical-align: middle;'>{$zeelool_data['yesterday_unit_price']}</td>
                        <td id='yesterday_shoppingcart_total' style='text-align: center; vertical-align: middle;'>{$zeelool_data['yesterday_shoppingcart_total']}</td>
                        <td id='yesterday_shoppingcart_conversion' style='text-align: center; vertical-align: middle;'>{$zeelool_data['yesterday_shoppingcart_conversion']}</td>
                        <td id='yesterday_shoppingcart_new' style='text-align: center; vertical-align: middle;'>{$zeelool_data['yesterday_shoppingcart_new']}</td>
                        <td id='yesterday_shoppingcart_newconversion' style='text-align: center; vertical-align: middle;'>{$zeelool_data['yesterday_shoppingcart_newconversion']}</td>
                        <td id='yesterday_register_customer' style='text-align: center; vertical-align: middle;'>{$zeelool_data['yesterday_register_customer']}</td>
                        <td id='yesterday_sign_customer' style='text-align: center; vertical-align: middle;'>{$zeelool_data['yesterday_sign_customer']}</td>
                    </tr>
                    <tr>
                        <td style='text-align: center; vertical-align: middle;'>过去7天</td>
                        <td id='pastsevenday_sales_money' style='text-align: center; vertical-align: middle;'>{$zeelool_data['pastsevenday_sales_money']}</td>
                        <td id='pastsevenday_order_num' style='text-align: center; vertical-align: middle;'>{$zeelool_data['pastsevenday_order_num']}</td>
                        <td id='pastsevenday_order_success' style='text-align: center; vertical-align: middle;'>{$zeelool_data['pastsevenday_order_success']}</td>
                        <td id='pastsevenday_unit_price' style='text-align: center; vertical-align: middle;'>{$zeelool_data['pastsevenday_unit_price']}</td>
                        <td id='pastsevenday_shoppingcart_total' style='text-align: center; vertical-align: middle;'>{$zeelool_data['pastsevenday_shoppingcart_total']}</td>
                        <td id='pastsevenday_shoppingcart_conversion' style='text-align: center; vertical-align: middle;'>{$zeelool_data['pastsevenday_shoppingcart_conversion']}</td>
                        <td id='pastsevenday_shoppingcart_new' style='text-align: center; vertical-align: middle;'>{$zeelool_data['pastsevenday_shoppingcart_new']}</td>
                        <td id='pastsevenday_shoppingcart_newconversion' style='text-align: center; vertical-align: middle;'>{$zeelool_data['pastsevenday_shoppingcart_newconversion']}</td>
                        <td id='pastsevenday_register_customer' style='text-align: center; vertical-align: middle;'>{$zeelool_data['pastsevenday_register_customer']}</td>
                        <td id='pastsevenday_sign_customer' style='text-align: center; vertical-align: middle;'>{$zeelool_data['pastsevenday_sign_customer']}</td>
                    </tr>
                    <tr>
                        <td style='text-align: center; vertical-align: middle;'>过去30天</td>
                        <td id='pastthirtyday_sales_money' style='text-align: center; vertical-align: middle;'>{$zeelool_data['pastthirtyday_sales_money']}</td>
                        <td id='pastthirtyday_order_num' style='text-align: center; vertical-align: middle;'>{$zeelool_data['pastthirtyday_order_num']}</td>
                        <td id='pastthirtyday_order_success' style='text-align: center; vertical-align: middle;'>{$zeelool_data['pastthirtyday_order_success']}</td>
                        <td id='pastthirtyday_unit_price' style='text-align: center; vertical-align: middle;'>{$zeelool_data['pastthirtyday_unit_price']}</td>
                        <td id='pastthirtyday_shoppingcart_total' style='text-align: center; vertical-align: middle;'>{$zeelool_data['pastthirtyday_shoppingcart_total']}</td>
                        <td id='pastthirtyday_shoppingcart_conversion' style='text-align: center; vertical-align: middle;'>{$zeelool_data['pastthirtyday_shoppingcart_conversion']}</td>
                        <td id='pastthirtyday_shoppingcart_new' style='text-align: center; vertical-align: middle;'>{$zeelool_data['pastthirtyday_shoppingcart_new']}</td>
                        <td id='pastthirtyday_shoppingcart_newconversion' style='text-align: center; vertical-align: middle;'>{$zeelool_data['pastthirtyday_shoppingcart_newconversion']}</td>
                        <td id='pastthirtyday_register_customer' style='text-align: center; vertical-align: middle;'>{$zeelool_data['pastthirtyday_register_customer']}</td>
                        <td id='pastthirtyday_sign_customer' style='text-align: center; vertical-align: middle;'>{$zeelool_data['pastthirtyday_sign_customer']}</td>
                    </tr>
                    <tr>
                        <td style='text-align: center; vertical-align: middle;'>当月</td>
                        <td id='thismonth_sales_money' style='text-align: center; vertical-align: middle;'>{$zeelool_data['thismonth_sales_money']}</td>
                        <td id='thismonth_order_num' style='text-align: center; vertical-align: middle;'>{$zeelool_data['thismonth_order_num']}</td>
                        <td id='thismonth_order_success' style='text-align: center; vertical-align: middle;'>{$zeelool_data['thismonth_order_success']}</td>
                        <td id='thismonth_unit_price' style='text-align: center; vertical-align: middle;'>{$zeelool_data['thismonth_unit_price']}</td>
                        <td id='thismonth_shoppingcart_total' style='text-align: center; vertical-align: middle;'>{$zeelool_data['thismonth_shoppingcart_total']}</td>
                        <td id='thismonth_shoppingcart_conversion' style='text-align: center; vertical-align: middle;'>{$zeelool_data['thismonth_shoppingcart_conversion']}</td>
                        <td id='thismonth_shoppingcart_new' style='text-align: center; vertical-align: middle;'>{$zeelool_data['thismonth_shoppingcart_new']}</td>
                        <td id='thismonth_shoppingcart_newconversion' style='text-align: center; vertical-align: middle;'>{$zeelool_data['thismonth_shoppingcart_newconversion']}</td>
                        <td id='thismonth_register_customer' style='text-align: center; vertical-align: middle;'>{$zeelool_data['thismonth_register_customer']}</td>
                        <td id='thismonth_sign_customer' style='text-align: center; vertical-align: middle;'>{$zeelool_data['thismonth_sign_customer']}</td>
                    </tr>
                    <tr>
                        <td style='text-align: center; vertical-align: middle;'>上月</td>
                        <td id='lastmonth_sales_money' style='text-align: center; vertical-align: middle;'>{$zeelool_data['lastmonth_sales_money']}</td>
                        <td id='lastmonth_order_num' style='text-align: center; vertical-align: middle;'>{$zeelool_data['lastmonth_order_num']}</td>
                        <td id='lastmonth_order_success' style='text-align: center; vertical-align: middle;'>{$zeelool_data['lastmonth_order_success']}</td>
                        <td id='lastmonth_unit_price' style='text-align: center; vertical-align: middle;'>{$zeelool_data['lastmonth_unit_price']}</td>
                        <td id='lastmonth_shoppingcart_total' style='text-align: center; vertical-align: middle;'>{$zeelool_data['lastmonth_shoppingcart_total']}</td>
                        <td id='lastmonth_shoppingcart_conversion' style='text-align: center; vertical-align: middle;'>{$zeelool_data['lastmonth_shoppingcart_conversion']}</td>
                        <td id='lastmonth_shoppingcart_new' style='text-align: center; vertical-align: middle;'>{$zeelool_data['lastmonth_shoppingcart_new']}</td>
                        <td id='lastmonth_shoppingcart_newconversion' style='text-align: center; vertical-align: middle;'>{$zeelool_data['lastmonth_shoppingcart_newconversion']}</td>
                        <td id='lastmonth_register_customer' style='text-align: center; vertical-align: middle;'>{$zeelool_data['lastmonth_register_customer']}</td>
                        <td id='lastmonth_sign_customer' style='text-align: center; vertical-align: middle;'>{$zeelool_data['lastmonth_sign_customer']}</td>
                    </tr>
                    <tr>
                        <td style='text-align: center; vertical-align: middle;'>今年</td>
                        <td id='thisyear_sales_money' style='text-align: center; vertical-align: middle;'>{$zeelool_data['thisyear_sales_money']}</td>
                        <td id='thisyear_order_num' style='text-align: center; vertical-align: middle;'>{$zeelool_data['thisyear_order_num']}</td>
                        <td id='thisyear_order_success' style='text-align: center; vertical-align: middle;'>{$zeelool_data['thisyear_order_success']}</td>
                        <td id='thisyear_unit_price' style='text-align: center; vertical-align: middle;'>{$zeelool_data['thisyear_unit_price']}</td>
                        <td id='thisyear_shoppingcart_total' style='text-align: center; vertical-align: middle;'>{$zeelool_data['thisyear_shoppingcart_total']}</td>
                        <td id='thisyear_shoppingcart_conversion' style='text-align: center; vertical-align: middle;'>{$zeelool_data['thisyear_shoppingcart_conversion']}</td>
                        <td id='thisyear_shoppingcart_new' style='text-align: center; vertical-align: middle;'>{$zeelool_data['thisyear_shoppingcart_new']}</td>
                        <td id='thisyear_shoppingcart_newconversion' style='text-align: center; vertical-align: middle;'>{$zeelool_data['thisyear_shoppingcart_newconversion']}</td>
                        <td id='thisyear_register_customer' style='text-align: center; vertical-align: middle;'>{$zeelool_data['thisyear_register_customer']}</td>
                        <td id='thisyear_sign_customer' style='text-align: center; vertical-align: middle;'>{$zeelool_data['thisyear_sign_customer']}</td>
                    </tr>
                    <tr>
                        <td style='text-align: center; vertical-align: middle;'>去年</td>
                        <td id='lastyear_sales_money' style='text-align: center; vertical-align: middle;'>{$zeelool_data['lastyear_sales_money']}</td>
                        <td id='lastyear_order_num' style='text-align: center; vertical-align: middle;'>{$zeelool_data['lastyear_order_num']}</td>
                        <td id='lastyear_order_success' style='text-align: center; vertical-align: middle;'>{$zeelool_data['lastyear_order_success']}</td>
                        <td id='lastyear_unit_price' style='text-align: center; vertical-align: middle;'>{$zeelool_data['lastyear_unit_price']}</td>
                        <td id='lastyear_shoppingcart_total' style='text-align: center; vertical-align: middle;'>{$zeelool_data['lastyear_shoppingcart_total']}</td>
                        <td id='lastyear_shoppingcart_conversion' style='text-align: center; vertical-align: middle;'>{$zeelool_data['lastyear_shoppingcart_conversion']}</td>
                        <td id='lastyear_shoppingcart_new' style='text-align: center; vertical-align: middle;'>{$zeelool_data['lastyear_shoppingcart_new']}</td>
                        <td id='lastyear_shoppingcart_newconversion' style='text-align: center; vertical-align: middle;'>{$zeelool_data['lastyear_shoppingcart_newconversion']}</td>
                        <td id='lastyear_register_customer' style='text-align: center; vertical-align: middle;'>{$zeelool_data['lastyear_register_customer']}</td>
                        <td id='lastyear_sign_customer'style='text-align: center; vertical-align: middle;'>{$zeelool_data['lastyear_sign_customer']}</td>
                    </tr>
                    <tr>
                        <td style='text-align: center; vertical-align: middle;'>总计</td>
                        <td id='total_sales_money' style='text-align: center; vertical-align: middle;'>{$zeelool_data['total_sales_money']}</td>
                        <td id='total_order_num' style='text-align: center; vertical-align: middle;'>{$zeelool_data['total_order_num']}</td>
                        <td id='total_order_success' style='text-align: center; vertical-align: middle;'>{$zeelool_data['total_order_success']}</td>
                        <td id='total_unit_price' style='text-align: center; vertical-align: middle;'>{$zeelool_data['total_unit_price']}</td>
                        <td id='total_shoppingcart_total' style='text-align: center; vertical-align: middle;'>{$zeelool_data['total_shoppingcart_total']}</td>
                        <td id='total_shoppingcart_conversion' style='text-align: center; vertical-align: middle;'>{$zeelool_data['total_shoppingcart_conversion']}</td>
                        <td id='total_shoppingcart_new' style='text-align: center; vertical-align: middle;'>{$zeelool_data['total_shoppingcart_new']}</td>
                        <td id='total_shoppingcart_newconversion' style='text-align: center; vertical-align: middle;'>{$zeelool_data['total_shoppingcart_newconversion']}</td>
                        <td id='total_register_customer' style='text-align: center; vertical-align: middle;'>{$zeelool_data['total_register_customer']}</td>
                        <td id='total_sign_customer' style='text-align: center; vertical-align: middle;'>{$zeelool_data['total_sign_customer']}</td>
                    </tr>";
        $this->success('', '', $str);
    }

    /**
     * 获取仓库数据
     * @author mjj
     * @date   2021/5/20 10:38:07
     */
    public function stock_data(){
        if ($this->request->isAjax()) {
            $platform = input('order_platform') ? input('order_platform') : 1;
            $stock = $this->getStockData($platform);
            $this->success('', '', $stock);
        }
    }
    /**
     * 获取仓库数据
     * @param $site  站点
     * @author mjj
     * @date   2021/5/19 10:20:30
     */
    public function getStockData($site)
    {
        //虚拟仓库存、虚拟仓库存金额
        $stock = $this->itemplatformsku
            ->alias('s')
            ->join('fa_item i','s.sku=i.sku')
            ->where('s.platform_type',$site)
            ->where('i.category_id','neq',43)
            ->field('sum(s.stock) stock,sum(s.stock*i.purchase_price) price')
            ->find();
        $arr['stock'] = $stock['stock'];
        $arr['stock_total'] = $stock['price'];
        //呆滞库存数量、呆滞库存金额
        $dullStockData = Db::name('supply_dull_stock_site')
            ->where('site',$site)
            ->where('grade','Z')
            ->field('stock,price')
            ->order('day_date desc')
            ->find();
        $arr['dull_stock'] = $dullStockData['stock'];
        $arr['dull_stock_total'] = $dullStockData['price'];
        //过去30天总销售副数
        $start = strtotime(date('Y-m-d 00:00:00', strtotime('-30 day')));
        $end = time();
        $orderWhere['payment_time'] = ['between',[$start,$end]];
        $orderWhere['order_type'] = 1;
        $orderWhere['o.site'] = $site;
        $orderWhere['o.status'] = ['in',['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered']];
        $salesNum = $this->orderitemoption
            ->alias('i')
            ->join('fa_order o','o.entity_id=i.magento_order_id')
            ->where($orderWhere)
            ->sum('i.qty');
        //周转月数
        $turn_month = $salesNum ? round($stock['stock']/$salesNum,2) : 0;
        $arr['turn_month'] = $turn_month;
        //过去30天上架的SKU数
        $skuNum = Db::name('sku_shelves_time')
            ->where('shelves_time','>=',$start)
            ->where('site',$site)
            ->count();
        $arr['shelves_sales_num_thirty'] = $skuNum;
        //新品SKU30天内的sku
        $skus = Db::name('sku_shelves_time')
            ->where('shelves_time','>=',$start)
            ->where('site',$site)
            ->column('platform_sku');
        //新品SKU30天内销量
        $newSalesNum = $this->orderitemoption
            ->alias('i')
            ->join('fa_order o','o.entity_id=i.magento_order_id')
            ->where($orderWhere)
            ->where('i.sku','in',$skus)
            ->sum('i.qty');
        $arr['sales_num_thirty'] = $newSalesNum;
        //新品30天内销量占比:新品30内总销量/30天平台总销量*100%
        $salesRate = $salesNum ? round($newSalesNum/$salesNum*100,2) : 0;
        $arr['sales_num_rate'] = $salesRate;
        return $arr;
    }
}
