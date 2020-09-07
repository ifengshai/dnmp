define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'jqui', 'fast', 'bootstrap-table-jump-to', 'template', 'editable', 'upload'], function ($, undefined, Backend, Table, Form, undefined, Fast, undefined, Template, undefined, Upload) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'new_product/index' + location.search,
                    add_url: 'new_product/add',
                    edit_url: 'new_product/edit',
                    detail_url: 'new_product/detail',
                    multi_url: 'new_product/multi',
                    table: 'new_product',
                }
            });

            var table = $("#table");

            Template.helper("Moment", Moment);

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'create_time',
                sortOrder: 'desc',
                // escape: false,
                templateView: true,
                //分页大小
                pageSize: 12,
                search: false,
                showExport: false,
                columns: [
                    [
                        { checkbox: true },
                        {
                            field: '', title: __('序号'), formatter: function (value, row, index) {
                                var options = table.bootstrapTable('getOptions');
                                var pageNumber = options.pageNumber;
                                var pageSize = options.pageSize;
                                return (pageNumber - 1) * pageSize + 1 + index;
                            }, operate: false
                        },
                        { field: 'id', title: __('Id'), operate: false, visible: false },
                        { field: 'sku', title: __('Sku'), operate: 'like' },
                        { field: 'category_name', title: __('分类名称'), operate: false },
                        {
                            field: 'category_id', title: __('Category_id'),
                            searchList: $.getJSON('itemmanage/item/ajaxGetItemCategoryList'),
                            formatter: Table.api.formatter.status, visible: false
                        },
                        { field: 'price', title: __('单价'), operate: false },
                        { field: 'sales_num', title: __('90天总销量'), operate: false },
                        { field: 'available_stock', title: __('可用库存'), operate: false },
                        // {
                        //     field: 'platform_plat', title: __('平台'), searchList: function (column) {
                        //         return Template('receptperson1tpl', {});
                        //     }, visible: false
                        // },
                        {
                            field: 'platform_type',
                            title: __('平台'),
                            custom: { 10: 'success', 1: 'success', 2: 'blue', 3: 'danger', 4: 'gray' },
                            searchList: {
                                10: '无',
                                1: 'zeelool',
                                2: 'voogueme',
                                3: 'nihao',
                                4: 'meeloog',
                                5: 'wesee',
                                8: 'amazon'
                            },

                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'name',
                            title: __('Name'),
                            operate: 'like',
                            cellStyle: formatTableUnit,
                            formatter: Controller.api.formatter.getClear
                        },
                        // { field: 'supplier.supplier_name', title: __('供应商名称'), operate: 'like' },
                        // { field: 'supplier_sku', title: __('供应商SKU'), operate: 'like' },
                        {
                            field: 'item_status', title: __('选品状态'),
                            custom: { 1: 'success', 2: 'blue', 3: 'danger', 4: 'gray', 0: 'red' },
                            searchList: { 0: '新建', 1: '待选品', 2: '选品通过', 3: '选品拒绝', 4: '已取消' },
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'newproductattribute.frame_remark',
                            title: __('选品备注'),
                            cellStyle: formatTableUnit,
                            formatter: Controller.api.formatter.getClear,
                            operate: false
                        },
                        { field: 'newproductattribute.frame_images', operate: false },
                        { field: 'create_person', title: __('Create_person') },
                        { field: 'create_time', title: __('Create_time'), operate: 'RANGE', addclass: 'datetimerange' },
                        {
                            field: 'available_stock',
                            title: __('可用库存'),
                            sortable: true,
                            operate: 'between',
                            formatter: Controller.api.formatter.int_format
                        },


                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [

                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: __('查看详情'),
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: Config.moduleurl + '/new_product/detail',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },
                                {
                                    name: 'edit',
                                    text: '编辑',
                                    title: __('Edit'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-pencil',
                                    url: Config.moduleurl + '/new_product/edit',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        //console.log(row.item_status);
                                        if (row.item_status == 1) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },

                            ],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

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

            //表格超出宽度鼠标悬停显示td内容
            function paramsMatter(value, row, index) {
                var span = document.createElement("span");
                span.setAttribute("title", value);
                span.innerHTML = value;
                return span.outerHTML;
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

            // 为表格绑定事件
            Table.api.bindevent(table);

            //点击详情
            $(document).on("click", ".btn-detail[data-id]", function () {
                Backend.api.open('new_product/detail/ids/' + $(this).data('id'), __('Detail'), { area: ['100%', '100%'] });
            });


            //商品审核通过
            $(document).on('click', '.btn-passAudit', function () {
                var ids = Table.api.selectedids(table);

                Backend.api.open('new_product/passAudit/ids/' + $(this).data('id') + '/sku/' + $(this).data('sku'), __('同步商品'), { area: ['35%', '35%'] });

                // Layer.confirm(
                //     __('确定要审核通过吗'),
                //     function (index) {
                //         Backend.api.ajax({
                //             url: "new_product/passAudit",
                //             data: { ids: ids }
                //         }, function (data, ret) {
                //             table.bootstrapTable('refresh');
                //             Layer.close(index);
                //         });
                //     }
                // );
            });

            //商品审核拒绝
            $(document).on('click', '.btn-auditRefused', function () {
                var ids = Table.api.selectedids(table);
                var idd = $(this).data('id');
                Layer.confirm(
                    __('确定要审核拒绝吗'),
                    function (index) {
                        Backend.api.ajax({
                            url: "new_product/auditRefused",
                            data: { ids: ids, idd: idd }
                        }, function (data, ret) {
                            table.bootstrapTable('refresh');
                            Layer.close(index);
                        });
                    }
                );
            });

            //商品审核取消
            $(document).on('click', '.btn-cancel', function () {
                var ids = $(this).data('id');
                Layer.confirm(
                    __('确定要取消吗'),
                    function (index) {
                        Backend.api.ajax({
                            url: "new_product/cancel",
                            data: { ids: ids }
                        }, function (data, ret) {
                            table.bootstrapTable('refresh');
                            Layer.close(index);
                        });
                    }
                );
            });

            //创建采购单
            $(document).on('click', '.btn-createPurchaseOrder', function () {
                var ids = Table.api.selectedids(table);
                Layer.confirm(
                    __('确定要创建采购单吗'),
                    function (index) {
                        Layer.closeAll();
                        var options = {
                            shadeClose: false,
                            shade: [0.3, '#393D49'],
                            area: ['100%', '100%'], //弹出层宽高
                            callback: function (value) {

                            }
                        };
                        Fast.api.open('purchase/purchase_order/add?new_product_ids=' + ids.join(','), '创建采购单', options);
                    }
                );
            });

            //批量导出xls 
            $('.btn-batch-export-xls').click(function () {
                var ids = Table.api.selectedids(table);
                if (ids.length > 0) {
                    window.open(Config.moduleurl + '/new_product/batch_export_xls?ids=' + ids, '_blank');
                } else {
                    var options = table.bootstrapTable('getOptions');
                    var search = options.queryParams({});
                    var filter = search.filter;
                    var op = search.op;
                    window.open(Config.moduleurl + '/new_product/batch_export_xls?filter=' + filter + '&op=' + op, '_blank');
                }

            });
        },
        add: function () {
            Controller.api.bindevent();
            $(document).on('click', '.btn-ok', function () {
                $('#status').val(0);
            })
            Form.api.bindevent($("form[role=form]"));

            //采集1688商品信息
            $(document).on('click', '.btn-caiji', function () {
                var link = $('#c-link').val();
                var label = $('#c-link').attr('label');

                var categoryId = $('#choose_category_id').val();
                if (categoryId == 0) {
                    return false;
                }


                if (!link) {
                    Layer.alert('请先填写商品链接！！');
                    return false;
                }
                Layer.load();
                Backend.api.ajax({
                    url: 'new_product/ajaxCollectionGoodsDetail',
                    data: { link: link, categoryId: categoryId }
                }, function (data, ret) {

                    Layer.closeAll();
                    //循环展示商品信息
                    var shtml = ' <tr><th>商品名称</th><th>商品颜色</th><th>供应商SKU</th><th>单价</th><th>操作</th></tr>';
                    $('.caigou table tbody').html('');

                    if (label == 1) {
                        for (var i in data.list) {
                            shtml += '<tr><td><input id="c-name" class="form-control c-name" name="row[name][]" value="' + data.list[i].title + '" type="text"></td>'
                            shtml += '<td><input id="c-color" class="form-control" name="row[color][]" value="' + data.list[i].color + '" type="text"></td>'

                            shtml += '<td><input id="c-supplier_sku" class="form-control" name="row[supplier_sku][]" value="' + data.list[i].cargoNumber + '" type="text"></td>'
                            shtml += '<td><input id="c-price" class="form-control" name="row[price][]" value="' + data.list[i].price + '" type="text"></td>'
                            shtml += '<td><a href="javascript:;" class="btn btn-danger btn-del" title="删除"><i class="fa fa-trash"></i> 删除</a></td>'
                            shtml += '<input  class="form-control" name="row[skuid][]" value="' + data.list[i].skuId + '" type="hidden">'
                            shtml += '</tr>'
                        }
                    } else {
                        for (var i in data.list) {
                            shtml += '<tr><td><input id="c-name" class="form-control c-name" name="row[name][]" value="' + data.list[i].title + '" type="text"></td>'
                            shtml += '<td><div class="col-xs-12 col-sm-12">';
                            shtml += '<select  id="c-color" data-rule="required" class="form-control " name="row[color][]" >';
                            for (var z in data.colorResult) {
                                shtml += '<option value="' + data.colorResult[z] + '">' + data.colorResult[z] + '</option>';
                            }
                            shtml += '</select></td>';
                            shtml += '<td><input id="c-supplier_sku" class="form-control" name="row[supplier_sku][]" value="' + data.list[i].cargoNumber + '" type="text"></td>'
                            shtml += '<td><input id="c-price" class="form-control" name="row[price][]" value="' + data.list[i].price + '" type="text"></td>'
                            shtml += '<td><a href="javascript:;" class="btn btn-danger btn-del" title="删除"><i class="fa fa-trash"></i> 删除</a></td>'
                            shtml += '<input  class="form-control" name="row[skuid][]" value="' + data.list[i].skuId + '" type="hidden">'
                            shtml += '</tr>'
                        }
                    }

                    $('.caigou table tbody').append(shtml);

                }, function (data, ret) {
                    //失败的回调
                    Layer.closeAll();
                    return false;
                });
            })
        },
        edit: function () {
            Controller.api.bindevent();
            $(document).on('click', '.btn-ok', function () {
                console.log($('#status').val())
                $('#status').val(0);
            });
            $(document).on('click', '.btn-submit', function () {
                console.log($('#status').val())
                $('#status').val(1);
            })

            Form.api.bindevent($("form[role=form]"));
        },
        detail: function () {
            Controller.api.bindevent();
        },
        passaudit: function () {
            Controller.api.bindevent();
        },
        replenishescalationlist: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'new_product/replenishescalationlist' + location.search + '&label=' + Config.label,
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
                        { field: 'purchase_price', title: __('采购成本'), operate: false },
                        { field: 'category_name', title: __('商品分类'), operate: false },
                        {
                            field: 'category_id', title: __('Category_id'),
                            searchList: $.getJSON('itemmanage/item/ajaxGetItemCategoryList'),
                            formatter: Table.api.formatter.status, visible: false
                        },
                        {
                            field: 'available_stock',
                            title: __('可用库存'),
                            sortable: true,
                            operate: 'between',
                            formatter: Controller.api.formatter.int_format
                        },
                        { field: 'grade', title: __('销量等级'), operate: false, },
                        { field: 'product_cycle', title: '生产周期', operate: false },
                        { field: 'available_stock', title: '可用库存', operate: false, visible: false },
                        { field: 'stock', title: '虚拟仓库存', operate: false },
                        { field: 'on_way_stock', title: '在途库存', operate: false },
                        { field: 'wait_in_num', title: '待入库数量', operate: false },
                        { field: 'sales_num_15days', title: '过去15天日均销量', operate: false },
                        { field: 'sales_num_90days', title: '90天总销量', operate: false },
                        { field: 'sales_days', title: '预估售卖天数', operate: false },
                        // {field: 'replenish_num', title: '建议补货量', operate: false},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [

                                {
                                    name: 'detail',
                                    text: '加入计划补货清单',
                                    title: __('加入计划补货清单'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-pencil',
                                    url: Config.moduleurl + '/new_product/addReplenishOrder/type/1',
                                    extend: 'data-area = \'["40%","40%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },

                                {
                                    name: 'detail',
                                    text: '加入紧急补货清单',
                                    title: __('加入紧急补货清单'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-pencil',
                                    url: Config.moduleurl + '/new_product/addReplenishOrder/type/2',
                                    extend: 'data-area = \'["40%","40%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },

                            ],
                            formatter: Table.api.formatter.operate
                        }

                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            //选项卡切换
            $('.panel-heading a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var field = $(this).data("field");
                var value = $(this).data("value");
                var options = table.bootstrapTable('getOptions');
                options.pageNumber = 1;
                var queryParams = options.queryParams;
                options.queryParams = function (params) {
                    var params = queryParams(params);
                    var filter = params.filter ? JSON.parse(params.filter) : {};
                    var op = params.op ? JSON.parse(params.op) : {};
                    filter[field] = value;
                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    return params;
                };
                table.bootstrapTable('refresh', {});
                return false;
            });

        },
        productmappinglist: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'new_product/productmappinglist' + location.search + '&label=' + Config.label,
                    del_url: 'new_product/replenish_cart_del',
                    edit_url: 'new_product/mappingedit',
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
                        {
                            field: '', title: __('序号'), formatter: function (value, row, index) {
                                var options = table.bootstrapTable('getOptions');
                                var pageNumber = options.pageNumber;
                                var pageSize = options.pageSize;
                                return (pageNumber - 1) * pageSize + 1 + index;
                            }, operate: false
                        },
                        { field: 'sku', title: __('Sku'), operate: 'like' },
                        { field: 'category_name', title: __('商品分类'), operate: false },
                        {
                            field: 'category_id', title: __('Category_id'),
                            searchList: $.getJSON('itemmanage/item/ajaxGetItemCategoryList'),
                            formatter: Table.api.formatter.status, visible: false
                        },
                        {
                            field: 'type', title: __('类型'), custom: { 1: 'success', 2: 'danger' },
                            searchList: { 1: '计划补货', 2: '紧急补货' },
                            formatter: Table.api.formatter.status
                        },

                        {
                            field: 'replenish_num', title: __('补货需求数量'), operate: false, editable: {
                                emptytext: "__", validate: function (value) { //字段验证
                                    // if (!/[1-9]+\d*/.test(value)) {
                                    if (!/^[1-9]\d*$/.test(value)) {
                                        return '请输入正整数';
                                    }
                                }
                            }
                        },
                        // {
                        //     field: 'operate',
                        //     title: __('Operate'),
                        //     table: table,
                        //     events: Table.api.events.operate,
                        //     formatter: Table.api.formatter.operate
                        // },
                        {
                            field: 'operate', title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: function (value, row, index) {
                                var that = $.extend({}, this);
                                $(table).data("operate-edit", null); // 列表页面隐藏 .编辑operate-edit - 删除按钮operate-del
                                that.table = table;
                                return Table.api.formatter.operate.call(that, value, row, index);
                            }
                        }

                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            table.bootstrapTable('getOptions').onEditableSave = function (field, row, oldValue, $el) {
                var data = {};
                data["row[" + field + "]"] = row[field];
                Fast.api.ajax({
                    url: this.extend.edit_url + "/ids/" + row[this.pk],
                    data: data
                }, function (data) {

                    table.bootstrapTable('refresh');
                })
            }

            $('.btn-edit').hide();
            //选项卡切换
            $('.panel-heading a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var field = $(this).data("field");
                var value = $(this).data("value");
                var options = table.bootstrapTable('getOptions');
                options.pageNumber = 1;
                var queryParams = options.queryParams;
                options.queryParams = function (params) {
                    var params = queryParams(params);
                    var filter = params.filter ? JSON.parse(params.filter) : {};
                    var op = params.op ? JSON.parse(params.op) : {};
                    filter[field] = value;
                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    return params;
                };
                table.bootstrapTable('refresh', {});
                return false;
            });
            $(document).on('click', '.btn-replenish_add', function () {
                var ids = Table.api.selectedids(table);
                var label = $('.panel-heading li.active a[data-toggle="tab"]').data("value");
                Layer.confirm(
                    __('确定要创建紧急补货需求单吗？'),
                    function (index) {
                        Layer.closeAll();
                        var options = {
                            shadeClose: false,
                            shade: [0.3, '#393D49'],
                            area: ['100%', '100%'], //弹出层宽高
                            callback: function (value) {

                            }
                        };
                        // Fast.api.open('purchase/purchase_order/add?new_product_ids=' + ids.join(','), '创建采购单', options);
                        Fast.api.open('new_product/emergency_replenishment?label=' + label, '创建紧急补货需求单', options);


                    }
                );
            });

        },
        productmappinglisthistory: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'new_product/productmappinglisthistory' + location.search + '&label=' + Config.label,
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'a.id',
                columns: [
                    [
                        // { checkbox: true },
                        {
                            field: '', title: __('序号'), formatter: function (value, row, index) {
                                var options = table.bootstrapTable('getOptions');
                                var pageNumber = options.pageNumber;
                                var pageSize = options.pageSize;
                                return (pageNumber - 1) * pageSize + 1 + index;
                            }, operate: false
                        },
                        { field: 'sku', title: __('Sku'), operate: 'like' },

                        {
                            field: 'type', title: __('类型'), custom: { 1: 'success', 2: 'danger' },
                            searchList: { 1: '计划补货', 2: '紧急补货' },
                            formatter: Table.api.formatter.status
                        },
                        // {field: 'create_person', title: __('Create_person')},
                        { field: 'create_time', title: __('需求提报时间'), operate: 'RANGE', addclass: 'datetimerange' },
                        { field: 'replenish_num', title: __('补货需求数量'), operate: false },
                        {
                            field: 'status', title: __('需求分配状态'), custom: { 1: 'success', 2: 'danger', 3: 'success', 4: 'success' },
                            searchList: { 1: '待分配', 2: '待处理', 3: '部分处理', 4: '已处理' }, operate: false,
                            formatter: Table.api.formatter.status
                        },
                        { field: 'real_dis_num', title: __('实际采购数量'), operate: false },
                        { field: 'arrival_time', title: __('预计到货时间'), operate: false },
                        { field: 'purchase_number', title: __('采购单号'), operate: false },
                        {
                            field: 'purchase_status', title: __('采购状态'),
                            custom: {
                                0: 'success',
                                1: 'yellow',
                                2: 'blue',
                                3: 'danger',
                                4: 'gray',
                                5: 'yellow',
                                6: 'yellow',
                                7: 'success',
                                8: 'success',
                                9: 'success',
                                10: 'success'
                            },
                            searchList: {
                                0: '新建',
                                1: '待审核',
                                2: '已审核',
                                3: '已拒绝',
                                4: '已取消',
                                5: '待发货',
                                6: '待收货',
                                7: '已收货',
                                8: '已退款',
                                9: '部分签收',
                                10: '已完成'
                            },
                            operate: false,
                            formatter: Controller.api.formatter.status
                        },
                        { field: 'real_dis_num', title: __('预计到货数量'), operate: false },
                        { field: 'arrivals_num', title: __('到货数量'), operate: false },
                        { field: 'quantity_num', title: __('质检合格数量'), operate: false },
                        { field: 'in_stock_num', title: __('入库数量'), operate: false },
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [

                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: __('Detail'),
                                    classname: 'btn btn-xs  btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'new_product/productmappingdetail/purchase_id/{purchase_id}',
                                    extend: 'data-area = \'["60%","60%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },

                            ],
                            formatter: Table.api.formatter.operate
                        }

                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            //选项卡切换
            $('.panel-heading a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var field = $(this).data("field");
                var value = $(this).data("value");
                var options = table.bootstrapTable('getOptions');
                options.pageNumber = 1;
                var queryParams = options.queryParams;
                options.queryParams = function (params) {
                    var params = queryParams(params);
                    var filter = params.filter ? JSON.parse(params.filter) : {};
                    var op = params.op ? JSON.parse(params.op) : {};
                    filter[field] = value;
                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    return params;
                };
                table.bootstrapTable('refresh', {});
                return false;
            });

        },
        productmappingdetail: function () {
            // 初始化表格参数配置
            Table.api.init({
                commonSearch: false,
                search: false,
                showExport: false,
                showColumns: false,
                showToggle: false,
                pagination: false,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'new_product/productmappingdetail' + location.search + '&purchase_id=' + Config.purchase_id,
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'a.id',
                columns: [
                    [
                        { field: 'id', title: __('批次'), operate: false },
                        { field: 'arrival_time', title: __('预计到货时间'), operate: false },
                        { field: 'wait_arrival_num', title: __('预计到货数量'), operate: false },
                        { field: 'arrivals_num', title: __('到货数量'), operate: false },
                        {
                            field: 'status', title: __('质检状态'), custom: { 0: 'success', 1: '待审核', 2: 'success', 3: 'danger', 4: 'gray' },
                            searchList: { 0: '新建', 1: '待审核', 2: '已审核', 3: '已拒绝', 4: '已取消' }, operate: false,
                            formatter: Controller.api.formatter.status
                        },
                        { field: 'quantity_num', title: __('质检合格数量'), operate: false },
                        {
                            field: 'instock_status', title: __('入库状态'), custom: { 0: 'success', 1: '待审核', 2: 'success', 3: 'danger', 4: 'gray' },
                            searchList: { 0: '新建', 1: '待审核', 2: '已审核', 3: '已拒绝', 4: '已取消' }, operate: false,
                            formatter: Controller.api.formatter.status
                        },
                        { field: 'in_stock_num', title: __('入库数量'), operate: false }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

        },
        addreplenishorder: function () {
            Controller.api.bindevent();
        },
        api: {

            formatter: {
                status: function (value, row, index) {
                    var custom = {normal: 'success', hidden: 'gray', deleted: 'danger', locked: 'info'};
                    if (typeof this.custom !== 'undefined') {
                        custom = $.extend(custom, this.custom);
                    }
                    this.custom = custom;
                    this.icon = 'fa '; //更改这里图标css
                    if (value) {
                        return Table.api.formatter.normal.call(this, value, row, index);
                    } else {
                        return '-';
                    }
                    
                },
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
                int_format: function (value, row, index) {
                    return parseInt(value);
                },

            },
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));

                //多项添加商品名称和颜色
                $(document).on('click', '.btn-add', function () {
                    $(".selectpicker").selectpicker('refresh');
                    var content = $('#table-content table tbody').html();
                    //console.log(content);
                    $('.caigou table tbody').append(content);
                    // Form.api.bindevent($("form[role=form]"));
                });
                $(document).on('click', '.btn-del', function () {
                    $(this).parent().parent().remove();
                });
                //根据商品分类的不同请求不同属性页面
                $(document).on('change', '#choose_category_id', function () {
                    var categoryId = $('#choose_category_id').val();
                    if (categoryId == 0) {
                        return false;
                    }
                    Backend.api.ajax({
                        url: 'new_product/ajaxCategoryInfo',
                        data: { categoryId: categoryId }
                    }, function (data, ret) {
                        var resultData = ret.data;
                        $('.ajax-add').remove();
                        $('.upload-photo').remove();
                        //console.log(resultData);
                        $('#item-stock').after(resultData);
                        Form.api.bindevent($("form[role=form]"));
                        $(".selectpicker").selectpicker('refresh');

                        return false;
                    }, function (data, ret) {
                        //失败的回调
                        Layer.alert(ret.msg);
                        return false;
                    });
                });
                //采购类型和采购产地二级联动
                $(document).on('change', '#c-procurement_type', function () {
                    var arrIds = $(this).val();
                    if (arrIds == 0) {
                        return false;
                    }
                    //线上采购
                    if (arrIds == 1) {
                        $('#c-procurement_origin').html('');
                        var str = '<option value="O">线上采购</option>';
                        $('#c-procurement_origin').append(str);
                        $("#c-procurement_origin").selectpicker('refresh');
                    } else {
                        Backend.api.ajax({
                            url: 'new_product/ajaxGetProOrigin',
                        }, function (data, ret) {
                            var rs = ret.data;
                            var r;
                            $('#c-procurement_origin').html('');
                            var str = '';
                            for (r in rs) {
                                str += '<option value="' + r + '">' + rs[r] + '</option>';
                            }
                            $('#c-procurement_origin').append(str);
                            $("#c-procurement_origin").selectpicker('refresh');
                        }, function (data, ret) {

                        });
                    }
                });
                //模糊匹配原始sku
                $('#c-origin_skus').autocomplete({

                    source: function (request, response) {
                        var origin_sku = $('#c-origin_skus').val();
                        console.log(origin_sku);
                        $.ajax({
                            type: "POST",
                            url: "new_product/ajaxGetLikeOriginSku",
                            dataType: "json",
                            cache: false,
                            async: false,
                            data: {
                                origin_sku: origin_sku
                            },
                            success: function (json) {
                                var data = json.data;
                                console.log(data);
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
                //根据选择的sku找出关于sku的商品
                $(document).on('change', '#c-origin_skus', function () {
                    var categoryId = $('#choose_category_id').val();
                    var sku = $('#c-origin_skus').val();
                    if (categoryId == 0) {
                        Layer.alert('请先选择商品分类');
                        return false;
                    }
                    Backend.api.ajax({
                        url: 'new_product/ajaxItemInfo',
                        data: { categoryId: categoryId, sku: sku }
                    }, function (data, ret) {
                        var resultData = ret.data;
                        if (resultData != false) {
                            $('.ajax-add').remove();
                            $('.upload-photo').remove();
                            $('#item-stock').after(resultData);
                            Form.api.bindevent($("form[role=form]"));
                            $(".selectpicker").selectpicker('refresh');

                            return false;


                        }
                        return false;
                    }, function (data, ret) {
                        //失败的回调
                        Layer.alert(ret.msg);
                        return false;
                    });
                });

            }
        },
        frame: function () {
            Controller.api.bindevent();
            Form.api.bindevent($("form[role=form]"));
        },

    };
    return Controller;
});