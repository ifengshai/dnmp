define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    function viewTable(table, value) {
        //隐藏、显示搜索及按钮
        $('#responsible_id').parents('.form-group').hide();
        $('select[name="status"]').parents('.form-group').hide();
        if (0 == value) {
            $('select[name="status"]').parents('.form-group').show();
            $table.bootstrapTable('hideColumn', 'responsible_id');

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
        }else{
            $('select[name="status"]').parents('.form-group').show();
        }
    }

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'new_product_design/index' + location.search,
                    add_url: 'new_product_design/add',
                    edit_url: 'new_product_design/edit',
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
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('序号'),operate: false},
                        {field: 'sku', title: __('Sku')},

                        {
                            field: 'status',
                            title: __('状态'),
                            searchList: { 1: '待录尺寸', 2: '待拍摄', 3: '拍摄中', 4: '待分配', 5: '待修图', 6: '修图中', 7: '待审核', 8: '已完成', 9: '审核拒绝', 10: '完成'},
                            custom: { 1: 'black', 2: 'black', 3: 'black', 4: 'black', 5: 'black', 6: 'black', 7: 'black', 8: 'black', 9: 'black', 10: 'black', 11: 'black' },
                            formatter: Table.api.formatter.status
                        },
                        {field: 'responsible_id', title: __('责任人')},
                        {field: 'create_time', title: __('创建时间'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [

                                {
                                    name: 'edit_recipient',
                                    text:__('录尺寸'),
                                    title:__('录尺寸'),
                                    extend: 'data-area = \'["50%","50%"]\'',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'new_product_design/record_size',
                                    icon: '',
                                    area: ['50%', '45%'],
                                    //extend: 'data-area = \'["100%","100%"]\' target=\'_blank\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function(row){
                                        if (row.status ==1 && row.label !==0){
                                            return  true;
                                        }else{
                                            return false;
                                        }

                                    }
                                },
                                {
                                    name: 'edit',
                                    text:'查看详情',
                                    title:__('查看详情'),

                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: '',
                                    url: 'new_product_design/detail/id/{row.id}',
                                    area: ['80%', '65%'],
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
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
                                    url: 'new_product_design/reviewTheOperation?status=8',
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
                                    name: 'audit_refused',
                                    text:__('审核拒绝'),
                                    title:__('审核拒绝'),
                                    classname: 'btn btn-xs btn-danger  btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'new_product_design/reviewTheOperation?status=9',
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


                            ],
                            // formatter: Table.api.formatter.operate

                            formatter: function (value, row, index) { //隐藏自定义的视频按钮
                                var that = $.extend({}, this);
                                var table = $(that.table).clone(true);
                                //权限判断
                                if(Config.record_size != true){ //通过Config.chapter 获取后台存的chapter
                                    console.log('没有录尺寸权限');
                                    $(table).data("operate-video", null);
                                    that.table = table;
                                }else{
                                    console.log('有录尺寸权限');
                                }
                                if(Config.shooting != true){
                                    console.log('没有拍摄权限');
                                    $(table).data("operate-start_shooting", null);
                                    $(table).data("operate-shot_over", null);
                                    that.table = table;
                                }else{
                                    console.log('有拍摄权限');
                                }
                                if(Config.allocate_personnel != true){
                                    console.log('没有分配权限');
                                    $(table).data("operate-tarted_making", null);
                                    that.table = table;
                                }else{
                                    console.log('有分配权限');
                                }
                                if(Config.making != true){
                                    console.log('没有修图权限');
                                    $(table).data("operate-tarted_making", null);
                                    that.table = table;
                                }else{
                                    console.log('有修图权限');
                                }
                                if(Config.add_img != true){
                                    console.log('没有上传图片的权限');
                                    $(table).data("operate-upload_pictures", null);
                                    that.table = table;
                                }else{
                                    console.log('有上传图片的权限');
                                }
                                if(Config.reviewTheOperation != true){ //通过Config.chapter 获取后台存的chapter
                                    console.log('没有审核操作权限');
                                    $(table).data("operate-approved", null);
                                    $(table).data("audit_refused-approved", null);
                                    that.table = table;
                                }else{
                                    console.log('有审核操作权限');
                                }



                                return Table.api.formatter.operate.call(that, value, row, index);
                            }
                        }

                    ]
                ],
                onLoadSuccess: function (value){
                    if (value.label ==1 || value.label ==2 || value.label ==3 ||value.label ==4 ){
                        table.bootstrapTable('hideColumn','responsible_id');
                    }else{
                        table.bootstrapTable('showColumn','responsible_id');
                    }
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
        },
        add: function () {
            Controller.api.bindevent();
        },
        allocate_personnel: function () {
            Controller.api.bindevent();
        },
        record_size: function () {
            Controller.api.bindevent();
        },
        edit: function () {
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