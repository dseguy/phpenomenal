# PHPenomenal 8.6

![sunrise on the sea, like PHP 8.6 on Internet](banner.jpg)

*[Read this in English](README.md)*

PHPenomenal 8.6,在这里,开发者们将接受挑战,编写使用 PHP 8.6 新特性的 PHP 8.6 代码。

## 目标

截止到 2026 年 11 月 19 日 00:00(UTC),编写一个实际可用的 PHP 应用程序,使用即将发布的 PHP 8.6 新特性。

"实用"没有精确的定义,所以这个应用程序可以做任何事情,只要它能运行、接受参数,并在合理有限的时间内在某处输出一些内容。

## 规则

唯一的规则是:在活动结束前,分享至少一个应用程序及其源代码。

源代码必须能在 PHP 8.6 下编译通过。不要求能在更早的 PHP 8.5(原文如此)下编译,而且对某些特性来说这大概也不可能做到。

PHP 8.6 特性清单如下:
+ 部分函数应用(Partial Function Application)
+ Time\\Duration
+ clamp()
+ 常量上的 #[\\Override]
+ 对 const 持有对象的写操作
+ readonly 属性默认值
+ SortDirection 枚举

还有一份次要特性清单,可以使用,但并非强制要求,只是锦上添花。随着新特性被"发现"(也就是被这篇文章发现),这份清单可能会持续增加。

+ 轮询(polling)API:这往往会打破"短时间运行"的挑战规则,但欢迎展示它
+ trim() 修剪换页符 \f
+ 新的流错误(Stream Error)管理机制
+ array_map() 性能优化
+ 函数参数中的文档注释(DocComments)
+ 带有 __debugInfo() 的枚举
+ 废弃特性(Deprecations):既然它们即将被移除,就无需刻意引入,只需正确使用它们即可。

代码不需要附带许可证,只要我们能在这里展示它就行。

你可以按自己的想法设计应用架构、添加资源文件、使用 Composer,大量编写测试和静态分析也完全没问题,我们不会阻止你。

"实用"的应用程序完全由你自己定义。输出参数、进行大量计算最后再显示一个常量,或者用代数数循环实现 fizz-buzz,都可以。你也可以调用任何你喜欢的其他 PHP 特性。

_请让应用程序保持在单台机器内运行_。毕竟没必要为了对一个整数调用 clamp() 而从远程服务器下载数据集吧?

本活动已经开始,并将于 2026 年 11 月 19 日凌晨 12:01(GMT)结束——那正是 PHP 8.6 正式发布的时刻。

## 如何参与

在本仓库 [提交一个 PR](../../pr/new),表明你的参与意向。你可以在活动结束前持续更新它。请附带一个 README.md 说明你在做什么,也欢迎发布开发日志、示例输出等内容。

如果你的点子特别多,也可以再开一个 PR :)

也欢迎在 [其他参与者的 issue](../../issues) 下留言交流。

## 管理员

PHPenomenal PHP 8.6 的官方管理员是 Damien Seguy [@dseguy](https://phpc.social/@dseguy) 和 Jon Purvis [@JonPurvis_](https://x.com/JonPurvis_)。你可以在这里找到我们,也可以在 [PHP 社区 Discord 服务器](https://discord.phpc.chat/) 找到我们。

## 资源

### php 8.6

你需要在自己的机器上安装 PHP 8.6,以便进行语法检查。

```
php8.6 -l phenomenal8.6.php 
```

以下是获取 PHP 8.6 beta 版的一些途径:

+ [PHP.net](https://www.php.net/pre-release-builds.php) 提供预发布版本,包括 Windows 二进制文件。
+ 你也可以用上面的软件包从源码编译 PHP 8.6
+ 可以使用 [PHP 8.6 的 Docker 镜像](https://hub.docker.com/_/php/tags?name=8.6)
+ Mac 用户可以使用 [PHP on brew](https://github.com/shivammathur/homebrew-php)
+ ...

### 检查脚本

仓库中提供了一个基础脚本 [scripts/check-php86-features.php](scripts/check-php86-features.php),用于检查代码中使用了哪些 PHP 8.6 特性。

### 更多信息

以下是关于 PHP 8.6 的额外资源:

+ [What new in PHP 8.6: every feature you need to know](https://msaied.com/articles/whats-new-in-php-86-every-feature-you-need-to-know)
+ [PHP 8.6 at PHP.watch](https://php.watch/versions/8.6)
+ [New in PHP 8.6](https://stitcher.io/blog/new-in-php-86)
+ [PHP 8.6](https://laravel-news.com/php-8-6)
+ [What's New in PHP 8.6: Features, Changes, and Deprecations](https://www.zend.com/blog/php-8-6)
+ [UPGRADING to PHP 8.6](https://github.com/php/php-src/blob/PHP-8.6/UPGRADING)
+ [PHP 8.6 Enters Beta: What You Should Actually Start Looking At](https://nicolas-dabene.fr/en/blog/php-86-enters-beta-what-you-should-actually-start-looking-at/)

欢迎提交 PR 在此补充更多资源。

## ResPHPect!

更重要的是,祝你在为 PHP 8.6 做准备的过程中玩得开心!有趣的应用会获得额外加分,其中最有趣的作品将获得一只 elePHPant 玩偶。
