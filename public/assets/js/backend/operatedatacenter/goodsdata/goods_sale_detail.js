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
                    index_url: 'operatedatacenter/goodsdata/goods_sale_detail/index' + location.search,
                    add_url: 'operatedatacenter/goodsdata/single_items/add',
                    edit_url: 'operatedatacenter/goodsdata/single_items/edit',
                    del_url: 'operatedatacenter/goodsdata/single_items/del',
                    multi_url: 'operatedatacenter/goodsdata/single_items/multi',
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
                        {field: 'sku_change', title: __('平台SKU'), operate: false},
                        {field: 'sku', title: __('SKU'), operate: false},
                        {field: 'sales_num', title: __('销量'), operate: false},
                        {field: 'available_stock', title: __('虚拟仓库存'), sortable: true, operate: false},
                        {field: 'grade', title: __('产品等级'), operate: false},

                        {
                            field: 'is_up', title: __('平台上下架状态'), operate: false,
                            custom: {1: 'success', 2: 'danger'},
                            searchList: {1: '上架', 2: '下架'},
                            formatter: Table.api.formatter.status
                        },

                    ]
                ]
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
            $("#sku_submit").click(function () {
                var time = $('#create_time').val();
                var platform = $('#order_platform').val();
                if (platform != 1){
                    $('#run_glass').hide();
                    $('#old_glass').hide();
                    $('#son_glass').hide();
                }else{
                    $('#run_glass').show();
                    $('#old_glass').show();
                    $('#son_glass').show();
                }
                Form.api.bindevent($("form[role=form]"));
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
                //眼镜和饰品关键指标
                table.bootstrapTable('refresh', params);
                Backend.api.ajax({
                    url: "operatedatacenter/goodsdata/goods_sale_detail/top_sale_list",
                    data: {
                        'time': time,
                        'platform': platform
                    }
                }, function (data, ret) {
                    $('#frame_money').text(ret.rows.frame_money);
                    $('#frame_sales_num').text(ret.rows.frame_sales_num);
                    $('#frame_avg_money').text(ret.rows.frame_avg_money);
                    $('#frame_onsales_num').text(ret.rows.frame_onsales_num);
                    $('#decoration_money').text(ret.rows.decoration_money);
                    $('#decoration_sales_num').text(ret.rows.decoration_sales_num);
                    $('#decoration_avg_money').text(ret.rows.decoration_avg_money);
                    $('#decoration_onsales_num').text(ret.rows.decoration_onsales_num);
                    $('#frame_in_print_num').text(ret.rows.frame_in_print_num);
                    $('#frame_in_print_rate').text(ret.rows.frame_in_print_rate);
                    $('#decoration_in_print_num').text(ret.rows.decoration_in_print_num);
                    $('#decoration_in_print_rate').text(ret.rows.decoration_in_print_rate);
                    $('#frame_new_money').text(ret.rows.frame_new_money);
                    $('#decoration_new_money').text(ret.rows.decoration_new_money);
                    $('#frame_order_customer').text(ret.rows.frame_order_customer);
                    $('#frame_avg_customer').text(ret.rows.frame_avg_customer);
                    $('#decoration_order_customer').text(ret.rows.decoration_order_customer);
                    $('#decoration_avg_customer').text(ret.rows.decoration_avg_customer);
                    $('#frame_new_num').text(ret.rows.frame_new_num);
                    $('#decoration_new_num').text(ret.rows.decoration_new_num);
                    $('#frame_new_in_print_num').text(ret.rows.frame_new_in_print_num);
                    $('#frame_new_in_print_rate').text(ret.rows.frame_new_in_print_rate);
                    $('#decoration_new_in_print_num').text(ret.rows.decoration_new_in_print_num);
                    $('#decoration_new_in_print_rate').text(ret.rows.decoration_new_in_print_rate);

                }, function (data, ret) {
                    alert(ret.msg);
                    return false;
                });
                //其他关键指标
                Backend.api.ajax({
                    url: "operatedatacenter/goodsdata/goods_sale_detail/mid_data",
                    data: {
                        'time_str': time,
                        'order_platform': platform
                    }
                }, function (data, ret) {
                    $('#sun_sales_num').text(ret.data.sun_glass.frame_money);
                    $('#sun_run_sales_num').text(ret.data.sun_glass.frame_in_print_num);
                    $('#sun_run_sales_rate').text(ret.data.sun_glass.frame_in_print_rate);
                    $('#new_sun_run_sales_num').text(ret.data.sun_glass.frame_new_money);
                    $('#avg_sun_run_sales_num').text(ret.data.sun_glass.frame_avg_money);
                    $('#user_avg_sun_run_sales_num').text(ret.data.sun_glass.frame_avg_customer);
                    $('#sun_zhengchang_shoumai').text(ret.data.sun_glass.frame_onsales_num);
                    $('#sun_xinpin_shuliang').text(ret.data.sun_glass.frame_new_num);
                    $('#sun_xinpin_dongxiao_shu').text(ret.data.sun_glass.frame_new_in_print_num);
                    $('#sun_xinpin_dongxiao_lv').text(ret.data.sun_glass.frame_new_in_print_rate);

                    $('#glass_sales_num').text(ret.data.glass.frame_money);
                    $('#glass_run_sales_num').text(ret.data.glass.frame_in_print_num);
                    $('#glass_run_sales_rate').text(ret.data.glass.frame_in_print_rate);
                    $('#new_glass_run_sales_num').text(ret.data.glass.frame_new_money);
                    $('#avg_glass_run_sales_num').text(ret.data.glass.frame_avg_money);
                    $('#user_avg_glass_run_sales_num').text(ret.data.glass.frame_avg_customer);
                    $('#glass_zhengchang_shoumai').text(ret.data.glass.frame_onsales_num);
                    $('#glass_xinpin_shuliang').text(ret.data.glass.frame_new_num);
                    $('#glass_xinpin_dongxiao_shu').text(ret.data.glass.frame_new_in_print_num);
                    $('#glass_xinpin_dongxiao_lv').text(ret.data.glass.frame_new_in_print_rate);

                    $('#run_sales_num').text(ret.data.sun_glass.frame_money);
                    $('#run_run_sales_num').text(ret.data.sun_glass.frame_in_print_num);
                    $('#run_run_sales_rate').text(ret.data.sun_glass.frame_in_print_rate);
                    $('#new_run_run_sales_num').text(ret.data.sun_glass.frame_new_money);
                    $('#avg_run_run_sales_num').text(ret.data.sun_glass.frame_avg_money);
                    $('#user_avg_run_run_sales_num').text(ret.data.sun_glass.frame_avg_customer);
                    $('#run_zhengchang_shoumai').text(ret.data.sun_glass.frame_onsales_num);
                    $('#run_xinpin_shuliang').text(ret.data.sun_glass.frame_new_num);
                    $('#run_xinpin_dongxiao_shu').text(ret.data.sun_glass.frame_new_in_print_num);
                    $('#run_xinpin_dongxiao_lv').text(ret.data.sun_glass.frame_new_in_print_rate);

                    $('#old_sales_num').text(ret.data.old_glass.frame_money);
                    $('#old_run_sales_num').text(ret.data.old_glass.frame_in_print_num);
                    $('#old_run_sales_rate').text(ret.data.old_glass.frame_in_print_rate);
                    $('#new_old_run_sales_num').text(ret.data.old_glass.frame_new_money);
                    $('#avg_old_run_sales_num').text(ret.data.old_glass.frame_avg_money);
                    $('#user_avg_old_run_sales_num').text(ret.data.old_glass.frame_avg_customer);
                    $('#old_zhengchang_shoumai').text(ret.data.old_glass.frame_onsales_num);
                    $('#old_xinpin_shuliang').text(ret.data.old_glass.frame_new_num);
                    $('#old_xinpin_dongxiao_shu').text(ret.data.old_glass.frame_new_in_print_num);
                    $('#old_xinpin_dongxiao_lv').text(ret.data.old_glass.frame_new_in_print_rate);

                    $('#child_sales_num').text(ret.data.son_glass.frame_money);
                    $('#child_run_sales_num').text(ret.data.son_glass.frame_in_print_num);
                    $('#child_run_sales_rate').text(ret.data.son_glass.frame_in_print_rate);
                    $('#new_child_run_sales_num').text(ret.data.son_glass.frame_new_money);
                    $('#avg_child_run_sales_num').text(ret.data.son_glass.frame_avg_money);
                    $('#user_avg_child_run_sales_num').text(ret.data.son_glass.frame_avg_customer);
                    $('#child_zhengchang_shoumai').text(ret.data.son_glass.frame_onsales_num);
                    $('#gchild_xinpin_shuliang').text(ret.data.son_glass.frame_new_num);
                    $('#child_xinpin_dongxiao_shu').text(ret.data.son_glass.frame_new_in_print_num);
                    $('#child_xinpin_dongxiao_lv').text(ret.data.son_glass.frame_new_in_print_rate);

                }, function (data, ret) {
                    alert(ret.msg);
                    return false;
                });
            });
            // $("#sku_reset").click(function () {
            //     $("#sku_data").css('display', 'none');
            //     $("#order_platform").val(1);
            //     $("#time_str").val('');
            //     $("#sku").val('');
            // });
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            formatter: {},
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});

function order_data_view() {
    var order_platform = $('#order_platform').val();
    var sku = $('#sku').val();
    var time_str = $('#time_str').val();
    Backend.api.ajax({
        url: 'operatedatacenter/goodsdata/single_items/ajax_top_data',
        data: {order_platform: order_platform, time_str: time_str, sku: sku}
    }, function (data, ret) {

        var total = ret.data.total;
        $('#total').text(total);
        var whole_platform_order_num = ret.data.whole_platform_order_num;
        $('#whole_platform_order_num').text(whole_platform_order_num);
        var order_rate = ret.data.order_rate;
        $('#order_rate').text(order_rate);
        var avg_order_glass = ret.data.avg_order_glass;
        $('#avg_order_glass').text(avg_order_glass);
        var pay_jingpian_glass = ret.data.pay_jingpian_glass;
        $('#pay_jingpian_glass').text(pay_jingpian_glass);
        var pay_jingpian_glass_rate = ret.data.pay_jingpian_glass_rate;
        $('#pay_jingpian_glass_rate').text(pay_jingpian_glass_rate);
        var only_one_glass_num = ret.data.only_one_glass_num;
        $('#only_one_glass_num').text(only_one_glass_num);
        var only_one_glass_rate = ret.data.only_one_glass_rate;
        $('#only_one_glass_rate').text(only_one_glass_rate);
        var every_price = ret.data.every_price;
        $('#every_price').text(every_price);
        var whole_price = ret.data.whole_price;
        $('#whole_price').text(whole_price);

        var $table = $('#guanliangoumai');
        $table.html($("<tr>" + "<td>" + "SKU" + "</td>" + "<td>" + "数量" + "</td>" + "</tr>"));
        $.each(ret.data.array_sku, function (i, val) {
            var $tr = $("<tr>" + "<td>" + i + "</td>" + "<td>" + val + "</td>" + "</tr>");
            $table.append($tr);
        });

        return false;
    }, function (data, ret) {
        Layer.alert(ret.msg);
        return false;
    });
}