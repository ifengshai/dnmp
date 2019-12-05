<?php

namespace app\admin\controller\lens;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 镜片管理管理
 *
 * @icon fa fa-circle-o
 */
class Index extends Backend
{

    /**
     * Index模型对象
     * @var \app\admin\model\lens\Index
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\lens\Index;
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 添加
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
                    //查询是否已存在记录
                    $map['refractive_index'] = $params['refractive_index'];
                    $map['lens_type'] = $params['lens_type'];
                    $map['sph'] = $params['sph'];
                    $map['cyl'] = $params['cyl'];
                    $count =  $this->model->where($map)->count();
                    if ($count > 0) {
                        $this->error('已存在此记录！！');
                    }
                    $params['create_person'] = session('admin.nickname');
                    $params['createtime'] = date('Y-m-d H:i:s', time());
                    $result = $this->model->allowField(true)->save($params);
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

    public function lens()
    {
        $data['CYL'] = config('CYL');

        $refractive_index = input('refractive_index', '1.57');
        $lens_type = input('lens_type', 'Mid-Index');

        if ($refractive_index) {
            $map['refractive_index'] = $refractive_index;
            $this->assign('refractive_index', $refractive_index);
        }

        if ($lens_type) {
            $map['lens_type'] = $refractive_index . ' ' . $lens_type;
            $this->assign('lens_type', $lens_type);
        }

        //显示类型 1是库存  2是价格
        $type = input('type', 1);
        if ($type == 2) {
            $res = $this->model->field('id,price as data,cyl,sph,stock_num as value')->where($map)->select();
        } else {
            $res = $this->model->field('id,stock_num as data,cyl,sph,price as value')->where($map)->select();
        }

        $res = collection($res)->toArray();
        $list = [];
        foreach ($res as  $v) {
            $list[$v['sph']][$v['cyl']] = $v;
        }

        unset($res);
        $label = input('label', 1);
        if ($label == 1) {
            $data['SPH'] = config('SPH');
            $data['FSPH'] = config('FSPH');
        } elseif ($label == 2) {
            $data['SPH'] = config('SPH_1');
            $data['FSPH'] = config('FSPH_1');
        } else {
            $data['SPH'] = config('SPH_all');
            $data['FSPH'] = config('FSPH_all');
        }

        $this->assign('data', $data);
        $this->assign('label', $label);
        $this->assign('list', $list);
        $this->assign('type', $type);

        return $this->fetch();
    }



    //修改镜片数据
    public function lens_edit()
    {
        if ($this->request->isAjax()) {
            $id = input('id');
            $data['stock_num'] = input('stock_num');
            $data['price'] = input('price');
            if (!$id) {
                $data['sph'] = input('sph');
                $data['cyl'] = input('cyl');
                $data['refractive_index'] = input('refractive_index');
                $data['lens_type'] = input('refractive_index') . ' ' . input('lens_type');
                $data['createtime'] = date('Y-m-d H:i:s', time());
                $data['create_person'] = session('admin.nickname');
                $res = $this->model->save($data);
            } else {
                $res = $this->model->save($data, ['id' => $id]);
            }
            if ($res) {
                return json(['code' => 1, 'msg' => '修改成功！！']);
            } else {
                return json(['code' => 1, 'msg' => '修改失败！！']);
            }
        }
    }
}
