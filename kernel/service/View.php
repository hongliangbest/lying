<?php
/**
 * @author carolkey <su@revoke.cc>
 * @link https://github.com/carolkey/lying
 * @copyright 2018 Lying
 * @license MIT
 */

namespace lying\service;

/**
 * Class View
 * @package lying\service
 */
class View
{
    /**
     * @var array 要渲染输出的参数
     */
    private $params = [];

    /**
     * 输出数据
     * @param string|array $key 参数名,如果为数组,则判断为批量输出数据
     * @param mixed $value 参数值,如果key为数组,此参数可不填写
     */
    public function assign($key, $value = null)
    {
        if (is_array($key)) {
            $this->params = array_merge($this->params, $key);
        } else {
            $this->params[$key] = $value;
        }
    }

    /**
     * 渲染视图文件
     * @param string $view 视图文件名称
     * @param string|bool $layout 布局文件
     * @return string 返回渲染后的HTML
     */
    public function render($view, $layout = false)
    {
        $content = $this->renderFile($this->findViewPath($view), $this->params);
        return empty($layout) ? $content : $this->renderFile(
            $this->findViewPath($layout),
            array_merge($this->params, ['container'=>$content])
        );
    }
    
    /**
     * 渲染视图文件
     * @param string $file 视图文件绝对路径
     * @param array $params 视图文件参数
     * @return string 返回渲染后的页面
     */
    private function renderFile($file, $params)
    {
        ob_start();
        ob_implicit_flush(false);
        empty($params) || extract($params);
        require $file;
        return ob_get_clean();
    }

    /**
     * 查找视图文件的路径
     * @param string $view 视图文件名称
     * @return string 返回视图文件的绝对路径
     * @throws \Exception
     */
    private function findViewPath($view)
    {
        $router = \Lying::$maker->router();
        $file = DIR_MODULE . DS;
        if (strncmp($view, '/', 1) === 0) {
            $file .= $router->module() . DS . 'view' . str_replace('/', DS, rtrim($view, '/')) . '.php';
        } else {
            $view = trim($view, '/');
            $viewArr = explode('/', $view);
            switch (count($viewArr)) {
                case 1:
                    $file .= $router->module() . DS . 'view' . DS . $router->controller() . DS . $view . '.php';
                    break;
                case 2:
                    $file .= $router->module() . DS . 'view' . DS . str_replace('/', DS, $view) . '.php';
                    break;
                case 3:
                    $file .= $viewArr[0] . DS . 'view' . DS . $viewArr[1] . DS . $viewArr[2] . '.php';
                    break;
            }
        }
        if (file_exists($file)) {
            return $file;
        } else {
            throw new \Exception("View file not found: $file", 500);
        }
    }
}
