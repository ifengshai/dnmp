define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'bootstrap-table-jump-to'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100, 300],
                extend: {
                    index_url: 'order/printlabel/voogueme/index' + location.search,
                    multi_url: 'order/printlabel/voogueme/multi',
                    table: 'sales_flat_order',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'entity_id',
                sortName: 'total_qty_ordered',
                sortOrder: 'asc',
                columns: [
                    [
                        { checkbox: true },
                        { field: 'entity_id', title: __('记录标识'), operate: false },
                        { field: 'increment_id', title: __('订单号'), operate: 'like' },
                        { field: 'status', title: __('状态'), addClass: 'selectpicker', data: 'multiple', operate: 'IN', searchList: { "processing": __('processing'), "free_processing": __('free_processing'), "paypal_reversed": "paypal_reversed", "paypal_canceled_reversal": "paypal_canceled_reversal", "creditcard_proccessing": "creditcard_proccessing", 'complete': 'complete' } },
                        { field: 'base_grand_total', title: __('订单金额'), operate: false, formatter: Controller.api.formatter.float_format },
                        { field: 'base_shipping_amount', title: __('运费'), operate: false, formatter: Controller.api.formatter.float_format },

                        { field: 'total_qty_ordered', title: __('SKU数量'), sortable: true, operate: 'between', formatter: Controller.api.formatter.int_format },
                        { field: 'custom_print_label_new', title: __('打印标签'), custom: { 0: 'danger', 1: 'green' }, searchList: { 1: '是', 0: '否' }, formatter: Table.api.formatter.status },
                        { field: 'custom_is_match_frame_new', title: __('配镜架'), custom: { 0: 'danger', 1: 'green' }, searchList: { 1: '是', 0: '否' }, formatter: Table.api.formatter.status },
                        { field: 'custom_is_match_lens_new', title: __('配镜片'), custom: { 0: 'danger', 1: 'green' }, searchList: { 1: '是', 0: '否' }, formatter: Table.api.formatter.status },
                        { field: 'custom_is_send_factory_new', title: __('加工'), custom: { 0: 'danger', 1: 'green' }, searchList: { 1: '是', 0: '否' }, formatter: Table.api.formatter.status },
                        { field: 'custom_is_delivery_new', title: __('质检'), custom: { 0: 'danger', 1: 'green' }, searchList: { 1: '是', 0: '否' }, formatter: Table.api.formatter.status },
                        { field: 'task_label', title: __('是否有协同任务'), visible: false, searchList: { 1: '是', 0: '否' }, formatter: Table.api.formatter.status },
                        { field: 'is_task', title: __('是否有工单'), visible: false, searchList: { 1: '是', 0: '否' }, formatter: Table.api.formatter.status },
                        {
                            field: 'task_info', title: __('工单'), operate: false, formatter: function (value, row) {
                                if (value) {
                                    return '<a href="' + Config.moduleurl + '/saleaftermanage/work_order_list/index?platform_order=' + row.increment_id + '" class="btn btn-primary btn-xs btn-click btn-dialog" data-table-id="table" target="_blank" data-field-index="11" data-row-index="0" data-button-index="3" title="工单"><i class="fa fa-list"></i> 工单</a>'
                                }
                            }
                        },
                        {
                            field: 'is_task_info', title: __('协同任务'), operate: false, formatter: function (value, row) {
                                if (value) {
                                    return '<a href="' + Config.moduleurl + '/infosynergytaskmanage/info_synergy_task/index?synergy_order_number=' + row.increment_id + '" class="btn btn-primary btn-xs btn-click btn-addtabs" data-table-id="table" data-field-index="11" data-row-index="0" data-button-index="3" title="协同任务"><i class="fa fa-list"></i> 问</a>'
                                }
                            }
                        },
                        { field: 'custom_order_prescription_type', title: __('处方类型'), addClass: 'selectpicker', data: 'multiple', operate: 'IN', custom: { 1: 'green', 2: 'green', 3: 'green', 4: 'green', 5: 'green', 6: 'green', }, searchList: { 1: '仅镜架', 2: '现货处方镜', 3: '定制处方镜', 4: '镜架+现货', 5: '镜架+定制', 6: '现片+定制片', '': '获取中' }, formatter: Table.api.formatter.status },
                        { field: 'order_type', title: __('订单类型'), custom: { 1: 'blue', 2: 'blue', 3: 'blue', 4: 'blue', 5: 'blue',6:'blue' }, searchList: { 1: '普通订单', 2: '批发单', 3: '网红单', 4: '补发单', 5: '补差价',6:'一键代发' }, addClass: 'selectpicker', data: 'multiple', operate: 'IN',formatter: Table.api.formatter.status },
                        { field: 'created_at', title: __('创建时间'), sortable: true, operate: 'RANGE', addclass: 'datetimerange' },
                        { field: 'custom_print_label_created_at_new', title: __('打印标签时间'), operate: 'RANGE', sortable: true, addclass: 'datetimerange' },
                        { field: 'custom_match_frame_created_at_new', title: __('配镜架时间'), operate: 'RANGE', sortable: true, addclass: 'datetimerange' },
                        { field: 'custom_match_lens_created_at_new', title: __('配镜片时间'), operate: 'RANGE', sortable: true, addclass: 'datetimerange' },
                        { field: 'custom_match_factory_created_at_new', title: __('加工时间'), operate: 'RANGE', sortable: true, addclass: 'datetimerange' },
                        { field: 'custom_match_delivery_created_at_new', title: __('提货时间'), operate: 'RANGE', sortable: true, addclass: 'datetimerange' },
                        { field: 'sku', title: __('SKU'), operate: 'like', visible: false },
                        {
                            field: 'category_id', title: __('任务分类'), searchList: function (column) {
                                return Template('categorytpl', {});
                            }, formatter: function (value, row, index) {
                                return '无';
                            }, visible: false
                        },
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [
                                {
                                    name: 'detail',
                                    text: '镜片参数',
                                    title: __('镜片参数'),
                                    classname: 'btn btn-xs  btn-primary  btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'order/printlabel/voogueme/detail',
                                    extend: 'data-area = \'["60%","60%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },
                                {
                                    name: 'detail',
                                    text: '操作记录',
                                    title: __('操作记录'),
                                    classname: 'btn btn-xs  btn-primary  btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'order/printlabel/voogueme/operational',
                                    extend: 'data-area = \'["60%","50%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                }

                            ], formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            // 监听下拉列表改变的事件
            $(document).on('change', 'select[name=c_id]', function () {
                $("input[name='category_id']").val($(this).val());
            });

            // 监听下拉列表改变的事件
            $(document).on('change', 'select[name=pid]', function () {
                $("input[name='p_id']").val($(this).val());
            });


            //批量打印标签    
            $('.btn-batch-printed').click(function () {
                console.log('id_params');
                var ids = Table.api.selectedids(table);
                // console.log(ids);
                var id_params = '';
                $.each(table.bootstrapTable('getSelections'), function (index, row) {
                    // console.log(row); 
                    id_params += row['entity_id'] + ',';
                });
                console.log(id_params);

                // var ids = Table.api.selectedids(table);

                window.open(Config.moduleurl + '/order/printlabel/voogueme/batch_print_label/id_params/' + id_params, '_blank');
            });


            //批量导出xls 
            $('.btn-batch-export-xls').click(function () {
                var ids = Table.api.selectedids(table);
                if (ids.length > 0) {
                    window.open(Config.moduleurl + '/order/printlabel/voogueme/batch_export_xls?id_params=' + ids, '_blank');
                } else {
                    var options = table.bootstrapTable('getOptions');
                    var search = options.queryParams({});
                    var filter = search.filter;
                    var op = search.op;
                    window.open(Config.moduleurl + '/order/printlabel/voogueme/batch_export_xls?filter=' + filter + '&op=' + op, '_blank');
                }

            });



            // //批量导出xls 
            // $('.btn-batch-export-xls').click(function () {
            //     console.log('id_params');
            //     var ids = Table.api.selectedids(table);
            //     // console.log(ids);
            //     var id_params = '';
            //     $.each(table.bootstrapTable('getSelections'), function (index, row) {
            //         // console.log(row); 
            //         id_params += row['entity_id'] + ',';
            //     });
            //     console.log(id_params);

            //     // var ids = Table.api.selectedids(table);

            //     window.open(Config.moduleurl + '/order/printlabel/voogueme/batch_export_xls/id_params/' + id_params, '_blank');
            // });

            //批量标记已打印    
            $('.btn-tag-printed').click(function () {
                var ids = Table.api.selectedids(table);
                Layer.confirm(
                    __('确定要这%s条记录 标记为 【已打印标签】吗?', ids.length),
                    { icon: 3, title: __('Warning'), shadeClose: true },
                    function (index) {
                        Layer.close(index);
                        Backend.api.ajax({
                            url: Config.moduleurl + '/order/printlabel/voogueme/tag_printed',
                            data: { id_params: ids },
                            type: 'post'
                        }, function (data, ret) {
                            if (data == 'success') {
                                table.bootstrapTable('refresh');
                            }
                        });

                    }
                );
            })

            //配镜架 配镜片 加工 质检通过 
            $('.btn-set-status').click(function () {
                var ids = Table.api.selectedids(table);
                var status = $(this).data('status');
                Layer.confirm(
                    __('确定要修改这%s条记录配货状态吗?', ids.length),
                    { icon: 3, title: __('Warning'), shadeClose: true },
                    function (index) {
                        Layer.close(index);
                        Backend.api.ajax({
                            url: Config.moduleurl + '/order/printlabel/voogueme/setOrderStatus',
                            data: { id_params: ids, status: status },
                            type: 'post'
                        }, function (data, ret) {
                            if (data == 'success') {
                                table.bootstrapTable('refresh');
                            }
                        });

                    }
                );
            })
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        operational: function () {
            // 初始化表格参数配置
            Table.api.init({
                commonSearch: false,
                search: false,
                showExport: false,
                showColumns: false,
                showToggle: false,
                pagination: false,
                extend: {
                    index_url: 'order/printlabel/voogueme/operational' + location.search + '&ids=' + Config.ids,
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
                        { field: 'id', title: __('序号'), operate: false },
                        { field: 'content', title: __('操作内容') },
                        { field: 'person', title: __('操作人') },
                        { field: 'createtime', title: __('操作时间') }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        _list: function () {
            // 初始化表格参数配置
            Table.api.init({
                pagination: false,
                commonSearch: false, //是否启用通用搜索
                search: false,
                showExport: false,
                showColumns: false,
                showToggle: false,
                extend: {
                    index_url: 'order/printlabel/voogueme/_list' + location.search,
                    table: 'sales_flat_order',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'entity_id',
                columns: [
                    [
                        { checkbox: true },
                        { field: 'entity_id', title: __('记录标识'), operate: false },
                        { field: 'increment_id', title: __('订单号') },
                        { field: 'status', title: __('状态'), searchList: { "processing": __('processing'), "free_processing": __('free_processing'), "paypal_reversed": "paypal_reversed", "creditcard_proccessing": "creditcard_proccessing", 'complete': 'complete' } },
                        { field: 'base_grand_total', title: __('订单金额'), operate: false, formatter: Controller.api.formatter.float_format },
                        { field: 'base_shipping_amount', title: __('运费'), operate: false, formatter: Controller.api.formatter.float_format },

                        { field: 'total_qty_ordered', title: __('SKU数量'), operate: false, formatter: Controller.api.formatter.int_format },
                        { field: 'custom_print_label_new', title: __('打印标签'), operate: false, custom: { 0: 'danger', 1: 'green' }, searchList: { 1: '是', 0: '否' }, formatter: Table.api.formatter.status },
                        { field: 'custom_is_match_frame_new', title: __('配镜架'), operate: false, custom: { 0: 'danger', 1: 'green' }, searchList: { 1: '是', 0: '否' }, formatter: Table.api.formatter.status },
                        { field: 'custom_is_match_lens_new', title: __('配镜片'), operate: false, custom: { 0: 'danger', 1: 'green' }, searchList: { 1: '是', 0: '否' }, formatter: Table.api.formatter.status },
                        { field: 'custom_is_send_factory_new', title: __('加工'), operate: false, custom: { 0: 'danger', 1: 'green' }, searchList: { 1: '是', 0: '否' }, formatter: Table.api.formatter.status },
                        { field: 'custom_is_delivery_new', title: __('质检'), operate: false, custom: { 0: 'danger', 1: 'green' }, searchList: { 1: '是', 0: '否' }, formatter: Table.api.formatter.status },

                        {
                            field: 'task_info', title: __('工单'), operate: false, formatter: function (value, row) {
                                if (value) {
                                    return '<a href="' + Config.moduleurl + '/saleaftermanage/work_order_list/index?platform_order=' + row.increment_id + '" class="btn btn-primary btn-xs btn-click btn-dialog" data-table-id="table" target="_blank" data-field-index="11" data-row-index="0" data-button-index="3" title="工单"><i class="fa fa-list"></i> 工单</a>'
                                }
                            }
                        },
                        {
                            field: 'is_task_info', title: __('协同任务'), operate: false, formatter: function (value, row) {
                                if (value) {
                                    return '<a href="' + Config.moduleurl + '/infosynergytaskmanage/info_synergy_task/index?synergy_order_number=' + row.increment_id + '" class="btn btn-primary btn-xs btn-click btn-addtabs" data-table-id="table" data-field-index="11" data-row-index="0" data-button-index="3" title="协同任务"><i class="fa fa-list"></i> 问</a>'
                                }
                            }
                        },
                        { field: 'created_at', title: __('创建时间'), operate: 'RANGE', addclass: 'datetimerange' },
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [
                                {
                                    name: 'detail',
                                    text: '镜片参数',
                                    title: __('镜片参数'),
                                    classname: 'btn btn-xs  btn-primary  btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'order/printlabel/voogueme/detail',
                                    extend: 'data-area = \'["60%","60%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },
                                {
                                    name: 'detail',
                                    text: '操作记录',
                                    title: __('操作记录'),
                                    classname: 'btn btn-xs  btn-primary  btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'order/printlabel/voogueme/operational',
                                    extend: 'data-area = \'["60%","50%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                }

                            ], formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            //批量打印标签    
            $('.btn-batch-printed').click(function () {
                var ids = Table.api.selectedids(table);
                var id_params = '';
                $.each(table.bootstrapTable('getSelections'), function (index, row) {
                    id_params += row['entity_id'] + ',';
                });

                window.open(Config.moduleurl + '/order/printlabel/voogueme/batch_print_label/id_params/' + id_params, '_blank');
            });

            //批量导出xls 
            $('.btn-batch-export-xls').click(function () {
                var ids = Table.api.selectedids(table);
                var id_params = '';
                $.each(table.bootstrapTable('getSelections'), function (index, row) {
                    id_params += row['entity_id'] + ',';
                });
                window.open(Config.moduleurl + '/order/printlabel/voogueme/batch_export_xls/id_params/' + id_params, '_blank');
            });

            //批量标记已打印    
            $('.btn-tag-printed').click(function () {
                var ids = Table.api.selectedids(table);
                var data = table.bootstrapTable("getAllSelections");
                var newdata = $.extend(true, [], data); //复制一份数据
                Layer.confirm(
                    __('确定要这%s条记录 标记为 【已打印标签】吗?', ids.length),
                    { icon: 3, title: __('Warning'), shadeClose: true },
                    function (index) {
                        Layer.close(index);
                        Backend.api.ajax({
                            url: Config.moduleurl + '/order/printlabel/voogueme/tag_printed',
                            data: { id_params: ids, label: 'list' },
                            type: 'post'
                        }, function (data) {
                            //移除所有
                            table.bootstrapTable("removeAll");
                            for (var i in newdata) {
                                newdata[i].custom_is_delivery_new = data[i].custom_is_delivery_new;
                                newdata[i].custom_is_match_frame_new = data[i].custom_is_match_frame_new;
                                newdata[i].custom_is_match_lens_new = data[i].custom_is_match_lens_new;
                                newdata[i].custom_is_send_factory_new = data[i].custom_is_send_factory_new;
                                newdata[i].custom_print_label_new = data[i].custom_print_label_new;
                            }
                            //追加
                            table.bootstrapTable("append", newdata);
                            //取消选中
                            table.bootstrapTable('uncheckAll');

                            $('.btn-set-status').addClass('disabled');
                            $('.btn-tag-printed').addClass('disabled');
                            $('.btn-batch-printed').addClass('disabled');
                        });

                    }
                );
            })


            //配镜架 配镜片 加工 质检通过 
            $('.btn-set-status').click(function () {
                var ids = Table.api.selectedids(table);
                var status = $(this).data('status');
                var data = table.bootstrapTable("getData");
                var newdata = $.extend(true, [], data); //复制一份数据
                Layer.confirm(
                    __('确定要修改这%s条记录配货状态吗?', ids.length),
                    { icon: 3, title: __('Warning'), shadeClose: true },
                    function (index) {
                        Layer.close(index);
                        Backend.api.ajax({
                            url: Config.moduleurl + '/order/printlabel/voogueme/setOrderStatus',
                            data: { id_params: ids, status: status, label: 'list' },
                            type: 'post'
                        }, function (row) {

                            //移除所有
                            table.bootstrapTable("removeAll");
                            //取消选中
                            table.bootstrapTable('uncheckAll');
                            for (var i in newdata) {
                                for (var k in row) {
                                    if (row[k].entity_id == newdata[i].entity_id) {
                                        newdata[i].custom_is_delivery_new = row[k].custom_is_delivery_new;
                                        newdata[i].custom_is_match_frame_new = row[k].custom_is_match_frame_new;
                                        newdata[i].custom_is_match_lens_new = row[k].custom_is_match_lens_new;
                                        newdata[i].custom_is_send_factory_new = row[k].custom_is_send_factory_new;
                                        newdata[i].custom_print_label_new = row[k].custom_print_label_new;
                                    }
                                }
                            }
                            //追加
                            table.bootstrapTable("prepend", newdata);

                            //取消选中
                            table.bootstrapTable('uncheckAll');
                            $('.btn-set-status').addClass('disabled');
                            $('.btn-tag-printed').addClass('disabled');
                            $('.btn-batch-printed').addClass('disabled');

                        });

                    }
                );
            })

            //搜索
            $(document).on('input', '#search_val', function (events) {
                if (event.target.value.length == 9) {
                    Backend.api.ajax({
                        url: Config.moduleurl + '/order/printlabel/voogueme/_list',
                        data: { increment_id: event.target.value },
                        type: 'post'
                    }, function (data, ret) {
                        $('#search_val').val('');
                        table.bootstrapTable("prepend", data);
                    });
                }
            })

        },
        api: {

            formatter: {
                device: function (value, row, index) {
                    var str = '';
                    if (value == 1) {
                        str = '电脑';
                    } else if (value == 4) {
                        str = '移动';
                    } else {
                        str = '未知';
                    }
                    return str;
                },
                printLabel: function (value, row, index) {
                    var str = '';
                    if (value == 0) {
                        str = '否';
                    } else if (value == 1) {
                        str = '<span style="font-weight:bold;color:#18bc9c;">是</span>';
                    } else {
                        str = '未知';
                    }
                    return str;
                },
                float_format: function (value, row, index) {
                    return parseFloat(value).toFixed(2);
                },
                int_format: function (value, row, index) {
                    return parseInt(value);
                },
            },
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});