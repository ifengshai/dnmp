<?php

namespace app\admin\controller\financial;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use app\admin\model\order\order\ProcessOrder;

/**
 * 财务管理
 *
 * @icon fa fa-circle-o
 */
class Index extends Backend
{

    /**
     * Contract模型对象
     * @var \app\admin\model\purchase\Contract
     */
    protected $model = null;

    protected $relationSearch = true;

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
     * 订单成本核算
     */
    public function order()
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
            $result = array("total" => $total, "rows" => $list, "extend" => ['money' => mt_rand(100000, 999999)]);

            return json($result);
        }
        $this->assign('label', $label);
        $this->assignconfig('label', $label);
        return $this->view->fetch();
    }

    /**
     * 采购单成本核算
     */
    public function purchaseOrder()
    {
        return $this->view->fetch();
    }

    /**
     * 补发单成本核算
     */
    public function reissueOrder()
    {
        return $this->view->fetch();
    }

    /**
     * 出库单成本核算
     */
    public function outStockOrder()
    {
        return $this->view->fetch();
    }

    /**
     * 入库单成本核算
     */
    public function inStockOrder()
    {
        return $this->view->fetch();
    }

    /**
     * 预处理订单
     */
    public function processOrder()
    {
        set_time_limit(0);
        $data['connection'] = 'database.db_zeelool';
        $this->processOrder = new ProcessOrder($data);
        //查询预处理表已存在的所有订单id
        $ids = $this->processOrder->column('entity_id');
        //查询订单表数据 转存预处理表
        $list = $this->zeelool->alias('a')->field('a.*,b.region,b.postcode,b.lastname,b.street,b.city,b.email,b.telephone,b.country_id,b.firstname,c.track_number')
            ->where('b.address_type', '=', 'shipping')
            ->where('a.entity_id', 'not in', $ids)
            ->join('sales_flat_order_address b', 'a.entity_id = b.parent_id')
            ->join('sales_flat_shipment_track c', 'a.entity_id = c.parent_id')
            ->limit(100)
            ->group('entity_id')
            ->select();

        $list = collection($list)->toArray();

        foreach ($list as $k => $v) {
            //如果已存在则为修改
            if (in_array($v['entity_id'], $ids)) {
                $this->processOrder->isUpdate(true)->allowField(true)->save($v, ['entity_id' => $v['entity_id']]);
            } else {
                $this->processOrder->isUpdate(false)->data($v, true)->allowField(true)->save($v);
            }
        }
        echo 'ok';
        return $this->view->fetch();
    }

    /**
     * 预处理订单项数据
     */
    public function testItem()
    {
        
    }
}
