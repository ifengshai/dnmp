<?php

namespace app\admin\controller\operatedatacenter\GoodsData;

use app\common\controller\Backend;
use think\Controller;
use think\Request;

class GoodsDataView extends Backend
{
    /**
     * 商品数据-数据概览
     *
     * @return \think\Response
     */
    public function index()
    {
        $label = input('label', 1);
        $this->assign('label', $label);
        $this->assignconfig('label', $label);
        return $this->view->fetch();
    }
}
