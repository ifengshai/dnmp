define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'echarts'], function ($, undefined, Backend, Table, Form) {
    Table.api.init({
        showJumpto: true,
        searchFormVisible: true,
        pageList: [10, 25, 50, 100],
        escape: false,
        extend: {
            index_url: 'kpi/task_load' + location.search,
            table: 'kpi',
        }
    });
    let table = $('#table');
    let bootstrapTable = {
        url: '',
        pk: 'id',
        sortName: 'id'
    };

    return {
        task_load() {
            bootstrapTable.url = 'kpi/task_load' + location.search;
            bootstrapTable.columns = [
                { field: 'id', title: __('Id') },
                { field: 'title', title: __('test') },
            ];
            table.bootstrapTable(bootstrapTable);
        },
        completion() {
            bootstrapTable.url = 'kpi/completion' + location.search;
            bootstrapTable.columns = [

            ];
            table.bootstrapTable(bootstrapTable);
        },
        overdue() {
            bootstrapTable.url = 'kpi/overdue' + location.search;
            bootstrapTable.columns = [

            ];
            table.bootstrapTable(bootstrapTable);
        }
    }
});