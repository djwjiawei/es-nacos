<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/29
 * Time: 10:30
 */

namespace EsSwoole\Nacos;


use EasySwoole\Component\Process\AbstractProcess;
use EasySwoole\EasySwoole\Trigger;
use Swoole\Timer;

class NacosProcess extends AbstractProcess
{

    /**
     * 定时从nacos中拉取配置并同步到对应进程中
     * @param $arg
     * User: dongjw
     * Date: 2021/11/29 17:06
     */
    public function run($arg)
    {
        //定时间隔,默认30s
        $interval = config('nocosFetch.interval') ?: 30;
        Timer::tick($interval * 1000, function () {
            NacosConfigManager::getInstance()->timerSyncConfig();
        });
    }

    public function onException(\Throwable $throwable, ...$args)
    {
        Trigger::getInstance()->throwable($throwable);
    }
}