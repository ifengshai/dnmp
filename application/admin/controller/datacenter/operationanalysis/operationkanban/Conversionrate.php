<?php
namespace app\admin\controller\datacenter\operationanalysis\operationkanban;
use app\common\controller\Backend;
use app\admin\model\OrderStatistics;
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
        $orderPlatform = (new MagentoPlatform())->getOrderPlatformList();
        $create_time = input('create_time');
        $platform    = input('order_platform', 1);	
        //头部数据
        if($this->request->isAjax()){
            $params = $this->request->param();
            //默认当天
            if ($params['time']) {
                $time = explode(' ', $params['time']);
                $map['create_date'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
            } else {
                $map['create_date'] = ['between', [date('Y-m-d 00:00:00', strtotime('-7 day')), date('Y-m-d H:i:s', time())]];
            }
            $order_platform = $params['platform'];
            if(4<=$order_platform){
                //return json(['code' => 0, 'data' =>'该平台暂时没有数据']);
                return $this->error('该平台暂时没有数据');
            }
            $orderStatistics = new OrderStatistics();
            $list = $orderStatistics->getDataBySite($order_platform,$map);
            $create_date = $shoppingCartUpdateTotal = $shoppingCartUpdateConversion = [];
            foreach ($list as $v) {
                $shoppingCartUpdateTotal[]        = $v['shoppingcart_update_total'];
                $shoppingCartUpdateConversion[]   = $v['shoppingcart_update_conversion'];
                $create_date[]                    = $v['create_date'];  
            }
            $json['xcolumnData'] = $create_date ? $create_date :[];
            $json['columnData'] = [
                [
                    'type' => 'line',
                    'data' => $shoppingCartUpdateTotal ? $shoppingCartUpdateTotal:[],
                    'name' => '购物车数量'
                ],
                [
                    'type' => 'line',
                    'data' => $shoppingCartUpdateConversion ? $shoppingCartUpdateConversion:[],
					'yAxisIndex'=>1,
                    'name' => '购物车转化率'                    
                ]

            ];
            /***********END*************/
            //列表           
            return json(['code' => 1, 'data' => $json,'rows' => $list]);
        }
        $this->view->assign(
            [

                'orderPlatformList'	=> $orderPlatform,
                'platform'          => $platform,
                'create_time'       => $create_time
            ]
        );
        $this->assignconfig('platform', $platform);
        $this->assignconfig('create_time',$create_time);
        return  $this->view->fetch();
    }
}