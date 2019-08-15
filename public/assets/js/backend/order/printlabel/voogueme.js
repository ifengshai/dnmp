define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
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
                sortName: 'entity_id',
                columns: [
                    [
                        { checkbox: true },
                        { field: 'entity_id', title: __('记录标识'), operate: false },
                        { field: 'increment_id', title: __('订单号') },
                        { field: 'status', title: __('状态'), searchList: { "processing": __('processing'), "free_processing": __('free_processing'), "creditcard_proccessing": "creditcard_proccessing" } },
                        { field: 'base_grand_total', title: __('订单金额'), operate: false, formatter: Controller.api.formatter.float_format },
                        { field: 'base_shipping_amount', title: __('运费'), operate: false, formatter: Controller.api.formatter.float_format },

                        { field: 'total_qty_ordered', title: __('SKU数量'), operate: false, formatter: Controller.api.formatter.int_format },
                        { field: 'custom_print_label', title: __('打印标签'), operate: false, formatter: Controller.api.formatter.printLabel },
                        { field: 'custom_is_match_frame', title: __('配镜架'), operate: false, formatter: Controller.api.formatter.printLabel },
                        { field: 'custom_is_match_lens', title: __('配镜片'), operate: false, formatter: Controller.api.formatter.printLabel },
                        { field: 'custom_is_send_factory', title: __('加工'), operate: false, formatter: Controller.api.formatter.printLabel },
                        { field: 'custom_is_delivery', title: __('提货'), operate: false, formatter: Controller.api.formatter.printLabel },
                        { field: 'custom_print_label', title: __('是否打印'), searchList: { 1: '是', 0: '否' }, formatter: Table.api.formatter.status, visible: false },
                        { field: 'custom_is_match_frame', title: __('是否配镜架'), searchList: { 1: '是', 0: '否' }, formatter: Table.api.formatter.status, visible: false },
                        { field: 'custom_is_match_lens', title: __('是否配镜片'), searchList: { 1: '是', 0: '否' }, formatter: Table.api.formatter.status, visible: false },
                        { field: 'custom_is_send_factory', title: __('是否加工'), searchList: { 1: '是', 0: '否' }, formatter: Table.api.formatter.status, visible: false },
                        { field: 'custom_is_delivery', title: __('是否提货'), searchList: { 1: '是', 0: '否' }, formatter: Table.api.formatter.status, visible: false },
                        { field: 'created_at', title: __('创建时间'), operate: 'RANGE', addclass: 'datetimerange' },
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [
                                {
                                    name: 'detail',
                                    text: '镜片参数',
                                    title: __('Detail'),
                                    classname: 'btn btn-xs  btn-primary  btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'order/index/detail?label=' + Config.label,
                                    extend: 'data-area = \'["50%","50%"]\'',
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

                window.open('/admin/order/printlabel/voogueme/batch_print_label/id_params/' + id_params, '_blank');
            });

            //批量导出xls 
            $('.btn-batch-export-xls').click(function () {
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

                window.open('/admin/order/printlabel/voogueme/batch_export_xls/id_params/' + id_params, '_blank');
            });

            //批量标记已打印    
            $('.btn-tag-printed').click(function () {
                var ids = Table.api.selectedids(table);
                Layer.confirm(
                    __('确定要这%s条记录 标记为 【已打印标签】吗?', ids.length),
                    { icon: 3, title: __('Warning'), shadeClose: true },
                    function (index) {
                        Layer.close(index);
                        Backend.api.ajax({
                            url: '/admin/order/printlabel/voogueme/tag_printed',
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
                            url: '/admin/order/printlabel/voogueme/setOrderStatus',
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