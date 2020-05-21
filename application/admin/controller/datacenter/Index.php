<?php

namespace app\admin\controller\datacenter;

use app\common\controller\Backend;
use think\Db;


/**
 * 数据中心
 *
 * @icon fa fa-circle-o
 */
class Index extends Backend
{

    /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    protected $noNeedRight = [];


    /**
     * Index模型对象
     * @var 
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->zeelool = new \app\admin\model\order\order\Zeelool;
        $this->voogueme = new \app\admin\model\order\order\Voogueme;
        $this->nihao = new \app\admin\model\order\order\Nihao;
        $this->meeloog = new \app\admin\model\order\order\Meeloog;
        $this->itemplatformsku = new \app\admin\model\itemmanage\ItemPlatformSku;
        $this->item = new \app\admin\model\itemmanage\Item;
        $this->lens = new \app\admin\model\lens\Index;
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 销量统计
     *
     * @Description
     * @author wpl
     * @since 2020/02/21 14:20:44 
     * @return void
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

            //统计三个站销量
            //自定义时间搜索
            $filter = json_decode($this->request->get('filter'), true);
            if ($filter['created_at']) {
                $createat = explode(' ', $filter['created_at']);
                $map['a.created_at'] = ['between', [$createat[0] . ' ' . $createat[1], $createat[3]  . ' ' . $createat[4]]];
                unset($filter['created_at']);
                $this->request->get(['filter' => json_encode($filter)]);
            } else {
                $map['a.created_at'] = ['between', [date("Y-m-d 00:00:00"), date("Y-m-d H:i:s", time())]];
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->item
                ->where($where)
                ->where('is_open', 1)
                ->order($sort, $order)
                ->count();

            $list = $this->item
                ->where($where)
                ->where('is_open', 1)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            foreach ($list as &$v) {
                //sku转换
                $v['z_sku'] = $this->itemplatformsku->getWebSku($v['sku'], 1);

                $v['v_sku'] = $this->itemplatformsku->getWebSku($v['sku'], 2);

                $v['n_sku'] = $this->itemplatformsku->getWebSku($v['sku'], 3);

                $v['m_sku'] = $this->itemplatformsku->getWebSku($v['sku'], 4);
            }
            unset($v);

            $z_sku = array_column($list, 'z_sku');
            $v_sku = array_column($list, 'v_sku');
            $n_sku = array_column($list, 'n_sku');
            $m_sku = array_column($list, 'm_sku');

            //获取三个站销量数据
            $zeelool = $this->zeelool->getOrderSalesNum($z_sku, $map);
            $voogueme = $this->voogueme->getOrderSalesNum($v_sku, $map);
            $nihao = $this->nihao->getOrderSalesNum($n_sku, $map);
            $meeloog = $this->meeloog->getOrderSalesNum($m_sku, $map);
            //重组数组
            foreach ($list as &$v) {

                $v['z_num'] = round($zeelool[$v['z_sku']]) ?? 0;

                $v['v_num'] = round($voogueme[$v['v_sku']]) ?? 0;

                $v['n_num'] = round($nihao[$v['n_sku']]) ?? 0;

                $v['m_num'] = round($meeloog[$v['m_sku']]) ?? 0;

                $v['all_num'] = $v['z_num'] + $v['v_num'] + $v['n_num'] + $v['m_num'];
            }
            unset($v);

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 供应链数据大屏
     *
     * @Description
     * @author wpl
     * @since 2020/02/21 14:23:45 
     * @return void
     */
    public function supply_chain_data()
    {
        $dataConfig = new \app\admin\model\DataConfig();

        /*******************************库存数据***********************************/
        //仓库总库存
        $allStock = $dataConfig->where('key', 'allStock')->value('value');

        //仓库库存总金额       
        $allStockPrice = $dataConfig->where('key', 'allStockPrice')->value('value');

        //镜架库存统计
        $frameStock = $dataConfig->where('key', 'frameStock')->value('value');

        //镜架总金额 
        $frameStockPrice = $dataConfig->where('key', 'frameStockPrice')->value('value');

        //镜片库存
        $lensStock = $dataConfig->where('key', 'lensStock')->value('value');

        //镜片库存总金额
        $lensStockPrice = $dataConfig->where('key', 'lensStockPrice')->value('value');

        //饰品库存
        $ornamentsStock = $dataConfig->where('key', 'ornamentsStock')->value('value');

        //饰品库存总金额
        $ornamentsStockPrice = $dataConfig->where('key', 'ornamentsStockPrice')->value('value');

        //样品库存
        $sampleNumStock = $dataConfig->where('key', 'sampleNumStock')->value('value');

        //样品库存总金额
        $sampleNumStockPrice = $dataConfig->where('key', 'sampleNumStockPrice')->value('value');


        //查询总SKU数
        $skuNum = $dataConfig->where('key', 'skuNum')->value('value');

        //查询待处理事件 售后未处理 + 协同未处理
        $taskAllNum = $dataConfig->where('key', 'taskAllNum')->value('value');


        //三个站待处理订单
        $allPendingOrderNum = $dataConfig->where('key', 'allPendingOrderNum')->value('value');

        /***
         * 库存周转天数 库存周转率
         * 库存周转天数 = 7*(期初总库存+期末总库存)/2/7天总销量
         * 库存周转率 =  360/库存周转天数
         */

        //查询最近7天总销量
        $allSalesNum = $dataConfig->where('key', 'allSalesNum')->value('value');

        //库存周转天数
        $stock7days = $dataConfig->where('key', 'stock7days')->value('value');

        //库存周转率
        $stock7daysPercent = $dataConfig->where('key', 'stock7daysPercent')->value('value');

        //在途库存
        $onwayAllStock = $dataConfig->where('key', 'onwayAllStock')->value('value');

        //在途库存总金额
        $onwayAllStockPrice = $dataConfig->where('key', 'onwayAllStockPrice')->value('value');

        //在途镜架库存
        $onwayFrameAllStock = $dataConfig->where('key', 'onwayFrameAllStock')->value('value');

        //在途镜架库存总金额
        $onwayFrameAllStockPrice = $dataConfig->where('key', 'onwayFrameAllStockPrice')->value('value');

        //在途饰品库存
        $onwayOrnamentAllStock = $dataConfig->where('key', 'onwayOrnamentAllStock')->value('value');

        //在途饰品库存总金额
        $onwayOrnamentAllStockPrice = $dataConfig->where('key', 'onwayOrnamentAllStockPrice')->value('value');

        /*******************************END****************************************/

        /*******************************采购数据***************************************/
        //当月采购总数
        $purchaseNum = $dataConfig->where('key', 'purchaseNum')->value('value');

        //当月采购总金额
        $purchasePrice = $dataConfig->where('key', 'purchasePrice')->value('value');

        //当月采购镜架总数
        $purchaseFrameNum = $dataConfig->where('key', 'purchaseFrameNum')->value('value');

        //当月采购总SKU数
        $purchaseSkuNum = $dataConfig->where('key', 'purchaseSkuNum')->value('value');

        //当月销售总数
        $salesNum = $dataConfig->where('key', 'salesNum')->value('value');

        //当月到货总数
        $arrivalsNum = $dataConfig->where('key', 'arrivalsNum')->value('value');

        //当月质检合格数量
        $quantityNum = $dataConfig->where('key', 'quantityNum')->value('value');

        //合格率
        $quantityPercent = $dataConfig->where('key', 'quantityPercent')->value('value');

        //采购平均单价
        $purchaseAveragePrice = $dataConfig->where('key', 'purchaseAveragePrice')->value('value');

        //当月销售总成本
        $salesCost = $dataConfig->where('key', 'salesCost')->value('value');

        //当月定做总数
        $customSkuNum = $dataConfig->where('key', 'customSkuNum')->value('value');

        //当月定做总金额
        $customSkuNumMoney = $dataConfig->where('key', 'customSkuNumMoney')->value('value');

        //当月定做SKU数
        $customSkuQty = $dataConfig->where('key', 'customSkuQty')->value('value');

        //当月定做平均单价
        $customSkuPrice = $dataConfig->where('key', 'customSkuPrice')->value('value');

        /**********************************END************************************************/
        /*********************************仓库数据***************************************/
        //当月总单量
        $lastMonthAllSalesNum = $dataConfig->where('key', 'lastMonthAllSalesNum')->value('value');

        //未出库订单总数
        $allUnorderNum = $dataConfig->where('key', 'allUnorderNum')->value('value');


        //7天未出库订单总数
        $days7UnorderNum = $dataConfig->where('key', 'days7UnorderNum')->value('value');


        //当月质检总数
        $orderCheckNum = $dataConfig->where('key', 'orderCheckNum')->value('value');

        //当日配镜架总数
        $orderFrameNum = $dataConfig->where('key', 'orderFrameNum')->value('value');

        //当日配镜片总数
        $orderLensNum = $dataConfig->where('key', 'orderLensNum')->value('value');

        //当日加工总数
        $orderFactoryNum = $dataConfig->where('key', 'orderFactoryNum')->value('value');

        //当日质检总数
        $orderCheckNewNum = $dataConfig->where('key', 'orderCheckNewNum')->value('value');

        //当日出库总数
        $outStockNum = $dataConfig->where('key', 'outStockNum')->value('value');

        //当日质检入库总数
        $inStockNum = $dataConfig->where('key', 'inStockNum')->value('value');

        //总压单率
        $pressureRate = $dataConfig->where('key', 'pressureRate')->value('value');

        //7天压单率
        $pressureRate7days = $dataConfig->where('key', 'pressureRate7days')->value('value');

        //当月妥投总量
        $monthAppropriate = $dataConfig->where('key', 'monthAppropriate')->value('value');

        //当月妥投占比
        $monthAppropriatePercent = $dataConfig->where('key', 'monthAppropriatePercent')->value('value');

        //超时订单总数
        $overtimeOrder = $dataConfig->where('key', 'overtimeOrder')->value('value');

        //在售SKU数
        $onSaleSkuNum = $dataConfig->where('key', 'onSaleSkuNum')->value('value');

        //在售镜架总数
        $onSaleFrameNum = $dataConfig->where('key', 'onSaleFrameNum')->value('value');

        //在售饰品总数
        $onSaleOrnamentsNum = $dataConfig->where('key', 'onSaleOrnamentsNum')->value('value');

        //当月选品总数
        $selectProductNum = $dataConfig->where('key', 'selectProductNum')->value('value');

        //当月新品上线总数
        $selectProductAdoptNum = $dataConfig->where('key', 'selectProductAdoptNum')->value('value');

        //新品10天的销量
        $days10SalesNum = $dataConfig->where('key', 'days10SalesNum')->value('value');

        //新品10天的销量占比
        $days10SalesNumPercent = $dataConfig->where('key', 'days10SalesNumPercent')->value('value');

        //计算产品等级的数量
        $productGrade = new \app\admin\model\ProductGrade();
        $where = [];
        $where['grade'] = 'A+';
        $AA_num = $productGrade->where($where)->count();

        $where['grade'] = 'A';
        $A_num = $productGrade->where($where)->count();

        $where['grade'] = 'B';
        $B_num = $productGrade->where($where)->count();

        $where['grade'] = 'C+';
        $CA_num = $productGrade->where($where)->count();

        $where['grade'] = 'C';
        $C_num = $productGrade->where($where)->count();

        $where['grade'] = 'D';
        $D_num = $productGrade->where($where)->count();

        $where['grade'] = 'E';
        $E_num = $productGrade->where($where)->count();

        $where['grade'] = 'F';
        $F_num = $productGrade->where($where)->count();

        //总数
        $all_num = $AA_num + $A_num + $B_num + $CA_num + $C_num + $D_num + $E_num + $F_num;
        //A级数量即总占比
        $res['AA_num'] = $AA_num;
        $res['AA_percent'] = round($AA_num / $all_num * 100, 2);
        $res['A_num'] = $A_num;
        $res['A_percent'] = round($A_num / $all_num * 100, 2);
        $res['B_num'] = $B_num;
        $res['B_percent'] = round($B_num / $all_num * 100, 2);
        $res['CA_num'] = $CA_num;
        $res['CA_percent'] = round($CA_num / $all_num * 100, 2);
        $res['C_num'] = $C_num;
        $res['C_percent'] = round($C_num / $all_num * 100, 2);
        $res['D_num'] = $D_num;
        $res['D_percent'] = round($D_num / $all_num * 100, 2);
        $res['E_num'] = $E_num;
        $res['E_percent'] = round($E_num / $all_num * 100, 2);
        $res['F_num'] = $F_num;
        $res['F_percent'] = round($F_num / $all_num * 100, 2);

        $this->view->assign('gradeSkuStock', $productGrade->getSkuStock());
        $this->view->assign('res', $res);

        //选品数据
        $this->view->assign('onSaleSkuNum', $onSaleSkuNum);
        $this->view->assign('onSaleFrameNum', $onSaleFrameNum);
        $this->view->assign('onSaleOrnamentsNum', $onSaleOrnamentsNum);
        $this->view->assign('selectProductNum', $selectProductNum);
        $this->view->assign('selectProductAdoptNum', $selectProductAdoptNum);
        $this->view->assign('days10SalesNum', $days10SalesNum);
        $this->view->assign('days10SalesNumPercent', $days10SalesNumPercent);

        //仓库数据
        $this->view->assign('lastMonthAllSalesNum', $lastMonthAllSalesNum);
        $this->view->assign('allUnorderNum', $allUnorderNum);
        $this->view->assign('days7UnorderNum', $days7UnorderNum);
        $this->view->assign('orderCheckNum', $orderCheckNum);
        $this->view->assign('orderFrameNum', $orderFrameNum);
        $this->view->assign('orderLensNum', $orderLensNum);
        $this->view->assign('orderFactoryNum', $orderFactoryNum);
        $this->view->assign('orderCheckNewNum', $orderCheckNewNum);
        $this->view->assign('outStockNum', $outStockNum);
        $this->view->assign('inStockNum', $inStockNum);
        $this->view->assign('pressureRate', $pressureRate);
        $this->view->assign('pressureRate7days', $pressureRate7days);
        $this->view->assign('monthAppropriate', $monthAppropriate);
        $this->view->assign('monthAppropriatePercent', $monthAppropriatePercent);
        $this->view->assign('overtimeOrder', $overtimeOrder);

        //采购数据
        $this->view->assign('purchaseNum', $purchaseNum);
        $this->view->assign('purchasePrice', $purchasePrice);
        $this->view->assign('purchaseFrameNum', $purchaseFrameNum);
        $this->view->assign('purchaseSkuNum', $purchaseSkuNum);
        $this->view->assign('salesNum', $salesNum);
        $this->view->assign('arrivalsNum', $arrivalsNum);
        $this->view->assign('quantityNum', $quantityNum);
        $this->view->assign('quantityPercent', $quantityPercent);
        $this->view->assign('purchaseAveragePrice', $purchaseAveragePrice);
        $this->view->assign('salesCost', $salesCost);
        $this->view->assign('customSkuNum', $customSkuNum);
        $this->view->assign('customSkuNumMoney', $customSkuNumMoney);
        $this->view->assign('customSkuQty', $customSkuQty);
        $this->view->assign('customSkuPrice', $customSkuPrice);
        $this->view->assign('allStock', $allStock);
        $this->view->assign('allStockPrice', $allStockPrice);
        $this->view->assign('frameStock', $frameStock);
        $this->view->assign('frameStockPrice', $frameStockPrice);
        $this->view->assign('lensStock', $lensStock);
        $this->view->assign('lensStockPrice', $lensStockPrice);
        $this->view->assign('ornamentsStock', $ornamentsStock);
        $this->view->assign('ornamentsStockPrice', $ornamentsStockPrice);
        $this->view->assign('sampleNumStock', $sampleNumStock);
        $this->view->assign('sampleNumStockPrice', $sampleNumStockPrice);
        $this->view->assign('skuNum', $skuNum);
        $this->view->assign('taskAllNum', $taskAllNum);
        $this->view->assign('allPendingOrderNum', $allPendingOrderNum);
        $this->view->assign('stock7days', $stock7days);
        $this->view->assign('stock7daysPercent', $stock7daysPercent);
        $this->view->assign('onway_all_stock', $onwayAllStock);
        $this->view->assign('onway_all_stock_price', $onwayAllStockPrice);
        $this->view->assign('onway_frame_all_stock', $onwayFrameAllStock);
        $this->view->assign('onway_frame_all_stock_price', $onwayFrameAllStockPrice);
        $this->view->assign('onway_ornament_all_stock', $onwayOrnamentAllStock);
        $this->view->assign('onway_ornament_all_stock_price', $onwayOrnamentAllStockPrice);
        $this->view->assign('allSalesNum', $allSalesNum);

        return $this->view->fetch();
    }


    /**
     * 数据统计
     *
     * @Description
     * @author wpl
     * @since 2020/02/25 13:52:27 
     * @return void
     */
    public function warehouse_data()
    {
        //默认当天
        $create_time = input('create_time');
        if ($create_time) {
            $time = explode(' ', $create_time);
            $map['a.created_at'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
        } else {
            $map['a.created_at'] = ['between', [date('Y-m-d 00:00:00'), date('Y-m-d H:i:s', time())]];
        }

        //统计时间段内未发货订单
        $zeeloolUnorderNum = $this->zeelool->undeliveredOrder($map);
        $vooguemeUnorderNum = $this->voogueme->undeliveredOrder($map);
        $nihaoUnorderNum = $this->nihao->undeliveredOrder($map);

        //统计时间段内未发货订单副数
        $zeeloolNum = $this->zeelool->undeliveredOrderNum($map);
        $vooguemeNum = $this->voogueme->undeliveredOrderNum($map);
        $nihaoNum = $this->nihao->undeliveredOrderNum($map);

        //统计处方镜
        $zeeloolOrderPrescriptionNum = $this->zeelool->getOrderPrescriptionNum($map);
        $vooguemeOrderPrescriptionNum = $this->voogueme->getOrderPrescriptionNum($map);
        $nihaoOrderPrescriptionNum = $this->nihao->getOrderPrescriptionNum($map);

        //统计现货处方镜
        $zeeloolSpotOrderPrescriptionNum = $this->zeelool->getSpotOrderPrescriptionNum($map);
        $vooguemeSpotOrderPrescriptionNum = $this->voogueme->getSpotOrderPrescriptionNum($map);
        $nihaoSpotOrderPrescriptionNum = $this->nihao->getSpotOrderPrescriptionNum($map);

        //统计定制处方镜副数
        $zeeloolCustomOrderPrescriptionNum = $this->zeelool->getCustomOrderPrescriptionNum($map);
        $vooguemeCustomOrderPrescriptionNum = $this->voogueme->getCustomOrderPrescriptionNum($map);
        $nihaoCustomOrderPrescriptionNum = $this->nihao->getCustomOrderPrescriptionNum($map);

        //统计仅镜架订单
        $zeeloolFrameOrderNum = $this->zeelool->frameOrder($map);
        $vooguemeFrameOrderNum = $this->voogueme->frameOrder($map);
        $nihaoFrameOrderNum = $this->nihao->frameOrder($map);

        //统计处方度数范围数据
        $skuRes = $this->order_sku_num($create_time);

        $this->view->assign('zeeloolUnorderNum', $zeeloolUnorderNum);
        $this->view->assign('vooguemeUnorderNum', $vooguemeUnorderNum);
        $this->view->assign('nihaoUnorderNum', $nihaoUnorderNum);
        $this->view->assign('zeeloolNum', $zeeloolNum);
        $this->view->assign('vooguemeNum', $vooguemeNum);
        $this->view->assign('nihaoNum', $nihaoNum);
        $this->view->assign('zeeloolOrderPrescriptionNum', $zeeloolOrderPrescriptionNum);
        $this->view->assign('vooguemeOrderPrescriptionNum', $vooguemeOrderPrescriptionNum);
        $this->view->assign('nihaoOrderPrescriptionNum', $nihaoOrderPrescriptionNum);
        $this->view->assign('zeeloolSpotOrderPrescriptionNum', $zeeloolSpotOrderPrescriptionNum);
        $this->view->assign('vooguemeSpotOrderPrescriptionNum', $vooguemeSpotOrderPrescriptionNum);
        $this->view->assign('nihaoSpotOrderPrescriptionNum', $nihaoSpotOrderPrescriptionNum);
        $this->view->assign('zeeloolCustomOrderPrescriptionNum', $zeeloolCustomOrderPrescriptionNum);
        $this->view->assign('vooguemeCustomOrderPrescriptionNum', $vooguemeCustomOrderPrescriptionNum);
        $this->view->assign('nihaoCustomOrderPrescriptionNum', $nihaoCustomOrderPrescriptionNum);
        $this->view->assign('zeeloolFrameOrderNum', $zeeloolFrameOrderNum);
        $this->view->assign('vooguemeFrameOrderNum', $vooguemeFrameOrderNum);
        $this->view->assign('nihaoFrameOrderNum', $nihaoFrameOrderNum);
        $this->view->assign('created_at', $create_time);
        $this->view->assign('skuRes', $skuRes);
        return $this->view->fetch();
    }

    /**
     * 根据处方范围统计SKU副数
     *
     * @Description
     * @author wpl
     * @param mixed $create_time 时间筛选
     * @since 2020/03/03 18:05:00 
     * @return void
     */
    protected function order_sku_num($create_time)
    {
        /**
         * 原：A SPH(0-300) and CYL<200
         * 原：B (SPH(0-300) and CYL>200) or (SPH(300-600) and CYL<200)
         * 原：C (SPH(300-600) and CYL>200) or (SPH>600 and CYL>0)
         * A sph > -3.00 and sph < 0 and cyl < 2.00
         * B (sph > -3.00 and sph < 0 AND cyl > 2.00) OR (sph < -3.00 and sph > -6.00 AND cyl < 2.00)
         * C (sph < -3.00 and sph > -6.00 AND cyl > 2.00) OR ( sph > -6.00 AND cyl > 0)
         */

        //默认当天
        if ($create_time) {
            $time = explode(' ', $create_time);
            $where = "created_at between '" . $time[0] . ' ' . $time[1] . "' and '" . $time[3] . ' ' . $time[4] . "'";
        } else {
            $stime = date('Y-m-d 00:00:00');
            $etime = date('Y-m-d H:i:s', time());
            $where = "created_at between '" . $stime . "' and '" . $etime . "'";
        }
        $sql = "select SUM(IF((b.sph > - 3 AND b.sph < 0 ) AND b.cyl < 2, 1, 0 )) AS A,
        SUM(IF(( sph > - 3.00 AND sph < 0 AND cyl > 2.00 ) OR ( sph < - 3.00 AND sph > - 6.00 AND cyl < 2.00 ),1, 0 )) AS B,
        SUM(IF(( sph < - 3.00 AND sph > - 6.00 AND cyl > 2.00 ) OR ( sph > - 6.00 AND cyl > 0 ),1, 0)) AS C from
        (select if (od_sph>os_sph,od_sph,os_sph) as sph,if(od_cyl>os_cyl,od_cyl,os_cyl) as cyl 
        from sales_flat_order_item_prescription where $where ) b where sph != '' and cyl != '' limit 1";
        $res = Db::connect('database.db_zeelool')->table('sales_flat_order_item_prescription')->query($sql);
        return $res;
    }

    /**
     * 销量排行榜
     *
     * @Description
     * @author wpl
     * @since 2020/03/11 16:14:50 
     * @return void
     */
    public function top_sale_list()
    {
        $create_time = input('create_time');
        $label = input('label', 1);
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            //默认当天
            if ($params['time']) {
                $time = explode(' ', $params['time']);
                $map['a.created_at'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
            } else {
                $map['a.created_at'] = ['between', [date('Y-m-d 00:00:00', strtotime('-7 day')), date('Y-m-d H:i:s', time())]];
            }

            /***********图表*************/
            $cachename = 'top_sale_list_' . md5(serialize($map)) . '_' . $params['site'];
            $res = cache($cachename);
            if (!$res) {
                if ($params['site'] == 1) {
                    $res = $this->zeelool->getOrderSalesNumTop30([], $map);
                } elseif ($params['site'] == 2) {
                    $res = $this->voogueme->getOrderSalesNumTop30([], $map);
                } elseif ($params['site'] == 3) {
                    $res = $this->nihao->getOrderSalesNumTop30([], $map);
                } elseif ($params['site'] == 4) {
                    $res = $this->meeloog->getOrderSalesNumTop30([], $map);
                }
                cache($cachename, $res, 7200);
            }

            if ($res) {
                array_multisort($res, SORT_ASC, $res);
            }

            $json['firtColumnName'] = $res ? array_keys($res) : [];
            $json['columnData'] = [
                'type' => 'bar',
                'data' => $res ? array_values($res) : [],
                'name' => '销售排行榜'
            ];
            /***********END*************/
            //列表
            $result = [];
            if ($params['type'] == 'list') {
                $itemPlatformSku = new \app\admin\model\itemmanage\ItemPlatformSku();
                if ($params['site'] == 1) {
                    //查询对应平台销量
                    $list = $this->zeelool->getOrderSalesNum([], $map);
                    //查询对应平台商品SKU
                    $skus = $itemPlatformSku->getWebSkuAll(1);
                } elseif ($params['site'] == 2) {
                    //查询对应平台销量
                    $list = $this->voogueme->getOrderSalesNum([], $map);
                    //查询对应平台商品SKU
                    $skus = $itemPlatformSku->getWebSkuAll(2);
                } elseif ($params['site'] == 3) {
                    //查询对应平台销量
                    $list = $this->nihao->getOrderSalesNum([], $map);
                    //查询对应平台商品SKU
                    $skus = $itemPlatformSku->getWebSkuAll(3);
                } elseif ($params['site'] == 4) {
                    //查询对应平台销量
                    $list = $this->meeloog->getOrderSalesNum([], $map);
                    //查询对应平台商品SKU
                    $skus = $itemPlatformSku->getWebSkuAll(4);
                }
                $productInfo = $this->item->getSkuInfo();
                $list = $list ?? [];
                $i = 0;
                foreach ($list as $k => $v) {
                    $result[$i]['platformsku'] = $k;
                    $result[$i]['sales_num'] = $v;
                    $result[$i]['sku'] = $skus[$k]['sku'];
                    $result[$i]['is_up'] = $skus[$k]['outer_sku_status'];
                    $result[$i]['available_stock'] = $productInfo[$skus[$k]['sku']]['available_stock'];
                    $result[$i]['name'] = $productInfo[$skus[$k]['sku']]['name'];
                    $result[$i]['type_name'] = $productInfo[$skus[$k]['sku']]['type_name'];
                    $i++;
                }
            }

            return json(['code' => 1, 'data' => $json, 'rows' => $result]);
        }
        $this->assign('create_time', $create_time);
        $this->assign('label', $label);
        $this->assignconfig('create_time', $create_time);
        $this->assignconfig('label', $label);
        return $this->view->fetch();
    }

    /**
     * 仓库数据分析
     *
     * @Description
     * @author wpl
     * @since 2020/03/13 16:35:07 
     * @return void
     */
    public function warehouse_data_analysis()
    {
        $dataConfig = new \app\admin\model\DataConfig();
        if ($this->request->isAjax()) {
            $key = input('key');
            if ($key == 'pie') {
                //镜架库存统计
                $frameStock = $dataConfig->where('key', 'frameStock')->value('value');

                //镜架总金额 
                $frameStockPrice = $dataConfig->where('key', 'frameStockPrice')->value('value');

                //饰品库存
                $ornamentsStock = $dataConfig->where('key', 'ornamentsStock')->value('value');

                //饰品库存总金额
                $ornamentsStockPrice = $dataConfig->where('key', 'ornamentsStockPrice')->value('value');

                //样品库存
                $sampleNumStock = $dataConfig->where('key', 'sampleNumStock')->value('value');

                //样品库存总金额
                $sampleNumStockPrice = $dataConfig->where('key', 'sampleNumStockPrice')->value('value');

                $json['column'] = ['镜架库存', '饰品库存', '辅料库存', '留样库存'];
                $json['columnData'] = [
                    [
                        'name' => '镜架库存',
                        'value' => $frameStock,
                    ],
                    [
                        'name' => '饰品库存',
                        'value' => $ornamentsStock,
                    ],
                    [
                        'name' => '辅料库存',
                        'value' => 0,
                    ],
                    [
                        'name' => '留样库存',
                        'value' => $sampleNumStock,
                    ]
                ];

                return json(['code' => 1, 'data' => $json]);
            } elseif ($key == 'line') {
                //查询三个站数据
                $orderStatistics = new \app\admin\model\OrderStatistics();
                $list = $orderStatistics->getAllData();

                //查询每天处理的订单
                $orderLog = new \app\admin\model\OrderLog();
                $order_process_res = $orderLog->getOrderProcessNum();

                $all_sales_num  = [];
                $order_process_num  = [];
                foreach ($list as $k => $v) {
                    $all_sales_num[$v['create_date']] = $v['all_sales_num'];
                    $order_process_num[$v['create_date']] = $order_process_res[$v['create_date']] ?? 0;
                }

                $json['xcolumnData'] = array_keys($all_sales_num);
                $json['column'] = ['每天订单量', '每天处理订单量'];
                $json['columnData'] = [
                    [
                        'name' => '每天订单量',
                        'type' => 'line',
                        'smooth' => true,
                        'data' =>  array_values($all_sales_num)
                    ],
                    [
                        'name' => '每天处理订单量',
                        'type' => 'line',
                        'smooth' => true,
                        'data' => array_values($order_process_num)
                    ],

                ];
                return json(['code' => 1, 'data' => $json]);
            } elseif ($key == 'list') {
                $model = new \app\admin\model\WarehouseData();
                list($where, $sort, $order, $offset, $limit) = $this->buildparams();
                $total = $model
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

                $list = $model
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
                $list = collection($list)->toArray();
                $result = array("total" => $total, "rows" => $list);

                return json($result);
            }
        }


        /*******************************库存数据***********************************/
        //仓库总库存
        $allStock = $dataConfig->where('key', 'allStock')->value('value');

        //仓库库存总金额       
        $allStockPrice = $dataConfig->where('key', 'allStockPrice')->value('value');

        //在途库存
        $onwayAllStock = $dataConfig->where('key', 'onwayAllStock')->value('value');

        //在途库存总金额
        $onwayAllStockPrice = $dataConfig->where('key', 'onwayAllStockPrice')->value('value');

        //库存周转天数
        $stock7days = $dataConfig->where('key', 'stock7days')->value('value');

        //可用库存
        $available_stock = $this->item->getAllAvailableStock();

        //样品库存
        $sampleNumStock = $dataConfig->where('key', 'sampleNumStock')->value('value');

        //样品库存总金额
        $sampleNumStockPrice = $dataConfig->where('key', 'sampleNumStockPrice')->value('value');

        //镜架库存统计
        $frameStock = $dataConfig->where('key', 'frameStock')->value('value');

        //镜架总金额 
        $frameStockPrice = $dataConfig->where('key', 'frameStockPrice')->value('value');

        //镜片库存
        $lensStock = $dataConfig->where('key', 'lensStock')->value('value');

        //镜片库存总金额
        $lensStockPrice = $dataConfig->where('key', 'lensStockPrice')->value('value');

        //饰品库存
        $ornamentsStock = $dataConfig->where('key', 'ornamentsStock')->value('value');

        //饰品库存总金额
        $ornamentsStockPrice = $dataConfig->where('key', 'ornamentsStockPrice')->value('value');

        //统计30天总销量
        $orderStatistics = new \app\admin\model\OrderStatistics();
        $days30Num = $orderStatistics->get30daysNum();

        //未出库总订单
        $allUnorderNum = $dataConfig->where('key', 'allUnorderNum')->value('value');

        //超时订单总数
        $overtimeOrder = $dataConfig->where('key', 'overtimeOrder')->value('value');

        //总SKU数
        $skuNum = $dataConfig->where('key', 'skuNum')->value('value');

        //30天处理订单
        $orderLog = new \app\admin\model\OrderLog();
        $days30OrderProcessNum = $orderLog->get30daysOrderProcessNum();

        //统计库存分级
        $stockData = $this->item->stockClass();

        //加工时效
        $processingAgingData = $this->processing_aging_data();


        //计算产品等级的数量
        $productGrade = new \app\admin\model\ProductGrade();
        $where = [];
        $where['grade'] = 'A+';
        $AA_num = $productGrade->where($where)->sum('counter');

        $where['grade'] = 'A';
        $A_num = $productGrade->where($where)->sum('counter');

        $where['grade'] = 'B';
        $B_num = $productGrade->where($where)->sum('counter');

        $where['grade'] = 'C+';
        $CA_num = $productGrade->where($where)->sum('counter');

        $where['grade'] = 'C';
        $C_num = $productGrade->where($where)->sum('counter');

        $where['grade'] = 'D';
        $D_num = $productGrade->where($where)->sum('counter');

        $where['grade'] = 'E';
        $E_num = $productGrade->where($where)->sum('counter');

        $where['grade'] = 'F';
        $F_num = $productGrade->where($where)->sum('counter');

        //总数
        $all_num = $AA_num + $A_num + $B_num + $CA_num + $C_num + $D_num + $E_num + $F_num;
        //A级数量即总占比
        $res['AA_num'] = $AA_num;
        $res['AA_percent'] = round($AA_num / $all_num * 100, 2);
        $res['A_num'] = $A_num;
        $res['A_percent'] = round($A_num / $all_num * 100, 2);
        $res['B_num'] = $B_num;
        $res['B_percent'] = round($B_num / $all_num * 100, 2);
        $res['CA_num'] = $CA_num;
        $res['CA_percent'] = round($CA_num / $all_num * 100, 2);
        $res['C_num'] = $C_num;
        $res['C_percent'] = round($C_num / $all_num * 100, 2);
        $res['D_num'] = $D_num;
        $res['D_percent'] = round($D_num / $all_num * 100, 2);
        $res['E_num'] = $E_num;
        $res['E_percent'] = round($E_num / $all_num * 100, 2);
        $res['F_num'] = $F_num;
        $res['F_percent'] = round($F_num / $all_num * 100, 2);

        $this->view->assign('gradeSkuStock', $productGrade->getSkuStock());
        $this->view->assign('res', $res);

        $this->view->assign('processingAgingData', $processingAgingData);
        $this->view->assign('skuNum', $skuNum);
        $this->view->assign('stockData', $stockData);
        $this->view->assign('days30OrderProcessNum', $days30OrderProcessNum);
        $this->view->assign('overtimeOrder', $overtimeOrder);
        $this->view->assign('days30Num', $days30Num);
        $this->view->assign('allUnorderNum', $allUnorderNum);
        $this->view->assign('allStock', $allStock);
        $this->view->assign('allStockPrice', $allStockPrice);
        $this->view->assign('onwayAllStock', $onwayAllStock);
        $this->view->assign('onwayAllStockPrice', $onwayAllStockPrice);
        $this->view->assign('stock7days', $stock7days);
        $this->view->assign('available_stock', $available_stock);
        $this->view->assign('sampleNumStock', $sampleNumStock);
        $this->view->assign('sampleNumStockPrice', $sampleNumStockPrice);
        $this->view->assign('frameStock', $frameStock);
        $this->view->assign('frameStockPrice', $frameStockPrice);
        $this->view->assign('lensStock', $lensStock);
        $this->view->assign('lensStockPrice', $lensStockPrice);
        $this->view->assign('ornamentsStock', $ornamentsStock);
        $this->view->assign('ornamentsStockPrice', $ornamentsStockPrice);
        return $this->view->fetch();
    }

    /**
     * 加工时效数据统计
     *
     * @Description
     * @author wpl
     * @since 2020/03/19 09:38:24 
     * @return void
     */
    protected function processing_aging_data()
    {
        $zeelool = $this->zeelool->getProcessingAging();

        $voogueme = $this->voogueme->getProcessingAging();

        $nihao = $this->nihao->getProcessingAging();

        //打印标签未超时未处理
        $data['labelNotOvertime'] = $zeelool['labelNotOvertime'] + $voogueme['labelNotOvertime'] + $nihao['labelNotOvertime'];
        //配镜架未超时未处理
        $data['frameNotOvertime'] = $zeelool['frameNotOvertime'] + $voogueme['frameNotOvertime'] + $nihao['frameNotOvertime'];
        //配镜片未超时未处理
        $data['lensNotOvertime'] = $zeelool['lensNotOvertime'] + $voogueme['lensNotOvertime'] + $nihao['lensNotOvertime'];
        //加工未超时未处理
        $data['machiningNotOvertime'] = $zeelool['machiningNotOvertime'] + $voogueme['machiningNotOvertime'] + $nihao['machiningNotOvertime'];
        //质检未超时未处理
        $data['checkNotOvertime'] = $zeelool['checkNotOvertime'] + $voogueme['checkNotOvertime'] + $nihao['checkNotOvertime'];
        //打印标签超时未处理
        $data['labelOvertime'] = $zeelool['labelOvertime'] + $voogueme['labelOvertime'] + $nihao['labelOvertime'];
        //配镜架超时未处理
        $data['frameOvertime'] = $zeelool['frameOvertime'] + $voogueme['frameOvertime'] + $nihao['frameOvertime'];
        //配镜片超时未处理
        $data['lensOvertime'] = $zeelool['lensOvertime'] + $voogueme['lensOvertime'] + $nihao['lensOvertime'];
        //加工超时未处理
        $data['machiningOvertime'] = $zeelool['machiningOvertime'] + $voogueme['machiningOvertime'] + $nihao['machiningOvertime'];
        //质检超时未处理
        $data['checkOvertime'] = $zeelool['checkOvertime'] + $voogueme['checkOvertime'] + $nihao['checkOvertime'];
        //打印标签未超时已处理
        $data['labelNotOvertimeProcess'] = $zeelool['labelNotOvertimeProcess'] + $voogueme['labelNotOvertimeProcess'] + $nihao['labelNotOvertimeProcess'];
        //配镜架未超时已处理
        $data['frameNotOvertimeProcess'] = $zeelool['frameNotOvertimeProcess'] + $voogueme['frameNotOvertimeProcess'] + $nihao['frameNotOvertimeProcess'];
        //配镜片未超时已处理
        $data['lensNotOvertimeProcess'] = $zeelool['lensNotOvertimeProcess'] + $voogueme['lensNotOvertimeProcess'] + $nihao['lensNotOvertimeProcess'];
        //加工未超时已处理
        $data['machiningNotOvertimeProcess'] = $zeelool['machiningNotOvertimeProcess'] + $voogueme['machiningNotOvertimeProcess'] + $nihao['machiningNotOvertimeProcess'];
        //质检未超时已处理
        $data['checkNotOvertimeProcess'] = $zeelool['checkNotOvertimeProcess'] + $voogueme['checkNotOvertimeProcess'] + $nihao['checkNotOvertimeProcess'];
        //打印标签超时已处理
        $data['labelOvertimeProcess'] = $zeelool['labelOvertimeProcess'] + $voogueme['labelOvertimeProcess'] + $nihao['labelOvertimeProcess'];
        //配镜架超时已处理
        $data['frameOvertimeProcess'] = $zeelool['frameOvertimeProcess'] + $voogueme['frameOvertimeProcess'] + $nihao['frameOvertimeProcess'];
        //配镜片超时已处理
        $data['lensOvertimeProcess'] = $zeelool['lensOvertimeProcess'] + $voogueme['lensOvertimeProcess'] + $nihao['lensOvertimeProcess'];
        //加工超时已处理
        $data['machiningOvertimeProcess'] = $zeelool['machiningOvertimeProcess'] + $voogueme['machiningOvertimeProcess'] + $nihao['machiningOvertimeProcess'];
        //质检超时已处理
        $data['checkOvertimeProcess'] = $zeelool['checkOvertimeProcess'] + $voogueme['checkOvertimeProcess'] + $nihao['checkOvertimeProcess'];

        return $data;
    }

    /**
     * 采购数据分析
     *
     * @Description
     * @author wpl
     * @since 2020/03/20 13:42:08 
     * @return void
     */
    public function purchase_data_analysis()
    {
        $purchase = new \app\admin\model\purchase\PurchaseOrder();
        if ($this->request->isAjax()) {
            $purchase_type = input('purchase_type');
            $key = input('key');
            $time = input('time');
            //拆分
            if ($time) {
                $arr = explode(' ', $time);
                $time = [$arr[0] . ' ' . $arr[1], $arr[3] . ' ' . $arr[4]];
            }
            if ($key == 'pie01') {
                $data = $purchase->getPurchaseNumNowPerson([], $time);
                $json['column'] = array_keys($data);
                //转二维数组
                if ($data) {
                    $list = [];
                    $i = 0;
                    foreach ($data as $k => $v) {
                        $list[$i]['name'] = $k;
                        $list[$i]['value'] = $v;
                        $i++;
                    }
                }
                $json['columnData'] = $list;
            } elseif ($key == 'pie02') {
                $data = $purchase->getPurchaseOrderNumNowPerson([], $time);
                $json['column'] = array_keys($data);
                //转二维数组
                if ($data) {
                    $list = [];
                    $i = 0;
                    foreach ($data as $k => $v) {
                        $list[$i]['name'] = $k;
                        $list[$i]['value'] = $v;
                        $i++;
                    }
                }
                $json['columnData'] = $list;
            } else {
                $warehouse_model = new \app\admin\model\WarehouseData();
                $warehouse_data = $warehouse_model->getPurchaseData();
                //线上采购单
                if ($purchase_type == 1) {
                    $barcloumndata = array_column($warehouse_data, 'online_purchase_num');
                    $linecloumndata = array_column($warehouse_data, 'online_purchase_price');
                } elseif ($purchase_type == 2) {
                    //线下采购单
                    $barcloumndata = array_column($warehouse_data, 'purchase_num');
                    $linecloumndata = array_column($warehouse_data, 'purchase_price');
                } else {
                    //全部采购单
                    $barcloumndata = array_column($warehouse_data, 'all_purchase_num');
                    $linecloumndata = array_column($warehouse_data, 'all_purchase_price');
                }

                $json['xColumnName'] = array_column($warehouse_data, 'create_date');
                $json['columnData'] = [
                    [
                        'type' => 'bar',
                        'data' => $barcloumndata,
                        'name' => '采购数量'
                    ],
                    [
                        'type' => 'line',
                        'data' => $linecloumndata,
                        'name' => '采购金额',
                        'yAxisIndex' => 1,
                        'smooth' => true //平滑曲线
                    ],

                ];
            }

            return json(['code' => 1, 'data' => $json]);
        }
        $dataConfig = new \app\admin\model\DataConfig();
        //采购总数
        $purchaseNum = $dataConfig->getValue('purchaseNum');
        //采购总金额
        $purchasePrice = $dataConfig->getValue('purchasePrice');
        //采购总SKU数
        $purchaseSkuNum = $dataConfig->getValue('purchaseSkuNum');
        //采购镜架总数
        $purchaseFrameNum = $dataConfig->getValue('purchaseFrameNum');
        //当月采购镜架总金额
        $purchaseFramePrice = $dataConfig->getValue('purchaseFramePrice');
        //采购到货总数
        $arrivalsNum = $dataConfig->getValue('arrivalsNum');
        //采购平均单价
        $purchaseAveragePrice = $dataConfig->getValue('purchaseAveragePrice');
        //当月销售总数
        $salesNum = $dataConfig->getValue('salesNum');
        //当月销售总成本
        $salesCost = $dataConfig->getValue('salesCost');
        //当月质检合格总数
        $quantityNum = $dataConfig->getValue('quantityNum');

        //当月线上采购数量
        $onlinePurchaseNum = $purchase->getOnlinePurchaseNum();
        //当月线下采购数量
        $underPurchaseNum = $purchase->getUnderPurchaseNum();

        //采购SKU排行数据
        $data = $purchase->getPurchaseNumRanking();

        $this->assign('data', $data);
        $this->assign('purchaseAveragePrice', $purchaseAveragePrice);
        $this->assign('purchaseFramePrice', $purchaseFramePrice);
        $this->assign('salesNum', $salesNum);
        $this->assign('salesCost', $salesCost);
        $this->assign('quantityNum', $quantityNum);
        $this->assign('onlinePurchaseNum', $onlinePurchaseNum);
        $this->assign('underPurchaseNum', $underPurchaseNum);
        $this->assign('purchaseNum', $purchaseNum);
        $this->assign('purchasePrice', $purchasePrice);
        $this->assign('purchaseSkuNum', $purchaseSkuNum);
        $this->assign('purchaseFrameNum', $purchaseFrameNum);
        $this->assign('arrivalsNum', $arrivalsNum);
        return $this->view->fetch();
    }

    /**
     * 测试
     *
     * @Description
     * @author wpl
     * @since 2020/03/24 14:04:27 
     * @return void
     */
    public function test()
    {
        $starttime = strtotime(date('Y-m-01 00:00:00', time())) - 8 * 3600;
        $endtime = strtotime(date('Y-m-d H:i:s', time()));
        $track = new Trackingmore();
        $track = $track->getStatusNumberCount($starttime, $endtime);
        dump($track);
        die;
    }
}
