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

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\itemmanage\ItemCategory;
        $this->view->assign('PutAway',$this->model->isPutAway());
        $this->view->assign('LevelList',$this->model->getLevelList());
        $this->view->assign('CategoryList',$this->model->getCategoryList());
        //$this->view->assign('PropertyGroup',(new ItemAttributePropertyGroup())->propertyGroupList());
        $this->view->assign('AttrGroup',$this->model->getAttrGroup());
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
