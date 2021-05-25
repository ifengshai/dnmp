<?php

namespace app\admin\model\platformmanage;

use think\Model;
use app\admin\library\Auth;

class MagentoPlatform extends Model
{



    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'magento_platform';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    /**
     * 禁用还是启用的状态
     */
    public function getPlatformStatus()
    {
        return [1 => '启用', 2 => '禁用'];
    }
    /***
     * 是否需要上传商品信息到平台
     */
    public function getPlatformIsUpload()
    {
        return [1 => '上传', 2 => '不上传'];
    }
    /***
     * @return array
     */
    public function getOrderPlatformList()
    {
        $result = $this->where('status', '=', 1)->field('id,name')->select();
        if (!$result) {
            return [0 => '请先添加平台'];
        }
        $arr = [];
        foreach ($result as $key => $val) {
            $arr[$val['id']] = $val['name'];
        }
        return $arr;
    }
    /**
     * 根据条件获取平台
     *
     * @Description
     * @author lsw
     * @since 2020/06/02 15:22:31 
     * @return void
     */
    public function getNewOrderPlatformList($arr)
    {
        $result = $this->where('status', '=', 1)->where('id', 'in', $arr)->field('id,name')->select();
        if (!$result) {
            return [0 => '请先添加平台'];
        }
        $arr = [];
        foreach ($result as $key => $val) {
            $arr[$val['id']] = $val['name'];
        }
        return $arr;
    }
    /**
     * 求出所有的对接平台
     */
    public function magentoPlatformList()
    {
        $where['status'] = 1;
        $where['is_del'] = 1;
        $where['is_upload_item'] = 1;
        $result = $this->where($where)->field('id,magento_account,magento_key,name')->select();
        return $result ? $result : false;
    }


    /**
     * 获取对应平台id
     */
    public function getMagentoPlatform($name = '')
    {
        $where['status'] = 1;
        $where['is_del'] = 1;
        $where['name'] = $name;
        $id = $this->where($where)->value('id');
        return $id ? $id : false;
    }

    /**
     * 获取对应平台id
     */
    public function getMagentoPrefix($id = '')
    {
        $where['status'] = 1;
        $where['is_del'] = 1;
        $where['id'] = $id;
        $name = $this->where($where)->value('prefix');
        return $name ? $name : false;
    }
    public function getNewAuthSite1()
    {
        $this->auth = Auth::instance();
        //查询对应平台
        $magentoplatformarr = $this->field('name,id')->select();
        $arr = [];
        //判断全部站点
        if($this->auth->check('dashboard/all')){
            foreach ($magentoplatformarr as $k => $v) {
                $arr[$v['id']]['name'] = $v['name'];
                $arr[$v['id']]['id'] = $v['id'];

            }
            $arr[100]['name'] ='全部';
            $arr[100]['id'] =100;

        }else{
            foreach ($magentoplatformarr as $k => $v) {
                //判断当前用户拥有的站点权限
                if (!$this->auth->check('dashboard/' . $v['name'])) {
                    unset($magentoplatformarr[$k]);
                    continue;
                }
                $arr[$v['id']]['name'] = $v['name'];
                $arr[$v['id']]['id'] = $v['id'];
            }
        }
        // dump($arr);die;
        return $arr ?? [];
    }

    /**
     * 获取站点权限
     *
     * @param  array  $only
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @return bool|\PDOStatement|string|\think\Collection
     * @author fangke
     * @date   5/17/21 12:23 PM
     */
    public function getAuthSite(array $only = [])
    {
        $this->auth = Auth::instance();
        //查询对应平台
        $magentoPlatforms = $this->field('name,id')->select();
        foreach ($magentoPlatforms as $k => $v) {
            //判断当前用户拥有的站点权限
            if (!$this->auth->check('dashboard/'.$v['name'])) {
                unset($magentoPlatforms[$k]);
            }

            if (!empty($only) && !in_array($v['name'], $only)) {
                unset($magentoPlatforms[$k]);
            }
        }
        return array_values($magentoPlatforms);
    }

    /**
     * 获取站点权限(适合下拉框列表)-公用站点方法-禁止修改
     *
     * @Author lsw 1461069578@qq.com
     * @DateTime 2020-09-29 09:37:20
     * @return void
     */
    public function getNewAuthSite()
    {
        $this->auth = Auth::instance();
        //查询对应平台
        $magentoplatformarr = $this->field('name,id')->select();
        $arr = [];
        //判断全部站点
        if($this->auth->check('dashboard/all')){
            foreach ($magentoplatformarr as $k => $v) {
                $arr[$v['id']] = $v['name'];

            }
            $arr[100] ='全部';

        }else{
            foreach ($magentoplatformarr as $k => $v) {
                //判断当前用户拥有的站点权限
                if (!$this->auth->check('dashboard/' . $v['name'])) {
                    unset($magentoplatformarr[$k]);
                    continue;
                }
                $arr[$v['id']] = $v['name'];
            }
        }
        return $arr ?? [];
    }
}
