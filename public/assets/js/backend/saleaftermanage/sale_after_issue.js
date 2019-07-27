define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'saleaftermanage/sale_after_issue/index' + location.search,
                    add_url: 'saleaftermanage/sale_after_issue/add',
                    edit_url: 'saleaftermanage/sale_after_issue/edit',
                    del_url: 'saleaftermanage/sale_after_issue/del',
                    multi_url: 'saleaftermanage/sale_after_issue/multi',
                    table: 'sale_after_issue',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'pid', title: __('Pid')},
                        {field: 'name', title: __('Name')}, //formatter:Controller.api.formatter.task_status
                        {field: 'level',
                         title: __('Level'),
                         custom: { 1: 'yellow', 2: 'blue', 3: 'danger'},
                         searchList: { 1: '一级问题', 2: '二级问题', 3: '三级问题' },
                        // formatter:Controller.api.formatter.levelList,
                         formatter: Table.api.formatter.status
                        },
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            formatter: {
              levelList: function(val) {
                  var str='';
                  if(val==1){
                      str='一级问题';
                  }else if(val==2){
                      str='二级问题';
                  }else{
                      str='三级问题';
                  }
                  return str;
              }
            }
        }
    };
    return Controller;
});