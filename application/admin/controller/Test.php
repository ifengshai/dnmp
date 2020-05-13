<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\Common\model\Auth;

class Test extends Backend
{

    public function _initialize()
    {
        parent::_initialize();
        $this->newproduct = new \app\admin\model\NewProduct();
        $this->item = new \app\admin\model\itemmanage\Item();
    }
    public function test123()
    {
        echo 1111;
    }

    /**
     * 更新采购负责人
     *
     * @Description
     * @author wpl
     * @since 2020/04/29 15:43:38 
     * @return void
     */
    public function test()
    {
        $ids = [];
        $person = '';
        $str = explode('
        ', $person);
        $supplier = new \app\admin\model\purchase\Supplier();
        foreach ($ids as $k => $v) {
            $supplier->where('id', $v)->update(['purchase_person' => $str[$k]]);
        }
    }

    public function demo()
    {
        $str = 'a:2:{s:15:"info_buyRequest";a:6:{s:7:"product";s:4:"2005";s:8:"form_key";s:16:"yI5A6tFE2tVBigo0";s:3:"qty";i:1;s:7:"options";a:1:{i:1905;s:4:"2267";}s:13:"cart_currency";s:3:"USD";s:7:"tmplens";a:29:{s:19:"frame_regural_price";d:29.949999999999999;s:11:"frame_price";d:29.949999999999999;s:12:"prescription";s:247:"prescription_type=Sunglasses&od_sph=-5.50&od_cyl=-1.25&od_axis=4&os_sph=2.00&os_cyl=0.50&os_axis=3&pdcheck=&pd_r=&pd_l=&pd=58&os_add=0.00&od_add=0.00&prismcheck=&od_pv=0.00&od_bd=&od_pv_r=0.00&od_bd_r=&os_pv=0.00&os_bd=&os_pv_r=0.00&os_bd_r=&save=";s:11:"lenstype_id";s:0:"";s:13:"lenstype_name";N;s:18:"lenstype_data_name";N;s:21:"lenstype_regual_price";N;s:14:"lenstype_price";d:0;s:19:"lenstype_base_price";N;s:7:"lens_id";s:13:"refractive_75";s:9:"lens_name";s:23:"Prescription Sunglasses";s:14:"lens_data_name";s:30:"1.61 Polarized Sunglass - Gray";s:10:"lens_index";s:4:"1.61";s:17:"lens_regual_price";i:39;s:10:"lens_price";d:39;s:15:"lens_base_price";d:39;s:8:"color_id";s:13:"refractive_55";s:10:"color_name";s:9:"Dark Grey";s:15:"color_data_name";s:22:"Color Tint (Dark Grey)";s:18:"color_regual_price";i:0;s:11:"color_price";d:0;s:16:"color_base_price";d:0;s:10:"coating_id";s:9:"coating_3";s:12:"coating_name";s:25:"Super Hydrophobic Coating";s:13:"coating_price";d:9;s:18:"coating_base_price";s:4:"9.00";s:3:"rid";N;s:4:"lens";d:48;s:5:"total";d:77.950000000000003;}}s:7:"options";a:1:{i:0;a:7:{s:5:"label";s:5:"Color";s:5:"value";s:8:"Tortoise";s:11:"print_value";s:8:"Tortoise";s:9:"option_id";s:4:"1905";s:11:"option_type";s:9:"drop_down";s:12:"option_value";s:4:"2267";s:11:"custom_view";b:0;}}}';
        $str1 = 'a:2:{s:15:"info_buyRequest";a:6:{s:7:"product";s:3:"239";s:8:"form_key";s:16:"vnNM5gXQsOGyqSlv";s:3:"qty";i:1;s:7:"options";a:1:{i:135;s:3:"369";}s:13:"cart_currency";s:3:"USD";s:7:"tmplens";a:19:{s:19:"frame_regural_price";s:5:"29.95";s:11:"frame_price";s:5:"21.56";s:12:"prescription";s:33:"prescription_type=NonPrescription";s:16:"is_special_price";s:0:"";s:10:"index_type";s:10:"FRAME ONLY";s:11:"index_price";s:4:"0.00";s:10:"index_name";s:4:"1.57";s:8:"index_id";s:13:"refractive_70";s:8:"color_id";N;s:10:"color_name";N;s:10:"coating_id";N;s:13:"coatiing_name";N;s:14:"coatiing_price";N;s:9:"dyeing_id";N;s:11:"dyeing_name";N;s:12:"dyeing_price";N;s:3:"rid";s:1:"0";s:4:"lens";s:4:"0.00";s:5:"total";s:5:"21.56";}}s:7:"options";a:1:{i:0;a:7:{s:5:"label";s:5:"Color";s:5:"value";s:8:"Tortoise";s:11:"print_value";s:8:"Tortoise";s:9:"option_id";s:3:"135";s:11:"option_type";s:9:"drop_down";s:12:"option_value";s:3:"369";s:11:"custom_view";b:0;}}}';
        $arr = unserialize($str);
        dump($arr);die;
    }
}
