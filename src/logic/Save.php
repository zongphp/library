<?php
namespace library\logic;

use library\Controller;

/**
 * 数据更新管理器
 * Class Save
 */
class Save extends Logic
{
    /**
     * 表单扩展数据
     * @var array
     */
    protected $data;

    /**
     * 表单额外更新条件
     * @var array
     */
    protected $where;

    /**
     * 数据对象主键名称
     * @var array|string
     */
    protected $pkField;

    /**
     * 数据对象主键值
     * @var string
     */
    protected $pkValue;

    /**
     * Save constructor.
     * @param string|Query $dbQuery
     * @param array $data 表单扩展数据
     * @param string $pkField 数据对象主键
     * @param array $where 额外更新条件
     */
    public function __construct($dbQuery, $data = [], $pkField = '', $where = [])
    {
        $this->where = $where;
        $this->query = $this->buildQuery($dbQuery);
        $this->data = empty($data) ? Request::post() : $data;
        $this->pkField = empty($pkField) ? $this->query->getPrimaryKey() : $pkField;
        $this->pkValue = Request::post($this->pkField, null);
    }

    /**
     * 逻辑器初始化
     * @param Controller $controller
     * @return boolean
     */
    public function init(Controller $controller)
    {
        $this->controller = $controller;
        $db = $this->query;
        // 主键限制处理
        if (!isset($this->where[$this->pkField]) && is_string($this->pkValue)) {
            $db->whereIn($this->pkField, explode(',', $this->pkValue));
            if (isset($this->data)) unset($this->data[$this->pkField]);
        }
        
        // 前置回调处理
        if (false === $this->controller->callback('_save_filter', $this->query, $this->data)) {
            return false;
        }

        // 执行更新操作
        if(empty($this->where)){
            $result = $db->update($this->data) !== false;
        }else{
            $result = $db->where($this->where)->update($this->data) !== false;
        }
        
        
        // 结果回调处理
        if (false === $this->controller->callback('_save_result', $result)) {
            return $result;
        }
        // 回复前端结果
        if ($result !== false) {
            $this->controller->success('数据更新成功!', '');
        } else {
            $this->controller->error('数据更新失败, 请稍候再试!');
        }
    }

}
