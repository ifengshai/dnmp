<?php

namespace app\admin\model\saleaftermanage;

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

    /***
     * 获取问题列表以及当前任务的下级任务
     * @param $level
     * @param int $pid
     * @return array|bool
     */
    public function getIssueList($level,$pid=0)
    {
        if($level!=0){
            $where['is_del'] = 1;
            $result =$this->where('level','=',$level)->where($where)->field('id,pid,name,level')->select();
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
            $where['is_del'] = 1;
            $rs = $this->where('pid','=',$pid)->where($where)->field('id,pid,name,level')->select();
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
     * 根据ajax任务获取列表
     */
    public function getAjaxIssueList()
    {
        $result = $this->field('id,name')->select();
        if(!$result){
            return [0=>'问题不存在请添加问题'];
        }
        $arr = [];
        foreach($result as $key=>$val){
            $arr[$val['id']] = $val['name'];
        }
        return $arr;
    }

    /***
     * 问题列表
     * @return array|bool
     */
    public function issueList()
    {
        $result = $this->where(['is_del'=>1])->field('id,pid,name')->select();
        if(!$result){
            $finalArr =[];
            $finalArr[0] = '无';
            return $finalArr;
        }
        $arr    = getTree($result);
        $finalArr = [];
        foreach ($arr as $k=>$v){
            $finalArr[0] = '无';
            $finalArr[$v['id']] = $v['name'];
        }
        return $finalArr;
    }

    /***
     * 获取下级问题id
     * @param int $pid
     * @return array|bool
     */
    public function getList($pid=0)
    {
        $rs = $this->where('pid','in',$pid)->field('id')->select();
        if(!$rs){
            return false;
        }
        static $arr = [];
        foreach ($rs as $k =>$v){
            $arr[] = $v['id'];
            $this->getList($v['id']);
        }
        return $arr;
    }
    /***
     * 获取下级的问题
     */
    public function getLowerIssue($id)
    {
        $ids = $this->getList($id);
        if($ids){
            $strIds = implode(',',$ids);
        }else{
            $strIds = '';
        }
        return $strIds;
    }



}
