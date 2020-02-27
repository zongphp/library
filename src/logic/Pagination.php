<?php
namespace library\logic;

use library\Controller;
/**
 * 列表处理管理器
 * Class Page
 */
class Pagination extends Logic
{
    /**
     * 集合分页记录数
     * @var integer
     */
    protected $total;

    /**
     * 集合每页记录数
     * @var integer
     */
    protected $limit;

    /**
     * 是否启用分页
     * @var boolean
     */
    protected $isPage;

    /**
     * 是否渲染模板
     * @var boolean
     */
    protected $isDisplay;

    /**
     * Page constructor.
     * @param string $dbQuery 数据库查询对象
     * @param boolean $isPage 是否启用分页
     * @param boolean $isDisplay 是否渲染模板
     * @param boolean $total 集合分页记录数
     * @param integer $limit 集合每页记录数
     */
    public function __construct($dbQuery, $isPage = true, $isDisplay = true, $total = false, $limit = 0)
    {
        $this->total = $total;
        $this->limit = $limit;
        $this->isPage = $isPage;
        $this->isDisplay = $isDisplay;
        $this->query = $this->buildQuery($dbQuery);
    }

    /**
     * 逻辑器初始化
     * @param Controller $controller
     * @return array
     */
    public function init(Controller $controller)
    {
        $this->controller = $controller;
        // 列表排序操作
        if (IS_POST){
            return $this->_sort();
        }
        // 未配置 order 规则时自动按 sort 字段排序
        if (!$this->query->getQueryParams('orderBy') && method_exists($this->query, 'getFields')) {
            if (deep_in_array('sort', $this->query->getFields())){       
                $this->query->orderBy('sort','DESC');
            } 
        }
        // 列表分页及结果集处理
        if ($this->isPage) {
            // 分页每页显示记录数
            $limit = intval(Request::get('limit', Cookie::get('page-limit')));
            Cookie::set('page-limit', $limit = $limit >= 10 ? $limit : 20);
            if ($this->limit > 0) $limit = $this->limit;
            $rows = [];
            $query = Request::get();
            unset($query['s']);
            $page = $this->query->paginate($limit);
            $routeInfo = Route::getMatchRoute();
            foreach ([10, 20, 30, 40, 50, 60, 70, 80, 90, 100, 110, 120, 130, 140, 150, 160, 170, 180, 190, 200] as $num) {
                list($query['limit'], $query['page'], $selected) = [$num, '1', $limit === $num ? 'selected' : ''];
                $url = url('admin/Index/index',[],false) . '#' . $routeInfo['route'] . '?' . urldecode(http_build_query($query));
                array_push($rows, "<option data-num='{$num}' value='{$url}' {$selected}>{$num}</option>");
            }
            
            $select = "<select onchange='location.href=this.options[this.selectedIndex].value' data-auto-none>" . join('', $rows) . "</select>";
            
            $html = "<div class='pagination-container nowrap'><span>共 {$page->getTotalRow()} 条记录，每页显示 {$select} 条，共 {$page->getTotalPage()} 页当前显示第 {$page->getSelfPage()} 页。</span>{$page->links()}</div>";
            //p($html);exit;
            View::with('pagehtml', $html);
            $result = ['page' => ['limit' => intval($limit), 'total' => intval($page->getTotalRow()), 'pages' => intval($page->getTotalPage()), 'current' => intval($page->getSelfPage())], 'list' =>$page->toArray()];
        } else {
            $result = ['list' => $this->query->get()];
        }
       
        if (false !== $this->controller->callback('_page_filter', $result['list']) && $this->isDisplay) {
            View::with($result);
            echo view();
        }
        return $result;
    }

    /**
     * 列表排序操作
     */
    protected function _sort()
    {
        switch (strtolower(Request::post('action'))) {
            case 'resort':
                foreach (Request::post() as $key => $value) {
                    if (preg_match('/^_\d{1,}$/', $key) && preg_match('/^\d{1,}$/', $value)) {
                        list($where, $update) = [['id' => trim($key, '_')], ['sort' => $value]];
                        if (false === Db::table($this->query->getTable(),true)->where($where)->update($update)) {
                            return $this->controller->error('排序失败, 请稍候再试！');
                        }
                    }
                }
                return $this->controller->success('排序成功, 正在刷新页面！', '');
            case 'sort':
                $where = Request::post();
                $map = [];
                $sort = intval(Request::post('sort'));
                unset($where['action'], $where['sort']);
                foreach ($where as $key => $value){
                    $map[] = [$key,$value];  
                }
                if (Db::table($this->query->getTable(),true)->where($map)->update(['sort' => $sort]) !== false) {
                    return $this->controller->success('排序参数修改成功！', '');
                }else{
                    return $this->controller->error('排序参数修改失败，请稍候再试！');
                }
                
        }
    }
    
}
