define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'itemmanage/item/index' + location.search,
                    add_url: 'itemmanage/item/add',
                    edit_url: 'itemmanage/item/edit',
                    del_url: 'itemmanage/item/del',
                    multi_url: 'itemmanage/item/multi',
                    table: 'item',
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
                        {field: 'name', title: __('Name')},
                        {field: 'category_id', title: __('Category_id')},
                        {field: 'attribute_id', title: __('Attribute_id')},
                        {field: 'stock', title: __('Stock')},
                        {field: 'weight', title: __('Weight'), operate:'BETWEEN'},
                        {field: 'create_person', title: __('Create_person')},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
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
                $(document).on('change','#choose_category_id',function(){
                    var categoryId = $('#choose_category_id').val();
                    Backend.api.ajax({
                        url:'itemmanage/item/ajaxCategoryInfo',
                        data:{categoryId:categoryId}
                    }, function(data, ret){

                        return false;
                    }, function(data, ret){
                        //失败的回调
                        alert(ret.msg);
                        console.log(ret);
                        return false;
                    });
                });
            }
        }
    };
    return Controller;
});