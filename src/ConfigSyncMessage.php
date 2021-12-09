<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/29
 * Time: 16:29
 */

namespace EsSwoole\Nacos;


use EasySwoole\EasySwoole\Logger;
use EasySwoole\EasySwoole\Trigger;
use EasySwoole\Utility\File;
use EsSwoole\Base\Abstracts\ProcessMessageInterface;

class ConfigSyncMessage implements ProcessMessageInterface
{

    protected $data = [];

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function run()
    {
        $processName = cli_get_process_title();
        if (!$this->data) {
            $fileScan = File::scanDirectory(nacosPath());
            if (!$fileScan) {
                return false;
            }
        }
        foreach ($this->data as $file) {
            NacosConfigManager::getInstance()->loadFile($file);
            Logger::getInstance()->info("进程: {$processName} 同步配置信息{$file}");
        }
        return true;
    }

    public function onException(\Throwable $throwable)
    {
        Trigger::getInstance()->throwable($throwable);
    }
}