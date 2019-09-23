<?php

namespace app\admin\controller\itemmanage\attribute;

use app\common\controller\Backend;
use think\Db;
use think\Request;

/**
 * 商品属性项列管理
 *
 * @icon fa fa-circle-o
 */
class ItemAttributeProperty extends Backend
{
    
    /**
     * ItemAttributeProperty模型对象
     * @var \app\admin\model\itemmanage\attribute\ItemAttributeProperty
     */
    protected $model = null;
    protected $propertyValue = null;
    protected $itemAttribute = null;
    protected $modelValidate = true;
    protected $modelSceneValidate = true;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\itemmanage\attribute\ItemAttributeProperty;
        $this->itemAttribute = new \app\admin\model\itemmanage\attribute\ItemAttribute;
        $this->propertyValue = new \app\admin\model\itemmanage\attribute\ItemAttributePropertyValue;
        $this->view->assign('attProValIsRequired',$this->model->attProValIsRequired());
        $this->view->assign('attProValInputMode',$this->model->attProValInputMode());

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

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
                    $params['create_person'] = session('admin.nickname');
                    $params['create_time']   = date("Y-m-d H:i:s",time());
                    $result = $this->model->allowField(true)->save($params);
                    Db::commit();
                }catch (\think\exception\ValidateException $e)
                {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                catch (ValidateException $e) {
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
                    $name_value_cn = $this->request->post("name_value_cn/a");
                    if($name_value_cn){
                        $name_value_en = $this->request->post("name_value_en/a");
                        $code_rule = $this->request->post("code_rule/a");
                        $descb = $this->request->post("descb/a");
                        $data = [];
                        $strArr = '';
                        foreach ($name_value_cn as $k => $v) {
                            $data[$k]['property_id'] = $this->model->id;
                            $data[$k]['name_value_cn'] = $v;
                            $data[$k]['name_value_en'] = $name_value_en[$k];
                            $data[$k]['code_rule'] = $code_rule[$k];
                            $data[$k]['descb'] = $descb[$k];
                            $data[$k]['create_person'] = session('admin.nickname');
                            $data[$k]['create_time']   = date("Y-m-d H:i:s",time());
                            $strArr.= "'$name_value_en[$k]'".',';
                        }
                        $str = rtrim($strArr,',');
                        $this->propertyValue->allowField(true)->saveAll($data);
                        $rs = $this->itemAttribute->appendField($params['input_mode'],$params['name_en'],$params['name_cn'],$str);
                        //批量添加
                    }else{
                        $rs=$this->itemAttribute->appendField($params['input_mode'],$params['name_en'],$params['name_cn']);
                    }
                    if(!$rs){
                        $this->error(__('Failed to add attribute'));
                    }
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
     * 异步获取是否存在某个字段
     */
    public function getAjaxItemAttrProperty()
    {
        if($this->request->isAjax()){
            $nameEn = $this->request->post('nameEn');
            $result = $this->model->itemAttrProperty($nameEn);
            if($result){
                $this->error(__('The field already exists. Do not add it again'));
            }else{
                $this->success('ok');
            }
            dump($nameEn);
            exit;
        }else{
            $this->error(__('404 Not found'));
        }
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
                    $name_value_cn = $this->request->post("name_value_cn/a");
                    if($name_value_cn) {
                        $name_value_en = $this->request->post("name_value_en/a");
                        $code_rule = $this->request->post("code_rule/a");
                        $descb = $this->request->post("descb/a");
                        $data = [];
                        $strArr = '';
                        foreach ($name_value_cn as $k => $v) {
                            $data[$k]['property_id'] = $row['id'];
                            $data[$k]['name_value_cn'] = $v;
                            $data[$k]['name_value_en'] = $name_value_en[$k];
                            $data[$k]['code_rule'] = $code_rule[$k];
                            $data[$k]['descb'] = $descb[$k];
                            $data[$k]['create_person'] = session('admin.nickname');
                            $data[$k]['create_time'] = date("Y-m-d H:i:s", time());
                            $strArr .= "'$name_value_en[$k]'" . ',';
                        }
                        $str = rtrim($strArr, ',');
                        $rs = $this->model->appendField($params['input_mode'],$params['name_en'],$params['name_cn'],$str);
                        $this->propertyValue->allowField(true)->saveAll($data);
                        //批量添加

                    }
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $row['value'] = $this->propertyValue->getAttrPropertyValue($row['id']);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    /***
     * 商品属性项详情
     */
    public function detail(Request $request)
    {
        $id = $request->param('ids');
        if(!$id){
            $this->error('参数错误，请重新尝试','/admin/itemmanage/attribute/item_attribute_property/index');
        }
        $result = $this->model->getAttrPropertyDetail($id);
        if(!$result){
            $this->error('属性项不存在，请重新尝试','/admin/itemmanage/attribute/item_attribute_property/index');
        }
//        dump($result['is_required']);
//        exit;
        $this->view->assign('row',$result);
        return $this->view->fetch();
    }
}
