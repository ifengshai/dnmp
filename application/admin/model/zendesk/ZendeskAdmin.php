<?php
/**
 * Class ${FILE_NAME}
 * @package app\admin\model\zendesk
 * @author miaojingjing
 * @date   2021/6/21 16:26:16
 */

namespace app\admin\model\zendesk;

use app\admin\model\Admin;
use think\Model;

class ZendeskAdmin extends Model
{

    // 表名
    protected $name = 'zendesk_admin';

    // 定义时间戳字段名
    protected $autoWriteTimestamp = 'timestamp';
    protected $dateFormat = 'Y-m-d H:i:s';

    // 追加属性
    protected $append = [
    ];

    /**
     * 用户关联
     * @return mixed
     * @author miaojingjing
     * @date   2021/6/21 16:27:05
     */
    public function admin()

    {
        return $this->belongsTo(Admin::class,'admin_id','id')->setEagerlyType(0)->joinType('left');
    }
}