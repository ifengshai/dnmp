define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                extend: {
                    index_url: 'purchase/new_product_replenish_order/index' + location.search,
                    add_url: 'purchase/new_product_replenish_order/add',
                    edit_url: 'purchase/new_product_replenish_order/edit',
                    del_url: 'purchase/new_product_replenish_order/del',
                    multi_url: 'purchase/new_product_replenish_order/multi',
                    table: 'new_product_replenish_order',
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
                        {field: 'sku', title: __('SKU')},
                        {field: 'replenishment_num', title: __('Replenishment_num')},
                        {field: 'status', title: __('状态'), custom: { 1: 'blue', 2: 'danger', 3: 'orange', 4: 'red'}, searchList: { 1: '待分配', 2: '待处理', 3: '部分处理', 4: '已处理' }, formatter: Table.api.formatter.status },
                        {field: 'is_verify', title: __('审核状态'),searchList: { 0: '未通过', 1: '通过'}, formatter: Table.api.formatter.status},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });


            // 为表格绑定事件
            Table.api.bindevent(table);
            //商品审核通过
            $(document).on('click', '.btn-pass', function () {
                var ids = Table.api.selectedids(table);
                Layer.confirm(
                    __('确定要审核通过吗'),
                    function (index) {
                        Backend.api.ajax({
                            url: "itemmanage/item/morePassAudit",
                            data: { ids: ids }
                        }, function (data, ret) {
                            table.bootstrapTable('refresh');
                            Layer.close(index);
                        });
                    }
                );
            });
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
            }
        }
    };
    return Controller;
});