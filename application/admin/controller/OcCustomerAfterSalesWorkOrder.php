<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use Think\Db;
use think\Request;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class OcCustomerAfterSalesWorkOrder extends Backend
{
    
    /**
     * OcCustomerAfterSalesWorkOrder模型对象
     * @var \app\common\model\OcCustomerAfterSalesWorkOrder
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\OcCustomerAfterSalesWorkOrder;

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    

    /**
     * 查看
     */
    public function index()
    {
        //当前是否为关联查询
        $this->relationSearch = false;
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }

            $filter = json_decode($this->request->get('filter'), true);
            unset($filter['site']);
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $row) {
                $row->visible(['id','email','increment_id','order_type','problem_type','concrete_problem','status','created_at','completed_at','handler_name']);
                
            }
            $list = collection($list)->toArray();
            //查询订单是否存在工单
            $workorder = new \app\admin\model\saleaftermanage\WorkOrderList();

            foreach ($list as $key=>$item){
                $list[$key]['site'] = 'zeelool';
                if ($item['order_type']  ==1){
                    $list[$key]['order_type'] = '普通订单';
                }elseif ($item['order_type'] ==2){
                    $list[$key]['order_type'] = '批发';
                }elseif ($item['order_type'] ==3){
                    $list[$key]['order_type'] = '网红';
                }else{
                    $list[$key]['order_type'] = '补发';
                }
                if ($item['status'] ==1){
                    $list[$key]['status'] = 'Submitted';
                }elseif ($item['status'] ==2){
                    $list[$key]['status'] = 'Processing';
                }elseif($item['status'] ==3){
                    $list[$key]['status'] = 'Completed';
                }else{
                    $list[$key]['status'] = '待处理';
                }

                $swhere['platform_order'] = $item['increment_id'];
                $swhere['work_platform'] = 1;
                $swhere['work_status'] = ['not in', [0, 4, 6]];
                $count = $workorder->where($swhere)->count();
                if ($count>1){
                    $list[$key]['task_info'] = 1;
                }
            }

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     *问题详情
     */
    public function question_detail($ids = null){
        if ($_POST){
            $params = $this->request->post("row/a");

            $where['id'] = $params['ids'];
            $save_question = $this->model->isUpdate(true, $where)->save(['status'=>$params['pm_audit_status'],'completed_at'=>date('Y-m-d H:i:s',time()),'handler_name'=>$this->auth->nickname]);
            if ($save_question){
                //如果更新成功  提交接口
                $url  = 'https://z.zhaokuangyi.com/magic/customer/updateTicket';
                $value['ticket_id'] = $params['ids'];
                $value['status'] = $params['pm_audit_status'];
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); //在HTTP请求中包含一个"User-Agent: "头的字符串。
                curl_setopt($curl, CURLOPT_HEADER, 0); //启用时会将头文件的信息作为数据流输出。
                curl_setopt($curl, CURLOPT_POST, true); //发送一个常规的Post请求
                curl_setopt($curl, CURLOPT_POSTFIELDS, $value);//Post提交的数据包
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); //启用时会将服务器服务器返回的"Location: "放在header中递归的返回给服务器，使用CURLOPT_MAXREDIRS可以限定递归返回的数量。
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //文件流形式
                curl_setopt($curl, CURLOPT_TIMEOUT, 20); //设置cURL允许执行的最长秒数。
                $content =json_decode(curl_exec($curl),true);
                curl_close($curl);
                if ($content['code'] ==200){
                    $this->success('操作成功');
                }else{
                    $this->error('工单信息更新失败');
                }
            }else{
                $this->error('操作失败');
            }
        }
        $row = Db::table('zeelool_test.oc_customer_after_sales_work_order oc')
            ->join("fa_zendesk ze",'ze.email = oc.email','left')
            ->join("fa_admin ad",'ze.due_id = ad.id','left')
            ->field('oc.*,ze.id as ze_id,ze.ticket_id,ze.subject,ze.to_email,ze.due_id,ze.create_time,ze.update_time,ze.status as ze_status,ad.nickname')
            ->where('oc.id',$ids)
            ->select();

        foreach ($row as $key=>$item){
            $data['id'] = $item['id'];
            $data['email'] = $item['email'];
            $data['status'] = $item['status'];
            $data['increment_id'] = $item['increment_id'];
            $data['customer_id'] = $item['customer_id'];
            $data['problem_type'] = $item['problem_type'];
            $data['concrete_problem'] = $item['concrete_problem'];
            $data['order_type'] = $item['order_type'];
            $data['good_skus'] = $item['good_skus'];
            $data['images'] = explode(',',$item['images']);
            $data['description'] = $item['description'];

            if ($item['ze_status'] == 1){
                $data['email_message'][$key]['ze_status'] = 'new';
            }elseif ($item['ze_status'] ==2){
                $data['email_message'][$key]['ze_status'] = 'open';
            }elseif ($item['ze_status'] ==3){
                $data['email_message'][$key]['ze_status'] = 'pending';
            }elseif ($item['ze_status'] ==4){
                $data['email_message'][$key]['ze_status'] = 'solved';
            }else{
                $data['email_message'][$key]['ze_status'] = 'other';
            }
            $data['email_message'][$key]['ze_id'] = $item['ze_id'];
            $data['email_message'][$key]['ticket_id'] = $item['ticket_id'];
            $data['email_message'][$key]['subject'] = $item['subject'];
            $data['email_message'][$key]['to_email'] = $item['to_email'];
            $data['email_message'][$key]['nickname'] = $item['nickname'];
            $data['email_message'][$key]['create_time'] = $item['create_time'];
            $data['email_message'][$key]['update_time'] = $item['update_time'];


        }
        $this->assign('row',$data);
        return $this->view->fetch();
    }


}
