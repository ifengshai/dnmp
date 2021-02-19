<?php

/*
 * This file is part of the mingyoung/dingtalk.
 *
 * (c) 张铭阳 <mingyoungcheung@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace EasyDingTalk\File;

use EasyDingTalk\Kernel\BaseClient;

class Client extends BaseClient
{

    /**
     * 单步文件上传
     *
     * @Description
     * @author wpl
     * @since 2021/01/13 18:08:15 
     * @param [type] $file
     * @param integer $file_size
     * @param [type] $agent_id
     * @return void
     */
    public function uploadSingle($file = [], $file_size = 0, $agent_id = null)
    {
        return $this->client->upload('file/upload/single', $file, [], ['file_size' => $file_size, 'agent_id' => $agent_id]);
    }

    /**
     * 保存文件到自定义或审批钉盘空间
     *
     * @Description
     * @author wpl
     * @since 2021/01/13 18:38:27 
     * @param [type] $media_id 上传接口返回
     * @param [type] $agent_id 
     * @param [type] $space_id
     * @param [type] $filename
     * @return void
     */
    public function saveFile($media_id, $agent_id, $space_id, $filename)
    {
        return $this->client->get('cspace/add', ['media_id' => $media_id, 'agent_id' => $agent_id, 'space_id' => $space_id, 'name' => $filename,'code' => 'bfbc762b9d363246b4bb3e4c4ee7da68']);
    }
}
