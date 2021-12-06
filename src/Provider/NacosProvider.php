<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/29
 * Time: 10:44
 */

namespace EsSwoole\Nacos\Provider;


use EasySwoole\Component\Process\Manager;
use EasySwoole\EasySwoole\ServerManager;
use EsSwoole\Base\Abstracts\AbstractProvider;
use EsSwoole\Base\Common\Event;
use EsSwoole\Nacos\NacosConfigManager;
use EsSwoole\Nacos\NacosProcess;

class NacosProvider extends AbstractProvider
{

    public function register()
    {
        //注册自定义进程事件,用来自定义进程重启后重新拉取新的配置
        Event::getInstance()->add(Event::USER_PROCESS_START_EVENT,function (){
            NacosConfigManager::getInstance()->onProcessStartLoad();
        });

        //注册worker进程启动事件,用来worker进程重启后重新拉取新的配置
        $register = ServerManager::getInstance()->getEventRegister();
        $register->add($register::onWorkerStart, function (\Swoole\Server $server,int $workerId) {
            NacosConfigManager::getInstance()->onProcessStartLoad();
        });

        //服务启动时拉取一次配置
        NacosConfigManager::getInstance()->loadConfig();
    }

    public function boot()
    {
        //配置了拉取信息且不是加载本地配置的才开启进程
        if (NacosConfigManager::getInstance()->getFetchInfo() && !NacosConfigManager::getInstance()->islLoadLocal()) {
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
}