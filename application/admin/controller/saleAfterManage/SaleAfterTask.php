<?php

namespace app\admin\controller\saleAfterManage;

use app\common\controller\Backend;
use app\admin\model\saleAfterManage\SaleAfterIssue;
use think\Request;
use think\Db;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class SaleAfterTask extends Backend
{
    
    /**
     * SaleAfterTask模型对象
     * @var \app\admin\model\saleAfterManage\SaleAfterTask
     */
    protected $model = null;
    protected $relationSearch = true;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\saleAfterManage\SaleAfterTask;
        $this->view->assign("orderPlatformList", $this->model->getOrderPlatformList());
        $this->view->assign("orderStatusList", $this->model->getOrderStatusList());
        $this->view->assign('prtyIdList',$this->model->getPrtyIdList());
        $this->view->assign('issueList',(new SaleAfterIssue())->getIssueList(1,0));

    }
    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
               ->with(['saleAfterIssue'])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
               ->with(['saleAfterIssue'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $params['task_number'] = 'CO'.rand(100,999).rand(100,999);
                    $result = $this->model->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success('','/admin/saleaftermanage/sale_after_task/index');
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /***
     * 异步请求获取订单所在平台和订单号处理
     * @param
     */
    public function ajax( Request $request)
    {
        if($this->request->isAjax()){
            $ordertype = $request->post('ordertype');
            $order_number = $request->post('order_number');
            if($ordertype<1 || $ordertype>5){ //不在平台之内
               return  $this->error('选择平台错误，请重新选择','','error',0);
            }
            if(!$order_number){
               return  $this->error('订单号不存在，请重新选择','','error',0);
            }
            $result = $this->model->getOrderInfo($ordertype,$order_number);
            if(!$result){
                return $this->error('找不到这个订单，请重新尝试','','error',0);
            }
            return $this->success('','',$result,0);
        }else{
            $arr=[
                12=>'a',34=>'b',57=>'c',84=>'d',
            ];
            $json = json_encode($arr);
            return $this->success('ok','',$json);
        }


    }
    public function ceshi()
    {
        $total = $this->model
            ->with(['sale_after_issue'])
            ->count();

        $list = $this->model
            ->with(['sale_after_issue'])
            ->select();

        $list = collection($list)->toArray();
        dump($list);
    }
    public function getOrderInfo()
    {
        //$order_number   = 430016936;
        $order_number = 100008922;
        //$order_number = 400000041; //zeelool
        $result = Db::connect('database.db_config1')->table('sales_flat_order')->where('increment_id','=',$order_number)->field('entity_id,status,increment_id,customer_email,customer_firstname,customer_lastname,total_item_count')->find();
        if(!$result){
            return false;
        }
        $item = Db::connect('database.db_config1')->table('sales_flat_order_item')->where('order_id','=',$result['entity_id'])->field('item_id,name,sku,qty_ordered,product_options')->select();
        if(!$item){
            return false;
        }
        $arr = [];
        foreach($item as $key=> $val){
            $arr[$key]['item_id'] = $val['item_id'];
            $arr[$key]['name']    = $val['name'];
            $arr[$key]['sku']     = $val['sku'];
            $arr[$key]['qty_ordered']     = $val['qty_ordered'];
            $tmp_product_options = unserialize($val['product_options']);
            $arr[$key]['index_type'] = $tmp_product_options['info_buyRequest']['tmplens']['index_type'];
            $arr[$key]['coatiing_name'] = isset($tmp_product_options['info_buyRequest']['tmplens']['coatiing_name']) ? $tmp_product_options['info_buyRequest']['tmplens']['coatiing_name'] : "";
            $tmp_prescription_params = $tmp_product_options['info_buyRequest']['tmplens']['prescription'];
            if(!empty($tmp_prescription_params)){
                $tmp_prescription_params = explode("&", $tmp_prescription_params);
                $tmp_lens_params = array();
                foreach ($tmp_prescription_params as $tmp_key => $tmp_value) {
                    $arr_value = explode("=", $tmp_value);
                    $tmp_lens_params[$arr_value[0]] = $arr_value[1];
                }
                $arr[$key]['prescription_type'] = $tmp_lens_params['prescription_type'];
                $arr[$key]['od_sph']   = $tmp_lens_params['od_sph'];
                $arr[$key]['od_cyl']   = $tmp_lens_params['od_cyl'];
                $arr[$key]['od_axis']   = $tmp_lens_params['od_axis'];
                $arr[$key]['od_add']   = $tmp_lens_params['od_add'];
                $arr[$key]['os_sph']   = $tmp_lens_params['os_sph'];
                $arr[$key]['os_cyl']   = $tmp_lens_params['os_cyl'];
                $arr[$key]['os_axis']   = $tmp_lens_params['os_axis'];
                $arr[$key]['os_add']   = $tmp_lens_params['os_add'];
                if(isset($tmp_lens_params['pdcheck']) && $tmp_lens_params['pdcheck'] == 'on'){  //双pd值
                    $arr[$key]['pd_r'] = $tmp_lens_params['pd_r'];
                    $arr[$key]['pd_l'] = $tmp_lens_params['pd_l'];
                }else{
                    $arr[$key]['pd_r'] = $tmp_lens_params['pd'];
                    $arr[$key]['pd_r'] = $tmp_lens_params['pd'];
                }
                if(isset($tmp_lens_params['prismcheck']) && $tmp_lens_params['prismcheck'] == 'on'){ //存在斜视
                    $arr[$key]['od_bd'] = $tmp_lens_params['od_bd'];
                    $arr[$key]['od_pv'] = $tmp_lens_params['od_pv'];
                    $arr[$key]['os_pv'] = $tmp_lens_params['os_pv'];
                    $arr[$key]['os_bd'] = $tmp_lens_params['os_bd'];
                    $arr[$key]['od_pv_r'] = $tmp_lens_params['od_pv_r'];
                    $arr[$key]['od_bd_r'] = $tmp_lens_params['od_bd_r'];
                    $arr[$key]['os_pv_r'] = $tmp_lens_params['os_pv_r'];
                    $arr[$key]['os_bd_r'] = $tmp_lens_params['os_bd_r'];
                }else{
                    $arr[$key]['od_bd'] = "";
                    $arr[$key]['od_pv'] = "";
                    $arr[$key]['os_pv'] = "";
                    $arr[$key]['os_bd'] = "";
                    $arr[$key]['od_pv_r'] = "";
                    $arr[$key]['od_bd_r'] = "";
                    $arr[$key]['os_pv_r'] = "";
                    $arr[$key]['os_bd_r'] = "";
                }
            }else{
                $arr[$key]['prescription_type'] = "";
                $arr[$key]['od_sph']   = "";
                $arr[$key]['od_cyl']   = "";
                $arr[$key]['od_axis']   = "";
                $arr[$key]['od_add']   = "";
                $arr[$key]['os_sph']   = "";
                $arr[$key]['os_cyl']   = "";
                $arr[$key]['os_axis']   = "";
                $arr[$key]['os_add']   = "";
                $arr[$key]['pd_r'] = "";
                $arr[$key]['pd_r'] = "";
                $arr[$key]['od_bd'] = "";
                $arr[$key]['od_pv'] = "";
                $arr[$key]['os_pv'] = "";
                $arr[$key]['os_bd'] = "";
                $arr[$key]['od_pv_r'] = "";
                $arr[$key]['od_bd_r'] = "";
                $arr[$key]['os_pv_r'] = "";
                $arr[$key]['os_bd_r'] = "";
            }
            //$arr[$key]['product'] = $tmp_product_options;

        }
        $result['item'] = $arr;
        dump($arr);
    }



}
