<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/29
 * Time: 10:47
 */

namespace EsSwoole\Nacos;


use EasySwoole\Component\Singleton;
use EasySwoole\EasySwoole\Config;
use EasySwoole\EasySwoole\Logger;
use EasySwoole\Utility\File;
use EsSwoole\Base\Common\ProcessSync;
use EsSwoole\Base\Util\AppUtil;
use EsSwoole\Base\Util\CoroutineUtil;
use EsSwoole\Nacos\Request\NacosRequest;
use Swoole\Coroutine\Scheduler;

class NacosConfigManager
{
    use Singleton;

    /**
     * @var array 拉取配置的MD5结果
     */
    protected $configMd5 = [];

    /**
     * @var array 要从nacos拉取的配置信息
     */
    protected $fetchInfo = [];

    /**
     * @var int 拉取配置开始时间
     */
    protected $startTime;

    //json解析格式
    const DECODE_JSON = 'json';

    //yaml解析格式
    const DECODE_YAML = 'yaml';
    const DECODE_YAM = 'yam';

    //ini解析格式
    const DECODE_INI = 'ini';

    public function __construct()
    {
        $this->startTime = time();
        $this->fetchInfo = config('nacosFetch.config');
    }

    /**
     * 根据配置的nacos信息从nacos中拉取配置
     * @param bool $needMerge 是否需要合并到config中
     * @return array|bool
     * User: dongjw
     * Date: 2021/11/29 14:18
     */
    public function fetchConfig($needMerge = false)
    {
        if (!$this->fetchInfo) {
            return false;
        }

        $configRes = [];
        foreach ($this->fetchInfo as $file => $configItem) {
            $res = (new NacosRequest())->getConfig(
                $configItem['dataId'],
                $configItem['group'],
                $configItem['tenant']
            );
            if (!$res) {
                continue;
            }
            //拉取成功后更新MD5信息
            $md5Config = md5($res);
            if (empty($this->configMd5[$file]) || $this->configMd5[$file] != $md5Config) {
                $this->configMd5[$file] = $md5Config;
                $configRes[$file] = $res;

                //保存到项目根目录的nacos目录下
                $saveDir = nacosPath();
                File::createDirectory($saveDir);
                file_put_contents($saveDir . "/{$file}." . $this->fetchInfo[$file]['type'],$res);

                if ($needMerge) {
                    Config::getInstance()->setConf(
                        "nacos.{$file}",
                        $this->decodeConfig($res, $this->fetchInfo[$file]['type'])
                    );
                }
            }
        }
        return $configRes;
    }

    public function timerSyncConfig()
    {
        //拉取一次配置
        $needSyncConfig = $this->fetchConfig();
        if (!$needSyncConfig) {
            return;
        }

        //配置文件改变,同步全部进程(除掉上边已经同步过的进程)
        ProcessSync::syncAllProcess(
            serialize(new ConfigSyncMessage(array_keys($needSyncConfig)))
        );
    }

    /**
     * 加载nacos目录的文件
     * @return bool
     * User: dongjw
     * Date: 2021/12/9 13:54
     */
    public function loadDir()
    {
        $fileArr = File::scanDirectory(nacosPath());
        if (!$fileArr || empty($fileArr['files'])) {
            return false;
        }
        foreach ($fileArr['files'] as $file) {
            $pathinfo = pathinfo($file);
            $this->mergeConf($pathinfo['filename'],$pathinfo['extension']);
        }
        return true;
    }

    /**
     * 加载nacos配置
     * @param $file
     * @return bool
     * User: dongjw
     * Date: 2021/11/29 17:04
     */
    public function loadFile($file)
    {
        if (!isset($this->fetchInfo[$file])) {
            return false;
        }
        return $this->mergeConf($file, $this->fetchInfo[$file]);
    }

    /**
     * set nacos配置文件数据
     * @param $file
     * @param $type
     * @return bool
     * User: dongjw
     * Date: 2021/12/9 14:14
     */
    public function mergeConf($file,$type)
    {
        $filePath = nacosPath("{$file}.{$type}");
        if (!file_exists($filePath)) {
            return false;
        }
        $content = file_get_contents($filePath);
        if (!$content) {
            return false;
        }
        Config::getInstance()->setConf(
            "nacos.{$file}",
            $this->decodeConfig($content,$type)
        );
        return true;
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
        if (config('APP_ENV') == AppUtil::DEV_ENV && !config('nacosFetch.devIsFetch')) {
            return true;
        }else{
            return false;
        }
    }

    /**
     * 加载一次配置
     * User: dongjw
     * Date: 2021/12/6 11:33
     */
    public function loadConfig()
    {
        //是开发环境且开发环境不拉取则从本地获取
        if ($this->islLoadLocal()) {
            $this->loadDir();
        }else{
            //从naocs中拉取一次
            $pid = posix_getpid();
            Logger::getInstance()->info("pid:{$pid} nacos拉取日志开始");
            if (CoroutineUtil::isInCoroutine()) {
                $this->fetchConfig(true);
            }else{
                //不在协程中的话，开启协程容器去执行
                $schedule = new Scheduler();
                $schedule->add(function () {
                    $this->fetchConfig(true);
                });
                $schedule->start();
            }
            Logger::getInstance()->info("pid:{$pid} nacos拉取日志结束");
        }
    }

    /**
     * 当进程启动时拉取配置(主要用来进程重启重新拉取配置)
     * @return bool
     * User: dongjw
     * Date: 2021/12/6 15:57
     */
    public function onProcessStartLoad()
    {
        //如果进程开始时间减服务启动前拉取配置事件小于10s,则证明是正常启动,否则认为是后面重启进程
        if ((time() - $this->startTime) < 10) {
            return false;
        }
        $this->loadConfig();
        return true;
    }

    /**
     * 解析nacos文件内容
     * @param $string
     * @param string $type
     * @return array|false|mixed
     * User: dongjw
     * Date: 2021/11/29 17:04
     */
    public function decodeConfig($string, $type = '')
    {
        $type = strtolower((string) $type);
        switch ($type) {
            case self::DECODE_JSON:
                return json_decode($string, true);
            case self::DECODE_YAM:
            case self::DECODE_YAML:
                return yaml_parse($string);
            case self::DECODE_INI:
                return parse_ini_string($string,true);
            default:
                return $string;
        }
    }

    /**
     * 获取拉取配置信息
     * @return array
     * User: dongjw
     * Date: 2021/12/6 11:01
     */
    public function getFetchInfo()
    {
        return $this->fetchInfo;
    }

    /**
     * 获取开始拉取配置的时间
     * @return int
     * User: dongjw
     * Date: 2021/12/6 11:01
     */
    public function getStartTime()
    {
        return $this->startTime;
    }
}