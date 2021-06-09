define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    function viewTable(table, value) {
        //隐藏、显示搜索及按钮
        $('#responsible_id').parents('.form-group').hide();
        $('#export_guanlian').hide();
        $('select[name="status"]').parents('.form-group').hide();
        if (0 == value) {
            $('select[name="status"]').parents('.form-group').show();
            table.bootstrapTable('hideColumn', 'responsible_id');
            $('#export_guanlian').show();
        } else if (1 == value) {
            $('select[name="status"]').parents('.form-group').show();
        } else if (2 == value) {
            $('select[name="status"]').parents('.form-group').show();
        } else if (3 == value) {
            $('select[name="status"]').parents('.form-group').show();
        } else if (4 == value) {
            $('select[name="status"]').parents('.form-group').show();
        } else if (5 == value) {
            $('#responsible_id').parents('.form-group').show();
        } else if (6 == value) {
            $('#responsible_id').parents('.form-group').show();
        } else if (7 == value) {
            $('#responsible_id').parents('.form-group').show();
            $('#site').parents('.form-group').show();
        }else{
            $('select[name="status"]').parents('.form-group').show();
            $('#export_guanlian').show();
        }
    }

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'new_product_design/index' + location.search,
                    add_url: 'new_product_design/add',
                    detail_url: 'new_product_design/detail',
                    del_url: 'new_product_design/del',
                    multi_url: 'new_product_design/multi',
                    table: 'new_product_design',
                }
            });

            var table = $("#table");
            $('.panel-heading .nav-tabs li a').on('click', function (e) {
                var field = $(this).data("field");
                var value = $(this).data("value");

                var options = table.bootstrapTable('getOptions');
                options.pageNumber = 1;
                var queryParams = options.queryParams;
                options.queryParams = function (params) {
                    var params = queryParams(params);
                    var filter = params.filter ? JSON.parse(params.filter) : {};
                    var op = params.op ? JSON.parse(params.op) : {};
                    if (field == '') {
                        delete filter.label;
                    } else {
                        filter[field] = value;
                    }
                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    return params;
                };
                table.bootstrapTable('refresh', {});
            });

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                search: false,
                showToggle:false,
                cardView: false,
                searchFormVisible: true,
                showExport:true,
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('序号'),operate: false},
                        {field: 'sku', title: __('Sku')},
                        {field: 'zeelool', title: __('Z虚拟仓库存'),operate: false,visible: false},
                        {field: 'voogueme', title: __('V虚拟仓库存'),operate: false,visible: false},
                        {field: 'meeloog', title: __('M虚拟仓库存'),operate: false,visible: false},
                        {field: 'vicmoo', title: __('Vicmoo虚拟仓库存'),operate: false,visible: false},
                        {field: 'wesee', title: __('W虚拟仓库存'),operate: false,visible: false},
                        {field: 'amazon', title: __('amazon虚拟仓库存'),operate: false,visible: false},
                        {field: 'zeelool_es', title: __('Es虚拟仓库存'),operate: false,visible: false},
                        {field: 'zeelool_de', title: __('De虚拟仓库存'),operate: false,visible: false},
                        {field: 'zeelool_jp', title: __('Jp虚拟仓库存'),operate: false,visible: false},
                        {field: 'voogmechic', title: __('chic虚拟仓库存'),operate: false,visible: false},
                        {field: 'zeelool_cn', title: __('z_cn虚拟仓库存'),operate: false,visible: false},
                        {field: 'alibaba', title: __('alibaba虚拟仓库存'),operate: false,visible: false},
                        {field: 'zeelool_fr', title: __('Fr虚拟仓库存'),operate: false,visible: false},
                        {field: 'category', title: __('商品分类'),operate: false},
                        {
                            field: 'item_status', title: __('商品状态'),
                            searchList: { 1: '新建', 2: '待审核', 3: '审核通过', 4: '待分配', 5: '审核拒绝', 6: '取消'},
                            custom: { 1: 'black', 2: 'red', 3: 'blue', 4: 'black', 5: 'black', 6: 'black'},
                            formatter: Table.api.formatter.status,
                        },
                        {
                            field: 'is_new', title: __('是否新品'),
                            searchList: { 1: '是', 2: '否'},
                            custom: { 1: 'black', 2: 'red'},
                            formatter: Table.api.formatter.status,
                        },
                        {
                            field: 'status',
                            addclass: 'design_status',
                            title: __('状态'),
                            searchList: {
                                1: '待录尺寸',
                                2: '待拍摄',
                                3: '拍摄中',
                                4: '待分配',
                                5: '待修图',
                                6: '修图中',
                                7: '待审核',
                                8: '已完成',
                                9: '审核拒绝',
                                10: '完成'
                            },
                            custom: {
                                1: 'black',
                                2: 'black',
                                3: 'black',
                                4: 'black',
                                5: 'black',
                                6: 'black',
                                7: 'black',
                                8: 'black',
                                9: 'black',
                                10: 'black',
                                11: 'black'
                            },

                            formatter: Table.api.formatter.status,
                        },
                        {field: 'responsible_id', title: __('责任人')},
                        {field: 'location_code', title: __('样品间库位号'), operate: false},
                        {field: 'platform', title: __('站点'), operate: false, visible: true},
                        {
                            field: 'site', title: __('站点'), visible: false,
                            addclass: 'plat_type selectpicker',
                            data: 'multiple',
                            operate: 'IN',
                            searchList: {
                                1: 'zeelool', 2: 'voogueme', 3: 'meeloog', 4: 'vicmoo', 5: 'wesee',
                                8: 'amazon', 9: 'zeelool_es', 10: 'zeelool_de', 11: 'zeelool_jp',
                                12: 'voogmechic', 13: 'zeelool_cn', 14: 'alibaba', 15: 'zeelool_fr'
                            },
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'addtime',
                            title: __('操作时间'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime,
                            sortable: true
                        },
                        {
                            field: 'create_time',
                            title: __('创建时间'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            sortable: true,
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [

                                {
                                    name: 'edit_recipient',
                                    text: __('录尺寸'),
                                    title: __('录尺寸'),
                                    extend: 'data-area = \'["50%","50%"]\'',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'new_product_design/record_size',
                                    icon: '',
                                    area: ['50%', '45%'],
                                    //extend: 'data-area = \'["100%","100%"]\' target=\'_blank\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        if (row.status ==1 && row.label !==0){
                                            return  true;
                                        }else{
                                            return false;
                                        }

                                    }
                                },
                                {
                                    name: 'detail',
                                    text:'查看详情',
                                    title:__('查看详情'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'new_product_design/detail',
                                    area: ['80%', '65%'],
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.label ==0 || row.label ==7){
                                            return  true;
                                        }else{
                                            return  false;
                                        }
                                    }
                                },
                                {
                                    name: 'start_shooting',
                                    text: '开始拍摄',
                                    title: __('开始拍摄'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'new_product_design/shooting?status=3',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh', {});
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    error: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function (row) {
                                        if (row.status ==2 && row.label !==0){
                                            return  true;
                                        }else{
                                            return false;
                                        }
                                    }
                                },

                                {
                                    name: 'shot_over',
                                    text: '拍摄完成',
                                    title: __('拍摄完成'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'new_product_design/shooting?status=4',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh', {});
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    error: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function (row) {
                                        if (row.status ==3 && row.label !==0){
                                            return  true;
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'distr_user',
                                    text:'分配',
                                    title:__('分配人员'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: '',
                                    url: 'new_product_design/allocate_personnel?ids={row.id}',
                                    area: ['30%', '20%'],
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        if (row.status ==4 && row.label !==0){
                                            return  true;
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'distr_user_change',
                                    text:'更换设计师',
                                    title:__('更换设计师'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: '',
                                    url: 'new_product_design/change_designer?ids={row.id}',
                                    area: ['30%', '20%'],
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        if (row.status ==5 && row.label !==0 || row.label ==6){
                                            return  true;
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'detail',
                                    text: '操作记录',
                                    title: __('操作记录'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'new_product_design/operation_log',
                                    extend: 'data-area = \'["60%","50%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {
                                            title: "回传数据"
                                        });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.label ==0){
                                            return  true;
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'tarted_making',
                                    text: '开始制作',
                                    title: __('开始制作'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'new_product_design/making?status=6',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh', {});
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    error: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function (row) {
                                        if (row.status ==5 && row.label !==0){
                                            return  true;
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'upload_pictures',
                                    text: __('上传图片'),
                                    title: __('上传图片'),
                                    extend: 'data-area = \'["100%", "100%"]\' data-shade = \'[0.3, "#393D49"]\'',
                                    icon: 'fa fa-plus',
                                    classname: 'btn btn-xs btn-info btn-dialog',
                                    url: 'new_product_design/add_img',
                                    visible: function (row) {
                                        if (row.status ==6 && row.label !==0){
                                            return  true;
                                        }else{
                                            return false;
                                        }
                                    },
                                    callback: function (data) {
                                    }
                                },
                                {
                                    name: 'approved',
                                    text:__('审核通过'),
                                    title:__('审核通过'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'new_product_design/review_the_operation?status=8',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh', {});
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    error: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function (row) {
                                        if (row.status ==7 && row.label !==0){
                                            return  true;
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'review_the_operation',
                                    text:__('审核拒绝'),
                                    title:__('审核拒绝'),
                                    classname: 'btn btn-xs btn-danger  btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'new_product_design/review_the_operation?status=9',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh', {});
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    error: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function (row) {

                                        if (row.status ==7 && row.label !==0 || Config.reviewTheOperation == true) {
                                            return  true;
                                        }else{
                                            return false;
                                        }
                                    }
                                },


                            ],
                             // formatter: Table.api.formatter.operate

                            formatter: function (value, row, index) { //隐藏自定义的视频按钮
                                console.log(Config);
                                var that = $.extend({}, this);
                                var table = $(that.table).clone(true);
                                //权限判断
                                if(Config.record_size != true){ //录尺寸
                                    $(table).data("operate-edit_recipient", null);
                                    that.table = table;
                                }
                                if(Config.shooting != true){
                                    $(table).data("operate-start_shooting", null);
                                    $(table).data("operate-shot_over", null);
                                    that.table = table;
                                }
                                if(Config.allocate_personnel != true){
                                    $(table).data("operate-distr_user", null);
                                    that.table = table;
                                }

                                if(Config.making != true){
                                    $(table).data("operate-tarted_making", null);
                                    that.table = table;
                                }
                                if(Config.add_img != true){
                                    $(table).data("operate-upload_pictures", null);
                                    that.table = table;
                                }
                                if(Config.review_the_operation != true){ //通过Config.chapter 获取后台存的chapter
                                    $(table).data("operate-approved", null);
                                    that.table = table;
                                }
                                if(Config.review_the_operation != true){ //通过Config.chapter 获取后台存的chapter
                                    $(table).data("operate-review_the_operation", null);
                                    that.table = table;
                                }
                                if(Config.change_designer != true){ //通过Config.chapter 获取后台存的chapter
                                    $(table).data("operate-distr_user_change", null);
                                    that.table = table;
                                }
                                return Table.api.formatter.operate.call(that, value, row, index);
                            }
                        }

                    ]
                ],
                onLoadSuccess: function (value) {
                    if (value.label == 1 || value.label == 2 || value.label == 3 || value.label == 4) {
                        table.bootstrapTable('hideColumn', 'responsible_id');
                    } else {
                        table.bootstrapTable('showColumn', 'responsible_id');
                    }
                    if (value.label == 3) {
                        table.bootstrapTable('showColumn', 'location_code');
                    } else {
                        table.bootstrapTable('hideColumn', 'location_code');
                    }
                    // if (value.label ==3 || value.label ==2 || value.label ==4){
                    //     table.bootstrapTable('showColumn','platform');
                    // }else {
                    //     table.bootstrapTable('hideColumn','platform');
                    // }
                }
            });

            $(table).data("operate-del", null);

            // 为表格绑定事件
            Table.api.bindevent(table);
            //根据菜单隐藏或显示对应列及按钮
            viewTable(table, Config.label);
            //选项卡切换
            $('.panel-heading a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var value = $(this).data("value");
                var options = table.bootstrapTable('getOptions');
                options.pageNumber = 1;
                var queryParams = options.queryParams;
                options.queryParams = function (params) {
                    params = queryParams(params);
                    params.label = value;
                    return params;
                };
                Config.label = value;

                //根据菜单隐藏或显示对应列及按钮
                viewTable(table, Config.label);

                table.bootstrapTable('refresh', {});
                return false;
            });
            $("#export_guanlian").click(function(){
                var design_status= $('.design_status').val();
                var plat_type = $('.plat_type').val();
                var create_time = $('#create_time').val();
                window.location.href=Config.moduleurl+'/new_product_design/export?design_status='+design_status+'&plat_type='+plat_type;
            });
        },
        add: function () {
            Controller.api.bindevent();
        },
        allocate_personnel: function () {
            Controller.api.bindevent();
        },
        shooting: function () {
            Controller.api.bindevent();
        },
        making: function () {
            Controller.api.bindevent();
        },
        record_size: function () {
            Controller.api.bindevent();
        },
        change_designer: function () {
            Controller.api.bindevent();
        },
        reviewTheOperation: function () {
            Controller.api.bindevent();
        },
        add_img: function () {
            Form.api.bindevent($("form[role=form]"));
            $(document).on('click', '.btn-status', function () {
                $('#status').val(2);
            });
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});