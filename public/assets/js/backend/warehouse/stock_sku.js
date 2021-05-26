define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'jqui','bootstrap-table-jump-to'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'warehouse/stock_sku/index' + location.search,
                    add_url: 'warehouse/stock_sku/add',
                    edit_url: 'warehouse/stock_sku/edit',
                    del_url: 'warehouse/stock_sku/del',
                    multi_url: 'warehouse/stock_sku/multi',
                    import_url: 'warehouse/stock_sku/import',
                    table: 'store_sku',
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
                        { field: 'id', title: __('Id') },
                        { field: 'warehouse_stock.name', title: __('实体仓名称'), operate: false },
                        {
                            field: 'stock_id', title: __('实体仓名称'), custom: Config.warehourseStock,
                            searchList: Config.warehourseStock,
                            formatter: Table.api.formatter.status,
                            visible:false
                        },
                        { field: 'area_coding', title: __('库区编码'), operate: 'like' },
                        { field: 'storehouse.coding', title: __('库位编码'), operate: 'like' },
                        { field: 'sku', title: __('Sku'), operate: 'like' },
                        { field: 'name', title: __('商品名称'), operate: false },
                        // {
                        //     field: 'is_open', title: __('SKU启用状态'), custom: { 1: 'success', 2: 'danger' },
                        //     searchList: { 1: '启用', 2: '禁用' }, operate: false,
                        //     formatter: Table.api.formatter.status
                        // },


                        { field: 'storehouse.library_name', title: __('Storehouse.library_name'), operate: 'like' },
                        {
                            field: 'storehouse.status', title: __('库位状态'), custom: { 1: 'success', 2: 'danger' },
                            searchList: { 1: '启用', 2: '禁用' },
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'area_status', title: __('库区状态'), custom: { 1: 'success', 2: 'danger' },
                            searchList: { 1: '启用', 2: '禁用' },operate: false,
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'is_open', title: __('SKU启用状态'), custom: { 1: 'success', 2: 'danger' },
                            searchList: { 1: '启用', 2: '禁用' }, operate: false,
                            formatter: Table.api.formatter.status
                        },
                        { field: 'create_person', title: __('Create_person') },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange' },
                        { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
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

                //模糊匹配订单
                $('.sku').autocomplete({
                    source: function (request, response) {
                        if (request.term.length > 2) {
                            $.ajax({
                                type: "POST",
                                url: "ajax/ajaxGetLikeOriginSku",
                                dataType: "json",
                                cache: false,
                                async: false,
                                data: {
                                    origin_sku: request.term
                                },
                                success: function (json) {
                                    var data = json.data;
                                    response($.map(data, function (item) {
                                        return {
                                            label: item,//下拉框显示值
                                            value: item,//选中后，填充到input框的值
                                            //id:item.bankCodeInfo//选中后，填充到id里面的值
                                        };
                                    }));
                                }
                            });
                        }

                    },
                    delay: 10,//延迟100ms便于输入
                    select: function (event, ui) {
                        $("#bankUnionNo").val(ui.item.id);//取出在return里面放入到item中的属性
                    },
                    scroll: true,
                    pagingMore: true,
                    max: 5000
                });
            }
        }
    };
    $('.warehouse_stock').on('change',function() {
        var id = $(this).val();
        $.ajax({
            url: "warehouse/warehouse_stock/getWarehouseArea",
            async: false,
            data: {stock_id:id},
            success: function (obj) {
                var html = [];
                html.push("<select class='form-control area' name='row[area_id]'>");
                html.push("<option value=''>请选择</option>");
                for (var i = 0; i < obj.length; i++) {
                    var stock = obj[i]
                    console.log(stock.id)
                    html.push("<option value='" + stock.id + "'>" + stock.name + "</option>");
                }
                html.push("</select>");
                $('#area').html(html.join(""));
            }
        });
    });
    $(document).on('change','.area',function() {
        var id = $(this).val();
        $.ajax({
            url: "warehouse/warehouse_area/getStockHouse",
            async: false,
            data: {area_id:id},
            success: function (obj) {
                var html = [];
                html.push("<select class='form-control store selectpicker' data-live-search = true name='row[store_id]'>");
                for (var i = 0; i < obj.length; i++) {
                    var house = obj[i]
                    console.log(house.id)
                    html.push("<option value='" + house.id + "'>" + house.coding + "</option>");
                }
                html.push("</select>");
                // $('#store').html(html.join(""));
            }
        });
    })
    return Controller;
});