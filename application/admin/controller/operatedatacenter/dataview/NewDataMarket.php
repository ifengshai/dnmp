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
        $zeelool_data = $this->model->getList(key($platform));
        //z站今天的销售额($) 订单数	订单支付成功数	客单价($)	购物车总数	购物车总转化率(%)	新增购物车数	新增购物车转化率	新增注册用户数
        //z站的历史数据  昨天、过去7天、过去30天、当月、上月、今年、总计
        $zeelool_data = collection($zeelool_data)->toArray();
        //下边部分数据 默认30天数据
        $this->view->assign([
            'magentoplatformarr' => $platform,
            'zeelool_data' => $zeelool_data,
            'date' => $this->date(),
            'result' => $arr,
            'arr' => $arr,
            'stock'=>$stock
        ]);
        return $this->view->fetch('operatedatacenter/new_statistical/all_data/index');
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
