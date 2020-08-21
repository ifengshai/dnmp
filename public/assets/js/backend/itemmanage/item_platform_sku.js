define(['jquery', 'bootstrap', 'backend', 'table', 'form','jqui','bootstrap-table-jump-to'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'itemmanage/item_platform_sku/index' + location.search,
                    add_url: 'itemmanage/item_platform_sku/add',
                    //edit_url: 'itemmanage/item_platform_sku/edit',
                    //del_url: 'itemmanage/item_platform_sku/del',
                    multi_url: 'itemmanage/item_platform_sku/multi',
                    table: 'item_platform_sku',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'item_platform_sku.id',
                columns: [
                    [
                        {checkbox: true},
                        {field: '', title: __('序号'), formatter: function (value, row, index) {
                            var options = table.bootstrapTable('getOptions');
                            var pageNumber = options.pageNumber;
                            var pageSize = options.pageSize;

                            //return (pageNumber - 1) * pageSize + 1 + index;
                            return 1+index;
                            }, operate: false
                        },
                        {field: 'id', title: __('Id'),operate:false},
                        {field: 'sku', title: __('Sku'),operate:'LIKE'},
                        {field: 'platform_sku', title: __('Platform_sku'),operate:'LIKE'},
                        {field: 'name', title: __('Name')},
                        {
                            field: 'platform_type',
                            title: __('站点'),
                            searchList:$.getJSON('saleaftermanage/sale_after_task/getAjaxOrderPlatformList'),
                            custom:{1:'blue',2:'yellow',3:'green',4:'red'},
                            formatter:Table.api.formatter.status,
                        },
                        {
                            field:'item.item_status',
                            title:__('Item_status'),
                            searchList: { 1: '新建', 2: '提交审核', 3: '审核通过', 4: '审核拒绝', 5: '取消' },
                            custom: { 1: 'yellow', 2: 'blue', 3: 'success', 4: 'red', 5: 'danger' },
                            formatter: Table.api.formatter.status
                        },
                        {
                            field:'outer_sku_status',
                            title:__('Outer_sku_status'),
                            searchList:{1:'上架',2:'下架'},
                            custom:{1:'blue',2:'red'},
                            formatter:Table.api.formatter.status,
                        },
                    
                        {
                            field:'is_upload',
                            title:__('Is_upload_item'),
                            searchList:{1:'已上传',2:'未上传'},
                            custom:{1:'blue',2:'green'},
                            formatter:Table.api.formatter.status,
                        },
                        // {
                        //     field:'is_upload_images',
                        //     title:__('Is_upload_images'),
                        //     searchList:{1:'已上传',2:'未上传'},
                        //     custom:{1:'blue',2:'green'},
                        //     formatter:Table.api.formatter.status,
                        // },
                        {field: 'create_person', title: __('Create_person')},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange'},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons:[
                               
                                {
                                    name: 'uploadToPlatform',
                                    text: '上传至对应平台',
                                    title: __('上传至对应平台'),
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    url: Config.moduleurl + '/itemmanage/item_platform_sku/afterUploadItem',
                                    confirm: '确定要上传到对应平台吗',
                                    success: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        $(".btn-refresh").trigger("click");
                                    },
                                    error: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function (row) {
                                        if (row.is_upload == 1) {
                                            return false;
                                        }
                                        return true;
                                    },
                                },
                                {
                                    name: 'uploadImagesToPlatform',
                                    text: '上传商品图片',
                                    title: __('上传商品图片到平台'),
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    url: Config.moduleurl + '/itemmanage/item_platform_sku/uploadImagesToPlatform',
                                    confirm: '确定要上传到对应平台吗',
                                    success: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        $(".btn-refresh").trigger("click");
                                    },
                                    error: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function (row) {
                                        return false;
                                    },
                                },
                            ]
                        },

                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            $(document).on('click', '.btn-upload', function () {
                var ids = Table.api.selectedids(table);
                var platformId = $(this).attr("id");
                Layer.confirm(
                    __('确定要传至对应的平台吗'),
                    function (index) {
                        Backend.api.ajax({
                            url: Config.moduleurl + "/itemmanage/item_platform_sku/uploadItem",
                            data: { ids: ids,platformId:platformId }
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
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
                $('#c-platform_sku').autocomplete({
                    source: function (request, response) {
                        var origin_sku = $('#c-platform_sku').val();
                        $.ajax({
                            type: "POST",
                            url: "itemmanage/item_platform_sku/ajaxGetLikePlatformSku",
                            dataType: "json",
                            cache: false,
                            async: false,
                            data: {
                                origin_sku: origin_sku
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
                //检查填写的平台sku数据库有没有
                $(document).on('change','#c-platform_sku',function () {
                    var platform_sku = $(this).val();
                    Backend.api.ajax({
                        url: 'itemmanage/item_platform_sku/ajaxGetPlatformSkuInfo',
                        data: { platform_sku: platform_sku }
                    }, function (data, ret) {
                        console.log(ret.data);
                        $('.btn-success').removeClass('btn-disabled disabled');
                        return false;
                    }, function (data, ret) {
                        //失败的回调
                        $('.btn-success').addClass('btn-disabled disabled');
                        Layer.alert(ret.msg);
                        return false;
                    });
                });
                //选中的开始时间和现在的时间比较
                $(document).on('dp.change','#c-presell_start_time',function () {
                    var time_value = $(this).val();
                    //console.log(time_value);
                    function getNow(s) {
                        return s < 10 ? '0' + s: s;
                    }

                    var myDate = new Date();

                    var year=myDate.getFullYear();        //获取当前年
                    var month=myDate.getMonth()+1;   //获取当前月
                    var date=myDate.getDate();            //获取当前日
                    var h=myDate.getHours();              //获取当前小时数(0-23)
                    var m=myDate.getMinutes()-10;          //获取当前分钟数(0-59)
                    var s=myDate.getSeconds();
                    var now=year+'-'+getNow(month)+"-"+getNow(date)+" "+getNow(h)+':'+getNow(m)+":"+getNow(s);
                    if(time_value>now){
                        //console.log(1111);
                    }else{
                        Layer.alert('预售开始时间超过当前时间,如果添加则默认开始预售');
                        //console.log(2222);
                    }
                    //console.log(now);
                });
                ////选中的结束时间和现在的时间比较
                $(document).on('dp.change','#c-presell_end_time',function () {
                    var time_end_value = $(this).val();
                    console.log(time_end_value);
                    function getNow(s) {
                        return s < 10 ? '0' + s: s;
                    }
                    var myDate = new Date();
                    var year=myDate.getFullYear();        //获取当前年
                    var month=myDate.getMonth()+1;   //获取当前月
                    var date=myDate.getDate();            //获取当前日
                    var h=myDate.getHours();              //获取当前小时数(0-23)
                    var m=myDate.getMinutes()-10;          //获取当前分钟数(0-59)
                    var s=myDate.getSeconds();
                    var now=year+'-'+getNow(month)+"-"+getNow(date)+" "+getNow(h)+':'+getNow(m)+":"+getNow(s);
                    if(time_end_value>now){
                        $('.btn-success').removeClass('btn-disabled disabled');
                    }else{
                        $('.btn-success').addClass('btn-disabled disabled');
                        Layer.alert('当前时间大于预售结束时间无法添加,请重新选择');
                        return false;
                    }
                });
            }
        },
        presell: function () {
            Table.api.init({
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'itemmanage/item_platform_sku/presell' + location.search,
                    add_url: 'itemmanage/item_platform_sku/addPresell',
                    edit_url: 'itemmanage/item_platform_sku/editPresell',
                    //del_url: 'itemmanage/item_platform_sku/del',
                    multi_url: 'itemmanage/item_platform_sku/multi',
                    table: 'item_platform_sku',
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
                        {checkbox: true},
                        {field: 'id', title: __('Id'),operate:false},
                        {field: 'sku', title: __('Sku'),operate:'LIKE'},
                        {field: 'platform_sku', title: __('Platform_sku'),operate:'LIKE'},
                        {field: 'name', title: __('Name')},
                        {
                            field: 'platform_type',
                            title: __('Platform_type'),
                            searchList:$.getJSON('saleaftermanage/sale_after_task/getAjaxOrderPlatformList'),
                            custom:{1:'blue',2:'yellow',3:'green',4:'red'},
                            formatter:Table.api.formatter.status,
                        },
                        {
                            field:'outer_sku_status',
                            title:__('Outer_sku_status'),
                            searchList:{1:'上架',2:'下架'},
                            custom:{1:'blue',2:'red'},
                            formatter:Table.api.formatter.status,
                        },
                        {
                            field: 'platform_sku_status',
                            title: __('Platform_sku_status'),
                            searchList: { 1:'上架', 2:'下架' },
                            custom: { 1: 'blue', 2: 'red' },
                            formatter: Table.api.formatter.status,
                        },
                        {
                          field:'presell_num',
                          title:__('Presell_num'),
                        },
                        {
                          field:'presell_residue_num',
                          title:__('Presell_residue_num'),
                        },
                        {
                          field:'presell_start_time',
                          title:__('Presell_start_time'),
                        },
                        {
                          field:'presell_end_time',
                          title:__('Presell_end_time'),
                        },
                        {
                          field:'presell_status',
                          title:__('Presell_status'),
                          searchList:{1:'未开始',2:'预售中',3:'已结束'},
                          custom:{1:'blue',2:'green',3:'yellow'},
                          formatter:Table.api.formatter.status,
                        },
                        {
                          field:'presell_create_person',
                          title:__('Presell_create_person'),
                        },
                        {field: 'create_person', title: __('Create_person')},
                        {field: 'presell_create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'presell_open_time', title: __('Presell_open_time'), operate:'RANGE', addclass:'datetimerange'},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons:[
                                // {
                                //     name: 'putaway',
                                //     text: '上架',
                                //     title: __('上架'),
                                //     classname: 'btn btn-xs btn-success btn-ajax',
                                //     url: Config.moduleurl + '/itemmanage/item_platform_sku/putaway',
                                //     confirm: '确定要上架吗',
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
                                //         // if (row.outer_sku_status == 2) {
                                //         //     return true;
                                //         // } else {
                                //         //     return false;
                                //         // }
                                //     },
                                // },
                                // {
                                //     name: 'soldOut',
                                //     text: '下架',
                                //     title: __('下架'),
                                //     classname: 'btn btn-xs btn-danger btn-ajax',
                                //     url: Config.moduleurl + '/itemmanage/item_platform_sku/soldOut',
                                //     confirm: '确定要下架吗',
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
                                //         // if (row.outer_sku_status == 2) {
                                //         //     return true;
                                //         // } else {
                                //         //     return false;
                                //         // }
                                //     },
                                // },
                                {
                                    name: 'openStart',
                                    text: '开启预售',
                                    title: __('开启预售'),
                                    classname: 'btn btn-xs btn-danger btn-ajax',
                                    url: Config.moduleurl + '/itemmanage/item_platform_sku/openStart',
                                    confirm: '确定要开启预售吗',
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
                                        return true;
                                        // if (row.outer_sku_status == 2) {
                                        //     return true;
                                        // } else {
                                        //     return false;
                                        // }
                                    },
                                },
                                {
                                    name: 'openEnd',
                                    text: '结束预售',
                                    title: __('结束预售'),
                                    classname: 'btn btn-xs btn-danger btn-ajax',
                                    url: Config.moduleurl + '/itemmanage/item_platform_sku/openEnd',
                                    confirm: '确定要结束预售吗',
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
                                        return true;
                                        // if (row.outer_sku_status == 2) {
                                        //     return true;
                                        // } else {
                                        //     return false;
                                        // }
                                    },
                                },
                            ]
                        },

                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        addpresell:function () {
            Controller.api.bindevent();
            Form.api.bindevent($("form[role=form]"));
        },
        editpresell:function () {
            Controller.api.bindevent();
            Form.api.bindevent($("form[role=form]"));
        }
    };
    return Controller;
});