define(['jquery', 'bootstrap', 'backend', 'table', 'form','custom-css','bootstrap-table-jump-to'], function ($, undefined, Backend, Table, Form) {

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
        supply_chain_data: function () {
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
    
    };
    return Controller;
});