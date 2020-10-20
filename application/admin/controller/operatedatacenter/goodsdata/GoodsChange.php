<?php

namespace app\admin\controller\operatedatacenter\GoodsData;

use app\common\controller\Backend;
use think\Controller;
use think\Request;

class GoodsChange extends Backend
{
    public function _initialize()
    {
        parent::_initialize();
        $this->zeeloolOperate = new \app\admin\model\operatedatacenter\Zeelool;
        $this->vooguemeOperate = new \app\admin\model\operatedatacenter\Voogueme();
        $this->nihaoOperate = new \app\admin\model\operatedatacenter\Nihao();
    }
    public function index()
    {

        set_time_limit (0);
        $_item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $sku_data = $_item_platform_sku
            ->field('sku,grade,platform_sku,outer_sku_status')
            ->where(['platform_type' => 1,'outer_sku_status'=>1])
            ->select();
        $sku_data = collection($sku_data)->toArray();
        $ga_skus = $this->zeeloolOperate->google_sku_detail(1,'2020-10-13');
        $ga_skus = array_column($ga_skus,'uniquePageviews','ga:pagePath');
        // dump($sku_data);
        // dump($ga_skus);
        foreach ($sku_data as $k=>$v){
            foreach ($ga_skus as $kk=>$vv){
                if(strpos($kk,$v['sku']) != false){
                    if ($arr[$v['sku']]){
                        $arr[$v['sku']] +=$vv;
                    }else{
                        $arr[$v['sku']] = $vv;
                    }

                }
            }
        }
        // dump($skus);die;
        dump($arr);die;

        $sku_arr = array_column($sku_data, 'sku');
        $platform = [];
        $grade = [];
        foreach($sku_data as $value){
            $grade[$value['sku']] = $value['grade'];
            $platform[$value['sku']] = $value['platform_sku'];
        }
        return $this->view->fetch();
    }

    public function sku_change()
    {

    }

}
