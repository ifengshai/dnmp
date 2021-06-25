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
use app\admin\model\saleaftermanage\WorkOrderChangeSku;
use app\admin\model\saleaftermanage\WorkOrderList;
use app\admin\model\saleaftermanage\WorkOrderMeasure;
use app\admin\model\StockLog;
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
        $this->createOrder($siteType, $workId,$incrementId,$measureId);
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
    public function createOrder($siteType, $workId,$incrementId,$measureId)
    {
        echo 'createOrder' . PHP_EOL;
        $orderDetail = $this->getOrderDetail($siteType,$incrementId);

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
                    if(!$replacementOrder) {
                        file_put_contents('./wangwei_error.log',$incrementId.PHP_EOL,FILE_APPEND);
                        echo '请求补发失败'.PHP_EOL;
                        return false;
                    }

                    $workOrderChangeSkusAll = [];
                    foreach($orderDetail['product'] as $key => $val) {
                        $workOrderChangeSkusAll[$key] = [
                            'work_id' => $workId,
                            'increment_id' => $incrementId,
                            'platform_type' => $siteType,
                            'original_name' => $val['sku'],
                            'original_sku' => $val['sku'],
                            'original_number' => $val['qty'],
                            'change_type' => 5,
                            'change_sku' => $val['sku'],
                            'change_number' => $val['qty'],
                            'recipe_type' => $val['prescription_type'],
                            'lens_type' => $val['lens_type'],
                            'od_sph' => $val['od_sph'],
                            'od_cyl' => $val['od_cyl'],
                            'od_axis' => $val['od_axis'],
                            'od_add' => $val['od_add'],
                            'pd_r' => $val['pd_r'],
                            'od_pv' => $val['od_pv'],
                            'od_bd' => $val['od_bd'],
                            'od_pv_r' => $val['od_pv_r'],
                            'od_bd_r' => $val['od_bd_r'],
                            'os_sph' => $val['os_sph'],
                            'os_cyl' => $val['os_cyl'],
                            'os_axis' => $val['os_axis'],
                            'os_add' => $val['os_add'],
                            'pd_l' => $val['pd_l'],
                            'os_pv' => $val['os_pv'],
                            'os_bd' => $val['os_bd'],
                            'os_pv_r' => $val['os_pv_r'],
                            'os_bd_r' => $val['os_bd_r'],
                            'create_person' => '王伟',
                            'update_time' => date('Y-m-d H:i:s'),
                            'create_time' => date('Y-m-d H:i:s'),
                            'measure_id' => $measureId,
                            'replacement_order' => $replacementOrder,
                            'email' => $orderDetail['email'],
                            'userinfo_option' => serialize($orderDetail),
                            'prescription_option' => serialize($val)
                        ];
                    }

                    //添加workorderchangesku的数据
                    WorkOrderChangeSku::insertAll($workOrderChangeSkusAll);
                    //回写主表
                    WorkOrderList::where('id',$workId)->setField('replacement_order', $replacementOrder);

                    $this->deductionStock($workId, $measureId);
                    file_put_contents('./wangwei_suceess.log',$incrementId.PHP_EOL,FILE_APPEND);
                    echo "补发单SUCCESS - ". $replacementOrder. PHP_EOL;
                } catch (Exception $e) {
                    file_put_contents('./wangwei.log',$incrementId.PHP_EOL,FILE_APPEND);
                    echo $e->getMessage().PHP_EOL;
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
     * @param int $work_id 工单ID
     * @param int $order_platform 平台类型
     * @param string $increment_id 订单号
     * @param array $change_row change_sku表数据
     * @param int $type 类型：1增品 2补发
     * @Author lzh
     * @return bool
     */
    public function workPresent($work_id, $order_platform, $increment_id, $change_row, $type)
    {
        if (!$work_id || !$order_platform || !$increment_id || !$change_row) return false;

        $_item = new Item();
        $_platform_sku = new  ItemPlatformSku();
        $_stock_log = new StockLog();

        foreach ($change_row as $v) {
            //获取sku
            $arr = explode('-', trim($v['original_sku']));
            $original_sku = 2 < count($arr) ? $arr[0] . '-' . $arr[1] : trim($v['original_sku']);

            if (!$original_sku) continue;

            //sku数量
            $original_number = $v['original_number'];
            //仓库sku、库存
            $warehouse_original_info = $_platform_sku
                ->field('sku,stock')
                ->where(['platform_sku'=>$original_sku, 'platform_type' => $order_platform])
                ->find()
            ;
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
                    'type'                      => 2,
                    'site'                      => $order_platform,
                    'modular'                   => 1 == $type ? 9 : 8,
                    'change_type'               => 1 == $type ? 15 : 14,
                    'source'                    => 1,
                    'sku'                       => $warehouse_original_sku,
                    'number_type'              => 1,
                    'order_number'              => $increment_id,
                    'public_id'                 => $work_id,
                    'available_stock_before'    => $item_before['available_stock'],
                    'available_stock_change'    => -$original_number,
                    'stock_before'              => 1 == $type ? $item_before['stock'] : 0,
                    'stock_change'              => 1 == $type ? -$original_number : 0,
                    'occupy_stock_before'       => 2 == $type ? $item_before['occupy_stock'] : 0,
                    'occupy_stock_change'       => 2 == $type ? $original_number : 0,
                    'fictitious_before'         => $warehouse_original_info['stock'],
                    'fictitious_change'         => -$original_number,
                    'create_person'             => session('admin.nickname'),
                    'create_time'               => time()
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
        $order = $this->order->where(['site' => $siteType,'increment_id' => $incrementId])->find();
        $orderDetails = $this->orderItem->where('order_id',$order->id)->select();
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
        foreach($orderDetails as $key => $orderDetail) {
            $pdCheck = $orderDetail['pdcheck'] == 'on' ? 'on' : '';
            $prismCheck = $orderDetail['prismcheck'] == 'on' ? 'on' : '';
            $is_frame_only = 0;
            if ($orderDetail['index_id'] || $orderDetail['coating_id'] || $orderDetail['color_id']) {
                $is_frame_only = 1;
            }
            if(!$orderDetail['prescription_type'] || $orderDetail['prescription_type']=='Frame Only') {
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
}