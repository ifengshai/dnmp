<?php

namespace app\admin\model\saleaftermanage;

use app\admin\model\Admin;
use app\admin\model\DistributionAbnormal;
use app\admin\model\DistributionLog;
use app\admin\model\order\order\NewOrderProcess;
use app\admin\model\warehouse\StockHouse;
use think\Cache;
use think\Db;
use think\exception\PDOException;
use think\Exception;
use think\Model;
use think\View;
use Util\NihaoPrescriptionDetailHelper;
use Util\VooguemePrescriptionDetailHelper;
use Util\ZeeloolPrescriptionDetailHelper;
use Util\MeeloogPrescriptionDetailHelper;
use Util\WeseeopticalPrescriptionDetailHelper;
use Util\ZeeloolEsPrescriptionDetailHelper;
use Util\ZeeloolDePrescriptionDetailHelper;
use Util\ZeeloolJpPrescriptionDetailHelper;
use GuzzleHttp\Client;
use app\admin\controller\warehouse\Inventory;
use app\admin\model\order\order\NewOrder;
use app\admin\model\order\order\NewOrderItemProcess;

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
        $status = ['1' => 'zeelool', '2' => 'voogueme', '3' => 'nihao','4'=>'meeloog','9'=>'zeelool_es','10'=>'zeelool_de','11'=>'zeelool_jp'];
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
            case 9:
                $this->model = new \app\admin\model\order\order\ZeeloolEs();
                break;
            case 10:
                $this->model = new \app\admin\model\order\order\ZeeloolDe();
                break;
            case 11:
                $this->model = new \app\admin\model\order\order\ZeeloolJp();
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
        $result['payment_time'] = $this->model->where('increment_id', $increment_id)->value('created_at');
        return $result ? $result : [];
    }

    /**
     * 根据订单号获取SKU列表-新
     *
     * @param string $increment_id  订单号
     * @param mixed $item_order_number  子订单号
     * @param int $work_type  工单类型：1客服 2仓库
     * @param array $work  工单数据
     * @param int $do_type  操作类型：0其他 1载入数据
     * @author lzh
     * @return array
     */
    public function getOrderItem($increment_id, $item_order_number='', $work_type=0, $work=[], $do_type=0)
    {
        $order_field = 'id,site,base_grand_total,base_to_order_rate,payment_method,customer_email,customer_firstname,customer_lastname,order_type,mw_rewardpoint_discount,base_currency_code,created_at as payment_time';

        $_new_order = new NewOrder();
        $result = $_new_order
            ->where('increment_id', $increment_id)
            ->field($order_field)
            ->find()
        ;
        if(empty($result)){
            return [];
        }

        $select_number = [];
        $order_item_where['order_id'] = $result['id'];
        if(!empty($item_order_number) && 2 == $work_type){
            if(empty($work)){
                $select_number = explode(',',$item_order_number);
            }
            $order_item_where['item_order_number'] = ['in',$item_order_number];
        }
        if(1 == $do_type){
            $order_item_where['distribution_status'] = ['>',0];
        }
        $_new_order_item_process = new NewOrderItemProcess();
        $order_item_list = $_new_order_item_process
            ->where($order_item_where)
            ->column('sku','item_order_number')
        ;

        //已创建工单获取最新镜架和镜片数据
        if($work){
            //获取更改镜框sku集
            $_work_order_change_sku = new WorkOrderChangeSku();
            $sku_list = $_work_order_change_sku
                ->where(['work_id'=>$work['id'],'change_type'=>1])
                ->column('change_sku,original_sku','item_order_number')
            ;

            //获取更改镜片sku集
            $prescription_field = 'recipe_type as prescription_type,coating_type as coating_name,od_sph,os_sph,od_cyl,os_cyl,od_axis,os_axis,pd_l,pd_r,os_add,od_add,od_pv,os_pv,od_pv_r,os_pv_r,od_bd,os_bd,od_bd_r,os_bd_r';
            $prescription_list = $_work_order_change_sku
                ->where(['work_id'=>$work['id'],'change_type'=>2])
                ->column($prescription_field,'item_order_number')
            ;
            if($prescription_list){
                foreach($prescription_list as $k=>$v){
                    if($v['pd_l'] && $v['pd_r']){
                        $pd = '';
                    }else{
                        $pd = $v['pd_l'] ?: $v['pd_r'];
                    }
                    $prescription_list[$k]['pd'] = $pd;
                }
                //增加默认数量
                array_walk($prescription_list, function (&$value, $k, $p) {
                    $value = array_merge($value, $p);
                }, ['qty_ordered' => 1]);
            }

            //获取措施ID
            $_work_order_measure = new WorkOrderMeasure();
            $measure_list = $_work_order_measure
                ->field('measure_choose_id,item_order_number')
                ->where(['work_id'=>$work['id']])
                ->select();
            ;

            //获取子订单措施、镜框、镜片数据
            if($work['order_item_numbers']){
                $item_order_info = [];
                $select_number = explode(',',$work['order_item_numbers']);
                foreach($select_number as $value){
                    $info = [];
                    $measure_ids = [];
                    foreach($measure_list as $v){
                        if($v['item_order_number'] == $value){
                            $measure_ids[] = $v['measure_choose_id'];
                        }
                    }
                    $info['item_choose'] = $measure_ids;
                    if(isset($sku_list[$value])){
                        $info['change_frame'] = $sku_list[$value];
                    }
                    if(isset($prescription_list[$value])){
                        $info['change_lens'] = $prescription_list[$value];
                    }
                    $item_order_info[$value] = $info;
                }
                $result['item_order_info'] = $item_order_info;
            }
        }

        $result['sku_list'] = $order_item_list;//子单号下拉框数据
        $result['select_number'] = $select_number;//已勾选子单号
        $result['mw_rewardpoint_discount'] = round($result['mw_rewardpoint_discount'],2);
        $result['payment_time'] = date('Y-m-d H:i:s',$result['payment_time']);

        return $result;
    }

    /**
     * 获取订单的地址-弃用
     * @param $siteType
     * @param $incrementId
     * @return array|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAddressOld($siteType, $incrementId)
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
            case 4:
                $this->model = new \app\admin\model\order\order\Meeloog();
                $prescriptions = MeeloogPrescriptionDetailHelper::get_one_by_increment_id($incrementId);
                break;
            case 5:
                $this->model = new \app\admin\model\order\order\Weseeoptical();
                $prescriptions = WeseeopticalPrescriptionDetailHelper::get_one_by_increment_id($incrementId);
                break;
            case 9:
                $this->model = new \app\admin\model\order\order\ZeeloolEs();
                $prescriptions = ZeeloolEsPrescriptionDetailHelper::get_one_by_increment_id($incrementId);
                break;
            case 10:
                $this->model = new \app\admin\model\order\order\ZeeloolDe();
                $prescriptions = ZeeloolDePrescriptionDetailHelper::get_one_by_increment_id($incrementId);
                break;
            case 11:
                $this->model = new \app\admin\model\order\order\ZeeloolJp();
                $prescriptions = ZeeloolJpPrescriptionDetailHelper::get_one_by_increment_id($incrementId);
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
     * 获取订单地址及处方信息-新
     * @param string $increment_id 订单号
     * @param string $item_order_number 子单号
     * @author lzh
     * @return array|bool
     */
    public function getAddress($increment_id, $item_order_number='')
    {
        //获取地址信息
        $order_field = 'id,site,customer_email as email,customer_firstname as firstname,customer_lastname as lastname,order_type,country_id,region,region_id,city,street,postcode,telephone';
        $_new_order = new NewOrder();
        $address = $_new_order
            ->where('increment_id', $increment_id)
            ->field($order_field)
            ->find()
        ;
        empty($address) && exception('无此订单号，请查询后重试');

        //获取更改镜片sku集
        $showPrescriptions = [];
        $prescriptions = [];
        if($item_order_number){
            $prescription_field = 'a.sku,a.name,b.prescription_type,b.index_type,b.index_id,b.coating_id,b.color_id,b.od_sph,b.os_sph,b.od_cyl,b.os_cyl,b.od_axis,b.os_axis,b.pd_l,b.pd_r,b.pd,b.os_add,b.od_add,b.od_pv,b.os_pv,b.od_pv_r,b.os_pv_r,b.od_bd,b.os_bd,b.od_bd_r,b.os_bd_r';
            $_order_item_process = new NewOrderItemProcess();
            $prescriptions = $_order_item_process
                ->alias('a')
                ->field($prescription_field)
                ->where('a.item_order_number',$item_order_number)
                ->join(['fa_order_item_option' => 'b'], 'a.option_id=b.id')
                ->select()
            ;
            $prescriptions = collection($prescriptions)->toArray();
            empty($prescriptions) && exception('子订单不存在，请查询后重试');

            //增加默认数量
            array_walk($prescriptions, function (&$value, $k, $p) {
                $value = array_merge($value, $p);
            }, ['qty_ordered' => 1]);

            foreach ($prescriptions as $prescription) {
                $showPrescriptions[] = $prescription['prescription_type'] . '--' . $prescription['index_type'];
            }
        }

        return $address ? compact('address', 'prescriptions', 'showPrescriptions') : [];
    }

    /**
     * 获取修改处方-弃用
     * @param $siteType
     * @param $showPrescriptions
     * @return array|bool
     * @throws \think\Exception
     */
    public function getReissueLensOld($siteType, $showPrescriptions, $type = 1, $isNewVersion = 0)
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
     * 获取镜片、镀膜、颜色列表-新
     * @param int $siteType 网站类型
     * @param array $showPrescriptions 镜片类型列表
     * @param int $type 操作类型：1补发 2更改镜片 3赠品
     * @param string $item_order_number 子订单号
     * @author lzh
     * @return array
     * @throws \think\Exception
     */
    public function getReissueLens($siteType, $showPrescriptions, $type = 1, $item_order_number = '')
    {
        //从网站端获取镜片、镀膜、颜色等列表数据
        $cache_key = $siteType . '_get_lens';
        $data = Cache::get($cache_key);
        if (!$data) {
            $data = $this->httpRequest($siteType, 'magic/product/lensData');
            Cache::set($cache_key, $data, 3600 * 24);
        }

        //html页面所需变量
        $prescriptions = $coating_type = '';
        $prescription = $data['lens_list'];
        $colorList = $data['color_list'] ?? [];
        $coating_type = $data['coating_list'];

        $rendering = [
            'prescription',
            'coating_type',
            'prescriptions',
            'colorList',
            'type',
            'item_order_number'
        ];
        if (1 == $type) {
            foreach ($showPrescriptions as $key => $val) {
                $prescriptions .= "<option value='{$key}'>{$val}</option>";
            }
        } else {
            $rendering[] = 'showPrescriptions';
        }

        //拼接html页面
        $html = (new View)->fetch('saleaftermanage/work_order_list/ajax_reissue_add', compact($rendering));
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
            case -1://发货系统
                $url = config('url.delivery_url');
                break;
            case 1:
                $url = config('url.zeelool_url');
                break;
            case 2:
                $url = config('url.voogueme_url');
                break;
            case 3:
                $url = config('url.nihao_url');
                break;
            case 4:
                $url = config('url.meeloog_url');
                break;
            case 5:
                $url = config('url.wesee_url');
                break;
            case 9:
                $url = config('url.zeelooles_url');
                break;
            case 10:
                $url = config('url.zeeloolde_url');
                break;
            case 11:
                $url = config('url.zeelooljp_url');
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

            $status = -1 == $siteType ? $res['code'] : $res['status'];
            if (200 == $status) {
                return $res['data'];
            }

            exception($res['msg']);
        } catch (Exception $e) {
            exception($e->getMessage());
        }
    }
    
    /**
     * 更改地址-创建措施、保存地址信息
     * @param $params
     * @param $work_id
     * @throws \Exception
     */
    public function changeAddress($params, $work_id, $measure_choose_id, $measure_id)
    {
        $work = $this->find($work_id);
        //修改地址
        if ($work && 13 == $measure_choose_id) {
            //子单sku变动表
            $_work_order_change_sku = new WorkOrderChangeSku();

            //措施表
            $_work_order_measure = new WorkOrderMeasure();

            $_work_order_change_sku->startTrans();
            $_work_order_measure->startTrans();
            try {
                if (!$params['modify_address']['country_id']) {
                    exception('国家不能为空');
                }
                //查询是否有该地址
                $is_exist = $_work_order_change_sku->where(['measure_id' => $measure_id])->value('id');
                if(!$is_exist){
                    $data = [
                        'work_id' => $work_id,
                        'email' => $params['modify_address']['email'],
                        'userinfo_option' => serialize($params['modify_address']),
                        'increment_id' => $params['platform_order'],
                        'platform_type' => $params['work_platform'],
                        'change_type' => 6,
                        'measure_id' => $measure_id,
                        'create_person' => session('admin.nickname'),
                        'update_time' => date('Y-m-d H:i:s'),
                        'create_time' => date('Y-m-d H:i:s')
                    ];
                    $_work_order_change_sku->create($data);

                    $_work_order_measure->where(['id' => $measure_id])->update(['sku_change_type' => 6]);
                }else{
                    //更新
                    $data['email'] = $params['modify_address']['email'];
                    $data['userinfo_option'] = serialize($params['modify_address']);
                    $_work_order_change_sku->where(['work_id' => $work_id])->update($data);
                }

                $_work_order_change_sku->commit();
                $_work_order_measure->commit();
            } catch (\Exception $e) {
                $_work_order_change_sku->rollback();
                $_work_order_measure->rollback();
                exception($e->getMessage());
            }
        } 
    }

    /**
     * 更改地址-通知网站
     * @param object $work 工单数据
     * @param int $measure_id 工单措施表自增ID
     * @author lzh
     * @return bool
     * @throws \Exception
     */
    public function presentAddress($work, $measure_id)
    {
        $user_info_option = WorkOrderChangeSku::where(['measure_id' => $measure_id,'change_type' => 6])->value('userinfo_option');
        if(!$user_info_option) return false;

        //通知网站
        $changeAddress = unserialize($user_info_option);
        $postData = array(
            'increment_id'=>$work->platform_order,
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
        $this->httpRequest($work->work_platform, 'magic/order/editAddress', $postData, 'POST');

        //通知发货系统
        $shipData = [
            'site'=>$work->work_platform,
            'increment_id'=>$work->platform_order,
            'operate_user'=>session('admin.nickname'),
            'describe'=>'售后工单：修改地址',
            'work_order_id'=>$work->id
        ];
        $this->httpRequest(-1, 'index.php/admin/SelfApi/up_address_ship', $shipData, 'POST');

        return true;
    }

    /**
     * 更改镜片，赠品 - 弃用
     * @param $params
     * @param $work_id
     * @throws \Exception
     */
    public function changeLensOld($params, $work_id, $measure_choose_id, $measure_id)
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
                        'original_sku' => trim($changeLens['original_sku'][$key]),
                        'original_number' => intval($changeLens['original_number'][$key]),
                        'change_type' => $change_type,
                        'change_sku' => trim($changeLens['original_sku'][$key]),
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
     * 更改镜片、赠品、补发新增sku表数据 - 新
     *
     * @param array $params 页面传参
     * @param int $work_id 工单ID
     * @param int $measure_choose_id 措施配置表ID
     * @param int $measure_id 措施ID
     * @param string $item_order_number 子单号
     * @author lzh
     * @throws \Exception
     */
    public function changeLens($params, $work_id, $measure_choose_id, $measure_id,$item_order_number)
    {
        $work = $this->find($work_id);
        if ($work && in_array($measure_choose_id,[6,7,20])) {
            //措施表
            $_work_order_measure = new WorkOrderMeasure();

            //子单sku变动表
            $_work_order_change_sku = new WorkOrderChangeSku();

            $_work_order_measure->startTrans();
            $_work_order_change_sku->startTrans();
            try {
                $platform_type = $params['work_platform'];
                $platform_order = $params['platform_order'];
                $admin_id = session('admin.nickname');
                $time = date('Y-m-d H:i:s');

                //修改镜片
                if (20 == $measure_choose_id) {
                    $changeLens = $params['item_order_info'][$item_order_number]['change_lens'];
                    $change_type = 2;

                    $lensId = $changeLens['lens_type'];
                    $colorId = $changeLens['color_id'];
                    $coatingId = $changeLens['coating_type'];
                    $recipe_type = $changeLens['recipe_type'];
                    !$recipe_type && exception('请选择处方类型');

                    //获取镜片、镀膜等名称
                    $lensCoatName = $this->getLensCoatingName($platform_type, $lensId, $coatingId, $colorId, $recipe_type);

                    //镜片、镀膜序列化信息
                    $prescriptionOption = [
                        'prescription_type' => $recipe_type,
                        'lens_id' => $lensId,
                        'lens_name' => $lensCoatName['lensName'] ?? '',
                        'lens_type' => $lensCoatName['lensType'] ?? '',
                        'coating_id' => $coatingId,
                        'coating_name' => $lensCoatName['coatingName'] ?? '',
                        'color_id' => $colorId,
                        'color_name' => $lensCoatName['colorName'] ?? ''
                    ];

                    //从网站接口获取镜片编码、文案、语种文案
                    $lens_number = '';
                    $web_lens_name = '';
                    if($lensId){
                        $postData = [
                            'sku'=>trim($changeLens['original_sku']),
                            'prescription_type' => $recipe_type,
                            'lens_id' => $lensId,
                            'coating_id' => $coatingId,
                            'color_id' => $colorId
                        ];
                        $lens_info = $this->httpRequest($work->work_platform, 'magic/product/lenInfo', $postData, 'POST');
                        $lens_number = $lens_info['lens_number'] ?: '';
                        $web_lens_name = $lens_info['lens_name'] ?: '';
                    }

                    $data = [
                        'email' => '',
                        'prescription_option' => serialize($prescriptionOption),
                        'userinfo_option' => '',
                        'work_id' => $work_id,
                        'item_order_number' => $item_order_number,
                        'increment_id' => $platform_order,
                        'platform_type' => $platform_type,
                        'original_name' => $changeLens['original_name'] ?? '',
                        'original_sku' => trim($changeLens['original_sku']),
                        'original_number' => intval($changeLens['original_number']),
                        'change_type' => $change_type,
                        'change_sku' => trim($changeLens['original_sku']),
                        'change_number' => intval($changeLens['original_number']),
                        'recipe_type' => $recipe_type,
                        'lens_number' => $lens_number,
                        'web_lens_name' => $web_lens_name,
                        'lens_type' => $lensCoatName['lensName'] ?? '',
                        'coating_type' => $lensCoatName['coatingName'] ?? '',
                        'od_sph' => $changeLens['od_sph'] ?? '',
                        'od_cyl' => $changeLens['od_cyl'] ?? '',
                        'od_axis' => $changeLens['od_axis'] ?? '',
                        'od_add' => $changeLens['od_add'] ?? '',
                        'pd_r' => $changeLens['pd_r'] ?? '',
                        'od_pv' => $changeLens['od_pv'] ?? '',
                        'od_bd' => $changeLens['od_bd'] ?? '',
                        'od_pv_r' => $changeLens['od_pv_r'] ?? '',
                        'od_bd_r' => $changeLens['od_bd_r'] ?? '',
                        'os_sph' => $changeLens['os_sph'] ?? '',
                        'os_cyl' => $changeLens['os_cyl'] ?? '',
                        'os_axis' => $changeLens['os_axis'] ?? '',
                        'os_add' => $changeLens['os_add'] ?? '',
                        'pd_l' => $changeLens['pd_l'] ?? '',
                        'os_pv' => $changeLens['os_pv'] ?? '',
                        'os_bd' => $changeLens['os_bd'] ?? '',
                        'os_pv_r' => $changeLens['os_pv_r'] ?? '',
                        'os_bd_r' => $changeLens['os_bd_r'] ?? '',
                        'measure_id' => $measure_id,
                        'create_person' => $admin_id,
                        'update_time' => $time,
                        'create_time' => $time
                    ];

                    //新增sku变动数据
                    $_work_order_change_sku->create($data);

                    //标记措施表更改类型
                    $_work_order_measure->where(['id' => $measure_id])->update(['sku_change_type' => $change_type]);
                }else{
                    if (6 == $measure_choose_id) { //赠品
                        $changeLens = $params['gift'];
                        $change_type = 4;
                    } else { //补发
                        !$params['address']['shipping_type'] && exception('请选择运输方式');
                        !$params['address']['country_id'] && exception('请选择国家');

                        $changeLens = $params['replacement'];
                        $change_type = 5;
                    }
                    (!is_array($changeLens['original_sku']) || empty($changeLens['original_sku'])) && exception('sku不能为空');

                    //循环插入数据
                    $original_sku = array_filter(array_unique($changeLens['original_sku']));
                    foreach ($original_sku as $key => $val) {
                        $lensId = $changeLens['lens_type'][$key];
                        $colorId = $changeLens['color_id'][$key];
                        $coatingId = $changeLens['coating_type'][$key];
                        $recipe_type = $changeLens['recipe_type'][$key];
                        !$recipe_type && exception('请选择处方类型');

                        //获取镜片、镀膜等名称
                        $lensCoatName = $this->getLensCoatingName($platform_type, $lensId, $coatingId, $colorId, $recipe_type);

                        //镜片、镀膜序列化信息
                        $prescriptionOption = [
                            'prescription_type' => $recipe_type,
                            'lens_id' => $lensId,
                            'lens_name' => $lensCoatName['lensName'] ?? '',
                            'lens_type' => $lensCoatName['lensType'] ?? '',
                            'coating_id' => $coatingId,
                            'coating_name' => $lensCoatName['coatingName'] ?? '',
                            'color_id' => $colorId,
                            'color_name' => $lensCoatName['colorName'] ?? '',
                        ];

                        $data = [
                            'email' => $params['address']['email'],
                            'prescription_option' => serialize($prescriptionOption),
                            'userinfo_option' => serialize($params['address']),
                            'work_id' => $work_id,
                            'increment_id' => $platform_order,
                            'platform_type' => $platform_type,
                            'original_name' => $changeLens['original_name'][$key] ?? '',
                            'original_sku' => trim($changeLens['original_sku'][$key]),
                            'original_number' => intval($changeLens['original_number'][$key]),
                            'change_type' => $change_type,
                            'change_sku' => trim($changeLens['original_sku'][$key]),
                            'change_number' => intval($changeLens['original_number'][$key]),
                            'recipe_type' => $recipe_type,
                            'lens_type' => $lensCoatName['lensName'] ?? '',
                            'coating_type' => $lensCoatName['coatingName'] ?? '',
                            'od_sph' => $changeLens['od_sph'][$key] ?? '',
                            'od_cyl' => $changeLens['od_cyl'][$key] ?? '',
                            'od_axis' => $changeLens['od_axis'][$key] ?? '',
                            'od_add' => $changeLens['od_add'][$key] ?? '',
                            'pd_r' => $changeLens['pd_r'][$key] ?? '',
                            'od_pv' => $changeLens['od_pv'][$key] ?? '',
                            'od_bd' => $changeLens['od_bd'][$key] ?? '',
                            'od_pv_r' => $changeLens['od_pv_r'][$key] ?? '',
                            'od_bd_r' => $changeLens['od_bd_r'][$key] ?? '',
                            'os_sph' => $changeLens['os_sph'][$key] ?? '',
                            'os_cyl' => $changeLens['os_cyl'][$key] ?? '',
                            'os_axis' => $changeLens['os_axis'][$key] ?? '',
                            'os_add' => $changeLens['os_add'][$key] ?? '',
                            'pd_l' => $changeLens['pd_l'][$key] ?? '',
                            'os_pv' => $changeLens['os_pv'][$key] ?? '',
                            'os_bd' => $changeLens['os_bd'][$key] ?? '',
                            'os_pv_r' => $changeLens['os_pv_r'][$key] ?? '',
                            'os_bd_r' => $changeLens['os_bd_r'][$key] ?? '',
                            'measure_id' => $measure_id,
                            'create_person' => $admin_id,
                            'update_time' => $time,
                            'create_time' => $time
                        ];

                        //新增sku变动数据
                        $_work_order_change_sku->create($data);

                        //标记措施表更改类型
                        $_work_order_measure->where(['id' => $measure_id])->update(['sku_change_type' => $change_type]);
                    }
                }

                $_work_order_measure->commit();
                $_work_order_change_sku->commit();
            } catch (\Exception $e) {
                $_work_order_measure->rollback();
                $_work_order_change_sku->rollback();
                exception($e->getMessage());
            }
        }
    }
    /**
     * 插入更换镜框数据 - 弃用
     *
     * @Description
     * @author lsw
     * @since 2020/04/23 17:02:32
     * @return void
     */
    public function changeFrameOld($params, $work_id, $measure_choose_id, $measure_id)
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

    /**
     * 更换镜框 - 新
     *
     * @param array $params 页面传参
     * @param int $work_id 工单ID
     * @param int $measure_choose_id 措施配置表ID
     * @param int $measure_id 措施ID
     * @param string $item_order_number 子单号
     * @Description
     * @author lzh
     * @return mixed
     */
    public function changeFrame($params, $work_id, $measure_choose_id, $measure_id, $item_order_number)
    {
        $work = $this->find($work_id);
        if ($work && 19 == $measure_choose_id) {
            $change_frame = $params['item_order_info'][$item_order_number]['change_frame'];
            empty($change_frame) && exception("请完善更改镜框信息！！");

            //插入更换镜框数据
            $orderChangeData = [
                'work_id'=>$work_id,
                'item_order_number'=>$item_order_number,
                'increment_id'=>$params['platform_order'],
                'platform_type'=>$params['work_platform'],
                'original_sku'=>$change_frame['original_sku'],
                'original_number'=>$change_frame['original_number'],
                'change_sku'=>$change_frame['change_sku'],
                'change_number'=>$change_frame['change_number'],
                'change_type'=>1,
                'measure_id'=>$measure_id,
                'create_person'=>session('admin.nickname'),
                'create_time'=>date('Y-m-d H:i:s'),
                'update_time'=>date('Y-m-d H:i:s'),
            ];
            $orderChangeRes = (new WorkOrderChangeSku())->save($orderChangeData);
            false === $orderChangeRes && exception("更换镜框添加失败！！");

            //标记措施表更改类型
            WorkOrderMeasure::where(['id' => $measure_id])->update(['sku_change_type' => 1]);
        }
    }

    /**
     * 取消操作 - 弃用
     *
     * @Description
     * @author lsw
     * @return mixed
     */
    public function cancelOrderOld($params, $work_id, $measure_choose_id, $measure_id)
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
     * 取消操作 - 新
     *
     * @param array $params 页面传参
     * @param int $work_id 工单ID
     * @param int $measure_choose_id 措施配置表ID
     * @param int $measure_id 措施ID
     * @param string $item_order_number 子单号
     * @Description
     * @author lzh
     * @return mixed
     */
    public function cancelOrder($params, $work_id, $measure_choose_id, $measure_id, $item_order_number)
    {
        $work = $this->find($work_id);
        if ($work && in_array($measure_choose_id,[3,18])) {
            $orderChangeList = [];
            if (3 == $measure_choose_id) {//主单取消
                $_new_order_item_process = new NewOrderItemProcess();
                $item_order_list = $_new_order_item_process
                    ->alias('a')
                    ->field('a.item_order_number,a.sku')
                    ->join(['fa_order' => 'b'], 'a.order_id=b.id')
                    ->where(['a.distribution_status'=>['>',0],'b.increment_id'=>$params['platform_order']])
                    ->select()
                ;
                if($item_order_list){
                    foreach ($item_order_list as $v) {
                        $orderChangeList[] = [
                            'work_id'=>$work_id,
                            'increment_id'=>$params['platform_order'],
                            'platform_type'=>$params['work_platform'],
                            'item_order_number'=>$v['item_order_number'],
                            'original_sku'=>$v['sku'],
                            'original_number'=>1,
                            'change_type'=>3,
                            'measure_id'=>$measure_id,
                            'create_person'=>session('admin.nickname'),
                            'create_time'=>date('Y-m-d H:i:s'),
                            'update_time'=>date('Y-m-d H:i:s')
                        ];
                    }
                }
            }else{//子单取消
                $orderChangeList[] = [
                    'work_id'=>$work_id,
                    'increment_id'=>$params['platform_order'],
                    'platform_type'=>$params['work_platform'],
                    'item_order_number'=>$item_order_number,
                    'original_sku'=>$params['item_order_info'][$item_order_number]['cancel_order']['sku'],
                    'original_number'=>1,
                    'change_type'=>3,
                    'measure_id'=>$measure_id,
                    'create_person'=>session('admin.nickname'),
                    'create_time'=>date('Y-m-d H:i:s'),
                    'update_time'=>date('Y-m-d H:i:s')
                ];
            }
            empty($orderChangeList) && exception("取消失败：子单号数据不存在");

            $cancelOrderRes = (new WorkOrderChangeSku())->saveAll($orderChangeList);
            false === $cancelOrderRes && exception("取消失败！！");

            //标记措施表更改类型
            WorkOrderMeasure::where(['id' => $measure_id])->update(['sku_change_type' => 3]);
        }
    }

    /**
     * 根据id获取镜片，镀膜的名称
     * @param int $siteType 网站类型
     * @param int $lens_id 镜片ID
     * @param int $coating_id 镀膜ID
     * @param int $color_id 颜色ID
     * @param string $prescription_type 处方类型
     * @author lzh
     * @return array
     */
    public function getLensCoatingName($siteType, $lens_id, $coating_id, $color_id, $prescription_type)
    {
        $key = $siteType . '_get_lens';
        $data = Cache::get($key);
        if (!$data) {
            $data = $this->httpRequest($siteType, 'magic/product/lensData');
            Cache::set($key, $data, 3600 * 24);
        }
        $prescription = $data['lens_list'];
        $coatingLists = $data['coating_list'];
        $colorList = $data['color_list'] ?? [];

        //返回lensName
        $lens = $prescription[$prescription_type] ?? [];       
        $lensName = $coatingName = $colorName = $lensType = '';

        //lensName
        if ($lens_id) {
            foreach ($lens as $len) {
                if ($len['lens_id'] == $lens_id) {
                    $lensName = $len['lens_data_name'];
                    $lensType = $len['lens_data_index'];
                    break;
                }
            }
        }

        //colorName
        if ($color_id) {
            foreach ($colorList as $val) {
                if ($val['lens_id'] == $color_id) {
                    $colorName = $val['lens_data_name'];
                    break;
                }
            }
        }

        //coatingName
        if ($coating_id) {
            foreach ($coatingLists as $coatingList) {
                if ($coatingList['coating_id'] == $coating_id) {
                    $coatingName = $coatingList['coating_name'];
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
    public function createOrder($siteType, $work_id)
    {
        $changeSkuList = WorkOrderChangeSku::where(['work_id' => $work_id, 'change_type' => 5])->select();
        //如果存在补发单的措施
        if ($changeSkuList) {
            $postData = $postDataCommon = [];
            $measure_id = 0;
            foreach ($changeSkuList as $key => $changeSku) {
                if(!empty($changeSku['replacement_order'])){
                    continue;
                }
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
                $pdCheck = $pd = $prismCheck = '';
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
                    $prismCheck = 'on';
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
                    'prismcheck' => $prismCheck,
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
            if(!empty($postData)){
                try {
                    $res = $this->httpRequest($siteType, 'magic/order/createOrder', $postData, 'POST');
                    $increment_id = $res['increment_id'];

                    //添加补发的订单号
                    WorkOrderChangeSku::where(['work_id' => $work_id, 'change_type' => 5])->setField('replacement_order', $increment_id);
                    self::where(['id' => $work_id])->setField('replacement_order', $increment_id);
    
                    //补发扣库存
                    $this->deductionStock($work_id, $measure_id);
                } catch (Exception $e) {
                    exception($e->getMessage());
                }
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
    public function getEditReissueLensOld($siteType, $showPrescriptions, $type = 1, $info = [], $operate_type = '',$is_new_version = 0)
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
     * 获取修改处方(编辑的时候带出存储的信息)
     * @param int $siteType 网站类型
     * @param array $showPrescriptions 镜片类型列表
     * @param int $type 操作类型：1补发 2更改镜片 3赠品
     * @param array $info
     * @param string $operate_type
     * @param string $item_order_number 子订单号
     * @author lzh
     * @return array|bool
     * @throws \think\Exception
     */
    public function getEditReissueLens($siteType, $showPrescriptions, $type = 1, $info = [], $operate_type = '',$item_order_number = '')
    {
        //从网站端获取镜片、镀膜、颜色等列表数据
        $cache_key = $siteType . '_get_lens';
        $data = Cache::get($cache_key);
        if (!$data) {
            $data = $this->httpRequest($siteType, 'magic/product/lensData');
            Cache::set($cache_key, $data, 3600 * 24);
        }

        //html页面所需变量
        $prescriptions = $coating_type = '';
        $prescription = $data['lens_list'];
        $colorList = $data['color_list'] ?? [];
        $coating_type = $data['coating_list'];

        $rendering = [
            'prescription',
            'coating_type',
            'prescriptions',
            'colorList',
            'type',
            'info',
            'operate_type',
            'item_order_number'
        ];
        if (1 == $type) {
            foreach ($showPrescriptions as $key => $val) {
                $prescriptions .= "<option value='{$key}'>{$val}</option>";
            }
        } else {
            $rendering[] = 'showPrescriptions';
        }

        //拼接html页面
        $html = (new View)->fetch('saleaftermanage/work_order_list/ajax_reissue_edit', compact($rendering));
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

        !$work && exception("工单不存在！！");

        //判断是否已审核
        if ($work->check_time) return true;

        //工单备注表
        $_work_order_remark = new WorkOrderRemark();

        $work->startTrans();
        $_work_order_remark->startTrans();
        try {
            $time = date('Y-m-d H:i:s');
            $admin_id = session('admin.id');

            //获取工单承接列表
            $orderRecepts = WorkOrderRecept::where('work_id', $work_id)->select();
            $count = count($orderRecepts);

            //不需要审核
            if ( 0 == $work->is_check ) {
                //客服工单或仓库工单经手人已处理，自动审核通过
                if ( 1 == $work->work_type || (2 == $work->work_type && 1 == $work->is_after_deal_with) ) {
                    $work->check_note = '系统自动审核通过';
                    $work->check_time = $time;
                    $key = 0;
                    foreach ($orderRecepts as $orderRecept) {
                        //获取措施配置表ID
                        $measure_choose_id = WorkOrderMeasure::where('id', $orderRecept->measure_id)->value('measure_choose_id');

                        //已处理
                        if (0 < $orderRecept->recept_status) {
                            $key++;
                        }else{
                            //无需审核并且审核后自动完成，直接处理优惠券、补价、积分等流程
                            if (1 == $orderRecept->is_auto_complete) {
                                $this->follow_up($orderRecept,$measure_choose_id,$work);
                                $key++;
                            }
                        }
                    }
                    if ($count == $key) {
                        //处理完成
                        $work_status = 6;
                        $work->complete_time = $time;

                        //检测是否标记异常，有则修改为已处理
                        $res = $this->handle_abnormal($work);
                        if(!$res['result']) throw new Exception($res['msg']);
                    } elseif ($key > 0 && $count > $key) {
                        //部分处理
                        $work_status = 5;
                    } else {
                        //审核成功
                        $work_status = 3;
                    }
                    $work->work_status = $work_status;
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
                    $_work_order_remark->create($remarkData);
                }
            }else{
                //需要审核
                if (!empty($params)) {
                    $work->operation_user_id = $admin_id;
                    $work->check_note = $params['check_note'];
                    $work->check_time = $time;
                    $key = 0;
                    foreach ($orderRecepts as $orderRecept) {
                        //查找措施的id
                        $measure_choose_id = WorkOrderMeasure::where('id', $orderRecept->measure_id)->value('measure_choose_id');

                        //已处理
                        if (0 < $orderRecept->recept_status) {
                            $key++;
                        }else{
                            //点击审核成功并且审核后自动完成，直接处理优惠券、补价、积分等流程
                            if (1 == $orderRecept->is_auto_complete && 1 == $params['success']) {
                                $this->follow_up($orderRecept,$measure_choose_id,$work);
                                $key++;
                            }
                        }
                    }

                    //审核拒绝
                    if ($params['success'] == 2) {
                        $work_status = 4;

                        //配货异常表
                        $_distribution_abnormal = new DistributionAbnormal();

                        //获取工单关联未处理异常数据
                        $item_process_ids = $_distribution_abnormal
                            ->where(['work_id' => $work->id, 'status' => 1])
                            ->column('item_process_id')
                        ;
                        if($item_process_ids){
                            //异常标记为已处理
                            $_distribution_abnormal
                                ->allowField(true)
                                ->save(
                                    ['status' => 2, 'do_time' => time(), 'do_person' => session('admin.nickname')],
                                    ['work_id' => $work->id, 'status' => 1]
                                );

                            //获取异常库位id集
                            $_new_order_item_process = new NewOrderItemProcess();
                            $abnormal_house_ids = $_new_order_item_process
                                ->field('abnormal_house_id')
                                ->where(['id' => ['in',$item_process_ids]])
                                ->select()
                            ;
                            if($abnormal_house_ids){
                                //异常库位号占用数量减1
                                $_stock_house = new StockHouse();
                                foreach($abnormal_house_ids as $v){
                                    $_stock_house
                                        ->where(['id' => $v['abnormal_house_id']])
                                        ->setDec('occupy', 1)
                                    ;
                                }
                            }

                            //解绑子订单的异常库位ID
                            $_new_order_item_process
                                ->allowField(true)
                                ->save(['abnormal_house_id' => 0],['id' => ['in',$item_process_ids]])
                            ;

                            //配货操作日志
                            DistributionLog::record((object)session('admin'),$item_process_ids,10,"工单审核拒绝，异常标记为已处理");
                        }
                    }else{//审核成功
                        if ($count == $key) {
                            //处理完成
                            $work_status = 6;
                            $work->complete_time = $time;

                            //检测是否标记异常，有则修改为已处理
                            $res = $this->handle_abnormal($work);
                            if(!$res['result']) throw new Exception($res['msg']);
                        } elseif ($key > 0  && $count > $key) {
                            //部分处理
                            $work_status = 5;
                        } else {
                            //审核成功
                            $work_status = 3;
                        }
                    }
                    $work->work_status = $work_status;
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
                    $_work_order_remark->create($remarkData);
                    //通知
                    //Ding::cc_ding(explode(',', $work->recept_person_id), '', '工单ID：' . $work->id . '😎😎😎😎有新工单需要你处理😎😎😎😎', '有新工单需要你处理');
                }
            }

            $work->commit();
            $_work_order_remark->commit();
        } catch (Exception $e) {
            $work->rollback();
            $_work_order_remark->rollback();
            exception($e->getMessage());
        }
    }

    /**
     * 工单绑定有异常则修改为已处理
     * @param object $work 工单表数据
     * @author lzh
     * @return array
     */
    public function handle_abnormal($work){
        //检测是否有标记异常
        $_distribution_abnormal = new DistributionAbnormal();
        $_new_order_process = new NewOrderProcess();
        $_new_order_item_process = new NewOrderItemProcess();
        $_stock_house = new StockHouse();
        $item_process_ids = $_distribution_abnormal
            ->where(['work_id' => $work->id, 'status' => 1])
            ->column('item_process_id')
        ;

        $_distribution_abnormal->startTrans();
        $_stock_house->startTrans();
        $_new_order_item_process->startTrans();
        $_new_order_process->startTrans();
        try {
            if($item_process_ids){
                //异常标记为已处理
                $_distribution_abnormal
                    ->allowField(true)
                    ->save(['status' => 2, 'do_time' => time(), 'do_person' => session('admin.nickname')],['work_id' => $work->id, 'status' => 1])
                ;

                //获取异常库位id集
                $abnormal_house_ids = $_new_order_item_process
                    ->field('abnormal_house_id')
                    ->where(['id' => ['in',$item_process_ids]])
                    ->select()
                ;
                if($abnormal_house_ids){
                    //异常库位号占用数量减1
                    $_stock_house = new StockHouse();
                    foreach($abnormal_house_ids as $v){
                        $_stock_house
                            ->where(['id' => $v['abnormal_house_id']])
                            ->setDec('occupy', 1)
                        ;
                    }
                }

                //解绑子订单的异常库位ID
                $_new_order_item_process
                    ->allowField(true)
                    ->save(['abnormal_house_id' => 0],['id' => ['in',$item_process_ids]])
                ;

                //配货操作日志
                DistributionLog::record((object)session('admin'),$item_process_ids,10,"工单处理完成，异常标记为已处理");
            }

            //获取取消子单号合集
            $cancel_order_number = (new WorkOrderChangeSku)
                ->alias('a')
                ->join(['fa_work_order_measure' => 'b'], 'a.measure_id=b.id')
                ->where([
                    'a.change_type'=>3,
                    'a.work_id'=>$work->id,
                    'b.operation_type'=>1
                ])
                ->column('a.item_order_number')
            ;
            if($cancel_order_number){
                $item_process_list = $_new_order_item_process
                    ->field('id,order_id,distribution_status')
                    ->where(['item_order_number' => ['in',$cancel_order_number]])
                    ->select()
                ;
                $item_process_list = collection($item_process_list)->toArray();
                if($item_process_list){
                    $order_id = array_column($item_process_list,'order_id')[0];//订单ID
                    $item_process_ids = array_column($item_process_list,'id');//子单ID

                    //获取本次取消子单中状态为合单中的数量
                    $combine_list = array_filter(array_column($item_process_list,'distribution_status'), function ($v) {
                        if (8 == $v) {
                            return $v;
                        }
                    });
                    $combine_count = count($combine_list);
                    if($combine_count){
                        //获取库位ID
                        $store_house_id = $_new_order_process->where(['order_id'=>$order_id])->value('store_house_id');
                        if($store_house_id){
                            //获取整单合单中状态的子单数量
                            $check_count = $_new_order_item_process
                                ->where(['order_id'=>$order_id,'distribution_status' => 8])
                                ->count()
                            ;

                            //如果整单都没有合单中的子单，则释放合单库位
                            if(($check_count - $combine_count) < 1){
                                //释放合单库位ID
                                $_new_order_process->allowField(true)->isUpdate(true, ['order_id'=>$order_id])->save(['store_house_id'=>0]);

                                //释放合单库位占用数量
                                $_stock_house->allowField(true)->isUpdate(true, ['id'=>$store_house_id])->save(['occupy'=>0]);
                            }
                        }
                    }

                    //标记子单号状态为取消
                    $_new_order_item_process
                        ->allowField(true)
                        ->save(['distribution_status'=>0], ['id' => ['in',$item_process_ids]])
                    ;

                    //配货操作日志
                    DistributionLog::record((object)session('admin'),$item_process_ids,10,"工单处理完成，子单取消");
                }
            }

            $_distribution_abnormal->commit();
            $_stock_house->commit();
            $_new_order_process->commit();
            $_new_order_item_process->commit();
        } catch (PDOException $e) {
            $_distribution_abnormal->rollback();
            $_stock_house->rollback();
            $_new_order_process->rollback();
            $_new_order_item_process->rollback();
            return ['result'=>false,'msg'=>$e->getMessage()];
        } catch (Exception $e) {
            $_distribution_abnormal->rollback();
            $_stock_house->rollback();
            $_new_order_process->rollback();
            $_new_order_item_process->rollback();
            return ['result'=>false,'msg'=>$e->getMessage()];
        }

        return ['result'=>true,'msg'=>''];
    }

    /**
     * 审核成功后自动完成处理相关流程
     * @param object $orderRecept 承接表数据
     * @param int $measure_choose_id 措施配置表ID
     * @param object $work 工单表数据
     * @author lzh
     * @return bool
     */
    public function follow_up($orderRecept,$measure_choose_id,$work){
        //承接表标记已处理
        WorkOrderRecept::where('id', $orderRecept->id)->update(['recept_status' => 1, 'finish_time' => date('Y-m-d H:i:s'), 'note' => '自动处理完成']);

        //工单措施标记已处理
        WorkOrderMeasure::where('id', $orderRecept->measure_id)->update(['operation_type' => 1, 'operation_time' => date('Y-m-d H:i:s')]);

        //补发
        if(7 == $measure_choose_id){
            $this->createOrder($work->work_platform, $work->id);
        }elseif(9 == $measure_choose_id){//发送优惠券
            $this->presentCoupon($work->id);
        }elseif(10 == $measure_choose_id){//赠送积分
            $this->presentIntegral($work->id);
        }elseif(13 == $measure_choose_id){//修改地址
            $this->presentAddress($work, $orderRecept->measure_id);
        }

        //措施不是补发的时候扣减库存，因为补发的时候库存已经扣减过了
        if (7 != $measure_choose_id){
            $this->deductionStock($work->id, $orderRecept->measure_id);
        }

        return true;
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
     * @return boolean
     * @author lsw
     * @since 2020/04/21 10:13:28
     */
    public function handleRecept($id, $work_id, $measure_id, $recept_group_id, $success, $process_note,$is_auto_complete)
    {
        $work = self::find($work_id);

        //承接人表
        $_work_order_recept = new WorkOrderRecept();

        //措施表
        $_work_order_measure = new WorkOrderMeasure();

        $_work_order_recept->startTrans();
        $_work_order_measure->startTrans();
        $this->startTrans();
        try {
            //更新本条工单数据承接人状态
            $data = [
                'recept_status'=> 1 == $success ? 1 : 2,
                'note'=> $process_note,
                'finish_time'=> date('Y-m-d H:i:s')
            ];
            $resultInfo = $_work_order_recept->where(['id' => $id])->update($data);

            $measure_choose_id = $_work_order_measure->where('id',$measure_id)->value('measure_choose_id');
            //不是自动处理完成
            if(1 != $is_auto_complete){
                //补发
                if(7 == $measure_choose_id){
                    $this->createOrder($work->work_platform, $work->id);
                }elseif(9 == $measure_choose_id){//发送优惠券
                    $this->presentCoupon($work->id);
                }elseif(10 == $measure_choose_id){//赠送积分
                    $this->presentIntegral($work->id);
                }elseif(13 == $measure_choose_id){//修改地址
                    $this->presentAddress($work, $measure_id);
                }
            }

            //措施不是补发的时候扣减库存，是补发的时候不扣减库存，因为补发的时候库存已经扣减过了
            if ($resultInfo && 1 == $data['recept_status'] && 7 != $measure_choose_id){
                $this->deductionStock($work_id, $measure_id);
            }

            //删除同样的承接组数据
            $where = [
                'work_id'=>$work_id,
                'measure_id'=>$measure_id,
                'recept_group_id'=>$recept_group_id,
                'recept_status'=>0,
            ];
            $_work_order_recept->where($where)->delete();

            //如果是处理失败的状态
            $dataMeasure = [
                'operation_type'=>1 == $data['recept_status'] ? 1 : 2,
                'operation_time'=>date('Y-m-d H:i:s')
            ];
            $_work_order_measure->where(['id' => $measure_id])->update($dataMeasure);

            //求出承接措施是否完成
            $whereMeasure = [
                'work_id'=>$work_id,
                'recept_status'=>0
            ];
            $resultRecept = $_work_order_recept->where($whereMeasure)->count();

            //表明整个措施已经完成
            if (0 == $resultRecept) {
                //求出整个工单的措施状态
                $whereWork['work_id'] = $work_id;
                $whereWork['operation_type'] = ['eq', 0];
                $resultMeasure = $_work_order_measure->where($whereWork)->count();
                if (0 == $resultMeasure) {
                    $dataWorkOrder['work_status'] = 6;
                    $dataWorkOrder['complete_time'] = date('Y-m-d H:i:s');

                    //检测是否标记异常，有则修改为已处理
                    $res = $this->handle_abnormal($work);
                    if(!$res['result']) throw new Exception($res['msg']);

                    //通知
                    //Ding::cc_ding(explode(',', $work->create_user_id), '', '工单ID：' . $work->id . '😎😎😎😎工单已处理完成😎😎😎😎',  '😎😎😎😎工单已处理完成😎😎😎😎');
                } else {
                    $dataWorkOrder['work_status'] = 5;
                }
            }else{
                $dataWorkOrder['work_status'] = 5;
            }
            $this->where(['id' => $work_id])->update($dataWorkOrder);

            $_work_order_recept->commit();
            $_work_order_measure->commit();
            $this->commit();
        } catch (PDOException $e) {
            $_work_order_recept->rollback();
            $_work_order_measure->rollback();
            $this->rollback();
            exception($e->getMessage());
        }catch (Exception $e) {
            $_work_order_recept->rollback();
            $_work_order_measure->rollback();
            $this->rollback();
            exception($e->getMessage());
        }
        return true;
    }
    //扣减库存逻辑
    public function deductionStock($work_id, $measure_id)
    {
        $measuerInfo = WorkOrderMeasure::where(['id' => $measure_id])->value('sku_change_type');
        if ($measuerInfo < 1) {
            return false;
        }
        $workOrderList = WorkOrderList::where(['id' => $work_id])->field('id,work_platform,platform_order,replacement_order')->find();
        //如果措施是补发但是没有生成补发单的话不扣减库存
        if(($measuerInfo == 5) && (empty($workOrderList->replacement_order))){
            return false;
        }
        $whereMeasure['work_id'] = $work_id;
        $whereMeasure['measure_id'] = $measure_id;
        $whereMeasure['change_type'] = $measuerInfo;
        $result = WorkOrderChangeSku::where($whereMeasure)->field('id,increment_id,platform_type,change_type,original_sku,original_number,change_sku,change_number,item_order_number')->select();
        if (!$result) {
            return false;
        }
        
        $result = collection($result)->toArray();
        if (1 == $measuerInfo) { //更改镜架
            $info = (new Inventory())->workChangeFrame($work_id, $workOrderList->work_platform, $workOrderList->platform_order, $result);
        } elseif (3 == $measuerInfo) { //取消订单
            $info = (new Inventory())->workCancelOrder($work_id, $workOrderList->work_platform, $workOrderList->platform_order, $result);
        } elseif (4 == $measuerInfo) { //赠品
            $info = (new Inventory())->workPresent($work_id, $workOrderList->work_platform, $workOrderList->platform_order, $result, 1);
        } elseif (5 == $measuerInfo) {//补发
            $info = (new Inventory())->workPresent($work_id, $workOrderList->work_platform, $workOrderList->platform_order, $result, 2);
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
                case 4:
                    $db = 'database.db_meeloog';
                    break;
                case 9:
                    $db = 'database.db_zeelool_es';
                    break;
                case 10:
                    $db = 'database.db_zeelool_de';
                    break;
                case 11:
                    $db = 'database.db_zeelool_jp';
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
    /**
     * 客服数据大屏 -- 工单概况
     *
     * @Description
     * @author mjj
     * @since 2020/07/23 18:06:03 
     * @return void
     */
    public function workorder_situation($platform){
        //今天的工单数据统计
        $today_startdate = date('Y-m-d');
        $today_enddate = date('Y-m-d', strtotime("+1 day"));
        $today = array(
            'wo_num' => $this->wo_sum($today_startdate,$today_enddate,$platform,1),
            'wo_complete_num' => $this->wo_complete_num($today_startdate,$today_enddate,$platform,1),
            'wo_bufa_percent' => $this->wo_bufa_percent($today_startdate,$today_enddate,$platform,1),
            'wo_refund_percent' => $this->wo_refund_percent($today_startdate,$today_enddate,$platform,1),
            'wo_refund_money_percent' => $this->wo_refund_money_percent($today_startdate,$today_enddate,$platform,1),
        );
        //昨天的工单数据统计
        $yesterday_startdate = date("Y-m-d", strtotime("-1 day"));
        $yesterday_enddate = date("Y-m-d");
        $yesterday = array(
            'wo_num' => $this->wo_sum($yesterday_startdate,$yesterday_enddate,$platform,1),
            'wo_complete_num' => $this->wo_complete_num($yesterday_startdate,$yesterday_enddate,$platform,1),
            'wo_bufa_percent' => $this->wo_bufa_percent($yesterday_startdate,$yesterday_enddate,$platform,1),
            'wo_refund_percent' => $this->wo_refund_percent($yesterday_startdate,$yesterday_enddate,$platform,1),
            'wo_refund_money_percent' => $this->wo_refund_money_percent($yesterday_startdate,$yesterday_enddate,$platform,1),
        );
        //过去7天的工单数据统计
        $seven_startdate = date("Y-m-d", strtotime("-7 day"));
        $seven_enddate = date("Y-m-d");
        $seven = array(
            'wo_num' => $this->wo_sum($seven_startdate,$seven_enddate,$platform,1),
            'wo_complete_num' => $this->wo_complete_num($seven_startdate,$seven_enddate,$platform,1),
            'wo_bufa_percent' => $this->wo_bufa_percent($seven_startdate,$seven_enddate,$platform,1),
            'wo_refund_percent' => $this->wo_refund_percent($seven_startdate,$seven_enddate,$platform,1),
            'wo_refund_money_percent' => $this->wo_refund_money_percent($seven_startdate,$seven_enddate,$platform,1),
        );
        //过去30天的工单数据统计
        $thirty_startdate = date("Y-m-d", strtotime("-30 day"));
        $thirty_enddate = date("Y-m-d");
        $thirty = array(
            'wo_num' => $this->wo_sum($thirty_startdate,$thirty_enddate,$platform,1),
            'wo_complete_num' => $this->wo_complete_num($thirty_startdate,$thirty_enddate,$platform,1),
            'wo_bufa_percent' => $this->wo_bufa_percent($thirty_startdate,$thirty_enddate,$platform,1),
            'wo_refund_percent' => $this->wo_refund_percent($thirty_startdate,$thirty_enddate,$platform,1),
            'wo_refund_money_percent' => $this->wo_refund_money_percent($thirty_startdate,$thirty_enddate,$platform,1),
        );
        //当月
        $nowmonth_startdate = date('Y-m-01', strtotime($today_startdate));
        $nowmonth_enddate = date("Y-m-d", strtotime("$nowmonth_startdate +1 month"));
        $nowmonth = array(
            'wo_num' => $this->wo_sum($nowmonth_startdate,$nowmonth_enddate,$platform,1),
            'wo_complete_num' => $this->wo_complete_num($nowmonth_startdate,$nowmonth_enddate,$platform,1),
            'wo_bufa_percent' => $this->wo_bufa_percent($nowmonth_startdate,$nowmonth_enddate,$platform,1),
            'wo_refund_percent' => $this->wo_refund_percent($nowmonth_startdate,$nowmonth_enddate,$platform,1),
            'wo_refund_money_percent' => $this->wo_refund_money_percent($nowmonth_startdate,$nowmonth_enddate,$platform,1),
        );
        //上月
        $premonth_startdate = date('Y-m-01', strtotime("$today_startdate -1 month"));
        $premonth_enddate = date('Y-m-d', strtotime("$premonth_startdate +1 month"));
        $premonth = array(
            'wo_num' => $this->wo_sum($premonth_startdate,$premonth_enddate,$platform,1),
            'wo_complete_num' => $this->wo_complete_num($premonth_startdate,$premonth_enddate,$platform,1),
            'wo_bufa_percent' => $this->wo_bufa_percent($premonth_startdate,$premonth_enddate,$platform,1),
            'wo_refund_percent' => $this->wo_refund_percent($premonth_startdate,$premonth_enddate,$platform,1),
            'wo_refund_money_percent' => $this->wo_refund_money_percent($premonth_startdate,$premonth_enddate,$platform,1),
        );
        //今年
        $year_startdate = date("Y",time())."-1"."-1"; //本年开始
        $year_enddate = date("Y",time())."-12"."-31"." 23:59:59"; //本年结束
        $year = array(
            'wo_num' => $this->wo_sum($year_startdate,$year_enddate,$platform,1),
            'wo_complete_num' => $this->wo_complete_num($year_startdate,$year_enddate,$platform,1),
            'wo_bufa_percent' => $this->wo_bufa_percent($year_startdate,$year_enddate,$platform,1),
            'wo_refund_percent' => $this->wo_refund_percent($year_startdate,$year_enddate,$platform,1),
            'wo_refund_money_percent' => $this->wo_refund_money_percent($year_startdate,$year_enddate,$platform,1),
        );
        //统计
        $total = array(
            'wo_num' => $this->wo_sum($year_startdate,$year_enddate,$platform),
            'wo_complete_num' => $this->wo_complete_num($year_startdate,$year_enddate,$platform),
            'wo_bufa_percent' => $this->wo_bufa_percent($year_startdate,$year_enddate,$platform),
            'wo_refund_percent' => $this->wo_refund_percent($year_startdate,$year_enddate,$platform),
            'wo_refund_money_percent' => $this->wo_refund_money_percent($year_startdate,$year_enddate,$platform),
        );
        $worklist = array(
            'today' => $today,
            'yesterday' => $yesterday,
            'seven' => $seven,
            'thirty' => $thirty,
            'nowmonth' => $nowmonth,
            'premonth' => $premonth,
            'year' => $year,
            'total' => $total,
        );
        return $worklist;
    }
    /**
     * 统计工单创建数量
     */
    public function wo_sum($start,$end,$platform = 0,$type = 0){
        if($type == 1){
            $map['create_time'] = array('between',[$start,$end]);
        }
        $map['work_status'] = array('not in','0,4,7');
        if($platform != 0){
            $map['work_platform'] = $platform;
        }

        $count = $this->where($map)->count();
        return $count ? $count : 0;
    }
    /**
     * 统计工单完成数量
     */
    public function wo_complete_num($start,$end,$platform = 0,$type = 0){
        if($type == 1){
            $map['complete_time'] = array('between',[$start,$end]);
        }
        $map['work_status'] = 6;
        if($platform != 0){
            $map['work_platform'] = $platform;
        }
        $count = $this->where($map)->count();
        return $count ? $count : 0;
    }
    /**
     * 统计补发订单比
     */
    public function wo_bufa_percent($start,$end,$platform = 0,$type = 0){
        $complete_count = $this->wo_complete_num($start,$end,$platform,$type);
        if($type == 1){
            $map['z.complete_time'] = array('between',[$start,$end]);
        }
        $map['z.work_status'] = 6;
        if($platform != 0){
            $map['z.work_platform'] = $platform;
        }
        $map['m.measure_choose_id'] = 7;
        $count = $this->alias('z')->join('fa_work_order_measure m','z.id=m.work_id')->where($map)->count();
        $sum = $complete_count == 0 ? 0 : round($count/$complete_count*100,2);
        return $sum ? $sum.'%' : 0;
    }
    /**
     * 统计退款订单比
     */
    public function wo_refund_percent($start,$end,$platform = 0,$type = 0){
        $complete_count = $this->wo_complete_num($start,$end,$platform,$type);
        if($type == 1){
            $map['complete_time'] = array('between',[$start,$end]);
        }
        $map['is_refund'] = 1;
        $map['work_status'] = 6;
        if($platform != 0){
            $map['work_platform'] = $platform;
        }
        $count = $this->where($map)->count();
        $sum = $complete_count == 0 ? 0 : round($count/$complete_count*100,2);
        return $sum ? $sum.'%' : 0;
    }
    /**
     * 统计退款金额比
     */
    public function wo_refund_money_percent($start,$end,$platform = 0,$type = 0){
        if($type == 1) {
            $map['complete_time'] = array('between', [$start, $end]);
        }
        $map['work_status'] = 6;
        if($platform != 0){
            $map['work_platform'] = $platform;
        }
        $complete_money = $this->where($map)->sum('base_grand_total');
        $money = $this->where($map)->where('is_refund',1)->sum('refund_money');
        $sum = $complete_money == 0 ? 0 : round($money/$complete_money*100,2);
        return $sum ? $sum.'%' : 0;
    }
    /*
     * 问题类型统计
     * */
    public function workorder_question_type($platform,$time_where){
        //客户问题
        $kefu_arr = $this->get_question_type(4,$platform,$time_where);
        //物流仓储
        $wuliu_arr = $this->get_question_type(2,$platform,$time_where);
        //产品问题
        $product_arr = $this->get_question_type(3,$platform,$time_where);
        //其他
        $other_arr = $this->get_question_type(6,$platform,$time_where);
        //仓库跟单
        $warehouse_arr = $this->get_question_type(5,$platform,$time_where);
        $arr = array(
            array(
                'name'=>'客户问题',
                'value'=>$kefu_arr
            ),
            array(
                'name'=>'物流仓储',
                'value'=>$wuliu_arr
            ),
            array(
                'name'=>'产品问题',
                'value'=>$product_arr
            ),
            array(
                'name'=>'其他',
                'value'=>$other_arr
            ),
            array(
                'name'=>'仓库跟单',
                'value'=>$warehouse_arr
            ),
        );
        return $arr;
    }
    /*
     * 问题类型通用方法
     * */
    public function get_question_type($type,$platform,$time_where){
        $kehu_where['problem_belong'] = $type;
        $kehu_where['is_del'] = 1;
        $problem_ids = Db::name('work_order_problem_type')->where($kehu_where)->column('id');
        $where['work_status'] = 6;
        $where['problem_type_id'] = array('in',$problem_ids);
        if($platform){
            $where['work_platform'] = $platform;
        }
        $count = $this->where($where)->where($time_where)->count();
        return $count;
    }
    /*
     * 措施统计
     * */
    public function workorder_measures($platform,$time_where){
        $this->step = new \app\admin\model\saleaftermanage\WorkOrderMeasure;
        $measures = Db::name('work_order_step_type')->where('is_del',1)->field('id,step_name')->select();
        $arr = array();
        foreach ($measures as $key=>$value){
            $arr[$key]['name'] = $value['step_name'];
            $where['m.operation_type'] = 1;
            $where['m.measure_choose_id'] = $value['id'];
            if($platform){
                $where['w.work_platform'] = $platform;
            }
            $arr[$key]['value'] = $this->step->alias('m')->join('fa_work_order_list w','m.work_id=w.id')->where($time_where)->where($where)->count();
        }

        return $arr;
    }
}
