<?php

namespace app\admin\controller\datacenter;

use app\common\controller\Backend;

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
        $this->itemplatformsku = new \app\admin\model\itemmanage\ItemPlatformSku;
        $this->item = new \app\admin\model\itemmanage\Item;
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
                ->order($sort, $order)
                ->count();

            $list = $this->item
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            foreach ($list as &$v) {
                //sku转换
                $v['z_sku'] = $this->itemplatformsku->getWebSku($v['sku'], 1);

                $v['v_sku'] = $this->itemplatformsku->getWebSku($v['sku'], 2);

                $v['n_sku'] = $this->itemplatformsku->getWebSku($v['sku'], 3);
            }
            unset($v);

            $z_sku = array_column($list, 'z_sku');
            $v_sku = array_column($list, 'v_sku');
            $n_sku = array_column($list, 'n_sku');

            //获取三个站销量数据
            $zeelool = $this->zeelool->getOrderSalesNum($z_sku, $map);
            $voogueme = $this->voogueme->getOrderSalesNum($v_sku, $map);
            $nihao = $this->nihao->getOrderSalesNum($n_sku, $map);
            //重组数组
            foreach ($list as &$v) {

                $v['z_num'] = round($zeelool[$v['z_sku']]) ?? 0;

                $v['v_num'] = round($voogueme[$v['v_sku']]) ?? 0;

                $v['n_num'] = round($nihao[$v['n_sku']]) ?? 0;

                $v['all_num'] = $v['z_num'] + $v['v_num'] + $v['n_num'];
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
        //仓库总库存
        $allStock = $this->item->getAllStock();
        return $this->view->fetch();
    }

    /**
     * 仓库数据
     *
     * @Description
     * @author wpl
     * @since 2020/02/25 13:52:27 
     * @return void
     */
    public function warehouse_data()
    {
        return $this->view->fetch();
    }
}
