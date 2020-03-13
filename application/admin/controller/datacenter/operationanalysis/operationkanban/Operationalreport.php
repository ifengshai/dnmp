<?php
namespace app\admin\controller\datacenter\operationanalysis\operationkanban;
use app\common\controller\Backend;
use app\admin\model\platformmanage\MagentoPlatform;
class Operationalreport extends Backend{
    /**
     * 运营报告首页数据
     *
     * @Description created by lsw
     * @author lsw
     * @since 2020/03/12 17:51:03 
     * @return void
     */
    public function index ()
    {
        $platform = (new MagentoPlatform())->getOrderPlatformList();
        $this->view->assign(
            [
                'orderPlatformList'	=> $platform
            ]
        );
        return  $this->view->fetch();
    }
}