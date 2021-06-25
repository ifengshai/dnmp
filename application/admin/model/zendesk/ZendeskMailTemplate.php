<?php

namespace app\admin\model\zendesk;

use think\Model;
use traits\model\SoftDelete;


class ZendeskMailTemplate extends Model
{
    use SoftDelete;
    // 表名
    protected $name = 'zendesk_mail_template';

    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'datetime';

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = 'delete_time';
    protected $dateFormat = 'Y-m-d H:i:s';

    // 追加属性
    protected $append = [
        'used_time_text'
    ];




    public function getUsedTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['used_time']) ? $data['used_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setUsedTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function template_list()
    {
        $info = $this->field('id,template_description')->select();
        if(!$info){
            return [];
        }
        $arr = [];
        foreach($info as $v){
            $arr[$v['id']] = $v['template_description'];
        }
        return $arr ? $arr : [];
    }

}
