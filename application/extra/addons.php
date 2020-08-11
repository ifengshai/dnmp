<?php

return array (
  'autoload' => false,
  'hooks' => 
  array (
    'express_query' => 
    array (
      0 => 'express',
    ),
    'admin_login_init' => 
    array (
      0 => 'loginbg',
    ),
    'response_send' => 
    array (
      0 => 'loginvideo',
    ),
    'testhook' => 
    array (
      0 => 'markdown',
    ),
    'config_init' => 
    array (
      0 => 'nkeditor',
    ),
  ),
  'route' => 
  array (
    '/example$' => 'example/index/index',
    '/example/d/[:name]' => 'example/demo/index',
    '/example/d1/[:name]' => 'example/demo/demo1',
    '/example/d2/[:name]' => 'example/demo/demo2',
  ),
);