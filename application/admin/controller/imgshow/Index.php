<?php
namespace app\admin\controller\imgshow;
use app\common\controller\Backend;
use think\Request;

class Index extends Backend{
    protected $noNeedLogin= [
        'index',
        'doUpload',
    ];

    public function index()
    {
        //读取本地存储json文件
        $path_txt = ROOT_PATH."/public/uploads/imgshow/imgshow.json";
        $data = json_decode(file_get_contents($path_txt));
        $this->assign('data',$data);
        return $this->view->fetch();
    }


    public function doUpload()
    {
        $files = request()->file('image');
        $info = "";
        foreach ($files as $picFile) {
            // 移动到框架应用根目录/public/uploads/ 目录下
            $info = $picFile->move(ROOT_PATH . 'public/uploads' . DS . 'imgshow');
            //获取存储路径，以便插入json文件
            $path= "/uploads/imgshow/".$info->getSaveName();
        }
        if ($info !== "") {
            $path_txt = ROOT_PATH."/public/uploads/imgshow/imgshow.json";
            $paths = json_encode($path);
            fopen($path_txt,'wb');
            file_put_contents($path_txt,$paths);
            return $this->success('上传成功！');
        } else {
            return $this->error('上传失败！');
        }

    }

    }
