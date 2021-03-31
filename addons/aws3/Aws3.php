<?php

namespace addons\aws3;

use Aws\S3\S3Client;
use think\Addons;
use think\Request;

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
     * @param $params
     */
    public function configInit(&$params)
    {
        $config = $this->getConfig();
        $params['upload'] = [
            'uploadurl' => $config['uploadurl'],
            'savekey' => $config['savekey'],
            'maxsize' => $config['maxsize'],
            'mimetype' => $config['mimetype']
        ];
    }
    public function upload($file)
    {
        $fileName = '1111';
        $this->s3Upload($fileName,$file->getInfo('tmp_name'));
    }
    public function s3Upload($fileName,$sourceFile)
    {
        $this->config = get_addon_config('aws3');
        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region'  => $this->config['default_region'],
            'credentials' => [
                'key' => $this->config['access_key_id'],
                'secret'  => $this->config['secret_access_key']
            ]
        ]);
        try {
            $result = $this->s3Client->putObject([
                'Bucket' => $this->config['bucket'],
                'Key'    => '',
                'Body'   => '',
                'ACL'    => 'public-read',
            ]);
        } catch (Aws\S3\Exception\S3Exception $e) {
            echo "There was an error uploading the file.\n";
        }
    }
}
