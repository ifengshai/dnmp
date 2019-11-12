<?php

namespace app\admin\controller\itemmanage;
use think\Db;
use app\common\controller\Backend;
use app\admin\model\itemmanage\attribute\ItemAttributePropertyGroup;
use think\Exception;
use think\exception\ErrorException;

/**
 * 商品分类管理
 *
 * @icon fa fa-circle-o
 */
class ItemCategory extends Backend
{
    
    /**
     * ItemCategory模型对象
     * @var \app\admin\model\itemmanage\ItemCategory
     */
    protected $model = null;
    protected $platform = null;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\itemmanage\ItemCategory;
        $this->platform = new \app\admin\model\platformmanage\MagentoPlatform;
        $this->view->assign('PutAway',$this->model->isPutAway());
        $this->view->assign('LevelList',$this->model->getLevelList());
        $this->view->assign('CategoryList',$this->model->getCategoryList());
        //$this->view->assign('PropertyGroup',(new ItemAttributePropertyGroup())->propertyGroupList());
        $this->view->assign('AttrGroup',$this->model->getAttrGroup());
        $this->view->assign('PlatformList',$this->platform->magentoPlatformList());
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
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
            $rsAll = $this->model->getAjaxCategoryList();
            $list = collection($list)->toArray();
            //$propertyGroup = (new ItemAttributePropertyGroup())->propertyGroupList();
            $attributeGroup = $this->model->getAttrGroup();
            foreach ($list as $k =>$v){
                if($v['pid']){
                    $list[$k]['pid'] = $rsAll[$v['pid']];
                }
                if($v['attribute_group_id']){
                    $list[$k]['attribute_group_id'] = $attributeGroup[$v['attribute_group_id']];
                }

            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 编辑
     */
    public function edit($ids = null)
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
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    //编辑完成之后要把上传状态全部变成未上传状态
                    $params['is_upload_zeelool'] = 2;
                    $params['is_upload_voogueme'] = 2;
                    $params['is_upload_nihao'] = 2;
                    $result = $row->allowField(true)->save($params);
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
     * 将平台商品分类上传至mangento平台
     */
    public function uploadItemCategory($ids = null,$platformId=null)
    {
        if($this->request->isAjax()){
            if(!is_array($ids) || in_array("",$ids)){
                $this->error(__('Error selecting category parameters. Please reselect or contact the developer'));
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
            $magentoUrl = $platformRow->magento_url;
            if(!$magentoUrl){
                $this->error(__('The platform url does not exist. Please go to edit it and it cannot be empty'));
            }
            $categoryRow = $this->model->get($ids);
            //查询数据库当中是否存在这个平台对应返回来的id,如果不存在的话则添加分类，存在的话则更新分类
             //拼接分类中存在的字段 zeelool_id/voogueme_id/nihao_id
             $name_field = $platformRow->name.'_id';
             //拼接分类中状态存在的字段is_upload_zeelool/is_upload_voogueme/is_upload_nihao
             $is_upload_field = 'is_upload_'.$platformRow->name;
             try{
                 $client = new \SoapClient($magentoUrl.'/api/soap/?wsdl');
                 $session = $client->login($platformRow->magento_account,$platformRow->magento_key);
             }catch (Exception $e){
                 $this->error($e->getMessage());
             }catch(ErrorException $e){
                 $this->error($e->getMessage());
             }
            if(($categoryRow->$name_field)>0){ //更新
                $result = $client->call($session, 'catalog_category.update', array($categoryRow->$name_field, array(
                    'name' => $categoryRow->name,
                    'is_active' => 1,
                    'position' => 1,
                    //<!-- position parameter is deprecated, category anyway will be positioned in the end of list
                    //and you can not set position directly, use catalog_category.move instead -->
                    'available_sort_by' => 'position',
                    'custom_design' => null,
                    'custom_apply_to_products' => null,
                    'custom_design_from' => null,
                    'custom_design_to' => null,
                    'custom_layout_update' => null,
                    'default_sort_by' => 'position',
                    'description' => 'Category description',
                    'display_mode' => null,
                    'is_anchor' => 0,
                    'landing_page' => null,
                    'meta_description' => 'Category meta description',
                    'meta_keywords' => 'Category meta keywords',
                    'meta_title' => 'Category meta title',
                    'page_layout' => 'two_columns_left',
                    'url_key' => 'url-key',
                    'include_in_menu' => 1,
                )));
                if($result){
                    $where['id'] = $categoryRow->id;
                    $data[$is_upload_field] = 1;
                    $categoryRowRs = $this->model->isUpdate(true, $where)->save($data);
                    if($categoryRowRs){
                        $this->success();
                    }else{
                        $this->error('Update failed. Please try again');
                    }
                }
            }else{ //添加
                //查看是否存在分类上级并且上级是否存在对应的zeelool_id/voogueme_id/nihao_id
                if(($categoryRow->pid)>0){
                    $parent_categoryRow = $this->model->get($categoryRow->pid);
                    $parent_name_field = ($parent_categoryRow->$name_field)>0 ? $parent_categoryRow->$name_field : 1;
                }else{
                    $parent_name_field = 1;
                }
                $result = $client->call($session, 'catalog_category.create', array($parent_name_field, array(
                    'name' => $categoryRow->name,
                    'is_active' => 1,
                    'position' => 1,
                    //<!-- position parameter is deprecated, category anyway will be positioned in the end of list
                    //and you can not set position directly, use catalog_category.move instead -->
                    'available_sort_by' => 'position',
                    'custom_design' => null,
                    'custom_apply_to_products' => null,
                    'custom_design_from' => null,
                    'custom_design_to' => null,
                    'custom_layout_update' => null,
                    'default_sort_by' => 'position',
                    'description' => 'Category description',
                    'display_mode' => null,
                    'is_anchor' => 0,
                    'landing_page' => null,
                    'meta_description' => 'Category meta description',
                    'meta_keywords' => 'Category meta keywords',
                    'meta_title' => 'Category meta title',
                    'page_layout' => 'two_columns_left',
                    'url_key' => 'url-key',
                    'include_in_menu' => 1,
                )));
                if($result){
                    $where['id'] = $categoryRow->id;
                    $data[$is_upload_field] = 1;
                    $data[$name_field] = $result;
                    $categoryRowRs = $this->model->isUpdate(true, $where)->save($data);
                    if($categoryRowRs){
                        $this->success();
                    }else{
                        $this->error('Update failed. Please try again');
                    }
                }
            }
        }else{
            $this->error('404 Not found');
        }
    }
    /**
     * 删除
     */
    public function del($ids = "")
    {
        if ($ids) {
            $nextIds = $this->model->getLowerCategory($ids);
            if($nextIds){
                $ids = $ids.','.$nextIds;
            }
//            dump($ids);
//            //exit;
            $pk = $this->model->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            //$list = $this->model->where($pk, 'in', $ids)->select();

            $count = 0;
            Db::startTrans();
            try {
                // foreach ($list as $k => $v) {
                //     $count += $v->delete();
                // }
                $count = $this->model->where($pk,'in',$ids)->update(['is_del'=>2]);
                Db::commit();
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            if ($count) {
                $this->success();
            } else {
                $this->error(__('No rows were deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }
    /**
     * 分类上架
     */
    public function putaway($ids = null)
    {
        if ($this->request->isAjax()) {
            $map['id'] = ['in', $ids];
            $row = $this->model->where($map)->field('id,is_putaway')->select();
            foreach ($row as $v) {
                if ( 0 !=$v['is_putaway']) {
                    $this->error('只有下架状态才能操作！！');
                }
            }
            $data['is_putaway'] = 1;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res !== false) {
                $this->success('上架成功');
            } else {
                $this->error('上架失败');
            }
        } else {
            $this->error('404 Not found');
        }
    }
    /***
     * 分类下架
     */
    public function soldout($ids = null)
    {
        if ($this->request->isAjax()) {
            $map['id'] = ['in', $ids];
            $row = $this->model->where($map)->field('id,is_putaway')->select();
            foreach ($row as $v) {
                if ( 1 != $v['is_putaway']) {
                    $this->error('只有上架状态才能操作！！');
                }
            }
            $data['is_putaway'] = 0;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res !== false) {
                $this->success('下架成功');
            } else {
                $this->error('下架失败');
            }
        } else {
            $this->error('404 Not found');
        }
    }

}
