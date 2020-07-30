<?php

namespace app\admin\model\zendesk;

use think\Model;


class ZendeskTags extends Model
{

    

    

    // 表名
    protected $name = 'zendesk_tags';

    // 定义时间戳字段名
    protected $autoWriteTimestamp = 'timestamp';
    protected $dateFormat = 'Y-m-d H:i:s';

    // 追加属性
    protected $append = [

    ];
    /**
     * 
     *
     * @Description
     * @author lsw
     * @since 2020/03/28 10:09:00 
     * @return void
     */
    public function tags_list()
    {
        $info = $this->field('id,name')->select();
        if(!$info){
            return [];
        }
        $arr = [];
        foreach($info as $v){
            $arr[$v['id']] = $v['name'];
        }
        return $arr ? $arr : [];
    }

    







}
