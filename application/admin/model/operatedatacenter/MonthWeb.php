<?php

namespace app\admin\model\operatedatacenter;

use think\Db;
use think\Model;

/**
 * Class Repurchase
 * 各站点每月数据
 * @package app\admin\model\operatedatacenter
 * @author mjj
 * @date   2021/4/2 14:10:13
 */
class MonthWeb extends Model
{

    // 表名
    protected $name = 'datacenter_supply_month_web';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';


    // 追加属性
    protected $append = [

    ];

    /**
     *
     * @param $type
     * @author mjj
     * @date   2021/4/2 14:10:00
     */
    /**
     * 获取各站点的新老用户数据
     * @param $site  站点
     * @param string $flag   标识：区别查询条数，传true查12条数据，即一年数据，默认查全部数据
     * @author mjj
     * @date   2021/4/2 14:14:14
     */
    public function getOldNewUserData($site,$flag = false){
        $where['site'] = $site;
        if($flag){
            $list = $this
                ->where($where)
                ->limit(12)
                ->order('day_date desc')
                ->select();
        }else{
            $list = $this
                ->where($where)
                ->order('day_date desc')
                ->select();
        }
        return $list;
    }
}
