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
                                        all_user_name += '<div class="step_recept"><b class="step">' + value[i].name + '：</b><b class="recept text-red">' + value[i].num + '</b></div>';
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
        distribute_detail: function () {
            Controller.api.bindevent();
            $(document).on('click', '.btn-add', function () {
                var content = $('#table-content table tbody').html();
                var content1 =
                    '<tr> <td>' +
                    '<div class="btn-group bootstrap-select form-control">' +
                    '<button type="button" class="btn dropdown-toggle btn-default" data-toggle="dropdown" role="button" title="没有选中任何项">' +
                    '<span class="filter-option pull-left">没有选中任何项</span>&nbsp;<span class="bs-caret"><span class="caret"></span></span>' +
                    '</button>' +
                    '<div class="dropdown-menu open" role="combobox">' +
                    '<div class="bs-searchbox">' +
                    '<input type="text" class="form-control" autocomplete="off" role="textbox" aria-label="Search">' +
                    '</div>' +
                    '<ul class="dropdown-menu inner" role="listbox" aria-expanded="false">' +
                    '<li data-original-index="0" class="selected">' +
                    '<a tabindex="0" class="" style="" data-tokens="null" role="option" aria-disabled="false" aria-selected="true">' +
                    '<span class="text">' +
                    '</span>' +
                    '<span class="glyphicon glyphicon-ok check-mark">' +
                    '</span>' +
                    '</a>' +
                    '</li>' +
                    '</ul>' +
                    '</div>' +
                    '<select class="form-control selectpicker" data-live-search="1" name="row[supplier_id]" tabindex="-98">' +
                    '<option value="0" selected="selected"></option>' +
                    '</select>' +
                    '</div>' +
                    '\n</td> <td><input id="c-purchase_remark" class="form-control" name="supplier_sku[]" type="text"></td> <td><a href="javascript:;" class="btn btn-danger btn-del" title="删除"><i class="fa fa-trash"></i> 删除</a></td> </tr>';
                $('.caigou table tbody').append(content1);

            })

            $(document).on('click', '.btn-del', function () {
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