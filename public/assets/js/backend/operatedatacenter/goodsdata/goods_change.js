define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'echartsobj'], function ($, undefined, Backend, Table, Form, EchartObj) {

    var Controller = {
        index: function () {
            Controller.api.bindevent();
            // 初始化表格参数配置
            Table.api.init({
                commonSearch: false,
                search: false,
                showExport: false,
                showColumns: false,
                showToggle: false,
                extend: {
                    index_url: 'operatedatacenter/goodsdata/goods_change/index' + location.search,
                    add_url: 'operatedatacenter/goodsdata/goods_change/add',
                    edit_url: 'operatedatacenter/goodsdata/goods_change/edit',
                    del_url: 'operatedatacenter/goodsdata/goods_change/del',
                    multi_url: 'operatedatacenter/goodsdata/goods_change/multi',
                    table: 'goods_change',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                search:false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'),operate: false},
                        {field: 'sku', title: __('sku'),operate: false},
                        {field: 'sku_change', title: __('SKU转换'),operate: false},
                        {field: 'cart_num', title: __('购物车数量'),operate: false},
                        {field: 'order_num', title: __('订单成功数'),operate: false},
                        {field: 'sku_grand_total', title: __('订单金额'),operate: false},
                        {field: 'cart_change', title: __('购物车转化率'),operate: false},
                        {field: 'now_pricce', title: __('售价'),operate: false},
                        {field: 'day_date', title: __('更新时间'),operate: false},
                        {field: 'status', title: __('状态'),
                            custom: { 1: 'success', 2: 'blue'},
                            searchList: { 1: '上架', 2: '下架'},operate: false,
                            formatter: Table.api.formatter.status},
                        {field: 'glass_num', title: __('销售副数'),operate: false},
                        {field: 'sku_row_total', title: __('实际支付的销售额'),operate: false},
                        {field: 'single_price', title: __('副单价'),operate: false},
                        {field: 'stock', title: __('虚拟库存'),operate: false},
                        {field: 'on_way_stock', title: __('在途库存'),operate: false},
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
            $("#sku_submit").click(function () {
                order_data_view();
                var params = table.bootstrapTable('getOptions')
                params.queryParams = function (params) {

                    //定义参数
                    var filter = {};
                    //遍历form 组装json
                    $.each($("#form").serializeArray(), function (i, field) {
                        filter[field.name] = field.value;
                    });

                    //参数转为json字符串
                    params.filter = JSON.stringify(filter)
                    console.info(params);
                    return params;
                }

                table.bootstrapTable('refresh', params);
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

            },
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
function order_data_view() {
    var order_platform = $('#order_platform').val();
    var time_str = $('#time_str').val();
    // var order_platform = 1;
    // var time_str = '2020-09-30 00:00:00 2020-10-30 23:59:59';
    Backend.api.ajax({
        url: 'operatedatacenter/goodsdata/goods_change/sku_grade_data',
        data: {order_platform: order_platform, time_str: time_str}
    }, function (data, ret) {
        var a_plus = ret.data.a_plus;
        var aa = ret.data.aa;
        var bb = ret.data.bb;
        var cc = ret.data.cc;
        var c_plus = ret.data.c_plus;
        var dd = ret.data.ddd;
        var ee = ret.data.ee;
        // alert(a_plus.a_plus_num)
        // var again_user_num = ret.data.again_user_num;
        // var vip_user_num = ret.data.vip_user_num;
        $('#a_plus_num').text(a_plus.a_plus_num);
        $('#a_plus_session_num').text(a_plus.a_plus_session_num);
        $('#a_plus_cart_num').text(a_plus.a_plus_cart_num);
        $('#a_plus_session_change').text(a_plus.a_plus_session_change);
        $('#a_plus_order_num').text(a_plus.a_plus_order_num);
        $('#a_plus_cart_change').text(a_plus.a_plus_cart_change);
        $('#a_plus_sku_total').text(a_plus.a_plus_sku_total);

        $('#a_num').text(aa.a_num);
        $('#a_session_num').text(aa.a_session_num);
        $('#a_cart_num').text(aa.a_cart_num);
        $('#a_session_change').text(aa.a_session_change);
        $('#a_order_num').text(aa.a_order_num);
        $('#a_cart_change').text(aa.a_cart_change);
        $('#a_sku_total').text(aa.a_sku_total);

        $('#b_num').text(bb.b_num);
        $('#b_session_num').text(bb.b_session_num);
        $('#b_cart_num').text(bb.b_cart_num);
        $('#b_session_change').text(bb.b_session_change);
        $('#b_order_num').text(bb.b_order_num);
        $('#b_cart_change').text(bb.b_cart_change);
        $('#b_sku_total').text(bb.b_sku_total);

        $('#c_num').text(cc.c_num);
        $('#c_session_num').text(cc.c_session_num);
        $('#c_cart_num').text(cc.c_cart_num);
        $('#c_session_change').text(cc.c_session_change);
        $('#c_order_num').text(cc.c_order_num);
        $('#c_cart_change').text(cc.c_cart_change);
        $('#c_sku_total').text(cc.c_sku_total);

        $('#c_plus_num').text(c_plus.c_plus_num);
        $('#c_plus_session_num').text(c_plus.c_plus_session_num);
        $('#c_plus_cart_num').text(c_plus.c_plus_cart_num);
        $('#c_plus_session_change').text(c_plus.c_plus_session_change);
        $('#c_plus_order_num').text(c_plus.c_plus_order_num);
        $('#c_plus_cart_change').text(c_plus.c_plus_cart_change);
        $('#c_plus_sku_total').text(c_plus.c_plus_sku_total);

        $('#d_num').text(dd.d_num);
        $('#d_session_num').text(dd.d_session_num);
        $('#d_cart_num').text(dd.d_cart_num);
        $('#d_session_change').text(dd.d_session_change);
        $('#d_order_num').text(dd.d_order_num);
        $('#d_cart_change').text(dd.d_cart_change);
        $('#d_sku_total').text(dd.d_sku_total);

        $('#e_num').text(ee.e_num);
        $('#e_session_num').text(ee.e_session_num);
        $('#e_cart_num').text(ee.e_cart_num);
        $('#e_session_change').text(ee.e_session_change);
        $('#e_order_num').text(ee.e_order_num);
        $('#e_cart_change').text(ee.e_cart_change);
        $('#e_sku_total').text(ee.e_sku_total);

        return false;
    }, function (data, ret) {
        Layer.alert(ret.msg);
        return false;
    });
}