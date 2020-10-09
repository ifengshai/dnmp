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
        // $user_id = session('admin.id');
        // $resultPrivilege = (new AuthGroupAccess)->getConversionratePrivilege($user_id);
        // if(1>count($resultPrivilege)){
        //     $this->error('您没有权限访问','general/profile?ref=addtabs');
        // }
        $orderPlatform = (new MagentoPlatform())->getNewAuthSite();
        if(empty($orderPlatform)){
            $this->error('您没有权限访问','general/profile?ref=addtabs');
        }
        $create_time = input('create_time');
        $platform    = input('order_platform', current($orderPlatform));	
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
            if(100<=$order_platform){
                //return json(['code' => 0, 'data' =>'该平台暂时没有数据']);
                return $this->error('该平台暂时没有数据');
            }
            $orderStatistics = new OrderStatistics();
            $list = $orderStatistics->getDataBySite($order_platform,$map);
            if(!empty($list)){
                $list = collection($list)->toArray();
                $create_date = $shoppingCartUpdateTotal = $shoppingCartUpdateConversion = [];
                $total_sales_money =  $total_shoppingcart_update_total = $total_sales_num = 0;
                foreach ($list as $v) {
                    $shoppingCartUpdateTotal[]        = $v['shoppingcart_update_total'];
                    $shoppingCartUpdateConversion[]   = $v['shoppingcart_update_conversion'];
                    $create_date[]                    = $v['create_date'];
                    $total_sales_money += $v['sales_money'];
                    $total_shoppingcart_update_total += $v['shoppingcart_update_total'];
                    $total_sales_num   += $v['sales_num'];
                }
                $key = count($list);
                $list[$key]['create_date'] = '总计';
                //总销售额
                $list[$key]['sales_money'] = round($total_sales_money,2);
                //平均客单价
                if(0< $total_sales_num){
                    $list[$key]['unit_price'] = round($total_sales_money/$total_sales_num,2); 
                }else{
                    $list[$key]['unit_price'] = 0;
                }
                $list[$key]['shoppingcart_update_total'] = $total_shoppingcart_update_total;
                $list[$key]['sales_num'] = $total_sales_num;
                if(0< $total_shoppingcart_update_total){
                    $list[$key]['shoppingcart_update_conversion'] = round($total_sales_num/$total_shoppingcart_update_total*100,2);
                }else{
                    $list[$key]['shoppingcart_update_conversion'] = 0;
                }
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
    /**
     * zeelool站点的权限
     *
     * @Description
     * @author lsw
     * @since 2020/06/03 15:37:41 
     * @return void
     */
    public function zeelool_privilege()
    {

    }
    /**
     * voogueme站点的权限
     *
     * @Description
     * @author lsw
     * @since 2020/06/03 15:38:12 
     * @return void
     */
    public function voogueme_privilege()
    {

    }
    /**
     * nihao站点权限
     *
     * @Description
     * @author lsw
     * @since 2020/06/03 15:39:07 
     * @return void
     */
    public function nihao_privilege()
    {

    }
    /**
     * meeloog站点权限
     *
     * @Description
     * @author lsw
     * @since 2020/06/03 16:14:30 
     * @return void
     */
    public function meeloog_privilege()
    {

    }
}