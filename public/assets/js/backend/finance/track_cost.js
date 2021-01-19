define(['jquery', 'bootstrap', 'backend', 'table', 'form','upload'], function ($, undefined, Backend, Table, Form,Upload) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                extend: {
                    index_url: 'finance/track_cost/index' + location.search,
                    edit_url: 'finance/track_cost/edit',
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
                        {field: 'id', title: __('ID'),operate:false},
                        {field: 'increment_id', title: __('订单号'),},
                        {field: 'track_number', title: __('运单号'),operate:false},
                        {field: 'platform_shop_name', title: __('平台'),searchList: { 1: 'zeelool', 2: 'voogueme', 3: 'nihao', 4: 'meeloog', 5: 'wesee', 8: 'amazon', 9: 'zeelool_es', 10: 'zeelool_de', 11: 'zeelool_jp',8003:"加诺头程", },formatter: Table.api.formatter.status},
                        {field: 'fi_actual_payment_fee', title: __('邮费'),operate:false},
                        {field: 'created_at', title: __('创建时间'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            // 导入按钮事件
            Upload.api.plupload($('.btn-import'), function (data, ret) {
                Fast.api.ajax({
                    url: 'finance/track_cost/import',
                    data: { file: data.url },
                }, function (data, ret) {
                    layer.msg('导入成功！！', { time: 3000, icon: 6 }, function () {
                        location.reload();
                    });

                });
            });


            //批量导出xls
            $('.btn-batch-export-xls').click(function () {
                var ids = Table.api.selectedids(table);
                if (ids.length > 0) {
                    window.open(Config.moduleurl + '/finance/track_cost/batch_export_xls?ids=' + ids, '_blank');
                } else {
                    var options = table.bootstrapTable('getOptions');
                    var search = options.queryParams({});
                    var filter = search.filter;
                    var op = search.op;
                    window.open(Config.moduleurl + '/customer/wholesale_customer/batch_export_xls?filter=' + filter + '&op=' + op, '_blank');
                }

            });
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
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});