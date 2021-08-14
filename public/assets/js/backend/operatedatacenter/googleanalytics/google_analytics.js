define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'operatedatacenter/googleanalytics/google_analytics/index' + location.search,
                    table: 'zendesk_account',
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
                        {field: 'sku', title: __('SKU'),operate:false},
                        {field: 'sku_quote_counter', title: __('加购数目'),operate:false},
                        {field: 'sku_order_counter', title: __('订单数目'),operate:false},
                        {field: 'quote_uniquePageviews_percent', title: __('加购转化率'),operate:false},
                        {field: 'order_quote_percent', title: __('购物车转化率'),operate:false},
                        {field: 'order_uniquePageviews_percent', title: __('订单转化率'),operate:false},
                        {field: 'pagePath', title: __('pagePath'),formatter: function (value, row, index) {
                               let page = '';
                               if(value){
                                   for (var i=0;i<value.length;i++)
                                   {
                                       page += value[i] + '</br>';
                                   }
                               }
                                return page;
                            }, operate: false
                        },
                        {field: 'pageviews', title: __('pageviews'),operate:false},
                        {field: 'uniquePageviews', title: __('uniquePageviews'),operate:false},
                        {field: 'entrances', title: __('entrances'),operate:false},
                        {field: 'exits', title: __('exits'),operate:false},
                        {field: 'pageValue', title: __('pageValue'),operate:false},
                        {
                            field: 'site', title: __('站点'), visible: false,
                            operate: 'IN',
                            searchList: {
                                1: 'zeelool', 2: 'voogueme', 3: 'meeloog', 5: 'wesee', 10: 'zeelool_de', 11: 'zeelool_jp',
                                12: 'voogmechic', 15: 'zeelool_fr'
                            },
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'time',
                            title: __('时间'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            visible: false
                        },
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
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});