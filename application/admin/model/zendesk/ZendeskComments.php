<?php

namespace app\admin\model\zendesk;

use app\admin\model\zendesk\ZendeskAgents;
use think\Db;
use think\Model;


class ZendeskComments extends Model
{
    // 表名
    protected $name = 'zendesk_comments';

    // 定义时间戳字段名
    protected $autoWriteTimestamp = 'timestamp';
    protected $dateFormat = 'Y-m-d H:i:s';

    // 追加属性
    protected $append = [

    ];
    public function agent()
    {
        return $this->hasOne(ZendeskAgents::class,'admin_id','due_id');
    }
    /*
      * 统计处理量
      * */
    public function dealnum_statistical($platform = 0,$time_str = '',$group_id = 0,$admin_id = 0){
        if($platform){
            $where['c.platform'] = $platform;
        }
        $where['c.due_id'] = ['neq',0];
        if($time_str){
            $createat = explode(' ', $time_str);
            $where['c.create_time'] = ['between', [$createat[0] . ' ' . $createat[1], $createat[3]  . ' ' . $createat[4]]];
        }else{
            $start = date('Y-m-d', strtotime('-6 day'));
            $end   = date('Y-m-d 23:59:59');
            $where['c.create_time'] = ['between', [$start,$end]];
        }
        if($group_id){
            //查询客服类型
            $group_admin_id = Db::name('admin')->where(['group_id'=>$group_id])->column('id');
            $where['c.due_id'] = array('in',$group_admin_id);
        }
        if($admin_id){
            $where['c.due_id'] = $admin_id;
        }
        $where['c.is_admin'] = 1;
        $where['c.is_public'] = ['neq',2];
        $where['z.channel'] = ['neq','voice'];
        $count = $this->alias('c')->join('fa_zendesk z','c.zid=z.id')->where($where)->count();
        return $count;
    }

    /**计算客服评价数量以及好评率
     * @param $platform 平台
     * @param $time_str1 筛选时间
     * @param $all_service 所有的客服人员
     * @author liushiwei
     * @date   2021/9/15 10:26
     */
    public function dealnum_estimate($platform,$time_str ='',$all_service)
    {
        //平台
        if($platform){
            $where['type'] = $platform;
        }
        //筛选时间
        if($time_str){
            $createat = explode(' ', $time_str);
            $where['zendesk_update_time'] = ['between', [$createat[0] . ' ' . $createat[1], $createat[3]  . ' ' . $createat[4]]];
        }else{
            $start = date('Y-m-d', strtotime('-6 day'));
            $end   = date('Y-m-d 23:59:59');
            $where['zendesk_update_time'] = ['between', [$start,$end]];
        }
        //关闭状态
        $where['status'] = 5;
        $where['rating_type'] = ['gt',0];
        //先求出所有的邮件
        $result = Db::name('zendesk')->where($where)->field('id,type,email,assign_id,rating_type')->select();
        $arr = [];
        //所有客服人员的好评数量以及好评率默认都是0
        foreach($all_service as $value){
            $arr[$value]['estimate'] = 0; //评价数量
            $arr[$value]['goods_estimate_num'] = 0; //好评数量
            $arr[$value]['goods_estimate_rate'] = 0; //好评率
        }
        if(!$result){
            return $arr;
        }
        switch ($platform) {
            case 1:
                $webModel = Db::connect('database.db_zeelool');
                break;
            case 2:
                $webModel = Db::connect('database.db_voogueme');
                break;
            case 3:
                $webModel = Db::connect('database.db_nihao');
                break;
        }
        //求出所有的vip客服
        $vip_customer_service = Db::name('zendesk_admin')->where(['group'=>1])->column('admin_id');
        foreach($result as $k =>$v){
            if($platform != 3){
                $group = $webModel->table('customer_entity')->where('email', $v['email'])->value('group_id');
                if($group!=4){
                    $is_vip_email = 0; //不是vip邮件
                }else{
                    $is_vip_email = 1; //是vip邮件
                }
            }else{
                $group = $webModel->table('users')->where('email', $v['email'])->value('group');
                if($group!=2){
                    $is_vip_email = 0;
                }else{
                    $is_vip_email = 1;
                }
            }
            $where_comment['is_admin'] = 1;
            $where_comment['is_public'] = ['neq',2];
            $where_comment['zid'] = $v['id'];
            $where_comment['due_id'] = ['in',$vip_customer_service];
            $where_comment['due_id'] = ['gt',0];
            //如果是vip邮件
            if($is_vip_email == 1){
                //如果第一承接人存在的话，承接人就是当前客服
                if($v['assign_id']){
                    if($v['rating_type']>0){
                        $arr[$v['assign_id']]['estimate'] +=1;
                    }
                    if($v['rating_type'] == 1){
                        $arr[$v['assign_id']]['goods_estimate_num']+=1;
                    }
                }else{ //如果第一承接人是空的话,处理人中vip客服处理时间最早的客服是当前客服
                    $handle_comment_result = $this->where($where_comment)->field('id,due_id')->order('create_time')->find();
                    if($handle_comment_result){
                        $arr[$handle_comment_result['due_id']]['estimate'] +=1;
                        if($v['rating_type'] == 1){
                            $arr[$handle_comment_result['due_id']]['goods_estimate_num']+=1;
                        }

                    }
                }


            }else{ //非vip邮件且邮件处理人不包含vip客服时，统计：邮件第一承接人是当前客服
                $vip_comment_result = $this->where($where_comment)->field('id,due_id')->order('create_time')->find();
                //dump($vip_comment_result);
                if(!$vip_comment_result && !empty($v['assign_id'])){
                        $arr[$v['assign_id']]['estimate'] +=1;
                    if($v['rating_type'] == 1){
                        $arr[$v['assign_id']]['goods_estimate_num']+=1;
                    }
                }elseif (!$vip_comment_result && empty($v['assign_id'])){
                    //非vip邮件第一承接人为空且邮件处理人不包含vip客服时，处理人中非vip客服处理时间最早的客服是当前客服
                    $general_comment['is_admin'] = 1;
                    $general_comment['is_public'] = ['neq',2];
                    $general_comment['zid'] = $v['id'];
                    $general_comment['due_id'] = ['not in',$vip_customer_service];
                    $general_comment['due_id'] =['gt',0];
                    $general_comment_result = $this->where($general_comment)->field('id,due_id')->order('create_time')->find();
                    if($general_comment_result){
                        $arr[$general_comment_result['due_id']]['estimate'] +=1;
                        if($v['rating_type'] == 1){
                            $arr[$general_comment_result['due_id']]['goods_estimate_num'] +=1;
                        }
                    }
                }
            }

        }
        $final_arr = [];
        foreach($arr as $key=> $val){
            $final_arr[$key]['estimate'] = $val['estimate'];
            $final_arr[$key]['goods_estimate_num'] = $val['goods_estimate_num'];
            if($val['estimate']>0){
                $final_arr[$key]['goods_estimate_rate'] = round($val['goods_estimate_num']*100/$val['estimate'],2);
            }else{
                $final_arr[$key]['goods_estimate_rate'] = 0;
            }

        }
        return $final_arr;
    }

    

    







}
