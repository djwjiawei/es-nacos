<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/29
 * Time: 10:44
 */

namespace EsSwoole\Nacos\Provider;


use EasySwoole\Command\Color;
use EasySwoole\Component\Process\Manager;
use EsSwoole\Base\Abstracts\AbstractProvider;
use EsSwoole\Base\Common\ConfigLoad;
use EsSwoole\Base\Util\AppUtil;
use EsSwoole\Nacos\NacosConfigManager;
use EsSwoole\Nacos\NacosProcess;
use Swoole\Coroutine\Scheduler;

class NacosProvider extends AbstractProvider
{

    public function register()
    {
        //服务启动时拉取一次配置
        if (config('APP_ENV') == AppUtil::DEV_ENV) {
            //开发环境拉取本地配置
            ConfigLoad::loadDir(
                NacosConfigManager::getInstance()->getLocalDir(),
                EASYSWOOLE_ROOT
            );
        }else{
            //不是开发环境 从naocs中拉取一次
            $schedule = new Scheduler();
            $schedule->add(function () {
                echo Color::info("nacos拉取日志开始") . PHP_EOL;
                NacosConfigManager::getInstance()->fetchConfig(true);
                echo Color::info("nacos拉取日志结束") . PHP_EOL;
            });
            $schedule->start();
        }
    }

    public function boot()
    {
        //创建定时同步进程
        $processConfig = new \EasySwoole\Component\Process\Config([
            'processName' => 'Nacos.Config', //设置进程名称
            'processGroup' => 'Nacos.Config', //设置进程组名称
            'enableCoroutine' => true, //设置开启协程
        ]);
        $process = new NacosProcess($processConfig);
        Manager::getInstance()->addProcess($process);
    }
}