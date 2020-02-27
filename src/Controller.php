<?php
/**
 * 工程基类
 */

namespace library;
use library\logic\Query;
use library\logic\Pagination;
use library\logic\Form;
use library\logic\Save;
use library\logic\Delete;
use library\logic\Input;
use zongphp\controller\Controller as ZongController;
use zongphp\exception\exception\HttpResponseException;
use zongphp\request\Request;

class Controller extends ZongController
{
    private $debug = [];
	public $request;

    public function __construct()
    {
        //设置错误级别
        error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));
        //定义常量
        define('API_VERSION', 'v1.0');
        define('APP_PATH', ROOT_PATH .DS . c('controller.app') .DS);
        define('DATA_PATH', ROOT_PATH .DS.'storage'.DS);
        define('CONFIG_PATH', ROOT_PATH .DS.'system'.DS.'config'.DS);

        define('UPLOAD_NAME', c('upload.path'));
        define('THEME_NAME', 'themes');
        define('ROOT_URL', rtrim(dirname($_SERVER["SCRIPT_NAME"]), '\\/').'/');
        define('PUBLIC_URL', ROOT_URL . 'resource/');
        define('UPLOAD_URL', ROOT_URL . UPLOAD_NAME.'/');
        define('THEME_PATH', ROOT_URL .THEME_NAME .'/');
        define('__PUBLIC__', substr(PUBLIC_URL, 0, -1));
        define('__UPLOAD__', substr(UPLOAD_URL, 0, -1));
        define('ACTION_URL', preg_replace('/&.*/i', "", __URL__));//去除&之后所有参数的完整URL
        define('BASE_URL', trim('/' . trim($_SERVER['REQUEST_URI'], '/\\'), '/'));//域名后的URL

        $this->request = new Request();
        $this->request->header('http_referer',$_SERVER['HTTP_REFERER']);
    }

    public function __destruct()
    {
        $this->request = new Request();
    }

    

    public function redirect($url, $code=302)
    {
        header('location:' . $url, true, $code);
        exit;
    }


    /**
     * 返回失败的操作
     * @param mixed $info 消息内容
     * @param array $data 返回数据
     * @param integer $code 返回代码
     */
    public function error($info, $data = [], $code = 0)
    {
        $result = ['code' => $code, 'msg' => $info, 'data' => $data];
        throw new HttpResponseException(die(json($result)->send()));
    }

    /**
     * 返回成功的操作
     * @param mixed $info 消息内容
     * @param array $data 返回数据
     * @param integer $code 返回代码
     */
    public function success($info, $data = [], $code = 1)
    {
        $result = ['code' => $code, 'msg' => $info, 'data' => $data];
		throw new HttpResponseException(die(json($result)->send()));
    }

    

    /**
     * 数据回调处理机制
     * @param string $name 回调方法名称
     * @param mixed $one 回调引用参数1
     * @param mixed $two 回调引用参数2
     * @return boolean
     */
    public function callback($name, &$one = [], &$two = [])
    {
        if (is_callable($name)) {
            return call_user_func($name, $this, $one, $two);
        }
        foreach ([$name, "_".ACTION."{$name}"] as $method) {
            if (method_exists($this, $method)) {
                if (false === $this->$method($one, $two)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * 快捷查询逻辑器
     * @param string $dbQuery
     * @return Query
     */
    protected function _query($dbQuery)
    {
        return (new Query($dbQuery))->init($this);
    }
    /**
     * 快捷分页逻辑器
     * @param string $dbQuery
     * @param boolean $isPage 是否启用分页
     * @param boolean $isDisplay 是否渲染模板
     * @param boolean $total 集合分页记录数
     * @param integer $limit 集合每页记录数
     * @return array
     */
    protected function _page($dbQuery, $isPage = true, $isDisplay = true, $total = false, $limit = 0)
    {
        return (new Pagination($dbQuery, $isPage, $isDisplay, $total, $limit))->init($this);
    }

    /**
     * 快捷表单逻辑器
     * @param string $tpl 模板名称
     * @param string $pkField 指定数据对象主键
     * @param array $where 额外更新条件
     * @param array $data 表单扩展数据
     * @return array|boolean
     */
    protected function _form($dbQuery, $tpl = '', $pkField = '', $where = [], $data = [])
    {
        return (new Form($dbQuery, $tpl, $pkField, $where, $data))->init($this);
    }

    /**
     * 快捷更新逻辑器
     * @param string $dbQuery
     * @param array $data 表单扩展数据
     * @param string $pkField 数据对象主键
     * @param array $where 额外更新条件
     * @return boolean
     */
    protected function _save($dbQuery, $data = [], $pkField = '', $where = [])
    {
        return (new Save($dbQuery, $data, $pkField, $where))->init($this);
    }

    /**
     * 快捷删除逻辑器
     * @param string $dbQuery
     * @param string $pkField 数据对象主键
     * @param array $where 额外更新条件
     * @return boolean|null
     */
    protected function _delete($dbQuery, $pkField = '', $where = [])
    {
        return (new Delete($dbQuery, $pkField, $where))->init($this);
    }

    /**
     * 快捷输入逻辑器
     * @param array|string $data 验证数据
     * @param array $rule 验证规则
     * @param array $info 验证消息
     * @return array
     */
    protected function _input($data, $rule = [], $info = [])
    {
        return (new Input($data, $rule, $info))->init($this);
    }

    
}
