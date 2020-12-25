<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use Think\Db;

/**
 *
 *
 * @icon fa fa-circle-o
 */
class OcPrescriptionPic extends Backend
{

    /**
     * OcPrescriptionPic模型对象
     * @var \app\admin\model\OcPrescriptionPic
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\OcPrescriptionPic;
        $this->zeelool = new \app\admin\model\order\order\Zeelool;
        $this->voogueme = new \app\admin\model\order\order\Voogueme;

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
            $WhereSql = ' id > 0';
            $filter = json_decode($this->request->get('filter'), true);
            list($where, $sort, $order,$offset,$limit) = $this->buildparams();
            if ($filter['id']){
                $WhereSql .= ' and id = '.$filter['id'];
            }
            if ($filter['status']){
                $WhereSql .= ' and status='.$filter['status'];
            }
            if ($filter['created_at']){
                $created_at = explode(' - ',$filter['created_at']);
                $WhereSql .= " and created_at between '$created_at[0]' and '$created_at[1]' ";
            }
            if ($filter['completion_time']){
                $completion_time = explode(' - ',$filter['completion_time']);
                $WhereSql .= " and completion_time between '$completion_time[0]' and '$completion_time[1]' ";
            }
            if ($filter['site']){
                if ($filter['site'] ==1){
                    $count = "SELECT COUNT(1) FROM zeelool_test.oc_prescription_pic where".$WhereSql;
                    $sql  = "SELECT * ,1 as site FROM zeelool_test.oc_prescription_pic where".$WhereSql." limit  ". $offset.','.$limit;
                }else{
                    $count = "SELECT COUNT(1) FROM vuetest_voogueme.oc_prescription_pic where".$WhereSql;
                    $sql  = "SELECT * ,2 as site FROM voogueme.oc_prescription_pic where".$WhereSql." limit  ". $offset.','.$limit;
                }
                $count = Db::query($count);
                $total = $count[0]['COUNT(1)'];
            }else{
                $count = "SELECT COUNT(1) FROM zeelool_test.oc_prescription_pic where".$WhereSql." union all  SELECT COUNT(1) FROM vuetest_voogueme.oc_prescription_pic where".$WhereSql;
                $sql  = "SELECT * ,1 as site FROM zeelool_test.oc_prescription_pic where".$WhereSql." union all  SELECT * ,2 as site FROM vuetest_voogueme.oc_prescription_pic where".$WhereSql." limit  ". $offset.','.$limit;
                $count = Db::query($count);
                $total = $count[0]['COUNT(1)']  + $count[1]['COUNT(1)'];
            }
            $list  = Db::query($sql);

            foreach ($list as $key=>$item){
                if ($item['status'] ==1){
                    $list[$key]['status']='未处理';
                }else{
                    $list[$key]['status']= '已处理';
                }
                $list[$key]['created_at'] =date("Y-m-d H:i:s",strtotime($item['created_at'])+28800);;
            }

            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    /*
 * 问题描述
 * */
    public function question_message($ids = null){
        if ($this->request->isPost()){
            $params = $this->request->post("row/a");
            $updata_queertion = $this->model->where('id',$params['id'])->update(['status'=>2,'handler_name'=>$this->auth->nickname,'completion_time'=>date('Y-m-d H:i:s',time()),'remarks'=>$params['remarks']]);
            if ($updata_queertion){
                $this->success('操作成功','oc_prescription_pic/index');
            }else{
                $this->error('操作失败');
            }
        }
        $row = $this->model->where('id',$ids)->find();
        $photo_href = $row['pic'] =explode(',',$row['pic']);
        foreach ($photo_href as $key=>$item){
            $photo_href[$key]= 'https://pc.zeelool.com/media'.$item;
        }
        $row['pic'] = $photo_href;
        $this->assign('row',$row);
        return $this->view->fetch();
    }
}
