define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'bootstrap-table-jump-to'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'warehouse/warehouse_transfer_order/index' + location.search,
                    table: 'warehouse_transfer_order'
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
                        { checkbox: true },
                        { field: 'id', title: __('Id') },
                        { field: 'transfer_order_number', title: __('调拨单号'), operate: 'like' },
                        {
                            field: 'status', title: __('Status'), custom: { 1: 'success', 2: 'danger' },
                            searchList: { 0: '新建', 1: '待审核' , 2: '已审核', 3: '已拒绝', 4: '已取消', 5: '调拨中', 6: '已完成'},
                            formatter: Table.api.formatter.status
                        },
                        { field: 'create_person', title: __('创建人'), operate: 'like' },
                        { field: 'create_time', title: __('订单创建时间'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime,visible:false},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: __('Detail'),
                                    classname: 'btn btn-xs  btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'warehouse/warehouse_transfer_order/detail',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },
                            ]
                        }
                    ]
                ]
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