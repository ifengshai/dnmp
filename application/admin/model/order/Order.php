<?php

/**
 *  订单
 */
namespace app\admin\model\order;

use app\admin\model\OrderNodeDetail;
use think\Model;
use think\Db;


class Order extends Model
{
    //数据库
    protected $connection = 'database.db_mojing_order';

    // 表名
    protected $table = 'fa_order';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];


    /**
     * @throws \think\Exception
     * 网站物流接口调整
     *$order_id 订单id   $order_number  订单号  $site 站点   $status 状态 $node_type 订单大节点
     */
    public function rulesto_adjust($order_id,$order_number,$site,$status,$node_type)
    {
        if ($status == 'processing'){
            //判断如果子节点大于等于1时  不更新
            $order_count = (new OrderNode)->where([
                'order_number' => $order_number,
                'order_id' => $order_id,
                'site' => $site,
                'node_type' => ['>=', 1]
            ])->count();
            if ($order_count < 0) {
                $res_node = (new OrderNode())->save([
                    'order_node' => 0,
                    'node_type' => 1,
                    'update_time' => date('Y-m-d H:i:s'),
                ], ['order_id' => $order_id, 'site' => $site]);
            }

            switch ($status){
                //已打印标签
                case 1:
                    $content = 'Order is under processing.';
                    break;
                //配货完成
                case 2:
                    $content = 'Order is under processing.';
                    break;
                //配镜片完成
                case 3:
                    $content = 'Lenses are matched, waiting for manufacturing.';
                    break;
                //加工完成-需要印logo
                case 4:
                    $content = 'Lenses manufacturing completed, waiting for LOGO customizing.';
                    break;
                //加工完成-不需要印logo
                case 5:
                    $content = 'Lenses manufacturing completed, waiting for quality inspection.';
                    break;
                //印logo完成
                case 6:
                    $content = 'LOGO customizing completed, waiting for quality inspection.';
                    break;
                //质检完成
                case 7:
                    $content = 'Quality Inspection completed, waiting for packaging.';
                    break;
                //合单完成
                case 8:
                    $content = 'Packaging completed, waiting for order review.';
                    break;
                //审单完成
                case 9:
                    $content = 'Order reviewing completed, waiting for dispatch.';
                    break;
                //已出库
                case 10:
                    $content = 'Order leave warehouse, waiting for being picked up.';
                    break;
            }

//            $res_node_detail = (new OrderNodeDetail())->allowField(true)->save([
            $res_node_detail = (new OrderNodeDetail())->allowField(true)->save([
                'order_number' => $order_number,
                'order_id' => $order_id,
                'content' => $content,
                'site' => $site,
                'create_time' => date('Y-m-d H:i:s'),
                'order_node' => 0,
                'node_type' => $node_type
            ]);
            if (false !== $res_node && false !== $res_node_detail) {
                $this->success('创建成功', [], 200);
            } else {
                $this->error('创建失败');
            }
        }
        $this->success('创建成功', [], 200);

    }

}