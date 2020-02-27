<?php
namespace library\logic;

use library\Controller;

/**
 * 通用删除管理器
 * Class Delete
 */
class Delete extends Logic
{

    /**
     * 表单额外更新条件
     * @var array
     */
    protected $where;

    /**
     * 数据对象主键名称
     * @var string
     */
    protected $pkField;

    /**
     * 数据对象主键值
     * @var string
     */
    protected $pkValue;

    /**
     * Delete constructor.
     * @param string|Query $dbQuery
     * @param string $pkField 数据对象主键
     * @param array $where 额外更新条件
     */
    public function __construct($dbQuery, $pkField = '', $where = [])
    {
        $this->where = $where;
        $this->query = $this->buildQuery($dbQuery);
        $this->pkField = empty($pkField) ? $this->query->getPrimaryKey() : $pkField;
        $this->pkValue = Request::post($this->pkField, null);
    }

    /**
     * 逻辑器初始化
     * @param Controller $controller
     * @return boolean|null
     */
    public function init(Controller $controller)
    {
        $this->controller = $controller;
        // 主键限制处理
        if (!isset($this->where[$this->pkField]) && is_string($this->pkValue)) {
            $this->query->whereIn($this->pkField, explode(',', $this->pkValue));
        }
        // 前置回调处理
        if (false === $this->controller->callback('_delete_filter', $this->query, $this->where)) {
            return null;
        }
        // 执行删除操作
        if (method_exists($this->query, 'getFields') && !empty($this->query->getFields()['is_deleted'])) {
            if(empty($this->where)){
                $result = $this->query->update(['is_deleted' => '1']);
            }else{
                $result = $this->query->where($this->where)->update(['is_deleted' => '1']);
            }
            
        } else {
            if(empty($this->where)){
                $result = $this->query->delete();
            }else{
                $result = $this->query->where($this->where)->delete();
            }
            
        }
        // 结果回调处理
        if (false === $this->controller->callback('_delete_result', $result)) {
            return $result;
        }
        // 回复前端结果
        if ($result !== false) {
            $this->controller->success('数据删除成功！', '');
        } else {
            $this->controller->error('数据删除失败, 请稍候再试！');
        }
    }

}
