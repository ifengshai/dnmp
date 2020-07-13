<?php

namespace app\admin\model\saleaftermanage;

use app\admin\model\Admin;
use fast\Http;
use think\Cache;
use think\Db;
use think\Exception;
use think\Model;
use Util\NihaoPrescriptionDetailHelper;
use Util\VooguemePrescriptionDetailHelper;
use Util\ZeeloolPrescriptionDetailHelper;
use Util\WeseeopticalPrescriptionDetailHelper;
use GuzzleHttp\Client;
use app\admin\model\saleaftermanage\WorkOrderMeasure;
use app\admin\model\saleaftermanage\WorkOrderRecept;
use app\admin\model\saleaftermanage\WorkOrderChangeSku;
use app\admin\controller\warehouse\Inventory;
use app\api\controller\Ding;

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
     * 平台类型
     * @param $value
     * @param $data
     * @return mixed
     */
    public function getWorkPlatFormFormatAttr($value, $data)
    {
        $status = ['1' => 'zeelool', '2' => 'voogueme', '3' => 'nihao'];
        return $status[$data['work_platform']];
    }

    /**
     * 工单类型
     * @param $value
     * @param $data
     * @return mixed
     */
    public function getWorkTypeFormatAttr($value, $data)
    {
        $status = ['1' => '客服工单', '2' => '仓库工单'];
        return $status[$data['work_type']];
    }

    /**
     * 工单状态
     * @param $value
     * @param $data
     * @return mixed
     */
    public function getWorkStatusFormatAttr($value, $data)
    {
        $status = ['0' => '取消', '1' => '新建', '2' => '待审核', '3' => '待处理', '4' => '审核拒绝', '5' => '部分处理', '6' => '已处理'];
        return $status[$data['work_status']];
    }

    /**
     * 工单级别
     * @param $value
     * @param $data
     * @return mixed
     */
    public function getWorkLevelFormatAttr($value, $data)
    {
        $status = ['1' => '低', '2' => '中', '3' => '高'];
        return $status[$data['work_type']];
    }

    //获取选项卡列表
    public function getTabList()
    {
        return [
            ['name' => '我创建的任务', 'field' => 'create_user_name', 'value' => session('admin.nickname')],
            ['name' => '我的任务', 'field' => 'recept_person_id', 'value' => session('admin.id')],
        ];
    }

    /**
     * 措施
     * @return \think\model\relation\HasMany
     */
    public function measures()
    {
        return $this->hasMany(WorkOrderMeasure::class, 'id', 'work_id');
    }

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
        $is_new_version = 0;
        switch ($order_platform) {
            case 1:
                $this->model = new \app\admin\model\order\order\Zeelool();
                $is_new_version = $this->model->where('increment_id', $increment_id)
                    ->value('is_new_version');
                break;
            case 2:
                $this->model = new \app\admin\model\order\order\Voogueme();
                break;
            case 3:
                $this->model = new \app\admin\model\order\order\Nihao();
                break;
            case 4:
                $this->model = new \app\admin\model\order\order\Meeloog();
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
            ->field('a.order_currency_code,a.base_grand_total,a.grand_total,a.base_to_order_rate,c.method,a.customer_email,a.order_type,a.mw_rewardpoint_discount')->find();
        if (!$sku && !$orderInfo) {
            return [];
        }
        $register_email = $this->model->alias('m')->where('m.increment_id',$increment_id)->join(['customer_entity' => 'r'], 'm.customer_id=r.entity_id')->value('email');
        $result['sku'] = array_unique($sku);
        $result['base_currency_code'] = $orderInfo['order_currency_code'];
        $result['method'] = $orderInfo['method'];
        $result['is_new_version'] = $is_new_version;
        $result['base_grand_total'] = $orderInfo['base_grand_total'];
        $result['grand_total']    = $orderInfo['grand_total'];
        $result['base_to_order_rate'] = $orderInfo['base_to_order_rate'];
        $result['customer_email'] = $register_email ?: $orderInfo['customer_email'];
        $result['order_type']     = $orderInfo['order_type'];
        $result['mw_rewardpoint_discount'] = round($orderInfo['mw_rewardpoint_discount'],2);
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
        if ($siteType < 3) {
            foreach ($prescriptions as $key => $val) {
                if (!isset($val['total_add'])) {
                    $prescriptions[$key]['os_add'] = $val['od_add'];
                    $prescriptions[$key]['od_add'] = $val['os_add'];
                }
            }
        }
        //获取地址信息
        $address = $this->model->alias('a')
            ->field('b.entity_id,b.firstname,b.lastname,b.telephone,b.email,b.region,b.region_id,b.postcode,b.street,b.city,b.country_id,b.address_type')
            ->where('increment_id', $incrementId)
            ->join(['sales_flat_order_address' => 'b'], 'a.entity_id=b.parent_id')
            ->order('b.entity_id desc')
            ->select();
        $showPrescriptions = [];
        if ($prescriptions === false) {
            exception('无此订单号，请查询后重试');
        }
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
    public function getReissueLens($siteType, $showPrescriptions, $type = 1, $isNewVersion = 0)
    {
        $url = '';
        $key = $siteType . '_getlens_' . $isNewVersion;
        $data = Cache::get($key);
        if (!$data) {
            if($isNewVersion == 1){
                $url = 'magic/product/newLensData';
            }else{
                $url = 'magic/product/lensData';
            }
            $data = $this->httpRequest($siteType, $url);
            Cache::set($key, $data, 3600 * 24);
        }

        $prescription = $prescriptions = $coating_type = '';

        $prescription = $data['lens_list'];
        $colorList = $data['color_list'] ?? [];
        $lensColorList = $data['lens_color_list'];
        $coating_type = $data['coating_list'];
        if ($type == 1) {
            foreach ($showPrescriptions as $key => $val) {
                $prescriptions .= "<option value='{$key}'>{$val}</option>";
            }
            //拼接html页面
            $html = (new \think\View())->fetch('saleaftermanage/work_order_list/ajax_reissue_add', compact('prescription', 'coating_type', 'prescriptions', 'colorList', 'type','lensColorList','isNewVersion'));
        } elseif ($type == 2) {
            $html = (new \think\View())->fetch('saleaftermanage/work_order_list/ajax_reissue_add', compact('showPrescriptions', 'prescription', 'coating_type', 'prescriptions', 'colorList', 'lensColorList', 'type','isNewVersion'));
        } else {
            $html = (new \think\View())->fetch('saleaftermanage/work_order_list/ajax_reissue_add', compact('showPrescriptions', 'prescription', 'coating_type', 'prescriptions', 'colorList', 'type','lensColorList','isNewVersion'));
        }
        return ['data' => $data, 'html' => $html];
    }

    /**
     * http请求
     * @param $siteType
     * @param $pathinfo
     * @param array $params
     * @param string $method
     * @return bool
     * @throws \Exception
     */
    public function httpRequest($siteType, $pathinfo, $params = [], $method = 'GET')
    {
        switch ($siteType) {
            case 1:
                $url = config('url.zeelool_url');
                break;
            case 2:
                $url = config('url.voogueme_url');
                break;
            case 3:
                $url = config('url.nihao_url');
                break;
            case 5:
                $url = config('url.wesee_url');
                break;
            default:
                return false;
                break;
        }
        $url = $url . $pathinfo;

        $client = new Client(['verify' => false]);
        //file_put_contents('/www/wwwroot/mojing/runtime/log/a.txt',json_encode($params),FILE_APPEND);
        try {
            if ($method == 'GET') {
                $response = $client->request('GET', $url, array('query' => $params));
            } else {
                $response = $client->request('POST', $url, array('form_params' => $params));
            }
            $body = $response->getBody();
            //file_put_contents('/www/wwwroot/mojing/runtime/log/a.txt',$body,FILE_APPEND);
            $stringBody = (string) $body;
            $res = json_decode($stringBody, true);
            //file_put_contents('/www/wwwroot/mojing/runtime/log/a.txt',$stringBody,FILE_APPEND);
            if ($res === null) {
                exception('网络异常');
            }
            if ($res['status'] == 200) {
                return $res['data'];
            }
            exception($res['msg'] . '   error_code:' . $res['status']);
        } catch (Exception $e) {
            exception($e->getMessage());
        }
    }
    
    /**
     * 更改地址
     * @param $params
     * @param $work_id
     * @throws \Exception
     */
    public function changeAddress($params, $work_id, $measure_choose_id, $measure_id)
    {
        $work = $this->find($work_id);
        $siteType = $params['work_platform'];
        //修改地址
        if (($work->work_type == 1 && $measure_choose_id == 13) || ($work->work_type == 2 && $measure_choose_id == 13)) {
            Db::startTrans();
            try {
                if (!$params['modify_address']['country_id']) {
                    exception('国家不能为空');
                }
                //查询是否有该地址
                $is_exist = WorkOrderChangeSku::where(['work_id' => $work_id])->value('id');
                if(!$is_exist){
                    $data = [
                        'work_id' => $work_id,
                        'increment_id' => $params['platform_order'],
                        'platform_type' => $params['work_platform'],
                        'change_type' => 6,
                        'measure_id' => $measure_id,
                        'create_person' => session('admin.nickname'),
                        'update_time' => date('Y-m-d H:i:s'),
                        'create_time' => date('Y-m-d H:i:s')
                    ];
                    //修改地址
                    $data['email'] = $params['modify_address']['email'];
 
                    $data['userinfo_option'] = serialize($params['modify_address']);
                    WorkOrderChangeSku::create($data);
                    WorkOrderMeasure::where(['id' => $measure_id])->update(['sku_change_type' => 6]);
                }else{
                    //更新
                    $data['email'] = $params['modify_address']['email'];
                    $data['userinfo_option'] = serialize($params['modify_address']);
                    WorkOrderChangeSku::where(['work_id' => $work_id])->update($data);
                }
                
                if($params['work_status'] == 2){
                    $changeAddress = $params['modify_address'];
                    $postData = array(
                        'increment_id'=>$params['platform_order'],
                        'type'=>$changeAddress['address_id'],
                        'first_name'=>$changeAddress['firstname'],
                        'last_name'=>$changeAddress['lastname'],
                        'email'=>$changeAddress['email'],
                        'telephone'=>$changeAddress['telephone'],
                        'country'=>$changeAddress['country_id'],
                        'region_id'=>$changeAddress['region_id'],
                        'region'=>$changeAddress['region'],
                        'city'=>$changeAddress['city'],
                        'street'=>$changeAddress['street'],
                        'postcode'=>$changeAddress['postcode'],
                    );
                    $res = $this->httpRequest($siteType, 'magic/order/editAddress', $postData, 'POST');
                }
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                exception($e->getMessage());
            }
        } 
    }
    /**
     * 更改镜片，赠品，
     * @param $params
     * @param $work_id
     * @throws \Exception
     */
    public function changeLens($params, $work_id, $measure_choose_id, $measure_id)
    {
        $work = $this->find($work_id);
        $measure = '';
        //修改镜片
        if (($work->work_type == 1  && $measure_choose_id == 12) || ($work->work_type == 2  && $measure_choose_id == 12)) {
            $measure = 12;
        } elseif ($measure_choose_id == 6) { //赠品
            $measure = 2;
        } elseif ($measure_choose_id == 7) { //补发
            $measure = 3;
        }
        if ($measure) {
            Db::startTrans();
            try {
                //如果是更改镜片
                if ($measure == 12) {
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
                    $recipe_type = $changeLens['recipe_type'][$key];
                    if (!$recipe_type) {
                        exception('处方类型不能为空');
                    }
                    $type = $params['work_platform'];
                    $lensId = $changeLens['lens_type'][$key];
                    $colorId = $changeLens['color_id'][$key];
                    $coatingId = $changeLens['coating_type'][$key];
                    $lensCoatName = $this->getLensCoatingName($type, $lensId, $coatingId, $colorId, $recipe_type,$work->is_new_version);
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
                        'measure_id' => $measure_id,
                        'create_person' => session('admin.nickname'),
                        'update_time' => date('Y-m-d H:i:s'),
                        'create_time' => date('Y-m-d H:i:s')
                    ];
                    //补发

                    $data['email'] = $params['address']['email'];
                    if ($change_type == 5) {
                        if (!$params['address']['country_id']) {
                            exception('国家不能为空');
                        }
                    }
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
                    //}
                    WorkOrderChangeSku::create($data);
                    WorkOrderMeasure::where(['id' => $measure_id])->update(['sku_change_type' => $change_type]);
                }
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                exception($e->getMessage());
            }
        }
    }
    /**
     * 插入更换镜框数据
     *
     * @Description
     * @author lsw
     * @since 2020/04/23 17:02:32
     * @return void
     */
    public function changeFrame($params, $work_id, $measure_choose_id, $measure_id)
    {
        //循环插入更换镜框数据
        $orderChangeList = [];
        //判断是否选中更改镜框问题类型
        if ($params['change_frame']) {
            if (( $params['work_type'] == 1 && $measure_choose_id == 1) || ($params['work_type'] == 2 && $measure_choose_id == 1)) {
                $original_sku = $params['change_frame']['original_sku'];
                $original_number = $params['change_frame']['original_number'];
                $change_sku = $params['change_frame']['change_sku'];
                $change_number = $params['change_frame']['change_number'];
                foreach ($change_sku as $k => $v) {
                    if (!$v) {
                        continue;
                    }
                    $orderChangeList[$k]['work_id'] = $work_id;
                    $orderChangeList[$k]['increment_id'] = $params['platform_order'];
                    $orderChangeList[$k]['platform_type'] = $params['work_platform'];
                    $orderChangeList[$k]['original_sku'] = $original_sku[$k];
                    $orderChangeList[$k]['original_number'] = $original_number[$k];
                    $orderChangeList[$k]['change_sku'] = $v;
                    $orderChangeList[$k]['change_number'] = $change_number[$k];
                    $orderChangeList[$k]['change_type'] = 1;
                    $orderChangeList[$k]['measure_id']  = $measure_id;
                    $orderChangeList[$k]['create_person'] = session('admin.nickname');
                    $orderChangeList[$k]['create_time'] = date('Y-m-d H:i:s');
                    $orderChangeList[$k]['update_time'] = date('Y-m-d H:i:s');
                }
                $orderChangeRes = (new WorkOrderChangeSku())->saveAll($orderChangeList);
                if (false === $orderChangeRes) {
                    throw new Exception("添加失败！！");
                } else {
                    WorkOrderMeasure::where(['id' => $measure_id])->update(['sku_change_type' => 1]);
                }
            } else {
                return false;
            }
        }
    }
    public function cancelOrder($params, $work_id, $measure_choose_id, $measure_id)
    {
        //循环插入取消订单数据
        $orderChangeList = [];
        //判断是否选中取消措施
        if ($params['cancel_order'] && (3 == $measure_choose_id)) {

            foreach ($params['cancel_order']['original_sku'] as $k => $v) {

                $orderChangeList[$k]['work_id'] = $work_id;
                $orderChangeList[$k]['increment_id'] = $params['platform_order'];
                $orderChangeList[$k]['platform_type'] = $params['work_platform'];
                $orderChangeList[$k]['original_sku'] = $v;
                $orderChangeList[$k]['original_number'] = $params['cancel_order']['original_number'][$k];
                $orderChangeList[$k]['change_type'] = 3;
                $orderChangeList[$k]['measure_id']  = $measure_id;
                $orderChangeList[$k]['create_person'] = session('admin.nickname');
                $orderChangeList[$k]['create_time'] = date('Y-m-d H:i:s');
                $orderChangeList[$k]['update_time'] = date('Y-m-d H:i:s');
            }
            $cancelOrderRes = (new WorkOrderChangeSku())->saveAll($orderChangeList);
            if (false === $cancelOrderRes) {
                throw new Exception("添加失败！！");
            } else {
                WorkOrderMeasure::where(['id' => $measure_id])->update(['sku_change_type' => 3]);
            }
        } else {
            return false;
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
    public function getLensCoatingName($siteType, $lens_id, $coating_id, $colorId, $prescription_type,$isNewVersion)
    {
        $key = $siteType . '_getlens_' . $isNewVersion;
        $data = Cache::get($key);
        if (!$data) {
            if($isNewVersion == 0){
                $url = 'magic/product/lensData';
            }elseif($isNewVersion == 1){
                $url = 'magic/product/newLensData';
            }
            $data = $this->httpRequest($siteType, $url);
            Cache::set($key, $data, 3600 * 24);
        }
        $prescription = $data['lens_list'];
        $coatingLists = $data['coating_list'];
        $colorList = $data['color_list'] ?? [];
        $lensColorList = $data['lens_color_list'];
        //返回lensName
        $lens = $prescription[$prescription_type] ?? [];
        $lensName = $coatingName = $colorName = $lensType = '';
        if (!$colorId) {
            foreach ($lens as $len) {
                if ($len['lens_id'] == $lens_id) {
                    $lensName = $len['lens_data_name'];
                    $lensType = $len['lens_data_index'];
                    break;
                }
            }
        } else {
            //colorname
            if($isNewVersion == 1){
                foreach ($lensColorList as $key => $val) {
                    if ($val['lens_id'] == $colorId) {
                        $colorName = $val['lens_data_name'];
                        break;
                    }
                }
            }else{
                foreach ($colorList as $key => $val) {
                    if ($val['id'] == $colorId) {
                        $colorName = $val['name'];
                        break;
                    }
                }
            }

            //lensName
            foreach ($lens as $val) {
                if ($val['lens_id'] == $lens_id) {
                    $lensName = $val['lens_data_name'] . "({$colorName})";
                    $lensType = $val['lens_data_index'];
                    break;
                }
            }
        }

        foreach ($coatingLists as $coatingList) {
            if($isNewVersion == 1){
                if ($coatingList['coating_id'] == $coating_id) {
                    $coatingName = $coatingList['coating_name'];
                    break;
                }
            }else{
                if ($coatingList['id'] == $coating_id) {
                    $coatingName = $coatingList['name'];
                    break;
                }
            }

        }

        return ['lensName' => $lensName, 'lensType' => $lensType, 'colorName' => $colorName, 'coatingName' => $coatingName];
    }

    /**
     * 创建补发单
     * @param $siteType
     * @param $work_id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function createOrder($siteType, $work_id, $isNewVersion = 0)
    {
        $changeSkus = WorkOrderChangeSku::where(['work_id' => $work_id, 'change_type' => 5])->select();
        //file_put_contents('/www/wwwroot/mojing/runtime/log/a.txt',json_encode(collection($changeSkus)->toArray()),FILE_APPEND);
        //如果存在补发单的措施
        if ($changeSkus) {
            $postData = $postDataCommon = [];
            foreach ($changeSkus as $key => $changeSku) {
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
                    'region_id' => $address['region_id'],
                    'street' => $address['street'],
                ];
                $pdCheck = $pd = $prismcheck = '';
                $pd_r = $pd_l = '';
                if ($changeSku['pd_r'] && $changeSku['pd_l']) {
                    $pdCheck = 'on';
                    $pd_r = $changeSku['pd_r'];
                    $pd_l = $changeSku['pd_l'];
                } else {
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
                if ($od_pv || $os_pv || $od_bd || $os_bd || $od_pv_r || $os_pv_r || $od_bd_r || $os_bd_r) {
                    $prismcheck = 'on';
                }
                $is_frame_only = 0;
                if ($prescriptions['lens_id'] || $prescriptions['coating_id'] || $prescriptions['color_id']) {
                    $is_frame_only = 1;
                }

                $postData['product'][$key] = [
                    'sku' => $changeSku['original_sku'],
                    'qty' => $changeSku['original_number'],
                    'prescription_type' => $changeSku['recipe_type'],
                    'is_frame_only' => $is_frame_only,
                    'od_sph' => $changeSku['od_sph'],
                    'os_sph' => $changeSku['os_sph'],
                    'od_cyl' => $changeSku['od_cyl'],
                    'os_cyl' => $changeSku['os_cyl'],
                    'od_axis' => $changeSku['od_axis'],
                    'os_axis' => $changeSku['os_axis'],
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
                $measure_id = $changeSku['measure_id'];
            }
            $postData = array_merge($postData, $postDataCommon);
            try {
                //file_put_contents('/www/wwwroot/mojing/runtime/log/a.txt',json_encode($postData),FILE_APPEND);
                if($isNewVersion == 0){
                    $url = 'magic/order/createOrder';
                }elseif($isNewVersion == 1){
                    $url = 'magic/order/newCreateOrder';
                }
                $res = $this->httpRequest($siteType, $url, $postData, 'POST');
                $increment_id = $res['increment_id'];
                //replacement_order添加补发的订单号
                WorkOrderChangeSku::where(['work_id' => $work_id, 'change_type' => 5])->setField('replacement_order', $increment_id);
                self::where(['id' => $work_id])->setField('replacement_order', $increment_id);

                //补发扣库存
                $this->deductionStock($work_id, $measure_id);
            } catch (Exception $e) {
                exception($e->getMessage());
            }
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
        $work = self::find($work_id);
        $postData = [
            'email' => $work->email,
            //'ordernum' => $work->platform_order,
            'point' => $work->integral,
            'content' => $work->integral_describe
        ];
        try {
            $res = $this->httpRequest($work['work_platform'], 'magic/promotion/bonusPoints', $postData, 'POST');
            return true;
        } catch (Exception $e) {
            //exception('赠送积分失败');
            exception($e->getMessage());
        }
    }

    /**
     * 领取优惠券
     * @param $work_id
     * @return bool
     * @throws \Exception
     */
    public function presentCoupon($work_id)
    {
        $work = self::find($work_id);
        $postData = [
            'rule_id' => $work->coupon_id
        ];
        try {
            $res = $this->httpRequest($work['work_platform'], 'magic/promotion/receive', $postData, 'POST');
            $work->coupon_str = $res['coupon_code'];
            $work->save();
            return true;
        } catch (Exception $e) {
            exception($e->getMessage());
        }
    }

    /**
     * 获取修改处方(编辑的时候带出存储的信息)
     * @param $siteType
     * @param $showPrescriptions
     * @return array|bool
     * @throws \think\Exception
     */
    public function getEditReissueLens($siteType, $showPrescriptions, $type = 1, $info = [], $operate_type = '',$is_new_version = 0)
    {
        $url = '';
        $key = $siteType . '_getlens_' . $is_new_version;
        $data = Cache::get($key);
        if (!$data) {
            if($is_new_version == 1){
                $url = 'magic/product/newLensData';
            }else{
                $url = 'magic/product/lensData';
            }
            $data = $this->httpRequest($siteType, $url);
            Cache::set($key, $data, 3600 * 24);
        }

        $prescription = $prescriptions = $coating_type = '';

        $prescription = $data['lens_list'];
        $colorList = $data['color_list'] ?? [];
        $lensColorList = $data['lens_color_list'];
        $coating_type = $data['coating_list'];
        if ($type == 1) {
            foreach ($showPrescriptions as $key => $val) {
                $prescriptions .= "<option value='{$key}'>{$val}</option>";
            }
            //拼接html页面
            $html = (new \think\View())->fetch('saleaftermanage/work_order_list/ajax_reissue_edit', compact('prescription', 'coating_type', 'prescriptions', 'colorList', 'type', 'info', 'operate_type','lensColorList','is_new_version'));
        } elseif ($type == 2) {
            $html = (new \think\View())->fetch('saleaftermanage/work_order_list/ajax_reissue_edit', compact('showPrescriptions', 'prescription', 'coating_type', 'prescriptions', 'colorList', 'lensColorList', 'type', 'info', 'operate_type','is_new_version'));
        } else {
            $html = (new \think\View())->fetch('saleaftermanage/work_order_list/ajax_reissue_edit', compact('showPrescriptions', 'prescription', 'coating_type', 'prescriptions', 'colorList', 'type', 'info', 'operate_type','lensColorList','is_new_version'));
        }
        return ['data' => $data, 'html' => $html];
    }

    /**
     * 审核
     * @param $work_id
     * @param array $params
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function checkWork($work_id, $params = [])
    {

        $work = self::find($work_id);
        //判断是否已审核
        if ($work->check_time) return true;
        Db::startTrans();
        try {
            $time = date('Y-m-d H:i:s');
            $admin_id = session('admin.id');
            //如果承接人是自己的话表示处理完成，不是自己的不做处理
            $orderRecepts = WorkOrderRecept::where('work_id', $work_id)->select();
            $allComplete = 1;
            $count = count($orderRecepts);

            //不需要审核的，
            if (($work->is_check == 0 && $work->work_type == 1) || ($work->is_check == 0 && $work->work_type == 2 && $work->is_after_deal_with == 1)) {

                $work->check_note = '系统自动审核通过';
                $work->check_time = $time;
                $work->submit_time = $time;
                $key = 0;
                foreach ($orderRecepts as $orderRecept) {
                    //查找措施的id
                    $measure_choose_id = WorkOrderMeasure::where('id', $orderRecept->measure_id)->value('measure_choose_id');
                    //承接人的自动完成状态
                    if ((1 == $orderRecept->is_auto_complete)) {
                        WorkOrderRecept::where('id', $orderRecept->id)->update(['recept_status' => 1, 'finish_time' => $time, 'note' => '自动处理完成']);
                        WorkOrderMeasure::where('id', $orderRecept->measure_id)->update(['operation_type' => 1, 'operation_time' => $time]);
                        $key++;
                    } else {
                        $allComplete = 0;
                    }
                }
                if ($allComplete == 1 && $count == $key) {
                    //处理完成
                    $work_status = 6;
                } elseif ($key > 0 && $count > $key) {
                    //部分处理
                    $work_status = 5;
                } else {
                    $work_status = 3;
                }
                $work->work_status = $work_status;

                if ($work_status == 6) {
                    $work->complete_time = $time;
                }
                $work->save();
                //工单备注表
                $remarkData = [
                    'work_id' => $work_id,
                    'remark_type' => 1,
                    'remark_record' => '系统自动审核通过',
                    'create_person_id' => $admin_id,
                    'create_person' => session('admin.nickname'),
                    'create_time' => $time
                ];
                WorkOrderRemark::create($remarkData);
            }
            //需要审核的，有参数才进行审核处理，其余跳过
            if (!empty($params)) {
                if ($work->is_check == 1) {
                    $work->operation_user_id = $admin_id;
                    $work->check_note = $params['check_note'];
                    $work->submit_time = $time;
                    $work->check_time = $time;
                    $key = 0;
                    foreach ($orderRecepts as $orderRecept) {
                        //查找措施的id
                        $measure_choose_id = WorkOrderMeasure::where('id', $orderRecept->measure_id)->value('measure_choose_id');

                        //承接人是自己并且是优惠券、补价、积分，承接默认完成
                        /* if (($orderRecept->recept_person_id == $work->create_user_id || $orderRecept->recept_person_id == $work->after_user_id) && in_array($measure_choose_id, [8, 9, 10])) { */
                        //优惠券、补价、积分，承接默认完成--修改时间20200528--lx
                        if ((1 == $orderRecept->is_auto_complete)) {
                            //审核成功直接进行处理
                            if ($params['success'] == 1) {
                                WorkOrderRecept::where('id', $orderRecept->id)->update(['recept_status' => 1, 'finish_time' => $time, 'note' => '自动处理完成']);
                                WorkOrderMeasure::where('id', $orderRecept->measure_id)->update(['operation_type' => 1, 'operation_time' => $time]);
                                if ($measure_choose_id == 9) {
                                    $this->presentCoupon($work->id);
                                } elseif ($measure_choose_id == 10) {
                                    $this->presentIntegral($work->id);
                                }
                                $key++;
                            }
                        } else {
                            $allComplete = 0;
                        }
                    }
                    if ($allComplete == 1  && $count == $key) {
                        //处理完成
                        $work_status = 6;
                    } elseif ($key > 0  && $count > $key) {
                        //部分处理
                        $work_status = 5;
                    } else {
                        $work_status = 3;
                    }
                    $work->work_status = $work_status;
                    if ($params['success'] == 2) {
                        $work->work_status = 4;
                    } elseif ($params['success'] == 1) {
                        $work->work_status = $work_status;
                        if ($work_status == 6) {
                            $work->complete_time = $time;
                        }
                        //存在补发审核通过后生成补发单
                        $this->createOrder($work->work_platform, $work_id, $work->is_new_version);
                    }

                    $work->save();

                    //工单备注表
                    $remarkData = [
                        'work_id' => $work_id,
                        'remark_type' => 1,
                        'remark_record' => $params['check_note'],
                        'create_person_id' => $admin_id,
                        'create_person' => session('admin.nickname'),
                        'create_time' => $time
                    ];
                    WorkOrderRemark::create($remarkData);
                    //通知
                    //Ding::cc_ding(explode(',', $work->recept_person_id), '', '工单ID：' . $work->id . '😎😎😎😎有新工单需要你处理😎😎😎😎', '有新工单需要你处理');
                }
            }

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            exception($e->getMessage());
        }
    }

    /**
     * 工单处理
     *
     * @Description
     * @param [type] $id   承接表ID
     * @param [type] $work_id 工单表ID
     * @param [type] $measure_id 措施表ID
     * @param [type] $success 是否成功 1 处理成功 2 处理失败
     * @param [type] $process_note 处理备注
     * @return void
     * @author lsw
     * @since 2020/04/21 10:13:28
     */
    public function handleRecept($id, $work_id, $measure_id, $recept_group_id, $success, $process_note,$is_auto_complete)
    {
        $work = self::find($work_id);
        Db::startTrans();
        try {
        if (1 == $success) {
            $data['recept_status'] = 1;
        } else {
            $data['recept_status'] = 2;
        }
        $data['note'] = $process_note;
        $data['finish_time'] = date('Y-m-d H:i:s');
        //更新本条工单数据承接人状态
        $resultInfo = WorkOrderRecept::where(['id' => $id])->update($data);
        //删除同组数据
        $where['work_id'] = $work_id;
        $where['measure_id'] = $measure_id;
        $where['recept_group_id'] = $recept_group_id;
        $where['recept_status'] = 0;
        //删除同样的承接组数据
        WorkOrderRecept::where($where)->delete();
        //如果是处理失败的状态
        if (1 == $data['recept_status']) {
            $dataMeasure['operation_type'] = 1;
        } else {
            $dataMeasure['operation_type'] = 2;
        }
        $dataMeasure['operation_time'] = date('Y-m-d H:i:s');
        WorkOrderMeasure::where(['id' => $measure_id])->update($dataMeasure);
        //求出承接措施是否完成
        $whereMeasure['work_id'] = $work_id;
        //$whereMeasure['measure_id'] = $measure_id;
        $whereMeasure['recept_status'] = ['eq', 0];
        $resultRecept = WorkOrderRecept::where($whereMeasure)->count();
        if (0 == $resultRecept) { //表明整个措施已经完成
            //求出整个工单的措施状态
            $whereWork['work_id'] = $work_id;
            $whereWork['operation_type'] = ['eq', 0];
            $resultMeasure = WorkOrderMeasure::where($whereWork)->count();
            if (0 == $resultMeasure) {
                $dataWorkOrder['work_status'] = 6;

                //通知
                //Ding::cc_ding(explode(',', $work->create_user_id), '', '工单ID：' . $work->id . '😎😎😎😎工单已处理完成😎😎😎😎',  '😎😎😎😎工单已处理完成😎😎😎😎');
            } else {
                $dataWorkOrder['work_status'] = 5;
            }
            $dataWorkOrder['complete_time'] = date('Y-m-d H:i:s');
            
        }else{
            $dataWorkOrder['work_status'] = 5;
        }
        WorkOrderList::where(['id' => $work_id])->update($dataWorkOrder);
        if ($resultInfo  && (1 == $data['recept_status'])) {
            $this->deductionStock($work_id, $measure_id);
        }
        //不是自动处理完成
        if($is_auto_complete != 1){
            $measure_choose_id = WorkOrderMeasure::where('id',$measure_id)->value('measure_choose_id');
            if ($measure_choose_id == 9) {
                $this->presentCoupon($work->id);
            } elseif ($measure_choose_id == 10) {
                $this->presentIntegral($work->id);
            }
        }
        Db::commit();
        return true;
    } catch (Exception $e) {
        Db::rollback();
        exception($e->getMessage());
    }        
  }
    //扣减库存逻辑
    public function deductionStock($work_id, $measure_id)
    {
        $measuerInfo = WorkOrderMeasure::where(['id' => $measure_id])->value('sku_change_type');
        if ($measuerInfo < 1) {
            return false;
        }
        $whereMeasure['work_id'] = $work_id;
        $whereMeasure['change_type'] = $measuerInfo;
        $result = WorkOrderChangeSku::where($whereMeasure)->field('id,increment_id,platform_type,change_type,original_sku,original_number,change_sku,change_number')->select();
        if (!$result) {
            return false;
        }
        $workOrderList = WorkOrderList::where(['id' => $work_id])->field('id,work_platform,platform_order')->find();
        $result = collection($result)->toArray();
        if (1 == $measuerInfo) { //更改镜片
            $info = (new Inventory())->workChangeFrame($work_id, $workOrderList->work_platform, $workOrderList->platform_order, $result, 1);
        } elseif (3 == $measuerInfo) { //取消订单
            $info = (new Inventory())->workCancelOrder($work_id, $workOrderList->work_platform, $workOrderList->platform_order, $result, 2);
        } elseif (4 == $measuerInfo) { //赠品
            $info = (new Inventory())->workPresent($work_id, $workOrderList->work_platform, $workOrderList->platform_order, $result, 3);
        } elseif (5 == $measuerInfo) {
            $info = (new Inventory())->workPresent($work_id, $workOrderList->work_platform, $workOrderList->platform_order, $result, 4);
        } else {
            return false;
        }
        return $info;
    }

    /**
     * 客户订单检索工单
     * @param $allIncrementOrder
     * @return array|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function workOrderListResult($allIncrementOrder)
    {
        $workOrderLists = self::where('platform_order', 'in', $allIncrementOrder)->select();
        foreach ($workOrderLists as &$workOrderList) {
            $receptPersonIds = $workOrderList->recept_person_id;
            $receptPerson = Admin::where('id', 'in', $receptPersonIds)->column('nickname');
            //承接人
            $workOrderList->recept_persons = join(',', $receptPerson);
            $measures = \app\admin\model\saleaftermanage\WorkOrderMeasure::where('work_id', $workOrderList->id)->column('measure_content');
            $measures = join(',', $measures);
            $workOrderList->measure = $measures;
        }
        return $workOrderLists;
    }


    /**
     * 客户订单检索工单 新
     * @param $allIncrementOrder
     * @return array|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function workOrderListInfo($incrementOrder)
    {
        //查询用户id对应姓名
        $admin = new \app\admin\model\Admin();
        $users = $admin->where('status', 'normal')->column('nickname', 'id');

        $workOrderLists = self::where('platform_order', '=', $incrementOrder)->select();
        $replenish_list = [];
        $i = 0;
        foreach ($workOrderLists as &$v) {

            switch ($v['work_platform']) {
                case 1:
                    $db = 'database.db_zeelool';
                    break;
                case 2:
                    $db = 'database.db_voogueme';
                    break;
                case 3:
                    $db = 'database.db_nihao';
                    break;
                default:
                    return false;
                    break;
            }

            //排列sku
            if ($v['order_sku']) {
                $v['order_sku_arr'] = explode(',', $v['order_sku']);
            }

            //取经手人
            if ($v['after_user_id'] != 0) {
                $v['after_user_name'] = $users[$v['after_user_id']];
            }

            //工单类型
            if ($v['work_type'] == 1) {
                $v['work_type_str'] = '客服工单';
            } else {
                $v['work_type_str'] = '仓库工单';
            }

            //工单等级
            if ($v['work_level'] == 1) {
                $v['work_level_str'] = '低';
            } elseif ($v['work_level'] == 2) {
                $v['work_level_str'] = '中';
            } elseif ($v['work_level'] == 3) {
                $v['work_level_str'] = '高';
            }


            $v['assign_user_name'] = $users[$v['assign_user_id']];
            $v['operation_user_name'] = $users[$v['operation_user_id']];

            switch ($v['work_status']) {
                case 0:
                    $v['work_status'] = '取消';
                    break;
                case 1:
                    $v['work_status'] = '新建';
                    break;
                case 2:
                    $v['work_status'] = '待审核';
                    break;
                case 3:
                    $v['work_status'] = '待处理';
                    break;
                case 4:
                    $v['work_status'] = '审核拒绝';
                    break;
                case 5:
                    $v['work_status'] = '部分处理';
                    break;
                case 6:
                    $v['work_status'] = '已处理';
                    break;
                default:
                    break;
            }

            $receptPersonIds = $v->recept_person_id;
            $receptPerson = Admin::where('id', 'in', $receptPersonIds)->column('nickname');
            //承接人
            $v['recept_persons'] = join(',', $receptPerson);
            $step_arr = \app\admin\model\saleaftermanage\WorkOrderMeasure::where('work_id', $v['id'])->select();
            $step_arr = collection($step_arr)->toArray();
            foreach ($step_arr as $key => $values) {
                $recept = \app\admin\model\saleaftermanage\WorkOrderRecept::where('measure_id', $values['id'])->where('work_id',  $v['id'])->select();
                $recept_arr = collection($recept)->toArray();
                $step_arr[$key]['recept_user'] = implode(',', array_column($recept_arr, 'recept_person'));

                $step_arr[$key]['recept'] = $recept_arr;
                if ($values['operation_type'] == 0) {
                    $step_arr[$key]['operation_type'] = '未处理';
                } elseif ($values['operation_type'] == 1) {
                    $step_arr[$key]['operation_type'] = '处理完成';
                } elseif ($values['operation_type'] == 2) {
                    $step_arr[$key]['operation_type'] = '处理失败';
                }
            }

            $v['step'] = $step_arr;
        }
        unset($v);
        $data['list'] = $workOrderLists;
        $data['replenish_list'] = $replenish_list;
        return $data;
    }
    /**
     * vip退款
     *
     * @Description
     * @author mjj
     * @since 2020/07/03 11:43:04 
     * @return void
     */
    public function vipOrderRefund($siteType,$order_number){
        $postData = array('order_number'=>$order_number);
        $res = $this->httpRequest($siteType, 'magic/order/cancelVip', $postData, 'POST');
        return $res;
    }
}
