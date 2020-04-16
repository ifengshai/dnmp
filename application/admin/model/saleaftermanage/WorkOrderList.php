<?php

namespace app\admin\model\saleaftermanage;

use fast\Http;
use think\Cache;
use think\Db;
use think\Exception;
use think\Model;
use Util\NihaoPrescriptionDetailHelper;
use Util\VooguemePrescriptionDetailHelper;
use Util\ZeeloolPrescriptionDetailHelper;
use Util\WeseeopticalPrescriptionDetailHelper;


class WorkOrderList extends Model
{





    // 表名
    protected $name = 'work_order_list';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    /**
     * 根据订单号获取SKU列表
     *
     * @Description
     * @author wpl
     * @since 2020/04/10 15:43:14 
     * @param [type] $order_platform 平台
     * @param [type] $increment_id 订单号
     * @return void
     */
    public function getSkuList($order_platform, $increment_id)
    {
        switch ($order_platform) {
            case 1:
                $this->model = new \app\admin\model\order\order\Zeelool();
                break;
            case 2:
                $this->model = new \app\admin\model\order\order\Voogueme();
                break;
            case 3:
                $this->model = new \app\admin\model\order\order\Nihao();
                break;
            case 5:
                $this->model = new \app\admin\model\order\order\Weseeoptical();
                break;
            default:
                return false;
                break;
        }
        $sku = $this->model->alias('a')
            ->where('increment_id', $increment_id)
            ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
            ->column('sku');
        $orderInfo = $this->model->alias('a')->where('increment_id', $increment_id)
            ->join(['sales_flat_order_payment' => 'c'], 'a.entity_id=c.parent_id')
            ->field('a.base_currency_code,c.method,a.customer_email')->find();
        if (!$sku && !$orderInfo) {
            return [];
        }
        $result['sku'] = array_unique($sku);
        $result['base_currency_code'] = $orderInfo['base_currency_code'];
        $result['method']             = $orderInfo['method'];
        $result['customer_email']     = $orderInfo['customer_email'];
        return $result ? $result : [];
    }

    /**
     * 获取订单的地址
     * @param $siteType
     * @param $incrementId
     * @return array|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAddress($siteType, $incrementId)
    {
        //处方信息
        switch ($siteType) {
            case 1:
                $this->model = new \app\admin\model\order\order\Zeelool();
                $prescriptions = ZeeloolPrescriptionDetailHelper::get_one_by_increment_id($incrementId);
                break;
            case 2:
                $this->model = new \app\admin\model\order\order\Voogueme();
                $prescriptions = VooguemePrescriptionDetailHelper::get_one_by_increment_id($incrementId);
                break;
            case 3:
                $this->model = new \app\admin\model\order\order\Nihao();
                $prescriptions = NihaoPrescriptionDetailHelper::get_one_by_increment_id($incrementId);

                break;
            case 5:
                $this->model = new \app\admin\model\order\order\Weseeoptical();
                $prescriptions = WeseeopticalPrescriptionDetailHelper::get_one_by_increment_id($incrementId);
                break;
            default:
                return false;
                break;
        }
        //获取地址信息
        $address = $this->model->alias('a')
            ->field('b.entity_id,b.firstname,b.lastname,b.telephone,b.email,b.region,b.region_id,b.postcode,b.street,b.city,b.country_id,b.address_type')
            ->where('increment_id', $incrementId)
            ->join(['sales_flat_order_address' => 'b'], 'a.entity_id=b.parent_id')
            ->order('b.entity_id desc')
            ->select();
        $showPrescriptions = [];
        foreach ($prescriptions as $prescription) {
            $showPrescriptions[] = $prescription['prescription_type'] . '--' . $prescription['index_type'];
        }
        return $address ? compact('address', 'prescriptions', 'showPrescriptions') : [];
    }

    /**
     * 获取修改处方
     * @param $siteType
     * @param $showPrescriptions
     * @return array|bool
     * @throws \think\Exception
     */
    public function getReissueLens($siteType, $showPrescriptions, $type = 1)
    {
        $url = '';
        $key = $siteType . '_getlens';
        $data = Cache::get($key);
        if (!$data) {
            $data = $this->httpRequest($siteType,'magic/product/lensData');
            Cache::set($key, $data, 3600 * 24);
        }

        $prescription = $prescriptions = $coating_type = '';

        $prescription = $data['lens_list'];
        $colorList = $data['color_list'];
        $lensColorList = $data['lens_color_list'];
        $coating_type = $data['coating_list'];
        if ($type == 1) {
            foreach ($showPrescriptions as $key => $val) {
                $prescriptions .= "<option value='{$key}'>{$val}</option>";
            }
            //拼接html页面
            $html = (new \think\View())->fetch('saleaftermanage/work_order_list/ajax_reissue_add',compact('prescription','coating_type','prescriptions','colorList','type'));
        }elseif($type == 2){
            $html = (new \think\View())->fetch('saleaftermanage/work_order_list/ajax_reissue_add',compact('showPrescriptions','prescription','coating_type','prescriptions','colorList','lensColorList','type'));
        }else{
            $html = (new \think\View())->fetch('saleaftermanage/work_order_list/ajax_reissue_add',compact('showPrescriptions','prescription','coating_type','prescriptions','colorList','type'));
        }
        return ['data' => $data, 'html' => $html];
    }

    /**
     * 获取lensdata数据
     * @param $siteType
     * @return bool
     */
    public function httpRequest($siteType,$pathinfo,$params = [],$method = 'GET')
    {
        switch ($siteType) {
            case 1:
                $url = 'http://z.zhaokuangyi.com/';
                break;
            case 2:
                $url = 'http://api.voogueme.com/';
                break;
            case 3:
                $url = 'http://nh.zhaokuangyi.com/';
                break;
            case 5:
                $url = 'http://www.eseeoptical.com/';
                break;
            default:
                return false;
                break;
        }
        //echo json_encode($params);die;
        $url = $url . $pathinfo;
        try{
            if($method == 'GET'){
                $res = json_decode(Http::get($url, []), true);
            }else{
                $res = json_decode(Http::post($url, $params), true);
            }
            if($res['code'] == 200){
                return $res['data'];
            }
            exception($res['msg']);
        }catch (Exception $e){
            exception($e->getMessage());
        }

    }

    /**
     * 更改镜框，赠品
     * @param $params
     * @param $work_id
     * @throws \Exception
     */
    public function changeLens($params, $work_id)
    {
        Db::startTrans();
        $measure = 3;
        try {
            //如果是更改镜片
            if ($measure == 1) {
                $changeLens = $params['change_lens'];
                $change_type = 2;
            } elseif ($measure == 2) { //赠品
                $changeLens = $params['gift'];
                $change_type = 4;
            } elseif ($measure == 3) { //补发
                $changeLens = $params['replacement'];
                $change_type = 5;
                if (!$params['address']['shipping_type']) {
                    exception('请选择运输方式');
                }
            }
            $original_skus = $changeLens['original_sku'];
            if (!is_array($original_skus)) {
                exception('sss');
            }
            //循环插入数据
            $changeSkuIds = [];
            $changeSkuData = [];
            foreach ($original_skus as $key => $val) {
                if (!$val) {
                    exception('sku不能为空');
                }
                $type = $params['work_platform'];
                $lensId = $changeLens['lens_type'][$key];
                $colorId = $changeLens['color_id'][$key];
                $coatingId = $changeLens['coating_type'][$key];
                $recipe_type = $changeLens['recipe_type'][$key];
                $lensCoatName = $this->getLensCoatingName($type,$lensId,$coatingId,$colorId,$recipe_type);
                $data = [
                    'work_id' => $work_id,
                    'increment_id' => $params['platform_order'],
                    'platform_type' => $type,
                    'original_name' => $changeLens['original_name'][$key] ?? '',
                    'original_sku' => $changeLens['original_sku'][$key],
                    'original_number' => intval($changeLens['original_number'][$key]),
                    'change_type' => $change_type,
                    'change_sku' => $changeLens['original_sku'][$key],
                    'change_number' => intval($changeLens['original_number'][$key]),
                    'recipe_type' => $recipe_type,
                    'lens_type' => $lensCoatName['lensName'],
                    'coating_type' => $lensCoatName['coatingName'],
                    'od_sph' => $changeLens['od_sph'][$key],
                    'od_cyl' => $changeLens['od_cyl'][$key],
                    'od_axis' => $changeLens['od_axis'][$key],
                    'od_add' => $changeLens['od_add'][$key],
                    'pd_r' => $changeLens['pd_r'][$key],
                    'od_pv' => $changeLens['od_pv'][$key],
                    'od_bd' => $changeLens['od_bd'][$key],
                    'od_pv_r' => $changeLens['od_pv_r'][$key],
                    'od_bd_r' => $changeLens['od_bd_r'][$key],
                    'os_sph' => $changeLens['os_sph'][$key],
                    'os_cyl' => $changeLens['os_cyl'][$key],
                    'os_axis' => $changeLens['os_axis'][$key],
                    'os_add' => $changeLens['os_add'][$key],
                    'pd_l' => $changeLens['pd_l'][$key],
                    'os_pv' => $changeLens['os_pv'][$key],
                    'os_bd' => $changeLens['os_bd'][$key],
                    'os_pv_r' => $changeLens['os_pv_r'][$key],
                    'os_bd_r' => $changeLens['os_bd_r'][$key],
                    'create_person' => session('admin.nickname'),
                    'update_time' => date('Y-m-d H:i:s'),
                    'create_time' => date('Y-m-d H:i:s')
                ];
                //补发
                if ($change_type == 5) {
                    $data['email'] = $params['address']['email'];
                    $data['userinfo_option'] = serialize($params['address']);
                    $prescriptionOption = [
                        'prescription_type' => $recipe_type,
                        'lens_id' => $lensId,
                        'lens_name' => $lensCoatName['lensName'],
                        'lens_type' => $lensCoatName['lensType'],
                        'coating_id' => $coatingId,
                        'coating_name' => $lensCoatName['coatingName'],
                        'color_id' => $colorId,
                        'color_name' => $lensCoatName['colorName'],
                    ];
                    $data['prescription_option'] = serialize($prescriptionOption);
                }
                WorkOrderChangeSku::create($data);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            exception($e->getMessage());
        }
    }

    /**
     * 根据id获取镜片，镀膜的名称
     * @param $siteType
     * @param $lens_id
     * @param $coating_id
     * @param $prescription_type
     * @return array
     */
    public function getLensCoatingName($siteType,$lens_id,$coating_id,$colorId,$prescription_type)
    {
        $key = $siteType . '_getlens';
        $data = Cache::get($key);
        if (!$data) {
            $data = $this->httpRequest($siteType,'magic/product/lensData');
            Cache::set($key, $data, 3600 * 24);
        }
        $prescription = $data['lens_list'];
        $coatingLists = $data['coating_list'];
        $colorList = $data['color_list'];
        $lensColorList = $data['lens_color_list'];
        //返回lensName
        $lens = $prescription[$prescription_type] ?? [];
        $lensName = $coatingName = $colorName = $lensType = '';
        if(!$colorId){
            foreach ($lens as $len) {
                if ($len['lens_id'] == $lens_id) {
                    $lensName = $len['lens_data_name'];
                    $lensType = $len['lens_data_index'];
                    break;
                }
            }
        }else{
            //colorname
            foreach($colorList as $key => $val){
                if($val['id'] == $colorId){
                    $colorName = $val['name'];
                    break;
                }
            }
            //lensName
            foreach($lensColorList as $val){
                if($val['lens_id'] == $lens_id){
                    $lensName = $val['lens_data_name']."({$colorName})";
                    $lensType = $val['lens_data_index'];
                    break;
                }
            }
        }

        foreach ($coatingLists as $coatingList) {
            if ($coatingList['id'] == $coating_id) {
                $coatingName = $coatingList['name'];
                break;
            }
        }
        return ['lensName' => $lensName,'lensType' => $lensType,'colorName' => $colorName,'coatingName' => $coatingName];
    }

    /**
     * 创建补发单
     * @param $siteType
     * @param $work_id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function createOrder($siteType,$work_id)
    {
        $changeSkus = WorkOrderChangeSku::where(['work_id' => $work_id, 'change_type' => 5])->select();
        $postData = $postDataCommon = [];
        foreach($changeSkus as $key =>  $changeSku){
            $address = unserialize($changeSku['userinfo_option']);
            $prescriptions = unserialize($changeSku['prescription_option']);
            $postDataCommon = [
                'currency_code' => $address['currency_code'],
                'country' => $address['country_id'],
                'shipping_type' => $address['shipping_type'],
                'telephone' => $address['telephone'],
                'email' => $address['email'],
                'first_name' => $address['firstname'],
                'last_name' => $address['lastname'],
                'postcode' => $address['postcode'],
                'city' => $address['city'],
                'region_code' => $address['region_id'],
                'street' => $address['street'],
            ];
            $pdCheck = $pd = $prismcheck = '';
            $pd_r = $pd_l = '';
            if($changeSku['pd_r'] && $changeSku['pd_l']){
                $pdCheck = 'on';
                $pd_r = $changeSku['pd_r'];
                $pd_l = $changeSku['pd_l'];
            }else{
                $pd = $changeSku['pd_r'] ?: $changeSku['pd_l'];
            }
            $od_pv = $changeSku['od_pv'];
            $os_pv = $changeSku['os_pv'];
            $od_bd = $changeSku['od_bd'];
            $os_bd = $changeSku['os_bd'];
            $od_pv_r = $changeSku['od_pv_r'];
            $os_pv_r = $changeSku['os_pv_r'];
            $od_bd_r = $changeSku['od_bd_r'];
            $os_bd_r = $changeSku['os_bd_r'];
            if($od_pv || $os_pv || $od_bd || $os_bd || $od_pv_r || $os_pv_r || $od_bd_r || $os_bd_r ){
                $prismcheck = 'on';
            }
            $postData['product'][$key] = [
                'sku' => $changeSku['original_sku'],
                'qty' => $changeSku['original_number'],
                'prescription_type' => $changeSku['recipe_type'],
                'is_frame_only' => $changeSku['recipe_type'],
                'od_sph' => $changeSku['od_sph'],
                'od_sph' => $changeSku['od_sph'],
                'od_cyl' => $changeSku['od_cyl'],
                'os_cyl' => $changeSku['os_cyl'],
                'od_axis' => $changeSku['od_axis'],
                'od_axis' => $changeSku['od_axis'],
                'od_add' => $changeSku['od_add'],
                'os_add' => $changeSku['os_add'],
                'pd' => $pd,
                'pdcheck' => $pdCheck,
                'pd_r' => $pd_r,
                'pd_l' => $pd_l,
                'prismcheck' => $prismcheck,
                'od_pv' => $changeSku['od_pv'],
                'os_pv' => $changeSku['os_pv'],
                'od_bd' => $changeSku['od_bd'],
                'os_bd' => $changeSku['os_bd'],
                'od_pv_r' => $changeSku['od_pv_r'],
                'os_pv_r' => $changeSku['os_pv_r'],
                'od_bd_r' => $changeSku['od_bd_r'],
                'os_bd_r' => $changeSku['os_bd_r'],
                'lens_id' => $prescriptions['lens_id'],
                'lens_name' => $prescriptions['lens_name'],
                'lens_type' => $prescriptions['lens_type'],
                'coating_id' => $prescriptions['coating_id'],
                'coating_name' => $prescriptions['coating_name'],
                'color_id' => $prescriptions['color_id'],
                'color_name' => $prescriptions['color_name'],
            ];
        }
        $postData = array_merge($postData,$postDataCommon);
        try{
            $res = $this->httpRequest($siteType,'magic/order/createOrder',$postData,'POST');
            $increment_id = $res['increment_id'];
            //replacement_order添加补发的订单号
            WorkOrderChangeSku::where(['work_id' => $work_id, 'change_type' => 5])->setField('replacement_order',$increment_id);
        }catch (Exception $e){
            exception($e->getMessage());
        }
    }

    /**
     * 赠送积分
     * @param $work_id
     * @return bool
     * @throws \Exception
     */
    public function presentIntegral($work_id)
    {
        $work = $this->model->find($work_id);
        $postData = [
            'email' => $work->email,
            'ordernum' => $work->platform_order,
            'point' => $work->integral,
            'content' => '测试'
        ];
        try{
            $res = $this->httpRequest($work['work_platform'],'magic/promotion/bonusPoints',$postData,'POST');
            return true;
        }catch (Exception $e){
            exception($e->getMessage());
        }
    }
}
