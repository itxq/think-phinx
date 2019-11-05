Think-Phinx 数据库迁移工具
===============

[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.1-8892BF.svg)](http://www.php.net/)
[![Latest Stable Version](https://poser.pugx.org/itxq/think-phinx/version)](https://packagist.org/packages/itxq/think-phinx)
[![Total Downloads](https://poser.pugx.org/itxq/think-phinx/downloads)](https://packagist.org/packages/itxq/think-phinx)
[![Latest Unstable Version](https://poser.pugx.org/itxq/think-phinx/v/unstable)](//packagist.org/packages/itxq/think-phinx)
[![License](https://poser.pugx.org/itxq/think-phinx/license)](https://packagist.org/packages/itxq/think-phinx)
[![composer.lock available](https://poser.pugx.org/itxq/think-phinx/composerlock)](https://packagist.org/packages/itxq/think-phinx)

> 本扩展在 `top-think/think-migration` 扩展的基础进行了优化修改。`phinx` 采用composer引用；并且增加了一个新的功能用于迁移其他composer包自带的迁移文件。

> 迁移其他composer包中的迁移文件时，需要其他包在composer.json 指定路径, 格式如下

```json
{
  "extra": {
    "itxq-phinx": "database"
  }
}
```
  
### 开源地址：

[【GitHub:】https://github.com/itxq/think-phinx](https://github.com/itxq/think-phinx)

[【码云:】https://gitee.com/itxq/think-phinx](https://gitee.com/itxq/think-phinx)

### 扩展安装：

 `composer require itxq/think-phinx`
 
### 命令说明

* **migrate:create**  
    Create a new migration
* **migrate:rollback**  
    Rollback the last or to a specific migration
* **migrate:run**  
    Migrate the database
* **seed:create**  
    Create a new database seeder
* **seed:run**  
    Run database seeders


