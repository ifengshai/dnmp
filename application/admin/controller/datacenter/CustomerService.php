<?php

namespace app\admin\controller\datacenter;

use app\common\controller\Backend;
use think\Db;
use think\Cache;
class CustomerService extends Backend
{
    protected $model = null;
    protected $step  = null;
    public function _initialize()
    {
        parent::_initialize();
        $this->model   = new \app\admin\model\saleaftermanage\WorkOrderList;
        $this->step    = new \app\admin\model\saleaftermanage\WorkOrderMeasure;
    }
    /**
     * 客服数据(首页)
     *
     * @Description
     * @author lsw
     * @since 2020/05/11 14:42:10
     * @return void
     */
    public function index()
    {
        $a = 10;
        if ($a>10) {
            echo 111;
        } else {
            echo 222;
        }
    }
    /**
     * 工单问题措施详情
     *
     * @Description
     * @author lsw
     * @since 2020/05/11 14:50:29
     * @return void
     */
    public function detail()
    {
        $create_time = input('create_time');
        $platform    = input('order_platform', 1);
        //异步调用图标数据
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            if ($params['time']) {
                $time = explode(' ', $params['time']);
                $map['complete_time'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
            } else {
                $map['complete_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-30 day')), date('Y-m-d H:i:s', time())]];
            }
            $order_platform = $params['platform'];
            //问题措施比统计
            if ('echart1' == $params['key']) {
                //问题大分类统计、措施统计
                $data = $this->get_workorder_data($order_platform, $map);
                $customer_problem_classify = config('workorder.customer_problem_classify');
                $column = array_keys($customer_problem_classify);
                $columnData = [];
                foreach ($column as $k =>$v) {
                    $columnData[$k]['name'] = $v;
                    $columnData[$k]['value'] = $data['problem_type'][$k];
                }
                // $json['column'] = $column;
                // $json['columnData'] = $columnData;
                // return json(['code' => 1, 'data' => $json]);
            } elseif ('echart2' == $params['key']) {
                //问题类型统计
                $problem_data = $this->get_problem_type_data($order_platform, $map, 1);
                //问题类型数组
                $customer_problem_arr   = config('workorder.customer_problem_classify_arr')[1];
                $customer_problem_list  = config('workorder.customer_problem_type');
                //循环数组根据id获取客服问题类型
                $column = $columnData = [];
                foreach ($customer_problem_arr as $k => $v) {
                    $column[] = $customer_problem_list[$v];
                }
                foreach ($column as $ck => $cv) {
                    $columnData[$ck]['name'] = $cv;
                    $columnData[$ck]['value'] = $problem_data[$ck];
                }
                // $json['column'] = $column;
                // $json['columnData'] = $columnData;
                // return json(['code' => 1, 'data' => $json]);
            } elseif ('echart3' == $params['key']) {
                //问题大分类统计、措施统计
                $data = $this->get_workorder_data($order_platform, $map);
                $step = config('workorder.step');
                $column = array_merge($step);
                $columnData = [];
                foreach ($column as $k =>$v) {
                    $columnData[$k]['name'] = $v;
                    $columnData[$k]['value'] = $data['step'][$k];
                }
                // $json['column'] = $column;
                // $json['columnData'] = $columnData;
                // return json(['code' => 1, 'data' => $json]);
            } elseif ('echart4' == $params['key']) {
                //问题类型统计
                $data = $this->get_problem_step_data($order_platform, $map, 1);
                //问题类型数组
                $step = config('workorder.step');
                $column = array_merge($step);
                $columnData = [];
                foreach ($column as $k =>$v) {
                    $columnData[$k]['name'] = $v;
                    $columnData[$k]['value'] = $data['step'][$k];
                }         
            }
                $json['column'] = $column;
                $json['columnData'] = $columnData;
                return json(['code' => 1, 'data' => $json]);
            // if (false == $data) {
            //     return $this->error('没有对应的时间数据，请重新尝试');
            // }
            // return $this->success('', '', $data, 0);
        }elseif($this->request->isPost()){ //提交获取右边的数据信息
            if ($create_time) {
                $time = explode(' ', $create_time);
                $map['complete_time'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
            } else {
                $map['complete_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-30 day')), date('Y-m-d H:i:s', time())]];
            }
            //问题大分类统计、措施统计
            $data = $this->get_workorder_data($platform, $map);
            //问题分类数据
            $problem_data = $this->get_problem_type_data($platform, $map, 1);
            //求出措施数据
            $step_data    = $this->get_problem_step_data($platform, $map, 1);
            //求出问题大分类的总数,措施的总数
            $problem_type_total = $step_total = $problem_form_total = $step_four_total =  0 ;
            foreach($data['problem_type'] as $pv){
                $problem_type_total += $pv;
            }
            foreach($data['step'] as $sv){
                $step_total += $sv;
            }
            //求出问题类型的总数
            foreach($problem_data as $dv){
                $problem_form_total +=$dv;
            }
            //求出措施总数据
            foreach($step_data['step'] as $tv){
                $step_four_total+= $tv;
            } 
            //问题类型统计
            $step = array_merge(config('workorder.step'));
            //求出默认的问题类型，饼图2右边展示的东东
            $customer_problem_arr   = config('workorder.customer_problem_classify_arr')[1];
            $customer_problem_list  = config('workorder.customer_problem_type');
            $customer_arr = [];
            foreach ($customer_problem_arr as $k => $v) {
                $customer_arr[] = $customer_problem_list[$v];
            }
            $this->view->assign(compact('data','problem_type_total','step_total','problem_form_total','step_four_total','problem_data','step_data','step','customer_arr'));
        }else{ //默认获取右边的信息
            if ($create_time) {
                $time = explode(' ', $create_time);
                $map['complete_time'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
            } else {
                $map['complete_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-30 day')), date('Y-m-d H:i:s', time())]];
            }
            //问题大分类统计、措施统计
            $data = $this->get_workorder_data($platform, $map);
            //问题分类数据
            $problem_data = $this->get_problem_type_data($platform, $map, 1);
            //求出措施数据
            $step_data    = $this->get_problem_step_data($platform, $map, 1);
            //求出问题大分类的总数,措施的总数
            $problem_type_total = $step_total = $problem_form_total = $step_four_total =  0 ;
            foreach($data['problem_type'] as $pv){
                $problem_type_total += $pv;
            }
            foreach($data['step'] as $sv){
                $step_total += $sv;
            }
            //求出问题类型的总数
            foreach($problem_data as $dv){
                $problem_form_total +=$dv;
            }
            //求出措施总数据
            foreach($step_data['step'] as $tv){
                $step_four_total+= $tv;
            } 
            //问题类型统计
            $step = array_merge(config('workorder.step'));
            //求出默认的问题类型，饼图2右边展示的东东
            $customer_problem_arr   = config('workorder.customer_problem_classify_arr')[1];
            $customer_problem_list  = config('workorder.customer_problem_type');
            $customer_arr = [];
            foreach ($customer_problem_arr as $k => $v) {
                $customer_arr[] = $customer_problem_list[$v];
            }
            $this->view->assign(compact('data','problem_type_total','step_total','problem_form_total','step_four_total','problem_data','step_data','step','customer_arr'));
        }

        //第四个饼图二级tab联动默认的二级数据 start
        $customer_problem_arr   = config('workorder.customer_problem_classify_arr')[1];
        $customer_problem_list  = config('workorder.customer_problem_type');
        //循环数组根据id获取客服问题类型
        $column = [];
        foreach ($customer_problem_arr as $k => $v) {
            $column[$v] = $customer_problem_list[$v];
        }
        //第四个饼图二级tab联动默认的二级数据 end
        //第二张和第四张tab默认显示数据 start
        $customer_problem_classify = config('workorder.customer_problem_classify');
        $problem_type = array_keys($customer_problem_classify);
        //第二张和第四张tab默认显示数据 end
        $orderPlatformList = config('workorder.platform');
        $this->view->assign(compact('orderPlatformList', 'create_time', 'platform', 'problem_type','column'));
        return $this->view->fetch();
    }
    /**
     * 切换问题类型
     *
     * @Description
     * @author lsw
     * @since 2020/05/13 18:47:18
     * @return void
     */
    public function problem()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            if ($params['time']) {
                $time = explode(' ', $params['time']);
                $map['complete_time'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
            } else {
                $map['complete_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-30 day')), date('Y-m-d H:i:s', time())]];
            }
            $value = $params['value'];
            $order_platform = $params['platform'];
            //问题类型统计
            $problem_data = $this->get_problem_type_data($order_platform, $map, $value);
            //问题类型数组
            $customer_problem_arr   = config('workorder.customer_problem_classify_arr')[$value];
            $customer_problem_list  = config('workorder.customer_problem_type');
            //循环数组根据id获取客服问题类型
            $column = $columnData = [];
            foreach ($customer_problem_arr as $k => $v) {
                $column[] = $customer_problem_list[$v];
            }
            foreach ($column as $ck => $cv) {
                $columnData[$ck]['name'] = $cv;
                $columnData[$ck]['value'] = $problem_data[$ck];
            }
            $json['column'] = $column;
            $json['columnData'] = $columnData;
            return json(['code' => 1, 'data' => $json]);
        }
    }
    /**
     * 根据措施切换问题类型
     *
     * @Description
     * @author lsw
     * @since 2020/05/14 11:37:10 
     * @return void
     */
    public function step()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            if ($params['time']) {
                $time = explode(' ', $params['time']);
                $map['complete_time'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
            } else {
                $map['complete_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-30 day')), date('Y-m-d H:i:s', time())]];
            }
            $value = $params['value'];
            $order_platform = $params['platform'];
            //问题类型统计
            $data = $this->get_problem_step_data($order_platform, $map, $value);
            //问题类型数组
            $step = config('workorder.step');
            $column = array_merge($step);
            $columnData = [];
            foreach ($column as $k =>$v) {
                $columnData[$k]['name'] = $v;
                $columnData[$k]['value'] = $data['step'][$k];
            }
            $json['column'] = $column;
            $json['columnData'] = $columnData;
            return json(['code' => 1, 'data' => $json]);
        }        
    }
    /**
     * 异步获取二级联动数据
     *
     * @Description
     * @author lsw
     * @since 2020/05/14 10:26:21 
     * @return void
     */
    public function get_problem_by_classify()
    {
        if($this->request->isAjax()){
            $params = $this->request->param();
            $value = $params['value'];
            $customer_problem_arr   = config('workorder.customer_problem_classify_arr')[$value];
            $customer_problem_list  = config('workorder.customer_problem_type');
            //循环数组根据id获取客服问题类型
            $column = [];
            foreach ($customer_problem_arr as $k => $v) {
                $column[$v] = $customer_problem_list[$v];
            }
            $this->success('','',$column);           
        }
    }
    /**
     *获取workorder的统计数据
     *问题大分类统计、措施统计
     * @Description
     * @author lsw
     * @since 2020/05/12 10:02:54
     * @return void
     */
    public function get_workorder_data($platform, $map)
    {
        $arr = Cache::get('CustomerService_get_workorder_data_'.$platform.md5(serialize($map)));
		if($arr){
			return $arr;
		}
        if ($platform<10) {
            $where['work_platform'] = $platform;
        }
        $where['work_type'] = 1;
        //订单修改数组
        //$changeOrderArr = config('workorder.customer_problem_classify_arr')[1];
        //
        //问题总数组
        $problem_arr = config('workorder.customer_problem_classify_arr');
        //问题结果
        $result = [];
        foreach ($problem_arr as $v) {
            //问题大分类的统计
            $result['problem_type'][] = $this->model->where($where)->where($map)->where('problem_type_id', 'in', $v)->count('id');
        }
        //所有完成的work_id
        $all_work_id = $this->model->where($where)->where($map)->column('id');
        //措施总数组
        $step_arr = config('workorder.step');
        $where_step['operation_type'] = 1;
        foreach ($step_arr as $sk=>$sv) {
            $result['step'][] = $this->step->where($where_step)->where('measure_choose_id', $sk)->where('work_id', 'in', $all_work_id)->count('id');
        }
        Cache::set('CustomerService_get_workorder_data_'.$platform.md5(serialize($map)),$result,7200);
        return $result;
    }
    /**
     * 问题类型统计
     *
     * @Description
     * @author lsw
     * @since 2020/05/12 14:46:20
     * @return void
     */
    public function get_problem_type_data($platform, $map, $problem_type)
    {
        $arr = Cache::get('CustomerService_get_problem_type_data_'.$platform.'_'.$problem_type.md5(serialize($map)));
		if($arr){
			return $arr;
		}        
        if ($platform<10) {
            $where['work_platform'] = $platform;
        }
        $where['work_type'] = 1;
        //所有的问题组
        $problem_arr = config('workorder.customer_problem_classify_arr');
        //当前的问题组
        $current_problem_arr = $problem_arr[$problem_type];
        $result = [];
        foreach ($current_problem_arr as $k =>$v) {
            $result[$k] = $this->model->where($where)->where($map)->where('problem_type_id', $v)->count('id');
        }
        Cache::set('CustomerService_get_problem_type_data_'.$platform.'_'.$problem_type.md5(serialize($map)),$result,7200);
        return $result;
    }
    /**
     * 问题措施比统计
     *
     * @Description
     * @author lsw
     * @since 2020/05/12 15:16:48
     * @param [type] $platform
     * @param [type] $map
     * @param [type] $problem_type
     * @param [type] $step_id
     * @return void
     */
    public function get_problem_step_data($platform, $map, $problem_id)
    {
        $arr = Cache::get('CustomerService_get_problem_step_data_'.$platform.'_'.$problem_id.md5(serialize($map)));
		if($arr){
			return $arr;
		}         
        if ($platform<10) {
            $where['work_platform'] = $platform;
        }
        $where['work_type'] = 1;
        $result = $info = [];
        $result = $this->model->where($where)->where($map)->where('problem_type_id', $problem_id)->column('id');
        $where_step['operation_type'] = 1;
        $step_arr = config('workorder.step');
        foreach ($step_arr as $k =>$v) {
            $info['step'][]  = $this->step->where($where_step)->where('work_id', 'in', $result)->where('measure_choose_id', $k)->count('id');
        }
        Cache::set('CustomerService_get_problem_step_data_'.$platform.'_'.$problem_id.md5(serialize($map)),$info,7200);
        return $info;
    }
    /**
     * 异步获取第二个饼图右边的数据
     *
     * @Description
     * @author lsw
     * @since 2020/05/14 18:51:57 
     * @return void
     */
    public function get_two_pie_data()
    {
        if($this->request->isAjax()){
            $params = $this->request->param();
            if ($params['time']) {
                $time = explode(' ', $params['time']);
                $map['complete_time'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
            } else {
                $map['complete_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-30 day')), date('Y-m-d H:i:s', time())]];
            }
            $order_platform = $params['platform'];
            $value          = $params['value'];
            $problem_data = $this->get_problem_type_data($order_platform, $map, $value);
            $problem_form_total = 0;
            foreach($problem_data as $dv){
                $problem_form_total +=$dv;
            }
            $customer_problem_arr   = config('workorder.customer_problem_classify_arr')[$value];
            $customer_problem_list  = config('workorder.customer_problem_type');
            $customer_arr = [];
            foreach ($customer_problem_arr as $k => $v) {
                $customer_arr[] = $customer_problem_list[$v];
            }
            $data['problem_data'] =  $problem_data;
            $data['problem_form_total'] =  $problem_form_total;
            $data['customer_arr'] =  $customer_arr;
            $this->success('','',$data);          
        }
    }
    /**
     * 异步获取第四个饼图右边数据
     *
     * @Description
     * @author lsw
     * @since 2020/05/15 09:18:21 
     * @return void
     */
    public function get_four_pie_data()
    {
        if($this->request->isAjax()){
            $params = $this->request->param();
            if ($params['time']) {
                $time = explode(' ', $params['time']);
                $map['complete_time'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
            } else {
                $map['complete_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-30 day')), date('Y-m-d H:i:s', time())]];
            }
            $order_platform = $params['platform'];
            $value          = $params['value'];
            $step_data = $this->get_problem_step_data($order_platform, $map, $value);
            $step_four_total = 0;
            //求出措施总数据
            foreach($step_data['step'] as $tv){
                $step_four_total+= $tv;
            }
            //问题类型统计
            $step = array_merge(config('workorder.step'));
            $data['step_data'] =  $step_data;
            $data['step_four_total'] =  $step_four_total;
            $data['step'] =  $step;
            $this->success('','',$data);          
        }
    }
}
