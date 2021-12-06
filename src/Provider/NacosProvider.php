<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/29
 * Time: 10:44
 */

namespace EsSwoole\Nacos\Provider;


use EasySwoole\Component\Process\Manager;
use EsSwoole\Base\Abstracts\AbstractProvider;
use EsSwoole\Base\Common\Event;
use EsSwoole\Nacos\NacosConfigManager;
use EsSwoole\Nacos\NacosProcess;

class NacosProvider extends AbstractProvider
{

    public function register()
    {
        //注册一个拉取配置事件
        Event::getInstance()->add(Event::USER_PROCESS_START_EVENT,function (){
            //如果进程开始时间减服务启动前拉取配置事件小于10s,则证明是正常启动,否则认为是后面重启进程
            if ((time() - NacosConfigManager::getInstance()->getStartTime()) < 10) {
                return false;
            }
            NacosConfigManager::getInstance()->loadConfig();
            return true;
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