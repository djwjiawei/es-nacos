# easyswoole nacos包

## 安装包
```
1. 在composer.json中添加该配置
"repositories": [
    {
        "type": "git",
        "url": "git@git.dev.enbrands.com:X/php/interaction/easyswoole_nacos.git"
    }
]

2. 执行composerrequire
composer require es-swoole/nacos:(dev-master或具体tag)
```

## 已开发功能
- [x] 配置中心

## 使用步骤
1. 安装easyswoole
2. 在dev/produce.php中添加APP_ENV='dev/test/prod' 用于标识当前启动服务是开发/测试/生产 环境
3. 安装本包(composer require es-swoole/nacos:dev-master或具体tag)
4. 在根目录的bootstrap文件中添加:
\EasySwoole\Command\CommandManager::getInstance()->addCommand(new \EsSwoole\Base\Command\PublishConfig());  用于发布vendor包配置 (命令为php easyswoole publish:config --vendor=包名)
5. 执行php easyswoole publish:config --vendor=es-swoole/nacos 发布本包配置
6. 修改发布的配置（nacos 拉取配置）
7. 添加配置nacoshost(nacos 服务端地址)
8. 在EasySwooleEvent的initialize方法中添加ServiceProvider::getInstance()->registerVendor(); 用户初始化vendor包的服务提供者
9. 在EasySwooleEvent的mainServerCreate方法中添加ServiceProvider::getInstance()->bootrVendor(); 用户启动vendor包的服务提供者
10. 如果需要在开发环境也拉取nacos配置,需要添加配置nacosDevFetch为true


## 具体获取配置
```php
//配置拉取后会在项目根目录下的nacos目录里同步配置，获取配置方法
config('nacos.database.host');
```