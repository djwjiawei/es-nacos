<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/12/6
 * Time: 11:33
 */

namespace EsSwoole\Nacos;


use EasySwoole\EasySwoole\Logger;
use EsSwoole\Base\Common\ConfigLoad;
use EsSwoole\Base\Util\AppUtil;
use Swoole\Coroutine\Scheduler;

abstract class AbstractConfigCenter
{
    /**
     * 加载一次配置
     * User: dongjw
     * Date: 2021/12/6 11:33
     */
    public function loadConfig()
    {
        //是开发环境且开发环境不拉取则从本地获取
        if ($this->islLoadLocal()) {
            ConfigLoad::loadDir(
                NacosConfigManager::getInstance()->getLocalDir(),
                EASYSWOOLE_ROOT
            );
        }else{
            //从naocs中拉取一次
            $schedule = new Scheduler();
            $schedule->add(function () {
                Logger::getInstance()->info("nacos拉取日志开始");
                $this->fetchConfig(true);
                Logger::getInstance()->info("nacos拉取日志结束");
            });
            $schedule->start();
        }
    }

    /**
     * 是否从本地加载
     * @return bool
     * User: dongjw
     * Date: 2021/12/6 11:45
     */
    public function islLoadLocal()
    {
        //是开发环境且开发环境不拉取则从本地获取
        if (config('APP_ENV') == AppUtil::DEV_ENV && !config('nacosDevFetch')) {
            return true;
        }else{
            return false;
        }
    }


    /**
     * 拉取配置
     * @param bool $needMerge 是否需要合并到config中
     * @return mixed
     * User: dongjw
     * Date: 2021/12/6 11:36
     */
    abstract public function fetchConfig($needMerge = true);
}
