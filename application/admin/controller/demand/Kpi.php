<?php


namespace app\admin\controller\demand;


use app\admin\controller\demand\ItWebTask;
use app\admin\model\Admin;
use app\admin\model\demand\ItWebDemand;
use app\common\controller\Backend;

class Kpi extends Backend {

    public function _initialize() {
        parent::_initialize();+
        $this ->user_model = new Admin();             // 用户
        $this ->demand_model = new ItWebDemand();   // 技术部需求和bug
        $this ->task_model = new ItWebTask();       // 开发任务
    }

    /**
     * 任务量
     */
    public function task_load() {
        if (request() ->isAjax()) {

        }
        return $this ->fetch();
    }

    /**
     * 任务完成量
     */
    public function completion() {
        if (request() ->isAjax()) {

        }
        return $this ->fetch();
    }

    /**
     * 逾期任务
     */
    public function overdue() {
        if (request() ->isAjax()) {

        }
        return $this ->fetch();
    }
}