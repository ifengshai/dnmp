<?php

namespace app\admin\controller\itemmanage;
use think\Db;
use think\Request;
use app\common\controller\Backend;
use app\admin\model\platformManage\ManagtoPlatform;
use app\admin\model\itemmanage\Item;
/**
 * 平台SKU管理
 *
 * @icon fa fa-circle-o
 */
class ItemPlatformSku extends Backend
{
    
    /**
     * ItemPlatformSku模型对象
     * @var \app\admin\model\itemmanage\ItemPlatformSku
     */
    protected $model = null;
    protected $platform = null;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\itemmanage\ItemPlatformSku;
        $this->platform = new \app\admin\model\platformManage\ManagtoPlatform;

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    //平台SKU首页
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            if(!empty($list) && is_array($list)){
                $platform = (new ManagtoPlatform())->getOrderPlatformList();
                foreach ($list as $k =>$v){
                    if($v['platform_type']){
                        $list[$k]['platform_type'] = $platform[$v['platform_type']];
                    }
                }
            }

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->view->assign('PlatformList',$this->platform->managtoPlatformList());
        return $this->view->fetch();
    }
    //商品预售首页
    public function presell()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->whereNotNull('presell_create_time')
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->whereNotNull('presell_create_time')
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            if(!empty($list) && is_array($list)){
                $platform = (new ManagtoPlatform())->getOrderPlatformList();
                foreach ($list as $k =>$v){
                    if($v['platform_type']){
                        $list[$k]['platform_type'] = $platform[$v['platform_type']];
                    }
                }
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
    /***
     * 商品上架
     */
    public function putaway()
    {
        if($this->request->isAjax()){
            $id = $this->request->param('ids');
            $row = $this->model->get($id);
            if($row['platform_sku_status'] == 1){
                $this->error('商品正在上架中,不能重复上架');
            }
            $map['id'] = $id;
            $data['platform_sku_status'] = 1;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res) {
                $this->success('上架成功');
            } else {
                $this->error('上架失败');
            }
        }else{
            $this->error('404 Not found');
        }
    }
    /****
     * 商品下架
     */
    public function soldOut()
    {
        if($this->request->isAjax()){
            $id = $this->request->param('ids');
            $row = $this->model->get($id);
            if($row['platform_sku_status'] == 2){
                $this->error('商品正在下架中,不能重复下架');
            }
            $map['id'] = $id;
            $data['platform_sku_status'] = 2;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res) {
                $this->success('下架成功');
            } else {
                $this->error('下架失败');
            }
        }else{
            $this->error('404 Not found');
        }
    }
    /***
     * 添加商品预售
     */
    public function addPresell()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                if(empty($params['platform_sku'])){
                    $this->error(__('Platform sku cannot be empty'));
                }
                if(empty($params['presell_num'])){
                    $this->error(__('SKU pre-order quantity cannot be empty'));
                }
//                echo $params['presell_start_time'];
//                echo '<br>';
//                echo $params['presell_end_time'];
//                echo '<br>';
//                echo date("Y-m-d H:i:s", time());
//                exit;
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $params['presell_create_person'] = session('admin.nickname');
                    $params['presell_create_time'] = $now_time =  date("Y-m-d H:i:s", time());
                    if($now_time>=$params['presell_start_time']){ //如果当前时间大于开始时间
                        $params['presell_status'] = 2;
                    }
                    $result = $this->model->allowField(true)->save($params,['platform_sku'=>$params['platform_sku']]);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }
    /***
     * 编辑商品预售
     */
    public function editPresell($ids=null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                if(empty($params['platform_sku'])){
                    $this->error(__('Platform sku cannot be empty'));
                }
                if(empty($params['presell_num'])){
                    $this->error(__('SKU pre-order quantity cannot be empty'));
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $params['presell_create_person'] = session('admin.nickname');
                    $params['presell_create_time'] = $now_time =  date("Y-m-d H:i:s", time());
                    if($now_time>=$params['presell_start_time']){ //如果当前时间大于开始时间
                        $params['presell_status'] = 2;
                    }
                    $result = $row->allowField(true)->save($params,['platform_sku'=>$params['platform_sku']]);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    /***
     * 开启预售
     */
    public function openStart($ids = null)
    {
        if($this->request->isAjax()){
            $row = $this->model->get($ids);
            if($row['presell_status'] == 2){
                $this->error(__('Pre-sale on, do not repeat on'));
            }
            $now_time = date('Y-m-d H:i:s',time());
            if($row['presell_end_time']<$now_time){
                $this->error(__('The closing time has expired, please select again'));
            }
            $map['id'] = $ids;
            $data['presell_status'] = 2;
            $data['presell_open_time'] =  date('Y-m-d H:i:s',time());
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res) {
                $this->success('预售开启成功');
            } else {
                $this->error('预售开启失败');
            }
        }else{
            $this->error('404 Not found');
        }
    }
    /***
     * 关闭预售
     */
    public function openEnd($ids = null)
    {
        if($this->request->isAjax()){
            $row = $this->model->get($ids);
            if($row['presell_status'] == 3){
                $this->error(__('Pre-sale on, do not repeat on'));
            }
            $map['id'] = $ids;
            $data['presell_status'] = 3;
            $data['presell_open_time'] =  date('Y-m-d H:i:s',time());
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res) {
                $this->success('关闭预售成功');
            } else {
                $this->error('关闭预售失败');
            }
        }else{
            $this->error('404 Not found');
        }
    }
    /***
     * 异步查询平台sku
     */
    public function ajaxGetLikePlatformSku(Request $request)
    {
        if ($this->request->isAjax()) {
            $origin_sku = $request->post('origin_sku');
            $result = $this->model->likePlatformSku($origin_sku);
            if (!$result) {
                return $this->error('商品SKU不存在，请重新尝试');
            }
            return $this->success('', '', $result, 0);
        } else {
            $this->error('404 not found');
        }
    }
    /***
     * 异步查询用户输入的平台sku是否存在
     */
    public function ajaxGetPlatformSkuInfo(Request $request)
    {
        if ($this->request->isAjax()) {
            $platform_sku = $request->post('platform_sku');
            $result = $this->model->getPlatformSku($platform_sku);
            if (!$result) {
                return $this->error('平台商品SKU不存在，请重新填写');
            }
            return $this->success('是正确的SKU');
        } else {
            $this->error('404 not found');
        }
    }
    /***
     * 上传商品到magento平台
     */
    public function uploadItem($ids=null,$platformId=null)
    {
        if($this->request->isAjax()){
            if(!is_array($ids) || in_array("",$ids)){
                $this->error(__('Error selecting item parameters. Please reselect or contact the developer'));
            }
            if(count($ids)>1){
                $this->error(__('Multiple data updates are not currently supported, please update one at a time'));
            }
            if(!$platformId){
                $this->error(__('Error selecting platform parameters. Please reselect or contact the developer'));
            }
            $platformRow = $this->platform->get($platformId);
            if(!$platformRow){
                $this->error(__('Platform information error, please try again or contact the developer'));
            }
            $managtoUrl = $platformRow->managto_url;
            if(!$managtoUrl){
                $this->error(__('The platform url does not exist. Please go to edit it and it cannot be empty'));
            }

        }else{
            $this->error('404 Not found');
        }
    }
    /****
     * 编辑后面的商品上传至对应平台
     */
    public function afterUploadItem($ids = null)
    {
        if($this->request->isAjax()){
            if(count($ids)>1){ //一次只能上传一个商品
                $this->error(__('Multiple data updates are not currently supported, please update one at a time'));
            }
            $itemPlatformRow = $this->model->findItemPlatform($ids);
            if(!$itemPlatformRow){ //对应商品不正确或者平台不正确
                $this->error(__('Incorrect product or incorrect platform'));
            }
            if($itemPlatformRow['is_upload_item'] == 2){ //控制不上传商品信息
                $this->error(__('The corresponding platform does not need to upload product information, please do not upload'));
            }
            if(!$itemPlatformRow['managto_url']){
                $this->error(__('The platform url does not exist. Please go to edit it and it cannot be empty'));
            }
            if($itemPlatformRow['is_upload'] == 1){ //商品已经上传，无需再次上传
                $this->error(__('The product has been uploaded, there is no need to upload again'));
            }
            if(empty($itemPlatformRow['item_attr_name']) || empty($itemPlatformRow['item_type'])){ //平台商品类型和商品属性
                $this->error(__('The product attributes or product types of the platform are not filled in'));
            }
            $uploadFields = $itemPlatformRow['upload_field'];
            if(empty($uploadFields)){
               $this->error(__('The upload field cannot be empty, please go to the platform to edit'));
            }
            $uploadFieldsArr = explode(',',$uploadFields);
            $itemRow = (new Item())->getItemRow($itemPlatformRow['sku']);
            $managtoUrl = $itemPlatformRow['managto_url'];
            try{
                $client = new \SoapClient($managtoUrl.'/api/soap/?wsdl');
                $session = $client->login($itemPlatformRow['managto_account'],$itemPlatformRow['managto_key']);
                $attributeSets = $client->call($session, 'product_attribute_set.list');
               // $attributeSet = current($attributeSets);
            }catch (\SoapFault $e){
                $this->error(__('Platform account or key is incorrect, please go to the platform to edit'));
            }catch (\Exception $e){
                $this->error(__('An error has occurred. Please contact the developer'));
            }
            if(is_array($attributeSets)){
                $attributeSet = [];
                foreach ($attributeSets as $k =>$v){
                    if($v['name'] == $itemPlatformRow['item_attr_name']){ //如果相等的话
                        $attributeSet['set_id'] = $v['set_id'];
                        $attributeSet['name'] = $v['name'];
                    }
                }
                $attributeList = $client->call( //求出商品属性列表
                    $session,
                    "product_attribute.list",
                    array(
                        $attributeSet['set_id']
                    )
                );
                //求出需要上传的属性和它们的值
                if(is_array($attributeList)){
                    $attributeListArr = [];
                    foreach ($attributeList as $k =>$v){
                        if(in_array($v['code'],$uploadFieldsArr)){
                            if(($v['type'] == 'multiselect') ||($v['type'] == 'select')){
                                $v['valueOptions'] = $client->call(
                                    $session,
                                    "product_attribute.options",
                                    array(
                                        $v['attribute_id']
                                    )
                                );
                            }
                            $attributeListArr [] = $v;
                        }
                    }
                }
//                foreach ($attributeListArr as $k=>$v){
//                    if(in_array())
//                }
//                echo '<pre>';
//                dump($attributeListArr);
//                exit;
            }
            if($itemPlatformRow['magento_id']>0){ //更新商品

            }else{ //添加商品
                try{
                    $result = $client->call($session, 'catalog_product.create', array($itemPlatformRow['item_type'], $attributeSet['set_id'], $itemRow['sku'], array(
                        'categories' => array(2),
                        'websites' => array(1),
                        'name' => $itemRow['name'],
                        'description' => 'Product description',
                        'short_description' => 'Product short description',
                        'weight' => $itemRow['frame_weight'],
                        'status' => $itemRow['is_open'],
                        'url_key' => $itemRow['sku'],
                        'url_path' => $itemRow['sku'],
                        'visibility' => '4',
                        'price' => '100',
                        'tax_class_id' => 1,
                        'meta_title' => 'Product meta title',
                        'meta_keyword' => 'Product meta keyword',
                        'meta_description' => 'Product meta description',
                        'frame_width' => $itemRow['frame_width'],
                        'shape'       =>'24,25'
                    )));
                    if($result){
                        $where['id'] = $itemPlatformRow['id'];
                        $data['magento_id'] = $result;
                        $data['is_upload'] = 1;
                        $categoryRowRs = $this->model->isUpdate(true, $where)->save($data);
                        if($categoryRowRs){
                            $this->success('上传成功');
                        }else{
                            $this->error('Update failed. Please try again');
                        }
                    }
                }catch(\SoapFault $e){
                    $this->error($e->getMessage());
                }

            }
            exit;

        }else{
            $this->error('404 Not found');
        }
    }

    /***
     * 上传商品图片到对应的平台
     */
    public function uploadImagesToPlatform($ids = null)
    {
        if($this->request->isAjax()){
            if(count($ids)>1){ //一次只能上传一个商品
                $this->error(__('Multiple data updates are not currently supported, please update one at a time'));
            }
            $itemPlatformRow = $this->model->findItemPlatform($ids);
            if(!$itemPlatformRow){ //对应商品不正确或者平台不正确
                $this->error(__('Incorrect product or incorrect platform'));
            }
            if($itemPlatformRow['is_upload_item'] == 2){ //控制不上传商品信息
                $this->error(__('The corresponding platform does not need to upload product information, please do not upload'));
            }
            if(!$itemPlatformRow['managto_url']){
                $this->error(__('The platform url does not exist. Please go to edit it and it cannot be empty'));
            }
            if(empty($itemPlatformRow['magento_id'])){
                $this->error(__('The corresponding product Id does not exist, please upload the product to the platform first'));
            }
//            if($itemPlatformRow['is_upload_images'] == 1){ //商品图片已经上传，无需再次上传
//                $this->error(__('The product has been uploaded, there is no need to upload again'));
//            }
            if(empty($itemPlatformRow['item_attr_name']) || empty($itemPlatformRow['item_type'])){ //平台商品类型和商品属性
                $this->error(__('The product attributes or product types of the platform are not filled in'));
            }
            $itemImagesRow = (new Item())->getItemImagesRow($itemPlatformRow['sku']);
            if(empty($itemImagesRow['frame_images'])){
                $this->error(__('No pictures of the goods have been uploaded. Please upload them'));
            }

            //需要上传的图片
            $itemImagesArr = explode(',',$itemImagesRow['frame_images']);
            $managtoUrl = $itemPlatformRow['managto_url'];
                try{
                    $client = new \SoapClient($managtoUrl.'/api/soap/?wsdl');
                    $session = $client->login($itemPlatformRow['managto_account'],$itemPlatformRow['managto_key']);
                    //如果存在需要删除的图片就删除magento平台上的照片
                    if(!empty($itemImageDelArr)){
                        //需要删除的图片
                        $itemImageDelArr = explode(',',$itemPlatformRow['uploaded_images']);
                        foreach ($itemImageDelArr as $kDel =>$vDel){
                             $client->call($session, 'catalog_product_attribute_media.remove', array('product' =>$itemPlatformRow['magento_id'], 'file' => $vDel));
                        }
                    }
                    //添加图片到magento平台
                    foreach($itemImagesArr as $k =>$v){ //循环照片
                        $file = array(
                            'content' => base64_encode(file_get_contents('./'.$v)),
                            'mime' => 'image/jpeg'
                        );
                        $result[] = $client->call(
                            $session,
                            'catalog_product_attribute_media.create',
                            [
                                $itemPlatformRow['magento_id'],
                                ['file'=>$file, 'label'=>'Label', 'position'=>'1', 'types'=>['thumbnail'], 'exclude'=>0]
                            ]
                        );
                    }
                    $client->endSession($session);
                }catch (\SoapFault $e){
                    $this->error($e->getMessage());
                    //$this->error(__('Platform account or key is incorrect, please go to the platform to edit'));
                }catch (\Exception $e){
                    $this->error($e->getMessage());
                    //$this->error(__('An error has occurred. Please contact the developer'));
                }
            if(is_array($result) && count($result)>=1){
                $where['id'] = $itemPlatformRow['id'];
                $data['is_upload_images'] = 1;
                $data['uploaded_images'] = implode(',',$result);
                $updateRow = $this->model->isUpdate(true, $where)->save($data);
                if($updateRow){

                    $this->success(__('upload successful'));
                }else{
                    $this->error(__('upload error'));
                }
            }else{
                $this->error(__('upload error'));
            }
        }else{
            $this->error('404 Not found');
        }
    }
}
