define(['jquery', 'bootstrap', 'backend', 'table', 'form','upload'], function ($, undefined, Backend, Table, Form,Upload) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                extend: {
                    index_url: 'finance/wait_pay/index' + location.search
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
                        {field: 'id', title: __('序号'),operate:false},
                        {field: 'order_number', title: __('付款申请单号'),},
                        {field: 'supplier_name', title: __('供应商名称'),operate:'like'},
                        {field: 'userid', title: __('审核人'), visible: false},
                        {field: 'create_person', title: __('创建人')},
                        {field: 'create_time', title: __('创建时间'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                    ]
                ]
            });
            $('#add').click(function () {
                var ids = Table.api.selectedids(table);
                if (ids.length > 0) {
                    window.open(Config.moduleurl + '/finance/pay_order/add?ids=' + ids, '_blank');
                } else {
                    layer.msg('请选择付款申请单号');
                    return false;
                }

            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});