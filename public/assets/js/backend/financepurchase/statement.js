define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'financepurchase/statement/index',
                    add_url: 'financepurchase/statement/add',
                    edit_url: 'financepurchase/statement/edit',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                searchList: true,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'statement_number', title: __('结算单号'), operate: 'LIKE'},
                        {field: 'supplier_name', title: __('供应商名称'), operate: 'LIKE'},
                        {field: 'period', title: __('供应商账期'), operate: 'LIKE'},
                        {field: 'wait_statement_total', title: __('结算金额'), operate: 'LIKE'},
                        {field: 'purchase_person', title: __('采购负责人'), operate: 'LIKE'},
                        {
                            field: 'status',
                            title: __('状态'),
                            custom: {0: 'success', 1: 'yellow', 2: 'blue', 3: 'danger'},
                            searchList: {0: '新建', 1: '待审核', 2: '审核拒绝', 3: '待对账', 4: '待财务确认', 5: '已取消', 6: '已完成'},
                            formatter: Table.api.formatter.status
                        },
                        // {field: 'create_person', title: __('创建人'), operate: 'LIKE'},
                        // {field: 'create_time', title: __('创建时间'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: __('Detail'),
                                    classname: 'btn btn-xs  btn-primary  btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'financepurchase/statement/detail',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },
                                {
                                    name: 'edit',
                                    text: '编辑',
                                    title: __('Edit'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-pencil',
                                    url: 'financepurchase/statement/edit',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.status == 0) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'pay',
                                    text: '创建付款申请单',
                                    title: __('创建付款申请单'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-pencil',
                                    url: 'financepurchase/purchase_pay/add/label/statement',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.status == 6 && row.wait_statement_total > 0 && row.can_create == 1) {
                                            return true;
                                        } else {
                                            return false;
                                        }
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
                    url: Config.moduleurl + '/financepurchase/statement/setStatus',
                    data: {ids: ids, status: 3}
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })

            //审核拒绝
            $(document).on('click', '.btn-close', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/financepurchase/statement/setStatus',
                    data: {ids: ids, status: 2}
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })
            //审核拒绝
            $(document).on('click', '.btn-duizhang', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/financepurchase/statement/setStatuss',
                    data: {ids: ids, status: 4}
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })

        },
        add: function () {
            Controller.api.bindevent();
            $(document).on('change', '#c-kou_reason', function () {
                var val = $(this).val();

                if (val == 0) {
                    $(this).parent().parent().find('#c-kou_money').attr("readonly", "readonly");
                    $(this).parent().parent().find('#c-kou_money').removeAttr("data-rule");
                } else {
                    $(this).parent().parent().find('#c-kou_money').attr("data-rule", "required");
                    $(this).parent().parent().find('#c-kou_money').attr("data-rule", "integer");
                    $(this).parent().parent().find('#c-kou_money').removeAttr("readonly");
                }
            });
            $(document).on('change', '#c-kou_money', function () {
                var val = $(this).val();
                $(this).parent().parent().find('#c-all_money').val($('#c-all_money').val() - val);
                $(this).attr("readonly", "readonly");
                $('#c-product_total').val($('#c-product_total').val() - val)
            });
        },

        edit: function () {
            Controller.api.bindevent();
            $(document).on('change', '#c-kou_reason', function () {
                var val = $(this).val();

                if (val == 0) {
                    $(this).parent().parent().find('#c-kou_money').attr("readonly", "readonly");
                    $(this).parent().parent().find('#c-kou_money').removeAttr("data-rule");
                } else {
                    $(this).parent().parent().find('#c-kou_money').attr("data-rule", "required");
                    // $(this).parent().parent().find('#c-kou_money').attr("data-rule", "integer");
                    $(this).parent().parent().find('#c-kou_money').removeAttr("readonly");
                }
            });
            $(document).on('change', '#c-kou_money', function () {
                var val = $(this).val();
                $(this).parent().parent().find('#c-all_money').val($('#c-all_money').val() - val);
                $(this).attr("readonly", "readonly");
                $('#c-product_total').val($('#c-product_total').val() - val)
            });
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
                $(document).on('click', '.btn-save1', function () {
                    //提交审核传状态为1
                    $('#status').val(1);
                })
            }
        }
    };
    return Controller;
});