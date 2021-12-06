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
use EasySwoole\Utility\File;
use EsSwoole\Base\Common\ProcessSync;
use EsSwoole\Nacos\Request\NacosRequest;

class NacosConfigManager extends AbstractConfigCenter
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
                $saveDir = $this->getLocalDir();
                File::createDirectory($saveDir);
                file_put_contents($saveDir . "/{$file}.conf",$res);

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
        $content = file_get_contents(EASYSWOOLE_ROOT . "/nacos/{$file}.conf");
        if (!$content) {
            return false;
        }
        Config::getInstance()->setConf(
            "nacos.{$file}",
            $this->decodeConfig($content,$this->fetchInfo[$file]['type'])
        );
        return true;
    }

    /**
     * 获取nacos存储路径
     * @return string
     * User: dongjw
     * Date: 2021/11/29 17:04
     */
    public function getLocalDir()
    {
        return EASYSWOOLE_ROOT . '/nacos';
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
            case 'json':
                return json_decode($string, true);
            case 'yml':
            case 'yaml':
                return yaml_parse($string);
            case 'ini':
                return parse_ini_string($string);
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