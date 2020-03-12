define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table','form', 'echarts', 'echarts-theme', 'template'], function ($, undefined, Backend, Datatable, Table,Form, Echarts, undefined, Template) {

    var Controller = {
        index: function () {
			Form.api.bindevent($("form[role=form]"));           
        }
    };
    return Controller;
});