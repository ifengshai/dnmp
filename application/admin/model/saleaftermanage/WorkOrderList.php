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
        $result = $this->model->alias('a')
            ->where('increment_id', $increment_id)
            ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
            ->column('sku');
        return $result ? array_unique($result) : [];
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
                'prescription_type' => ['singlevision','nonprescription'],
                'lens_type' => ['refractive_5','refractive_2','refractive_3'],
                'coating_type' => ['coating_2','coating_1','coating_3']
            ];
            $data = $res['data'];
            session($key, $data, 3600*24);
        }
        $original_sku = $prescription_type = $prescriptions = $lens_type = $coating_type = '<option value="">请选择</option>';
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
        $html = <<<Crasp
            <div>
                        <div class="step7_function2">
                            <div class="step7_function2_child">
                                <label class="control-label col-xs-12 col-sm-3">SKU:</label>
                                <div class="col-xs-12 col-sm-8">
                                    <select class="form-control selectpicker" name="row[lens][original_sku][]">
                                        {$original_sku}
                                    </select>
                                </div>
                            </div>
    
                            <div class="step7_function2_child">
                                <label class="control-label col-xs-12 col-sm-3">Name:</label>
                                <div class="col-xs-12 col-sm-8">
                                    <input class="form-control" name="row[lens][original_name][]" type="text" value="">
                                </div>
                            </div>
                            <div class="step7_function2_child">
                                <label class="control-label col-xs-12 col-sm-3">QTY:</label>
                                <div class="col-xs-12 col-sm-8">
                                    <input class="form-control" name="row[lens][original_number][]" type="text" value="">
                                </div>
                            </div>
                            <div class="step7_function2_child">
                                <label class="control-label col-xs-12 col-sm-3">选择已有处方:</label>
                                <div class="col-xs-12 col-sm-8">
                                    <select id="prescription_select" class="form-control selectpicker" name="row[]">
                                        {$prescriptions}
                                    </select>
                                </div>
                            </div>
                            <div class="step7_function2_child">
                                <label class="control-label col-xs-12 col-sm-3">prescription_type:</label>
                                <div class="col-xs-12 col-sm-8">
                                    <select class="form-control selectpicker" name="row[lens][prescription_type][]">
                                        {$prescription_type}
                                    </select>
                                </div>
                            </div>
    
                        </div>
    
                        <div class="step1_function3">
                            <div class="panel-body">
                                <div class="step1_function3_child">
                                    <label class="control-label col-xs-12 col-sm-3">lens_type:</label>
                                    <div class="col-xs-12 col-sm-8">
                                        <select class="form-control selectpicker" name="row[lens][lens_type][]">
                                           {$lens_type}
                                        </select>
                                    </div>
                                </div>
                                <div class="step1_function3_child">
                                    <label class="control-label col-xs-12 col-sm-3">coating_type:</label>
                                    <div class="col-xs-12 col-sm-8">
                                        <select id="c-coating_type" class="form-control selectpicker"
                                            name="row[lens][coating_type][]">
                                            {$coating_type}
                                        </select>
                                    </div>
                                </div>
                                <table>
                                    <tbody>
                                        <tr>
                                            <td style="text-align: center">value</td>
                                            <td style="text-align: center">SPH</td>
                                            <td style="text-align: center">CYL</td>
                                            <td style="text-align: center">AXI</td>
                                            <td style="text-align: center">ADD</td>
                                            <td style="text-align: center">PD</td>
                                        </tr>
                                        <tr>
                                            <td style="text-align: center">Right(OD)</td>
                                            <td><input class="form-control" name="row[lens][od_sph][]" type="text" value="">
                                            </td>
                                            <td><input class="form-control" name="row[lens][od_cyl][]" type="text" value="">
                                            </td>
                                            <td><input class="form-control" name="row[lens][od_axis][]" type="text"
                                                    value=""></td>
                                            <td><input class="form-control" name="row[lens][od_add][]" type="text" value="">
                                            </td>
                                            <td><input class="form-control" name="row[lens][pd_r][]" type="text" value="">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="text-align: center">Left(OS)</td>
                                            <td><input class="form-control" name="row[lens][os_sph][]" type="text" value="">
                                            </td>
                                            <td><input class="form-control" name="row[lens][os_cyl][]" type="text" value="">
                                            </td>
                                            <td><input class="form-control" name="row[lens][os_axis][]" type="text"
                                                    value=""></td>
                                            <td><input class="form-control" name="row[lens][os_add][]" type="text" value="">
                                            </td>
                                            <td><input class="form-control" name="row[lens][pd_l][]" type="text" value="">
                                            </td>
                                        </tr>
    
                                        <tr>
                                            <td style="text-align: center"></td>
                                            <td style="text-align: center">Prism Horizontal</td>
                                            <td style="text-align: center">Base Direction</td>
                                            <td style="text-align: center">Prism Vertical</td>
                                            <td style="text-align: center">Base Direction</td>
                                        </tr>
                                        <tr>
                                            <td style="text-align: center">Right(OD)</td>
                                            <td><input class="form-control" type="text" name="row[lens][od_pv][]" value="">
                                            </td>
                                            <td><input class="form-control" type="text" name="row[lens][od_bd][]" value="">
                                            </td>
                                            <td><input class="form-control" type="text" name="row[lens][od_pv_r][]"
                                                    value=""></td>
                                            <td><input class="form-control" type="text" name="row[lens][od_bd_r][]"
                                                    value=""></td>
                                        </tr>
                                        <tr>
                                            <td style="text-align: center">Left(OS)</td>
                                            <td><input class="form-control" name="row[lens][os_pv][]" type="text" value="">
                                            </td>
                                            <td><input class="form-control" name="row[lens][os_bd][]" type="text" value="">
                                            </td>
                                            <td><input class="form-control" name="row[lens][os_pv_r][]" type="text"
                                                    value=""></td>
                                            <td><input class="form-control" name="row[lens][os_bd_r][]" type="text"
                                                    value=""></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
    
                        <div class="form-group-child4_del">
                            <!--<a href="javascript:;" style="width: 50%;" class="btn btn-danger btn-del-supplement" title="删除"><i class="fa fa-trash"></i>删除</a>-->
                        </div>
                    </div>
Crasp;
        return ['data' => $data,'html' => $html];
    }
}
