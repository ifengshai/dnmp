define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'bootstrap-table-jump-to'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'warehouse/stock_transfer_order/index' + location.search,
                    add_url: 'warehouse/stock_transfer_order/add?type=1',
                    edit_url: 'warehouse/stock_transfer_order/edit?type=1',
                    import_url: 'warehouse/stock_transfer_order/import',
                    table: 'stock_transfer_order'
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                search: false,
                showToggle: false,
                cardView: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'transfer_order_number', title: __('实体仓调拨单号'), operate: 'like'},

                        {
                            field: 'status',
                            title: __('Status'),
                            custom: {
                                0: 'scarlet',
                                1: 'yellow',
                                2: 'blue',
                                3: 'success',
                                4: 'red',
                                5: 'danger',
                                8: 'green'
                            },
                            searchList: {
                                0: '新建',
                                1: '待审核',
                                2: '待配货',
                                3: '待物流揽收',
                                4: '待收货',
                                5: '待入库',
                                6: '已完成',
                                7: '审核拒绝',
                                8: '已取消'
                            },
                            formatter: Table.api.formatter.status
                        },
                        {field: 'response_person', title: __('调拨负责人'), operate: 'like'},
                        {field: 'create_person', title: __('创建人'), operate: 'like'},
                        {
                            field: 'create_time',
                            title: __('创建时间'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'edit',
                                    text: '编辑',
                                    title: '编辑',
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'warehouse/stock_transfer_order/edit',
                                    extend: 'data-area = \'["100%","100%"]\' target=\'_blank\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        if (row.status == 0){
                                            //返回true时按钮显示,返回false隐藏
                                            return true;
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'cancel',
                                    text: __('取消'),
                                    title: __('取消'),
                                    classname: 'btn btn-xs btn-danger btn-ajax',
                                    url: 'warehouse/stock_transfer_order/cancel?status=8',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    confirm: '确定要取消吗',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                    callback: function (data) {
                                    },
                                    visible: function (row) {
                                        if (row.status == 0) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'detail',
                                    text: '查看详情',
                                    title: '查看详情',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'warehouse/stock_transfer_order/detail',
                                    extend: 'data-area = \'["100%","100%"]\' target=\'_blank\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                }
                                ],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            //审核通过
            $(document).on('click', '.btn-open', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/warehouse/stock_transfer_order/setStatus',
                    data: {ids: ids, status: 2}
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })
            //审核拒绝
            $(document).on('click', '.btn-close', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/warehouse/stock_transfer_order/setStatus',
                    data: {ids: ids, status: 7}
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            });
        },
        add: function () {
            Controller.api.bindevent();
            $(document).on('click', '.btn-add', function () {
                var content = $('#table-content table tbody').html();
                $('.table_list table tbody').append(content);
                Controller.api.bindevent();
            });
            //删除商品数据
            $(document).on('click', '.btn-del', function () {
                $(this).parent().parent().remove();
            });

        },
        edit: function () {
            Controller.api.bindevent();
            $(document).on('click', '.btn-add', function () {
                var content = $('#table-content table tbody').html();
                $('.table_list table tbody').append(content);
                Controller.api.bindevent();
            })

            $(document).on('click', '.btn-status', function () {
                $('.status').val(1);
                $('#status').val(1);
            })

            //删除商品数据
            $(document).on('click', '.btn-del', function () {
                var _this = $(this);
                _this.parent().parent().remove();
            });
        },
        detail: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
                $(document).on('click', '.btn-status', function () {
                    $('.status').val(1);
                    $('#status').val(1);
                });
            }
        }
    };
    return Controller;
});