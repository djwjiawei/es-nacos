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

    public function __construct($host = '')
    {
        $this->apiDomain = $host ?: config('nacosFetch.host');
    }

    /**
     * 获取nacos配置
     * @param $dataId
     * @param $group
     * @param string $telnet
     * @return mixed
     * User: dongjw
     * Date: 2021/11/26 18:15
     */
    public function getConfig($dataId, $group, $telnet = '')
    {
        $params = [
            'dataId' => $dataId,
            'group' => $group
        ];
        if ($telnet) {
            $params['tenant'] = $telnet;
        }
        return $this->get(self::GET_CONFIG_URL,$params)->getBody();
    }

    /**
     * 监听nacos配置
     * @param $dataId
     * @param $group
     * @param string $telnet
     * @param string $md5
     * @param int $timeout
     * @return mixed
     * User: dongjw
     * Date: 2021/11/26 18:15
     */
    public function listen($dataId, $group, $telnet = '', $md5 = '', $timeout = 30000)
    {
        return $this->post(self::LISTEN_CONFIG_URL,sprintf(
            "%s%s%s%s%s%s%s%s",
            $dataId,
            chr(2),
            $group,
            chr(2),
            $md5,
            chr(2),
            $telnet,
            chr(1)
        ),['Long-Pulling-Timeout' => $timeout])->getBody();
    }

}