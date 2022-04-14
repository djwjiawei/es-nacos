<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/26
 * Time: 18:09
 */

namespace EsSwoole\Nacos\Request;

use EsSwoole\Base\Request\AbstractRequest;

class NacosRequest extends AbstractRequest
{

    //获取配置url
    const GET_CONFIG_URL = '/nacos/v1/cs/configs';

    //监听配置url
    const LISTEN_CONFIG_URL = '/nacos/v1/cs/configs/listener';

    //不记录日志
    protected $isLog = false;

    //日志文件名
    protected $logFile = 'nacos';

    public function __construct($host = '')
    {
        $host = $host ?: config('nacosFetch.host');

        if (!$host) {
            throw new \Exception('nacos host为空');
        }

        $hostArr = explode(',', $host);

        if (count($hostArr) == 0) {
            $this->apiDomain = $hostArr[0];
        } else {
            $this->apiDomain = $hostArr[array_rand($hostArr)];
        }
    }

    /**
     * 获取nacos配置
     *
     * @param string $dataId
     * @param string $group
     * @param string $telnet
     *
     * @return mixed
     * User: dongjw
     * Date: 2021/11/26 18:15
     */
    public function getConfig($dataId, $group, $telnet = '')
    {
        $params = [
            'dataId' => $dataId,
            'group'  => $group,
        ];
        if ($telnet) {
            $params['tenant'] = $telnet;
        }

        return $this->get(self::GET_CONFIG_URL, $params)->getBody();
    }

    /**
     * 监听nacos配置
     *
     * @param        $dataId
     * @param        $group
     * @param string $telnet
     * @param string $md5
     * @param int    $timeout
     *
     * @return mixed
     * User: dongjw
     * Date: 2021/11/26 18:15
     */
    public function listen($dataId, $group, $telnet = '', $md5 = '', $timeout = 30000)
    {
        return $this->post(
            self::LISTEN_CONFIG_URL, sprintf(
            "%s%s%s%s%s%s%s%s", $dataId, chr(2), $group, chr(2), $md5, chr(2), $telnet, chr(1)
        ), ['Long-Pulling-Timeout' => $timeout]
        )->getBody();
    }

}