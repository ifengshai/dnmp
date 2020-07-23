<?php

namespace fast;

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
    public static function createProduct($config = [], $params)
    {
        if (!$config) {
            return false;
        }
        //M需要SSL证书验证
        if ($config['id'] == 4) {
            $opts = array(
                'cache_wsdl' => 0,
                'ssl'   => array(
                    'verify_peer'          => false
                ),
                'https' => array(
                    'curl_verify_ssl_peer'  => false,
                    'curl_verify_ssl_host'  => false
                )
            );
            //stream_context_create作用：创建并返回一个文本数据流并应用各种选项，可用于fopen()、file_get_contents、soap等过程的超时设置、代理服务器、请求方式、头信息设置的特殊过程。
            $streamContext = stream_context_create($opts);
            $options =  array('trace' => 1, 'stream_context' => $streamContext);
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
}
