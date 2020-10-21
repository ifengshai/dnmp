define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'jqui', 'bootstrap-table-jump-to'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'warehouse/outstock/index' + location.search,
                    add_url: 'warehouse/outstock/add',
                    // edit_url: 'warehouse/outstock/edit',
                    // del_url: 'warehouse/outstock/del',
                    multi_url: 'warehouse/outstock/multi',
                    import_url: 'warehouse/outstock/import',

                    table: 'out_stock',
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
                        { field: 'out_stock_number', title: __('Out_stock_number'), operate: 'like' },
                        { field: 'outstocktype.name', title: __('Type_id'), operate: 'like' },
                        { field: 'order_number', title: __('Order_number'), operate: 'like' },
                        { field: 'remark', title: __('Remark'), cellStyle: formatTableUnit, formatter: Controller.api.formatter.getClear, operate: false },
                        {
                            field: 'status', title: __('Status'), custom: { 0: 'success', 1: 'yellow', 2: 'blue', 3: 'danger', 4: 'gray' },
                            searchList: { 0: '新建', 1: '待审核', 2: '已审核', 3: '已拒绝', 4: '已取消' },
                            formatter: Table.api.formatter.status
                        },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange' },
                        { field: 'create_person', title: __('Create_person'), operate: 'like' },
                        { field: 'sku', title: __('sku'), operate: 'like', visible: false },
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [
                                {
                                    name: 'submitAudit',
                                    text: '提交审核',
                                    title: __('提交审核'),
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    icon: 'fa fa-leaf',
                                    url: 'warehouse/outstock/audit',
                                    confirm: '确认提交审核吗',
                                    success: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        $(".btn-refresh").trigger("click");
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    error: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.status < 1) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    },
                                },
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: __('Detail'),
                                    classname: 'btn btn-xs  btn-primary  btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'warehouse/outstock/detail',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },
                                {
                                    name: 'cancel',
                                    text: '取消',
                                    title: '取消',
                                    classname: 'btn btn-xs btn-danger btn-cancel',
                                    icon: 'fa fa-remove',
                                    url: 'warehouse/outstock/cancel',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.status == 0) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'edit',
                                    text: '',
                                    title: __('Edit'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-pencil',
                                    url: 'warehouse/outstock/edit',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.status == 0) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                }

                            ], formatter: Table.api.formatter.operate
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

            //审核通过
            $(document).on('click', '.btn-open', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/warehouse/outstock/setStatus',
                    data: { ids: ids, status: 2 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })

            //审核拒绝
            $(document).on('click', '.btn-close', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/warehouse/outstock/setStatus',
                    data: { ids: ids, status: 3 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })

            // 导入按钮事件
            Upload.api.plupload($('.btn-import'), function (data, ret) {
                Fast.api.ajax({

                    url: 'warehouse/outstock/import',
                    data: {file: data.url},
                }, function (data, ret) {
                    layer.msg('导入成功！！', {time: 3000, icon: 6}, function () {
                        location.reload();
                    });
                });
            });

            //审核取消
            $(document).on('click', '.btn-cancel', function (e) {
                e.preventDefault();
                var url = $(this).attr('href');
                Backend.api.ajax({
                    url: url,
                    data: { status: 4 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })
        },
        add: function () {
            Controller.api.bindevent();


            $(document).on('change', '.platform', function () {
                $(".sku").val("");
                $(".out_stock_num").val("");
                $(".product_name").text("");
                $(".tdtd").text("");

            });
            //移除
            $(document).on('click', '.btn-del', function () {
                $(this).parent().parent().remove();
            })

        },
        edit: function () {
            Controller.api.bindevent();
            $(document).on('change', '.platform', function () {
                $(".sku").val("");
                $(".out_stock_num").val("");
                $(".product_name").text("");
                $(".tdtd").text("");

            });
            //删除商品数据
            $(document).on('click', '.btn-del', function () {
                $(this).parent().parent().remove();
                var id = $(this).parent().parent().find('.item_id').val();
                if (id) {
                    Backend.api.ajax({
                        url: Config.moduleurl + '/warehouse/outstock/deleteItem',
                        data: { id: id }
                    });
                }
            })
        },
        detail: function () {
            Controller.api.bindevent();
        },
        api: {
            formatter: {
                strip_tags: function (msg) {
                    var msg = msg.replace(/<\/?[^>]*>/g, ''); //去除HTML Tag
                    msg = msg.replace(/[|]*\n/, '') //去除行尾空格
                    msg = msg.replace(/&nbsp;/ig, ''); //去掉nbsp
                    return msg;
                },
                getClear: function (value) {
                    if (value == null || value == undefined) {
                        return '';
                    } else {
                        return '<div class="problem_desc_info" data = "' + encodeURIComponent(value) + '"' + '>' + value + '</div>';
                    }
                },

            },
            
            bindevent: function () {
                //提交审核
                $(document).on('click', '.btn-status', function () {
                    $('.status').val(1);
                })
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


                //添加
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

                //根据sku异步查询商品信息
                $(document).on('blur', '.sku', function () {
                    var _this = $(this);
                    var sku = _this.val();
                    var site = $('.platform').val()
                    Backend.api.ajax({
                        url: Config.moduleurl + '/itemmanage/item/ajaxGoodsInfo',
                        data: { sku: sku,site:site}
                    }, function (res) {
                        if (res) {
                            _this.parent().next().text(res.name);
                            _this.parent().next().next().text(res.now_stock);
                            _this.parent().next().next().next().text(res.occupy_stock);
                            _this.parent().next().next().next().next().text(res.available_stock);
                            _this.parent().next().next().next().next().next().text(res.platform_stock);
                        }
                    });

                })
            }
        },
        out_stock_order: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'warehouse/outstock/out_stock_order' + location.search,
                    add_url: '',
                    edit_url: '',
                    // del_url: 'warehouse/outstock/del',
                    multi_url: '',
                    table: 'out_stock',
                }
            });

            var table = $("#table");
            table.on('load-success.bs.table', function (e, data) {
                //这里可以获取从服务端获取的JSON数据
                //这里我们手动设置底部的值
                $("#money").text(data.totalPriceInfo);

            });

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        { checkbox: true },
                        { field: 'id', title: __('Id'), operate: false },
                        { field: 'out_stock_number', title: __('Financial_out_stock_number') },
                        { field: 'outstocktype.name', title: __('Financial_type_id'), operate: false },
                        { field: 'order_number', title: __('Financial_order_number'), operate: false },
                        //{ field: 'remark', title: __('Remark'),operate:false },
                        {
                            field: 'status', title: __('Status'), custom: { 0: 'success', 1: 'yellow', 2: 'blue', 3: 'danger', 4: 'gray' },
                            searchList: { 0: '新建', 1: '待审核', 2: '已审核', 3: '已拒绝', 4: '已取消' },
                            operate: false,
                            formatter: Table.api.formatter.status
                        },
                        { field: 'total_money', title: __('Financial_total_money'), operate: false },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange' },
                        //{ field: 'create_person', title: __('Create_person'),operate:false },
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [
                                {
                                    name: 'submitAudit',
                                    text: '提交审核',
                                    title: __('提交审核'),
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    icon: 'fa fa-leaf',
                                    url: 'warehouse/outstock/audit',
                                    confirm: '确认提交审核吗',
                                    success: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        $(".btn-refresh").trigger("click");
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    error: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.status < 1) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    },
                                },
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: __('Detail'),
                                    classname: 'btn btn-xs  btn-primary  btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'warehouse/outstock/out_stock_order_detail',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },
                                {
                                    name: 'cancel',
                                    text: '取消',
                                    title: '取消',
                                    classname: 'btn btn-xs btn-danger btn-cancel',
                                    icon: 'fa fa-remove',
                                    url: 'warehouse/outstock/cancel',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.status == 0) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'edit',
                                    text: '',
                                    title: __('Edit'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-pencil',
                                    url: 'warehouse/outstock/edit',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.status == 0) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                }

                            ], formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });
        },
        out_stock_order_detail: function () {
            Form.api.bindevent($("form[role=form]"));
        }
    };
    return Controller;
});