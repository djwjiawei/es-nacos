<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/26
 * Time: 17:59
 */

return [
    //nacos服务端host
    'host' => '',
    //间隔多长时间拉取一次配置(单位/秒)
    'interval' => 30,
    'config' => [
        //发布后的配置文件名
        'database' => [
            //命名空间
            'tenant' => '',
            //配置列表group
            'group' => '',
            //配置列表dataid
            'dataId' => '',
            //配置类型(json/yaml/ini)
            'type' => \EsSwoole\Nacos\NacosConfigManager::DECODE_INI
        ]
    ],
    //是否在开发环境也拉取nacos
    'devIsFetch' => false
];