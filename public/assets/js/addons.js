define([], function () {
    require(['../addons/bootstrapcontextmenu/js/bootstrap-contextmenu'], function (undefined) {
        if (Config.controllername == 'index' && Config.actionname == 'index') {
            $("body").append(
                '<div id="context-menu">' +
                '<ul class="dropdown-menu" role="menu">' +
                '<li><a tabindex="-1" data-operate="refresh"><i class="fa fa-refresh fa-fw"></i>刷新</a></li>' +
                '<li><a tabindex="-1" data-operate="refreshTable"><i class="fa fa-table fa-fw"></i>刷新表格</a></li>' +
                '<li><a tabindex="-1" data-operate="close"><i class="fa fa-close fa-fw"></i>关闭</a></li>' +
                '<li><a tabindex="-1" data-operate="closeOther"><i class="fa fa-window-close-o fa-fw"></i>关闭其他</a></li>' +
                '<li class="divider"></li>' +
                '<li><a tabindex="-1" data-operate="closeAll"><i class="fa fa-power-off fa-fw"></i>关闭全部</a></li>' +
                '</ul>' +
                '</div>');

            $(".nav-addtabs").contextmenu({
                target: "#context-menu",
                scopes: 'li[role=presentation]',
                onItem: function (e, event) {
                    var $element = $(event.target);
                    var tab_id = e.attr('id');
                    var id = tab_id.substr('tab_'.length);
                    var con_id = 'con_' + id;
                    switch ($element.data('operate')) {
                        case 'refresh':
                            $("#" + con_id + " iframe").attr('src', function (i, val) {
                                return val;
                            });
                            break;
                        case 'refreshTable':
                            try {
                                if ($("#" + con_id + " iframe").contents().find(".btn-refresh").size() > 0) {
                                    $("#" + con_id + " iframe")[0].contentWindow.$(".btn-refresh").trigger("click");
                                }
                            } catch (e) {

                            }
                            break;
                        case 'close':
                            if (e.find(".close-tab").length > 0) {
                                e.find(".close-tab").click();
                            }
                            break;
                        case 'closeOther':
                            e.parent().find("li[role='presentation']").each(function () {
                                if ($(this).attr('id') == tab_id) {
                                    return;
                                }
                                if ($(this).find(".close-tab").length > 0) {
                                    $(this).find(".close-tab").click();
                                }
                            });
                            break;
                        case 'closeAll':
                            e.parent().find("li[role='presentation']").each(function () {
                                if ($(this).find(".close-tab").length > 0) {
                                    $(this).find(".close-tab").click();
                                }
                            });
                            break;
                        default:
                            break;
                    }
                }
            });
        }
        $(document).on('click', function () { // iframe内点击 隐藏菜单
            try {
                top.window.$(".nav-addtabs").contextmenu("closemenu");
            } catch (e) {
            }
        });

    });
    require.config({
        paths: {
            'editable': '../libs/bootstrap-table/dist/extensions/editable/bootstrap-table-editable.min',
            'x-editable': '../addons/editable/js/bootstrap-editable.min',
        },
        shim: {
            'editable': {
                deps: ['x-editable', 'bootstrap-table']
            },
            "x-editable": {
                deps: ["css!../addons/editable/css/bootstrap-editable.css"],
            }
        }
    });
    if ($("table.table").size() > 0) {
        require(['editable', 'table'], function (Editable, Table) {
            $.fn.bootstrapTable.defaults.onEditableSave = function (field, row, oldValue, $el) {
                var data = {};
                data["row[" + field + "]"] = row[field];
                Fast.api.ajax({
                    url: this.extend.edit_url + "/ids/" + row[this.pk],
                    data: data
                });
            };
        });
    }
    require.config({
        paths: {
            'async': '../addons/example/js/async',
            'BMap': ['//api.map.baidu.com/api?v=2.0&ak=mXijumfojHnAaN2VxpBGoqHM'],
        },
        shim: {
            'BMap': {
                deps: ['jquery'],
                exports: 'BMap'
            }
        }
    });

    require.config({
        paths: {
            'bootstrap-markdown': '../addons/markdown/js/bootstrap-markdown.min',
            'hyperdown': '../addons/markdown/js/hyperdown.min',
            'pasteupload': '../addons/markdown/js/jquery.pasteupload'
        },
        shim: {
            'bootstrap-markdown': {
                deps: [
                    'jquery',
                    'css!../addons/markdown/css/bootstrap-markdown.css'
                ],
                exports: '$.fn.markdown'
            },
            'pasteupload': {
                deps: [
                    'jquery',
                ],
                exports: '$.fn.pasteUploadImage'
            }
        }
    });
    require(['form', 'upload'], function (Form, Upload) {
        var _bindevent = Form.events.bindevent;
        Form.events.bindevent = function (form) {
            _bindevent.apply(this, [form]);
            try {
                if ($(".editor_markdown", form).size() > 0) {
                    require(['bootstrap-markdown', 'hyperdown', 'pasteupload'], function (undefined, undefined, undefined) {
                        $.fn.markdown.messages.zh = {
                            Bold: "粗体",
                            Italic: "斜体",
                            Heading: "标题",
                            "URL/Link": "链接",
                            Image: "图片",
                            List: "列表",
                            "Unordered List": "无序列表",
                            "Ordered List": "有序列表",
                            Code: "代码",
                            Quote: "引用",
                            Preview: "预览",
                            "strong text": "粗体",
                            "emphasized text": "强调",
                            "heading text": "标题",
                            "enter link description here": "输入链接说明",
                            "Insert Hyperlink": "URL地址",
                            "enter image description here": "输入图片说明",
                            "Insert Image Hyperlink": "图片URL地址",
                            "enter image title here": "在这里输入图片标题",
                            "list text here": "这里是列表文本",
                            "code text here": "这里输入代码",
                            "quote here": "这里输入引用文本"
                        };
                        var parser = new HyperDown();
                        window.marked = function (text) {
                            return parser.makeHtml(text);
                        };
                        //粘贴上传图片
                        $.fn.pasteUploadImage.defaults = $.extend(true, $.fn.pasteUploadImage.defaults, {
                            fileName: "file",
                            appendMimetype: false,
                            ajaxOptions: {
                                url: Fast.api.fixurl(Config.upload.uploadurl),
                                beforeSend: function (jqXHR, settings) {
                                    $.each(Config.upload.multipart, function (i, j) {
                                        settings.data.append(i, j);
                                    });
                                    return true;
                                }
                            },
                            success: function (data, filename, file) {
                                var ret = Upload.events.onUploadResponse(data);
                                $(this).insertToTextArea(filename, Config.upload.cdnurl + data.data.url);
                                return false;
                            },
                            error: function (data, filename, file) {
                                console.log(data, filename, file);
                            }
                        });
                        //手动选择上传图片
                        $(document).on("change", "#selectimage", function () {
                            $.each($(this)[0].files, function (i, file) {
                                $("").uploadFile(file, file.name);
                            });
                            //$("#message").pasteUploadImage();
                        });
                        $(".editor_markdown", form).each(function () {
                            $(this).markdown({
                                resize: 'vertical',
                                language: 'zh',
                                iconlibrary: 'fa',
                                autofocus: false,
                                savable: false,
                                additionalButtons: [
                                    [{
                                        name: "groupCustom",
                                        data: [{
                                            name: "cmdSelectImage",
                                            toggle: false,
                                            title: "Select image",
                                            icon: "fa fa-file-image-o",
                                            callback: function (e) {
                                                parent.Fast.api.open("general/attachment/select?element_id=&multiple=true&mimetype=image/*", __('Choose'), {
                                                    callback: function (data) {
                                                        var urlArr = data.url.split(/\,/);
                                                        $.each(urlArr, function () {
                                                            var url = Fast.api.cdnurl(this);
                                                            e.replaceSelection("\n" + '![输入图片说明](' + url + ' "在这里输入图片标题")');
                                                        });
                                                        e.$element.blur();
                                                        e.$element.focus();
                                                    }
                                                });
                                                return false;
                                            }
                                        }, {
                                            name: "cmdSelectAttachment",
                                            toggle: false,
                                            title: "Select image",
                                            icon: "fa fa-file",
                                            callback: function (e) {
                                                parent.Fast.api.open("general/attachment/select?element_id=&multiple=true&mimetype=*", __('Choose'), {
                                                    callback: function (data) {
                                                        var urlArr = data.url.split(/\,/);
                                                        $.each(urlArr, function () {
                                                            var url = Fast.api.cdnurl(this);
                                                            e.replaceSelection("\n" + '[输入链接说明](' + url + ')');
                                                        });
                                                        e.$element.blur();
                                                        e.$element.focus();
                                                    }
                                                });
                                                return false;
                                            }
                                        }]
                                    }]
                                ]
                            });
                            $(this).pasteUploadImage();
                        });
                    });
                }
            } catch (e) {

            }

        };
    });

    require.config({
        paths: {
            'simditor': '../addons/simditor/js/simditor.min',
        },
        shim: {
            'simditor': [
                'css!../addons/simditor/css/simditor.min.css'
            ]
        }
    });
    require(['form'], function (Form) {
        var _bindevent = Form.events.bindevent;
        Form.events.bindevent = function (form) {
            _bindevent.apply(this, [form]);
            if ($(".editor_simditor", form).size() > 0) {
                //修改上传的接口调用
                require(['upload', 'simditor'], function (Upload, Simditor) {
                    var editor, mobileToolbar, toolbar;
                    Simditor.locale = 'zh-CN';
                    Simditor.list = {};
                    toolbar = ['title', 'bold', 'italic', 'underline', 'strikethrough', 'fontScale', 'color', '|', 'ol', 'ul', 'blockquote', 'code', 'table', '|', 'link', 'image', 'hr', '|', 'indent', 'outdent', 'alignment'];
                    mobileToolbar = ["bold", "underline", "strikethrough", "color", "ul", "ol"];
                    $(".editor_simditor", form).each(function () {
                        var id = $(this).attr("id");
                        editor = new Simditor({
                            textarea: this,
                            toolbarFloat: false,
                            toolbar: toolbar,
                            pasteImage: true,
                            defaultImage: Config.__CDN__ + '/assets/addons/simditor/images/image.png',
                            upload: { url: '/' }
                        });
                        editor.uploader.on('beforeupload', function (e, file) {
                            Upload.api.send(file.obj, function (data) {
                                var url = Fast.api.cdnurl(data.url);
                                editor.uploader.trigger("uploadsuccess", [file, { success: true, file_path: url }]);
                            });
                            return false;
                        });
                        editor.on("blur", function () {
                            this.textarea.trigger("blur");
                        });
                        Simditor.list[id] = editor;
                    });
                });
            }
        }
    });
    require.config({
        paths: {
            'summernote': '../addons/summernote/lang/summernote-zh-CN.min'
        },
        shim: {
            'summernote': ['../addons/summernote/js/summernote.min', 'css!../addons/summernote/css/summernote.css'],
        }
    });
    require(['form', 'upload'], function (Form, Upload) {
        var _bindevent = Form.events.bindevent;
        Form.events.bindevent = function (form) {
            _bindevent.apply(this, [form]);
            try {
                //绑定summernote事件
                if ($(".summernote,.editor", form).size() > 0) {
                    require(['summernote'], function () {
                        var imageButton = function (context) {
                            var ui = $.summernote.ui;
                            var button = ui.button({
                                contents: '<i class="fa fa-file-image-o"/>',
                                tooltip: __('Choose'),
                                click: function () {
                                    parent.Fast.api.open("general/attachment/select?element_id=&multiple=true&mimetype=image/*", __('Choose'), {
                                        callback: function (data) {
                                            var urlArr = data.url.split(/\,/);
                                            $.each(urlArr, function () {
                                                var url = Fast.api.cdnurl(this);
                                                context.invoke('editor.insertImage', url);
                                            });
                                        }
                                    });
                                    return false;
                                }
                            });
                            return button.render();
                        };
                        var attachmentButton = function (context) {
                            var ui = $.summernote.ui;
                            var button = ui.button({
                                contents: '<i class="fa fa-file"/>',
                                tooltip: __('Choose'),
                                click: function () {
                                    parent.Fast.api.open("general/attachment/select?element_id=&multiple=true&mimetype=*", __('Choose'), {
                                        callback: function (data) {
                                            var urlArr = data.url.split(/\,/);
                                            $.each(urlArr, function () {
                                                var url = Fast.api.cdnurl(this);
                                                var node = $("<a href='" + url + "'>" + url + "</a>");
                                                context.invoke('insertNode', node[0]);
                                            });
                                        }
                                    });
                                    return false;
                                }
                            });
                            return button.render();
                        };
                        var HelloButton = function (context) {

                            var ui = $.summernote.ui;

                            // create button

                            var button = ui.button({

                                contents: '<i class="fa fa-child"/> 知识库',

                                tooltip: 'hello',

                                click: function () {
                                    $('#modal-default-knowledge').modal('toggle');
                                    $('.show-posts').html('');
                                    $('.search-posts').html('');
                                }

                            });

                            return button.render();  // return button as jquery object

                        }
                        $(".summernote,.editor", form).summernote({
                            height: 250,
                            lang: 'zh-CN',
                            fontNames: [
                                'Arial', 'Arial Black', 'Serif', 'Sans', 'Courier',
                                'Courier New', 'Comic Sans MS', 'Helvetica', 'Impact', 'Lucida Grande',
                                "Open Sans", "Hiragino Sans GB", "Microsoft YaHei",
                                '微软雅黑', '宋体', '黑体', '仿宋', '楷体', '幼圆',
                            ],
                            fontNamesIgnoreCheck: [
                                "Open Sans", "Microsoft YaHei",
                                '微软雅黑', '宋体', '黑体', '仿宋', '楷体', '幼圆'
                            ],
                            toolbar: [
                                ['style', ['style', 'undo', 'redo']],
                                ['font', ['bold', 'underline', 'strikethrough', 'clear']],
                                ['fontname', ['color', 'fontname', 'fontsize']],
                                ['para', ['ul', 'ol', 'paragraph', 'height']],
                                ['table', ['table', 'hr']],
                                ['insert', ['link', 'picture', 'video']],
                                ['select', ['image', 'attachment']],
                                ['view', ['fullscreen', 'codeview', 'help']],
                                ['mybutton', ['hello']]
                            ],
                            buttons: {
                                image: imageButton,
                                attachment: attachmentButton,
                                hello: HelloButton
                            },
                            dialogsInBody: true,
                            followingToolbar: false,
                            callbacks: {
                                onChange: function (contents) {
                                    $(this).val(contents);
                                    $(this).trigger('change');
                                },
                                onInit: function () {
                                },
                                onImageUpload: function (files) {

                                    var that = this;
                                    //依次上传图片
                                    for (var i = 0; i < files.length; i++) {
                                        Upload.api.send(files[i], function (data) {
                                            var url = Fast.api.cdnurl(data.url);
                                            $(that).summernote("insertImage", url, 'filename');
                                        });
                                    }
                                },
                                onPaste: function (ne) {
                                    ne.preventDefault ? ne.preventDefault() : (ne.returnValue = false);
                                },
                            }
                        });
                    });
                }
            } catch (e) {

            }

        };
    });

});