<?php
namespace library\logic;

use library\Controller;
/**
 * 搜索条件处理器
 * Class Query
 */
class Query extends Logic
{

    /**
     * Query constructor.
     * @param $dbQuery
     */
    public function __construct($dbQuery)
    {
        $this->query = $this->buildQuery($dbQuery);
    }

    /**
     * Query call.
     * @param string $name 调用方法名称
     * @param array $args 调用参数内容
     * @return $this
     */
    public function __call($name, $args)
    {
        if (is_callable($callable = [$this->query, $name])) {
            call_user_func_array($callable, $args);
        }
        return $this;
    }

    /**
     * 逻辑器初始化
     * @param Controller $controller
     * @return $this
     */
    public function init(Controller $controller)
    {
        $this->controller = $controller;
        return $this;
    }

    /**
     * 获取当前Db操作对象
     * @return \zongphp\db\Query
     */
    public function db()
    {
        return $this->query;
    }

    /**
     * 设置Like查询条件
     * @param string|array $fields 查询字段
     * @param string $input 输入类型 get|post
     * @param string $alias 别名分割符
     * @return $this
     */
    public function like($fields, $input = 'get', $alias = '#')
    {
        $data = Request::$input();
        foreach (is_array($fields) ? $fields : explode(',', $fields) as $field) {
            list($dk, $qk) = [$field, $field];
            if (stripos($field, $alias) !== false) {
                list($dk, $qk) = explode($alias, $field);
            }
            if (isset($data[$qk]) && $data[$qk] !== '') {
                $this->query->where($dk, 'like',"%{$data[$qk]}%");
            }
        }
        return $this;
    }

    /**
     * 设置Equal查询条件
     * @param string|array $fields 查询字段
     * @param string $input 输入类型 get|post
     * @param string $alias 别名分割符
     * @return $this
     */
    public function equal($fields, $input = 'get', $alias = '#')
    {
        $data = Request::$input();
        foreach (is_array($fields) ? $fields : explode(',', $fields) as $field) {
            list($dk, $qk) = [$field, $field];
            if (stripos($field, $alias) !== false) {
                list($dk, $qk) = explode($alias, $field);
            }
            if (isset($data[$qk]) && $data[$qk] !== '') {
                $this->query->where($dk, "{$data[$qk]}");
            }
        }
        return $this;
    }

    /**
     * 设置IN区间查询
     * @param string $fields 查询字段
     * @param string $split 输入分隔符
     * @param string $input 输入类型 get|post
     * @param string $alias 别名分割符
     * @return $this
     */
    public function in($fields, $split = ',', $input = 'request', $alias = '#')
    {
        $data = $this->controller->request->$input();
        foreach (is_array($fields) ? $fields : explode(',', $fields) as $field) {
            list($dk, $qk) = [$field, $field];
            if (stripos($field, $alias) !== false) {
                list($dk, $qk) = explode($alias, $field);
            }
            if (isset($data[$qk]) && $data[$qk] !== '') {
                $this->query->whereIn($dk, explode($split, $data[$qk]));
            }
        }
        return $this;
    }

    /**
     * 设置内容区间查询
     * @param string|array $fields 查询字段
     * @param string $split 输入分隔符
     * @param string $input 输入类型 get|post
     * @param string $alias 别名分割符
     * @return $this
     */
    public function valueBetween($fields, $split = ' ', $input = 'get', $alias = '#')
    {
        return $this->setBetweenWhere($fields, $split, $input, $alias);
    }

    /**
     * 设置日期时间区间查询
     * @param string|array $fields 查询字段
     * @param string $split 输入分隔符
     * @param string $input 输入类型
     * @param string $alias 别名分割符
     * @return $this
     */
    public function dateBetween($fields, $split = ' - ', $input = 'get', $alias = '#')
    {
        return $this->setBetweenWhere($fields, $split, $input, $alias, function ($value, $type) {
            if ($type === 'after') {
                return "{$value} 23:59:59";
            } else {
                return "{$value} 00:00:00";
            }
        });
    }

    /**
     * 设置时间戳区间查询
     * @param string|array $fields 查询字段
     * @param string $split 输入分隔符
     * @param string $input 输入类型
     * @param string $alias 别名分割符
     * @return $this
     */
    public function timeBetween($fields, $split = ' - ', $input = 'get', $alias = '#')
    {
        return $this->setBetweenWhere($fields, $split, $input, $alias, function ($value, $type) {
            if ($type === 'after') {
                return strtotime("{$value} 23:59:59");
            } else {
                return strtotime("{$value} 00:00:00");
            }
        });
    }

    /**
     * 设置区域查询条件
     * @param string|array $fields 查询字段
     * @param string $split 输入分隔符
     * @param string $input 输入类型
     * @param string $alias 别名分割符
     * @param callable $callback
     * @return $this
     */
    private function setBetweenWhere($fields, $split = ' ', $input = 'get', $alias = '#', $callback = null)
    {
        $data = Request::$input();
        foreach (is_array($fields) ? $fields : explode(',', $fields) as $field) {
            list($dk, $qk) = [$field, $field];
            if (stripos($field, $alias) !== false) {
                list($dk, $qk) = explode($alias, $field);
            }
            if (isset($data[$qk]) && $data[$qk] !== '') {
                list($begin, $after) = explode($split, $data[$qk]);
                if (is_callable($callback)) {
                    $after = call_user_func($callback, $after, 'after');
                    $begin = call_user_func($callback, $begin, 'begin');
                }
                $this->query->whereBetween($dk, [$begin, $after]);
            }
        }
        return $this;
    }

    /**
     * 实例化分页管理器
     * @param boolean $isPage 是否启用分页
     * @param boolean $isDisplay 是否渲染模板
     * @param boolean $total 集合分页记录数
     * @param integer $limit 集合每页记录数
     * @return mixed
     */
    public function page($isPage = true, $isDisplay = true, $total = false, $limit = 0)
    {
        return (new Pagination($this->query, $isPage, $isDisplay, $total, $limit))->init($this->controller);
    }
}
