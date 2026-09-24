# PHPenomenal 8.6

![sunrise on the sea, like PHP 8.6 on Internet](banner.jpg)

PHPenomenal 8.6, where developers are challenged to write PHP 8.6 code that uses PHP 8.6 features.

## The Goal

Until November 19th, 2026, 00h00 UTC, write a useful PHP application that makes use of the new PHP 8.6 features that are coming up. 

There is no precise definition of useful, so the application may do anything, as long as it runs, takes parameter and output something, somewhere, in reasonably finite time. 

## The Rules

The only rule is that you share at least one application, along with it source code, by the end of the event.

The source code must compile with PHP 8.6. Compilation with older PHP 8.5 (sic) is not necessary, and probably not possible. 

The list of PHP 8.6 features is the following: 
+ Partial Function Application
+ Time\\Duration
+ clamp()
+ #[\\Override] on a constant
+ writes on const-held objects
+ readonly property defaults
+ SortDirection enum

There is a secondary list of features that may be used too, but these are not compulsory, just nice to have. This list might grow as new features are 'discovered', that is, by this article.

+ polling API: this tends to break the short running time challenge, but feel free to display it
+ trim() trim Form-Feed \f
+ New Stream Error management
+ array_map() optimisation
+ DocComments in function parameters
+ Enums with __debugInfo()
+ Deprecations: no need to introduce them if they are going away. So, just use them correctly. 

The code does not have to be licenced, as long as we can display it here. 

You may architecture the application as you want, have assets, and use composer too. Splurges in tests and static analysis, we won't stop you. 

The useful application is defined however you want. Echo the arguments, run lots of calculations and ditch it in the end to display a constant, or loop fizz-buzz with algebraic numbers. And you can call any other PHP features you like. 

_Please keep the application within one machine_. No need to download a dataset from a remote server to clamp() an integer, right? 

This activity is already started, and ends on November 19th, 2026, at 12:01am GMT: that is when PHP 8.6 is available.

## How to Participate

[Open an pr](../../pr/new) on this repo and declare your intent to participate. You may update the issue as you work on it until the end of the event. Include a README.md to tell what you're doing, and feel free to post dev diaries, sample output, etc. 

If you're full of ideas, open a second PR too :) 

Also feel free to comment on [other participants' issues](../../issues).

## Admins

Official admin for PHPenomenal PHP 8.6 is Damien Seguy [@dseguy](https://phpc.social/@dseguy) and Jon Purvis [@JonPurvis_](https://x.com/JonPurvis_). Find us here, or on the [PHP community discord server](https://discord.phpc.chat/).

## Resources

### php 8.6

You'll need PHP 8.6 on your machine, to check for linting. 

```
php8.6 -l phenomenal8.6.php 
```

Here are a few places to get PHP 8.6-beta

+ [PHP.net](https://www.php.net/pre-release-builds.php) has pre release versions, including Windows binaries. 
+ You can compile PHP 8.6 from source, with the packages above
+ Docker images are available [PHP 8.6](https://hub.docker.com/_/php/tags?name=8.6) 
+ [PHP on brew](https://github.com/shivammathur/homebrew-php) for mac
+ ...

### check script

There is a basic script [scripts/check-php86-features.php](scripts/check-php86-features.php) available to check which PHP 8.6 features are there. 

### more information

Here are extra resources for PHP 8.6

+ [What new in PHP 8.6: every feature you need to know](https://msaied.com/articles/whats-new-in-php-86-every-feature-you-need-to-know)
+ [PHP 8.6 at PHP.watch](https://php.watch/versions/8.6)
+ [New in PHP 8.6](https://stitcher.io/blog/new-in-php-86)
+ [PHP 8.6](https://laravel-news.com/php-8-6)
+ [What's New in PHP 8.6: Features, Changes, and Deprecations](https://www.zend.com/blog/php-8-6)
+ [UPGRADING  to PHP 8.6](https://github.com/php/php-src/blob/PHP-8.6/UPGRADING)
+ [PHP 8.6 Enters Beta: What You Should Actually Start Looking At](https://nicolas-dabene.fr/en/blog/php-86-enters-beta-what-you-should-actually-start-looking-at/)

You can submit a PR to add more resources here.

## ResPHPect! 

More importantly, have fun getting ready for PHP 8.6! There will be bonus points for funny applications, and the best of them will receive an elePHPant. 


