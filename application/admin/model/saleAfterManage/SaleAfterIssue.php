<?php

namespace app\admin\model\saleAfterManage;

use think\Model;


class SaleAfterIssue extends Model
{

    

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'sale_after_issue';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    public function getLevelList()
    {
        //return config('site.level');
        return [1=>'一级问题',2=>'二级问题',3=>'三级问题'];
    }
    public function getIssueList($level,$pid=0)
    {
        if($level!=0){
            $result =$this->where('level','=',$level)->field('id,pid,name,level')->select();
            if(!$result){
                return false;
            }
            $arr = [];
            foreach ($result as $key =>$val){
                $arr[$key]['id'] = $val['id'];
                $arr[$key]['pid'] = $val['pid'];
                $arr[$key]['name'] = $val['name'];
                $arr[$key]['level'] = $val['level'];
                //下级的问题
                $arr[$key]['junior'] = $this->getIssueList(0,$val['id']);
            }
            return $arr;
        }else{
            $rs = $this->where('pid','=',$pid)->field('id,pid,name,level')->select();
            if(!$rs){
                return false;
            }
            $nextArr = [];
            foreach ($rs as $keys => $vals){
                $nextArr[$keys]['id'] = $vals['id'];
                $nextArr[$keys]['pid'] = $vals['pid'];
                $nextArr[$keys]['name'] = $vals['name'];
                $nextArr[$keys]['level'] = $vals['level'];
                //下级的问题
                $nextArr[$keys]['junior'] = $this->getIssueList(0,$vals['id']);
            }
            return $nextArr;
        }

    }
    

    







}
