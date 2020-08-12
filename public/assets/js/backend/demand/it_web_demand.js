define(['jquery', 'bootstrap', 'backend', 'table', 'form','nkeditor', 'upload'], function ($, undefined, Backend, Table, Form,Nkeditor, Upload) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'demand/it_web_demand/index' + location.search,
                    add_url: 'demand/it_web_demand/add',
                    edit_url: 'demand/it_web_demand/edit',
                    del_url: 'demand/it_web_demand/del',
                    table: 'it_web_demand',
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
                        {field: 'id', title: __('Id'),operate:'='},
                        {
                            field: 'site',
                            title: __('项目'),
                            searchList: { 1: 'Zeelool', 2: 'Voogueme', 3: 'Nihao' , 4: 'Meeloog', 5: 'Wesee', 6:'Rufoo',7:'Toloog',8:'Other'},
                            custom:{1: 'black', 2: 'black', 3: 'black' , 4: 'black', 5: 'black', 6:'black',7:'black',8:'black'},
                            formatter: Table.api.formatter.status
                        },
                        {field: 'entry_user_name', title: __('提出人'), operate:'like'},
                        {
                            field: 'type',
                            title: __('任务类型'),
                            searchList: { 1: 'Bug', 2: '维护', 3: '优化' , 4: '新功能', 5: '开发'},
                            custom:{1: 'red', 2: 'blue', 3: 'blue' , 4: 'blue', 5: 'green'},
                            formatter: Table.api.formatter.status
                        },

                        {
                            field: 'title',
                            title: __('标题'),
                            operate: 'LIKE',
                            events: Controller.api.events.gettitle,
                            cellStyle: formatTableUnit,
                            formatter: Controller.api.formatter.gettitle,
                            operate:false
                        },

                        {field: 'create_time', title: __('创建时间'), operate: 'RANGE', addclass: 'datetimerange',formatter: Table.api.formatter.datetime},

                        {
                            field: 'pm_audit_status',
                            title: __('评审'),
                            events: Controller.api.events.ge_pm_status,
                            searchList: { 1: '待审', 2: 'Pending', 3: '通过'},
                            formatter: Controller.api.formatter.ge_pm_status,
                        },
                        {
                            field: 'priority',
                            title: __('优先级'),
                            searchList: { '':'-', 1: 'D1', 2: 'D2', 3: 'V1' , 4: 'V2', 5: 'V3'},
                            custom:{1: 'black', 2: 'black', 3: 'black' , 4: 'black', 5: 'black'},
                            formatter: Table.api.formatter.status,
                            operate:false
                        },
                        {field: 'node_time', title: __('任务周期'),operate:false},
                        {
                            field: 'status',
                            title: __('任务状态'),
                            searchList: { 1: '未激活', 2: '激活', 3: '已响应' , 4: '完成', 5: '超时完成'},
                            custom:{1: 'gray', 2: 'blue', 3: 'green' , 4: 'gray', 5: 'yellow'},
                            formatter: Table.api.formatter.status,
                            operate:false
                        },
                        {
                            field: 'develop_finish_status',
                            title: __('开发进度'),
                            events: Controller.api.events.get_develop_status,
                            formatter: Controller.api.formatter.get_develop_status,
                            operate:false
                        },
                        {
                            field: 'test_status',
                            title: __('测试进度'),
                            events: Controller.api.events.get_test_status,
                            formatter: Controller.api.formatter.get_test_status,
                            operate:false
                        },
                        {
                            field: 'all_finish_time',
                            title: __('时间节点'),
                            operate: false,
                            formatter: function (value, rows) {
                                var all_user_name = '';
                                if(rows.develop_finish_time){
                                    all_user_name += '<span class="all_user_name">开发完成：<b>'+ rows.develop_finish_time + '</b></span><br>';
                                }

                                if(rows.test_is_finish == 1){
                                    all_user_name += '<span class="all_user_name">测试完成：<b>'+ rows.test_finish_time + '</b></span><br>';
                                }

                                if(rows.all_finish_time){
                                    all_user_name += '<span class="all_user_name">完&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;成：<b>'+ rows.all_finish_time + '</b></span><br>';
                                }
                                if(all_user_name == ''){
                                    all_user_name = '-';
                                }

                                return all_user_name;
                            },
                        },
                        {
                            field: 'entry_user_confirm',
                            title: __('完成确认'),
                            events: Controller.api.events.get_user_confirm,
                            formatter: Controller.api.formatter.get_user_confirm,
                            operate:false
                        },
                        {
                            field: 'all_user_id',
                            title: __('责任人'),
                            operate: false,
                            formatter: function (value, rows) {
                                var all_user_name = '';
                                if(rows.web_designer_user_id){
                                    all_user_name += '<span class="all_user_name">前端：<b>'+ rows.web_designer_user_name + '</b></span><br>';
                                }
                                if(rows.phper_user_id){
                                    all_user_name += '<span class="all_user_name">后端：<b>'+ rows.php_user_name + '</b></span><br>';
                                }
                                if(rows.app_user_id){
                                    all_user_name += '<span class="all_user_name">APP：<b>'+ rows.app_user_name + '</b></span><br>';
                                }
                                if(rows.test_user_id){
                                    all_user_name += '<span class="all_user_name">测试：<b>'+ rows.test_user_name + '</b></span><br>';
                                }
                                return all_user_name;
                            },
                        },
                        {
                            field: 'detail',
                            title: __('详情记录'),
                            events: Controller.api.events.get_detail,
                            formatter: Controller.api.formatter.get_detail,
                            operate:false
                        },
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
                    var op     = params.op ? JSON.parse(params.op) : {};
                    if (field == ''){
                        delete filter.label;
                    } else {
                        filter[field] = value;
                    }
                    params.filter = JSON.stringify(filter);
                    params.op     = JSON.stringify(op);
                    return params;
                };
                table.bootstrapTable('refresh', {});
                return false;
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },

        rdc_demand_list: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'demand/it_web_demand/rdc_demand_list' + location.search,
                    add_url: 'demand/it_web_demand/add/demand_type/2',
                    edit_url: 'demand/it_web_demand/edit/demand_type/2',
                    del_url: 'demand/it_web_demand/del',
                    table: 'it_web_demand',
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
                        {field: 'id', title: __('Id'),operate:'='},
                        {
                            field: 'site',
                            title: __('项目'),
                            searchList: { 1: 'Zeelool', 2: 'Voogueme', 3: 'Nihao' , 4: 'Meeloog', 5: 'Wesee', 6:'Rufoo',7:'Toloog',8:'Other'},
                            custom:{1: 'black', 2: 'black', 3: 'black' , 4: 'black', 5: 'black', 6:'black',7:'black',8:'black'},
                            formatter: Table.api.formatter.status
                        },
                        {field: 'entry_user_name', title: __('提出人'), operate:'like'},
                        {
                            field: 'type',
                            title: __('任务类型'),
                            searchList: { 1: 'Bug', 2: '维护', 3: '优化' , 4: '新功能', 5: '开发'},
                            custom:{1: 'red', 2: 'blue', 3: 'blue' , 4: 'blue', 5: 'green'},
                            formatter: Table.api.formatter.status
                        },

                        {
                            field: 'title',
                            title: __('标题'),
                            operate: 'LIKE',
                            events: Controller.api.events.getrdctitle,
                            cellStyle: formatTableUnit,
                            formatter: Controller.api.formatter.getrdctitle,
                            operate:false
                        },

                        {field: 'create_time', title: __('创建时间'), operate: 'RANGE', addclass: 'datetimerange',formatter: Table.api.formatter.datetime},

                        {
                            field: 'pm_audit_status',
                            title: __('评审'),
                            events: Controller.api.events.ge_rdcpm_status,
                            searchList: { 1: '待通过', 3: '通过'},
                            formatter: Controller.api.formatter.ge_rdcpm_status,
                        },
                        {field: 'node_time', title: __('任务周期'),operate:false},
                        {
                            field: 'status',
                            title: __('任务状态'),
                            searchList: { 1: '未激活', 2: '激活', 3: '已响应' , 4: '完成', 5: '超时完成'},
                            custom:{1: 'gray', 2: 'blue', 3: 'green' , 4: 'gray', 5: 'yellow'},
                            formatter: Table.api.formatter.status,
                            operate:false
                        },
                        {
                            field: 'develop_finish_status',
                            title: __('开发进度'),
                            events: Controller.api.events.get_develop_status,
                            formatter: Controller.api.formatter.get_develop_status,
                            operate:false
                        },
                        {
                            field: 'test_status',
                            title: __('测试进度'),
                            events: Controller.api.events.get_test_status,
                            formatter: Controller.api.formatter.get_test_status,
                            operate:false
                        },
                        {
                            field: 'all_finish_time',
                            title: __('时间节点'),
                            operate: false,
                            formatter: function (value, rows) {
                                var all_user_name = '';
                                if(rows.develop_finish_time){
                                    all_user_name += '<span class="all_user_name">开发完成：<b>'+ rows.develop_finish_time + '</b></span><br>';
                                }


                                if(rows.test_is_finish == 1){
                                    all_user_name += '<span class="all_user_name">测试完成：<b>'+ rows.test_finish_time + '</b></span><br>';
                                }

                                if(rows.all_finish_time){
                                    all_user_name += '<span class="all_user_name">完&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;成：<b>'+ rows.all_finish_time + '</b></span><br>';
                                }
                                if(all_user_name == ''){
                                    all_user_name = '-';
                                }

                                return all_user_name;
                            },
                        },
                        {
                            field: 'all_user_id',
                            title: __('责任人'),
                            operate: false,
                            formatter: function (value, rows) {
                                var all_user_name = '';
                                if(rows.web_designer_user_id){
                                    all_user_name += '<span class="all_user_name">前端：<b>'+ rows.web_designer_user_name + '</b></span><br>';
                                }
                                if(rows.phper_user_id){
                                    all_user_name += '<span class="all_user_name">后端：<b>'+ rows.php_user_name + '</b></span><br>';
                                }
                                if(rows.app_user_id){
                                    all_user_name += '<span class="all_user_name">APP：<b>'+ rows.app_user_name + '</b></span><br>';
                                }
                                if(rows.test_user_id){
                                    all_user_name += '<span class="all_user_name">测试：<b>'+ rows.test_user_name + '</b></span><br>';
                                }
                                return all_user_name;
                            },
                        },

                        {
                            field: 'detail',
                            title: __('详情记录'),
                            events: Controller.api.events.get_detail,
                            formatter: Controller.api.formatter.get_detail,
                            operate:false
                        },

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
                    var op     = params.op ? JSON.parse(params.op) : {};
                    if (field == '') {
                        delete filter.label;
                    } else {
                        filter[field] = value;
                    }
                    params.filter = JSON.stringify(filter);
                    params.op     = JSON.stringify(op);
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
            $(".editor_nkeditor", $("form[role=form]")).each(function () {
                var that = this;
                Nkeditor.create(that, {
                    width: '100%',
                    height: '50%',
                    filterMode: false,
                    wellFormatMode: false,
                    allowMediaUpload: true, //是否允许媒体上传
                    allowFileManager: true,
                    allowImageUpload: true,
                    wordImageServer: typeof Config.nkeditor != 'undefined' && Config.nkeditor.wordimageserver ? "127.0.0.1:10101" : "", //word图片替换服务器的IP和端口
                    urlType: Config.upload.cdnurl ? 'domain' : '',//给图片加前缀
                    cssPath: Fast.api.cdnurl('/assets/addons/nkeditor/plugins/code/prism.css'),
                    cssData: "body {font-size: 13px}",
                    fillDescAfterUploadImage: false, //是否在上传后继续添加描述信息
                    themeType: typeof Config.nkeditor != 'undefined' ? Config.nkeditor.theme : 'black', //编辑器皮肤,这个值从后台获取
                    fileManagerJson: Fast.api.fixurl("/addons/nkeditor/index/attachment/module/" + Config.modulename),
                    items: [
                        'source'
                    ],
                    afterCreate: function () {
                        var self = this;
                        //Ctrl+回车提交
                        Nkeditor.ctrl(document, 13, function () {
                            self.sync();
                            $(that).closest("form").submit();
                        });
                        Nkeditor.ctrl(self.edit.doc, 13, function () {
                            self.sync();
                            $(that).closest("form").submit();
                        });
                        //粘贴上传
                        $("body", self.edit.doc).bind('paste', function (event) {
                            var image, pasteEvent;
                            pasteEvent = event.originalEvent;
                            if (pasteEvent.clipboardData && pasteEvent.clipboardData.items) {
                                image = getImageFromClipboard(pasteEvent);
                                if (image) {
                                    event.preventDefault();
                                    Upload.api.send(image, function (data) {
                                        self.exec("insertimage", Fast.api.cdnurl(data.url));
                                    });
                                }
                            }
                        });
                        //挺拽上传
                        $("body", self.edit.doc).bind('drop', function (event) {
                            var image, pasteEvent;
                            pasteEvent = event.originalEvent;
                            if (pasteEvent.dataTransfer && pasteEvent.dataTransfer.files) {
                                images = getImageFromDrop(pasteEvent);
                                if (images.length > 0) {
                                    event.preventDefault();
                                    $.each(images, function (i, image) {
                                        Upload.api.send(image, function (data) {
                                            self.exec("insertimage", Fast.api.cdnurl(data.url));
                                        });
                                    });
                                }
                            }
                        });
                    },
                    //FastAdmin自定义处理
                    beforeUpload: function (callback, file) {
                        var file = file ? file : $("input.ke-upload-file", this.form).prop('files')[0];
                        Upload.api.send(file, function (data) {
                            var data = {code: '000', data: {url: Fast.api.cdnurl(data.url)}, title: '', width: '', height: '', border: '', align: ''};
                            callback(data);
                        });

                    },
                    //错误处理 handler
                    errorMsgHandler: function (message, type) {
                        try {
                            console.log(message, type);
                        } catch (Error) {
                            alert(message);
                        }
                    }
                });
            });
            $('.ke-edit-iframe').css('height', '240px');
        },
        edit: function () {
            Controller.api.bindevent();
            $(".editor_nkeditor", $("form[role=form]")).each(function () {
                var that = this;
                Nkeditor.create(that, {
                    width: '100%',
                    height: '50%',
                    filterMode: false,
                    wellFormatMode: false,
                    allowMediaUpload: true, //是否允许媒体上传
                    allowFileManager: true,
                    allowImageUpload: true,
                    wordImageServer: typeof Config.nkeditor != 'undefined' && Config.nkeditor.wordimageserver ? "127.0.0.1:10101" : "", //word图片替换服务器的IP和端口
                    urlType: Config.upload.cdnurl ? 'domain' : '',//给图片加前缀
                    cssPath: Fast.api.cdnurl('/assets/addons/nkeditor/plugins/code/prism.css'),
                    cssData: "body {font-size: 13px}",
                    fillDescAfterUploadImage: false, //是否在上传后继续添加描述信息
                    themeType: typeof Config.nkeditor != 'undefined' ? Config.nkeditor.theme : 'black', //编辑器皮肤,这个值从后台获取
                    fileManagerJson: Fast.api.fixurl("/addons/nkeditor/index/attachment/module/" + Config.modulename),
                    items: [
                        'source'
                    ],
                    afterCreate: function () {
                        var self = this;
                        //Ctrl+回车提交
                        Nkeditor.ctrl(document, 13, function () {
                            self.sync();
                            $(that).closest("form").submit();
                        });
                        Nkeditor.ctrl(self.edit.doc, 13, function () {
                            self.sync();
                            $(that).closest("form").submit();
                        });
                        //粘贴上传
                        $("body", self.edit.doc).bind('paste', function (event) {
                            var image, pasteEvent;
                            pasteEvent = event.originalEvent;
                            if (pasteEvent.clipboardData && pasteEvent.clipboardData.items) {
                                image = getImageFromClipboard(pasteEvent);
                                if (image) {
                                    event.preventDefault();
                                    Upload.api.send(image, function (data) {
                                        self.exec("insertimage", Fast.api.cdnurl(data.url));
                                    });
                                }
                            }
                        });
                        //挺拽上传
                        $("body", self.edit.doc).bind('drop', function (event) {
                            var image, pasteEvent;
                            pasteEvent = event.originalEvent;
                            if (pasteEvent.dataTransfer && pasteEvent.dataTransfer.files) {
                                images = getImageFromDrop(pasteEvent);
                                if (images.length > 0) {
                                    event.preventDefault();
                                    $.each(images, function (i, image) {
                                        Upload.api.send(image, function (data) {
                                            self.exec("insertimage", Fast.api.cdnurl(data.url));
                                        });
                                    });
                                }
                            }
                        });
                    },
                    //FastAdmin自定义处理
                    beforeUpload: function (callback, file) {
                        var file = file ? file : $("input.ke-upload-file", this.form).prop('files')[0];
                        Upload.api.send(file, function (data) {
                            var data = {code: '000', data: {url: Fast.api.cdnurl(data.url)}, title: '', width: '', height: '', border: '', align: ''};
                            callback(data);
                        });

                    },
                    //错误处理 handler
                    errorMsgHandler: function (message, type) {
                        try {
                            console.log(message, type);
                        } catch (Error) {
                            alert(message);
                        }
                    }
                });
            });
            $('.ke-edit-iframe').css('height', '240px');
            $(document).on('click', ".btn-sub", function () {
                var type = $(this).val();
                if(type == 'del'){
                    $("#demand_edit").attr('action','demand/it_web_demand/del');
                }
                if(type == 'edit'){
                    $("#demand_edit").attr('action','demand/it_web_demand/edit');
                }
                if(type == 'pending'){
                    $('#pm_audit_status').val(2);
                    $("#demand_edit").attr('action','demand/it_web_demand/edit');
                }
                if(type == 'sub'){
                    $('#pm_audit_status').val(3);
                    $("#demand_edit").attr('action','demand/it_web_demand/edit');
                }
                $("#demand_edit").submit();
            });

        },
        distribution: function () {
            Controller.api.bindevent();

            $(document).on('change', ".web_group_status", function () {
                var status_val = $(this).val();
                if(status_val){
                    if(status_val == 2){
                        $('.web_html').css('display','none');
                    }
                    if(status_val == 1){
                        $('.web_html').css('display','block');
                    }
                }
            });
            $(document).on('change', ".php_group_status", function () {
                var status_val = $(this).val();
                if(status_val){
                    if(status_val == 2){
                        $('.php_html').css('display','none');
                    }
                    if(status_val == 1){
                        $('.php_html').css('display','block');
                    }
                }
            });
            $(document).on('change', ".app_group_status", function () {
                var status_val = $(this).val();
                if(status_val){
                    if(status_val == 2){
                        $('.app_html').css('display','none');
                    }
                    if(status_val == 1){
                        $('.app_html').css('display','block');
                    }
                }
            });

            $(document).on('click', ".btn-sub", function () {
                var type = $(this).val();
                $('#input_'+type).val('2');
                $("#form_"+type).submit();
            });
        },
        test_handle: function () {
            Controller.api.bindevent();

            $(document).on('click', ".btn-sub", function () {
                var type = $(this).val();
                if(type == 'tongguo_yes'){
                    $('#tongguo_status').val('1');
                    $("#tongguo_form").submit();
                }
                if(type == 'tongguo_no'){
                    $('#tongguo_status').val('2');
                    $("#tongguo_form").submit();
                }
            });
        },
        detail: function () {
            Controller.api.bindevent();


            $(document).on('change', ".check_value,#web_designer_user_id,#phper_user_id,#app_user_id,#test_user_id", function () {
                var layer_index = layer.load(2, {
                    shade: [0.2,'#000']
                });

                var id = $('#demand_id').val();

                var is_small_probability = 0;
                if($('#is_small_probability').is(":checked")){
                    is_small_probability = 1;
                }
                var is_low_level_error = 0;
                if($('#is_low_level_error').is(":checked")){
                    is_low_level_error = 1;
                }
                var is_difficult = 0;
                if($('#is_difficult').is(":checked")){
                    is_difficult = 1;
                }

                var web_designer_user_id = $('#web_designer_user_id').val();
                var phper_user_id = $('#phper_user_id').val();
                var app_user_id = $('#app_user_id').val();
                var test_user_id = $('#test_user_id').val();

                $.ajax({
                    type: "POST",
                    url: "demand/it_web_demand/detail",
                    dataType: "json",
                    cache: false,
                    async: false,
                    data: {
                        id: id,
                        is_small_probability: is_small_probability,
                        is_low_level_error: is_low_level_error,
                        is_difficult: is_difficult,
                        web_designer_user_id: web_designer_user_id,
                        phper_user_id: phper_user_id,
                        app_user_id: app_user_id,
                        test_user_id: test_user_id
                    },
                    success: function (json) {
                        Toastr.success(json.msg);
                        layer.close(layer_index);
                        parent.$('#table').bootstrapTable('refresh');
                    }
                });
            });

            $(document).on('click', ".sub_review", function () {
                var layer_index = layer.load(2, {
                    shade: [0.2,'#000']
                });

                var form_id = $(this).attr('data');
                var content = $('#c_'+form_id).val();
                var id = $('#demand_id').val();
                if(form_id == 'test_review'){
                    var type = 1;
                }else{
                    var type = 2;
                }

                $.ajax({
                    type: "POST",
                    url: "demand/it_web_demand/demand_review",
                    dataType: "json",
                    cache: false,
                    async: false,
                    data: {
                        pid: id,
                        type: type,
                        content: content,
                    },
                    success: function (json) {
                        if(json.data){
                            var str = '<li class="item"><p><span class="name">'+json.data.group_name+'</span><span class="time">'+json.data.create_time+'</span></p><p class="text-content">'+json.data.content+'</p></li>'
                            $('#'+form_id).append(str);
                            $('#c_'+form_id).val('');
                        }


                        Toastr.success(json.msg);
                        layer.close(layer_index);
                    }
                });
            });



        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },

            formatter: {
                //点击标题，弹出窗口
                gettitle: function (value) {
                    return '<a class="btn-gettitle" style="color: #333333!important;">' + value + '</a>';
                },
                //RDC点击标题，弹出窗口
                getrdctitle: function (value) {
                    return '<a class="btn-gettitle" style="color: #333333!important;">' + value + '</a>';
                },
                //点击评审，弹出窗口
                ge_pm_status: function (value, row, index) {
                    if(row.pm_audit_status == 1){
                        return '<div><span class="check_pm_status status1_color">待审</span></div>';
                    }
                    if(row.pm_audit_status == 2){
                        return '<div><span class="check_pm_status status2_color">Pending</span></div>';
                    }
                    if(row.pm_audit_status == 3){
                        return '<div><span class="check_pm_status status3_color">通过</span></div>';
                    }
                },
                //rdc点击评审,直接通过
                ge_rdcpm_status: function (value, row, index) {
                    if(row.pm_audit_status == 1){
                        if(row.pm_status){
                            return '<div><span class="check_pm_status status1_color">待通过</span></div>';
                        }else{
                            return '<div><span class="check_rdcpm_status status1_color disabled">待通过</span></div>';
                        }
                    }
                    if(row.pm_audit_status == 3){
                        return '<div><span class="check_rdcpm_status status3_color disabled">通过</span></div>';
                    }
                },
                //开发进度点击弹窗
                get_develop_status: function (value, row, index) {
                    if(row.status >= 2){
                        if(row.develop_finish_status == 1){
                            return '<div><span class="check_develop_status status1_color">未响应</span></div>';
                        }else if (row.develop_finish_status == 2){
                            return '<div><span class="check_develop_status status1_color">开发中</span></div>';
                        }else if(row.develop_finish_status == 3){
                            if(row.status == 5){
                                return '<div><span class="check_develop_status status4_color">开发完成</span></div>';
                            }else{
                                return '<div><span class="check_develop_status status3_color">开发完成</span></div>';
                            }
                        }
                    }else{
                        return '-';
                    }
                },
                //测试进度点击弹窗
                get_test_status: function (value, row, index) {
                    if(row.status >= 2){
                        if(row.test_status == 1){
                            return '<div><span class="check_test_status status1_color">未确认</span></div>';
                        }else if (row.test_status == 2){
                            if(row.test_group == 1){
                                return '<div><span class="check_test_status status3_color">待测试</span></div>';
                            }else{
                                return '<div><span class="check_test_status status3_color">无需测试</span></div>';
                            }

                        }else if (row.test_status == 3){
                            return '<div><span class="check_test_status status1_color">待通过</span></div>';
                        }else if (row.test_status == 4){
                            return '<div><span class="check_test_status status1_color">待上线</span></div>';
                        }else if (row.test_status == 5){
                            return '<div><span class="check_test_status status3_color">已上线</span></div>';
                        }
                    }else{
                        return '-';
                    }
                },
                //完成确认
                get_user_confirm: function (value, row, index) {
                    if(row.test_status == 5){
                        //状态=5才可以点击通过
                        if(row.demand_pm_status && row.entry_user_id == Config.admin_id){
                            //当前登录人有产品确认权限，并且当前登录人就是录入人，则一个按钮
                            if(row.entry_user_confirm == 1 && row.pm_confirm == 1){
                                return '已确认';
                            }else{
                                return '<div><span class="check_user_confirm status1_color">确认</span></div>';
                            }
                        }else{
                            //如果是产品
                            if(row.demand_pm_status){
                                if(row.pm_confirm == 1){
                                    //产品已经确认
                                    if(row.entry_user_confirm == 1){
                                        return '已确认';
                                    }else{
                                        return '产品已确认';
                                    }
                                }else{
                                    return '<div><span class="check_user_confirm status1_color">确认</span></div>';
                                }
                            }
                            //如果是提出人
                            if(row.entry_user_id == Config.admin_id){
                                if(row.entry_user_confirm == 1){
                                    //提出人已经确认
                                    if(row.pm_confirm == 1){
                                        return '已确认';
                                    }else{
                                        return '提出人已确认';
                                    }
                                }else{
                                    return '<div><span class="check_user_confirm status1_color">确认</span></div>';
                                }
                            }
                            //如果是其他人
                            if(row.entry_user_confirm == 1 && row.pm_confirm == 1){
                                return '已确认';
                            }else{
                                if(row.entry_user_confirm == 1){
                                    return '提出人已确认';
                                }
                                if(row.pm_confirm == 1){
                                    return '产品已确认';
                                }
                                return '未确认';
                            }
                        }
                    }else{
                        return '-'
                    }
                },

                //详情记录点击查看
                get_detail: function (value, row, index) {
                    return '<div><span class="check_detail">查看</span></div>';
                },
            },
            events: {//绑定事件的方法
                //点击标题，弹出窗口
                gettitle: {
                    //格式为：方法名+空格+DOM元素
                    'click .btn-gettitle': function (e, value, row, index) {
                        Backend.api.open('demand/it_web_demand/edit/type/view/ids/' +row.id, __('任务查看'), { area: ['70%', '70%'] });
                    }
                },
                //RDC点击标题，弹出窗口
                getrdctitle: {
                    //格式为：方法名+空格+DOM元素
                    'click .btn-gettitle': function (e, value, row, index) {
                        Backend.api.open('demand/it_web_demand/edit/demand_type/2/type/view/ids/' +row.id, __('任务查看'), { area: ['70%', '70%'] });
                    }
                },
                //点击评审，弹出窗口
                ge_pm_status: {
                    'click .check_pm_status': function (e, value, row, index) {
                        Backend.api.open('demand/it_web_demand/edit/type/pm_audit/ids/' +row.id, __('任务评审'), { area: ['70%', '70%'] });
                    }
                },
                //点击评审，弹出窗口
                ge_rdcpm_status: {
                    'click .check_pm_status': function (e, value, row, index) {

                        Backend.api.ajax({
                            url:'demand/it_web_demand/rdc_demand_pass/ids/' +row.id,
                        }, function(data, ret){
                            $("#table").bootstrapTable('refresh');
                        }, function(data, ret){
                            //失败的回调
                            $("#table").bootstrapTable('refresh');
                        });
                    }
                },
                //开发进度，弹出窗口
                get_develop_status:{
                    'click .check_develop_status': function (e, value, row, index) {
                        Backend.api.open('demand/it_web_demand/distribution/ids/' +row.id, __('开发进度'), { area: ['80%', '55%'] });
                    }
                },
                //测试进度
                get_test_status: {
                    'click .check_test_status': function (e, value, row, index) {
                        Backend.api.open('demand/it_web_demand/test_handle/ids/' +row.id, __('测试进度'), { area: ['40%', '50%'] });
                    }
                },
                //完成确认
                get_user_confirm: {
                    'click .check_user_confirm': function (e, value, row, index) {
                        layer.confirm('确认本需求？', {
                            btn: ['确认','取消'] //按钮
                        }, function(){
                            Backend.api.ajax({
                                url:'demand/it_web_demand/add/is_user_confirm/1/ids/' +row.id,
                            }, function(data, ret){
                                $("#table").bootstrapTable('refresh');
                                Layer.closeAll();
                            }, function(data, ret){
                                //失败的回调
                                Layer.closeAll();
                                return false;
                            });
                        }, function(){
                            Layer.closeAll();
                        });
                    }
                },

                //详情记录点击查看
                get_detail: {
                    'click .check_detail': function (e, value, row, index) {
                        Backend.api.open('demand/it_web_demand/detail/ids/' +row.id, __('详情记录'), { area: ['70%', '60%'] });
                    }
                },
            }            
        }
    };
    return Controller;
});

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