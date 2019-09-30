<?php

namespace app\admin\controller\order;

use app\common\controller\Backend;
use think\Hook;
use fast\Trackingmore;


/**
 * 订单列表
 */
class Index extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->nihao = new \app\admin\model\order\order\Nihao;
        $this->zeelool = new \app\admin\model\order\order\Zeelool;
        $this->voogueme = new \app\admin\model\order\order\Voogueme;
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


    /**
     * 查看
     */
    public function index()
    {
        $label = $this->request->get('label', 1);
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            //根据传的标签切换对应站点数据库
            $label = $this->request->get('label', 1);
            if ($label == 1) {
                $model = $this->zeelool;
            } elseif ($label == 2) {
                $model = $this->voogueme;
            } elseif ($label == 3) {
                $model = $this->nihao;
            }
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
        $this->assign('label', $label);
        $this->assignconfig('label', $label);
        return $this->view->fetch();
    }

    /**
     * 详情
     */
    public function detail($ids = null)
    {
        $ids = $ids ?? $this->request->get('id');
        //根据传的标签切换对应站点数据库
        $label = $this->request->get('label', 1);
        if ($label == 1) {
            $model = $this->zeelool;
        } elseif ($label == 2) {
            $model = $this->voogueme;
        } elseif ($label == 3) {
            $model = $this->nihao;
        }

        //查询订单详情
        $row = $model->where('entity_id', '=', $ids)->find();
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }

        //获取收货信息
        $address = $this->zeelool->getOrderDetail($label, $ids);

        //获取订单商品信息
        $goods = $this->zeelool->getGoodsDetail($label, $ids);

        //获取支付信息
        $pay = $this->zeelool->getPayDetail($label, $ids);

        $this->view->assign("row", $row);
        $this->view->assign("address", $address);
        $this->view->assign("goods", $goods);
        $this->view->assign("pay", $pay);
        return $this->view->fetch();
    }

    /**
     * 订单执行信息
     */
    public function checkDetail($ids = null)
    {
        $ids = $ids ?? $this->request->get('id');
        //根据传的标签切换对应站点数据库
        $label = $this->request->get('label', 1);
        if ($label == 1) {
            $model = $this->zeelool;
        } elseif ($label == 2) {
            $model = $this->voogueme;
        } elseif ($label == 3) {
            $model = $this->nihao;
        }

        //查询订单详情
        $row = $model->where('entity_id', '=', $ids)->find();
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        //查询订单快递单号
        $express = $this->zeelool->getExpressData($label, $ids);

        if ($express) {
            //缓存一个小时
            $express_data = session('order_checkDetail_' . $express['track_number'] . '_' . date('YmdH'));
            if (!$express_data) {
                try {
                    //查询物流信息
                    $title = str_replace(' ', '-', $express['title']);
                    $track = new Trackingmore();
                    $track = $track->getRealtimeTrackingResults($title, $express['track_number']);
                    $express_data = $track['data']['items'][0];
                    session('order_checkDetail_' . $express['track_number'] . '_' . date('YmdH'), $express_data);
                } catch (\Exception $e) {
                    $this->error($e->getMessage());
                }
            }

            $this->view->assign("express_data", $express_data);
        }

        $this->view->assign("row", $row);

        return $this->view->fetch();
    }
}
