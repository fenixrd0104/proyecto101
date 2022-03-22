<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace think;

use Exception;
use Psr\SimpleCache\CacheInterface;

/**
 * Template engine separated from ThinkPHP
 * Support template parsing of XML tags and normal tags
 * Compiled template engine supports dynamic caching
 */
class Template
{
    /**
     * template variable
     * @var array
     */
    protected $data = [];

    /**
     * Template configuration parameters
     * @var array
     */
    protected $config = [
        'view_path' => '', // template path
        'view_suffix' => 'html', // default template file suffix
        'view_depr' => DIRECTORY_SEPARATOR,
        'cache_path' => '',
        'cache_suffix' => 'php', // default template cache suffix
        'tpl_deny_func_list' => 'echo,exit', // Template engine disable function
        'tpl_deny_php' => false, // Whether the default template engine disables PHP native code
        'tpl_begin' => '{', // Template engine normal tag start tag
        'tpl_end' => '}', // Template engine normal tag end tag
        'strip_space' => false, // Whether to remove HTML spaces and line breaks in the template file
        'tpl_cache' => true, // Whether to enable the template compilation cache, if set to false, it will be recompiled every time
        'compile_type' => 'file', // Template compilation type
        'cache_prefix' => '', // Template cache prefix identifier, which can be changed dynamically
        'cache_time' => 0, // Template cache validity 0 is permanent, (number as value, unit: second)
        'layout_on' => false, // layout template switch
        'layout_name' => 'layout', // layout template entry file
        'layout_item' => '{__CONTENT__}', // Content replacement flag of layout template
        'taglib_begin' => '{', // tag library tag start tag
        'taglib_end' => '}', // tag library tag end tag
        'taglib_load' => true, // Whether to use other tag libraries other than the built-in tag library, it is automatically detected by default
        'taglib_build_in' => 'cx', // built-in tag library name (tag library name does not need to be specified), separated by commas Pay attention to the parsing order
        'taglib_pre_load' => '', // Tag library to be loaded additionally (tag library name must be specified), multiple separated by commas
        'display_cache' => false, // template rendering cache
        'cache_id' => '', // Template cache ID
        'tpl_replace_string' => [],
        'tpl_var_identify' => 'array', // .Syntax variable identification, array|object|'', automatic identification when it is empty
        'default_filter' => 'htmlentities', // default filter method for normal tag output
    ];

    /**
     * Keep content information
     * @var array
     */
    private $literal = [];

    /**
     * Extended parsing rules
     * @var array
     */
    private $extend = [];

    /**
     * Template contains information
     * @var array
     */
    private $includeFile = [];

    /**
     * template storage object
     * @var object
     */
    protected $storage;

    /**
     * query cache object
     * @var CacheInterface
     */
    protected $cache;

    /**
     * Architecture function
     * @access public
     * @param  array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);

        $this->config['taglib_begin_origin'] = $this->config['taglib_begin'];
        $this->config['taglib_end_origin']   = $this->config['taglib_end'];

        $this->config['taglib_begin'] = preg_quote($this->config['taglib_begin'], '/');
        $this->config['taglib_end']   = preg_quote($this->config['taglib_end'], '/');
        $this->config['tpl_begin']    = preg_quote($this->config['tpl_begin'], '/');
        $this->config['tpl_end']      = preg_quote($this->config['tpl_end'], '/');

        // Initialize template compilation memory
        $type  = $this->config['compile_type'] ? $this->config['compile_type'] : 'File';
        $class = false !== strpos($type, '\\') ? $type : '\\think\\template\\driver\\' . ucwords($type);

        $this->storage = new $class();
    }

    /**
     * template variable assignment
     * @access public
     * @param  array $vars template variable
     * @return $this
     */
    public function assign(array $vars = [])
    {
        $this->data = array_merge($this->data, $vars);
        return $this;
    }

    /**
     * Template engine parameter assignment
     * @access public
     * @param  string $name
     * @param  mixed  $value
     */
    public function __set($name, $value)
    {
        $this->config[$name] = $value;
    }

    /**
     * Set the cache object
     * @access public
     * @param CacheInterface $cache cache object
     * @return void
     */
    public function setCache(CacheInterface $cache): void
    {
        $this->cache = $cache;
    }

    /**
     * Template engine configuration
     * @access public
     * @param  array $config
     * @return $this
     */
    public function config(array $config)
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    /**
     * Get template engine configuration items
     * @access public
     * @param  string $name
     * @return mixed
     */
    public function getConfig(string $name)
    {
        return $this->config[$name] ?? null;
    }

    /**
     * get template variable
     * @access public
     * @param  string $name 变量名
     * @return mixed
     */
    public function get(string $name = '')
    {
        if ('' == $name) {
            return $this->data;
        }

        $data = $this->data;

        foreach (explode('.', $name) as $key => $val) {
            if (isset($data[$val])) {
                $data = $data[$val];
            } else {
                $data = null;
                break;
            }
        }

        return $data;
    }

    /**
     * Extended template parsing rules
     * @access public
     * @param string $rule parsing rule
     * @param callable $callback parsing rules
     * @return void
     */
    public function extend(string $rule, callable $callback = null): void
    {
        $this->extend[$rule] = $callback;
    }

    /**
     * Render template file
     * @access public
     * @param string $template template file
     * @param array $vars template variables
     * @return void
     */
    public function fetch(string $template, array $vars = []): void
    {
        if ($vars) {
            $this->data = array_merge($this->data, $vars);
        }

        if (!empty($this->config['cache_id']) && $this->config['display_cache'] && $this->cache) {
            // read render cache
            if ($this->cache->has($this->config['cache_id'])) {
                echo $this->cache->get($this->config['cache_id']);
                return;
            }
        }

        $template = $this->parseTemplateFile($template);

        if ($template) {
            $cacheFile = $this->config['cache_path'] . $this->config['cache_prefix'] . md5($this->config['layout_on'] . $this->config['layout_name'] . $template) . '.' . ltrim($this->config['cache_suffix'], '.');

            if (!$this->checkCache($cacheFile)) {
                // Invalid cache Recompile template
                $content = file_get_contents($template);
                $this->compiler($content, $cacheFile);
            }

            // page cache
            ob_start();
            ob_implicit_flush(0);

            // read build store
            $this->storage->read($cacheFile, $this->data);

            // get and clear cache
            $content = ob_get_clean();

            if (!empty($this->config['cache_id']) && $this->config['display_cache'] && $this->cache) {
                // cache page output
                $this->cache->set($this->config['cache_id'], $content, $this->config['cache_time']);
            }

            echo $content;
        }
    }

    /**
     * Check if the compile cache exists
     * @access public
     * @param string $cacheId cache id
     * @return boolean
     */
    public function isCache(string $cacheId): bool
    {
        if ($cacheId && $this->cache && $this->config['display_cache']) {
            // cache page output
            return $this->cache->has($cacheId);
        }

        return false;
    }

    /**
     * Render template content
     * @access public
     * @param string $content template content
     * @param array $vars template variables
     * @return void
     */
    public function display(string $content, array $vars = []): void
    {
        if ($vars) {
            $this->data = array_merge($this->data, $vars);
        }

        $cacheFile = $this->config['cache_path'] . $this->config['cache_prefix'] . md5($content) . '.' . ltrim($this->config['cache_suffix'], '.');

        if (!$this->checkCache($cacheFile)) {
            // cache invalidation template compilation
            $this->compiler($content, $cacheFile);
        }

        // read build store
        $this->storage->read($cacheFile, $this->data);
    }

    /**
     * set the layout
     * @access public
     * @param mixed $name layout template name false to close the layout
     * @param string $replace layout template content replacement flag
     * @return $this
     */
    public function layout($name, string $replace = '')
    {
        if (false === $name) {
            // close layout
            $this->config['layout_on'] = false;
        } else {
            // open layout
            $this->config['layout_on'] = true;

            // name must be a string
            if (is_string($name)) {
                $this->config['layout_name'] = $name;
            }

            if (!empty($replace)) {
                $this->config['layout_item'] = $replace;
            }
        }

        return $this;
    }

    /**
     * Check if the compile cache is valid
     * If invalid, need to recompile
     * @access private
     * @param string $cacheFile cache file name
     * @return bool
     */
    private function checkCache(string $cacheFile): bool
    {
        if (!$this->config['tpl_cache'] || !is_file($cacheFile) || !$handle = @fopen($cacheFile, "r")) {
            return false;
        }

        // read first line
        preg_match('/\/\*(.+?)\*\//', fgets($handle), $matches);

        if (!isset($matches[1])) {
            return false;
        }

        $includeFile = unserialize($matches[1]);

        if (!is_array($includeFile)) {
            return false;
        }

        // Check template files for updates
        foreach ($includeFile as $path => $time) {
            if (is_file($path) && filemtime($path) > $time) {
                // If the template file is updated, the cache needs to be updated
                return false;
            }
        }

        // Check if the compilation store is valid
        return $this->storage->check($cacheFile, $this->config['cache_time']);
    }

    /**
     * Compile the contents of the template file
     * @access private
     * @param string $content template content
     * @param string $cacheFile cache file name
     * @return void
     */
    private function compiler(string &$content, string $cacheFile): void
    {
        // Determine whether to enable layout
        if ($this->config['layout_on']) {
            if (false !== strpos($content, '{__NOLAYOUT__}')) {
                // Can be defined separately without using layout
                $content = str_replace('{__NOLAYOUT__}', '', $content);
            } else {
                // Read layout template
                $layoutFile = $this->parseTemplateFile($this->config['layout_name']);

                if ($layoutFile) {
                    // Replace the body content of the layout
                    $content = str_replace($this->config['layout_item'], $content, file_get_contents($layoutFile));
                }
            }
        } else {
            $content = str_replace('{__NOLAYOUT__}', '', $content);
        }

        // Template parsing
        $this->parse($content);

        if ($this->config['strip_space']) {
            /* remove html spaces and line breaks */
            $find    = ['~>\s+<~', '~>(\s+\n|\r)~'];
            $replace = ['><', '>'];
            $content = preg_replace($find, $replace, $content);
        }

        // Optimize the generated php code
        $content = preg_replace('/\?>\s*<\?php\s(?!echo\b|\bend)/s', '', $content);

        // Template filtered output
        $replace = $this->config['tpl_replace_string'];
        $content = str_replace(array_keys($replace), array_values($replace), $content);

        // Add secure code and template reference records
        $content = '<?php /*' . serialize($this->includeFile) . '*/ ?>' . "\n" . $content;
        // compile storage
        $this->storage->write($cacheFile, $content);

        $this->includeFile = [];
    }

    /**
     * 模板解析入口
     * 支持普通标签和TagLib解析 支持自定义标签库
     * @access public
     * @param  string $content 要解析的模板内容
     * @return void
     */
    public function parse(string &$content): void
    {
        // 内容为空不解析
        if (empty($content)) {
            return;
        }

        // 替换literal标签内容
        $this->parseLiteral($content);

        // 解析继承
        $this->parseExtend($content);

        // 解析布局
        $this->parseLayout($content);

        // 检查include语法
        $this->parseInclude($content);

        // 替换包含文件中literal标签内容
        $this->parseLiteral($content);

        // 检查PHP语法
        $this->parsePhp($content);

        // 获取需要引入的标签库列表
        // 标签库只需要定义一次，允许引入多个一次
        // 一般放在文件的最前面
        // 格式：<taglib name="html,mytag..." />
        // 当TAGLIB_LOAD配置为true时才会进行检测
        if ($this->config['taglib_load']) {
            $tagLibs = $this->getIncludeTagLib($content);

            if (!empty($tagLibs)) {
                // 对导入的TagLib进行解析
                foreach ($tagLibs as $tagLibName) {
                    $this->parseTagLib($tagLibName, $content);
                }
            }
        }

        // 预先加载的标签库 无需在每个模板中使用taglib标签加载 但必须使用标签库XML前缀
        if ($this->config['taglib_pre_load']) {
            $tagLibs = explode(',', $this->config['taglib_pre_load']);

            foreach ($tagLibs as $tag) {
                $this->parseTagLib($tag, $content);
            }
        }

        // 内置标签库 无需使用taglib标签导入就可以使用 并且不需使用标签库XML前缀
        $tagLibs = explode(',', $this->config['taglib_build_in']);

        foreach ($tagLibs as $tag) {
            $this->parseTagLib($tag, $content, true);
        }

        // 解析普通模板标签 {$tagName}
        $this->parseTag($content);

        // 还原被替换的Literal标签
        $this->parseLiteral($content, true);
    }

    /**
     * 检查PHP语法
     * @access private
     * @param  string $content 要解析的模板内容
     * @return void
     * @throws Exception
     */
    private function parsePhp(string &$content): void
    {
        // 短标签的情况要将<?标签用echo方式输出 否则无法正常输出xml标识
        $content = preg_replace('/(<\?(?!php|=|$))/i', '<?php echo \'\\1\'; ?>' . "\n", $content);

        // PHP语法检查
        if ($this->config['tpl_deny_php'] && false !== strpos($content, '<?php')) {
            throw new Exception('not allow php tag');
        }
    }

    /**
     * 解析模板中的布局标签
     * @access private
     * @param  string $content 要解析的模板内容
     * @return void
     */
    private function parseLayout(string &$content): void
    {
        // 读取模板中的布局标签
        if (preg_match($this->getRegex('layout'), $content, $matches)) {
            // 替换Layout标签
            $content = str_replace($matches[0], '', $content);
            // 解析Layout标签
            $array = $this->parseAttr($matches[0]);

            if (!$this->config['layout_on'] || $this->config['layout_name'] != $array['name']) {
                // 读取布局模板
                $layoutFile = $this->parseTemplateFile($array['name']);

                if ($layoutFile) {
                    $replace = isset($array['replace']) ? $array['replace'] : $this->config['layout_item'];
                    // 替换布局的主体内容
                    $content = str_replace($replace, $content, file_get_contents($layoutFile));
                }
            }
        } else {
            $content = str_replace('{__NOLAYOUT__}', '', $content);
        }
    }

    /**
     * 解析模板中的include标签
     * @access private
     * @param  string $content 要解析的模板内容
     * @return void
     */
    private function parseInclude(string &$content): void
    {
        $regex = $this->getRegex('include');
        $func  = function ($template) use (&$func, &$regex, &$content) {
            if (preg_match_all($regex, $template, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $array = $this->parseAttr($match[0]);
                    $file  = $array['file'];
                    unset($array['file']);

                    // 分析模板文件名并读取内容
                    $parseStr = $this->parseTemplateName($file);

                    foreach ($array as $k => $v) {
                        // 以$开头字符串转换成模板变量
                        if (0 === strpos($v, '$')) {
                            $v = $this->get(substr($v, 1));
                        }

                        $parseStr = str_replace('[' . $k . ']', $v, $parseStr);
                    }

                    $content = str_replace($match[0], $parseStr, $content);
                    // 再次对包含文件进行模板分析
                    $func($parseStr);
                }
                unset($matches);
            }
        };

        // 替换模板中的include标签
        $func($content);
    }

    /**
     * 解析模板中的extend标签
     * @access private
     * @param  string $content 要解析的模板内容
     * @return void
     */
    private function parseExtend(string &$content): void
    {
        $regex  = $this->getRegex('extend');
        $array  = $blocks  = $baseBlocks  = [];
        $extend = '';

        $func = function ($template) use (&$func, &$regex, &$array, &$extend, &$blocks, &$baseBlocks) {
            if (preg_match($regex, $template, $matches)) {
                if (!isset($array[$matches['name']])) {
                    $array[$matches['name']] = 1;
                    // 读取继承模板
                    $extend = $this->parseTemplateName($matches['name']);

                    // 递归检查继承
                    $func($extend);

                    // 取得block标签内容
                    $blocks = array_merge($blocks, $this->parseBlock($template));

                    return;
                }
            } else {
                // 取得顶层模板block标签内容
                $baseBlocks = $this->parseBlock($template, true);

                if (empty($extend)) {
                    // 无extend标签但有block标签的情况
                    $extend = $template;
                }
            }
        };

        $func($content);

        if (!empty($extend)) {
            if ($baseBlocks) {
                $children = [];
                foreach ($baseBlocks as $name => $val) {
                    $replace = $val['content'];

                    if (!empty($children[$name])) {
                        // 如果包含有子block标签
                        foreach ($children[$name] as $key) {
                            $replace = str_replace($baseBlocks[$key]['begin'] . $baseBlocks[$key]['content'] . $baseBlocks[$key]['end'], $blocks[$key]['content'], $replace);
                        }
                    }

                    if (isset($blocks[$name])) {
                        // 带有{__block__}表示与所继承模板的相应标签合并，而不是覆盖
                        $replace = str_replace(['{__BLOCK__}', '{__block__}'], $replace, $blocks[$name]['content']);

                        if (!empty($val['parent'])) {
                            // 如果不是最顶层的block标签
                            $parent = $val['parent'];

                            if (isset($blocks[$parent])) {
                                $blocks[$parent]['content'] = str_replace($blocks[$name]['begin'] . $blocks[$name]['content'] . $blocks[$name]['end'], $replace, $blocks[$parent]['content']);
                            }

                            $blocks[$name]['content'] = $replace;
                            $children[$parent][]      = $name;

                            continue;
                        }
                    } elseif (!empty($val['parent'])) {
                        // 如果子标签没有被继承则用原值
                        $children[$val['parent']][] = $name;
                        $blocks[$name]              = $val;
                    }

                    if (!$val['parent']) {
                        // 替换模板中的顶级block标签
                        $extend = str_replace($val['begin'] . $val['content'] . $val['end'], $replace, $extend);
                    }
                }
            }

            $content = $extend;
            unset($blocks, $baseBlocks);
        }
    }

    /**
     * 替换页面中的literal标签
     * @access private
     * @param  string   $content 模板内容
     * @param  boolean  $restore 是否为还原
     * @return void
     */
    private function parseLiteral(string &$content, bool $restore = false): void
    {
        $regex = $this->getRegex($restore ? 'restoreliteral' : 'literal');

        if (preg_match_all($regex, $content, $matches, PREG_SET_ORDER)) {
            if (!$restore) {
                $count = count($this->literal);

                // 替换literal标签
                foreach ($matches as $match) {
                    $this->literal[] = substr($match[0], strlen($match[1]), -strlen($match[2]));
                    $content         = str_replace($match[0], "<!--###literal{$count}###-->", $content);
                    $count++;
                }
            } else {
                // 还原literal标签
                foreach ($matches as $match) {
                    $content = str_replace($match[0], $this->literal[$match[1]], $content);
                }

                // 清空literal记录
                $this->literal = [];
            }

            unset($matches);
        }
    }

    /**
     * 获取模板中的block标签
     * @access private
     * @param  string   $content 模板内容
     * @param  boolean  $sort 是否排序
     * @return array
     */
    private function parseBlock(string &$content, bool $sort = false): array
    {
        $regex  = $this->getRegex('block');
        $result = [];

        if (preg_match_all($regex, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            $right = $keys = [];

            foreach ($matches as $match) {
                if (empty($match['name'][0])) {
                    if (count($right) > 0) {
                        $tag    = array_pop($right);
                        $start  = $tag['offset'] + strlen($tag['tag']);
                        $length = $match[0][1] - $start;

                        $result[$tag['name']] = [
                            'begin'   => $tag['tag'],
                            'content' => substr($content, $start, $length),
                            'end'     => $match[0][0],
                            'parent'  => count($right) ? end($right)['name'] : '',
                        ];

                        $keys[$tag['name']] = $match[0][1];
                    }
                } else {
                    // 标签头压入栈
                    $right[] = [
                        'name'   => $match[2][0],
                        'offset' => $match[0][1],
                        'tag'    => $match[0][0],
                    ];
                }
            }

            unset($right, $matches);

            if ($sort) {
                // 按block标签结束符在模板中的位置排序
                array_multisort($keys, $result);
            }
        }

        return $result;
    }

    /**
     * 搜索模板页面中包含的TagLib库
     * 并返回列表
     * @access private
     * @param  string $content 模板内容
     * @return array|null
     */
    private function getIncludeTagLib(string &$content)
    {
        // 搜索是否有TagLib标签
        if (preg_match($this->getRegex('taglib'), $content, $matches)) {
            // 替换TagLib标签
            $content = str_replace($matches[0], '', $content);

            return explode(',', $matches['name']);
        }
    }

    /**
     * TagLib库解析
     * @access public
     * @param  string   $tagLib 要解析的标签库
     * @param  string   $content 要解析的模板内容
     * @param  boolean  $hide 是否隐藏标签库前缀
     * @return void
     */
    public function parseTagLib(string $tagLib, string &$content, bool $hide = false): void
    {
        if (false !== strpos($tagLib, '\\')) {
            // 支持指定标签库的命名空间
            $className = $tagLib;
            $tagLib    = substr($tagLib, strrpos($tagLib, '\\') + 1);
        } else {
            $className = '\\think\\template\\taglib\\' . ucwords($tagLib);
        }

        $tLib = new $className($this);

        $tLib->parseTag($content, $hide ? '' : $tagLib);
    }

    /**
     * 分析标签属性
     * @access public
     * @param  string   $str 属性字符串
     * @param  string   $name 不为空时返回指定的属性名
     * @return array
     */
    public function parseAttr(string $str, string $name = null): array
    {
        $regex = '/\s+(?>(?P<name>[\w-]+)\s*)=(?>\s*)([\"\'])(?P<value>(?:(?!\\2).)*)\\2/is';
        $array = [];

        if (preg_match_all($regex, $str, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $array[$match['name']] = $match['value'];
            }
            unset($matches);
        }

        if (!empty($name) && isset($array[$name])) {
            return $array[$name];
        }

        return $array;
    }

    /**
     * 模板标签解析
     * 格式： {TagName:args [|content] }
     * @access private
     * @param  string $content 要解析的模板内容
     * @return void
     */
    private function parseTag(string &$content): void
    {
        $regex = $this->getRegex('tag');

        if (preg_match_all($regex, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $str  = stripslashes($match[1]);
                $flag = substr($str, 0, 1);

                switch ($flag) {
                    case '$':
                        // 解析模板变量 格式 {$varName}
                        // 是否带有?号
                        if (false !== $pos = strpos($str, '?')) {
                            $array = preg_split('/([!=]={1,2}|(?<!-)[><]={0,1})/', substr($str, 0, $pos), 2, PREG_SPLIT_DELIM_CAPTURE);
                            $name  = $array[0];

                            $this->parseVar($name);
                            //$this->parseVarFunction($name);

                            $str = trim(substr($str, $pos + 1));
                            $this->parseVar($str);
                            $first = substr($str, 0, 1);

                            if (strpos($name, ')')) {
                                // $name为对象或是自动识别，或者含有函数
                                if (isset($array[1])) {
                                    $this->parseVar($array[2]);
                                    $name .= $array[1] . $array[2];
                                }

                                switch ($first) {
                                    case '?':
                                        $this->parseVarFunction($name);
                                        $str = '<?php echo (' . $name . ') ? ' . $name . ' : ' . substr($str, 1) . '; ?>';
                                        break;
                                    case '=':
                                        $str = '<?php if(' . $name . ') echo ' . substr($str, 1) . '; ?>';
                                        break;
                                    default:
                                        $str = '<?php echo ' . $name . '?' . $str . '; ?>';
                                }
                            } else {
                                if (isset($array[1])) {
                                    $express = true;
                                    $this->parseVar($array[2]);
                                    $express = $name . $array[1] . $array[2];
                                } else {
                                    $express = false;
                                }

                                if (in_array($first, ['?', '=', ':'])) {
                                    $str = trim(substr($str, 1));
                                    if ('$' == substr($str, 0, 1)) {
                                        $str = $this->parseVarFunction($str);
                                    }
                                }

                                // $name为数组
                                switch ($first) {
                                    case '?':
                                        // {$varname??'xxx'} $varname有定义则输出$varname,否则输出xxx
                                        $str = '<?php echo ' . ($express ?: 'isset(' . $name . ')') . ' ? ' . $this->parseVarFunction($name) . ' : ' . $str . '; ?>';
                                        break;
                                    case '=':
                                        // {$varname?='xxx'} $varname为真时才输出xxx
                                        $str = '<?php if(' . ($express ?: '!empty(' . $name . ')') . ') echo ' . $str . '; ?>';
                                        break;
                                    case ':':
                                        // {$varname?:'xxx'} $varname为真时输出$varname,否则输出xxx
                                        $str = '<?php echo ' . ($express ?: '!empty(' . $name . ')') . ' ? ' . $this->parseVarFunction($name) . ' : ' . $str . '; ?>';
                                        break;
                                    default:
                                        if (strpos($str, ':')) {
                                            // {$varname ? 'a' : 'b'} $varname为真时输出a,否则输出b
                                            $array = explode(':', $str, 2);

                                            $array[0] = '$' == substr(trim($array[0]), 0, 1) ? $this->parseVarFunction($array[0]) : $array[0];
                                            $array[1] = '$' == substr(trim($array[1]), 0, 1) ? $this->parseVarFunction($array[1]) : $array[1];

                                            $str = implode(' : ', $array);
                                        }
                                        $str = '<?php echo ' . ($express ?: '!empty(' . $name . ')') . ' ? ' . $str . '; ?>';
                                }
                            }
                        } else {
                            $this->parseVar($str);
                            $this->parseVarFunction($str);
                            $str = '<?php echo ' . $str . '; ?>';
                        }
                        break;
                    case ':':
                        // 输出某个函数的结果
                        $str = substr($str, 1);
                        $this->parseVar($str);
                        $str = '<?php echo ' . $str . '; ?>';
                        break;
                    case '~':
                        // 执行某个函数
                        $str = substr($str, 1);
                        $this->parseVar($str);
                        $str = '<?php ' . $str . '; ?>';
                        break;
                    case '-':
                    case '+':
                        // 输出计算
                        $this->parseVar($str);
                        $str = '<?php echo ' . $str . '; ?>';
                        break;
                    case '/':
                        // 注释标签
                        $flag2 = substr($str, 1, 1);
                        if ('/' == $flag2 || ('*' == $flag2 && substr(rtrim($str), -2) == '*/')) {
                            $str = '';
                        }
                        break;
                    default:
                        // 未识别的标签直接返回
                        $str = $this->config['tpl_begin'] . $str . $this->config['tpl_end'];
                        break;
                }

                $content = str_replace($match[0], $str, $content);
            }

            unset($matches);
        }
    }

    /**
     * 模板变量解析,支持使用函数
     * 格式： {$varname|function1|function2=arg1,arg2}
     * @access public
     * @param  string $varStr 变量数据
     * @return void
     */
    public function parseVar(string &$varStr): void
    {
        $varStr = trim($varStr);

        if (preg_match_all('/\$[a-zA-Z_](?>\w*)(?:[:\.][0-9a-zA-Z_](?>\w*))+/', $varStr, $matches, PREG_OFFSET_CAPTURE)) {
            static $_varParseList = [];

            while ($matches[0]) {
                $match = array_pop($matches[0]);

                //如果已经解析过该变量字串，则直接返回变量值
                if (isset($_varParseList[$match[0]])) {
                    $parseStr = $_varParseList[$match[0]];
                } else {
                    if (strpos($match[0], '.')) {
                        $vars  = explode('.', $match[0]);
                        $first = array_shift($vars);

                        if (isset($this->extend[$first])) {
                            $callback = $this->extend[$first];
                            $parseStr = $callback($vars);
                        } elseif ('$Request' == $first) {
                            // 输出请求变量
                            $parseStr = $this->parseRequestVar($vars);
                        } elseif ('$Think' == $first) {
                            // 所有以Think.打头的以特殊变量对待 无需模板赋值就可以输出
                            $parseStr = $this->parseThinkVar($vars);
                        } else {
                            switch ($this->config['tpl_var_identify']) {
                                case 'array': // 识别为数组
                                    $parseStr = $first . '[\'' . implode('\'][\'', $vars) . '\']';
                                    break;
                                case 'obj': // 识别为对象
                                    $parseStr = $first . '->' . implode('->', $vars);
                                    break;
                                default: // 自动判断数组或对象
                                    $parseStr = '(is_array(' . $first . ')?' . $first . '[\'' . implode('\'][\'', $vars) . '\']:' . $first . '->' . implode('->', $vars) . ')';
                            }
                        }
                    } else {
                        $parseStr = str_replace(':', '->', $match[0]);
                    }

                    $_varParseList[$match[0]] = $parseStr;
                }

                $varStr = substr_replace($varStr, $parseStr, $match[1], strlen($match[0]));
            }
            unset($matches);
        }
    }

    /**
     * 对模板中使用了函数的变量进行解析
     * 格式 {$varname|function1|function2=arg1,arg2}
     * @access public
     * @param  string $varStr     变量字符串
     * @param  bool   $autoescape 自动转义
     * @return string
     */
    public function parseVarFunction(string &$varStr, bool $autoescape = true): string
    {
        if (!$autoescape && false === strpos($varStr, '|')) {
            return $varStr;
        } elseif ($autoescape && !preg_match('/\|(\s)?raw(\||\s)?/i', $varStr)) {
            $varStr .= '|' . $this->config['default_filter'];
        }

        static $_varFunctionList = [];

        $_key = md5($varStr);

        //如果已经解析过该变量字串，则直接返回变量值
        if (isset($_varFunctionList[$_key])) {
            $varStr = $_varFunctionList[$_key];
        } else {
            $varArray = explode('|', $varStr);

            // 取得变量名称
            $name = trim(array_shift($varArray));

            // 对变量使用函数
            $length = count($varArray);

            // 取得模板禁止使用函数列表
            $template_deny_funs = explode(',', $this->config['tpl_deny_func_list']);

            for ($i = 0; $i < $length; $i++) {
                $args = explode('=', $varArray[$i], 2);

                // 模板函数过滤
                $fun = trim($args[0]);
                if (in_array($fun, $template_deny_funs)) {
                    continue;
                }

                switch (strtolower($fun)) {
                    case 'raw':
                        break;
                    case 'date':
                        $name = 'date(' . $args[1] . ',!is_numeric(' . $name . ')? strtotime(' . $name . ') : ' . $name . ')';
                        break;
                    case 'first':
                        $name = 'current(' . $name . ')';
                        break;
                    case 'last':
                        $name = 'end(' . $name . ')';
                        break;
                    case 'upper':
                        $name = 'strtoupper(' . $name . ')';
                        break;
                    case 'lower':
                        $name = 'strtolower(' . $name . ')';
                        break;
                    case 'format':
                        $name = 'sprintf(' . $args[1] . ',' . $name . ')';
                        break;
                    case 'default': // 特殊模板函数
                        if (false === strpos($name, '(')) {
                            $name = '(isset(' . $name . ') && (' . $name . ' !== \'\')?' . $name . ':' . $args[1] . ')';
                        } else {
                            $name = '(' . $name . ' ?: ' . $args[1] . ')';
                        }
                        break;
                    default: // 通用模板函数
                        if (isset($args[1])) {
                            if (strstr($args[1], '###')) {
                                $args[1] = str_replace('###', $name, $args[1]);
                                $name    = "$fun($args[1])";
                            } else {
                                $name = "$fun($name,$args[1])";
                            }
                        } else {
                            if (!empty($args[0])) {
                                $name = "$fun($name)";
                            }
                        }
                }
            }

            $_varFunctionList[$_key] = $name;
            $varStr                  = $name;
        }
        return $varStr;
    }

    /**
     * 请求变量解析
     * 格式 以 $Request. 打头的变量属于请求变量
     * @access public
     * @param  array $vars 变量数组
     * @return string
     */
    public function parseRequestVar(array $vars): string
    {
        $type  = strtoupper(trim(array_shift($vars)));
        $param = implode('.', $vars);

        switch ($type) {
            case 'SERVER':
                $parseStr = '$_SERVER[\'' . $param . '\']';
                break;
            case 'GET':
                $parseStr = '$_GET[\'' . $param . '\']';
                break;
            case 'POST':
                $parseStr = '$_POST[\'' . $param . '\']';
                break;
            case 'COOKIE':
                $parseStr = '$_COOKIE[\'' . $param . '\']';
                break;
            case 'SESSION':
                $parseStr = '$_SESSION[\'' . $param . '\']';
                break;
            case 'ENV':
                $parseStr = '$_ENV[\'' . $param . '\']';
                break;
            case 'REQUEST':
                $parseStr = '$_REQUEST[\'' . $param . '\']';
                break;
            default:
                $parseStr = '\'\'';
        }

        return $parseStr;
    }

    /**
     * 特殊模板变量解析
     * 格式 以 $Think. 打头的变量属于特殊模板变量
     * @access public
     * @param  array $vars 变量数组
     * @return string
     */
    public function parseThinkVar(array $vars): string
    {
        $type  = strtoupper(trim(array_shift($vars)));
        $param = implode('.', $vars);

        switch ($type) {
            case 'CONST':
                $parseStr = strtoupper($param);
                break;
            case 'NOW':
                $parseStr = "date('Y-m-d g:i a',time())";
                break;
            case 'LDELIM':
                $parseStr = '\'' . ltrim($this->config['tpl_begin'], '\\') . '\'';
                break;
            case 'RDELIM':
                $parseStr = '\'' . ltrim($this->config['tpl_end'], '\\') . '\'';
                break;
            default:
                $parseStr = defined($type) ? $type : '\'\'';
        }

        return $parseStr;
    }

    /**
     * 分析加载的模板文件并读取内容 支持多个模板文件读取
     * @access private
     * @param  string $templateName 模板文件名
     * @return string
     */
    private function parseTemplateName(string $templateName): string
    {
        $array    = explode(',', $templateName);
        $parseStr = '';

        foreach ($array as $templateName) {
            if (empty($templateName)) {
                continue;
            }

            if (0 === strpos($templateName, '$')) {
                //支持加载变量文件名
                $templateName = $this->get(substr($templateName, 1));
            }

            $template = $this->parseTemplateFile($templateName);

            if ($template) {
                // 获取模板文件内容
                $parseStr .= file_get_contents($template);
            }
        }

        return $parseStr;
    }

    /**
     * 解析模板文件名
     * @access private
     * @param  string $template 文件名
     * @return string
     */
    private function parseTemplateFile(string $template): string
    {
        if ('' == pathinfo($template, PATHINFO_EXTENSION)) {

            if (0 !== strpos($template, '/')) {
                $template = str_replace(['/', ':'], $this->config['view_depr'], $template);
            } else {
                $template = str_replace(['/', ':'], $this->config['view_depr'], substr($template, 1));
            }

            $template = $this->config['view_path'] . $template . '.' . ltrim($this->config['view_suffix'], '.');
        }

        if (is_file($template)) {
            // 记录模板文件的更新时间
            $this->includeFile[$template] = filemtime($template);

            return $template;
        }

        throw new Exception('template not exists:' . $template);
    }

    /**
     * 按标签生成正则
     * @access private
     * @param  string $tagName 标签名
     * @return string
     */
    private function getRegex(string $tagName): string
    {
        $regex = '';
        if ('tag' == $tagName) {
            $begin = $this->config['tpl_begin'];
            $end   = $this->config['tpl_end'];

            if (strlen(ltrim($begin, '\\')) == 1 && strlen(ltrim($end, '\\')) == 1) {
                $regex = $begin . '((?:[\$]{1,2}[a-wA-w_]|[\:\~][\$a-wA-w_]|[+]{2}[\$][a-wA-w_]|[-]{2}[\$][a-wA-w_]|\/[\*\/])(?>[^' . $end . ']*))' . $end;
            } else {
                $regex = $begin . '((?:[\$]{1,2}[a-wA-w_]|[\:\~][\$a-wA-w_]|[+]{2}[\$][a-wA-w_]|[-]{2}[\$][a-wA-w_]|\/[\*\/])(?>(?:(?!' . $end . ').)*))' . $end;
            }
        } else {
            $begin  = $this->config['taglib_begin'];
            $end    = $this->config['taglib_end'];
            $single = strlen(ltrim($begin, '\\')) == 1 && strlen(ltrim($end, '\\')) == 1 ? true : false;

            switch ($tagName) {
                case 'block':
                    if ($single) {
                        $regex = $begin . '(?:' . $tagName . '\b\s+(?>(?:(?!name=).)*)\bname=([\'\"])(?P<name>[\$\w\-\/\.]+)\\1(?>[^' . $end . ']*)|\/' . $tagName . ')' . $end;
                    } else {
                        $regex = $begin . '(?:' . $tagName . '\b\s+(?>(?:(?!name=).)*)\bname=([\'\"])(?P<name>[\$\w\-\/\.]+)\\1(?>(?:(?!' . $end . ').)*)|\/' . $tagName . ')' . $end;
                    }
                    break;
                case 'literal':
                    if ($single) {
                        $regex = '(' . $begin . $tagName . '\b(?>[^' . $end . ']*)' . $end . ')';
                        $regex .= '(?:(?>[^' . $begin . ']*)(?>(?!' . $begin . '(?>' . $tagName . '\b[^' . $end . ']*|\/' . $tagName . ')' . $end . ')' . $begin . '[^' . $begin . ']*)*)';
                        $regex .= '(' . $begin . '\/' . $tagName . $end . ')';
                    } else {
                        $regex = '(' . $begin . $tagName . '\b(?>(?:(?!' . $end . ').)*)' . $end . ')';
                        $regex .= '(?:(?>(?:(?!' . $begin . ').)*)(?>(?!' . $begin . '(?>' . $tagName . '\b(?>(?:(?!' . $end . ').)*)|\/' . $tagName . ')' . $end . ')' . $begin . '(?>(?:(?!' . $begin . ').)*))*)';
                        $regex .= '(' . $begin . '\/' . $tagName . $end . ')';
                    }
                    break;
                case 'restoreliteral':
                    $regex = '<!--###literal(\d+)###-->';
                    break;
                case 'include':
                    $name = 'file';
                case 'taglib':
                case 'layout':
                case 'extend':
                    if (empty($name)) {
                        $name = 'name';
                    }
                    if ($single) {
                        $regex = $begin . $tagName . '\b\s+(?>(?:(?!' . $name . '=).)*)\b' . $name . '=([\'\"])(?P<name>[\$\w\-\/\.\:@,\\\\]+)\\1(?>[^' . $end . ']*)' . $end;
                    } else {
                        $regex = $begin . $tagName . '\b\s+(?>(?:(?!' . $name . '=).)*)\b' . $name . '=([\'\"])(?P<name>[\$\w\-\/\.\:@,\\\\]+)\\1(?>(?:(?!' . $end . ').)*)' . $end;
                    }
                    break;
            }
        }

        return '/' . $regex . '/is';
    }

    public function __debugInfo()
    {
        $data = get_object_vars($this);
        unset($data['storage']);

        return $data;
    }
}