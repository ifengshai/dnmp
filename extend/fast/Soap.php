<?php

namespace fast;

use GuzzleHttp\Client;

class Soap
{
    /**
     * 创建产品
     *
     * @Description
     * @author wpl
     * @since 2020/07/22 11:34:19 
     * @param string $magentoUrl
     * @param [type] $magento_account
     * @param [type] $magento_key
     * @param [type] $params
     * @return void
     */
    public static function createProduct_bak($config = [], $params)
    {
        if (!$config) {
            return false;
        }
        //M需要SSL证书验证
        if ($config['id'] == 4) {
            //stream_context_create作用：创建并返回一个文本数据流并应用各种选项，可用于fopen()、file_get_contents、soap等过程的超时设置、代理服务器、请求方式、头信息设置的特殊过程。
            $options = array(
                'cache_wsdl' => 0,
                'trace' => 1,
                'stream_context' => stream_context_create(array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                ))
            );
        } else {
            $options = array("trace" => 1, 'cache_wsdl' => 0);
        }

        try {
            $client = new \SoapClient($config['magento_url'] . '/api/soap/?wsdl', $options);
            $session = $client->login($config['magento_account'], $config['magento_key']);
            //获取magento产品属性设置
            $attributeSets = $client->call($session, 'product_attribute_set.list');
            foreach ($attributeSets as $v) {
                //选择默认值
                if ($v['name'] == $config['item_attr_name']) {
                    $attributeSet['set_id'] = $v['set_id'];
                }
            }
            // product creation
            $client->call($session, 'catalog_product.create', array($config['item_type'], $attributeSet['set_id'], $params['sku'], $params));
        } catch (\SoapFault $e) {
            return false;
        }
        return true;
    }

    /**
     * 创建商品
     *
     * @Description
     * @author wpl
     * @since 2020/08/06 14:33:16 
     * @return void
     */
    public static function createProduct($params)
    {
        if (!$params) {
            return false;
        }
        switch ($params['site']) {
            case 1:
                $url = config('url.zeelool_url');
                break;
            case 2:
                $url = config('url.voogueme_url');
                break;
            case 3:
                $url = config('url.nihao_url');
                break;
            case 4:
                $url = config('url.api_meeloog_url');
                break;
            case 5:
                $url = config('url.wesee_url');
                break;
            default:
                return false;
                break;
        }

        $client = new Client(['verify' => false]);
        try {
            // $response = $client->request('GET', $url, array('query' => $params));
            unset($params['site']);
            $response = $client->request('POST', $url, array('form_params' => $params));
            $body = $response->getBody();
            $stringBody = (string) $body;
            $res = json_decode($stringBody, true);
            if ($res === null) {
                return false;
            }
            if ($res['code'] == 200) {
                return true;
            }
            exception($res['msg']);
        } catch (\Exception $e) {
            exception($e->getMessage());
        }
    }

   
}
