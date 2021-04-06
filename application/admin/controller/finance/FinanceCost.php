<?php

namespace app\admin\controller\finance;

use app\common\controller\Backend;
use fast\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class FinanceCost extends Backend
{

    /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    protected $noNeedRight = ['income', 'cost', 'batch_export_xls'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\finance\FinanceCost();
    }

    /*
     * 成本核算
     * */
    public function index()
    {
        return $this->view->fetch();
    }

    /**
     * 收入
     *
     * @Description
     * @author gyh
     * @since 2021/01/21 15:24:14 
     * @return void
     */
    public function income()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where('type=1')
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where('type=1')
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $result = ["total" => $total, "rows" => $list];

            return json($result);
        }

        return $this->view->fetch('index');
    }


    /**
     * 成本
     *
     * @Description
     * @author gyh
     * @since 2021/01/21 15:24:14 
     * @return void
     */
    public function cost()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where('type=2')
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where('type=2')
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $result = ["total" => $total, "rows" => $list];

            return json($result);
        }

        return $this->view->fetch('index');
    }

    //导出数据
    public function batch_export_xls()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        //设置过滤方法

        $ids = input('ids');

        $type = json_decode($this->request->get('type'), true);

        !empty($type) && $where['type'] = $type;

        if (!empty($ids)) {

            $where['id'] = ['in', $ids];

        } else {

            $filter = json_decode($this->request->get('filter'), true);

            !empty($filter['bill_type']) && $where['bill_type'] = ['in', $filter['bill_type']];

            !empty($filter['order_number']) && $where['order_number'] = ['like', '%'.$filter['order_number'].'%'];

            !empty($filter['site']) && $where['site'] = ['in', $filter['site']];

            !empty($filter['order_type']) && $where['order_type'] = $filter['order_type'];

            !empty($filter['order_currency_code']) && $where['order_currency_code'] = $filter['order_currency_code'];

            !empty($filter['action_type']) && $where['action_type'] = $filter['action_type'];

            !empty($filter['is_carry_forward']) && $where['is_carry_forward'] = $filter['is_carry_forward'];

            if ($filter['createtime']) {
                $createtime = explode(' - ', $filter['createtime']);
                $where['createtime'] = ['between', [strtotime($createtime[0]), strtotime($createtime[1])]];
            }

        }

        //站点列表
        $siteList = [
            1 => 'Zeelool',

            2 => 'Voogueme',

            3 => 'Nihao',

            4 => 'Meeloog',

            5 => 'Wesee',

            8 => 'Amazon',

            9 => 'Zeelool_es',

            10 => 'Zeelool_de',

            11 => 'Zeelool_jp',
        ];

        //节点类型
        $typeDocument = [

            1 => '订单收入',

            2 => 'Vip订单',

            3 => '工单补差价',

            4 => '工单退货退款',

            5 => '工单取消',

            6 => '工单部分退款',

            7 => 'Vip退款',

            8 => '订单出库',

            9 => '出库单出库',

            10 => '冲减暂估',
        ];

        //订单类型
        $orderType = [

            1 => '普通订单',

            2 => '批发',

            3 => '网红单',

            4 => '补发',

            5 => '补差价',

            9 => 'vip订单',
        ];

        $path = '/uploads/financeCost/';
        if ($type == 1) {
            $headList = ['ID', '关联单据类型', '订单号', '站点', '订单类型', '订单金额', '收入金额', '币种', '是否结转', '增加/冲减', '订单支付时间', '支付方式', '创建时间'];
            $saveName = '订单成本明细-收入'.date("YmdHis", time());
        } else {
            $headList = ['ID', '关联单据类型', '关联单号', '镜架成本', '镜片成本', '是否结转', '创建时间', '币种', '站点'];
            $saveName = '订单成本明细-成本'.date("YmdHis", time());
        }


        $i = 0;
        $this->model
            ->where($where)
            ->chunk(1000, function ($data) use ($siteList, $typeDocument, $orderType, $type, $headList, $saveName, $path, &$i) {
                $params = [];
                foreach ($data as $k => &$value) {
                    if ($value['action_type'] == 1) {
                        $value['action_type'] = '增加';
                    } else {
                        $value['action_type'] = '减少';
                    }

                    if ($value['payment_time']) {
                        $value['payment_time'] = date('Y-m-d H:i:s', $value['payment_time']);
                    } else {
                        $value['payment_time'] = '无';
                    }

                    if ($value['createtime']) {
                        $value['createtime'] = date('Y-m-d H:i:s', $value['createtime']);
                    }

                    if ($value['is_carry_forward'] == 1) {
                        $value['is_carry_forward'] = '是';
                    } else {
                        $value['is_carry_forward'] = '否';
                    }

                    if ($type == 1) {
                        $params[$k]['id'] = $value['id'];
                        $params[$k]['bill_type'] = $typeDocument[$value['bill_type']];
                        $params[$k]['order_number'] = $value['order_number'];
                        $params[$k]['site'] = $siteList[$value['site']];
                        $params[$k]['order_type'] = $orderType[$value['order_type']];
                        $params[$k]['order_money'] = $value['order_money'];
                        $params[$k]['income_amount'] = $value['income_amount'];
                        $params[$k]['order_currency_code'] = $value['order_currency_code'];
                        $params[$k]['is_carry_forward'] = $value['is_carry_forward'];
                        $params[$k]['action_type'] = $value['action_type'];
                        $params[$k]['payment_time'] = $value['payment_time'];
                        $params[$k]['payment_method'] = $value['payment_method'];
                        $params[$k]['createtime'] = $value['createtime'];
                    } else {
                        $params[$k]['id'] = $value['id'];
                        $params[$k]['bill_type'] = $typeDocument[$value['bill_type']];
                        $params[$k]['order_number'] = $value['order_number'];
                        $params[$k]['frame_cost'] = $value['frame_cost'];
                        $params[$k]['lens_cost'] = $value['lens_cost'];
                        $params[$k]['is_carry_forward'] = $value['is_carry_forward'];
                        $params[$k]['createtime'] = $value['createtime'];
                        $params[$k]['order_currency_code'] = $value['order_currency_code'];
                        $params[$k]['site'] = $siteList[$value['site']];
                    }
                }
                if ($i > 0) {
                    $headList = [];
                }
                $i++;
                Excel::writeCsv($params, $headList, $path.$saveName, false);
            });
        unset($i);
        header('Location: http://mj.com/'.$path . $saveName.'.csv');
        die;
    }

}
