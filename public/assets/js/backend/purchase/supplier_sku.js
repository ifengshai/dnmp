define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'jqui', 'custom-css', 'bootstrap-table-jump-to'], function ($, undefined, Backend, Table, Form, undefined) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'purchase/supplier_sku/index' + location.search,
                    add_url: 'purchase/supplier_sku/add',
                    edit_url: 'purchase/supplier_sku/edit',
                    // del_url: 'purchase/supplier_sku/del',
                    multi_url: 'purchase/supplier_sku/multi',
                    import_url: 'purchase/supplier_sku/import',
                    table: 'supplier_sku',
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
                        { field: 'id', title: __('Id'), operate: false },
                        { field: 'sku', title: __('Sku'), operate: 'like' },
                        { field: 'supplier_sku', title: __('Supplier_sku'), operate: 'like' },
                        { field: 'supplier.supplier_name', title: __('Supplier_id'), operate: 'like', },
                        {
                            field: 'link', title: '1688商品购买页链接', operate: false, cellStyle: formatTableUnit,
                            formatter: Controller.api.formatter.getClear
                        },
                        { field: 'is_matching', title: '是否匹配', operate: false, custom: { 0: 'danger', 1: 'success' }, searchList: { 0: '否', 1: '是' }, formatter: Table.api.formatter.status },
                        { field: 'is_big_goods', title: '是否为大货', operate: false, custom: { 0: 'danger', 1: 'success' }, searchList: { 0: '否', 1: '是' }, formatter: Table.api.formatter.status },
                        { field: 'label', title: '是否为主供应商', operate: false, custom: { 0: 'danger', 1: 'success' }, searchList: { 0: '否', 1: '是' }, formatter: Table.api.formatter.status },
                        { field: 'skuid', title: '匹配的skuId', operate: false },
                        { field: 'product_cycle', title: '生产周期', operate: false },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange' },
                        { field: 'create_person', title: __('Create_person'), operate: false },
                        { field: 'status', title: __('Status'), searchList: { 1: '启用', 2: '禁用' }, formatter: Table.api.formatter.status },
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate, buttons: [
                                {
                                    name: 'detail',
                                    text: '匹配sku',
                                    title: '匹配sku',
                                    classname: 'btn btn-xs  btn-primary  btn-dialog',
                                    icon: 'fa fa-envira',
                                    url: 'purchase/supplier_sku/matching',
                                    extend: 'data-area = \'["80%","70%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },
                            ]
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            //td宽度以及内容超过宽度隐藏
            function formatTableUnit(value, row, index) {
                return {
                    css: {
                        "white-space": "nowrap",
                        "text-overflow": "ellipsis",
                        "overflow": "hidden",
                        "max-width": "200px"
                    }
                }
            }

            $(document).on('click', ".problem_desc_info", function () {
                var problem_desc = $(this).attr('data');
                Layer.open({
                    closeBtn: 1,
                    title: '问题描述',
                    area: ['900px', '500px'],
                    content: decodeURIComponent(problem_desc)
                });
                return false;
            });

            //启用
            $(document).on('click', '.btn-open', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/purchase/supplier_sku/setStatus',
                    data: { ids: ids, status: 1 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })

            //禁用
            $(document).on('click', '.btn-close', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/purchase/supplier_sku/setStatus',
                    data: { ids: ids, status: 2 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })
        },
        add: function () {
            Controller.api.bindevent();
            $(document).on('click', '.btn-add', function () {
                var content = $('#table-content table tbody').html();
                $('.caigou table tbody').append(content);

                //模糊匹配订单
                $('.sku').autocomplete({
                    source: function (request, response) {
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
                    },
                    delay: 10,//延迟100ms便于输入
                    select: function (event, ui) {
                        $("#bankUnionNo").val(ui.item.id);//取出在return里面放入到item中的属性
                    },
                    scroll: true,
                    pagingMore: true,
                    max: 5000
                });
            })

            $(document).on('click', '.btn-del', function () {
                $(this).parent().parent().remove();
            })
        },
        edit: function () {
            Controller.api.bindevent();
        },
        matching: function () {
            // 初始化表格参数配置
            Table.api.init({
                commonSearch: false,
                search: false,
                showExport: false,
                showColumns: false,
                showToggle: false,
                pagination: false,
                extend: {
                    index_url: 'purchase/supplier_sku/matching' + location.search + '&ids=' + Config.ids,
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
                        // { checkbox: true },
                        { field: 'id', title: __('Id'), operate: false },
                        { field: 'title', title: '标题', operate: false },
                        { field: 'color', title: '颜色', operate: false },
                        { field: 'cargoNumber', title: '供应商货号', operate: false },
                        { field: 'price', title: '参考价格', operate: false },
                        { field: 'skuId', title: 'skuId', operate: false },
                        { field: 'parent_id', title: 'parent_id', operate: false, visible: false },
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate, buttons: [
                                {
                                    name: 'ajax',
                                    text: '选择',
                                    title: '选择',
                                    classname: 'btn btn-xs  btn-success  btn-magic  btn-ajax',
                                    icon: 'fa fa-envira',
                                    url: 'purchase/supplier_sku/matchingSkuId?skuId={skuId}&parent_id={parent_id}',
                                    confirm: '确认选择？',
                                    success: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        //再执行关闭
                                        parent.Layer.closeAll();
                                        parent.$("a.btn-refresh").trigger("click");
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    error: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        return false;
                                    }
                                },
                            ]
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        api: {
            
            formatter: {
                getClear: function (value) {
                    if (value == null || value == undefined) {
                        return '';
                    } else {
                        return '<div class="problem_desc_info" data = "' + encodeURIComponent(value) + '"' + '>' + value + '</div>';
                    }
                },
                status: function (value, row, index) {
                    var custom = { hidden: 'gray', normal: 'success', deleted: 'danger', locked: 'info' };
                    if (typeof this.custom !== 'undefined') {
                        custom = $.extend(custom, this.custom);
                    }
                    this.custom = custom;
                    this.icon = 'fa fa-circle';
                    return Table.api.formatter.normal.call(this, value, row, index);
                }
            },
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));

                //模糊匹配订单
                $('.sku').autocomplete({
                    source: function (request, response) {
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
                    },
                    delay: 10,//延迟100ms便于输入
                    select: function (event, ui) {
                        $("#bankUnionNo").val(ui.item.id);//取出在return里面放入到item中的属性
                    },
                    scroll: true,
                    pagingMore: true,
                    max: 5000
                });


                $(document).on('change', '.sku', function () {
                    var sku = $(this).val();
                    var supplier_id = $('.supplier').val();
                    var _this = $(this);
                    if (!sku) {
                        return false;
                    }
                    Backend.api.ajax({
                        url: 'ajax/getSkuList',
                        data: { sku: sku, supplier_id: supplier_id }
                    }, function (data, ret) {

                    }, function (data, ret) {
                        Fast.api.error(ret.msg);
                    });

                })
            }
        }
    };
    return Controller;
});