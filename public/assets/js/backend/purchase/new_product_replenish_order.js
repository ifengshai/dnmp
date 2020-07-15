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
                        {field: 'sku', title: __(' sku')},
                        {field: 'replenishment_num', title: __('Replenishment_num'), operate: false},
                        {
                            field: 'status',
                            title: __('状态'),
                            custom: {1: 'blue', 2: 'danger', 3: 'orange', 4: 'red'},
                            searchList: {1: '待分配', 2: '待处理', 3: '部分处理', 4: '已处理'},
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'is_verify',
                            title: __('审核状态'),
                            custom: {0: 'blue', 1: 'green', 2: 'danger'},
                            searchList: {0: '待审核', 1: '审核通过', 2: '审核拒绝'},
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'create_time',
                            title: __('Create_time'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        },
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate},
                        {
                            field: 'operate', title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: function (value, row, index) {
                                var that = $.extend({}, this);
                                $(table).data("operate-edit", null); // 列表页面隐藏 .编辑operate-edit  - 删除按钮operate-del
                                that.table = table;
                                return Table.api.formatter.operate.call(that, value, row, index);
                            }
                            // formatter: Table.api.formatter.operate
                        }
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
                            url: "purchase/new_product_replenish_order/morePassAudit",
                            data: {ids: ids}
                        }, function (data, ret) {
                            table.bootstrapTable('refresh');
                            Layer.close(index);
                        });
                    }
                );
            });
            //商品审核拒绝
            $(document).on('click', '.btn-refused', function () {
                var ids = Table.api.selectedids(table);
                Layer.confirm(
                    __('确定要审核拒绝吗'),
                    function (index) {
                        Backend.api.ajax({
                            url: "purchase/new_product_replenish_order/moreAuditRefused",
                            data: {ids: ids}
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
        distribution: function () {
            // 初始化表格参数配置
            Table.api.init({
                singleSelect: true,
                showJumpto: true,
                searchFormVisible: true,
                extend: {
                    index_url: 'purchase/new_product_replenish_order/distribution' + location.search,
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
                        // {checkbox: true},
                        {

                            field: '', title: __('序号'), formatter: function (value, row, index) {
                                var options = table.bootstrapTable('getOptions');
                                var pageNumber = options.pageNumber;
                                var pageSize = options.pageSize;

                                //return (pageNumber - 1) * pageSize + 1 + index;
                                return 1 + index;
                            }, operate: false, visible: false
                        },
                        {field: 'id', title: __('Id'), operate: false},
                        {field: 'sku', title: __(' sku'), operate: 'LIKE'},
                        {
                            field: 'status',
                            title: __('状态'),
                            custom: {1: 'blue', 2: 'danger', 3: 'orange', 4: 'red'},
                            searchList: {1: '待分配', 2: '待处理', 3: '部分处理', 4: '已处理'},
                            formatter: Table.api.formatter.status
                        },
                        {field: 'replenishment_num', title: __('Replenishment_num'), operate: false},
                        {
                            field: 'supplier',
                            title: __('分配数量'),
                            operate: false,
                            formatter: function (value, rows) {
                                var all_user_name = '';
                                if (value.length > 0) {
                                    for (i = 0, len = value.length; i < len; i++) {
                                        // all_user_name += '<div class="step_recept"><b class="step">' + value[i].name + '：</b><input id="'+ rows +'" type="text" class="form-control" style="display: inline-block;width: 180px;text-align: center;" value="'+ value[i].num +'"></div>';
                                        all_user_name += '<div class="step_recept"><b class="step">' + value[i].supplier_name + '：</b><b class="recept text-red">' + value[i].distribute_num + '</b></div>';
                                    }
                                }
                                return all_user_name;
                            },
                        },
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate,
                            buttons: [
                                // {
                                //     name: 'passAudit',
                                //     text: '确认分配',
                                //     title: __('确认分配'),
                                //     classname: 'btn btn-xs btn-success btn-confirmdis',
                                //     icon: 'fa fa-pencil',
                                //     visible: function (row) {
                                //         //返回true时按钮显示,返回false隐藏
                                //         if (row.status == 1) {
                                //             return true;
                                //         } else {
                                //             return false;
                                //         }
                                //     }
                                // },
                                {
                                    name: 'distribution_detail',
                                    text: __('确认分配'),
                                    title: __('确认分配'),
                                    icon: 'fa fa-pencil',
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    url: 'purchase/new_product_replenish_order/distribute_detail',
                                    visible: function (row) {
                                            //返回true时按钮显示,返回false隐藏
                                            if (row.status == 1) {
                                                return true;
                                            } else {
                                                return false;
                                            }
                                        },
                                    callback: function (data) {
                                    }
                                },

                            ],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            //需求单确认分配
            // $(document).on('click', '.btn-confirmdis', function () {
            //     var ids = Table.api.selectedids(table);
            //     Layer.confirm(
            //         __('确定分配吗'),
            //         function (index) {
            //             Backend.api.ajax({
            //                 url: "purchase/new_product_replenish_order/distribution_confirm",
            //                 data: {ids: ids,}
            //             }, function (data, ret) {
            //                 table.bootstrapTable('refresh');
            //                 Layer.close(index);
            //             });
            //         }
            //     );
            // });
        },
        handle: function () {
            // 初始化表格参数配置
            Table.api.init({
                singleSelect: true,
                showJumpto: true,
                searchFormVisible: true,
                extend: {
                    index_url: 'purchase/new_product_replenish_order/handle' + location.search,
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
                        {

                            field: '', title: __('序号'), formatter: function (value, row, index) {
                                var options = table.bootstrapTable('getOptions');
                                var pageNumber = options.pageNumber;
                                var pageSize = options.pageSize;

                                //return (pageNumber - 1) * pageSize + 1 + index;
                                return 1 + index;
                            }, operate: false, visible: false
                        },
                        {field: 'id', title: __('Id'), operate: false},
                        {field: 'sku', title: __(' sku'), operate: 'LIKE'},
                        {field: 'num', title: __('总需求数量'), operate: false},
                        {field: 'supplier_name', title: __('供应商'), operate: false},
                        {field: 'distribute_num', title: __('分配数量'), operate: false},
                        {field: 'real_dis_num', title: __('实际采购数量'), operate: false},
                        {field: 'purchase_person', title: __('采购负责人'), operate: false},
                        {
                            field: 'status',
                            title: __('状态'),
                            custom: {1: 'green', 2: 'danger'},
                            searchList: { 1: '未采购', 2: '已采购'},
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate,
                            buttons: [

                                {
                                    name: 'distribution_detail',
                                    text: __('创建采购单'),
                                    title: __('确认分配'),
                                    icon: 'fa fa-pencil',
                                    classname: 'btn btn-xs btn-success btn-createPurchaseOrder',
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.status == 1) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    },
                                    callback: function (data) {
                                    }
                                },

                            ],
                            formatter: Table.api.formatter.operate
                        }                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            //创建采购单
            $(document).on('click', '.btn-createPurchaseOrder', function () {
                var ids = Table.api.selectedids(table);
                Layer.confirm(
                    __('确定要创建采购单吗'),
                    function (index) {
                        Layer.closeAll();
                        var options = {
                            shadeClose: false,
                            shade: [0.3, '#393D49'],
                            area: ['100%', '100%'], //弹出层宽高
                            callback: function (value) {

                            }
                        };
                        Fast.api.open('purchase/purchase_order/add?new_product_ids=' + ids.join(','), '创建采购单', options);
                    }
                );
            });
        },

        distribute_detail: function () {
            Controller.api.bindevent();
            $(document).on('click', '.btn-add', function () {
                var content = $('#table-content table tbody').html();
                $('.caigou table tbody').append(content);
                $('.selectpicker').selectpicker('refresh');
            })

            $(document).on('click', '.btn-del', function () {
                $('.selectpicker').selectpicker('refresh');
                $(this).parent().parent().remove();
            })
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});