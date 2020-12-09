<?php

namespace app\admin\controller\operatedatacenter\userdata;

use app\admin\model\platformManage\MagentoPlatform;
use app\common\controller\Backend;
use think\Cache;
use think\Db;
use think\Request;

class UserDataViewVip extends Backend
{

    public function _initialize()
    {
        parent::_initialize();

        $this->zeelool = new \app\admin\model\order\order\Zeelool();
        $this->voogueme = new \app\admin\model\order\order\Voogueme();
        $this->nihao = new \app\admin\model\order\order\Nihao();
        $this->zeeloolOperate = new \app\admin\model\operatedatacenter\Zeelool;
        $this->vooguemeOperate = new \app\admin\model\operatedatacenter\Voogueme();
        $this->nihaoOperate = new \app\admin\model\operatedatacenter\Nihao();
        $this->datacenterday = new \app\admin\model\operatedatacenter\Datacenter();
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
    }
    /**
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/10/13
     * Time: 15:02:03
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
            $filter = json_decode($this->request->get('filter'), true);
            if($filter['order_platform'] == 2){
                $order_model = $this->voogueme;
                $web_model = Db::connect('database.db_voogueme');
            }elseif($filter['order_platform'] == 3){
                $order_model = $this->nihao;
                $web_model = Db::connect('database.db_nihao');
            }else{
                $order_model = $this->zeelool;
                $web_model = Db::connect('database.db_zeelool');
            }

            $map['order_status'] = 'success';

            unset($filter['one_time-operate']);
            unset($filter['time_str']);
            unset($filter['time_str2']);
            unset($filter['order_platform']);
            $this->request->get(['filter' => json_encode($filter)]);
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $web_model
                ->table('oc_vip_order')
                ->where($where)
                ->where($map)
                ->count();
            $list = $web_model
                ->table('oc_vip_order')
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->field('customer_id,customer_email,start_time,end_time')
                ->select();
            $list = collection($list)->toArray();
            foreach ($list as $key=>$value){
                $list[$key]['customer_id'] = $value['customer_id'];  //用户id
                $list[$key]['customer_email'] = $value['customer_email'];          //注册邮箱
                $list[$key]['start_time'] = $value['start_time'];  //VIP开始时间
                $list[$key]['end_time'] = $value['end_time'];  //VIP结束时间
                $end_time = strtotime($value['end_time']);
                $now_time = time();
                if($now_time>$end_time){
                    $list[$key]['rest_days'] = 0;
                }else{
                    $list[$key]['rest_days'] = ceil(($end_time-$now_time)/60/60/24);
                }
                $order_where['customer_id'] = $value['customer_id'];
                $order_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
                $order_where['order_type'] = 1;
                $order_time_where['created_at'] = ['between',[$value['start_time'],$value['end_time']]];
                $list[$key]['vip_order_num'] = $order_model->where($order_where)->where($order_time_where)->count();  //VIP期间支付订单数
                $list[$key]['vip_order_amount'] = $order_model->where($order_where)->where($order_time_where)->sum('base_grand_total');//VIP期间支付金额
                $order_amount = $order_model->where($order_where)->sum('base_grand_total');  //总订单金额
                $order_num = $order_model->where($order_where)->count();  //总订单数
                $list[$key]['avg_order_amount'] = $order_num ? round($order_amount/$order_num,2) : 0;
                $list[$key]['order_num'] = $order_num;

            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key=>$val){
            if(!in_array($val['name'],['zeelool','voogueme'])){
                unset($magentoplatformarr[$key]);
            }
        }
        $this->view->assign(compact('magentoplatformarr'));
        return $this->view->fetch();

    }

    /**
     * ajax获取上半部分数据
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/10/13
     * Time: 13:42:57
     */
    public function ajax_top_data()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            //站点
            $order_platform = $params['order_platform'] ? $params['order_platform'] : 1;
            if($order_platform == 2){
                $model = $this->vooguemeOperate;
                $web_model = Db::connect('database.db_voogueme');
            }elseif($order_platform == 3){
                $model = $this->nihaoOperate;
                $web_model = Db::connect('database.db_nihao');
            }else{
                $model = $this->zeeloolOperate;
                $web_model = Db::connect('database.db_zeelool');
            }
            $web_model->table('oc_vip_order')->query("set time_zone='+8:00'");
            $map['order_status'] = 'success';
            //新增VIP会员数
            $vip_num = $model->getVipUser($params['time_str'],$params['time_str2']);
            //复购VIP会员数
            $again_user_num['again_user_num'] = $model->get_again_user_vip($params['time_str']);
            if($params['time_str2']){
                $contrast_again_user_num = $model->get_again_user_vip($params['time_str2']);
                $again_user_num['contrast_again_user_num'] = $contrast_again_user_num ? round(($again_user_num['again_user_num']-$contrast_again_user_num)/$contrast_again_user_num*100,2) : '0';
            }
            //总VIP会员数
            $sum_vip_num = $web_model->table('customer_entity')->where('is_vip',1)->count();

            $data = compact('vip_num', 'again_user_num', 'sum_vip_num');
            $this->success('', '', $data);
        }
    }
    public function export(){
        set_time_limit(0);
        header ( "Content-type:application/vnd.ms-excel" );
        header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", date('Y-m-d-His',time()) ) . ".csv" );//导出文件名

        // 打开PHP文件句柄，php://output 表示直接输出到浏览器
        $fp = fopen('php://output', 'a');
        $order_platform = input('order_platform');

        // 将中文标题转换编码，否则乱码
        $field_arr = array(
            '用户ID','注册邮箱','VIP开始时间','VIP结束时间','VIP剩余天数','VIP期间订单数','VIP期间订单金额','平均订单金额','总订单数'
        );
        foreach ($field_arr as $i => $v) {
            $field_arr[$i] = iconv('utf-8', 'GB18030', $v);
        }
        // 将标题名称通过fputcsv写到文件句柄
        fputcsv($fp, $field_arr);

        if($order_platform == 2){
            $order_model = $this->voogueme;
            $web_model = Db::connect('database.db_voogueme');
        }elseif($order_platform == 3){
            $order_model = $this->nihao;
            $web_model = Db::connect('database.db_nihao');
        }else{
            $order_model = $this->zeelool;
            $web_model = Db::connect('database.db_zeelool');
        }
        $web_model->table('oc_vip_order')->query("set time_zone='+8:00'");
        $map['order_status'] = 'success';
        $total_export_count = $web_model
            ->table('oc_vip_order')
            ->where($map)
            ->count();
        $pre_count = 5000;
        for ($i=0;$i<intval($total_export_count/$pre_count)+1;$i++){
            $start = $i*$pre_count;
            //切割每份数据
            $list = $web_model
                ->table('oc_vip_order')
                ->where($map)
                ->field('customer_id,customer_email,start_time,end_time')
                ->order('id desc')
                ->limit($start,$pre_count)
                ->select();
            $list = collection($list)->toArray();
            //整理数据
            foreach ( $list as &$val ) {
                $tmpRow = [];
                $tmpRow['customer_id'] =$val['customer_id'];//用户ID
                $tmpRow['customer_email'] =$val['customer_email'];//注册邮箱
                $tmpRow['start_time'] =$val['start_time'];//VIP开始时间
                $tmpRow['end_time'] =$val['end_time'];//VIP结束时间
                //VIP剩余天数
                $end_time = strtotime($val['end_time']);
                $now_time = time();
                if($now_time>$end_time){
                    $tmpRow['rest_days'] = 0;
                }else{
                    $tmpRow['rest_days'] = ceil(($end_time-$now_time)/60/60/24);
                }
                //VIP期间支付订单数
                $order_where['customer_id'] = $val['customer_id'];
                $order_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
                $order_where['order_type'] = 1;
                $order_time_where['created_at'] = ['between',[$val['start_time'],$val['end_time']]];
                $tmpRow['vip_order_num'] = $order_model->where($order_where)->where($order_time_where)->count();
                $tmpRow['vip_order_amount'] = $order_model->where($order_where)->where($order_time_where)->sum('base_grand_total');//VIP期间支付金额
                $order_amount = $order_model->where($order_where)->sum('base_grand_total');  //总订单金额
                $order_num = $order_model->where($order_where)->count();  //总订单数
                $tmpRow['avg_order_amount'] = $order_num ? round($order_amount/$order_num,2) : 0;
                $tmpRow['order_num'] = $order_num;

                $rows = array();
                foreach ( $tmpRow as $export_obj){
                    $rows[] = iconv('utf-8', 'GB18030', $export_obj);
                }
                fputcsv($fp, $rows);
            }
            // 将已经写到csv中的数据存储变量销毁，释放内存占用
            unset($list);
            ob_flush();
            flush();
        }
        fclose($fp);
    }
}
