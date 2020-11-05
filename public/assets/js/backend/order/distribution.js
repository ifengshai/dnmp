define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'bootstrap-table-jump-to', 'template'], function ($, undefined, Backend, Table, Form, Template) {
    function viewTable(table,value){
        //隐藏、显示列
        -1 != $.inArray(value,[7,8]) ? table.bootstrapTable('showColumn','stock_house_num') : table.bootstrapTable('hideColumn','stock_house_num');

        //隐藏、显示搜索及按钮
        $('#stock_house_num').parents('.form-group').hide();
        $('select[name="abnormal"]').parents('.form-group').hide();
        $('.btn-distribution').addClass('hide');
        if(0 == value){
            $('select[name="abnormal"]').parents('.form-group').show();
            $('.btn-batch-export-xls').removeClass('hide');
        }else if(1 == value){
            $('.btn-batch-printed').removeClass('hide');
            $('.btn-tag-printed').removeClass('hide');
        }else if(2 == value){
            $('.btn-product').removeClass('hide');
        }else if(3 == value){
            $('.btn-lens').removeClass('hide');
        }else if(4 == value){
            $('.btn-machining').removeClass('hide');
        }else if(5 == value){
            $('.btn-logo').removeClass('hide');
        }else if(6 == value){
            $('.btn-finish-adopt').removeClass('hide');
            $('.btn-finish-refuse').removeClass('hide');
        }else if(7 == value){
            $('#stock_house_num').parents('.form-group').show();
            // $('.btn-join-complete').removeClass('hide');
        }else if(8 == value){
            $('select[name="abnormal"]').parents('.form-group').show();
            $('#stock_house_num').parents('.form-group').show();
            $('.btn-batch-export-xls').removeClass('hide');
            // $('.btn-abnormal-handle').removeClass('hide');
            // $('.btn-abnormal-sign').removeClass('hide');
        }
    }

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100, 300],
                extend: {
                    index_url: 'order/distribution/index' + location.search + '&label=' + Config.label,
                    table: 'distribution'
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'a.created_at',
                sortOrder: 'desc',
                columns: [
                    [
                        { checkbox: true },
                        {
                            field: '', title: __('序号'), operate: false,
                            formatter: function (value, row, index) {
                                return index+1;
                            }
                        },
                        { field: 'increment_id', title: __('订单号'), operate: 'LIKE' },
                        { field: 'item_order_number', title: __('子单号'), operate: 'LIKE' },
                        { field: 'sku', title: __('SKU'), operate: 'LIKE' },
                        {
                            field: 'total_qty_ordered', title: __('订单副数'), sortable: true, operate: false,
                            formatter: Controller.api.formatter.int_format
                        },
                        /*{ field: 'is_task', title: __('是否有工单'), visible: false, searchList: { 1: '是', 0: '否' }, formatter: Table.api.formatter.status },
                        {
                            field: 'task_info', title: __('工单'), operate: false,
                            formatter: function (value, row) {
                                if (value) {
                                    return '<a href="' + Config.moduleurl + '/saleaftermanage/work_order_list/index?platform_order=' + row.increment_id + '" class="btn btn-primary btn-xs btn-click btn-dialog" data-table-id="table" target="_blank" data-field-index="11" data-row-index="0" data-button-index="3" title="工单"><i class="fa fa-list"></i> 工单</a>'
                                }
                            }
                        },*/
                        {
                            field: 'site', title: __('站点'), addClass: 'selectpicker', data: 'multiple',
                            searchList: {
                                1 : 'Zeelool',
                                2 : 'Voogueme',
                                3 : 'Nihao',
                                4 : 'Meeloog',
                                5 : 'Wesee',
                                8 : 'Amazon',
                                9 : 'Zeelool_es',
                                10 : 'Zeelool_de',
                                11 : 'Zeelool_jp'
                            },
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'order_prescription_type', title: __('加工类型'), addClass: 'selectpicker', data: 'multiple',
                            custom: { 0: 'gray',1: 'green', 2: 'green', 3: 'green', 4: 'green' },
                            searchList: { 0: '待处理',1: '仅镜架', 2: '现货处方镜', 3: '定制处方镜', 4: '其他'},
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'order_type', title: __('订单类型'), addClass: 'selectpicker', data: 'multiple',
                            custom: { 1: 'blue', 2: 'blue', 3: 'blue', 4: 'blue', 5: 'blue' },
                            searchList: { 1: '普通订单', 2: '批发单', 3: '网红单', 4: '补发单', 5: '补差价', 6: '一件代发' },
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'status', title: __('订单状态'), addClass: 'selectpicker', data: 'multiple',
                            searchList: {
                                "processing": __('processing'),
                                "free_processing": __('free_processing'),
                                "paypal_reversed": __('paypal_reversed'),
                                "creditcard_proccessing": __('creditcard_proccessing'),
                                "paypal_canceled_reversal": __('paypal_canceled_reversal'),
                                'complete': __('complete')
                            },
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'distribution_status', title: __('子单号状态'), addClass: 'selectpicker', data: 'multiple',
                            searchList: {
                                1: __('待打印标签'),
                                2: __('待配货'),
                                3: __('待配镜片'),
                                4: __('待加工'),
                                5: __('待印logo'),
                                6: __('待成品质检'),
                                7: __('待合单'),
                                8: __('合单中'),
                                9: __('合单完成')
                            },
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'abnormal', title: __('处理异常'), addClass: 'selectpicker', data: 'multiple',visible:false,
                            searchList: {
                                1: __('缺货'),
                                2: __('商品条码贴错'),
                                3: __('核实处方'),
                                4: __('镜片缺货'),
                                5: __('镜片重做'),
                                6: __('定制片超时'),
                                7: __('不可加工'),
                                8: __('镜架加工报损'),
                                9: __('镜片加工报损'),
                                10: __('logo不可加工'),
                                11: __('镜架印logo报损'),
                                12: __('核实地址'),
                                13: __('物流退件'),
                                14: __('客户退件')
                            }
                        },
                        { field: 'stock_house_num', title: __('库位号'), operate: 'LIKE' },
                        { field: 'a.created_at', title: __('创建时间'), operate: 'RANGE', addclass: 'datetimerange',visible:false  },
                        { field: 'created_at', title: __('创建时间'), operate: false},
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    text: '处理异常',
                                    title: __('处理异常'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'order/distribution/handle_abnormal',
                                    extend: 'data-area = \'["60%","60%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if(8 == Config.label && row.abnormal_house_id > 0){
                                            return true;
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'detail',
                                    text: '创建工单',
                                    title: __('创建工单'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'order/distribution/operational',
                                    extend: 'data-area = \'["60%","50%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if(8 == Config.label && row.abnormal_house_id > 0){
                                            return true;
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'detail',
                                    text: '操作记录',
                                    title: __('操作记录'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'order/distribution/operation_log',
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

            //根据菜单隐藏或显示对应列及按钮
            viewTable(table,Config.label);

            //选项卡切换
            $('.panel-heading a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var value = $(this).data("value");
                var options = table.bootstrapTable('getOptions');
                options.pageNumber = 1;
                var queryParams = options.queryParams;
                options.queryParams = function (params) {
                    params = queryParams(params);
                    params.label = value;
                    return params;
                };
                Config.label = value;

                //根据菜单隐藏或显示对应列及按钮
                viewTable(table,Config.label);

                table.bootstrapTable('refresh', {});
                return false;
            });

            //批量导出xls
            $('.btn-batch-export-xls').click(function () {
                var ids = Table.api.selectedids(table);
                var params = '';
                if (ids.length > 0) {
                    params = 'ids=' + ids;
                } else {
                    var options = table.bootstrapTable('getOptions');
                    var search = options.queryParams({});
                    var filter = search.filter;
                    var op = search.op;
                    params = 'filter=' + filter + '&op=' + op + '&label=' + Config.label;
                }
                window.open(Config.moduleurl + '/order/distribution/batch_export_xls?' + params, '_blank');
            });

            //批量打印
            $('.btn-batch-printed').click(function () {
                var ids = Table.api.selectedids(table);
                window.open(Config.moduleurl + '/order/distribution/batch_print_label/ids/' + ids, '_blank');
            });

            //配货完成、配镜片完成、加工完成、印logo完成
            $('.btn-set-status').click(function () {
                var ids = Table.api.selectedids(table);
                Layer.confirm(
                    __('确定要修改这%s条记录配货状态吗?', ids.length),
                    { icon: 3, title: __('Warning'), shadeClose: true },
                    function (index) {
                        Layer.close(index);
                        Backend.api.ajax({
                            url: Config.moduleurl + '/order/distribution/set_status',
                            data: { id_params: ids, status: $(this).data('status') },
                            type: 'post'
                        }, function (data, ret) {
                            if (data == 'success') {
                                table.bootstrapTable('refresh');
                            }
                        });
                    }
                );
            });

            //成检通过
            $('.btn-finish-adopt').click(function () {
                var ids = Table.api.selectedids(table);
                Layer.confirm(
                    __('确定要通过这%s条子订单吗?', ids.length),
                    { icon: 3, title: __('Warning'), shadeClose: true },
                    function (index) {
                        Layer.close(index);
                        Backend.api.ajax({
                            url: Config.moduleurl + '/order/distribution/set_status',
                            data: { id_params: ids, status: $(this).data('status') },
                            type: 'post'
                        }, function (data, ret) {
                            if (data == 'success') {
                                table.bootstrapTable('refresh');
                            }
                        });
                    }
                );
            });

            //成检拒绝
            $('.btn-finish-refuse').click(function () {
                var ids = Table.api.selectedids(table);
                Layer.confirm(
                    __('确定要拒绝这%s条子订单吗?', ids.length),
                    {
                        icon: 3,
                        title: __('Warning'),
                        shadeClose: true,
                        content: '<div class="layui-form-item">' +
                        '<label class="layui-form-label">拒绝原因</label>' +
                        '<div class="layui-input-block">' +
                        '<select id="reason" lay-filter="range">' +
                        '<option value="1">加工调整</option>' +
                        '<option value="2">镜架报损</option>' +
                        '<option value="3">镜片报损</option>' +
                        '<option value="4">logo调整</option>' +
                        '</select>' +
                        '</div>' +
                        '</div>'
                    },
                    function (index) {
                        Layer.close(index);
                        Backend.api.ajax({
                            url: Config.moduleurl + '/order/distribution/finish_refuse',
                            data: { id_params: ids, reason: $('#reason').val() },
                            type: 'post'
                        }, function (data, ret) {
                            if (data == 'success') {
                                table.bootstrapTable('refresh');
                            }
                        });
                    }
                );
            });
        },
        handle_abnormal: function () {
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
                }
            },
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});