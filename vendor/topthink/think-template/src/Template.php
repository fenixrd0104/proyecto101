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
     * @param  string $name ?????????
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
     * ??????????????????
     * ?????????????????????TagLib?????? ????????????????????????
     * @access public
     * @param  string $content ????????????????????????
     * @return void
     */
    public function parse(string &$content): void
    {
        // ?????????????????????
        if (empty($content)) {
            return;
        }

        // ??????literal????????????
        $this->parseLiteral($content);

        // ????????????
        $this->parseExtend($content);

        // ????????????
        $this->parseLayout($content);

        // ??????include??????
        $this->parseInclude($content);

        // ?????????????????????literal????????????
        $this->parseLiteral($content);

        // ??????PHP??????
        $this->parsePhp($content);

        // ????????????????????????????????????
        // ?????????????????????????????????????????????????????????
        // ??????????????????????????????
        // ?????????<taglib name="html,mytag..." />
        // ???TAGLIB_LOAD?????????true?????????????????????
        if ($this->config['taglib_load']) {
            $tagLibs = $this->getIncludeTagLib($content);

            if (!empty($tagLibs)) {
                // ????????????TagLib????????????
                foreach ($tagLibs as $tagLibName) {
                    $this->parseTagLib($tagLibName, $content);
                }
            }
        }

        // ???????????????????????? ??????????????????????????????taglib???????????? ????????????????????????XML??????
        if ($this->config['taglib_pre_load']) {
            $tagLibs = explode(',', $this->config['taglib_pre_load']);

            foreach ($tagLibs as $tag) {
                $this->parseTagLib($tag, $content);
            }
        }

        // ??????????????? ????????????taglib??????????????????????????? ???????????????????????????XML??????
        $tagLibs = explode(',', $this->config['taglib_build_in']);

        foreach ($tagLibs as $tag) {
            $this->parseTagLib($tag, $content, true);
        }

        // ???????????????????????? {$tagName}
        $this->parseTag($content);

        // ??????????????????Literal??????
        $this->parseLiteral($content, true);
    }

    /**
     * ??????PHP??????
     * @access private
     * @param  string $content ????????????????????????
     * @return void
     * @throws Exception
     */
    private function parsePhp(string &$content): void
    {
        // ????????????????????????<??????????echo???????????? ????????????????????????xml??????
        $content = preg_replace('/(<\?(?!php|=|$))/i', '<?php echo \'\\1\'; ?>' . "\n", $content);

        // PHP????????????
        if ($this->config['tpl_deny_php'] && false !== strpos($content, '<?php')) {
            throw new Exception('not allow php tag');
        }
    }

    /**
     * ??????????????????????????????
     * @access private
     * @param  string $content ????????????????????????
     * @return void
     */
    private function parseLayout(string &$content): void
    {
        // ??????????????????????????????
        if (preg_match($this->getRegex('layout'), $content, $matches)) {
            // ??????Layout??????
            $content = str_replace($matches[0], '', $content);
            // ??????Layout??????
            $array = $this->parseAttr($matches[0]);

            if (!$this->config['layout_on'] || $this->config['layout_name'] != $array['name']) {
                // ??????????????????
                $layoutFile = $this->parseTemplateFile($array['name']);

                if ($layoutFile) {
                    $replace = isset($array['replace']) ? $array['replace'] : $this->config['layout_item'];
                    // ???????????????????????????
                    $content = str_replace($replace, $content, file_get_contents($layoutFile));
                }
            }
        } else {
            $content = str_replace('{__NOLAYOUT__}', '', $content);
        }
    }

    /**
     * ??????????????????include??????
     * @access private
     * @param  string $content ????????????????????????
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

                    // ????????????????????????????????????
                    $parseStr = $this->parseTemplateName($file);

                    foreach ($array as $k => $v) {
                        // ???$????????????????????????????????????
                        if (0 === strpos($v, '$')) {
                            $v = $this->get(substr($v, 1));
                        }

                        $parseStr = str_replace('[' . $k . ']', $v, $parseStr);
                    }

                    $content = str_replace($match[0], $parseStr, $content);
                    // ???????????????????????????????????????
                    $func($parseStr);
                }
                unset($matches);
            }
        };

        // ??????????????????include??????
        $func($content);
    }

    /**
     * ??????????????????extend??????
     * @access private
     * @param  string $content ????????????????????????
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
                    // ??????????????????
                    $extend = $this->parseTemplateName($matches['name']);

                    // ??????????????????
                    $func($extend);

                    // ??????block????????????
                    $blocks = array_merge($blocks, $this->parseBlock($template));

                    return;
                }
            } else {
                // ??????????????????block????????????
                $baseBlocks = $this->parseBlock($template, true);

                if (empty($extend)) {
                    // ???extend????????????block???????????????
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
                        // ??????????????????block??????
                        foreach ($children[$name] as $key) {
                            $replace = str_replace($baseBlocks[$key]['begin'] . $baseBlocks[$key]['content'] . $baseBlocks[$key]['end'], $blocks[$key]['content'], $replace);
                        }
                    }

                    if (isset($blocks[$name])) {
                        // ??????{__block__}???????????????????????????????????????????????????????????????
                        $replace = str_replace(['{__BLOCK__}', '{__block__}'], $replace, $blocks[$name]['content']);

                        if (!empty($val['parent'])) {
                            // ????????????????????????block??????
                            $parent = $val['parent'];

                            if (isset($blocks[$parent])) {
                                $blocks[$parent]['content'] = str_replace($blocks[$name]['begin'] . $blocks[$name]['content'] . $blocks[$name]['end'], $replace, $blocks[$parent]['content']);
                            }

                            $blocks[$name]['content'] = $replace;
                            $children[$parent][]      = $name;

                            continue;
                        }
                    } elseif (!empty($val['parent'])) {
                        // ??????????????????????????????????????????
                        $children[$val['parent']][] = $name;
                        $blocks[$name]              = $val;
                    }

                    if (!$val['parent']) {
                        // ????????????????????????block??????
                        $extend = str_replace($val['begin'] . $val['content'] . $val['end'], $replace, $extend);
                    }
                }
            }

            $content = $extend;
            unset($blocks, $baseBlocks);
        }
    }

    /**
     * ??????????????????literal??????
     * @access private
     * @param  string   $content ????????????
     * @param  boolean  $restore ???????????????
     * @return void
     */
    private function parseLiteral(string &$content, bool $restore = false): void
    {
        $regex = $this->getRegex($restore ? 'restoreliteral' : 'literal');

        if (preg_match_all($regex, $content, $matches, PREG_SET_ORDER)) {
            if (!$restore) {
                $count = count($this->literal);

                // ??????literal??????
                foreach ($matches as $match) {
                    $this->literal[] = substr($match[0], strlen($match[1]), -strlen($match[2]));
                    $content         = str_replace($match[0], "<!--###literal{$count}###-->", $content);
                    $count++;
                }
            } else {
                // ??????literal??????
                foreach ($matches as $match) {
                    $content = str_replace($match[0], $this->literal[$match[1]], $content);
                }

                // ??????literal??????
                $this->literal = [];
            }

            unset($matches);
        }
    }

    /**
     * ??????????????????block??????
     * @access private
     * @param  string   $content ????????????
     * @param  boolean  $sort ????????????
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
                    // ??????????????????
                    $right[] = [
                        'name'   => $match[2][0],
                        'offset' => $match[0][1],
                        'tag'    => $match[0][0],
                    ];
                }
            }

            unset($right, $matches);

            if ($sort) {
                // ???block??????????????????????????????????????????
                array_multisort($keys, $result);
            }
        }

        return $result;
    }

    /**
     * ??????????????????????????????TagLib???
     * ???????????????
     * @access private
     * @param  string $content ????????????
     * @return array|null
     */
    private function getIncludeTagLib(string &$content)
    {
        // ???????????????TagLib??????
        if (preg_match($this->getRegex('taglib'), $content, $matches)) {
            // ??????TagLib??????
            $content = str_replace($matches[0], '', $content);

            return explode(',', $matches['name']);
        }
    }

    /**
     * TagLib?????????
     * @access public
     * @param  string   $tagLib ?????????????????????
     * @param  string   $content ????????????????????????
     * @param  boolean  $hide ???????????????????????????
     * @return void
     */
    public function parseTagLib(string $tagLib, string &$content, bool $hide = false): void
    {
        if (false !== strpos($tagLib, '\\')) {
            // ????????????????????????????????????
            $className = $tagLib;
            $tagLib    = substr($tagLib, strrpos($tagLib, '\\') + 1);
        } else {
            $className = '\\think\\template\\taglib\\' . ucwords($tagLib);
        }

        $tLib = new $className($this);

        $tLib->parseTag($content, $hide ? '' : $tagLib);
    }

    /**
     * ??????????????????
     * @access public
     * @param  string   $str ???????????????
     * @param  string   $name ????????????????????????????????????
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
     * ??????????????????
     * ????????? {TagName:args [|content] }
     * @access private
     * @param  string $content ????????????????????????
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
                        // ?????????????????? ?????? {$varName}
                        // ????????????????
                        if (false !== $pos = strpos($str, '?')) {
                            $array = preg_split('/([!=]={1,2}|(?<!-)[><]={0,1})/', substr($str, 0, $pos), 2, PREG_SPLIT_DELIM_CAPTURE);
                            $name  = $array[0];

                            $this->parseVar($name);
                            //$this->parseVarFunction($name);

                            $str = trim(substr($str, $pos + 1));
                            $this->parseVar($str);
                            $first = substr($str, 0, 1);

                            if (strpos($name, ')')) {
                                // $name????????????????????????????????????????????????
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

                                // $name?????????
                                switch ($first) {
                                    case '?':
                                        // {$varname??'xxx'} $varname??????????????????$varname,????????????xxx
                                        $str = '<?php echo ' . ($express ?: 'isset(' . $name . ')') . ' ? ' . $this->parseVarFunction($name) . ' : ' . $str . '; ?>';
                                        break;
                                    case '=':
                                        // {$varname?='xxx'} $varname??????????????????xxx
                                        $str = '<?php if(' . ($express ?: '!empty(' . $name . ')') . ') echo ' . $str . '; ?>';
                                        break;
                                    case ':':
                                        // {$varname?:'xxx'} $varname???????????????$varname,????????????xxx
                                        $str = '<?php echo ' . ($express ?: '!empty(' . $name . ')') . ' ? ' . $this->parseVarFunction($name) . ' : ' . $str . '; ?>';
                                        break;
                                    default:
                                        if (strpos($str, ':')) {
                                            // {$varname ? 'a' : 'b'} $varname???????????????a,????????????b
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
                        // ???????????????????????????
                        $str = substr($str, 1);
                        $this->parseVar($str);
                        $str = '<?php echo ' . $str . '; ?>';
                        break;
                    case '~':
                        // ??????????????????
                        $str = substr($str, 1);
                        $this->parseVar($str);
                        $str = '<?php ' . $str . '; ?>';
                        break;
                    case '-':
                    case '+':
                        // ????????????
                        $this->parseVar($str);
                        $str = '<?php echo ' . $str . '; ?>';
                        break;
                    case '/':
                        // ????????????
                        $flag2 = substr($str, 1, 1);
                        if ('/' == $flag2 || ('*' == $flag2 && substr(rtrim($str), -2) == '*/')) {
                            $str = '';
                        }
                        break;
                    default:
                        // ??????????????????????????????
                        $str = $this->config['tpl_begin'] . $str . $this->config['tpl_end'];
                        break;
                }

                $content = str_replace($match[0], $str, $content);
            }

            unset($matches);
        }
    }

    /**
     * ??????????????????,??????????????????
     * ????????? {$varname|function1|function2=arg1,arg2}
     * @access public
     * @param  string $varStr ????????????
     * @return void
     */
    public function parseVar(string &$varStr): void
    {
        $varStr = trim($varStr);

        if (preg_match_all('/\$[a-zA-Z_](?>\w*)(?:[:\.][0-9a-zA-Z_](?>\w*))+/', $varStr, $matches, PREG_OFFSET_CAPTURE)) {
            static $_varParseList = [];

            while ($matches[0]) {
                $match = array_pop($matches[0]);

                //???????????????????????????????????????????????????????????????
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
                            // ??????????????????
                            $parseStr = $this->parseRequestVar($vars);
                        } elseif ('$Think' == $first) {
                            // ?????????Think.?????????????????????????????? ?????????????????????????????????
                            $parseStr = $this->parseThinkVar($vars);
                        } else {
                            switch ($this->config['tpl_var_identify']) {
                                case 'array': // ???????????????
                                    $parseStr = $first . '[\'' . implode('\'][\'', $vars) . '\']';
                                    break;
                                case 'obj': // ???????????????
                                    $parseStr = $first . '->' . implode('->', $vars);
                                    break;
                                default: // ???????????????????????????
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
     * ????????????????????????????????????????????????
     * ?????? {$varname|function1|function2=arg1,arg2}
     * @access public
     * @param  string $varStr     ???????????????
     * @param  bool   $autoescape ????????????
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

        //???????????????????????????????????????????????????????????????
        if (isset($_varFunctionList[$_key])) {
            $varStr = $_varFunctionList[$_key];
        } else {
            $varArray = explode('|', $varStr);

            // ??????????????????
            $name = trim(array_shift($varArray));

            // ?????????????????????
            $length = count($varArray);

            // ????????????????????????????????????
            $template_deny_funs = explode(',', $this->config['tpl_deny_func_list']);

            for ($i = 0; $i < $length; $i++) {
                $args = explode('=', $varArray[$i], 2);

                // ??????????????????
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
                    case 'default': // ??????????????????
                        if (false === strpos($name, '(')) {
                            $name = '(isset(' . $name . ') && (' . $name . ' !== \'\')?' . $name . ':' . $args[1] . ')';
                        } else {
                            $name = '(' . $name . ' ?: ' . $args[1] . ')';
                        }
                        break;
                    default: // ??????????????????
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
     * ??????????????????
     * ?????? ??? $Request. ?????????????????????????????????
     * @access public
     * @param  array $vars ????????????
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
     * ????????????????????????
     * ?????? ??? $Think. ???????????????????????????????????????
     * @access public
     * @param  array $vars ????????????
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
     * ?????????????????????????????????????????? ??????????????????????????????
     * @access private
     * @param  string $templateName ???????????????
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
                //???????????????????????????
                $templateName = $this->get(substr($templateName, 1));
            }

            $template = $this->parseTemplateFile($templateName);

            if ($template) {
                // ????????????????????????
                $parseStr .= file_get_contents($template);
            }
        }

        return $parseStr;
    }

    /**
     * ?????????????????????
     * @access private
     * @param  string $template ?????????
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
            // ?????????????????????????????????
            $this->includeFile[$template] = filemtime($template);

            return $template;
        }

        throw new Exception('template not exists:' . $template);
    }

    /**
     * ?????????????????????
     * @access private
     * @param  string $tagName ?????????
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
