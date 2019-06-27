<?php

/*
 * This file is part of the overtrue/wechat.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * AbstractMiniProgram.php.
 *
 * Part of Overtrue\WeChat.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author    mingyoung <mingyoungcheung@gmail.com>
 * @copyright 2016
 *
 * @see      https://github.com/overtrue
 * @see      http://overtrue.me
 */

namespace EasyWeChat\MiniProgram\Core;

use EasyWeChat\Core\AbstractAPI;

class AbstractMiniProgram extends AbstractAPI
{
    /**
     * Mini program config.
     *
     * @var array
     */
    protected $config;

    /**
     * AbstractMiniProgram constructor.
     *
     * @param \EasyWeChat\MiniProgram\AccessToken $accessToken
     * @param array                               $config
     */
    public function __construct($accessToken, $config)
    {
        parent::__construct($accessToken);

        $this->config = $config;
    }
}
