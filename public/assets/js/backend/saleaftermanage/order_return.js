define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'jqui'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                extend: {
                    index_url: 'saleaftermanage/order_return/index' + location.search,
                    add_url: 'saleaftermanage/order_return/add',
                    edit_url: 'saleaftermanage/order_return/edit',
                    del_url: 'saleaftermanage/order_return/del',
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
                alert(problem_desc);
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
                        { field: 'id', title: __('Id'), operate: false },
                        { field: 'return_order_number', title: __('Return_order_number') },
                        { field: 'increment_id', title: __('Increment_id') },
                        { field: 'order_platform', title: __('Order_platform'), searchList: $.getJSON('saleaftermanage/sale_after_task/getAjaxOrderPlatformList'), formatter: Controller.api.formatter.devicess },
                        { field: 'customer_email', title: __('Customer_email') },
                        { field: 'sale_after_issue.name', title: __('Issue_id'), operate: false },
                        { field: 'return_remark', title: __('Return_remark'), formatter: Controller.api.formatter.getClear, operate: false },
                        { field: 'status', title: __('Status'), searchList: { 'new': 'new', 'arrival': 'arrival', 'check': 'check', 'stock': 'stock', 'refund': 'refund', 'closed': 'closed' } },
                        { field: 'create_person', title: __('Create_person') },
                        { field: 'create_time', title: __('Create_time'), operate: 'RANGE', addclass: 'datetimerange' },
                        // {field: 'customer_name', title: __('Customer_name')},
                        // {field: 'return_shipping_number', title: __('Return_shipping_number')},
                        // {field: 'check_person', title: __('Check_person')},
                        // {field: 'check_remark', title: __('Check_remark')},
                        // {field: 'check_created_time', title: __('Check_created_time'), operate:'RANGE', addclass:'datetimerange'},
                        // {field: 'stock_person', title: __('Stock_person')},
                        // {field: 'stock_created_time', title: __('Stock_created_time'), operate:'RANGE', addclass:'datetimerange'},
                        // {field: 'refund_amount', title: __('Refund_amount'), operate:'BETWEEN'},
                        // {field: 'refund_person', title: __('Refund_person')},
                        // {field: 'refund_created_time', title: __('Refund_created_time'), operate:'RANGE', addclass:'datetimerange'},
                        // {field: 'final_loss_amount', title: __('Final_loss_amount'), operate:'BETWEEN'},
                        // {field: 'is_visable', title: __('Is_visable')},
                        //{field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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
                                {
                                    name: 'refund',
                                    text: '退款',
                                    title: __('Refund'),
                                    classname: 'btn btn-xs btn-primary btn-ajax',
                                    icon: 'fa fa-money',
                                    confirm: '确定要退款吗',
                                    url: 'saleaftermanage/order_return/refund',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }

                                },
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
                options.queryParams = function (params) {
                    var filter = {};
                    if (value !== '') {
                        filter[field] = value;
                    }
                    params.filter = JSON.stringify(filter);
                    return params;
                };
                table.bootstrapTable('refresh', {});
                return false;
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
                        $(".item_info").empty();
                        $('#c-order_status').val(ret.data.status);
                        $('#c-customer_name').val(ret.data.customer_firstname + " " + ret.data.customer_lastname);
                        $('#c-customer_email').val(ret.data.customer_email);
                        if (ret.data.store_id >= 2) {
                            $('#c-order_source').val(2);
                        } else {
                            $('#c-order_source').val(1);
                        }
                        var item = ret.data.item;
                        $('#customer_info').after(function () {
                            var Str = '';
                            Str += '<div class="row item_info" style="margin-top:15px;margin-left:15%;" >' +
                                '<div class="col-lg-12">' +
                                '</div>' +
                                '<div class="col-xs-6 col-md-12">' +
                                '<div class="col-xs-6 col-md-10">' +
                                '<div class="panel bg-aqua-gradient">' +
                                '<div class="panel-body">' +
                                '<div class="ibox-title">' +
                                '<table id="caigou-table" class="col-xs-6 col-md-12">' +
                                '<tr>' +
                                '<td style="text-align: center">序号</td>' +
                                '<td style="text-align: center">SKU</td>' +
                                '<td style="text-align: center" class="col-xs-6 col-md-6">商品名称</td>' +
                                '<td style="text-align: center" >购买数量</td>' +
                                '<td style="text-align: center">退回数量</td>' +
                                '<td style="text-align: center">到货数量</td>' +
                                '<td style="text-align: center">质检合格数量</td>' +
                                '</tr>';
                            for (var j = 0, len = item.length; j < len; j++) {
                                var newItem = item[j];
                                var m = j + 1;
                                Str += '<tr>';
                                //Str +='<input type="hidden" id="c-order_id" name="row[]"> ';
                                Str += '<td><input id="c-right_SPH" class="form-control"  type="text" value="' + m + '"></td>';
                                Str += '<td><input id="c-sku" class="form-control"  name="row[item][' + m + '][item_sku]" type="text" value="' + newItem.sku + '"></td>';
                                Str += '<td><input id="c-name" class="form-control" name="row[item][' + m + '][item_name]" type="text" value="' + newItem.name + '"></td>';
                                Str += '<td><input id="c-qty_ordered" class="form-control" name="row[item][' + m + '][sku_qty]"  type="text" value="' + newItem.qty_ordered + '"></td>';
                                Str += '<td><input id="c-right_PD" class="form-control" name="row[item][' + m + '][return_sku_qty]" type="text" value="0"></td>';
                                Str += '<td><input id="c-right_Prism_Horizontal" name="row[item][' + m + '][arrived_sku_qty]" class="form-control"  type="text" value="0"></td>';
                                Str += '<td><input id="c-return_sku_qty" name="row[item][' + m + '][check_sku_qty]" class="form-control"  type="number" value="0"></td>';
                                Str += '</tr>';
                            }
                            Str += '</table>' +
                                '</div>' +
                                '</div>' +
                                '</div>' +
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
                window.top.location.href = 'admin/saleaftermanage/order_return/search';
            });
            // var incrementId = $('#increment_id').val();
            // var orderType = $('#c-order_platform').val();
            var availableTags = [
                'ActionScript',
                'AppleScript',
                'Asp',
                'BASIC',
                'C',
                'C++',
                'Clojure',
                'COBOL',
                'ColdFusion',
                'Erlang',
                'Fortran',
                'Groovy',
                'Haskell',
                'Java',
                'JavaScript',
                'Lisp',
                'Perl',
                'PHP',
                'Python',
                'Ruby',
                'Scala',
                'Scheme'
            ];
            $('#increment_id').autocomplete({
                source: availableTags
            });
           
            

            // var array_tags=['boat', 'bear', 'dog', 'drink', 'elephant', 'fruit'];
            //     $("#increment_id").autocomplete({
            //         serviceUrl: "${pageContext.request.contextPath}/numGroupInfo/getAllnumgroupInfoJson.do",
            //         width: 150,//提示框的宽度
            //         delimiter: /(,|;)\s*/,//分隔符
            //         deferRequestBy: 0, //单位微秒
            //         zIndex: 9999,
            //         noCache: false,//是否启用缓存 默认是开启缓存的
            //         onSelect: function (suggestions) {
            //             array_tags;
            //         }
            //     });
            // $(document).on('keypress','#increment_id',function(data){
            //         var incrementId = $('#increment_id').val();
            //         var orderType = $('#c-order_platform').val();
            //     $("#increment_id").autocomplete(
            //         {
            //             source: function(request, response) {
            //                 $.ajax({
            //                     url: "saleaftermanage/order_return/ajaxGetLikeOrder",
            //                     dataType: "json",
            //                     data:{orderType:orderType,order_number:incrementId},
            //                     success: function(data) {
            //                         console.log(data);
            //                         response(data);
            //                     },
            //                 });
            //             }
            //         }
            //     );
            // });
            // $(document).on('keypress','#increment_id',function(){
            //     var incrementId = $('#increment_id').val();
            //     var orderType = $('#c-order_platform').val();
            //     console.log(orderType);
            //     if(orderType<=0){
            //         Layer.alert('请选择正确的平台');
            //         return false;
            //     }
            //     if(incrementId.length>=3){
            //         Backend.api.ajax({
            //             url:'saleaftermanage/order_return/ajaxGetLikeOrder',
            //             data:{orderType:orderType,order_number:incrementId}
            //         }, function(data, ret){
            //
            //             console.log(ret.data);
            //         }, function(data, ret){
            //             //失败的回调
            //             alert(ret.msg);
            //             console.log(ret);
            //             return false;
            //         });
            //     }
            // });
        }
    };
    return Controller;
});