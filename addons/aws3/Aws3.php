<?php

namespace addons\aws3;

use Aws\S3\S3Client;
use think\Addons;

/**
 * 插件
 */
class Aws3 extends Addons
{

    protected $s3Client = null;
    protected $config = [];

    /**
     * 插件安装方法
     * @return bool
     */
    public function install()
    {

        return true;
    }

    /**
     * 插件卸载方法
     * @return bool
     */
    public function uninstall()
    {

        return true;
    }

    /**
     * 插件启用方法
     * @return bool
     */
    public function enable()
    {

        return true;
    }

    /**
     * 插件禁用方法
     * @return bool
     */
    public function disable()
    {

        return true;
    }

    /**
     * 配置初始化
     *
     * @param $params 上传图片参数
     *
     * @author crasphb
     * @date   2021/4/1 9:53
     */
    public function configInit(&$params)
    {
        $config = $this->getConfig();
        $params['upload'] = [
            'uploadurl' => $config['uploadurl'],
            'savekey'   => $config['savekey'],
            'maxsize'   => $config['maxsize'],
            'mimetype'  => $config['mimetype'],
            'cdnurl'    => $config['cdnurl'],
        ];
    }

    /**
     * 上传方法
     *
     * @param $attachment 文件信息
     *
     * @author crasphb
     * @date   2021/4/1 9:53
     */
    public function uploadAfter($attachment)
    {
        $sourceFile = '.'.$attachment['url'];
        $fileName = substr($attachment['url'], 1);

        return $this->s3Upload($fileName, $sourceFile);
    }

    /**
     * s3上传
     *
     * @param $fileName   文件名
     * @param $sourceFile 文件路径
     *
     * @author crasphb
     * @date   2021/4/1 9:53
     */
    public function s3Upload($fileName, $sourceFile)
    {
        $this->config = get_addon_config('aws3');
        //获取s3client
        $this->s3Client = new S3Client([
            'version'     => 'latest',
            'region'      => $this->config['default_region'],
            'credentials' => [
                'key'    => $this->config['access_key_id'],
                'secret' => $this->config['secret_access_key'],
            ],
        ]);
        $fileNameEnd = array_pop(explode('.', $fileName));
        //非图片类型不上传到s3
        try {
            $this->s3Client->putObject([
                'Bucket'     => $this->config['bucket'],
                'Key'        => $fileName,
                'SourceFile' => $sourceFile,
                'ACL'        => 'public-read',
            ]);
        } catch (\Aws\S3\Exception\S3Exception $e) {
            return ['code' => 0, 'msg' => $e->getMessage()];
        }
        if (in_array($fileNameEnd, ['gif', 'jpeg', 'png', 'jpg', 'bmp'])) {
            //删除原文件
            @unlink($sourceFile);
        }

        //上传文件
        return ['code' => 1, 'msg' => '上传成功', 'url' => $this->config['s3_url'].$fileName];
    }
}
