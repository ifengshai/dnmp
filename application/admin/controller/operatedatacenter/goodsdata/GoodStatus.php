<?php

namespace app\admin\controller\operatedatacenter\goodsdata;

use app\admin\model\itemmanage\ItemPlatformSku;
use app\common\controller\Backend;
use think\Controller;
use think\Db;
use think\Request;

class GoodStatus extends Backend
{
    public function _initialize()
    {
        parent::_initialize();
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
        $this->item_platform = new ItemPlatformSku();
    }

    public function index()
    {
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key=>$val){
            if(!in_array($val['name'],['zeelool','voogueme','nihao'])){
                unset($magentoplatformarr[$key]);
            }
        }
        $this->view->assign(compact('magentoplatformarr'));
        return $this->view->fetch();
    }
    /**
     * 商品状态分析柱状图
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/11/18
     * Time: 15:08:36
     */
    public function ajax_histogram(){

        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $order_platform = $params['order_platform'];
            $json['xColumnName'] = ['zeelool','voogueme','nihao','meeloog','wesee','zeelool-es','zeelool-de','zeelool-jp'];

            if ($order_platform == 1){
                $up_field = 'glass_in_sale_num as total';
                $down_field = 'glass_shelves_num as total';
                $presell_field = 'glass_presell_num as total';
            }elseif ($order_platform == 2){
                $up_field = 'box_in_sale_num as total';
                $down_field = 'box_shelves_num as total';
                $presell_field = 'box_presell_num as total';
            }else{
                $up_field = 'sum(glass_in_sale_num)+sum(box_in_sale_num) as total';
                $down_field = 'sum(glass_shelves_num)+sum(box_shelves_num) as total';
                $presell_field = 'sum(glass_presell_num)+sum(box_presell_num) as total';
            }
            if(!$params['time_str']){
                $start = date('Y-m-d', strtotime('-1 day'));
                $end   = $start.' 23:59:59';
                $where['day_date'] = ['between', [$start, $end]];
            }else{
                $createat = explode(' ', $params['time_str']);
                $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            }
            $platform_z_up_num =Db::name('datacenter_day')->where('site',1)->where($where)->field($up_field)->select();
            $platform_z_up_num =$platform_z_up_num[0]['total'];

            $platform_z_down_num =Db::name('datacenter_day')->where('site',1)->where($where)->field($down_field)->select();
            $platform_z_down_num =$platform_z_down_num[0]['total'];
            $platform_z_yushou_num =Db::name('datacenter_day')->where('site',1)->where($where)->field($presell_field)->select();
            $platform_z_yushou_num =$platform_z_yushou_num[0]['total'];
            $platform_v_up_num =Db::name('datacenter_day')->where('site',2)->where($where)->field($up_field)->select();
            $platform_v_up_num =$platform_v_up_num[0]['total'];
            $platform_v_down_num =Db::name('datacenter_day')->where('site',2)->where($where)->field($down_field)->select();
            $platform_v_down_num =$platform_v_down_num[0]['total'];
            $platform_v_yushou_num =Db::name('datacenter_day')->where('site',2)->where($where)->field($presell_field)->select();
            $platform_v_yushou_num =$platform_v_yushou_num[0]['total'];
            $platform_n_up_num =Db::name('datacenter_day')->where('site',3)->where($where)->field($up_field)->select();
            $platform_n_up_num =$platform_n_up_num[0]['total'];
            $platform_n_down_num =Db::name('datacenter_day')->where('site',3)->where($where)->field($down_field)->select();
            $platform_n_down_num =$platform_n_down_num[0]['total'];
            $platform_n_yushou_num =Db::name('datacenter_day')->where('site',3)->where($where)->field($presell_field)->select();
            $platform_n_yushou_num =$platform_n_yushou_num[0]['total'];
            $platform_w_up_num =Db::name('datacenter_day')->where('site',4)->where($where)->field($up_field)->select();
            $platform_w_up_num =$platform_w_up_num[0]['total'];
            $platform_w_down_num =Db::name('datacenter_day')->where('site',4)->where($where)->field($down_field)->select();
            $platform_w_down_num =$platform_w_down_num[0]['total'];
            $platform_w_yushou_num =Db::name('datacenter_day')->where('site',4)->where($where)->field($presell_field)->select();
            $platform_w_yushou_num =$platform_w_yushou_num[0]['total'];
            $platform_m_up_num =Db::name('datacenter_day')->where('site',5)->where($where)->field($up_field)->select();
            $platform_m_up_num =$platform_m_up_num[0]['total'];
            $platform_m_down_num =Db::name('datacenter_day')->where('site',5)->where($where)->field($down_field)->select();
            $platform_m_down_num =$platform_m_down_num[0]['total'];
            $platform_m_yushou_num =Db::name('datacenter_day')->where('site',5)->where($where)->field($presell_field)->select();
            $platform_m_yushou_num =$platform_m_yushou_num[0]['total'];
            $platform_es_up_num =Db::name('datacenter_day')->where('site',9)->where($where)->field($up_field)->select();
            $platform_es_up_num =$platform_es_up_num[0]['total'];
            $platform_es_down_num =Db::name('datacenter_day')->where('site',9)->where($where)->field($down_field)->select();
            $platform_es_down_num =$platform_es_down_num[0]['total'];
            $platform_es_yushou_num =Db::name('datacenter_day')->where('site',9)->where($where)->field($presell_field)->select();
            $platform_es_yushou_num =$platform_es_yushou_num[0]['total'];
            $platform_de_up_num =Db::name('datacenter_day')->where('site',10)->where($where)->field($up_field)->select();
            $platform_de_up_num =$platform_de_up_num[0]['total'];
            $platform_de_down_num =Db::name('datacenter_day')->where('site',10)->where($where)->field($down_field)->select();
            $platform_de_down_num =$platform_de_down_num[0]['total'];
            $platform_de_yushou_num =Db::name('datacenter_day')->where('site',10)->where($where)->field($presell_field)->select();
            $platform_de_yushou_num =$platform_de_yushou_num[0]['total'];
            $platform_jp_up_num =Db::name('datacenter_day')->where('site',11)->where($where)->field($up_field)->select();
            $platform_jp_up_num =$platform_jp_up_num[0]['total'];
            $platform_jp_down_num =Db::name('datacenter_day')->where('site',11)->where($where)->field($down_field)->select();
            $platform_jp_down_num =$platform_jp_down_num[0]['total'];
            $platform_jp_yushou_num =Db::name('datacenter_day')->where('site',11)->where($where)->field($presell_field)->select();
            $platform_jp_yushou_num =$platform_jp_yushou_num[0]['total'];

            $json['columnData'] = [
                [
                    'type' => 'bar',
                    'barWidth' => '10%',
                    'data' => [$platform_z_up_num,$platform_v_up_num,$platform_n_up_num,$platform_w_up_num,$platform_m_up_num,$platform_es_up_num,$platform_de_up_num,$platform_jp_up_num],
                    'name' => '在售'
                ],
                [
                    'type' => 'bar',
                    'barWidth' => '10%',
                    'data' => [$platform_z_yushou_num,$platform_v_yushou_num,$platform_n_yushou_num,$platform_w_yushou_num,$platform_m_yushou_num,$platform_es_yushou_num,$platform_de_yushou_num,$platform_jp_yushou_num],
                    'name' => '预售'
                ],
                [
                    'type' => 'bar',
                    'barWidth' => '10%',
                    'data' => [$platform_z_down_num,$platform_v_down_num,$platform_n_down_num,$platform_w_down_num,$platform_m_down_num,$platform_es_down_num,$platform_de_down_num,$platform_jp_down_num],
                    'name' => '下架'
                ],
            ];
            return json(['code' => 1, 'data'=>$json]);
        }
    }

    /**
     * 各站点镜框产品重复比例及数量统计
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/11/18
     * Time: 15:09:24
     */
    public function glass_same_data()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            //站点a id
            $platform_a = $params['platform_a'];
            //站点a名称
            $platform_a_name = $params['platform_a_name'];
            //站点b id
            $platform_b = $params['platform_b'];
            //站点b名称
            $platform_b_name = $params['platform_b_name'];
            $item = new \app\admin\model\itemmanage\Item();
            //获取仓库镜架SKU
            $skus = $item->getFrameSku();
            $map['sku'] = ['in', $skus];
            //镜框数量
            $platform_a_num =$this->item_platform->where('platform_type',$platform_a)->where($map)->count();
            $platform_b_num =$this->item_platform->where('platform_type',$platform_b)->where($map)->count();

            $json['column'] = [$platform_a_name, $platform_b_name];
            $json['columnData'] = [
                [
                    'name' => $platform_a_name,
                    'value' => $platform_a_num,
                ],
                [
                    'name' => $platform_b_name,
                    'value' => $platform_b_num,
                ],
            ];
            $json['total'] = $platform_a_num+$platform_b_num;
            return json(['code' => 1, 'data' => $json]);
        }
    }

    /**
     * 获取重复数量和重复占比
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/11/19
     * Time: 10:37:44
     */
    public function again_glass_same_data()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $platform_a = $params['platform_a'];
            $platform_b = $params['platform_b'];

            $item = new \app\admin\model\itemmanage\Item();
            //获取仓库镜架SKU
            $skus = $item->getFrameSku();
            $map['sku'] = ['in', $skus];
            $again_num = $this->item_platform
                ->where('platform_type','in',[$platform_a,$platform_b])
                ->where($map)
                ->where('outer_sku_status',1)
                ->group('sku')
                ->having('count(platform_type)>1')
                ->count();
            //镜框数量
            $platform_a_num =$this->item_platform->where('platform_type',$platform_a)->where($map)->count();
            // dump($platform_a_num);
            $again_rate = round($again_num/$platform_a_num * 100,2).'%';

            $data = compact('again_num','again_rate');
            $this->success('', '', $data);
        }
    }
}
