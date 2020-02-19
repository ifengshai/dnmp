define(['jquery', 'bootstrap', 'backend', 'table', 'form','bootstrap-table-jump-to'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                showExport: false,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'datacenter/index/index' + location.search,
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
                        {
                            field: '', title: __('序号'), formatter: function (value, row, index) {
                                var options = table.bootstrapTable('getOptions');
                                var pageNumber = options.pageNumber;
                                var pageSize = options.pageSize;
                                return (pageNumber - 1) * pageSize + 1 + index;
                            }, operate: false
                        },
                        { field: 'sku', title: __('SKU'), operate: 'like' },
                        { field: 'z_sku', title: __('Zeelool_SKU'), operate: false},
                        { field: 'z_num', title: __('Z站销量'), operate: false},
                        { field: 'v_sku', title: __('Voogueme_SKU'), operate: false},
                        { field: 'v_num', title: __('V站销量'), operate: false},
                        { field: 'n_sku', title: __('Nihao_SKU'), operate: false},
                        { field: 'n_num', title: __('Nihao站销量'), operate: false},
                        { field: 'available_stock', title: __('实时库存'), operate: false},
                        { field: 'all_num', title: __('汇总销量'), operate: false},
                        { field: 'created_at',visible:false, title: __('创建时间'), operate: 'RANGE', addclass: 'datetimerange' },
                        
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        detail: function () {
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
                    if (value) {
                        return parseFloat(value).toFixed(2);
                    }
                },
                int_format: function (value, row, index) {
                    return parseInt(value);
                },
            },
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        },
        account_order: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                showExport: false,
                showColumns: false,
                showToggle: false,
                extend: {
                    index_url: 'order/index/account_order' + '?label=' + Config.label,
                    add_url: '',
                    edit_url: '',
                    del_url: '',
                    multi_url: '',
                }
            });

            var table = $("#table");

            //在普通搜索提交搜索前
            table.on('common-search.bs.table', function (event, table, query) {
                //这里可以获取到普通搜索表单中字段的查询条件
                console.log(query);
            });


            //在表格内容渲染完成后回调的事件
            table.on('post-body.bs.table', function (e, settings, json, xhr) {
                console.log(e, settings, json, xhr);
            });

            //当表格数据加载完成时
            table.on('load-success.bs.table', function (e, data) {
                //这里我们手动设置底部的值
                console.log(data);
                $("#totalPayInfo").text(data.totalPayInfo);
                $("#totalFramePrice").text(data.totalFramePrice);
                $("#totalLensPrice").text(data.totalLensPrice);
                $("#totalRefundMoney").text(data.totalRefundMoney);
                $("#totalFullPostMoney").text(data.totalFullPostMoney);

            });

            // 初始化表格
            // 这里使用的是Bootstrap-table插件渲染表格
            // 相关文档：http://bootstrap-table.wenzhixin.net.cn/zh-cn/documentation/
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'entity_id',
                sortName: 'entity_id',
                columns: [
                    [
                        //更多配置参数可参考http://bootstrap-table.wenzhixin.net.cn/zh-cn/documentation/#c
                        //该列为复选框字段,如果后台的返回state值将会默认选中
                        // {field: 'state', checkbox: true,},
                        //sortable为是否排序,operate为搜索时的操作符,visible表示是否可见
                        { checkbox: true },
                        { field: 'entity_id', title: __('记录标识'), operate: false },
                        //默认隐藏该列
                        { field: 'increment_id', title: __('订单号') },
                        { field: 'customer_email', title: __('邮箱'), operate: 'like' },
                        { field: 'status', title: __('状态'), searchList: { "processing": __('processing'), 'complete': 'complete', 'creditcard_failed': 'creditcard_failed', 'creditcard_pending': 'creditcard_pending', 'holded': 'holded', 'payment_review': 'payment_review', 'paypal_canceled_reversal': 'paypal_canceled_reversal', 'paypal_reversed': 'paypal_reversed', 'pending': 'pending', 'canceled': 'canceled', 'closed': 'closed', "free_processing": __('free_processing') } },
                        { field: 'total_money', title: __('支付金额（$）'), operate: false},
                        { field: 'frame_cost',title:__('镜架成本金额（￥）'),operate:false,formatter:Controller.api.formatter.float_format},
                        { field: 'lens_cost',title:__('镜片成本金额（￥）'),operate:false,formatter:Controller.api.formatter.float_format},
                        { field: 'postage_money',title:__('邮费成本金额（￥）'),operate:false,formatter:Controller.api.formatter.float_format},
                        { field: 'process_cost',title:__('加工费成本金额（￥）'),operate:false,formatter:Controller.api.formatter.float_format},
                        { field: 'refund_money',title:__('退款金额'),operate:false,formatter:Controller.api.formatter.float_format},
                        { field: 'fill_post',title:__('补差价金额'),operate:false,formatter:Controller.api.formatter.float_format},
                        { field: 'created_at', title: __('创建时间'), operate: 'RANGE', addclass: 'datetimerange' },
                    ],
                ],
                //更多配置参数可参考http://bootstrap-table.wenzhixin.net.cn/zh-cn/documentation/#t
                //亦可以参考require-table.js中defaults的配置
                //快捷搜索,这里可在控制器定义快捷搜索的字段
                search: false,
                //启用普通表单搜索
                commonSearch: true,
                //显示导出按钮
                showExport: true,
                //导出类型
                exportDataType: "all", //共有basic, all, selected三种值 basic当前页 all全部 selected仅选中
                //导出下拉列表选项
                exportTypes: ['json', 'xml', 'csv', 'txt', 'doc', 'excel'],
                //可以控制是否默认显示搜索单表,false则隐藏,默认为false
                searchFormVisible: true
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        postage_import: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                showExport: false,
                showColumns: false,
                showToggle: false,
                extend: {
                    index_url: 'order/index/postage_import' + '?label=' + Config.label,
                    import_url: 'order/index/import',
                    edit_url: '',
                    del_url: '',
                    multi_url: '',
                }
            });

            var table = $("#table");

            //在普通搜索提交搜索前
            table.on('common-search.bs.table', function (event, table, query) {
                //这里可以获取到普通搜索表单中字段的查询条件
                console.log(query);
            });


            //在表格内容渲染完成后回调的事件
            table.on('post-body.bs.table', function (e, settings, json, xhr) {
                console.log(e, settings, json, xhr);
            });

            //当表格数据加载完成时
            table.on('load-success.bs.table', function (e, data) {
                //这里我们手动设置底部的值
                console.log(data);
                $("#totalPayInfo").text(data.totalPayInfo);
                $("#totalFramePrice").text(data.totalFramePrice);
                $("#totalLensPrice").text(data.totalLensPrice);
                $("#totalPostageMoney").text(data.totalPostageMoney);

            });

            // 初始化表格
            // 这里使用的是Bootstrap-table插件渲染表格
            // 相关文档：http://bootstrap-table.wenzhixin.net.cn/zh-cn/documentation/
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'entity_id',
                sortName: 'entity_id',
                columns: [
                    [
                        //更多配置参数可参考http://bootstrap-table.wenzhixin.net.cn/zh-cn/documentation/#c
                        //该列为复选框字段,如果后台的返回state值将会默认选中
                        // {field: 'state', checkbox: true,},
                        //sortable为是否排序,operate为搜索时的操作符,visible表示是否可见
                        { checkbox: true },
                        { field: 'entity_id', title: __('记录标识'), operate: false },
                        //默认隐藏该列
                        { field: 'increment_id', title: __('订单号') },
                        { field: 'postage_money', title: __('邮费'), operate: false},
                        { field: 'postage_create_time', title: __('创建时间'), operate: 'RANGE', addclass: 'datetimerange' },
                        //操作栏,默认有编辑、删除或排序按钮,可自定义配置buttons来扩展按钮
                        // {
                        //     field: 'operate', width: "120px", title: __('操作'), table: table, formatter: Table.api.formatter.operate,
                        //     buttons: [
                        //         {
                        //             name: 'passAudit',
                        //             text: '修改',
                        //             title: __('修改'),
                        //             classname: 'btn btn-xs btn-success btn-ajax',
                        //             icon: 'fa fa-pencil',
                        //             url: 'order/index/account_order_detail_edit?label=' + Config.label,
                        //             confirm: '确认修改吗',
                        //             success: function (data, ret) {
                        //                 Layer.alert(ret.msg);
                        //                 $(".btn-refresh").trigger("click");
                        //                 //如果需要阻止成功提示，则必须使用return false;
                        //                 //return false;
                        //             },
                        //             error: function (data, ret) {
                        //                 Layer.alert(ret.msg);
                        //                 return false;
                        //             },
                        //             visible: function (row) {
                        //                 //返回true时按钮显示,返回false隐藏
                        //                 return true;
                        //             }
                        //         }
                        //     ]
                        // },
                    ],
                ],
                //更多配置参数可参考http://bootstrap-table.wenzhixin.net.cn/zh-cn/documentation/#t
                //亦可以参考require-table.js中defaults的配置
                //快捷搜索,这里可在控制器定义快捷搜索的字段
                search: false,
                //启用普通表单搜索
                commonSearch: true,
                //显示导出按钮
                showExport: true,
                //导出类型
                exportDataType: "all", //共有basic, all, selected三种值 basic当前页 all全部 selected仅选中
                //导出下拉列表选项
                exportTypes: ['json', 'xml', 'csv', 'txt', 'doc', 'excel'],
                //可以控制是否默认显示搜索单表,false则隐藏,默认为false
                searchFormVisible: true
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        }        
    };
    return Controller;
});