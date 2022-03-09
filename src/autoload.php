<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/12/08
 * Time: 18:11
 */

if (!function_exists('nacosPath')) {
    /**
     * 获取nacos配置文件路径
     *
     * @param $key
     *
     * @return array|mixed|null
     * User: dongjw
     * Date: 2021/12/08 18:11
     */
    function nacosPath($path = '')
    {
        return EASYSWOOLE_ROOT . '/nacos' . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
}

if (!function_exists('nacos')) {
    /**
     * 获取nacos配置
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return array|mixed|null
     * User: dongjw
     * Date: 2022/2/14 10:39
     */
    function nacos($key, $default = null)
    {
        return \config("nacos.{$key}", $default);
    }
}
