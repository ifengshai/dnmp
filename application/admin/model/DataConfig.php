<?php

namespace app\admin\model;

use think\Model;

class DataConfig extends Model
{
    // 表名
    protected $name = 'data_config';

    /**
     * 查询对应字段数据
     *
     * @Description
     * @author wpl
     * @since 2020/03/20 16:27:53 
     * @param [type] $key 对应key
     * @return void
     */
    public function getValue($key)
    {
       return $this->where('key', $key)->value('value');
    }
}
