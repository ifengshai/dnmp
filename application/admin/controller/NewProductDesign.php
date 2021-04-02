<?php

namespace app\admin\controller;

use app\admin\model\Admin;
use app\admin\model\AuthGroup;
use app\admin\model\AuthGroupAccess;
use app\admin\model\DistributionLog;
use app\admin\model\itemmanage\attribute\ItemAttribute;
use app\admin\model\itemmanage\Item;
use app\admin\model\itemmanage\ItemBrand;
use app\admin\model\order\Order;
use app\common\controller\Backend;
use Aws\S3\S3Client;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

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

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\NewProductDesign;
        $this->itemAttribute = new \app\admin\model\itemmanage\attribute\ItemAttribute;
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
        $this->category = new \app\admin\model\itemmanage\ItemCategory;
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
        //当前是否为关联查询
        $this->relationSearch = false;
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            $filter = json_decode($this->request->get('filter'), true);

            if ($filter['label']) {
                $map['status'] = $filter['label'];
            }
            unset($filter['label']);
            if ($filter['responsible_id']){
                $whe_like['nickname'] = ['like','%'.$filter['responsible_id'].'%'];
                $responsible_id =  $admin->where($whe_like)->column('id');
                if ($responsible_id){
                    $map['responsible_id'] = ['in',$responsible_id];
                }else{
                    $map['responsible_id'] = ['eq','999999999'];
                }
            }
            unset($filter['responsible_id']);
            $this->request->get(['filter' => json_encode($filter)]);
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            foreach ($list as $row) {
                $row->visible(['id', 'sku', 'status', 'responsible_id', 'create_time']);

            }
            $list = collection($list)->toArray();

            foreach ($list as $key=>$item){
                $list[$key]['label'] = $map['status']?$map['status']:0;
                if ($item['responsible_id'] !==null){
                    $list[$key]['responsible_id'] = $admin->where('id',$item['responsible_id'])->value('nickname');
                }else{
                    $list[$key]['responsible_id'] = '暂无';
                }
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }


    public function detail($ids=null)
    {
        $item = new Item();
        $itemAttribute = new \app\admin\model\itemmanage\ItemAttribute();
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
        $img = explode(',',$row->frame_aws_imgs);
        $net = 'https://mojing.s3-us-west-2.amazonaws.com/';
        if (is_array($img)){
            foreach ($img as $key=>$value){
                $img[$key] = $net.$value;
            }
        }

        $this->assign('attributeType',$attributeType);
        $this->assign('goodsId',$goodsId);
        $this->assign('ids',$ids);
        $this->assign('row',$row);
        $this->assign('img',$img);
        return $this->view->fetch();
    }

    //录尺寸
    public function record_size($ids =null)
    {
        $itemAttribute = new \app\admin\model\itemmanage\ItemAttribute();
        if ($this->request->post()){
           $data = $this->request->post();
            if ($data['attributeType'] ==1){
                if ($data['row']['frame_height'] < 0.1){
                    $this->error('请输入正确的镜框高数值');
                }
                if($data['row']['frame_bridge']<0.1){
                    $this->error('请输入正确的桥数值');
                }
                if($data['row']['frame_temple_length']<0.1){
                    $this->error('请输入正确的镜腿长数值');
                }
                if($data['row']['frame_height']<0.1){
                    $this->error('请输入正确的镜架总长数值');
                }
                if($data['row']['frame_weight']<0.1){
                    $this->error('请输入正确的重量数值');
                }
                if($data['row']['mirror_width']<0.1){
                    $this->error('请输入正确的镜面宽数值');
                }

            }
            if ($data['attributeType'] ==34){
                if($data['row']['necklace_perimeter']<0.1){
                    $this->error('请输入正确的周长数值');
                }
                if($data['row']['necklace_chain']<0.1){
                    $this->error('请输入正确的延长链数值');
                }
            }
            if ($data['attributeType'] ==35){
                if($data['row']['earrings_height']<0.1){
                    $this->error('请输入正确的高度数值');
                }
                if($data['row']['earrings_width']<0.1){
                    $this->error('请输入正确的宽度数值');
                }
            }
            if ($data['attributeType'] ==38){
                if($data['row']['eyeglasses_chain']<0.1){
                    $this->error('请输入正确的周长数值数值');
                }
            }
            if ($data['attributeType'] ==32 ||$data['attributeType'] ==39){
                if($data['row']['necklace_perimeter']<0.1){
                    $this->error('请输入正确的周长数值');
                }
                if($data['row']['necklace_chain']<0.1){
                    $this->error('请输入正确的延长链数值');
                }
            }

            //标记打印状态
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
        $item = new Item();
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

    //更改状态
    public function change_status()
    {
       $ids =  $this->request->get('ids');
       $status =  $this->request->get('status');
       empty($ids) && $this->error('缺少重要参数');
       empty($status) && $this->error('数据异常');
        $map['id'] = $ids;
        if ($status ==9){
            $status =6;
        }
        $data['status'] = $status;
        $data['update_time']  = date("Y-m-d H:i:s", time());
        $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
        if ($res){
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
                $this->success('人员分配成功');
            }else{
                $this->error('人员分配失败');
            }
        }
        //获取筛选人
        $authGroupAccess= new AuthGroupAccess();
        $auth_user = $authGroupAccess
            ->alias('a')
            ->join(['fa_admin'=>'b'],'a.uid=b.id')
            ->where('a.group_id=71')
            ->field('id,nickname')
            ->select();
        $this->assign('ids',$ids);
        $this->assign('auth_user',collection($auth_user)->toArray());
        return $this->view->fetch();

    }

    //上传图片
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
            $item_status = $params['item_status'];
            $itemAttrData['frame_images'] = $params['frame_images'];
            $itemAttrData['create_frame_images_time'] = date("Y-m-d H:i:s", time());
            $imgsArr = [];
            $net = 'https://mojing.s3-us-west-2.amazonaws.com/';
            $itemAttribute->startTrans();
            $item->startTrans();
            $newProductDesign->startTrans();
            try {
                $itemResult = $item->where('id', '=', $itemId)->update(['item_status' => $item_status]);
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
            if (($itemAttrResult !== false) && ($itemResult !== false) && ($data['type'] == 1) && ($newProductDesignResult !== false)){
                $this->success();
            } else {
                $this->error(__('Failed to upload product picture, please try again'));
            }
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }


}
