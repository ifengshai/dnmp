<?php
/**
 * @Author: CrashpHb彬
 * @Date: 2020/4/17 17:02
 * @Email: 646054215@qq.com
 */

namespace app\admin\model;


use think\Model;

class Department extends Model
{
    // 表名
    protected $name = 'department';

    // 定义时间戳字段名
    protected $autoWriteTimestamp = 'datetime';
    protected $dateFormat = 'Y-m-d H:i:s';
    /**
     * 钉钉新增部门
     * @param $department
     * @return mixed
     */
    public static function deptAdd($department)
    {
        $pid = self::where('department_id',$department['parentid'])->value('id');
        if($pid){
            $data = [
                'name' => $department['name'],
                'pid' => $pid,
                'department_id' => $department['id'],
                'parentid' => $department['parentid'],
            ];
            $authGroup = self::create($data);
            return $authGroup->id;
        }
    }

    /**
     * 钉钉部门修改同步
     * @param $department
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function deptUpdate($department)
    {
        $preDepartment = self::where('department_id',$department['department_id'])->find();
        //名称修改
        if($preDepartment['name'] != $department['name']){
            self::where('id',$preDepartment['id'])->setField('name',$department['name']);
        }
        //pid修改
        if($preDepartment['parentid'] != $department['parentid']){
            //获取新的pid
            $pid = self::where('department_id',$department['parentid'])->value('id');
            self::update([
                'pid' => $pid,
                'parentid' => $department['parentid']
            ],['id' => $preDepartment['id']]);
        }
        return $preDepartment['id'];
    }

    /**
     * 钉钉删除部门
     * @param $deptId
     * @return mixed
     */
    public static function deptDelete($deptId)
    {
        $group_id = self::where('department_id',$deptId)->value('id');
        //删除角色
        self::where('id',$group_id)->delete();
        return $group_id;
    }
}