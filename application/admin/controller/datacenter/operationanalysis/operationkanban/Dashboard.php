<?php

namespace app\admin\controller\datacenter\operationanalysis\operationkanban;

use app\admin\model\OrderStatistics;
use app\common\controller\Backend;
use think\Db;
use think\Exception;
use app\admin\model\platformmanage\MagentoPlatform;
use think\Cache;
use app\admin\model\AuthGroupAccess;
class Dashboard extends Backend
{
    protected $model = null;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\OperationAnalysis;
    }
    /**
     *定义时间日志
     */
    public function date()
    {
        $date = [
            1 => '过去30天',
            2 => '过去14天',
            3 => '过去7天',
            4 => '昨天',
            5 => '今天'
        ];
        return $date;
    }
    /**
     * 仪表盘首页(原先)
     */
    public function index_yuan()
    {
        //上边部分数据 默认zeelool站数据
        $platform = (new MagentoPlatform())->getOrderPlatformList();
        $zeelool_data = $this->model->getList(1);
        //z站今天的销售额($) 订单数	订单支付成功数	客单价($)	购物车总数	购物车总转化率(%)	新增购物车数	新增购物车转化率	新增注册用户数
        //z站的历史数据  昨天、过去7天、过去30天、当月、上月、今年、总计
        $zeelool_data = collection($zeelool_data)->toArray();
        //中间部分数据
        $orderStatistics = new OrderStatistics();
        $list = $orderStatistics->getAllData();
        $zeeloolSalesNumList = $vooguemeSalesNumList = $nihaoSalesNumList = [];
        foreach ($list as $v) {
            $zeeloolSalesNumList[$v['create_date']]  			 = $v['zeelool_sales_num'];
            $vooguemeSalesNumList[$v['create_date']] 			 = $v['voogueme_sales_num'];
			$nihaoSalesNumList[$v['create_date']]    			 = $v['nihao_sales_num'];
			$meeloogSalesNumList[$v['create_date']]    			 = $v['meeloog_sales_num'];
            $zeeloolSalesMoneyList[$v['create_date']] 			 = $v['zeelool_sales_money'];
            $vooguemeSalesMoneyList[$v['create_date']]			 = $v['voogueme_sales_money'];
			$nihaoSalesMoneyList[$v['create_date']]				 = $v['nihao_sales_money'];
			$meeloogSalesMoneyList[$v['create_date']]			 = $v['meeloog_sales_money'];
            $zeeloolUnitPriceList[$v['create_date']]			 = $v['zeelool_unit_price'];
            $vooguemeUnitPriceList[$v['create_date']]			 = $v['voogueme_unit_price'];
			$nihaoUnitPriceList[$v['create_date']]				 = $v['nihao_unit_price'];
			$meeloogUnitPriceList[$v['create_date']]			 = $v['meeloog_unit_price'];
            $zeeloolShoppingcartTotal[$v['create_date']]		 = $v['zeelool_shoppingcart_total'];
            $vooguemeShoppingcartTotal[$v['create_date']]		 = $v['voogueme_shoppingcart_total'];
			$nihaoShoppingcartTotal[$v['create_date']]			 = $v['nihao_shoppingcart_total'];
			$meeloogShoppingcartTotal[$v['create_date']]		 = $v['meeloog_shoppingcart_total'];
            $zeeloolShoppingcartConversion[$v['create_date']]	 = $v['zeelool_shoppingcart_conversion'];
            $vooguemeShoppingcartConversion[$v['create_date']]   = $v['voogueme_shoppingcart_conversion'];
            $nihaoShoppingcartConversion[$v['create_date']]	     = $v['nihao_shoppingcart_conversion'];
			$meeloogShoppingcartConversion[$v['create_date']]	 = $v['meeloog_shoppingcart_conversion'];
			$zeeloolRegisterCustomer[$v['create_date']]		     = $v['zeelool_register_customer'];
            $vooguemeRegisterCustomer[$v['create_date']]		 = $v['voogueme_register_customer'];
			$nihaoRegisterCustomer[$v['create_date']]			 = $v['nihao_register_customer'];
			$meeloogRegisterCustomer[$v['create_date']]			 = $v['meeloog_register_customer'];
        }
        //下边部分数据 默认30天数据
        $bottom_data = $this->get_platform_data(1);
        $this->view->assign([
            'orderPlatformList'					=> $platform,
            'zeelool_data'						=> $zeelool_data,
            'date'								=> $this->date(),
            'zeeloolSalesNumList'       		=> $zeeloolSalesNumList ?:[], //折线图数据
            'vooguemeSalesNumList'      		=> $vooguemeSalesNumList ?:[],
			'nihaoSalesNumList'         		=> $nihaoSalesNumList ?:[],
			'meeloogSalesNumList'         		=> $meeloogSalesNumList ?:[],
            'zeeloolSalesMoneyList'				=> $zeeloolSalesMoneyList ?:[],
            'vooguemeSalesMoneyList'			=> $vooguemeSalesMoneyList ?:[],
			'nihaoSalesMoneyList'				=> $nihaoSalesMoneyList ?:[],
			'meeloogSalesMoneyList'				=> $meeloogSalesMoneyList ?:[],
            'zeeloolUnitPriceList'				=> $zeeloolUnitPriceList ?:[],
            'vooguemeUnitPriceList'				=> $vooguemeUnitPriceList ?:[],
			'nihaoUnitPriceList'				=> $nihaoUnitPriceList ?:[],
			'meeloogUnitPriceList'				=> $meeloogUnitPriceList ?:[],
            'zeeloolShoppingcartTotal'			=> $zeeloolShoppingcartTotal ?:[],
            'vooguemeShoppingcartTotal' 		=> $vooguemeShoppingcartTotal ?:[],
			'nihaoShoppingcartTotal'			=> $nihaoShoppingcartTotal ?:[],
			'meeloogShoppingcartTotal'			=> $meeloogShoppingcartTotal ?:[],
            'zeeloolShoppingcartConversion'	 	=> $zeeloolShoppingcartConversion ?:[],
            'vooguemeShoppingcartConversion'	=> $vooguemeShoppingcartConversion ?:[],
			'nihaoShoppingcartConversion'	 	=> $nihaoShoppingcartConversion ?:[],
			'meeloogShoppingcartConversion'	 	=> $meeloogShoppingcartConversion ?:[],
            'zeeloolRegisterCustomer'			=> $zeeloolRegisterCustomer ?:[],
            'vooguemeRegisterCustomer'			=> $vooguemeRegisterCustomer ?:[],
			'nihaoRegisterCustomer'				=> $nihaoRegisterCustomer ?:[],
			'meeloogRegisterCustomer'			=> $meeloogRegisterCustomer ?:[],
            'bottom_data'						=> $bottom_data
        ]);
        // $this->view->assign("orderPlatformList", $platform);
        // $this->view->assign("zeelool_data",$zeelool_data);
        // $this->view->assign("date",$this->date());
        return $this->view->fetch();
    }

    public function index()
    {
        $user_id = session('admin.id');
        $result = (new AuthGroupAccess)->getUserPrivilege($user_id);
        if(0 == $result){
            $this->error('您没有权限访问','general/profile?ref=addtabs');
        }
            switch($result){
                //只有zeelool权限
                case 1:
                    $arr = [1];
                break;
                //只有voogueme权限
                case 2:
                    $arr = [2];
                break;
                //只有nihao权限
                case 3:
                    $arr = [3];
                break;
                //只有meeloog权限
                case 4:
                    $arr = [4];
                break;
                //只有zeelool和voogueme权限    
                case 5:
                    $arr = [1,2];
                break;
                //只有zeelool和nihao权限
                case 6:
                    $arr = [1,3];
                break;
                //只有zeelool和meeloog权限
                case 7:
                    $arr = [1,4];
                break;
                //只有voogueme和nihao权限
                case 8:
                    $arr = [2,3];
                break;
                //只有voogueme和meeloog权限
                case 9:
                    $arr = [2,4];
                break;
                //只有nihao和meeloog权限
                case 10:
                    $arr = [3,4];
                break;
                //只有zeelool、voogueme、nihao的权限
                case 11:
                    $arr = [1,2,3];
                break;
                //只有zeelool、voogueme、meeloog权限
                case 12:
                    $arr = [1,2,4];
                break;
                //只有zeelool、nihao、meeloog权限
                case 13:
                    $arr = [1,3,4];
                break;
                //只有voogueme、nihao、meeloog权限
                case 14:
                    $arr = [2,3,4];
                break;    
                //所有
                case 15:
                    $arr = [1,2,3,4];
                break;    
            }            
        //上边部分数据 默认zeelool站数据
        $platform = (new MagentoPlatform())->getNewOrderPlatformList($arr);
        $zeelool_data = $this->model->getList($arr[0]);
        //z站今天的销售额($) 订单数	订单支付成功数	客单价($)	购物车总数	购物车总转化率(%)	新增购物车数	新增购物车转化率	新增注册用户数
        //z站的历史数据  昨天、过去7天、过去30天、当月、上月、今年、总计
        $zeelool_data = collection($zeelool_data)->toArray();
        //中间部分数据
        $orderStatistics = new OrderStatistics();
        $list = $orderStatistics->getAllData();
        $zeeloolSalesNumList = $vooguemeSalesNumList = $nihaoSalesNumList = $meeloogSalesNumList = [];
        foreach ($list as $v) {
            //如果有zeelool权限
            if(in_array(1,$arr)){
                $zeeloolSalesNumList[$v['create_date']]  			 = $v['zeelool_sales_num'];
                $zeeloolSalesMoneyList[$v['create_date']] 			 = $v['zeelool_sales_money'];
                $zeeloolUnitPriceList[$v['create_date']]			 = $v['zeelool_unit_price'];
                $zeeloolShoppingcartTotal[$v['create_date']]		 = $v['zeelool_shoppingcart_total'];
                $zeeloolShoppingcartConversion[$v['create_date']]	 = $v['zeelool_shoppingcart_conversion'];
                $zeeloolRegisterCustomer[$v['create_date']]		     = $v['zeelool_register_customer'];
            }
            //如果有voogueme权限
            if(in_array(2,$arr)){
                $vooguemeSalesNumList[$v['create_date']] 			 = $v['voogueme_sales_num'];
                $vooguemeSalesMoneyList[$v['create_date']]			 = $v['voogueme_sales_money'];
                $vooguemeUnitPriceList[$v['create_date']]			 = $v['voogueme_unit_price'];
                $vooguemeShoppingcartTotal[$v['create_date']]		 = $v['voogueme_shoppingcart_total'];
                $vooguemeShoppingcartConversion[$v['create_date']]   = $v['voogueme_shoppingcart_conversion'];
                $vooguemeRegisterCustomer[$v['create_date']]		 = $v['voogueme_register_customer'];
            }
            //如果有nihao权限
            if(in_array(3,$arr)){
                $nihaoSalesNumList[$v['create_date']]    			 = $v['nihao_sales_num'];
                $nihaoSalesMoneyList[$v['create_date']]				 = $v['nihao_sales_money'];
                $nihaoUnitPriceList[$v['create_date']]				 = $v['nihao_unit_price'];
                $nihaoShoppingcartTotal[$v['create_date']]			 = $v['nihao_shoppingcart_total'];
                $nihaoShoppingcartConversion[$v['create_date']]	     = $v['nihao_shoppingcart_conversion'];
                $nihaoRegisterCustomer[$v['create_date']]			 = $v['nihao_register_customer'];
            }
            //如果有meeloog权限
            if(in_array(4,$arr)){
                $meeloogSalesNumList[$v['create_date']]    			 = $v['meeloog_sales_num'];
                $meeloogSalesMoneyList[$v['create_date']]			 = $v['meeloog_sales_money'];
                $meeloogUnitPriceList[$v['create_date']]			 = $v['meeloog_unit_price'];
                $meeloogShoppingcartTotal[$v['create_date']]		 = $v['meeloog_shoppingcart_total'];
                $meeloogShoppingcartConversion[$v['create_date']]	 = $v['meeloog_shoppingcart_conversion'];
                $meeloogRegisterCustomer[$v['create_date']]			 = $v['meeloog_register_customer'];
            }	
        }
        //下边部分数据 默认30天数据
        $bottom_data = $this->get_platform_data(1);
        $this->view->assign([
            'orderPlatformList'					=> $platform,
            'zeelool_data'						=> $zeelool_data,
            'date'								=> $this->date(),
            'zeeloolSalesNumList'       		=> $zeeloolSalesNumList ?:[], //折线图数据
            'vooguemeSalesNumList'      		=> $vooguemeSalesNumList ?:[],
			'nihaoSalesNumList'         		=> $nihaoSalesNumList ?:[],
			'meeloogSalesNumList'         		=> $meeloogSalesNumList ?:[],
            'zeeloolSalesMoneyList'				=> $zeeloolSalesMoneyList ?:[],
            'vooguemeSalesMoneyList'			=> $vooguemeSalesMoneyList ?:[],
			'nihaoSalesMoneyList'				=> $nihaoSalesMoneyList ?:[],
			'meeloogSalesMoneyList'				=> $meeloogSalesMoneyList ?:[],
            'zeeloolUnitPriceList'				=> $zeeloolUnitPriceList ?:[],
            'vooguemeUnitPriceList'				=> $vooguemeUnitPriceList ?:[],
			'nihaoUnitPriceList'				=> $nihaoUnitPriceList ?:[],
			'meeloogUnitPriceList'				=> $meeloogUnitPriceList ?:[],
            'zeeloolShoppingcartTotal'			=> $zeeloolShoppingcartTotal ?:[],
            'vooguemeShoppingcartTotal' 		=> $vooguemeShoppingcartTotal ?:[],
			'nihaoShoppingcartTotal'			=> $nihaoShoppingcartTotal ?:[],
			'meeloogShoppingcartTotal'			=> $meeloogShoppingcartTotal ?:[],
            'zeeloolShoppingcartConversion'	 	=> $zeeloolShoppingcartConversion ?:[],
            'vooguemeShoppingcartConversion'	=> $vooguemeShoppingcartConversion ?:[],
			'nihaoShoppingcartConversion'	 	=> $nihaoShoppingcartConversion ?:[],
			'meeloogShoppingcartConversion'	 	=> $meeloogShoppingcartConversion ?:[],
            'zeeloolRegisterCustomer'			=> $zeeloolRegisterCustomer ?:[],
            'vooguemeRegisterCustomer'			=> $vooguemeRegisterCustomer ?:[],
			'nihaoRegisterCustomer'				=> $nihaoRegisterCustomer ?:[],
			'meeloogRegisterCustomer'			=> $meeloogRegisterCustomer ?:[],
            'bottom_data'						=> $bottom_data,
            'result'                            => $result,
            'arr'                               => $arr
        ]);
        // $this->view->assign("orderPlatformList", $platform);
        // $this->view->assign("zeelool_data",$zeelool_data);
        // $this->view->assign("date",$this->date());
        return $this->view->fetch();
    }
    /***
     * 异步获取仪表盘首页上部分数据
     */
    public function async_data($order_platform = null)
    {
        if ($this->request->isAjax()) {
            if (!$order_platform) {
                return   $this->error('参数不存在，请重新尝试');
            }
            if (10 != $order_platform) {
                $data = $this->model->getList($order_platform);
            } else {
                $data = $this->model->getAllList();
            }
            if (false == $data) {
                return $this->error('没有该平台数据,请重新选择');
            }

            return $this->success('', '', $data, 0);
        }
    }
    /**
     * 异步获取仪表盘首页下部分数据
     *
     * @Description created by lsw
     * @author lsw
     * @since 2020/03/12 15:37:42
     * @param [type] $id
     * @return void
     */
    public function async_bottom_data($create_time=null)
    {
        if ($this->request->isAjax()) {
            if ($create_time) {
                $time = explode(' ', $create_time);
                $map['created_at'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
            } else {
                $map['created_at'] = ['between', [date('Y-m-d 00:00:00', strtotime('-7 day')), date('Y-m-d H:i:s', time())]];
            }
            $data = $this->get_platform_data($map);
            if (false == $data) {
                return $this->error('没有对应的时间数据，请重新尝试');
            }
            return $this->success('', '', $data, 0);
        }
    }
    /**
     * 获取平台数据来源(原先)
     * @param $id  date 中的ID
     * @Description created by lsw
     * @author lsw
     * @since 2020/03/12 14:04:02
     * @return void
     */
    // public function get_platform_data($map)
    // {
    // 	switch($id){
    // 		case 1:
    // 			$where = 'DATE_SUB(CURDATE(),INTERVAL 30 DAY) <= date(created_at) and created_at< curdate()';
    // 		break;
    // 		case 2:
    // 			$where = 'DATE_SUB(CURDATE(),INTERVAL 14 DAY) <= date(created_at) and created_at< curdate()';
    // 		break;
    // 		case 3:
    // 			$where = 'DATE_SUB(CURDATE(),INTERVAL 7 DAY) <= date(created_at) and created_at< curdate()';
    // 		break;
    // 		case 4:
    // 			$where = 'DATEDIFF(created_at,NOW())=-1';
    // 		break;
    // 		default:
    // 			$where = 'TO_DAYS(created_at) = TO_DAYS(NOW())';
    // 		break;
    // 	}
    // 	$zeelool_model 	= Db::connect('database.db_zeelool');
    // 	$voogueme_model = Db::connect('database.db_voogueme');
    // 	$nihao_model	= Db::connect('database.db_nihao');
    // 	$zeelool_model->table('sales_flat_order')->query("set time_zone='+8:00'");
    // 	$voogueme_model->table('sales_flat_order')->query("set time_zone='+8:00'");
    // 	$nihao_model->table('sales_flat_order')->query("set time_zone='+8:00'");
    // 	$status['status']  = ['in', ['processing', 'complete', 'creditcard_proccessing']];
    // 	$pc['store_id']    = 1;
    // 	$wap['store_id']   = ['in',[2,4]];
    // 	$app['store_id']   = 5;
    // 	//zeelool中pc销售额
    // 	$zeelool_pc_sales_money  	= $zeelool_model->table('sales_flat_order')->where($pc)->where($status)->where($where)->sum('base_grand_total');
    // 	//zeelool中wap销售额
    // 	$zeelool_wap_sales_money 	= $zeelool_model->table('sales_flat_order')->where($wap)->where($status)->where($where)->sum('base_grand_total');
    // 	//zeelool中app销售额
    // 	$zeelool_app_sales_money 	= $zeelool_model->table('sales_flat_order')->where($app)->where($status)->where($where)->sum('base_grand_total');
    // 	//zeelool中pc支付成功数
    // 	$zeelool_pc_sales_num	 	= $zeelool_model->table('sales_flat_order')->where($pc)->where($status)->where($where)->count('*');
    // 	//zeelool中wap支付成功数
    // 	$zeelool_wap_sales_num	 	= $zeelool_model->table('sales_flat_order')->where($wap)->where($status)->where($where)->count('*');
    // 	//zeelool中pc支付成功数
    // 	$zeelool_app_sales_num	 	= $zeelool_model->table('sales_flat_order')->where($app)->where($status)->where($where)->count('*');
    // 	//zeelool pc端客单价
    // 	$zeelool_pc_unit_price   	= @round(($zeelool_pc_sales_money/$zeelool_pc_sales_num),2);
    // 	//zeelool wap客单价
    // 	$zeelool_wap_unit_price  	= @round(($zeelool_wap_sales_money/$zeelool_wap_sales_num),2);
    // 	//zeelool app端客单价
    // 	$zeelool_app_unit_price 	= @round(($zeelool_app_sales_money/$zeelool_app_sales_num),2);
    // 	//voogueme中pc销售额
    // 	$voogueme_pc_sales_money 	= $voogueme_model->table('sales_flat_order')->where($pc)->where($status)->where($where)->sum('base_grand_total');
    // 	//voogueme中wap销售额
    // 	$voogueme_wap_sales_money	= $voogueme_model->table('sales_flat_order')->where($wap)->where($status)->where($where)->sum('base_grand_total');
    // 	//voogueme中pc支付成功数
    // 	$voogueme_pc_sales_num		= $voogueme_model->table('sales_flat_order')->where($pc)->where($status)->where($where)->count('*');
    // 	//voogueme中wap支付成功数
    // 	$voogueme_wap_sales_num	 	= $voogueme_model->table('sales_flat_order')->where($wap)->where($status)->where($where)->count('*');
    // 	//voogueme pc端客单价
    // 	$voogueme_pc_unit_price   	= @round(($voogueme_pc_sales_money/$voogueme_pc_sales_num),2);
    // 	//voogueme wap客单价
    // 	$voogueme_wap_unit_price  	= @round(($voogueme_wap_sales_money/$voogueme_wap_sales_num),2);
    // 	//nihao中pc销售额
    // 	$nihao_pc_sales_money 		= $nihao_model->table('sales_flat_order')->where($pc)->where($status)->where($where)->sum('base_grand_total');
    // 	//nihao中wap销售额
    // 	$nihao_wap_sales_money		= $nihao_model->table('sales_flat_order')->where($wap)->where($status)->where($where)->sum('base_grand_total');
    // 	//nihao中pc支付成功数
    // 	$nihao_pc_sales_num			= $nihao_model->table('sales_flat_order')->where($pc)->where($status)->where($where)->count('*');
    // 	//nihao中wap支付成功数
    // 	$nihao_wap_sales_num	 	= $nihao_model->table('sales_flat_order')->where($wap)->where($status)->where($where)->count('*');
    // 	//nihao pc端客单价
    // 	$nihao_pc_unit_price   		= @round(($nihao_pc_sales_money/$nihao_pc_sales_num),2);
    // 	//nihao wap客单价
    // 	$nihao_wap_unit_price  		= @round(($nihao_wap_sales_money/$nihao_wap_sales_num),2);
    // 	return [
    // 		'zeelool_pc_sales_money' 	=> $zeelool_pc_sales_money,
    // 		'zeelool_wap_sales_money' 	=> $zeelool_wap_sales_money,
    // 		'zeelool_app_sales_money' 	=> $zeelool_app_sales_money,
    // 		'zeelool_pc_sales_num' 		=> $zeelool_pc_sales_num,
    // 		'zeelool_wap_sales_num'		=> $zeelool_wap_sales_num,
    // 		'zeelool_app_sales_num' 	=> $zeelool_app_sales_num,
    // 		'zeelool_pc_unit_price' 	=> $zeelool_pc_unit_price,
    // 		'zeelool_wap_unit_price' 	=> $zeelool_wap_unit_price,
    // 		'zeelool_app_unit_price' 	=> $zeelool_app_unit_price,
    // 		'voogueme_pc_sales_money' 	=> $voogueme_pc_sales_money,
    // 		'voogueme_wap_sales_money' 	=> $voogueme_wap_sales_money,
    // 		'voogueme_pc_sales_num' 	=> $voogueme_pc_sales_num,
    // 		'voogueme_wap_sales_num' 	=> $voogueme_wap_sales_num,
    // 		'voogueme_pc_unit_price' 	=> $voogueme_pc_unit_price,
    // 		'voogueme_wap_unit_price' 	=> $voogueme_wap_unit_price,
    // 		'nihao_pc_sales_money' 		=> $nihao_pc_sales_money,
    // 		'nihao_wap_sales_money' 	=> $nihao_wap_sales_money,
    // 		'nihao_pc_sales_num' 		=> $nihao_pc_sales_num,
    // 		'nihao_wap_sales_num' 		=> $nihao_wap_sales_num,
    // 		'nihao_pc_unit_price' 		=> $nihao_pc_unit_price,
    // 		'nihao_wap_unit_price' 		=> $nihao_wap_unit_price
    // 	];

    // }
    public function get_platform_data($map)
    {
        $arr = Cache::get('Dashboard_get_platform_data_'.md5(serialize($map)));
        if ($arr) {
            return $arr;
        }
        $zeelool_model 	= Db::connect('database.db_zeelool');
        $voogueme_model = Db::connect('database.db_voogueme');
        $nihao_model	= Db::connect('database.db_nihao');
        $meeloog_model	= Db::connect('database.db_meeloog');
        $zeelool_model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $voogueme_model->table('sales_flat_order')->query("set time_zone='+8:00'");
		$nihao_model->table('sales_flat_order')->query("set time_zone='+8:00'");
		$meeloog_model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $status['status']  = ['in', ['processing', 'complete', 'free_processing','paypal_canceled_reversal','paypal_reversed']];
        $pc['store_id']    = 1;
        $wap['store_id']   = ['in',[2,4]];
        $app['store_id']   = 5;
        $android['store_id']   = 6;
        //zeelool中pc销售额
        $zeelool_pc_sales_money  	= $zeelool_model->table('sales_flat_order')->where($pc)->where($status)->where($map)->sum('base_grand_total');
        //zeelool中wap销售额
        $zeelool_wap_sales_money 	= $zeelool_model->table('sales_flat_order')->where($wap)->where($status)->where($map)->sum('base_grand_total');
        //zeelool中IOS销售额
        $zeelool_app_sales_money 	= $zeelool_model->table('sales_flat_order')->where($app)->where($status)->where($map)->sum('base_grand_total');
        //zeelool中Android销售额
        $zeelool_android_sales_money 	= $zeelool_model->table('sales_flat_order')->where($android)->where($status)->where($map)->sum('base_grand_total');

        //zeelool中pc支付成功数
        $zeelool_pc_sales_num	 	= $zeelool_model->table('sales_flat_order')->where($pc)->where($status)->where($map)->count('*');
        //zeelool中wap支付成功数
        $zeelool_wap_sales_num	 	= $zeelool_model->table('sales_flat_order')->where($wap)->where($status)->where($map)->count('*');
        //zeelool中IOS支付成功数
        $zeelool_app_sales_num	 	= $zeelool_model->table('sales_flat_order')->where($app)->where($status)->where($map)->count('*');
        //zeelool中Android支付成功数
        $zeelool_android_sales_num	 	= $zeelool_model->table('sales_flat_order')->where($android)->where($status)->where($map)->count('*');
		
		if($zeelool_pc_sales_num>0){
			//zeelool pc端客单价
			$zeelool_pc_unit_price   	= round(($zeelool_pc_sales_money/$zeelool_pc_sales_num), 2);			
		}else{
			$zeelool_pc_unit_price  	= 0;
		}
		//zeelool wap客单价
		if($zeelool_wap_sales_num>0){
			$zeelool_wap_unit_price  	= round(($zeelool_wap_sales_money/$zeelool_wap_sales_num), 2);
		}else{
			$zeelool_wap_unit_price     = 0;
		}
		//zeelool IOS端客单价
		if($zeelool_app_sales_num>0){
			$zeelool_app_unit_price 	= round(($zeelool_app_sales_money/$zeelool_app_sales_num), 2);
		}else{
			$zeelool_app_unit_price		= 0;
		}
        //zeelool Android端客单价
        if($zeelool_android_sales_num>0){
            $zeelool_android_unit_price 	= round(($zeelool_android_sales_money/$zeelool_android_sales_num), 2);
        }else{
            $zeelool_android_unit_price		= 0;
        }
        //voogueme中pc销售额
        $voogueme_pc_sales_money 	= $voogueme_model->table('sales_flat_order')->where($pc)->where($status)->where($map)->sum('base_grand_total');
        //voogueme中wap销售额
        $voogueme_wap_sales_money	= $voogueme_model->table('sales_flat_order')->where($wap)->where($status)->where($map)->sum('base_grand_total');
        //voogueme中pc支付成功数
        $voogueme_pc_sales_num		= $voogueme_model->table('sales_flat_order')->where($pc)->where($status)->where($map)->count('*');
        //voogueme中wap支付成功数
        $voogueme_wap_sales_num	 	= $voogueme_model->table('sales_flat_order')->where($wap)->where($status)->where($map)->count('*');
		//voogueme pc端客单价
		if($voogueme_pc_sales_num>0){
        	$voogueme_pc_unit_price   	= round(($voogueme_pc_sales_money/$voogueme_pc_sales_num), 2);
		}else{
			$voogueme_pc_unit_price   	= 0;
		}
		//voogueme wap客单价
		if($voogueme_wap_sales_num>0){
			$voogueme_wap_unit_price  	= round(($voogueme_wap_sales_money/$voogueme_wap_sales_num), 2);
		}else{
			$voogueme_wap_unit_price  	= 0;
		}

        //nihao中pc销售额
        $nihao_pc_sales_money 		= $nihao_model->table('sales_flat_order')->where($pc)->where($status)->where($map)->sum('base_grand_total');
        //nihao中wap销售额
        $nihao_wap_sales_money		= $nihao_model->table('sales_flat_order')->where($wap)->where($status)->where($map)->sum('base_grand_total');
        //nihao中pc支付成功数
        $nihao_pc_sales_num			= $nihao_model->table('sales_flat_order')->where($pc)->where($status)->where($map)->count('*');
        //nihao中wap支付成功数
        $nihao_wap_sales_num	 	= $nihao_model->table('sales_flat_order')->where($wap)->where($status)->where($map)->count('*');
		//nihao pc端客单价
		if($nihao_pc_sales_num>0){
			$nihao_pc_unit_price   	= round(($nihao_pc_sales_money/$nihao_pc_sales_num), 2);
		}else{
			$nihao_pc_unit_price    = 0;
		}
		//nihao wap客单价
		if($nihao_wap_sales_num>0){
			$nihao_wap_unit_price   = round(($nihao_wap_sales_money/$nihao_wap_sales_num), 2);
		}else{
			$nihao_wap_unit_price   = 0;
		}
        
		
        //meeloog中pc销售额
        $meeloog_pc_sales_money 	= $meeloog_model->table('sales_flat_order')->where($pc)->where($status)->where($map)->sum('base_grand_total');
        //meeloog中wap销售额
        $meeloog_wap_sales_money	= $meeloog_model->table('sales_flat_order')->where($wap)->where($status)->where($map)->sum('base_grand_total');
        //meeloog中pc支付成功数
        $meeloog_pc_sales_num		= $meeloog_model->table('sales_flat_order')->where($pc)->where($status)->where($map)->count('*');
        //meeloog中wap支付成功数
        $meeloog_wap_sales_num	 	= $meeloog_model->table('sales_flat_order')->where($wap)->where($status)->where($map)->count('*');
		//meeloog pc端客单价
		if($meeloog_pc_sales_num>0){
			$meeloog_pc_unit_price  = round(($meeloog_pc_sales_money/$meeloog_pc_sales_num), 2);
		}else{
			$meeloog_pc_unit_price  = 0;
		}
		//meeloog wap客单价
		if($meeloog_wap_sales_num>0){
			$meeloog_wap_unit_price = round(($meeloog_wap_sales_money/$meeloog_wap_sales_num), 2);
		}else{
			$meeloog_wap_unit_price = 0;
		}
        		
        $arr = [
            'zeelool_pc_sales_money' 	=> $zeelool_pc_sales_money ?:0,
            'zeelool_wap_sales_money' 	=> $zeelool_wap_sales_money ?:0,
            'zeelool_app_sales_money' 	=> $zeelool_app_sales_money ?:0,
            'zeelool_android_sales_money' 	=> $zeelool_android_sales_money ?:0,
            'zeelool_pc_sales_num' 		=> $zeelool_pc_sales_num ?:0,
            'zeelool_wap_sales_num'		=> $zeelool_wap_sales_num ?:0,
            'zeelool_app_sales_num' 	=> $zeelool_app_sales_num ?:0,
            'zeelool_android_sales_num' 	=> $zeelool_android_sales_num ?:0,
            'zeelool_pc_unit_price' 	=> $zeelool_pc_unit_price ?:0,
            'zeelool_wap_unit_price' 	=> $zeelool_wap_unit_price ?:0,
            'zeelool_app_unit_price' 	=> $zeelool_app_unit_price ?:0,
            'zeelool_android_unit_price' 	=> $zeelool_android_unit_price ?:0,
            'voogueme_pc_sales_money' 	=> $voogueme_pc_sales_money ?:0,
            'voogueme_wap_sales_money' 	=> $voogueme_wap_sales_money ?:0,
            'voogueme_pc_sales_num' 	=> $voogueme_pc_sales_num ?:0,
            'voogueme_wap_sales_num' 	=> $voogueme_wap_sales_num ?:0,
            'voogueme_pc_unit_price' 	=> $voogueme_pc_unit_price ?:0,
            'voogueme_wap_unit_price' 	=> $voogueme_wap_unit_price ?:0,
            'nihao_pc_sales_money' 		=> $nihao_pc_sales_money ?:0,
            'nihao_wap_sales_money' 	=> $nihao_wap_sales_money ?:0,
            'nihao_pc_sales_num' 		=> $nihao_pc_sales_num ?:0,
            'nihao_wap_sales_num' 		=> $nihao_wap_sales_num ?:0,
            'nihao_pc_unit_price' 		=> $nihao_pc_unit_price ?:0,
			'nihao_wap_unit_price' 		=> $nihao_wap_unit_price ?:0,
            'meeloog_pc_sales_money' 	=> $meeloog_pc_sales_money ?:0,
            'meeloog_wap_sales_money' 	=> $meeloog_wap_sales_money ?:0,
            'meeloog_pc_sales_num' 		=> $meeloog_pc_sales_num ?:0,
            'meeloog_wap_sales_num' 	=> $meeloog_wap_sales_num ?:0,
            'meeloog_pc_unit_price' 	=> $meeloog_pc_unit_price ?:0,
            'meeloog_wap_unit_price' 	=> $meeloog_wap_unit_price ?:0,			
        ];
        Cache::set('Dashboard_get_platform_data_'.md5(serialize($map)), $arr, 7200);
        return $arr;
    }
    /**
     * 对应的zeelool权限
     *
     * @Description
     * @author lsw
     * @since 2020/06/01 16:38:25 
     * @return void
     */
    public function zeelool_index()
    {

    }
    /**
     * 对应的voogueme权限
     *
     * @Description
     * @author lsw
     * @since 2020/06/01 16:39:19 
     * @return void
     */
    public function voogueme_index()
    {

    }
    /**
     * 对应的nihao权限
     *
     * @Description
     * @author lsw
     * @since 2020/06/01 16:40:10 
     * @return void
     */
    public function nihao_index()
    {

    }
    /**
     * 对应的meeloog权限
     *
     * @Description
     * @author lsw
     * @since 2020/06/01 16:41:01 
     * @return void
     */
    public function meeloog_index()
    {

    }
    public function ceshi(){
        $user_id = session('admin.id');
        $result = (new AuthGroupAccess)->getUserPrivilege($user_id);
        dump($result);
    }
}
