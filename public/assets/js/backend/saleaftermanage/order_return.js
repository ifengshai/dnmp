define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'jqui', 'custom-css', 'bootstrap-table-jump-to'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'saleaftermanage/order_return/index' + location.search,
                    add_url: 'saleaftermanage/order_return/add',
                    edit_url: 'saleaftermanage/order_return/edit',
                    //del_url: 'saleaftermanage/order_return/del',
                    multi_url: 'saleaftermanage/order_return/multi',
                    table: 'order_return',
                }
            });

            var table = $("#table");
            $(".btn-add").data("area", ["100%", "100%"]);
            table.on('post-body.bs.table', function (e, settings, json, xhr) {
                $(".btn-editone").data("area", ["100%", "100%"]);
            });
            $(document).on('click', ".problem_desc_info", function () {
                var problem_desc = $(this).attr('name');
                Layer.alert(problem_desc);
                return false;
            });
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

                                //return (pageNumber - 1) * pageSize + 1 + index;
                                return 1 + index;
                            }, operate: false
                        },
                        { field: 'id', title: __('Id'), operate: false },
                        { field: 'return_order_number', title: __('Return_order_number') },
                        { field: 'increment_id', title: __('Increment_id') },
                        { field: 'order_platform', title: __('Order_platform'), searchList: $.getJSON('saleaftermanage/sale_after_task/getAjaxOrderPlatformList'), formatter: Controller.api.formatter.devicess },
                        { field: 'customer_email', title: __('Customer_email') },
                        { field: 'sale_after_issue.name', title: __('Issue_id'), operate: false },
                        { field: 'return_remark', title: __('Return_remark'), formatter: Controller.api.formatter.getClear, operate: false },
                        {
                            field: 'order_status',
                            title: __('Order_status'),
                            searchList: { 1: '新建', 2: '待审核', 3: '已审核', 4: '已拒绝', 5: '已取消' },
                            custom: { 1: 'yellow', 2: 'blue', 3: 'success', 4: 'red', 5: 'gray' },
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'quality_status',
                            title: __('Quality_status'),
                            searchList: { 0: '未质检', 1: '已质检' },
                            custom: { 0: 'yellow', 1: 'blue' },
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'in_stock_status',
                            title: __('In_stock_status'),
                            searchList: { 0: '未入库', 1: '已入库' },
                            custom: { 0: 'yellow', 1: 'blue' },
                            formatter: Table.api.formatter.status
                        },
                        { field: 'create_person', title: __('Create_person') },
                        { field: 'create_time', title: __('Create_time'), operate: 'RANGE', addclass: 'datetimerange' },
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, extend: 'data-area = \'["100%","100%"]\'', buttons: [
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: __('Detail'),
                                    classname: 'btn btn-xs  btn-primary  btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'saleaftermanage/order_return/detail',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    // callback: function (data) {
                                    //     Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    // },
                                    // visible: function (row) {
                                    //     //返回true时按钮显示,返回false隐藏
                                    //     return true;
                                    // }
                                },
                                // {
                                //     name: 'receive',
                                //     text: '退货收到',
                                //     title: __('退货收到'),
                                //     classname: 'btn btn-xs btn-success btn-ajax',
                                //     icon: 'fa fa-pencil',
                                //     confirm: '确定要收到退货吗',
                                //     url: 'saleaftermanage/order_return/receive',
                                //     success: function (data, ret) {
                                //         Layer.alert(ret.msg);
                                //         $(".btn-refresh").trigger("click");
                                //         //如果需要阻止成功提示，则必须使用return false;
                                //         //return false;
                                //     },
                                //     error: function (data, ret) {
                                //         Layer.alert(ret.msg);
                                //         return false;
                                //     },
                                //     visible: function (row) {
                                //         //返回true时按钮显示,返回false隐藏
                                //         return true;
                                //     }

                                // },
                                // {
                                //     name: 'quality',
                                //     text: '退货质检',
                                //     title: __('quality'),
                                //     classname: 'btn btn-xs btn-success btn-ajax',
                                //     icon: 'fa fa-pencil',
                                //     confirm: '确定已经质检了吗',
                                //     url: 'saleaftermanage/order_return/quality',
                                //     success: function (data, ret) {
                                //         Layer.alert(ret.msg);
                                //         $(".btn-refresh").trigger("click");
                                //         //如果需要阻止成功提示，则必须使用return false;
                                //         //return false;
                                //     },
                                //     error: function (data, ret) {
                                //         Layer.alert(ret.msg);
                                //         return false;
                                //     },
                                //     visible: function (row) {
                                //         //返回true时按钮显示,返回false隐藏
                                //         return true;
                                //     }

                                // },
                                // {
                                //     name: 'syncStock',
                                //     text: '同步库存',
                                //     title: __('syncStock'),
                                //     classname: 'btn-xs btn-success btn-ajax',
                                //     icon: 'fa fa-pencil',
                                //     confirm: '确定要同步库存吗',
                                //     url: 'saleaftermanage/order_return/syncStock',
                                //     success: function (data, ret) {
                                //         Layer.alert(ret.msg);
                                //         $(".btn-refresh").trigger("click");
                                //         //如果需要阻止成功提示，则必须使用return false;
                                //         //return false;
                                //     },
                                //     error: function (data, ret) {
                                //         Layer.alert(ret.msg);
                                //         return false;
                                //     },
                                //     visible: function (row) {
                                //         //返回true时按钮显示,返回false隐藏
                                //         return true;
                                //     }

                                // },
                                // {
                                //     name: 'refund',
                                //     text: '退款',
                                //     title: __('Refund'),
                                //     classname: 'btn btn-xs btn-success btn-ajax',
                                //     icon: 'fa fa-pencil',
                                //     confirm: '确定要退款吗',
                                //     url: 'saleaftermanage/order_return/refund',
                                //     success: function (data, ret) {
                                //         Layer.alert(ret.msg);
                                //         $(".btn-refresh").trigger("click");
                                //         //如果需要阻止成功提示，则必须使用return false;
                                //         //return false;
                                //     },
                                //     error: function (data, ret) {
                                //         Layer.alert(ret.msg);
                                //         return false;
                                //     },
                                //     visible: function (row) {
                                //         //返回true时按钮显示,返回false隐藏
                                //         return true;
                                //     }

                                // },
                                {
                                    name: 'edit',
                                    text: '编辑',
                                    title: __('编辑'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-pencil',
                                    url: 'saleaftermanage/order_return/edit',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        if (row.order_status == 1) {
                                            return true;
                                        }
                                        return false;
                                    }
                                },
                                {
                                    name: 'closed',
                                    text: '取消',
                                    title: __('Closed'),
                                    classname: 'btn btn-xs btn-danger btn-ajax',
                                    icon: 'fa fa-remove',
                                    confirm: '确定要取消吗',
                                    url: 'saleaftermanage/order_return/closed',
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
                                        if (row.order_status == 1) {
                                            return true;
                                        }
                                        return false;

                                    }

                                }
                                // {
                                //     name: 'edit',
                                //     text: '',
                                //     title: __('Edit'),
                                //     classname: 'btn btn-xs btn-success btn-dialog',
                                //     icon: 'fa fa-pencil',
                                //     url: 'purchase/purchase_order/edit',
                                //     extend: 'data-area = \'["100%","100%"]\'',
                                //     callback: function (data) {
                                //         Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                //     },
                                //     visible: function (row) {
                                //         //返回true时按钮显示,返回false隐藏
                                //         return true;
                                //     }
                                // }
                            ], formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });
            $('.panel-heading a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var field = $(this).data("field");
                var value = $(this).data("value");
                var options = table.bootstrapTable('getOptions');
                options.pageNumber = 1;
                var queryParams = options.queryParams;
                options.queryParams = function (params) {
                    var params = queryParams(params);
                    var filter = params.filter ? JSON.parse(params.filter) : {};
                    if (field == 'create_person') {
                        delete filter.rep_id;
                        filter[field] = value;
                    } else if (field == 'rep_id') {
                        delete filter.create_person;
                        filter[field] = value;
                    } else {
                        delete filter.rep_id;
                        delete filter.create_person;
                    }
                    params.filter = JSON.stringify(filter);
                    return params;
                };
                table.bootstrapTable('refresh', {});
                return false;
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
            //提交审核
            $(document).on('click', '.btn-start', function () {
                var ids = Table.api.selectedids(table);
                Layer.confirm(
                    __('确定要提交审核吗'),
                    function (index) {
                        Backend.api.ajax({
                            url: "saleaftermanage/order_return/submitAudit",
                            data: { ids: ids }
                        }, function (data, ret) {
                            table.bootstrapTable('refresh');
                            Layer.close(index);
                        });
                    }
                );
            });
            //审核通过
            $(document).on('click', '.btn-pass', function () {
                var ids = Table.api.selectedids(table);
                Layer.confirm(
                    __('确定要审核通过吗'),
                    function (index) {
                        Backend.api.ajax({
                            url: "saleaftermanage/order_return/morePassAudit",
                            data: { ids: ids }
                        }, function (data, ret) {
                            table.bootstrapTable('refresh');
                            Layer.close(index);
                        });
                    }
                );
            });
            //审核拒绝
            $(document).on('click', '.btn-refused', function () {
                var ids = Table.api.selectedids(table);
                Layer.confirm(
                    __('确定要审核拒绝吗'),
                    function (index) {
                        Backend.api.ajax({
                            url: "saleaftermanage/order_return/moreAuditRefused",
                            data: { ids: ids }
                        }, function (data, ret) {
                            table.bootstrapTable('refresh');
                            Layer.close(index);
                        });
                    }
                );
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
                devicess: function (value) {
                    var str2 = '';
                    if (value == 1) {
                        str2 = 'zeelool';
                    } else if (value == 2) {
                        str2 = 'voogueme';
                    } else if (value == 3) {
                        str2 = 'nihao';
                    }
                    return str2;
                },
                getClear: function (value) {
                    if (value == null || value == undefined) {
                        return '';
                    } else {
                        var tem = value
                            .replace(/&lt;/g, "<")
                            .replace(/&gt;/g, ">")
                            .replace(/&quot;/g, "\"")
                            .replace(/&apos;/g, "'")
                            .replace(/&amp;/g, "&")
                            .replace(/&nbsp;/g, '').replace(/<\/?.+?\/?>/g, '').replace(/<[^>]+>/g, "")
                        if (tem.length <= 10) {
                            console.log(value);
                            return tem;
                        } else {
                            return tem.substr(0, 10) + '<span class="problem_desc_info" name = "' + tem + '" style="color:red;">...</span>';

                        }
                    }
                }
            },

            bindevent: function () {
                Form.api.bindevent($("form[role=form]"), function (data, ret) {
                    //console.log(ret);
                    location.href = ret.url;
                });
                //模糊匹配订单
                $('#c-increment_id').autocomplete({
                    source: function (request, response) {
                        var incrementId = $('#c-increment_id').val();
                        var orderType = $('#c-order_platform').val();
                        if (incrementId.length > 2) {
                            $.ajax({
                                type: "POST",
                                url: "saleaftermanage/order_return/ajaxGetLikeOrder",
                                dataType: "json",
                                cache: false,
                                async: false,
                                data: {
                                    orderType: orderType, order_number: incrementId
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
                $(document).on('blur', '#c-increment_id', function () {
                    var ordertype = $('#c-order_platform').val();
                    var order_number = $('#c-increment_id').val();
                    if (ordertype <= 0) {
                        alert('请选择正确的平台');
                        return false;
                    }
                    Backend.api.ajax({
                        url: 'saleaftermanage/sale_after_task/ajax',
                        data: { ordertype: ordertype, order_number: order_number }
                    }, function (data, ret) {
                        //成功的回调
                        //alert(ret);
                        //清除html商品数据
                        var email = ret.data[0].customer_email != undefined ? ret.data[0].customer_email : '';
                        var firstname = ret.data[0].customer_firstname != undefined ? ret.data[0].customer_firstname : '';
                        var lastname = ret.data[0].customer_lastname != undefined ? ret.data[0].customer_lastname : '';
                        $(".item_info").empty();
                        $('#c-order_status').val(ret.data.status);
                        $('#c-customer_name').val(firstname + " " + lastname);
                        $('#c-customer_email').val(email);
                        if (ret.data.store_id >= 2) {
                            $('#c-order_source').val(2);
                        } else {
                            $('#c-order_source').val(1);
                        }
                        var item = ret.data;
                        $('#customer_info').after(function () {
                            var Str = '';
                            Str += '<div class="row item_info" style="margin-top:15px;margin-left:15%;" >' +
                                '<div class="col-lg-12">' +
                                '</div>' +
                                '<div class="col-xs-6 col-md-12">' +
                                '<div class="col-xs-6 col-md-10">' +
                                '<table id="caigou-table" class="col-xs-6 col-md-12">' +
                                '<tr>' +
                                '<td style="text-align: center">序号</td>' +
                                '<td style="text-align: center" class="col-xs-6 col-md-4">SKU</td>' +
                                '<td style="text-align: center" class="col-xs-6 col-md-4">商品名称</td>' +
                                '<td style="text-align: center" >购买数量</td>' +
                                '<td style="text-align: center">退回数量</td>' +
                                // '<td style="text-align: center">到货数量</td>' +
                                // '<td style="text-align: center">质检合格数量</td>' +
                                '</tr>';
                            for (var j = 0, len = item.length; j < len; j++) {
                                var newItem = item[j];
                                var m = j + 1;
                                Str += '<tr>';
                                Str += '<td><input id="c-right_SPH" class="form-control"  type="text" value="' + m + '"></td>';
                                Str += '<td><input id="c-sku" class="form-control"  name="row[item][' + m + '][item_sku]" type="text" value="' + newItem.sku + '"></td>';
                                Str += '<td><input id="c-name" class="form-control" name="row[item][' + m + '][item_name]" type="text" value="' + newItem.name + '"></td>';
                                Str += '<td><input id="c-qty_ordered" class="form-control" name="row[item][' + m + '][sku_qty]"  type="text" value="' + Math.round(newItem.qty_ordered) + '"></td>';
                                Str += '<td><input id="c-right_PD" class="form-control" name="row[item][' + m + '][return_sku_qty]" type="text" value="0"></td>';
                                // Str += '<td><input id="c-right_Prism_Horizontal" name="row[item][' + m + '][arrived_sku_qty]" class="form-control"  type="text" value="0"></td>';
                                // Str += '<td><input id="c-return_sku_qty" name="row[item][' + m + '][check_sku_qty]" class="form-control"  type="number" value="0"></td>';
                                Str += '</tr>';
                            }
                            Str += '</table>' +
                                '</div>' +
                                '</div>';
                            return Str;
                        });
                        //console.log(ret);
                        //console.log($('#c-order_status').val());
                        return false;
                    }, function (data, ret) {
                        //失败的回调
                        alert(ret.msg);
                        console.log(ret);
                        return false;
                    });
                });
            }
        },
        detail: function () {
            Form.api.bindevent($("form[role=form]"));
        },
        search: function () {
            Form.api.bindevent($("form[role=form]"), function (data) {
                $('#div-list').html(data);
            });

            //站点切换
            $(document).on('click', '.site', function () {
                $(this).addClass("active").siblings().removeClass("active");
                $('#order_platform').val($(this).data('id'));
            })

            $(document).on('click', '.option .detail-btn', function () {
                var str = $(this).html();
                if (str == '展开详情') {
                    $(this).html('收起详情');
                } else {
                    $(this).html('展开详情');
                }
                $(this).parent().parent().find(".detail-wrap").stop(true, true).slideToggle();
            })

            $(document).on('click', '.slide-down', function () {
                var str = $(this).parent().parent().parent().find('.detail-btn').html();
                if (str == '展开详情') {
                    $(this).parent().parent().parent().find('.detail-btn').html('收起详情');
                } else {
                    $(this).parent().parent().parent().find('.detail-btn').html('展开详情');
                }
                $(this).parent().parent().stop(true, true).slideToggle();
            })

            //点击重置按钮
            $(document).on('click', '.btn-default', function () {
                $('#increment_id').attr({ "value": "" });
                $('#customer_email').attr({ "value": "" });
                $('#customer_name').attr({ "value": "" });
                $('#customer_phone').attr({ "value": "" });
                $('#track_number').attr({ "value": "" });
            });
            //模糊匹配订单
            $('#increment_id').autocomplete({
                source: function (request, response) {
                    var incrementId = $('#increment_id').val();
                    var orderType = $('#order_platform').val();
                    if (incrementId.length > 2) {
                        $.ajax({
                            type: "POST",
                            url: "saleaftermanage/order_return/ajaxGetLikeOrder",
                            dataType: "json",
                            cache: false,
                            async: false,
                            data: {
                                orderType: orderType, order_number: incrementId
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
            //模糊匹配邮箱
            $('#customer_email').autocomplete({
                source: function (request, response) {
                    var customer_email = $('#customer_email').val();
                    var orderType = $('#order_platform').val();
                    if (customer_email.length > 2) {
                        $.ajax({
                            type: "POST",
                            url: "saleaftermanage/order_return/ajaxGetLikeEmail",
                            dataType: "json",
                            cache: false,
                            async: false,
                            data: {
                                orderType: orderType, email: customer_email
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
            //模糊匹配电话
            $('#customer_phone').autocomplete({
                source: function (request, response) {
                    var customer_phone = $('#customer_phone').val();
                    var orderType = $('#order_platform').val();
                    if (customer_phone.length > 2) {
                        $.ajax({
                            type: "POST",
                            url: "saleaftermanage/order_return/ajaxGetLikePhone",
                            dataType: "json",
                            cache: false,
                            async: false,
                            data: {
                                orderType: orderType, customer_phone: customer_phone
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
            //模糊匹配姓名
            $('#customer_name').autocomplete({
                source: function (request, response) {
                    var customer_name = $('#customer_name').val();
                    var orderType = $('#order_platform').val();
                    if (customer_name.length > 2) {
                        $.ajax({
                            type: "POST",
                            url: "saleaftermanage/order_return/ajaxGetLikeName",
                            dataType: "json",
                            cache: false,
                            async: false,
                            data: {
                                orderType: orderType, customer_name: customer_name
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
            //模糊匹配运单号
            $('#track_number').autocomplete({
                source: function (request, response) {
                    var track_number = $('#track_number').val();
                    var orderType = $('#order_platform').val();
                    if (track_number.length > 2) {
                        $.ajax({
                            type: "POST",
                            url: "saleaftermanage/order_return/ajaxGetLikeTrackNumber",
                            dataType: "json",
                            cache: false,
                            async: false,
                            data: {
                                orderType: orderType, track_number: track_number
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

            //模糊匹配交易号
            $('#transaction_id').autocomplete({
                source: function (request, response) {
                    var transaction_id = $('#transaction_id').val();
                    var orderType = $('#order_platform').val();
                    if (transaction_id.length > 2) {
                        $.ajax({
                            type: "POST",
                            url: "saleaftermanage/order_return/ajaxGetLikeTransaction",
                            dataType: "json",
                            cache: false,
                            async: false,
                            data: {
                                orderType: orderType, transaction_id: transaction_id
                            },
                            success: function (json) {
                                var data = json.data;
                                response($.map(data, function (item) {
                                    return {
                                        label: item,//下拉框显示值
                                        value: item//选中后，填充到input框的值
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

            //点击查看物流信息
            $(document).on('click', '.track-number', function () {
                var entity_id = $(this).data('id');
                var order_platform = $('#order_platform').val();
                var track_number = $(this).html();
                if (!entity_id || !order_platform || !track_number) {
                    Toastr.error('缺少参数');
                    return false;
                }
                Backend.api.open('saleaftermanage/order_return/get_logistics_info/?track_number=' + track_number + '&entity_id=' + entity_id + '&order_platform=' + order_platform, '查询物流信息', { area: ["60%", "60%"] });
            });


            //增加工单
            $(document).on('click', '.addOrder', function () {
                var incrementId = $(this).data('id');
                if (!incrementId) {
                    Toastr.error('缺少订单号');
                    return false;
                }
                Backend.api.open('saleaftermanage/work_order_list/add/?order_number=' + incrementId, '添加工单', { area: ["100%", "100%"] });

            })

            //增加工单
            $(document).on('click', '.machiningBtn', function () {
                var incrementId = $(this).data('id');
                var order_platform = $('#order_platform').val();
                if (!incrementId || !order_platform) {
                    Toastr.error('缺少参数');
                    return false;
                }
                Backend.api.open('saleaftermanage/order_return/machining/?order_number=' + incrementId + '&order_platform=' + order_platform, '配货记录', { area: ["60%", "600px"] });

            })


        }
    };
    return Controller;
});