<?php

namespace app\admin\model;

use think\Model;

class NewProductAttribute extends Model
{
    // 表名
    protected $name = 'new_product_attribute';
    protected $append = ['s3_frame_images'];

    /**
     * 自定义s3获取器
     *
     * @param $value
     * @param $data
     *
     * @return string
     * @author crasphb
     * @date   2021/4/1 10:36
     */
    public function getS3FrameImagesAttr($value, $data)
    {
        return 'https://mojing.s3-us-west-2.amazonaws.com'.$data['frame_images'];
    }

}
