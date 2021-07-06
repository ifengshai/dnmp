define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'bootstrap-table-jump-to', 'template', 'upload'], function ($, undefined, Backend, Table, Form, undefined, Template, Upload) {
    function viewTable(table, value) {
        //隐藏、显示列
        -1 != $.inArray(value, [3, 7, 8]) ? table.bootstrapTable('showColumn', 'stock_house_num') : table.bootstrapTable('hideColumn', 'stock_house_num');

        //隐藏、显示搜索及按钮
        $('#stock_house_num').parents('.form-group').hide();
        $('.btn-cancel-abnormal').parents('.form-group').hide();
        $('select[name="abnormal"]').parents('.form-group').hide();
        $('select[name="work_status"]').parents('.form-group').hide();
        $('select[name="work_type"]').parents('.form-group').hide();
        $('select[name="is_work_order"]').parents('.form-group').hide();
        $('select[name="shelf_number"]').parents('.form-group').hide();
        $('select[name="has_work_order"]').parents('.form-group').hide();
        $('#check_time').parents('.form-group').hide();
        $('.btn-distribution').addClass('hide');
        $('input[name="b.payment_time"]').val("");
        table.bootstrapTable('hideColumn', 'payment_time');
        table.bootstrapTable('showColumn', 'created_at');
        $('input[name="a.created_at"]').parents('.form-group').show();
        $('input[name="b.payment_time"]').parents('.form-group').hide();
        if (0 == value) {
            $('select[name="abnormal"]').parents('.form-group').show();
            $('#check_time').parents('.form-group').show();
            table.bootstrapTable('hideColumn', 'oprate_created_at');
            table.bootstrapTable('showColumn', 'payment_time');
            $('input[name="b.payment_time"]').parents('.form-group').show();
            $('input[name="oprate_created_at"]').parents('.form-group').hide();
            $('select[name="has_work_order"]').parents('.form-group').show();
            $('.btn-batch-export-xls').removeClass('hide');
            $('.btn-batch-printed').removeClass('hide');
            $('.btn-tag-printed').removeClass('hide');
            $('.btn-batch-export-account').removeClass('hide');
        } else if (1 == value) {
            $('#check_time').hide();
            $('.btn-batch-printed').removeClass('hide');
            $('.btn-tag-printed').removeClass('hide');
            $('.btn-batch-export-xlsz').removeClass('hide');
            $('select[name="shelf_number"]').parents('.form-group').show();
        } else if (2 == value) {
            $('.btn-batch-printed').removeClass('hide');
            $('.btn-product').removeClass('hide');
            table.bootstrapTable('showColumn', 'oprate_created_at');
        } else if (3 == value) {
            $('#stock_house_num').parents('.form-group').show();
            $('.btn-batch-printed').removeClass('hide');
            $('.btn-sign-abnormals').removeClass('hide');
            $('.btn-lens').removeClass('hide');
            table.bootstrapTable('showColumn', 'oprate_created_at');
        } else if (4 == value) {
            $('.btn-batch-printed').removeClass('hide');
            $('.btn-machining').removeClass('hide');
            table.bootstrapTable('showColumn', 'oprate_created_at');
        } else if (5 == value) {
            $('.btn-batch-printed').removeClass('hide');
            $('.btn-logo').removeClass('hide');
            table.bootstrapTable('showColumn', 'oprate_created_at');

        } else if (6 == value) {
            $('.btn-finish-adopt').removeClass('hide');
            $('.btn-finish-refuse').removeClass('hide');
            table.bootstrapTable('showColumn', 'oprate_created_at');
        } else if (7 == value) {
            $('#stock_house_num').parents('.form-group').show();
            table.bootstrapTable('showColumn', 'oprate_created_at');
            // $('.btn-join-complete').removeClass('hide');
        } else if (8 == value) {
            table.bootstrapTable('hideColumn', 'created_at');
            table.bootstrapTable('showColumn', 'payment_time');
            $('input[name="b.payment_time"]').parents('.form-group').show();
            // $('input[name="a.created_at"]').parents('.form-group').hide();
            $('select[name="abnormal"]').parents('.form-group').show();
            $('select[name="work_status"]').parents('.form-group').show();
            $('select[name="work_type"]').parents('.form-group').show();
            $('select[name="is_work_order"]').parents('.form-group').show();
            $('#stock_house_num').parents('.form-group').show();
            $('.btn-creat-work-order').removeClass('hide');
            $('.btn-cancel-abnormal').removeClass('hide');
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
                    index_url: 'order/distribution/index' + location.search + (location.search ? '&label=' + Config.label : '?label=' + Config.label),

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
                    [{
                            checkbox: true
                        },
                        {
                            field: '',
                            title: __('序号'),
                            operate: false,
                            formatter: function (value, row, index) {
                                return index + 1;
                            }
                        },
                        {
                            field: 'increment_id',
                            title: __('订单号'),
                            operate: 'LIKE'
                        },
                        {
                            field: 'item_order_number',
                            title: __('子单号'),
                            operate: 'LIKE'
                        },
                        {
                            field: 'wave_order_id',
                            title: __('波次单id')
                        },
                        {
                            field: 'sku',
                            title: __('SKU'),
                            operate: 'LIKE'
                        },
                        {
                            field: 'total_qty_ordered',
                            title: __('订单副数'),
                            sortable: true,
                            operate: false,
                            formatter: Controller.api.formatter.int_format
                        },
                        // { field: 'is_task', title: __('是否有工单'), visible: false, searchList: { 1: '是', 0: '否' }, formatter: Table.api.formatter.status },
                        {
                            field: 'task_info',
                            title: __('工单'),
                            operate: false,
                            formatter: function (value, row) {
                                if (value) {
                                    return '<a href="' + Config.moduleurl + '/saleaftermanage/work_order_list/index?platform_order=' + row.increment_id + '" class="btn btn-primary btn-xs btn-click btn-dialog" data-table-id="table" target="_blank" data-field-index="11" data-row-index="0" data-button-index="3" title="工单"><i class="fa fa-list"></i> 工单</a>'
                                }
                            }
                        },
                        {
                            field: 'site',
                            title: __('站点'),
                            addClass: 'selectpicker',
                            data: 'multiple',
                            searchList: {
                                1: 'Zeelool',
                                2: 'Voogueme',
                                3: 'Meeloog',
                                4: 'Vicmoo',
                                5: 'Wesee',
                                8: 'Amazon',
                                9: 'Zeelool_es',
                                10: 'Zeelool_de',
                                11: 'Zeelool_jp',
                                12: 'Voogueme_acc',
                                13: 'Zeelool_cn',
                                14: 'Alibaba',
                                15: 'Zeelool_fr',
                            },
                            operate: 'IN',
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'order_prescription_type',
                            title: __('加工类型'),
                            addClass: 'selectpicker',
                            data: 'multiple',
                            operate: 'IN',
                            custom: {
                                0: 'gray',
                                1: 'green',
                                2: 'green',
                                3: 'green',
                                4: 'green'
                            },
                            searchList: {
                                0: '待处理',
                                1: '仅镜架',
                                2: '现货处方镜',
                                3: '定制处方镜',
                                4: '其他'
                            },
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'stock_id',
                            title: __('仓库'),
                            addClass: 'selectpicker',
                            searchList: {
                                1: '郑州仓',
                                2: '丹阳仓'
                            },
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'order_type',
                            title: __('订单类型'),
                            addClass: 'selectpicker',
                            data: 'multiple',
                            custom: {
                                1: 'blue',
                                2: 'blue',
                                3: 'blue',
                                4: 'blue',
                                5: 'blue'
                            },
                            searchList: {
                                1: '普通订单',
                                2: '批发单',
                                3: '网红单',
                                4: '补发单',
                                5: '补差价',
                                6: '一件代发',
                                7: '手动补单',
                                10: '货到付款',
                                11: '普通订单'
                            },
                            operate: 'IN',
                            field: 'order_type', title: __('订单类型'), addClass: 'selectpicker', data: 'multiple',
                            custom: { 1: 'blue', 2: 'blue', 3: 'blue', 4: 'blue', 5: 'blue' },
                            searchList: { 1: '普通订单', 2: '批发单', 3: '网红单', 4: '补发单', 5: '补差价', 6: '一件代发', 7: '手动补单', 10: '货到付款', 11: '普通订单', 41: '免加工单' }, operate: 'IN',
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'is_prescription_abnormal',
                            title: __('处方异常'),
                            addClass: 'selectpicker',
                            data: 'multiple',
                            searchList: {
                                0: __('无异常'),
                                1: __('异常')
                            },
                            custom: {
                                0: 'green',
                                1: 'danger',
                            },
                            operate: 'IN',
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'status',
                            title: __('订单状态'),
                            addClass: 'selectpicker',
                            data: 'multiple',
                            searchList: {
                                "canceled": __('canceled'),
                                "closed": __('closed'),
                                "complete": __('complete'),
                                "creditcard_failed": __('creditcard_failed'),
                                "creditcard_pending": __('creditcard_pending'),
                                "delivered": __('delivered'),
                                "fraud": __('fraud'),
                                "free_processing": __('free_processing'),
                                "holded": __('holded'),
                                "payment_review": __('payment_review'),
                                "paypal_canceled_reversal": __('paypal_canceled_reversal'),
                                "paypal_reversed": __('paypal_reversed'),
                                "pending": __('pending'),
                                "processing": __('processing'),
                                "unpaid": __('unpaid')
                            },
                            operate: 'IN',
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'distribution_status',
                            title: __('子单号状态'),
                            addClass: 'selectpicker',
                            data: 'multiple',
                            searchList: {
                                0: __('取消'),
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
                            operate: 'IN',
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'work_status',
                            title: __('工单状态'),
                            addClass: 'selectpicker',
                            data: 'multiple',
                            searchList: {
                                1: __('新建'),
                                2: __('待审核'),
                                3: __('待处理'),
                                5: __('部分处理')
                            },
                            operate: 'IN',
                            visible: false
                        },
                        {
                            field: 'work_type',
                            title: __('工单类型'),
                            searchList: {
                                1: __('客服工单'),
                                2: __('仓库工单')
                            },
                            visible: false
                        },
                        {
                            field: 'is_work_order',
                            title: __('工单/异常'),
                            searchList: {
                                1: __('工单'),
                                2: __('异常')
                            },
                            visible: false
                        },
                        {
                            field: 'has_work_order',
                            title: __('是否有工单'),
                            searchList: {
                                1: __('是'),
                            },
                            visible: false
                        },
                        {
                            field: 'abnormal',
                            title: __('处理异常'),
                            addClass: 'selectpicker',
                            data: 'multiple',
                            visible: false,
                            operate: 'IN',
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

                        {
                            field: 'stock_house_num',
                            title: __('库位号'),
                            operate: 'LIKE'
                        },
                        {
                            field: 'shelf_number',
                            title: __('货架号'),
                            visible: false,
                            addClass: 'selectpicker',
                            data: 'multiple',
                            operate: 'IN',
                            searchList: {
                                'A': 'A',
                                'B': 'B',
                                'C': 'C',
                                'D': 'D',
                                'E': 'E',
                                'F': 'F',
                                'G': 'G',
                                'H': 'H',
                                'I': 'I',
                                'J': 'J',
                                'K': 'K',
                                'L': 'L',
                                'M': 'M',
                                'N': 'N',
                                'O': 'O',
                                'P': 'P',
                                'Q': 'Q',
                                'R': 'R',
                                'S': 'S',
                                'T': 'T',
                                'U': 'U',
                                'V': 'V',
                                'W': 'W',
                                'X': 'X',
                                'Y': 'Y',
                                'Z': 'Z'
                            }
                        },
                        {
                            field: 'created_at',
                            title: __('订单创建时间'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            sortable: true,
                        },
                        {
                            field: 'b.payment_time',
                            title: __('支付时间'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            visible: false
                        },
                        {
                            field: 'check_time',
                            title: __('审单通过时间'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            visible: false
                        },
                        {
                            field: 'oprate_created_at',
                            title: __('操作时间'),
                            operate: false
                        },
                        {
                            field: 'payment_time',
                            title: __('支付时间'),
                            operate: false,
                            formatter: Table.api.formatter.datetime,
                        },
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [{
                                    name: 'detail',
                                    text: '处理异常',
                                    title: __('处理异常'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'order/distribution/handle_abnormal',
                                    extend: 'data-area = \'["60%","60%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {
                                            title: "回传数据"
                                        });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.handle_abnormal > 0) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'detail',
                                    text: '镜片参数',
                                    title: __('镜片参数'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'order/distribution/detail',
                                    extend: 'data-area = \'["60%","50%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {
                                            title: "回传数据"
                                        });
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
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'order/distribution/operation_log',
                                    extend: 'data-area = \'["60%","50%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {
                                            title: "回传数据"
                                        });
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

            //根据菜单隐藏或显示对应列及按钮
            viewTable(table, Config.label);

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
                viewTable(table, Config.label);

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

            //批量导出xls
            $('.btn-batch-export-xlsz').click(function () {
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
                window.open(Config.moduleurl + '/order/distribution/printing_batch_export_xls?' + params, '_blank');
            });

            //批量导出xls
            $('.btn-batch-export-account').click(function () {
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
                window.open(Config.moduleurl + '/order/distribution/batch_export_xls_account?' + params, '_blank');
            });

            //批量打印
            $('.btn-batch-printed').click(function () {
                var ids = Table.api.selectedids(table);
                window.open(Config.moduleurl + '/order/distribution/batch_print_label/ids/' + ids, '_blank');
            });

            //批量标记已打印
            $('.btn-tag-printed').click(function () {
                var ids = Table.api.selectedids(table);
                Layer.confirm(
                    __('确定要标记这%s条记录已打印吗?', ids.length), {
                        icon: 3,
                        title: __('Warning'),
                        shadeClose: true
                    },
                    function (index) {
                        Layer.close(index);
                        Backend.api.ajax({
                            url: Config.moduleurl + '/order/distribution/tag_printed',
                            data: {
                                id_params: ids
                            },
                            type: 'post'
                        }, function (data, ret) {
                            if (data == 'success') {
                                table.bootstrapTable('refresh');
                            }
                        });
                    }
                );
            });

            //配货完成、配镜片完成、加工完成、印logo完成
            $('.btn-set-status').click(function () {
                var ids = Table.api.selectedids(table);
                var status = $(this).data('status');
                var content = 6 == status ? '确定要通过这%s条子订单吗?' : '确定要修改这%s条记录配货状态吗?';
                Layer.confirm(
                    __(content, ids.length), {
                        icon: 3,
                        title: __('Warning'),
                        shadeClose: true
                    },
                    function (index) {
                        Layer.close(index);
                        Backend.api.ajax({
                            url: Config.moduleurl + '/order/distribution/set_status',
                            data: {
                                id_params: ids,
                                status: status
                            },
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
                    __('确定要拒绝这%s条子订单吗?', ids.length), {
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
                            data: {
                                id_params: ids,
                                reason: $('#reason').val()
                            },
                            type: 'post'
                        }, function (data, ret) {
                            if (data == 'success') {
                                table.bootstrapTable('refresh');
                            }
                        });
                    }
                );
            });

            //创建工单，ids为子单ID
            $('.btn-creat-work-order').click(function () {
                var ids = Table.api.selectedids(table);
                Layer.confirm(
                    __('确定要%s创建工单吗?', ids.length ? '为这' + ids.length + '条记录' : ''), {
                        icon: 3,
                        title: __('Warning'),
                        shadeClose: true
                    },
                    function (index) {
                        Layer.close(index);
                        Backend.api.ajax({
                            url: Config.moduleurl + '/order/distribution/add',
                            data: {
                                id_params: ids
                            },
                            type: 'post'
                        }, function (data, ret) {
                            if (data.url) {
                                //跳转添加工单页面
                                Fast.api.open(data.url, __('创建工单'), {
                                    area: ["100%", "100%"],
                                    end: function () {
                                        table.bootstrapTable('refresh');
                                    }
                                });
                            }
                        });
                    }
                );
            });

            //批量标记异常
            $('.btn-sign-abnormals').click(function () {
                var ids = Table.api.selectedids(table);
                Backend.api.open(Config.moduleurl + '/order/distribution/sign_abnormals/ids/' + ids, __('批量标记异常'), {
                    area: ['50%', '50%']
                });
            });

            //取消异常
            $('.btn-cancel-abnormal').click(function () {
                var ids = Table.api.selectedids(table);
                Layer.confirm(
                    __('确定要为这%s条子订单取消异常么?', ids.length), {
                        icon: 3,
                        title: __('Warning'),
                        shadeClose: true
                    },
                    function (index) {
                        Layer.close(index);
                        Backend.api.ajax({
                            url: Config.moduleurl + '/order/distribution/cancel_abnormal',
                            data: {
                                ids: ids
                            },
                            type: 'post'
                        }, function (data, ret) {
                            if (data == 'success') {
                                table.bootstrapTable('refresh');
                            }
                        });
                    }
                );
            });

            // 导入按钮事件
            Upload.api.plupload($('.btn-import'), function (data, row) {
                Fast.api.ajax({
                    url: 'order/distribution/importOrder',
                    data: {file: data.url},
                }, function (data, ret) {
                    layer.alert(ret.msg, function () {
                        layer.closeAll();
                        table.bootstrapTable('refresh');
                    });

                });
            });


        },
        wave_order_list: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100, 300],
                extend: {
                    index_url: 'order/distribution/wave_order_list' + location.search,
                    table: 'distribution'
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                sortOrder: 'desc',
                columns: [
                    [{
                            checkbox: true
                        },
                        {
                            field: 'id',
                            title: __('Id')
                        },
                        {
                            field: 'item_order_number',
                            title: __('子单号'),
                            operate: 'like',
                            visible: false
                        },
                        {
                            field: 'wave_time_type',
                            title: __('波次'),
                            addClass: 'selectpicker',
                            custom: {
                                1: 'danger',
                                2: 'danger',
                                3: 'danger',
                                4: 'danger',
                                5: 'danger',
                                6: 'danger',
                                7: 'danger',
                                8: 'danger'
                            },
                            searchList: {
                                1: '00:00-2:59:59',
                                2: '3:00-5:59:59',
                                3: '6:00-8:59:59',
                                4: '9:00-11:59:59',
                                5: '12:00-14:59:59',
                                6: '15:00-17:59:59',
                                7: '18:00-20:59:59',
                                8: '21:00-23:59:59',
                                9: '加诺补发加急订单'
                            },
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'type',
                            title: __('类型'),
                            addClass: 'selectpicker',
                            searchList: {
                                1: '品牌独立站',
                                2: '第三方平台店铺'
                            },
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'stock_id',
                            title: __('仓库'),
                            addClass: 'selectpicker',
                            searchList: {
                                1: '郑州仓',
                                2: '丹阳仓'
                            },
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'status',
                            title: __('打印状态'),
                            addClass: 'selectpicker',
                            custom: {
                                0: 'gray',
                                1: 'green',
                                2: 'green'
                            },
                            searchList: {
                                0: '未打印',
                                1: '部分打印',
                                2: '已打印'
                            },
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'createtime',
                            title: __('创建时间'),
                            operate: 'RANGE',
                            addclass: 'datetimerange'
                        },
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: __('详情'),
                                    classname: 'btn btn-xs btn-primary btn-addtabs',
                                    icon: 'fa fa-list',
                                    url: 'order/distribution/wave_order_detail',
                                    // extend: 'data-area = \'["60%","50%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {
                                            title: "回传数据"
                                        });
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

            //批量导出xls
            $('.btn-batch-export-xlsz').click(function () {
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
                window.open(Config.moduleurl + '/order/distribution/printing_batch_export_xls?' + params, '_blank');
            });

            //批量打印
            $('.btn-batch-printed').click(function () {
                var ids = Table.api.selectedids(table);
                window.open(Config.moduleurl + '/order/distribution/batch_print_label/ids/' + ids, '_blank');
            });

            //批量标记已打印
            $('.btn-tag-printed').click(function () {
                var ids = Table.api.selectedids(table);
                Layer.confirm(
                    __('确定要标记这%s条记录已打印吗?', ids.length), {
                        icon: 3,
                        title: __('Warning'),
                        shadeClose: true
                    },
                    function (index) {
                        Layer.close(index);
                        Backend.api.ajax({
                            url: Config.moduleurl + '/order/distribution/tag_printed',
                            data: {
                                id_params: ids
                            },
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
        wave_order_detail: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageSize: 10,
                pageList: [10, 25, 50, 100, 300, 500],
                extend: {
                    index_url: 'order/distribution/wave_order_detail' + location.search + (location.search ? '&ids=' + Config.ids : '?ids=' + Config.ids),
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'picking_sort',
                sortOrder: 'asc',
                columns: [
                    [{
                            checkbox: true
                        },
                        {
                            field: '',
                            title: __('序号'),
                            operate: false,
                            formatter: function (value, row, index) {
                                return index + 1;
                            }
                        },
                        {
                            field: 'picking_sort',
                            title: __('拣货顺序'),
                            operate: false
                        },
                        {
                            field: 'increment_id',
                            title: __('订单号'),
                            operate: 'like'
                        },
                        {
                            field: 'item_order_number',
                            title: __('子单号'),
                            operate: 'like'
                        },
                        {
                            field: 'stock_id',
                            title: __('仓库'),
                            addClass: 'selectpicker',
                            searchList: {
                                1: '郑州仓',
                                2: '丹阳仓'
                            },
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'sku',
                            title: __('SKU'),
                            operate: 'like'
                        },
                        {
                            field: 'total_qty_ordered',
                            title: __('订单副数'),
                            sortable: true,
                            operate: false,
                            formatter: Controller.api.formatter.int_format
                        },
                        // { field: 'is_task', title: __('是否有工单'), visible: false, searchList: { 1: '是', 0: '否' }, formatter: Table.api.formatter.status },
                        {
                            field: 'task_info',
                            title: __('工单'),
                            operate: false,
                            formatter: function (value, row) {
                                if (value) {
                                    return '<a href="' + Config.moduleurl + '/saleaftermanage/work_order_list/index?platform_order=' + row.increment_id + '" class="btn btn-primary btn-xs btn-click btn-dialog" data-table-id="table" target="_blank" data-field-index="11" data-row-index="0" data-button-index="3" title="工单"><i class="fa fa-list"></i> 工单</a>'
                                }
                            }
                        },
                        {
                            field: 'site',
                            title: __('站点'),
                            addClass: 'selectpicker',
                            data: 'multiple',
                            searchList: {
                                1: 'Zeelool',
                                2: 'Voogueme',
                                3: 'Meeloog',
                                4: 'Vicmoo',
                                5: 'Wesee',
                                8: 'Amazon',
                                9: 'Zeelool_es',
                                10: 'Zeelool_de',
                                11: 'Zeelool_jp',
                                12: 'Voogueme_acc',
                                13: 'Zeelool_cn',
                                14: 'Alibaba',
                                15: 'Zeelool_fr',
                            },
                            operate: 'IN',
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'order_prescription_type',
                            title: __('加工类型'),
                            addClass: 'selectpicker',
                            data: 'multiple',
                            operate: 'IN',
                            custom: {
                                0: 'gray',
                                1: 'green',
                                2: 'green',
                                3: 'green',
                                4: 'green'
                            },
                            searchList: {
                                0: '待处理',
                                1: '仅镜架',
                                2: '现货处方镜',
                                3: '定制处方镜',
                                4: '其他'
                            },
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'order_type',
                            title: __('订单类型'),
                            addClass: 'selectpicker',
                            data: 'multiple',
                            custom: {
                                1: 'blue',
                                2: 'blue',
                                3: 'blue',
                                4: 'blue',
                                5: 'blue'
                            },
                            searchList: {
                                1: '普通订单',
                                2: '批发单',
                                3: '网红单',
                                4: '补发单',
                                5: '补差价',
                                6: '一件代发',
                                7: '手动补单',
                                10: '货到付款',
                                11: '普通订单'
                            },
                            operate: 'IN',
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'status',
                            title: __('订单状态'),
                            addClass: 'selectpicker',
                            data: 'multiple',
                            searchList: {
                                "canceled": __('canceled'),
                                "closed": __('closed'),
                                "complete": __('complete'),
                                "creditcard_failed": __('creditcard_failed'),
                                "creditcard_pending": __('creditcard_pending'),
                                "delivered": __('delivered'),
                                "fraud": __('fraud'),
                                "free_processing": __('free_processing'),
                                "holded": __('holded'),
                                "payment_review": __('payment_review'),
                                "paypal_canceled_reversal": __('paypal_canceled_reversal'),
                                "paypal_reversed": __('paypal_reversed'),
                                "pending": __('pending'),
                                "processing": __('processing'),
                                "unpaid": __('unpaid')
                            },
                            operate: 'IN',
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'distribution_status',
                            title: __('子单号状态'),
                            addClass: 'selectpicker',
                            data: 'multiple',
                            searchList: {
                                0: __('取消'),
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
                            operate: 'IN',
                            formatter: Table.api.formatter.status
                        },



                        {
                            field: 'a.created_at',
                            title: __('创建时间'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            visible: false
                        },
                        {
                            field: 'created_at',
                            title: __('创建时间'),
                            operate: false
                        },

                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [{
                                    name: 'detail',
                                    text: '镜片参数',
                                    title: __('镜片参数'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'order/distribution/detail',
                                    extend: 'data-area = \'["60%","50%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {
                                            title: "回传数据"
                                        });
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
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'order/distribution/operation_log',
                                    extend: 'data-area = \'["60%","50%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {
                                            title: "回传数据"
                                        });
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

            //批量打印
            $('.btn-batch-printed').click(function () {
                var ids = Table.api.selectedids(table);
                window.open(Config.moduleurl + '/order/distribution/batch_print_label/ids/' + ids, '_blank');
            });

            //批量标记已打印
            $('.btn-tag-printed').click(function () {
                var ids = Table.api.selectedids(table);
                Layer.confirm(
                    __('确定要标记这%s条记录已打印吗?', ids.length), {
                        icon: 3,
                        title: __('Warning'),
                        shadeClose: true
                    },
                    function (index) {
                        Layer.close(index);
                        Backend.api.ajax({
                            url: Config.moduleurl + '/order/distribution/tag_printed',
                            data: {
                                id_params: ids
                            },
                            type: 'post'
                        }, function (data, ret) {
                            if (data == 'success') {
                                table.bootstrapTable('refresh');
                            }
                        });
                    }
                );
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
                window.open(Config.moduleurl + '/order/distribution/batch_export_xls?' + params + '&wave_order_id=' + Config.ids, '_blank');
            });
        },
        handle_abnormal: function () {
            Controller.api.bindevent();
        },
        sign_abnormals: function () {
            Controller.api.bindevent();
            $('#abnormal').change(function () {
                var flag = $('#abnormal').val();
                if (flag == 3) {
                    $('#status').show();
                } else {
                    $('#status').hide();
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
                }
            },
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});