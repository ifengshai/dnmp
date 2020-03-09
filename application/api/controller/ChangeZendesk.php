<?php
/**
 * @Author: CrashpHb彬
 * @Date: 2020/3/9 10:27
 * @Email: 646054215@qq.com
 */

namespace app\api\controller;


use app\admin\controller\zendesk\ZendeskReplyDetail;
use think\Controller;
use app\admin\model\zendesk\ZendeskReplyDetail as Detail;

//修改zendesk_detail数据库之前的数据
class ChangeZendesk extends Controller
{
    public function index()
    {
        $detail = Detail::all();
        foreach($detail as $key => $val){
            //dump(strrpos($val->body,'Hi there'));die;
            //echo $val->body;die;
            if(strrpos($val->body,'Hi there') !== false){
                Detail::where('id',$val->id)->setField('is_admin',1);
                //dump($res);die;
            }
            if(!$val->body){
                Detail::where('id',$val->id)->setField('is_admin',1);
                //dump($res);die;
            }
        }
    }

}