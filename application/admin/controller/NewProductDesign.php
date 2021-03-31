<?php

namespace app\admin\controller;

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
        $this->view->assign('getTabList', $this->model->getTabList());
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
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }


    public function detail()
    {

        return $this->view->fetch();
    }

    //录尺寸
    public function record_size()
    {
        return $this->view->fetch();
    }

    //更改状态
    public function change_status()
    {

    }

    //分配人员
    public function allocate_personnel()
    {
        return $this->view->fetch();
    }

    //上传图片
    public function add_img()
    {
        $item = new \app\admin\model\itemmanage\Item;
        $row = $item->get(14584, 'itemAttribute');
        if ($this->request->isAjax()) {
            $params = $this->request->post("row/a");
            $item_status = $params['item_status'];
            $itemAttrData['frame_images'] = $params['frame_images'];
            $itemAttrData['create_frame_images_time'] = date("Y-m-d H:i:s", time());
            Db::connect('database.db_stock')->name('item_attribute')->startTrans();
            Db::connect('database.db_stock')->name('item')->startTrans();
            try {
                $itemAttrResult = Db::connect('database.db_stock')->name('item_attribute')->where('item_id', '=', 14584)->update($itemAttrData);
                if ($item_status == 2) {
                    $itemResult = Db::connect('database.db_stock')->name('item')->where('id', '=', 14584)->update(['item_status' => $item_status]);
                    $imgArr = explode(',', $params['frame_images']);
                    foreach ($imgArr as $k => $v) {
                        $arr = explode("/", $v);
                        //获取最后一个/后边的字符
                        $sku = $arr[count($arr) - 1];
                        $file_url = '.' . $v;
                        //私有
                        $acl = 'private';
                        //上传至桶的名称
                        $bucket = 'xmslaravel';

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
                        unlink($file_url);
                    }
                } else {
                    $itemResult = true;
                }
                Db::connect('database.db_stock')->name('item_attribute')->commit();
                Db::connect('database.db_stock')->name('item')->commit();
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
                Db::connect('database.db_stock')->name('item_attribute')->rollback();
                Db::connect('database.db_stock')->name('item')->rollback();
                $this->error($e->getMessage(), [], 406);
            } catch (PDOException $e) {
                Db::connect('database.db_stock')->name('item_attribute')->rollback();
                Db::connect('database.db_stock')->name('item')->rollback();
                $this->error($e->getMessage(), [], 407);
            } catch (Exception $e) {
                Db::connect('database.db_stock')->name('item_attribute')->rollback();
                Db::connect('database.db_stock')->name('item')->rollback();
                $this->error($e->getMessage(), [], 408);
            }
            if (($itemAttrResult !== false) && ($itemResult !== false) && ($data['type'] == 1)) {
                $this->success();
            } else {
                $this->error(__('Failed to upload product picture, please try again'));
            }
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }


}
