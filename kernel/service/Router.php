<?php
namespace lying\service;

/**
 * 路由组件
 *
 * @author carolkey <me@suyaqi.cn>
 * @since 2.0
 * @link https://github.com/carolkey/lying
 * @license MIT
 */
class Router extends Service
{
    /**
     * @var boolean 是否PATHINFO
     */
    private $pathinfo;

    /**
     * @var array 存当前路由[module, controller, action]
     */
    private $router;

    /**
     * @var string 默认模块
     */
    protected $module = 'index';

    /**
     * @var string 默认控制器
     */
    protected $controller = 'index';

    /**
     * @var string 默认方法
     */
    protected $action = 'index';

    /**
     * @var array 路由规则
     */
    protected $rule = [];

    /**
     * 解析路由
     * @return array 返回路由数组[module, controller, action]
     */
    public function parse()
    {
        //解析URL
        $parse = parse_url(\Lying::$maker->request()->uri());
        var_dump($_GET);
        //解析原生GET，这里是为了去除转发规则中$_GET本身中无用的参数
        parse_str(isset($parse['query']) ? $parse['query'] : '', $_GET);
        //去掉index.php，不区分大小写
        $path = preg_replace('/^\/index\.php/i', '', $parse['path'], 1, $this->pathinfo);

        $path = trim(preg_replace('/^\/index\.php/i', '', $parse['path'], 1), '/');
        //分割后对每个元素进行url解码，包括键名
        $pathArray = array_map(function ($val) {
            return urldecode($val);
        }, explode('/', $path));
        //路由匹配
        foreach ($this->rule as $pattern => $rule) {
            $patternArr = explode('/', $pattern);
            $patternNum = count($patternArr);
            //path个数不匹配，匹配下一个
            if (count($pathArray) < $patternNum) continue;
            //参数映射
            $params = [];
            foreach ($patternArr as $i => $r) {
                if (strncmp($r, ':', 1) === 0 && ($key = ltrim($r, ':'))) {
                    if (isset($rule[$key]) && preg_match($rule[$key], $pathArray[$i]) === 0) {
                        continue 2;
                    } else {
                        array_push($params, $key, $pathArray[$i]);
                    }
                } elseif ($pathArray[$i] !== $r) {
                    continue 2;
                }
            }
            //匹配到就不进行下一条匹配
            $pathArray = array_merge(explode('/', $rule[0]), $params, array_splice($pathArray, $patternNum));
            break;
        }

        //获取模块、控制器、方法
        ($m = array_shift($pathArray)) || ($m = $this->module);
        ($c = array_shift($pathArray)) || ($c = $this->controller);
        ($a = array_shift($pathArray)) || ($a = $this->action);

        //存下当前的路由，全部小写，没有转换成驼峰
        $this->router = [strtolower($m), strtolower($c), strtolower($a)];

        //解析多余的参数到GET
        while ($pathArray) {
            $key = array_shift($pathArray);
            $value = array_shift($pathArray);
            $_GET[$key] = $value === null ? '' : $value;
        }

        //转换为驼峰，返回当前请求的模块、控制器、方法
        return [
            $this->str2hump($this->router[0]),
            $this->str2hump($this->router[1], true),
            $this->str2hump($this->router[2])
        ];
    }

    /**
     * 把横线分割的小写字母转换为驼峰
     * @param string $val 要转换的字符串
     * @param boolean $ucfirst 首字母是否大写
     * @return string 返回转换后的字符串
     */
    private function str2hump($val, $ucfirst = false)
    {
        $val = str_replace(' ', '', ucwords(str_replace('-', ' ', $val)));
        return $ucfirst ? $val : lcfirst($val);
    }

    /**
     * 返回此次请求的路由
     * @param boolean $string 是否以字符串的形式返回
     * @return array|string 如user-name/index/index
     */
    public function path($string = false)
    {
        return $string ? implode('/', $this->router) : $this->router;
    }
    
    /**
     * url生成
     * ```php
     * 如果路径post，则生成当前模块、当前控制器下的post方法
     * 如果路径post/index，则生成当前模块，控制器为post下的index方法
     * 如果路径admin/post/index，则生成模块为admin、控制器为post下的index方法
     * ```
     * @param string $path 要生成的相对路径
     * @param array $params 要生成的参数，一个关联数组，如果有路由规则，参数中必须包含rule中的参数才能反解析
     * @param boolean $normal 是否把参数设置成?a=1&b=2
     * @return string 返回生成的url
     */
    public function createUrl($path, $params = [], $normal = false)
    {
        $route = trim($path, "/ ");
        $route = empty($route) ? [] : explode('/', $route);
        switch (count($route)) {
            case 1:
                $route = [$this->router[0], $this->router[1], $route[0]];
                break;
            case 2:
                $route = [$this->router[0], $route[0], $route[1]];
                break;
            case 3:
                break;
            default:
                $route = $this->router;
        }
        $route = implode('/', $route);

        //路由反解析
        foreach ($this->rule as $r => $v) {
            if ($route === $v[0] && false !== preg_match_all('/:([^\/]+)/', $r, $matchs)) {
                foreach ($matchs[1] as $m) {
                    //寻找参数并且匹配参数正则,不匹配就继续寻找下一条规则
                    if (!isset($params[$m]) || isset($v[$m]) && !preg_match($v[$m], $params[$m])) {
                        continue 2;
                    }
                    $r = str_replace(":$m", urlencode($params[$m]), $r);
                }
                //反解析的参数都存在
                $params = array_diff_key($params, array_flip($matchs[1]));
                $route = $r;
                break;
            }
        }

        //过滤一些奇怪的值
        $p1 = $p2 = [];
        foreach ($params as $key => $val) {
            if (in_array(gettype($val), ['string', 'integer', 'double', 'boolean', 'array'])) {
                if ($normal || $val === '' || is_array($val)) {
                    $p1[$key] = $val;
                } else {
                    $p2[$key] = $val;
                }
            }
        }
        $p1 = http_build_query($p1, '', '&');
        $p2 = str_replace(['=', '&'], '/', http_build_query($p2, '', '&'));

        //URL拼接
        $schema = isset($_SERVER['HTTPS']) && (strcasecmp($_SERVER['HTTPS'], 'on') === 0) ? 'https://' : 'http://';
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
        $url = $schema . $host;
        $url .= ($this->pathinfo ? '/index.php/' : '/') . $route . '/';
        $url .= empty($p2) ? '' : "$p2/";
        $url .= empty($p1) ? '' : "?$p1";
        return $url;
    }
}
