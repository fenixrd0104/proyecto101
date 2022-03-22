# think-view

ThinkPHP6.0 Think-Template template engine driver


## Install

~~~php
composer require topthink/think-view
~~~

## Usage example

This extension cannot be used alone and depends on ThinkPHP6.0+

First configure the template.php configuration file in the config directory, and then use it as follows.

~~~php

use think\facade\View;

// Template variable assignment and rendering output
View::assign(['name' => 'think'])
// output filter
->filter(function($content){
return str_replace('search', 'replace', $content);
})
// Read template file rendering output
->fetch('index');


// or use a helper function
view('index', ['name' => 'think']);
~~~

For the specific template engine configuration, please refer to the think-template library.