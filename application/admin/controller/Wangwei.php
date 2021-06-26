<?php
/**
 * Wangwei.php
 * @author huangbinbin
 * @date   2021/6/25 14:56
 */

namespace app\admin\controller;


use app\admin\model\itemmanage\Item;
use app\admin\model\itemmanage\ItemPlatformSku;
use app\admin\model\order\order\NewOrderItemOption;
use app\admin\model\order\order\NewOrderItemProcess;
use app\admin\model\order\order\WaveOrder;
use app\admin\model\saleaftermanage\WorkOrderChangeSku;
use app\admin\model\saleaftermanage\WorkOrderList;
use app\admin\model\saleaftermanage\WorkOrderMeasure;
use app\admin\model\StockLog;
use app\admin\model\warehouse\StockSku;
use app\common\controller\Backend;
use think\Controller;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Log;
use think\Model;
use think\Request;

/**
 * 王伟批量创建补发订单的功能
 *
 * Class Wangwei
 * @package app\admin\controller
 * @author  huangbinbin
 * @date    2021/6/25 14:56
 */
class Wangwei extends Backend
{
    protected $noNeedLogin = ['*'];
    protected $order = null;
    protected $orderItem = null;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->order = new \app\admin\model\order\order\NewOrder();
        $this->orderItem = new NewOrderItemOption();
        $this->work = new WorkOrderList();
    }

    public function test()
    {
        $siteType = input('sitetype');
        $workId = input('workid');
        $incrementId = input('incrementid');
        $measureId = input('measureid');
        $this->createOrder($siteType, $workId, $incrementId, $measureId);
    }

    /**
     * 创建补发单
     *
     * @param $siteType
     * @param $work_id
     *
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function createOrder($siteType, $workId, $incrementId, $measureId)
    {
        echo 'createOrder' . PHP_EOL;
        $orderDetail = $this->getOrderDetail($siteType, $incrementId);

        //如果存在补发单的措施
        if ($orderDetail) {
            echo "补发单创建请求" . PHP_EOL;
            echo serialize($orderDetail) . PHP_EOL;
            echo "补发单创建请求two" . PHP_EOL;
            if (!empty($orderDetail)) {
                try {
                    //补发扣库存
                    $pathinfo = 'magic/order/createOrder';
                    $res = $this->work->httpRequest($siteType, $pathinfo, $orderDetail, 'POST');

                    $replacementOrder = $res['increment_id'] ?? '';
                    if (!$replacementOrder) {
                        file_put_contents('./wangwei_error.log', $incrementId . PHP_EOL, FILE_APPEND);
                        echo '请求补发失败' . PHP_EOL;

                        return false;
                    }

                    $workOrderChangeSkusAll = [];
                    foreach ($orderDetail['product'] as $key => $val) {
                        $workOrderChangeSkusAll[$key] = [
                            'work_id'             => $workId,
                            'increment_id'        => $incrementId,
                            'platform_type'       => $siteType,
                            'original_name'       => $val['sku'],
                            'original_sku'        => $val['sku'],
                            'original_number'     => $val['qty'],
                            'change_type'         => 5,
                            'change_sku'          => $val['sku'],
                            'change_number'       => $val['qty'],
                            'recipe_type'         => $val['prescription_type'],
                            'lens_type'           => $val['lens_type'],
                            'od_sph'              => $val['od_sph'],
                            'od_cyl'              => $val['od_cyl'],
                            'od_axis'             => $val['od_axis'],
                            'od_add'              => $val['od_add'],
                            'pd_r'                => $val['pd_r'],
                            'od_pv'               => $val['od_pv'],
                            'od_bd'               => $val['od_bd'],
                            'od_pv_r'             => $val['od_pv_r'],
                            'od_bd_r'             => $val['od_bd_r'],
                            'os_sph'              => $val['os_sph'],
                            'os_cyl'              => $val['os_cyl'],
                            'os_axis'             => $val['os_axis'],
                            'os_add'              => $val['os_add'],
                            'pd_l'                => $val['pd_l'],
                            'os_pv'               => $val['os_pv'],
                            'os_bd'               => $val['os_bd'],
                            'os_pv_r'             => $val['os_pv_r'],
                            'os_bd_r'             => $val['os_bd_r'],
                            'create_person'       => '王伟',
                            'update_time'         => date('Y-m-d H:i:s'),
                            'create_time'         => date('Y-m-d H:i:s'),
                            'measure_id'          => $measureId,
                            'replacement_order'   => $replacementOrder,
                            'email'               => $orderDetail['email'],
                            'userinfo_option'     => serialize($orderDetail),
                            'prescription_option' => serialize($val),
                        ];
                    }

                    //添加workorderchangesku的数据
                    WorkOrderChangeSku::insertAll($workOrderChangeSkusAll);
                    //回写主表
                    WorkOrderList::where('id', $workId)->setField('replacement_order', $replacementOrder);
                    file_put_contents('./wangwei_suceess.log', $replacementOrder . PHP_EOL, FILE_APPEND);
                    $res = $this->deductionStock($workId, $measureId);
                    echo "补发单SUCCESS - " . $replacementOrder . PHP_EOL;
                } catch (Exception $e) {
                    file_put_contents('./wangwei.log', $incrementId . PHP_EOL, FILE_APPEND);
                    echo $e->getMessage() . PHP_EOL;

                    return false;

                }

                return true;
            }
        }
    }

    //扣减库存逻辑
    public function deductionStock($work_id, $measure_id)
    {
        $measuerInfo = 5;
        $workOrderList = WorkOrderList::where(['id' => $work_id])->field('id,work_platform,platform_order,replacement_order')->find();
        $whereMeasure['work_id'] = $work_id;
        $whereMeasure['measure_id'] = $measure_id;
        $whereMeasure['change_type'] = $measuerInfo;
        $result = WorkOrderChangeSku::where($whereMeasure)->field('id,increment_id,platform_type,change_type,original_sku,original_number,change_sku,change_number,item_order_number')->select();
        if (!$result) {
            return false;
        }

        $result = collection($result)->toArray();
        if (5 == $measuerInfo) {//补发
            $info = $this->workPresent($work_id, $workOrderList->work_platform, $workOrderList->platform_order, $result, 2);
        }

        return $info;
    }

    /**
     * 赠品、补发-库存处理
     *
     * @param int    $work_id        工单ID
     * @param int    $order_platform 平台类型
     * @param string $increment_id   订单号
     * @param array  $change_row     change_sku表数据
     * @param int    $type           类型：1增品 2补发
     *
     * @Author lzh
     * @return bool
     */
    public function workPresent($work_id, $order_platform, $increment_id, $change_row, $type)
    {
        if (!$work_id || !$order_platform || !$increment_id || !$change_row) {
            return false;
        }

        $_item = new Item();
        $_platform_sku = new  ItemPlatformSku();
        $_stock_log = new StockLog();

        foreach ($change_row as $v) {
            //获取sku
            $arr = explode('-', trim($v['original_sku']));
            $original_sku = 2 < count($arr) ? $arr[0] . '-' . $arr[1] : trim($v['original_sku']);

            if (!$original_sku) {
                continue;
            }

            //sku数量
            $original_number = $v['original_number'];
            //仓库sku、库存
            $warehouse_original_info = $_platform_sku
                ->field('sku,stock')
                ->where(['platform_sku' => $original_sku, 'platform_type' => $order_platform])
                ->find();
            $warehouse_original_sku = $warehouse_original_info['sku'];

            //获取当前可用库存、总库存
            $item_before = $_item
                ->field('available_stock,occupy_stock,stock')
                ->where(['sku' => $warehouse_original_sku])
                ->find();

            //开启事务
            $_item->startTrans();
            $_platform_sku->startTrans();
            $_stock_log->startTrans();
            try {
                if (1 == $type) { //赠品
                    //减少可用库存、总库存
                    $_item
                        ->where(['sku' => $warehouse_original_sku])
                        ->dec('available_stock', $original_number)
                        ->dec('stock', $original_number)
                        ->update();
                } else { //补发
                    //减少可用库存，增加占用库存
                    $_item
                        ->where(['sku' => $warehouse_original_sku])
                        ->dec('available_stock', $original_number)
                        ->inc('occupy_stock', $original_number)->update();
                }

                //扣减对应站点虚拟库存
                $_platform_sku
                    ->where(['sku' => $warehouse_original_sku, 'platform_type' => $order_platform])
                    ->setDec('stock', $original_number);

                //记录库存日志
                $_stock_log->setData([
                    'type'                   => 2,
                    'site'                   => $order_platform,
                    'modular'                => 1 == $type ? 9 : 8,
                    'change_type'            => 1 == $type ? 15 : 14,
                    'source'                 => 1,
                    'sku'                    => $warehouse_original_sku,
                    'number_type'            => 1,
                    'order_number'           => $increment_id,
                    'public_id'              => $work_id,
                    'available_stock_before' => $item_before['available_stock'],
                    'available_stock_change' => -$original_number,
                    'stock_before'           => 1 == $type ? $item_before['stock'] : 0,
                    'stock_change'           => 1 == $type ? -$original_number : 0,
                    'occupy_stock_before'    => 2 == $type ? $item_before['occupy_stock'] : 0,
                    'occupy_stock_change'    => 2 == $type ? $original_number : 0,
                    'fictitious_before'      => $warehouse_original_info['stock'],
                    'fictitious_change'      => -$original_number,
                    'create_person'          => '王伟',
                    'create_time'            => time(),
                ]);

                $_item->commit();
                $_platform_sku->commit();
                $_stock_log->commit();
            } catch (ValidateException $e) {
                $_item->rollback();
                $_platform_sku->rollback();
                $_stock_log->rollback();
                $this->error($e->getMessage());
            } catch (PDOException $e) {
                $_item->rollback();
                $_platform_sku->rollback();
                $_stock_log->rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                $_item->rollback();
                $_platform_sku->rollback();
                $_stock_log->rollback();
                $this->error($e->getMessage());
            }
        }

        return true;
    }


    /**
     * 获取订单详情
     *
     * @param $siteType
     * @param $incrementId
     *
     * @return string
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author huangbinbin
     * @date   2021/6/25 15:34
     */
    public function getOrderDetail($siteType, $incrementId)
    {
        $order = $this->order->where(['site' => $siteType, 'increment_id' => $incrementId])->find();
        $orderDetails = $this->orderItem->where('order_id', $order->id)->select();
        $postDataCommon = [
            'currency_code' => $order['order_currency_code'],
            'country'       => $order['country_id'],
            'shipping_type' => $order['shipping_method'],
            'telephone'     => $order['telephone'],
            'email'         => $order['customer_email'],
            'first_name'    => $order['customer_firstname'],
            'last_name'     => $order['customer_lastname'],
            'postcode'      => $order['postcode'],
            'city'          => $order['city'],
            'region_id'     => $order['region_id'],
            'street'        => $order['street'],
            'pay_method'    => $order['payment_method'],
            'cpf'           => $order['taxno'],
        ];
        $skuDetail = [];
        foreach ($orderDetails as $key => $orderDetail) {
            $pdCheck = $orderDetail['pdcheck'] == 'on' ? 'on' : '';
            $prismCheck = $orderDetail['prismcheck'] == 'on' ? 'on' : '';
            $is_frame_only = 0;
            if ($orderDetail['index_id'] || $orderDetail['coating_id'] || $orderDetail['color_id']) {
                $is_frame_only = 1;
            }
            if (!$orderDetail['prescription_type'] || $orderDetail['prescription_type'] == 'Frame Only') {
                $is_frame_only = 1;
            }

            $skuDetail[$key] = [
                'sku'               => $orderDetail['sku'],
                'qty'               => $orderDetail['qty'],
                'prescription_type' => $orderDetail['prescription_type'] ?: 'Frame Only',
                'is_frame_only'     => $is_frame_only,
                'od_sph'            => $orderDetail['od_sph'],
                'os_sph'            => $orderDetail['os_sph'],
                'od_cyl'            => $orderDetail['od_cyl'],
                'os_cyl'            => $orderDetail['os_cyl'],
                'od_axis'           => $orderDetail['od_axis'],
                'os_axis'           => $orderDetail['os_axis'],
                'od_add'            => $orderDetail['od_add'],
                'os_add'            => $orderDetail['os_add'],
                'pd'                => $orderDetail['pd'],
                'pdcheck'           => $pdCheck,
                'pd_r'              => $orderDetail['pd_r'],
                'pd_l'              => $orderDetail['pd_l'],
                'prismcheck'        => $prismCheck,
                'od_pv'             => $orderDetail['od_pv'],
                'os_pv'             => $orderDetail['os_pv'],
                'od_bd'             => $orderDetail['od_bd'],
                'os_bd'             => $orderDetail['os_bd'],
                'od_pv_r'           => $orderDetail['od_pv_r'],
                'os_pv_r'           => $orderDetail['os_pv_r'],
                'od_bd_r'           => $orderDetail['od_bd_r'],
                'os_bd_r'           => $orderDetail['os_bd_r'],
                'lens_id'           => $orderDetail['index_id'],
                'lens_name'         => $orderDetail['index_name'],
                'lens_type'         => $orderDetail['index_type'],
                'coating_id'        => $orderDetail['coating_id'],
                'coating_name'      => $orderDetail['coating_name'],
                'color_id'          => $orderDetail['color_id'],
                'color_name'        => '',
            ];
        }
        $postData['product'] = $skuDetail;

        return array_merge($postData, $postDataCommon);
    }


    public function create_wave_order()
    {
        $order_number = [
            '100276935',
            '130124228',
            '130124229',
            '100276936',
            '130124230',
            '100276937',
            '100276938',
            '100276939',
            '100276940',
            '100276941',
            '100276942',
            '100276943',
            '100276944',
            '100276945',
            '100276946',
            '130124231',
            '100276947',
            '130124232',
            '130124233',
            '100276948',
            '130124234',
            '130124235',
            '100276949',
            '100276950',
            '130124236',
            '130124237',
            '130124238',
            '130124239',
            '100276951',
            '100276952',
            '100276953',
            '100276954',
            '300052881',
            '100276955',
            '100276956',
            '100276957',
            '130124240',
            '100276958',
            '130124241',
            '130124242',
            '130124243',
            '130124244',
            '130124245',
            '100276959',
            '100276960',
            '100276961',
            '100276962',
            '100276963',
            '100276964',
            '100276965',
            '100276966',
            '100276967',
            '100276968',
            '130124246',
            '130124247',
            '100276969',
            '130124248',
            '100276970',
            '130124249',
            '100276971',
            '100276972',
            '100276973',
            '100276974',
            '100276975',
            '100276976',
            '100276977',
            '100276978',
            '100276979',
            '100276980',
            '100276981',
            '130124250',
            '100276982',
            '100276984',
            '100276985',
            '130124251',
            '130124252',
            '130124253',
            '130124254',
            '100276986',
            '100276987',
            '100276988',
            '100276989',
            '100276990',
            '100276991',
            '130124256',
            '100276992',
            '130124257',
            '100276993',
            '130124258',
            '130124259',
            '130124260',
            '130124261',
            '100276994',
            '100276995',
            '100276996',
            '100276997',
            '100276998',
            '130124262',
            '100276999',
            '100277000',
            '100277001',
            '100277002',
            '100277003',
            '130124263',
            '130124264',
            '100277004',
            '130124265',
            '130124266',
            '130124267',
            '130124268',
            '100277005',
            '130124269',
            '130124270',
            '130124271',
            '130124272',
            '100277006',
            '130124273',
            '100277007',
            '100277008',
            '100277009',
            '100277010',
            '100277011',
            '100277012',
            '100277013',
            '100277014',
            '100277015',
            '130124274',
            '130124275',
            '130124276',
            '100277016',
            '100277017',
            '130124277',
            '100277018',
            '100277019',
            '130124278',
            '100277020',
            '100277021',
            '100277022',
            '100277023',
            '100277024',
            '100277025',
            '100277026',
            '100277027',
            '100277028',
            '100277029',
            '100277030',
            '100277031',
            '100277032',
            '130124279',
            '100277033',
            '130124280',
            '130124281',
            '100277034',
            '100277035',
            '130124282',
            '100277036',
            '100277037',
            '130124283',
            '130124284',
            '130124285',
            '100277039',
            '130124286',
            '100277040',
            '100277041',
            '130124287',
            '100277042',
            '100277043',
            '130124288',
            '100277044',
            '100277045',
            '130124289',
            '130124290',
            '130124291',
            '100277046',
            '130124292',
            '100277047',
            '100277048',
            '100277049',
            '100277050',
            '100277051',
            '130124293',
            '130124294',
            '130124295',
            '130124296',
            '100277052',
            '100277053',
            '100277054',
            '130124297',
            '130124298',
            '100277055',
            '100277056',
            '100277057',
            '130124299',
            '130124300',
            '130124301',
            '130124302',
            '100277058',
            '100277059',
            '100277060',
            '100277061',
            '100277062',
            '100277063',
            '100277064',
            '130124303',
            '100277065',
            '100277066',
            '100277067',
            '100277068',
            '130124304',
            '130124305',
            '130124306',
            '100277069',
            '130124307',
            '130124308',
            '130124309',
            '130124310',
            '100277070',
            '100277071',
            '100277072',
            '130124311',
            '100277073',
            '100277074',
            '100277075',
            '100277076',
            '300052883',
            '130124312',
            '130124313',

        ];
        /**
         *
         * 生成规则
         * 1）按业务模式：品牌独立站、第三方平台店铺
         * 2）按时间段
         * 第一波次：00:00-2:59:59
         * 第二波次：3：00-5:59:59
         * 第三波次：6:00-8:59:59
         * 第四波次：9:00-11:59:59
         * 第五波次：12:00-14:59:59
         * 第六波次：15:00-17:59:59
         * 第七波次：18:00-20:59:59
         * 第八波次：21:00-23:59:59
         *
         */
        $where['a.increment_id'] = ['in', $order_number];

        $list = $this->order->where($where)->alias('a')
            ->field('a.increment_id,b.id,b.sku,a.created_at,a.updated_at,entity_id,a.site,a.is_custom_lens,a.stock_id')
            ->join(['fa_order_item_process' => 'b'], 'a.entity_id=b.magento_order_id and a.site=b.site')
            ->order('id desc')
            ->select();
        $list = collection($list)->toArray();
        //第三方站点id
        $third_site = [13, 14];
        $this->orderitemprocess = new NewOrderItemProcess();
        $itemPlatform = new ItemPlatformSku();
        $waveOrder = new WaveOrder();
        foreach ($list as $k => $v) {


            $stockId = 1; //郑州仓

            $params = [];
            $params['wave_order_number'] = 'BC' . date('YmdHis') . rand(100, 999) . rand(100, 999);
            $params['type'] = 1;
            $params['wave_time_type'] = 9;
            $params['stock_id'] = $stockId;
            $params['order_date'] = time();
            $params['createtime'] = time();
            $id = $waveOrder->insertGetId($params);

            //转换平台SKU
            $sku = $itemPlatform->getTrueSku($v['sku'], $v['site']);
            //根据sku查询库位排序
            $stockSku = new StockSku();
            $where = [];
            $where['c.type'] = 2;//默认拣货区
            $where['b.status'] = 1;//启用状态
            $where['a.is_del'] = 1;//正常状态
            $where['b.stock_id'] = $stockId;//查询对应仓库
            $location_data = $stockSku
                ->alias('a')
                ->where($where)
                ->where(['a.sku' => $sku])
                ->field('b.coding,b.picking_sort')
                ->join(['fa_store_house' => 'b'], 'a.store_id=b.id')
                ->join(['fa_warehouse_area' => 'c'], 'b.area_id=c.id')
                ->find();
            $this->order->where(['increment_id' => $v['increment_id']])->update(['stock_id' => 1]);
            $this->orderitemprocess->where(['id' => $v['id']])->update(['wave_order_id' => $id, 'stock_id' => 1, 'location_code' => $location_data['coding'], 'picking_sort' => $location_data['picking_sort']]);
        }
        echo "ok";
    }
}




