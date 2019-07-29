<?php

namespace app\admin\model\itemmanage;

use think\Model;


class ItemCategory extends Model
{

    

    

    // 表名
    protected $name = 'item_category';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];

    /**
     * 选择上架还是下架
     * @return array
     */
    public function isPutAway()
    {
        return [1=>'上架',0=>'下架'];
    }
    public function getLevelList()
    {
        //return config('site.level');
        return [1=>'一级分类',2=>'二级分类',3=>'三级分类'];
    }

    /***
     * 获取分类列表
     */
    public function getCategoryList()
    {
        $result = $this->field('id,pid,name')->select();
        if(!$result){
            return false;
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
     * 根据ajax任务获取列表
     */
    public function getAjaxCategoryList()
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
     * 获取分类列表以及当前任务的下级任务
     * @param $level
     * @param int $pid
     * @return array|bool
     */
    public function getList($pid)
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
     * 根据id获取下级分类的id
     * @param $id
     * @return array|string
     */
    public function getLowerCategory($id)
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
