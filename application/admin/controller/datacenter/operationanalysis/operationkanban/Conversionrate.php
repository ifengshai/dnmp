<?php
namespace app\admin\controller\datacenter\operationanalysis\operationkanban;
use app\common\controller\Backend;
use app\admin\model\platformmanage\MagentoPlatform;
class Conversionrate extends Backend{
    /**
     * 转化率首页
     *
     * @Description created by lsw
     * @author lsw
     * @since 2020/03/13 16:53:17 
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