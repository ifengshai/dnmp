<?php

/**
 * @Author: CraspHB彬
 * @Date:   2020-03-31 14:40:56
 * @Email:   646054215@qq.com
 * @Last Modified time: 2020-03-31 14:41:04
 */
namespace Zendesk\API\Resources\Core;

use Psr\Http\Message\StreamInterface;
use Zendesk\API\Exceptions\CustomException;
use Zendesk\API\Exceptions\MissingParametersException;
use Zendesk\API\Http;
use Zendesk\API\Resources\ResourceAbstract;
use Zendesk\API\Traits\Resource\FindAll;

/**
 * sdk中没有的方法，自定义的方法
 * @package Zendesk\API
 */
class Crasp extends ResourceAbstract
{
    use FindAll;

    /**
     * {@inheritdoc}
     */
    protected function setUpRoutes()
    {
        $this->setRoutes([
            'findTags'       => "tags.json",
        ]);
    }

    /**
     * 获取所有的tags
     * @param array $params
     * @return \stdClass|null
     * @throws \Zendesk\API\Exceptions\ApiResponseException
     * @throws \Zendesk\API\Exceptions\AuthException
     */
    public function findTags(array $params = [])
    {
        return $this->findAll($params, __FUNCTION__);
    }
}
