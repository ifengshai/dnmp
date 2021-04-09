<?php

namespace app\admin\controller\warehouse;

use app\admin\model\warehouse\ProductBarCodeItem;
use app\common\controller\Backend;
use fast\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Loader;

/**
 * 库位管理
 *
 * @icon fa fa-circle-o
 */
class LocationInventory extends Backend
{

    /**
     * StockHouse模型对象
     * @var \app\admin\model\warehouse\StockHouse
     */
    protected $model = null;

    /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    protected $noNeedRight = ['print_label,batch_export_xls'];
    protected $noNeedLogin = ['batch_export_xls'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\warehouse\StockSku;
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 库位库存列表
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/3/5
     * Time: 15:38:45
     */
    public function index()
    {
        //当前是否为关联查询
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $map = [];
            $maps = [];
            $area = new \app\admin\model\warehouse\WarehouseArea();
            $productbarcodeitem = new ProductBarCodeItem();
            //自定义sku搜索
            $filter = json_decode($this->request->get('filter'), true);
            if ($filter['area_code']) {
                $map['coding'] = ['like','%'.$filter['area_code'].'%'];
                $area_id = $area->where($map)->value('id');
                $maps['area_id'] = $area_id;
                unset($filter['area_code']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            $area_list = $area->column('coding','id');

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->with(['storehouse1'])
                ->where($where)
                ->where($maps)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['storehouse1'])
                ->where($where)
                ->where($maps)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            //查询商品SKU
            $item = new \app\admin\model\itemmanage\Item();
            $arr = $item->where('is_del', 1)->column('name,is_open', 'sku');

            foreach ($list as $k => $row) {
                $row->getRelation('storehouse1')->visible(['coding', 'library_name', 'status','area_id']);
                $list[$k]['name'] = $arr[$row['sku']]['name'];
                //在库 子单号为空 库位号 库区id都一致的库存作为此库位的库存
                $list[$k]['stock'] = $productbarcodeitem
                    ->where(['location_id'=>$row['storehouse']['area_id'],'location_code'=>$row['storehouse']['coding'],'library_status'=>1,'item_order_number'=>'','sku'=>$row['sku']])
                    ->count();
                $list[$k]['area_code'] = $area_list[$row['storehouse']['area_id']];
            }
            $list = collection($list)->toArray();

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }

        return $this->view->fetch();
    }


    /**
     * 导出功能,临时功能,跑完脚本删除
     * Interface batch_export_xls
     * @package app\admin\controller\warehouse
     * @author  fzg
     * @date    2021/4/9 16:34
     */
    public function batch_export_xls()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        //根据传的标签切换对应站点数据库
        $maps['area_id'] = 1;
        $list = $this->model
            ->alias('fss')
            ->join(['fa_store_house' => 'fsh'], 'fss.store_id=fsh.id')
            ->field("fss.sku,fsh.area_id,fsh.coding")
            ->limit(1)
            ->select();
        $list = collection($list)->toArray();

        $productbarcodeitem = new ProductBarCodeItem();
        //查询商品SKU
        $item = new \app\admin\model\itemmanage\Item();
        $arr = $item->where('is_del', 1)->column('name,is_open,distribution_occupy_stock,stock', 'sku');
        foreach ($list as $k => $row) {
            $list[$k]['name'] = $arr[$row['sku']]['name'];
            //在库 子单号为空 库位号 库区id都一致的库存作为此库位的库存
            $list[$k]['stock'] = $productbarcodeitem
                ->where(['location_id'=>$row['area_id'],'location_code'=>$row['coding'],'library_status'=>1,'item_order_number'=>'','sku'=>$row['sku']])
                ->count();
            $list[$k]['stock_number'] = $arr[$row['sku']]['stock']-$arr[$row['sku']]['distribution_occupy_stock'];
        }
        $header = ['sku', 'area_id', 'coding', 'name', 'stock','stock_number'];
        $filename = '库存库区数量导出';
        Excel::writeCsv($list, $header, $filename);
    }

}
