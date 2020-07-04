<?php

namespace Zendesk\API\Resources\Core;

use Psr\Http\Message\StreamInterface;
use Zendesk\API\Exceptions\CustomException;
use Zendesk\API\Exceptions\MissingParametersException;
use Zendesk\API\Http;
use Zendesk\API\Resources\ResourceAbstract;
use Zendesk\API\Traits\Resource\FindAll;

/**
 * The Attachments class exposes methods for uploading and retrieving attachments
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
            'findUser'       => 'users/{id}.json'
        ]);
    }

    /**
     * 获取所有的tag
     * @param  array  $params [description]
     * @return [type]         [description]
     */
    public function findTags(array $params = [])
    {
        return $this->findAll($params, __FUNCTION__);
    }

    /**
     * 获取用户信息
     * @param  array  $params [description]
     * @return [type]         [description]
     */
    public function findUser(array $params)
    {
        $params = $this->addChainedParametersToParams($params, ['id' => get_class($this)]);
        if (! $this->hasKeys($params, ['id'])) {
            throw new MissingParametersException(__METHOD__, ['id']);
        }
        $id = $params['id'];
        unset($params['id']);

        return $this->client->get($this->getRoute(__FUNCTION__, ['id' => $id]), $params);
    }

    /**
     * 创建新用户
     * @param array $params
     * @return \stdClass|null
     * @throws \Zendesk\API\Exceptions\ApiResponseException
     * @throws \Zendesk\API\Exceptions\AuthException
     */
    public function createUser(array $params)
    {
        return $this->client->post(
            $this->getRoute(__FUNCTION__),
            $params
        );
    }
}
