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

            $filter = json_decode($this->request->get('filter'), true);
//            $site = $filter['site'] ? $filter['site'] :1;
            $site = $filter['site'];
            if ($site ==1){
                $model = Db::connect('database.db_zeelool');
            }elseif($site ==2){
                $model = Db::connect('database.db_voogueme');
            }else{
                $sql  = 'SELECT * ';
            }
            unset($filter['site']);
            $this->request->get(['filter' => json_encode($filter)]);

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $model->table('oc_prescription_pic')->where($where)->count();
            $list = $model->table('oc_prescription_pic')->where($where)->order('id desc')->limit($offset, $limit)->select();

            foreach ($list as $key=>$item){

                if ($item['status'] ==1){
                    $list[$key]['status']='未处理';
                }else{
                    $list[$key]['status']= '已处理';
                }
                $list[$key]['site'] = $site;
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
            if ($params['site'] ==1){
                $model = Db::connect('database.db_zeelool');
            }else{
                $model = Db::connect('database.db_voogueme');
            }
            $updata_queertion =$model->table('oc_prescription_pic')->where('id',$params['id'])->update(['status'=>2,'handler_name'=>$this->auth->nickname,'completion_time'=>date('Y-m-d H:i:s',time()),'remarks'=>$params['remarks']]);
            if ($updata_queertion){
                $this->success('操作成功','oc_prescription_pic/index');
            }else{
                $this->error('操作失败');
            }
        }
        $site = input('param.site');
        if ($site ==1){
            $model = Db::connect('database.db_zeelool');
            $url =config('url.zeelool_url').'/media';
        }else{
            $model = Db::connect('database.db_voogueme');
            $url =config('url.voogueme_url').'/media';
        }
        $row =$model->table('oc_prescription_pic')->where('id',$ids)->find();
        $photo_href = $row['pic'] =explode(',',$row['pic']);
        foreach ($photo_href as $key=>$item){
            $photo_href[$key]= $url.$item;
        }
        $row['pic'] = $photo_href;
        $this->assign('row',$row);
        $this->assign('zhandian',$site);


        return $this->view->fetch();
    }
}
