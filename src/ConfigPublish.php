<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/24
 * Time: 13:51
 */

namespace EsSwoole\Base;


use EsSwoole\Base\Abstracts\ConfigPublishInterface;

class ConfigPublish implements ConfigPublishInterface
{
    public function publish()
    {
        return [
            __DIR__ . '/config/nacosFetch.php' => configPath('nacosFetch.php'),
        ];
    }
}