<?php

namespace app\admin\model\platformManage;

use think\Model;


class ManagtoPlatform extends Model
{

    

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'managto_platform';
    
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
     * 禁用还是启用的状态
     */
    public function getPlatformStatus()
    {
        return [1=>'启用',2=>'禁用'];
    }
    /***
     * 是否需要上传商品信息到平台
     */
    public function getPlatformIsUpload()
    {
        return [1=>'上传',2=>'不上传'];
    }
    /***
     * @return array
     */
    public function getOrderPlatformList()
    {
        $result = $this->where('status','=',1)->field('id,name')->select();
        if(!$result){
            return [0=>'请先添加平台'];
        }
        $arr = [];
        foreach($result as $key=>$val){
            $arr[$val['id']] = $val['name'];
        }
        return $arr;
    }

    /**
     * 求出所有的对接平台
     */
    public function managtoPlatformList()
    {
      $where['status'] = 1;
      $where['is_del'] = 1;
      $where['is_upload_item'] = 1;
      $result = $this->where($where)->field('id,managto_account,managto_key,name')->select();
      return $result ? $result : false;
    }


    /**
     * 获取对应平台id
     */
    public function getManagtoPlatform($name = '')
    {
      $where['status'] = 1;
      $where['is_del'] = 1;
      $where['name'] = $name;
      $id = $this->where($where)->value('id');
      return $id ? $id : false;
    }









}
