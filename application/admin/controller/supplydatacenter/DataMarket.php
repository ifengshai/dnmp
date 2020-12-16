<?php

namespace app\admin\controller\supplydatacenter;

use app\admin\model\OrderStatistics;
use app\common\controller\Backend;
use think\Cache;
use think\Controller;
use think\Db;
use think\Request;

class DataMarket extends Backend
{
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\itemmanage\Item;
        $this->itemAttribute = new \app\admin\model\itemmanage\attribute\ItemAttribute;
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
        $this->category = new \app\admin\model\itemmanage\ItemCategory;
    }
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $params = $this->request->param();
        if(!$params['time_str']){
            $start = date('Y-m-d', strtotime('-6 day'));
            $end   = date('Y-m-d 23:59:59');
            $time_str = $start . ' ' . '00:00:00' . ' - ' . $end;
        }else{
            $time_str = $params['time_str'];
        }
        $stock_overview = $this->stock_overview($time_str);
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        $this->view->assign(compact('stock_overview','magentoplatformarr'));
        return $this->view->fetch();
    }
    //库存总览
    public function stock_overview($time_str){
        $where['is_open'] = 1;
        $where['is_del'] = 1;
        $createat = explode(' ', $time_str);
        $where['create_time'] = ['between', [$createat[0], $createat[3]]];
        //库存总数量
        $arr['stock_num'] = $this->model->where($where)->sum('stock');
        //库存总金额
        $arr['stock_amount'] = $this->model->where($where)->sum('stock*purchase_price');
        //库存单价
        $arr['stock_price'] = $arr['stock_num'] ? round($arr['stock_amount']/$arr['stock_num'],2) : 0;
        //在途库存数量
        $arr['onway_stock_num'] = $this->model->where($where)->sum('on_way_stock');
        //在途库存总金额
        $arr['onway_stock_amount'] = $this->model->where($where)->sum('on_way_stock*purchase_price');
        //在途库存单价
        $arr['onway_stock_price'] = $arr['onway_stock_num'] ? round($arr['onway_stock_amount']/$arr['onway_stock_num'],2) : 0;
        //待入库数量
        $arr['wait_stock_num'] = $this->model->where($where)->sum('wait_instock_num');
        //待入库金额
        $arr['wait_stock_amount'] = $this->model->where($where)->sum('wait_instock_num*purchase_price');
        return $arr;
    }
}
