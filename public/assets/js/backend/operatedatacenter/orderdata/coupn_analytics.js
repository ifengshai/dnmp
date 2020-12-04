define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'echartsobj'], function ($, undefined, Backend, Table, Form, EchartObj) {

    var Controller = {
        index: function () {
            Controller.api.bindevent();
            // 初始化表格参数配置
            Table.api.init({
                commonSearch: true,
                search: false,
                showExport: true,
                showColumns: false,
                showToggle: false,
                extend: {
                    index_url: 'operatedatacenter/orderdata/coupn_analytics/index' + location.search,
                    add_url: 'operatedatacenter/orderdata/coupn_analytics/add',
                    edit_url: 'operatedatacenter/orderdata/coupn_analytics/edit',
                    del_url: 'operatedatacenter/orderdata/coupn_analytics/del',
                    multi_url: 'operatedatacenter/orderdata/coupn_analytics/multi',
                    table: 'sku_detail',
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
                        { field: 'rule_id', title: __('优惠券id'),operate: false},
                        { field: 'channel', title: __('优惠卷类型'), custom: { 1: 'danger', 2: 'green' , 3: 'blue', 4: 'yellow', 5: 'pink'}, searchList: { 1:'网站优惠券', 2:'主页优惠券', 3:'用户优惠券', 4:'渠道优惠券',5:'客服优惠券'}, formatter: Table.api.formatter.status },
                        { field: 'name', title: __('优惠卷名称'),operate: 'like'},
                        { field: 'use_order_num', title: __('应用订单数量'),operate: false, sortable: true},
                        { field: 'use_order_num_rate', title: __('订单数量占比'),operate: false},
                        { field: 'use_order_total_price', title: __('订单金额'),operate: false, sortable: true},
                        { field: 'use_order_total_price_rate', title: __('订单金额占比'),operate: false},
                        { field: 'time_str', title: __('订单创建时间'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime,visible:false},
                        { field: 'order_platform', title: __('平台'),searchList: { 1:'zeelool', 2:'voogueme', 3:'nihao'}, formatter: Table.api.formatter.status,visible:false},

                    ]
                ]
            });
            // 为表格绑定事件
            Controller.api.formatter.user_data_pie();
            Controller.api.formatter.lens_data_pie();
            // var params = table.bootstrapTable('getOptions')
            // params.queryParams = function(params) {
            //
            //     //定义参数
            //     var filter = {};
            //     //遍历form 组装json
            //     $.each($("#form").serializeArray(), function(i, field) {
            //         filter[field.name] = field.value;
            //     });
            //
            //     //参数转为json字符串
            //     params.filter = JSON.stringify(filter)
            //     return params;
            // }
            //
            // table.bootstrapTable('refresh',params);
            Table.api.bindevent(table);

            $("#sku_submit").click(function(){

                Controller.api.formatter.user_data_pie();
                Controller.api.formatter.lens_data_pie();

            });
            $("#sku_reset").click(function(){
                $("#sku_data").css('display','none'); 
                $("#order_platform").val(1);
                $("#time_str").val('');
                $("#sku").val('');

            });
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            formatter: {
                user_data_pie: function () {
                    //库存分布
                    var chartOptions = {
                        targetId: 'echart1',
                        downLoadTitle: '图表',
                        type: 'pie',
                        pie: {

                            tooltip: { //提示框组件。
                                trigger: 'item',
                                formatter: function (param) {
                                    return param.data.name + '<br/>数量：' + param.data.value + '<br/> 占比：' + param.percent.toFixed(2) + '%';
                                }
                            },
                            title:{
                                subtext:'总订单数',
                                left:"center",
                                top:"40%",
                                subtextStyle:{
                                    textAlign:"center",
                                    fill:"#333",
                                    fontSize:16,
                                    fontWeight:700
                                },
                                textStyle:{
                                    color:"#27D9C8",
                                    fontSize:32,
                                    align:"center"
                                }
                            },
                            series:[{
                                radius: ['50%', '70%'],
                            }]
                        }
                    };

                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/orderdata/coupn_analytics/user_data_pie',
                        data: {
                            'time_str' :  $("#time_str").val(),
                            'order_platform' :  $("#order_platform").val(),
                        }

                    };
                    EchartObj.api.ajax(options, chartOptions)
                },
                lens_data_pie: function () {
                    var chartOptions = {
                        targetId: 'echart2',
                        downLoadTitle: '图表',
                        type: 'pie',
                        pie: {

                            tooltip: { //提示框组件。
                                trigger: 'item',
                                formatter: function (param) {
                                    return param.data.name + '<br/>金额：' + param.data.value + '<br/> 占比：' + param.percent.toFixed(2) + '%';
                                }
                            },
                            title:{
                                subtext:'总订单金额',
                                left:"center",
                                top:"40%",
                                subtextStyle:{
                                    textAlign:"center",
                                    fill:"#333",
                                    fontSize:16,
                                    fontWeight:700
                                },
                                textStyle:{
                                    color:"#27D9C8",
                                    fontSize:32,
                                    align:"center"
                                }
                            },
                            series:[{
                                radius: ['50%', '70%'],
                            }]
                        }
                    };

                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/orderdata/coupn_analytics/lens_data_pie',
                        data: {
                            'time_str' :  $("#time_str").val(),
                            'order_platform' :  $("#order_platform").val(),
                        }

                    };
                    EchartObj.api.ajax(options, chartOptions)
                },
            },
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});