<?php

namespace app\admin\model\saleaftermanage;

use fast\Http;
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
        $orderInfo = $this->model->alias('a')->where('increment_id',$increment_id)
        ->join(['sales_flat_order_payment' => 'c'],'a.entity_id=c.parent_id')
        ->field('a.base_currency_code,c.method')->find(); 
        if(!$sku && !$orderInfo){
            return [];
        }
        $result['sku'] = array_unique($sku);
        $result['base_currency_code'] = $orderInfo['base_currency_code'];
        $result['method']             = $orderInfo['method'];
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
        foreach($prescriptions as $prescription){
            $showPrescriptions[] = $prescription['prescription_type'] . '--' . $prescription['index_type'];
        }
        return $address ? compact('address','prescriptions','showPrescriptions') : [];
    }
    public function getLens($siteType, $showPrescriptions)
    {
        $url = '';
        $key = $siteType . '_getlens';
        $data = session($key);
        if(!$data){
            //处方信息
            switch ($siteType) {
                case 1:
                    $url = 'https://www.zeelool.com/';
                    break;
                case 2:
                    $url = 'https://pc.voogueme.com/';
                    break;
                case 3:
                    $url = 'https://www.nihaooptical.com/';
                    break;
                case 5:
                    $url = 'https://www.eseeoptical.com/';
                    break;
                default:
                    return false;
                    break;
            }
            $url = $url . 'api/mojing/getLens';
            //$res = Http::post($url, []);
            //模拟数据
            $res['data'] = [
                'skus' => [],
                'prescription_type' => ['SingleVision','NonPrescription'],
                'lens_type' => ['refractive_5','refractive_2','refractive_3'],
                'coating_type' => ['coating_2','coating_1','coating_3']
            ];
            $data = $res['data'];
            session($key, $data, 3600*24);
        }
        $original_sku = $prescription_type = $prescriptions = $lens_type = $coating_type = '';
        foreach($data['skus'] as $key => $val){
            $original_sku .= "<option value='{$val}'>{$val}</option>";
        }
        foreach($data['prescription_type'] as $key => $val){
            $prescription_type .= "<option value='{$val}'>{$val}</option>";
        }
        foreach($data['lens_type'] as $key => $val){
            $lens_type .= "<option value='{$val}'>{$val}</option>";
        }
        foreach($data['coating_type'] as $key => $val){
            $coating_type .= "<option value='{$val}'>{$val}</option>";
        }
        foreach($showPrescriptions as $key => $val){
            $prescriptions .= "<option value='{$key}'>{$val}</option>";
        }
        //拼接html页面
        $html = (new \think\View())->fetch('saleaftermanage/work_order_list/ajax_reissue_add',compact('original_sku','prescription_type','lens_type','coating_type','prescriptions'));
        return ['data' => $data,'html' => $html];
    }
}
