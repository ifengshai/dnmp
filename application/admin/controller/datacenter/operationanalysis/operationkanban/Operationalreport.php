<?php
namespace app\admin\controller\datacenter\operationanalysis\operationkanban;
use think\Db;
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
        $orderPlatform = (new MagentoPlatform())->getOrderPlatformList();
        $create_time = input('create_time');
        $platform    = input('order_platform', 1);
        if($this->request->isAjax()){
            $params = $this->request->param();
            //默认当天
            if ($params['time']) {
                $time = explode(' ', $params['time']);
                $map['created_at'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
            } else {
                $map['created_at'] = ['between', [date('Y-m-d 00:00:00', strtotime('-7 day')), date('Y-m-d H:i:s', time())]];
            }
            $order_platform = $params['platform'];
            if(4<=$order_platform){
                return $this->error('该平台暂时没有数据');
            }
            $result = $this->platformOrderInfo($order_platform,$map);
            if(!$result){
                return $this->error('暂无数据');
            }
            return json(['code' => 1, 'rows' => $result]);

        }	
        $this->view->assign(
            [
                'orderPlatformList'	=> $orderPlatform,
                'create_time'       => $create_time,
                'platform'          => $platform,
            ]
        );
        return  $this->view->fetch();
    }
    /**
     * 获取订单信息 运费数据统计、未成功订单状态统计、币种数据统计、订单类型数据统计
     *
     * @Description created by lsw
     * @author lsw
     * @since 2020/03/16 17:16:16 
     * @param [type] $platform
     * @param [type] $map
     * @return void
     */
    public function platformOrderInfo($platform,$map)
    {

        switch($platform){
            case 1:
            $model = Db::connect('database.db_zeelool');
            break;
            case 2:
            $model = Db::connect('database.db_voogueme');
            break;
            case 3:
            $model = Db::connect('database.db_nihao');
            break;
            default:
            $model = false;
            break;            
        }
        if(false == $model){
            return false;
        }
        //订单类型数据统计
        
    }
}