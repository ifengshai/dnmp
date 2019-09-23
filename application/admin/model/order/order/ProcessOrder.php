<?php

namespace app\admin\model\order\order;

use think\Model;
use think\Db;


class ProcessOrder extends Model
{
    //数据库
    protected $connection = '';

    
    // 表名
    protected $table = 'fa_process_order';
    
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
     * 构造方法
     * @access public
     * @param array|object $data 数据
     */
    public function __construct($data = [])
    {
        $this->connection = $data['connection'];
        parent::__construct();
    }
    







}
