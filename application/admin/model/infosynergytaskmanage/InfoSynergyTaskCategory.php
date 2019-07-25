<?php

namespace app\admin\model\infosynergytaskmanage;

use think\Model;


class InfoSynergyTaskCategory extends Model
{

    

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'info_synergy_task_category';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    public function taskCategory()
    {
        return [1=>'一级分类',2=>'二级分类',3=>'三级分类'];
    }

    /***
     * 获取问题列表以及当前任务的下级任务
     * @param $level
     * @param int $pid
     * @return array|bool
     */
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

    /***
     * 获取信息协同任务分类列表
     */
    public function getSynergyTaskCategoryList()
    {
        $result = $this->field('id,name')->select();
        if(!$result){
            return [0=>'问题不存在请添加问题'];
        }
        foreach($result as $key=>$val){
            $result[$val['id']] = $val['name'];
        }
        return $result;
    }





}
