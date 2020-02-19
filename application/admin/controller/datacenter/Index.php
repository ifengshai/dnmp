<?php

namespace app\admin\controller\datacenter;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * 镜片管理管理
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
                $map['a.created_at'] = ['between', [$createat[0], $createat[2]]];
                unset($filter['created_at']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            // $zeeloolRes = $this->zeelool->getOrderSalesNum($zmap, $where);
            // $vooguemeRes = $this->voogueme->getOrderSalesNum($vmap, $where);
            // $nihaoRes = $this->nihao->getOrderSalesNum($nmap, $where);

            // dump($zeeloolRes);
            // dump($vooguemeRes);
            // dump($nihaoRes);die;

            $total = $this->item
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->item
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            foreach ($list as $k => $v) {
                //sku转换
                $z_sku = $this->itemplatformsku->getWebSku($v['sku'], 1);
                $zmap['sku'] = ['like', '%' . $z_sku . '%'];

                $v_sku = $this->itemplatformsku->getWebSku($v['sku'], 2);
                $vmap['sku'] = ['like', '%' . $v_sku . '%'];

                $n_sku = $this->itemplatformsku->getWebSku($v['sku'], 3);
                $nmap['sku'] = ['like', '%' . $n_sku . '%'];
                
                $list['z_num'] = $this->zeelool->getOrderSalesNum($zmap, $map);
                $list['v_num'] = $this->voogueme->getOrderSalesNum($vmap, $map);
                $list['n_num'] = $this->nihao->getOrderSalesNum($nmap, $map);
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
}
