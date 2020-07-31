<?php

namespace app\admin\model\zendesk;

use app\admin\model\Admin;
use think\Model;


class ZendeskAgents extends Model
{





    // 表名
    protected $name = 'zendesk_agents';

    // 定义时间戳字段名
    protected $autoWriteTimestamp = 'timestamp';
    protected $dateFormat = 'Y-m-d H:i:s';

    // 追加属性
    protected $append = [
    ];
    public function getType() {
        return $this->data['type'];
    }

    /**
     * 用户关联
     * @return \think\model\relation\BelongsTo1
     */
    public function admin()

    {
        return $this->belongsTo(Admin::class,'admin_id','id')->setEagerlyType(0)->joinType('left');
    }
    public function tickets()
    {
        return $this->hasMany(Zendesk::class,'assign_id','admin_id');
    }
    public function agent()
    {
        return $this->belongsTo(ZendeskAccount::class,'agent_id','account_id');
    }

}
