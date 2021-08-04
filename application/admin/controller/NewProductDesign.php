<?php

namespace app\admin\controller;

use app\admin\model\Admin;
use app\admin\model\AuthGroup;
use app\admin\model\AuthGroupAccess;
use app\admin\model\DistributionLog;
use app\admin\model\itemmanage\attribute\ItemAttribute;
use app\admin\model\itemmanage\Item;
use app\admin\model\itemmanage\ItemBrand;
use app\admin\model\itemmanage\ItemCategory;
use app\admin\model\order\Order;
use app\common\controller\Backend;
use app\common\model\Auth;
use Aws\S3\S3Client;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\admin\model\itemmanage\ItemPlatformSku;
use app\admin\model\NewProductDesignLog;
use think\Model;

/**
 * 选品设计管理
 *
 * @icon fa fa-circle-o
 */
class NewProductDesign extends Backend
{

    /**
     * NewProductDesign模型对象
     * @var \app\admin\model\NewProductDesign
     */
    protected $model = null;
    protected $noNeedRight = ['detail','designRecording'];
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\NewProductDesign;
        $this->itemAttribute = new \app\admin\model\itemmanage\attribute\ItemAttribute;
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
        $this->category = new \app\admin\model\itemmanage\ItemCategory;
        $this->newProduct = new \app\admin\model\NewProduct;
        $this->view->assign('getTabList', $this->model->getTabList());
        $this->view->assign('categoryList', $this->category->categoryList());
        $this->view->assign('brandList', (new ItemBrand())->getBrandList());
        $this->view->assign('AllFrameColor', $this->itemAttribute->getFrameColor());
        $this->view->assign('AllDecorationColor', $this->itemAttribute->getFrameColor(3));
        $this->view->assign('AllProductSize', config('FRAME_SIZE'));
        $this->client = new S3Client([
            'version' => 'latest',
            'region' => 'us-west-2', # 可用区必须是这个
            'credentials' => [
                'key' => 'AKIAT2RCARUTCLJLTCDL',
                'secret' => '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',
            ],
        ]);

        $this->assignconfig('record_size', $this->auth->check('new_product_design/record_size'));//录尺寸
        $this->assignconfig('allocate_personnel', $this->auth->check('new_product_design/allocate_personnel'));//分配人员信息
        $this->assignconfig('shooting', $this->auth->check('new_product_design/shooting')); //拍摄开始 拍摄完成
        $this->assignconfig('making', $this->auth->check('new_product_design/making')); //开始制作
        $this->assignconfig('review_the_operation', $this->auth->check('new_product_design/review_the_operation')); //审核操作
        $this->assignconfig('add_img', $this->auth->check('new_product_design/add_img')); //图片上传操作
        $this->assignconfig('change_designer', $this->auth->check('new_product_design/change_designer')); //修改人员
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


    /**
     * 查看
     */
    public function index()
    {
        $admin = new Admin();
        $Item = new Item();
        //当前是否为关联查询
        $this->relationSearch = false;
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            $filter = json_decode($this->request->get('filter'), true);

            if ($filter['label']) {
                if ($filter['label'] == 5 || $filter['label'] == 6){
                    $adminId = session('admin.id');
                    //品牌设计部主管都可以看到所有待修图和修图中的数据
                    $ids = Db::name('auth_group_access')->where('group_id',159)->column('uid');
                    if (in_array($adminId,$ids)){

                    }else{
                        $map['a.responsible_id'] = ['eq', $adminId];
                    }
                }
                $map['a.status'] = $filter['label'];
            }
            if ($filter['sku']) {
                $map['a.sku'] = ['like', '%' . $filter['sku'] . '%'];
            }
            if ($filter['is_spot']) {
                $sku = $Item
                    ->where('is_spot',$filter['is_spot'])
                    ->column('sku');
                $sku = array_unique($sku);
                unset($filter['is_spot']);
                $map['a.sku'] = ['in', $sku];
            }

            if ($filter['site'] || $filter['item_status'] || $filter['is_new']){
                if ($filter['site']){
                    $cat['b.platform_type'] = ['in', $filter['site']];
                    unset($filter['site']);
                }
                if ($filter['item_status']){
                    $cat['a.item_status'] = ['eq',$filter['item_status']];
                    unset($filter['item_status']);
                }
                if ($filter['is_new']){
                    $cat['a.is_new'] = ['eq',$filter['is_new']];
                    unset($filter['is_new']);
                }
                $sku = $Item->alias('a')
                    ->join(['fa_item_platform_sku' => 'b'], 'a.sku = b.sku')
                    ->where($cat)
                    ->column('a.sku');
                $sku = array_unique($sku);
                $map['a.sku'] = ['in', $sku];
            }
            unset($filter['label']);
            if ($filter['responsible_id']) {
                $wheLike['nickname'] = ['like', '%' . $filter['responsible_id'] . '%'];
                $responsibleId = $admin->where($wheLike)->column('id');
                if ($responsibleId) {
                    $map['a.responsible_id'] = ['in', $responsibleId];
                } else {
                    $map['a.responsible_id'] = ['eq', '999999999'];
                }
            }
            $whereLogIds = [];
            $logIds = Db::name('new_product_design_log')->field('max(id) as mid')->group('design_id')->select();
            if ($logIds) {
                $logIds = array_column($logIds, 'mid');
                $whereLogIds['b.id'] = ['in', $logIds];
            }
            $whereLogTime = [];
            if ($filter['addtime']) {
                $time = explode(' ', $filter['addtime']);
                $whereLogTime['b.addtime'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
            }

            unset($filter['responsible_id']);
            unset($filter['sku']);
            unset($filter['addtime']);
            $this->request->get(['filter' => json_encode($filter)]);
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            //为了排序
            $allPlat = ['zeelool','voogueme','meeloog','vicmoo','wesee','amazon','zeelool_es','zeelool_de','zeelool_jp','voogmechic','zeelool_cn','alibaba','zeelool_fr'];
            if (in_array($sort, $allPlat)) {
                $sortPlat = $sort;
                $sort = 'id';
            }
            $total = $this->model
                ->alias('a')
                ->join('new_product_design_log b', 'b.design_id=a.id ', 'left')
                ->field('a.*,b.addtime,b.design_id,b.id as bid')
                ->where($where)
                ->where($map)
                ->where(function ($query) use ($whereLogIds) {
                    $query->where($whereLogIds)->whereOr('b.id', null);
                })
                ->where($whereLogTime)
                ->group('a.id')
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->alias('a')
                ->join('new_product_design_log b', 'b.design_id=a.id ', 'left')
                ->field('a.*,b.addtime,b.design_id,b.id as bid')
                ->where($where)
                ->where($map)
                ->where(function ($query) use ($whereLogIds) {
                    $query->where($whereLogIds)->whereOr('b.id', null);
                })
                ->where($whereLogTime)
                ->group('a.id')
                ->order($sort, $order)
                ->order('b.id desc')
                ->limit($offset, $limit)
                ->select();
            foreach ($list as $row) {
                $row->visible(['id', 'sku', 'status', 'responsible_id', 'create_time', 'addtime']);
            }
            $list = collection($list)->toArray();
            $skuList = array_column($list,'sku');
            $itemPlatform = new ItemPlatformSku();
            $platStock = $itemPlatform->where('sku','in',$skuList)->field('stock,platform_type,sku')->select();
            $itemStatusIsNew = $Item->where('sku','in',$skuList)->field('sku,item_status,is_new,available_stock,category_id,is_spot')->select();
            $newProducts = $Item->where('sku','in',$skuList)->field('sku,goods_supply')->select();
            $itemStatusIsNew = collection($itemStatusIsNew)->toArray();
            $itemStatusIsNew = array_column($itemStatusIsNew,null,'sku');
            $newProducts = array_column($newProducts,null,'sku');
            $platStock = collection($platStock)->toArray();
            $itemCategory= new ItemCategory();
            $itemCategoryAll = $itemCategory->column('name','id');
            $platformType = ['0','Z','V','M','Vm','W','0','0','A','Es','De','Jp','Chic','Z_cn','Ali','Z_fr'];
            foreach ($list as $key=>$item){
                $list[$key]['label'] = $map['a.status'] ? $map['a.status'] : 0;
                if ($item['responsible_id'] !==null){
                    $list[$key]['responsible_id'] = $admin->where('id',$item['responsible_id'])->value('nickname');
                }else{
                    $list[$key]['responsible_id'] = '暂无';
                }

                $list[$key]['item_status'] =$itemStatusIsNew[$item['sku']]['item_status'];
                $list[$key]['is_spot'] =$itemStatusIsNew[$item['sku']]['is_spot'];
                $goodsSupply =$newProducts[$item['sku']]['goods_supply'];
                //自主设计,采样定做,线上现货,线下现货
                switch($goodsSupply) {
                    case 1:
                        $goodsSupplyName = '自主设计';
                        break;
                    case 2:
                        $goodsSupplyName = '采样定做';
                        break;
                    case 3:
                        $goodsSupplyName = '线上现货';
                        break;
                    case 4:
                        $goodsSupplyName = '线下现货';
                        break;
                        default:
                        $goodsSupplyName = '';
                }
                $list[$key]['goods_supply'] = $goodsSupplyName;
                $list[$key]['zeelool'] = array_reduce($platStock,function($carry,$val)use($item){
                    if ($val['sku'] == $item['sku'] && $val['platform_type'] == 1){
                        return $val['stock'];
                    }else{
                        return $carry;
                    }
                }) ?? '-';
                $list[$key]['voogueme'] = array_reduce($platStock,function($carry,$val)use($item){
                        if ($val['sku'] == $item['sku'] && $val['platform_type'] == 2){
                            return $val['stock'];
                        }else{
                            return $carry;
                        }
                    }) ?? '-';
                $list[$key]['meeloog'] = array_reduce($platStock,function($carry,$val)use($item){
                        if ($val['sku'] == $item['sku'] && $val['platform_type'] == 3){
                            return $val['stock'];
                        }else{
                            return $carry;
                        }
                    }) ?? '-';
                $list[$key]['vicmoo'] = array_reduce($platStock,function($carry,$val)use($item){
                        if ($val['sku'] == $item['sku'] && $val['platform_type'] == 4){
                            return $val['stock'];
                        }else{
                            return $carry;
                        }
                    }) ?? '-';
                $list[$key]['wesee'] = array_reduce($platStock,function($carry,$val)use($item){
                        if ($val['sku'] == $item['sku'] && $val['platform_type'] == 5){
                            return $val['stock'];
                        }else{
                            return $carry;
                        }
                    }) ?? '-';
                $list[$key]['amazon'] = array_reduce($platStock,function($carry,$val)use($item){
                        if ($val['sku'] == $item['sku'] && $val['platform_type'] == 8){
                            return $val['stock'];
                        }else{
                            return $carry;
                        }
                    }) ?? '-';
                $list[$key]['zeelool_es'] = array_reduce($platStock,function($carry,$val)use($item){
                        if ($val['sku'] == $item['sku'] && $val['platform_type'] == 9){
                            return $val['stock'];
                        }else{
                            return $carry;
                        }
                    }) ?? '-';
                $list[$key]['zeelool_de'] = array_reduce($platStock,function($carry,$val)use($item){
                        if ($val['sku'] == $item['sku'] && $val['platform_type'] == 10){
                            return $val['stock'];
                        }else{
                            return $carry;
                        }
                    }) ?? '-';
                $list[$key]['zeelool_jp'] = array_reduce($platStock,function($carry,$val)use($item){
                        if ($val['sku'] == $item['sku'] && $val['platform_type'] == 11){
                            return $val['stock'];
                        }else{
                            return $carry;
                        }
                    }) ?? '-';
                $list[$key]['voogmechic'] = array_reduce($platStock,function($carry,$val)use($item){
                        if ($val['sku'] == $item['sku'] && $val['platform_type'] == 12){
                            return $val['stock'];
                        }else{
                            return $carry;
                        }
                    }) ?? '-';
                $list[$key]['zeelool_cn'] = array_reduce($platStock,function($carry,$val)use($item){
                        if ($val['sku'] == $item['sku'] && $val['platform_type'] == 13){
                            return $val['stock'];
                        }else{
                            return $carry;
                        }
                    }) ?? '-';
                $list[$key]['alibaba'] = array_reduce($platStock,function($carry,$val)use($item){
                        if ($val['sku'] == $item['sku'] && $val['platform_type'] == 14){
                            return $val['stock'];
                        }else{
                            return $carry;
                        }
                    }) ?? '-';
                $list[$key]['zeelool_fr'] = array_reduce($platStock,function($carry,$val)use($item){
                        if ($val['sku'] == $item['sku'] && $val['platform_type'] == 15){
                            return $val['stock'];
                        }else{
                            return $carry;
                        }
                    }) ?? '-';
//                $list[$key]['category'] =$itemCategory->where('id',$itemStatusIsNew[$item['sku']]['category_id'])->value('name');
                $list[$key]['category'] =$itemCategoryAll[$itemStatusIsNew[$item['sku']]['category_id']];
                $list[$key]['is_new'] = $itemStatusIsNew[$item['sku']]['is_new'];
                //$list[$key]['addtime'] = Db::name('new_product_design_log')->where('design_id',$item['id'])->order('addtime desc')->value('addtime') ? Db::name('new_product_design_log')->where('design_id',$item['id'])->order('addtime desc')->value('addtime'):'';
                $list[$key]['location_code'] = Db::name('purchase_sample')->alias('a')->join(['fa_purchase_sample_location' => 'b'],'a.location_id=b.id')->where('a.sku',$item['sku'])->value('b.location');
                $list[$key]['platform'] =$itemPlatform->where('sku',$item['sku'])->order('platform_type asc')->column('platform_type');
                foreach ($list[$key]['platform'] as $k1=>$v1){
                    $list[$key]['platform'][$k1] = $platformType[$v1];
                }
            }
            if ($sortPlat && in_array($sortPlat, $allPlat)) {
                $lastNames = array_column($list,$sortPlat);
                array_multisort($lastNames,$order == 'desc'? SORT_DESC:SORT_ASC,SORT_NUMERIC,$list);
            }

            $result = ["total" => $total, "label" => $map['a.status'] ? $map['a.status'] : 0, "rows" => $list];

            return json($result);
        }
        return $this->view->fetch();
    }
    public function detail($ids=null)
    {
        $item = new Item();
        $itemAttribute =new ItemAttribute();
        $value = $this->model->get($ids);
        $where['sku'] = $value->sku;
        $data = $item->where($where)
            ->field('id,category_id')
            ->find();
        $attributeType = $data->category_id;
        $goodsId = $data->id;
        $compareValue = ['32','34','35','38','39'];
        if (!in_array($attributeType,$compareValue)){
            $attributeType = true;
        }
        $row =$itemAttribute->where('item_id',$goodsId)->find();
        if (!empty($row->frame_aws_imgs)){
            $img = explode(',',$row->frame_aws_imgs);
            $net = 'https://mojing.s3-us-west-2.amazonaws.com/';
                if (is_array($img)){
                    foreach ($img as $key=>$value){
                        $img[$key] = $net.$value;
                    }
                    $this->assign('img',$img);
                }
        }
        $this->assign('attributeType',$attributeType);
        $this->assign('goodsId',$goodsId);
        $this->assign('ids',$ids);
        $this->assign('row',$row);

        return $this->view->fetch();
    }
    //录尺寸
    public function record_size($ids =null)
    {
        $item = new Item();
        $itemAttribute = new ItemAttribute();
        if ($this->request->post()){
            $data = $this->request->post();
            $attributeType = $item->where('id',$data['goodsId'])
                ->field('id,category_id')
                ->find();
//            if ($data['attributeType'] ==1){
//                if ($data['row']['frame_height'] < 0.1){
//                    $this->error('请输入正确的镜框高数值');
//                }
//                if($data['row']['frame_bridge']<0.1){
//                    $this->error('请输入正确的桥数值');
//                }
//                if($data['row']['frame_temple_length']<0.1){
//                    $this->error('请输入正确的镜腿长数值');
//                }
//                if($data['row']['frame_length']<0.1){
//                    $this->error('请输入正确的镜架总长数值');
//                }
//                if($data['row']['frame_weight']<0.1){
//                    $this->error('请输入正确的重量数值');
//                }
//                if($data['row']['mirror_width']<0.1){
//                    $this->error('请输入正确的镜面宽数值');
//                }
//
//            }
//            if ($data['attributeType'] ==32){
//                if($data['row']['box_height']<0.1){
//                    $this->error('请输入正确的高度数值');
//                }
//                if($data['row']['box_width']<0.1){
//                    $this->error('请输入正确的宽度数值');
//                }
//            }
//            if ($data['attributeType'] ==35){
//                if($data['row']['earrings_height']<0.1){
//                    $this->error('请输入正确的高度数值');
//                }
//                if($data['row']['earrings_width']<0.1){
//                    $this->error('请输入正确的宽度数值');
//                }
//            }
//            if ($data['attributeType'] ==38){
//                if($data['row']['eyeglasses_chain']<0.1){
//                    $this->error('请输入正确的周长数值数值');
//                }
//            }
//            if ($data['attributeType'] ==34 ||$data['attributeType'] ==39){
//                if($data['row']['necklace_perimeter']<0.1){
//                    $this->error('请输入正确的周长数值');
//                }
//                if($data['row']['necklace_chain']<0.1){
//                    $this->error('请输入正确的延长链数值');
//                }
//            }
            $this->model->startTrans();
            $itemAttribute->startTrans();
            try {
                //更新设计表
                $map['id'] = $ids;
                $data['status'] = 2;
                $data['update_time']  = date("Y-m-d H:i:s", time());
                $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
                if ($res){
                    //更新商品属性表
                    $whe['item_id'] = $data['goodsId'];
                    $itemAttribute->where($whe)->update($data['row']);
                    //添加操作记录
                    $valueLog['operator'] = session('admin.nickname');
                    $valueLog['addtime'] = date('Y-m-d H:i:s',time());
                    $valueLog['node'] = 2;
                    $valueLog['operation_type'] = '录尺寸';
                    $valueLog['design_id'] = $ids;
                    $valueLog['value_log'] = json_encode($data['row']);
                    $this->designRecording($valueLog);
                }
                $this->model->commit();
                $itemAttribute->commit();
            } catch (PDOException $e) {
                $this->model->rollback();
                $itemAttribute->rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                $itemAttribute->rollback();
                $this->error($e->getMessage());
            }
            $this->success('操作成功');
        }

        $value = $this->model->get($ids);
        $where['sku'] = $value->sku;
        $data = $item->where($where)
            ->field('id,category_id')
            ->find();
        $attributeType = $data->category_id;
        $goodsId = $data->id;
        $compareValue = ['32','34','35','38','39'];
        if (!in_array($attributeType,$compareValue)){
            $attributeType = true;
        }
        //获取商品属性
        $cat['item_id'] = $goodsId;
        $item_attribute = $itemAttribute->where($cat)->find();
        $this->assign('attributeType',$attributeType);
        $this->assign('goodsId',$goodsId);
        $this->assign('ids',$ids);
        $this->assign('item_attribute',$item_attribute);
        return $this->view->fetch();
    }
    //产品要求  状态更改需要拆分为多个方法-用于权限限制
    //拍摄-（开始拍摄、拍摄完成）、分配-（分配）、制作-（开始制作）、上传-（上传图片）、审核（审核通过、审核拒绝）
    /**
     * @author zjw
     * @date   2021/4/9 13:55
     * 选品设计拍摄开始 -- 完成--按钮
     */
    public function shooting(){
        $ids =  $this->request->get('ids');
        $status =  $this->request->get('status');
        empty($ids) && $this->error('缺少重要参数');
        empty($status) && $this->error('数据异常');
        $map['id'] = $ids;
        $data['status'] = $status;
        $data['update_time']  = date("Y-m-d H:i:s", time());
        $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
        if ($data['status']==3){
            $valueLog['operation_type'] = '开始拍摄';
        }else{
            $valueLog['operation_type'] = '拍摄完成';
        }
        if ($res){
            //添加操作记录
            $valueLog['operator'] = session('admin.nickname');
            $valueLog['addtime'] = date('Y-m-d H:i:s',time());
            $valueLog['node'] = $data['status'];
            $valueLog['design_id'] = $ids;
            $this->designRecording($valueLog);
            $this->success('操作成功');
        }else{
            $this->error('操作失败');
        }
    }

    /**
     * @author zjw
     * @date   2021/4/9 14:04
     * 开始制作
     */
    public function making(){
        $ids =  $this->request->get('ids');
        $status =  $this->request->get('status');
        empty($ids) && $this->error('缺少重要参数');
        empty($status) && $this->error('数据异常');
        $map['id'] = $ids;
        $data['status'] = $status;
        $data['update_time']  = date("Y-m-d H:i:s", time());
        $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
        if ($res){
            //添加操作记录
            $valueLog['operator'] = session('admin.nickname');
            $valueLog['addtime'] = date('Y-m-d H:i:s',time());
            $valueLog['node'] = 6;
            $valueLog['operation_type'] = '开始制作';
            $valueLog['design_id'] = $ids;
            $this->designRecording($valueLog);
            $this->success('操作成功');
        }else{
            $this->error('操作失败');
        }
    }

    /**
     * @author zjw
     * @date   2021/4/9 14:11
     * 审核操作
     */
    public function review_the_operation(){
        $item = new  Item();
        $ids =  $this->request->get('ids');
        $status =  $this->request->get('status');
        empty($ids) && $this->error('缺少重要参数');
        empty($status) && $this->error('数据异常');
        $map['id'] = $ids;
        if ($status ==9){
            $valueLog['operation_type'] = '审核拒绝';
            $valueLog['node'] = $status;
            $status =6;
        }
        if ($status ==8){
            $valueLog['node'] = $status;
            $valueLog['operation_type'] = '审核通过';
            $value = $this->model->get($ids);
            $data['item_status']=1;
            $change['sku'] = $value->sku;
            $item->allowField(true)->isUpdate(true, $change)->save($data);
            createNewProductProcessLog([$value->sku],6,session('admin.id'));
        }
        $data['status'] = $status;
        $data['update_time']  = date("Y-m-d H:i:s", time());
        $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
        if ($res){
            //添加操作记录
            $valueLog['operator'] = session('admin.nickname');
            $valueLog['addtime'] = date('Y-m-d H:i:s',time());
            $valueLog['design_id'] = $ids;
            $this->designRecording($valueLog);
            $this->success('操作成功');
        }else{
            $this->error('操作失败');
        }
    }


    //分配人员
    public function allocate_personnel($ids = null)
    {
        if($this->request->post()){
            $ids =  $this->request->post('ids');
            $responsible_id =  $this->request->post('responsible_id');
            $map['id'] = $ids;
            $data['responsible_id'] = $responsible_id;
            $data['status'] = 5;
            $data['update_time']  = date("Y-m-d H:i:s", time());
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res){
                //添加操作记录
                $valueLog['operator'] = session('admin.nickname');
                $valueLog['addtime'] = date('Y-m-d H:i:s',time());
                $valueLog['node'] = 5;
                $valueLog['operation_type'] = '分配人员';
                $valueLog['design_id'] = $ids;
                $this->designRecording($valueLog);
                $this->success('人员分配成功');
            }else{
                $this->error('人员分配失败');
            }
        }
        //获取筛选人
        $authGroupAccess= new AuthGroupAccess();
        $auth_user = $authGroupAccess
            ->alias('a')
            ->join(['fa_admin' => 'b'], 'a.uid=b.id')
            ->where('a.group_id=160')
            ->field('id,nickname')
            ->select();
        $this->assign('ids', $ids);
        $this->assign('auth_user', collection($auth_user)->toArray());

        return $this->view->fetch();

    }

    /**
     * @author zjw
     * @date   2021/4/26 17:59
     * 更换设计师
     */
    public function change_designer($ids=null){
        if($this->request->post()){
            $responsible_id =  $this->request->post('responsible_id');
            $map['id'] = $ids;
            $data['responsible_id'] = $responsible_id;
            $data['update_time']  = date("Y-m-d H:i:s", time());
            $res = $this->model->where($map)->update($data);
            if ($res){
                //添加操作记录
                $valueLog['operator'] = session('admin.nickname');
                $valueLog['addtime'] = date('Y-m-d H:i:s',time());
                $valueLog['node'] = 5;
                $valueLog['operation_type'] = '更换设计师';
                $valueLog['design_id'] = $ids;
                $this->designRecording($valueLog);
                $this->success('人员分配成功');
            }else{
                $this->error('人员分配失败');
            }
        }
        //获取筛选人
        $authGroupAccess= new AuthGroupAccess();
        $auth_user = $authGroupAccess
            ->alias('a')
            ->join(['fa_admin' => 'b'], 'a.uid=b.id')
            ->where('a.group_id=160')
            ->field('id,nickname')
            ->select();
        $this->assign('ids', $ids);
        $this->assign('auth_user', collection($auth_user)->toArray());

        return $this->view->fetch();

    }


    //上传图片 弃用
    public function add_img_old()
    {
        $item = new \app\admin\model\itemmanage\Item;
        $itemAttribute = new ItemAttribute();
        $newProductDesign = new \app\admin\model\NewProductDesign();
        $newProductDesignDetail = $newProductDesign->where('id', input('ids'))->find();
        $itemId = $item->where('sku', $newProductDesignDetail['sku'])->value('id');
        $row = $item->get($itemId, 'itemAttribute');
        if ($this->request->isAjax()) {
            $params = $this->request->post("row/a");
            $item_status = $params['item_status'];
            $itemAttrData['frame_images'] = $params['frame_images'];
            $itemAttrData['create_frame_images_time'] = date("Y-m-d H:i:s", time());
            $imgsArr = [];
            $net = 'https://mojing.s3-us-west-2.amazonaws.com/';
            $itemAttribute->startTrans();
            $item->startTrans();
            $newProductDesign->startTrans();
            try {
//                $itemResult = $item->where('id', '=', $itemId)->update(['item_status' => $item_status]);
                $imgArr = explode(',', $params['frame_images']);
                foreach ($imgArr as $k => $v) {
                    $arr = explode("/", $v);
                    //获取最后一个/后边的字符
                    $sku = $arr[count($arr) - 1];
                    $imgsArr[$k] = 'skupic/' . $sku;
                    $file_url = '.' . $v;
                    //私有
                    $acl = 'private';
                    $acl = 'public-read';
                    //上传至桶的名称
                    $bucket = 'mojing';
                    $result = $this->client->putObject(array(
                        'Bucket' => $bucket,
                        'Key' => 'skupic/' . $sku,
                        'Body' => fopen($file_url, 'rb'),
                        'ACL' => $acl,
                    ));
                    //上传成功--返回上传后的地址
                    $data = [
                        'type' => '1',
                        'data' => urldecode($result['ObjectURL']),
                    ];
                    // unlink($file_url);
                }
                $itemAttrData['frame_aws_imgs'] = implode(',', $imgsArr);
                $itemAttrResult = $itemAttribute->where('item_id', '=', $itemId)->update($itemAttrData);
                $newProductDesignResult = $newProductDesign->where('id', '=', input('ids'))->update(['status'=>7,'update_time'=>date("Y-m-d H:i:s", time())]);
                //添加操作记录
                $valueLog['operator'] = session('admin.nickname');
                $valueLog['addtime'] = date('Y-m-d H:i:s',time());
                $valueLog['node'] = 6;
                $valueLog['operation_type'] = '上传图片';
                $this->designRecording($valueLog);
                $itemAttribute->commit();
                $item->commit();
                $newProductDesign->commit();
            } catch (Aws\Exception\MultipartUploadExcepti $e) {
                //上传失败--返回错误信息
                $uploader = new Aws\S3\MultipartUploader($this->client, $file_url, [
                    'state' => $e->getState(),
                ]);
                $data = [
                    'type' => '0',
                    'data' => $e->getMessage(),
                ];
            } catch (ValidateException $e) {
                $itemAttribute->rollback();
                $item->rollback();
                $newProductDesign->rollback();
                $this->error($e->getMessage(), [], 406);
            } catch (PDOException $e) {
                $itemAttribute->rollback();
                $item->rollback();
                $newProductDesign->rollback();
                $this->error($e->getMessage(), [], 407);
            } catch (Exception $e) {
                $itemAttribute->rollback();
                $item->rollback();
                $newProductDesign->rollback();
                $this->error($e->getMessage(), [], 408);
            }
            if (($itemAttrResult !== false) && ($data['type'] == 1) && ($newProductDesignResult !== false)) {
                $this->success();
            } else {
                $this->error(__('Failed to upload product picture, please try again'));
            }
        }
        $this->view->assign("row", $row);

        return $this->view->fetch();
    }

    public function add_img()
    {
        $item = new \app\admin\model\itemmanage\Item;
        $itemAttribute = new ItemAttribute();
        $newProductDesign = new \app\admin\model\NewProductDesign();
        $newProductDesignDetail = $newProductDesign->where('id', input('ids'))->find();
        $itemId = $item->where('sku', $newProductDesignDetail['sku'])->value('id');
        $row = $item->get($itemId, 'itemAttribute');
        if ($this->request->isAjax()) {
            $params = $this->request->post("row/a");
            $itemAttrData['frame_images'] = $params['frame_images'];
            $itemAttrData['create_frame_images_time'] = date("Y-m-d H:i:s", time());
            $itemAttribute->startTrans();
            $item->startTrans();
            $newProductDesign->startTrans();
            try {
                $itemAttrData['frame_aws_imgs'] = $params['frame_images'];
                $value = $itemAttrData['frame_aws_imgs'];
                $value = explode(',', $value);
                foreach ($value as $k => $v) {
                    $value[$k] = substr($v, 1);
                }
                $itemAttrData['frame_aws_imgs'] = implode(',', $value);
                $itemAttrResult = $itemAttribute->where('item_id', '=', $itemId)->update($itemAttrData);
                $newProductDesignResult = $newProductDesign->where('id', '=', input('ids'))->update(['status' => 7, 'update_time' => date("Y-m-d H:i:s", time())]);

                //添加操作记录
                $valueLog['operator'] = session('admin.nickname');
                $valueLog['addtime'] = date('Y-m-d H:i:s',time());
                $valueLog['node'] = 6;
                $valueLog['operation_type'] = '上传图片';
                $valueLog['design_id'] =input('ids');
                $this->designRecording($valueLog);

                $itemAttribute->commit();
                $item->commit();
                $newProductDesign->commit();
            } catch (ValidateException $e) {
                $itemAttribute->rollback();
                $item->rollback();
                $newProductDesign->rollback();
                $this->error($e->getMessage(), [], 406);
            } catch (PDOException $e) {
                $itemAttribute->rollback();
                $item->rollback();
                $newProductDesign->rollback();
                $this->error($e->getMessage(), [], 407);
            } catch (Exception $e) {
                $itemAttribute->rollback();
                $item->rollback();
                $newProductDesign->rollback();
                $this->error($e->getMessage(), [], 408);
            }
            if (($itemAttrResult !== false) && ($newProductDesignResult !== false)) {
                $this->success();
            } else {
                $this->error(__('Failed to upload product picture, please try again'));
            }
        }
        $this->view->assign("row", $row);

        return $this->view->fetch();
    }
    /**
     * @author zjw
     * @date   2021/4/26 18:17
     * 操作记录
     */
    public function operation_log($ids=null){
        $value = Db::table('fa_new_product_design_log')->where(['design_id'=>$ids])->order('addtime desc')->select();
        $this->assign('list',$value);
        return $this->view->fetch();
    }


    /**
     * @param $value
     * @author zjw
     * @date   2021/4/26 14:26
     */
    public function designRecording($value){
        Db::name('new_product_design_log')->insert($value);
    }
    /**
     * 选品设计管理导出
     * Interface export
     * @package app\admin\controller
     * @author  jhh
     * @date    2021/4/26 14:55:01
     */
    public function export(){
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $item = new Item();
        $itemAttribute =new ItemAttribute();
        $designStatus = input('design_status');
        $platType= input('plat_type');
        $createTime = input('create_time');
        $map = [];
        if ($platType){
            $itemPlatformSku = new ItemPlatformSku();
            $sku = $itemPlatformSku
                ->where(['platform_type'=>$platType])
                ->column('sku');
            $map['sku'] = ['in',$sku];
        }
        if ($designStatus > 0){
            $map['status'] = ['=',$designStatus];
        }
        if ($createTime){
            $createat = explode(' ', $createTime);
            $map['create_time'] = ['between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]]];
        }
        $list = $this->model
            ->field('id,sku')
            ->where($map)
            ->select();

        $list = collection($list)->toArray();
        $sheet1 = [];
        $sheet2 = [];
        $sheet3 = [];
        $sheet4 = [];
        $sheet5 = [];
        $sheet1Key = 0;
        $sheet2Key = 0;
        $sheet3Key = 0;
        $sheet4Key = 0;
        $sheet5Key = 0;
        foreach ($list as $k=>$v){
            $value = $this->model->get($v['id']);
            $where['sku'] = $value->sku;
            $data = $item
                ->where($where)
                ->field('id,category_id')
                ->find();
            $attributeType = $data->category_id;
            $goodsId = $data->id;
            $row =$itemAttribute
                ->where('item_id',$goodsId)
                ->find();
            $list[$k]['detail'] = $row;
            if ($attributeType == 35){
                //耳饰
                $sheet2[$sheet2Key] = $v;
                $sheet2[$sheet2Key]['detail'] = $row;
                $sheet2Key += 1;
            }elseif ($attributeType== 39 || $attributeType == 34){
                //项链/手链
                $sheet3[$sheet3Key] = $v;
                $sheet3[$sheet3Key]['detail'] = $row;
                $sheet3Key += 1;
            }elseif ($attributeType == 38){
                //眼镜链
                $sheet4[$sheet4Key] = $v;
                $sheet4[$sheet4Key]['detail'] = $row;
                $sheet4Key += 1;
            }elseif ($attributeType == 32){
                //镜盒
                $sheet5[$sheet5Key] = $v;
                $sheet5[$sheet5Key]['detail'] = $row;
                $sheet5Key += 1;
            }else{
                $sheet1[$sheet1Key] = $v;//眼镜
                $sheet1[$sheet1Key]['detail'] = $row;//眼镜
                $sheet1Key += 1;
            }
        }
        $spreadsheet = new Spreadsheet();
        $pIndex = 0;
        if (!empty($sheet1)){
            //从数据库查询需要的数据
            $spreadsheet->setActiveSheetIndex(0);
            $spreadsheet->getActiveSheet()->setCellValue("A1", "SKU");
            $spreadsheet->getActiveSheet()->setCellValue("B1", "镜框高（mm）");
            $spreadsheet->getActiveSheet()->setCellValue("C1", "镜面宽（mm）:");
            $spreadsheet->getActiveSheet()->setCellValue("D1", "桥（mm）:");
            $spreadsheet->getActiveSheet()->setCellValue("E1", "镜腿长（mm）:");
            $spreadsheet->getActiveSheet()->setCellValue("F1", "镜架总长（mm）:");
            $spreadsheet->getActiveSheet()->setCellValue("G1", "重量（mm）:");
            //设置宽度
            $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(22);
            $spreadsheet->setActiveSheetIndex(0)->setTitle('眼镜尺寸明细');
            $spreadsheet->setActiveSheetIndex(0);
            $num = 0;
            foreach ($sheet1 as $k=>$v){
                $spreadsheet->getActiveSheet()->setCellValue('A' . ($num * 1 + 2), $v['sku']);
                $spreadsheet->getActiveSheet()->setCellValue('B' . ($num * 1 + 2), $v['detail']['frame_height']);
                $spreadsheet->getActiveSheet()->setCellValue('C' . ($num * 1 + 2), $v['detail']['mirror_width']);
                $spreadsheet->getActiveSheet()->setCellValue('D' . ($num * 1 + 2), $v['detail']['frame_bridge']);
                $spreadsheet->getActiveSheet()->setCellValue('E' . ($num * 1 + 2), $v['detail']['frame_temple_length']);
                $spreadsheet->getActiveSheet()->setCellValue('F' . ($num * 1 + 2), $v['detail']['frame_length']);
                $spreadsheet->getActiveSheet()->setCellValue('G' . ($num * 1 + 2), $v['detail']['frame_weight']);
                $num += 1;
            }
            $pIndex += 1;
        }
        if (!empty($sheet2)){
            $spreadsheet->createSheet();
            $spreadsheet->setActiveSheetIndex($pIndex);
            $spreadsheet->getActiveSheet()->setCellValue("A1", "SKU");
            $spreadsheet->getActiveSheet()->setCellValue("B1", "高度（mm）");
            $spreadsheet->getActiveSheet()->setCellValue("C1", "宽度（mm）:");
            //设置宽度
            $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(22);
            $spreadsheet->setActiveSheetIndex($pIndex)->setTitle('耳饰尺寸明细');
            $spreadsheet->setActiveSheetIndex($pIndex);
            $num = 0;
            foreach ($sheet2 as $k=>$v){
                $spreadsheet->getActiveSheet()->setCellValue('A' . ($num * 1 + 2), $v['sku']);
                $spreadsheet->getActiveSheet()->setCellValue('B' . ($num * 1 + 2), $v['detail']['earrings_height']);
                $spreadsheet->getActiveSheet()->setCellValue('C' . ($num * 1 + 2), $v['detail']['earrings_width']);
                $num += 1;
            }
            $pIndex += 1;
        }

        if (!empty($sheet3)){
            $spreadsheet->createSheet();
            $spreadsheet->setActiveSheetIndex($pIndex);
            $spreadsheet->getActiveSheet()->setCellValue("A1", "SKU");
            $spreadsheet->getActiveSheet()->setCellValue("B1", "周长（mm）");
            $spreadsheet->getActiveSheet()->setCellValue("C1", "延长链（mm）:");
            //设置宽度
            $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(22);
            $spreadsheet->setActiveSheetIndex($pIndex)->setTitle('项链手链尺寸明细');
            $spreadsheet->setActiveSheetIndex($pIndex);
            $num = 0;
            foreach ($sheet3 as $k=>$v){
                $spreadsheet->getActiveSheet()->setCellValue('A' . ($num * 1 + 2), $v['sku']);
                $spreadsheet->getActiveSheet()->setCellValue('B' . ($num * 1 + 2), $v['detail']['necklace_perimeter']);
                $spreadsheet->getActiveSheet()->setCellValue('C' . ($num * 1 + 2), $v['detail']['necklace_chain']);
                $num += 1;
            }
            $pIndex += 1;
        }

        if (!empty($sheet4)){
            $spreadsheet->createSheet();
            $spreadsheet->setActiveSheetIndex($pIndex);
            $spreadsheet->getActiveSheet()->setCellValue("A1", "SKU");
            $spreadsheet->getActiveSheet()->setCellValue("B1", "周长（mm）");
            //设置宽度
            $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(22);
            $spreadsheet->setActiveSheetIndex($pIndex)->setTitle('眼镜链尺寸明细');
            $spreadsheet->setActiveSheetIndex($pIndex);
            $num = 0;
            foreach ($sheet4 as $k=>$v){
                $spreadsheet->getActiveSheet()->setCellValue('A' . ($num * 1 + 2), $v['sku']);
                $spreadsheet->getActiveSheet()->setCellValue('B' . ($num * 1 + 2), $v['detail']['eyeglasses_chain']);
                $num += 1;
            }
            $pIndex += 1;
        }

        if (!empty($sheet5)){
            $spreadsheet->createSheet();
            $spreadsheet->setActiveSheetIndex($pIndex);
            $spreadsheet->getActiveSheet()->setCellValue("A1", "SKU");
            $spreadsheet->getActiveSheet()->setCellValue("B1", "镜盒高度");
            $spreadsheet->getActiveSheet()->setCellValue("C1", "镜盒宽度:");
            //设置宽度
            $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(22);
            $spreadsheet->setActiveSheetIndex($pIndex)->setTitle('镜盒尺寸明细');
            $spreadsheet->setActiveSheetIndex($pIndex);
            $num = 0;
            foreach ($sheet5 as $k=>$v){
                $spreadsheet->getActiveSheet()->setCellValue('A' . ($num * 1 + 2), $v['sku']);
                $spreadsheet->getActiveSheet()->setCellValue('B' . ($num * 1 + 2), $v['detail']['box_height']);
                $spreadsheet->getActiveSheet()->setCellValue('C' . ($num * 1 + 2), $v['detail']['box_width']);
                $num += 1;
            }
        }

        //设置边框
        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // 设置border样式
                    'color'       => ['argb' => 'FF000000'], // 设置border颜色
                ],
            ],
        ];
        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑')->setSize(12);
        $setBorder = 'A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow();
        $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);
        $spreadsheet->getActiveSheet()->getStyle('A1:Q' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);
        $format = 'xlsx';
        $savename = '产品设计管理';
        if ($format == 'xls') {
            //输出Excel03版本
            header('Content-Type:application/vnd.ms-excel');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xls";
        } elseif ($format == 'xlsx') {
            //输出07Excel版本
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xlsx";
        }
        //输出名称
        header('Content-Disposition: attachment;filename="' . $savename . '.' . $format . '"');
        //禁止缓存
        header('Cache-Control: max-age=0');
        $writer = new $class($spreadsheet);
        $writer->save('php://output');
    }
}
