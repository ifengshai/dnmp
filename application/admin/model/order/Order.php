<?php

/**
 *  订单
 */
namespace app\admin\model\order;

use app\admin\model\OrderNode;
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
     *$order_id 订单id   $order_number  订单号  $site 站点   $status 状态 $order_node订单大节点 $node_type 订单小节点
     */
    public function rulesto_adjust($order_id=null,$order_number=null,$site=null,$order_node=null,$node_type=null)
    {

            //判断如果子节点大于等于1时  不更新
            $order_count = (new OrderNode())->where([
                'order_number' => $order_number,
                'order_id' => $order_id,
                'site' => $site,
                'node_type' => ['>=', 1]
            ])->count();

            if ($order_count < 0) {
                (new OrderNode())->save([
                    'order_node' => $order_node,
                    'node_type' => $node_type,
                    'update_time' => date('Y-m-d H:i:s'),
                ], ['order_id' => $order_id, 'site' => $site]);
            }

            switch ($node_type){
                //已打印标签
                case 2:
                    $content = 'Order is under processing.';
                    break;
                //配货完成
                case 3:
                    $content = 'Frame(s) is/are ready, waiting for lenses.';
                    break;
                //配镜片完成
                case 4:
                    $content = 'Lenses are matched, waiting for manufacturing.';
                    break;
                //加工完成-需要印logo
                case 5:
                    $content = 'Lenses manufacturing completed, waiting for LOGO customizing.';
                    break;
                //加工完成-不需要印logo
                case 13:
                    $content = 'Lenses manufacturing completed, waiting for quality inspection.';
                    break;
                //印logo完成 质检中
                case 6:
                    $content = 'LOGO customizing completed, waiting for quality inspection.';
                    break;
                //质检完成 已出库
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
          //查看是否有存在记录
            $detail_count = (new OrderNodeDetail())->where('order_number',$order_number)
                ->where('order_id',$order_id)
                ->where('order_node',$order_node)
                ->where('node_type',$node_type)
                ->count();

            //如果没有存在 则添加一条记录
            if ($detail_count < 1 ){
                $OrderNodeDetail = new OrderNodeDetail();
                $OrderNodeDetail->order_number = $order_number;
                $OrderNodeDetail->order_id = $order_id;
                $OrderNodeDetail->content = $content;
                $OrderNodeDetail->site = $site;
                $OrderNodeDetail->handle_user_id = session('admin.id');
                $OrderNodeDetail->create_time = date('Y-m-d H:i:s');
                $OrderNodeDetail->order_node = $order_node;
                $OrderNodeDetail->node_type =$node_type;
                $OrderNodeDetail->save();
            }

    }

}