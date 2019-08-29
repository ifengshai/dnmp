<?php

namespace app\admin\controller\itemmanage;
use think\Db;
use app\common\controller\Backend;
use app\admin\model\itemmanage\attribute\ItemAttributePropertyGroup;
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
        $this->platform = new \app\admin\model\platformManage\ManagtoPlatform;
        $this->view->assign('PutAway',$this->model->isPutAway());
        $this->view->assign('LevelList',$this->model->getLevelList());
        $this->view->assign('CategoryList',$this->model->getCategoryList());
        //$this->view->assign('PropertyGroup',(new ItemAttributePropertyGroup())->propertyGroupList());
        $this->view->assign('AttrGroup',$this->model->getAttrGroup());
        $this->view->assign('PlatformList',$this->platform->managtoPlatformList());
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
            $managtoUrl = $platformRow->managto_url;
            if(!$managtoUrl){
                $this->error(__('The platform url does not exist. Please go to edit it and it cannot be empty'));
            }
            $categoryRow = $this->model->get($ids);
//            echo $categoryRow->pid;
//            echo '<br>';
//            echo $categoryRow->name;
//            exit;
            $client = new \SoapClient($managtoUrl.'/api/soap/?wsdl');
            //$session = $client->login($platformRow->managto_account,$platformRow->managto_key);
            $session = $client->login($platformRow->managto_account,$platformRow->managto_key);
            $result = $client->call($session, 'catalog_category.create', array(286, array(
                'name' => '太阳镜',
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
            var_dump ($result);
            exit;
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
            $list = $this->model->where($pk, 'in', $ids)->select();

            $count = 0;
            Db::startTrans();
            try {
                foreach ($list as $k => $v) {
                    $count += $v->delete();
                }
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

}
