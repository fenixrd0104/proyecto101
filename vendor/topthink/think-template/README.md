# ThinkTemplate

Compiled template engine based on XML and tag library

## Main Features

- Support the mixed definition of XML tag library and common tags;
- Support writing directly with PHP code;
- Supporting files include;
- Support multi-level label nesting;
- Support layout template function;
- Compile and run multiple times at one time, the compilation and operation efficiency is very high;
- Template files and layout templates are updated, and the template cache is automatically updated;
- System variables are output directly without assignment;
- Supports fast output of multidimensional arrays;
- Support default values ​​for template variables;
- Support page code to remove Html blank;
- Supports variable combination modifiers and formatting functions;
- allows to define template disable functions and disable PHP syntax;
- Expansion through tag library;

## Install

~~~php
composer require topthink/think-template
~~~

## Usage example


~~~php
<?php
namespace think;

require __DIR__.'/vendor/autoload.php';

// Set template engine parameters
$config = [
	'view_path'	=>	'./template/',
	'cache_path'	=>	'./runtime/',
	'view_suffix'   =>	'html',
];

$template = new Template($config);
// template variable assignment
$template->assign(['name' => 'think']);
// Read template file rendering output
$template->fetch('index');
// full template file rendering
$template->fetch('./template/test.php');
// render content output
$template->display($content);
~~~

Support static call

~~~
use think\facade\Template;

Template::config([
	'view_path'	=>	'./template/',
	'cache_path'	=>	'./runtime/',
	'view_suffix'   =>	'html',
]);
Template::assign(['name' => 'think']);
Template::fetch('index',['name' => 'think']);
Template::display($content,['name' => 'think']);
~~~

For detailed usage, refer to [Development Manual](https://www.kancloud.cn/manual/think-template/content)