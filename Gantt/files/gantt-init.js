/**
 * Gantt Chart Initialization
 * Gantt Plugin for MantisBT
 */

(function() {
    'use strict';

    // 获取容器元素
    var container = document.getElementById('gantt-container');
    if (!container) {
        console.error('Gantt container not found');
        return;
    }

    // 从 data 属性获取数据
    var mantisRecords = [];
    var mantisLinks = [];

    try {
        var dataAttr = container.getAttribute('data-gantt-tasks');
        var linksAttr = container.getAttribute('data-gantt-links');

        if (dataAttr) {
            mantisRecords = JSON.parse(dataAttr);
            // 初始化 hierarchyState 为 expand 展开所有任务
            for (var i = 0; i < mantisRecords.length; i++) {
                mantisRecords[i].hierarchyState = 'expand';
                for (var j = 0; j < mantisRecords[i].children.length; j++) {
                    if (mantisRecords[i].children[j].status === '已关闭') {
                        mantisRecords[i].children[j].type = 'milestone';
                        mantisRecords[i].children[j].hierarchyState = 'expand';
                        const startDate = new Date(mantisRecords[i].children[j].startDate);
                        startDate.setDate(startDate.getDate() + 2);
                        mantisRecords[i].children[j].startDate = startDate.toISOString().split('T')[0];
                    }
                }
            }
        }
        if (linksAttr) {
            mantisLinks = JSON.parse(linksAttr);
        }
    } catch (e) {
        console.error('Failed to parse gantt data:', e);
    }

        const mantisColumns = [
            {
                field: 'text',
                title: '名称',
                width: 150,
                tree: true,
                sort: true,
                editor: 'input'
            },
            {
                field: 'startDate',
                title: '开始时间',
                width: 'auto',
                sort: true,
                editor: 'date-input'
            },
            {
                field: 'endDate',
                title: '结束时间',
                width: 'auto',
                sort: true,
                editor: 'date-input'
            },
            {
                field: 'status',
                title: '状态',
                width: 'auto',
                sort: true,
                editor: 'input'
            }
        ];
        const option = {
            minDate: '2026-01-01',
            tasksShowMode: "Tasks_Separate",
            records: mantisRecords,
            taskListTable: {
                columns: mantisColumns,
            },
            dependency: {
                links: mantisLinks,
            },
            grid: {
                verticalLine: {
                    lineWidth: 1,
                    lineColor: '#e1e4e8'
                },
                horizontalLine: {
                    lineWidth: 1,
                    lineColor: '#e1e4e8'
                }
            },
            headerRowHeight: 30,
            rowHeight: 40,
            taskBar: {
                startDateField: 'startDate',
                endDateField: 'endDate',
                progressField: 'progress',
                resizable: true,
                moveable: true,
                hoverBarStyle: {
                    barOverlayColor: 'rgba(99, 144, 0, 0.4)'
                },
                labelText: '{text}',
                labelTextStyle: {
                    fontFamily: 'Arial',
                    fontSize: 14,
                    textAlign: 'left',
                    textOverflow: 'ellipsis'
                },
                barStyle: {
                    width: 20,
                    /** 任务条的颜色 */
                    barColor: '#91e8e0',
                    /** 已完成部分任务条的颜色 */
                    completedBarColor: '#ccc',
                    /** 任务条的圆角 */
                    cornerRadius: 8,
                    /** 任务条的边框 */
                    borderLineWidth: 1,
                    /** 边框颜色 */
                    borderColor: 'black'
                },
                customLayout: args => {
                    const { width, height, index, startDate, endDate, taskDays, progress, taskRecord, ganttInstance } = args;
                    const oneDayWidth = Math.ceil(width / taskDays);
                    const endsWidth = Math.floor(oneDayWidth / 3);
                    const containerWidth = 200; //width - 2 * (oneDayWidth - endsWidth);
                    const container = new VTableGantt.VRender.Group({
                        width: width - 200,
                        height,
                        display: 'flex',
                        flexDirection: 'row',
                        flexWrap: 'nowrap',
                    });
                    return {
                        rootContainer: container,
                        renderDefaultBar: true,
                        // renderDefaultText: true,
                        // renderDefaultResizeIcon: true,
                    };
                },
            },
            timelineHeader: {
                colWidth: 60,
                backgroundColor: '#EEF1F5',
                horizontalLine: {
                    lineWidth: 1,
                    lineColor: '#e1e4e8'
                },
                verticalLine: {
                    lineWidth: 1,
                    lineColor: '#e1e4e8'
                },
                scales: [
                    {
                        unit: 'year',
                        step: 1,
                        format(date) {
                            return `${date.dateIndex}年`;
                        },
                        style: {
                            fontSize: 14,
                        },
                    },
                    {
                        unit: 'month',
                        step: 1,
                        format(date) {
                            return `${date.dateIndex}月`;
                        },
                        style: {
                            fontSize: 14,
                        },
                    },
                    {
                        unit: 'day',
                        step: 1,
                        format(date) {
                            return date.dateIndex.toString();
                        },
                        style: {
                            fontSize: 14,
                        },
                    }
                ]
            },
        };

    // 创建 Gantt 实例
    try {
        const ganttInstance = new VTableGantt.Gantt(container, option);
        window['ganttInstance'] = ganttInstance;
        console.log('Gantt initialized successfully');
    } catch (e) {
        console.error('Failed to initialize Gantt:', e);
        container.innerHTML =
            '<div style="text-align: center; padding: 50px; color: #999;">' +
            '<i class="ace-icon fa fa-exclamation-circle fa-2x"></i><br><br>' +
            '甘特图初始化失败: ' + e.message +
            '</div>';
    }
})();

/**
 * 人员任务统计表格初始化
 */
(function() {
    'use strict';

    var tableContainer = document.getElementById('person-task-table-container');
    if (!tableContainer) {
        return;
    }

    var mantisRecords = [];
    try {
        var dataAttr = tableContainer.getAttribute('data-gantt-tasks');
        if (dataAttr) {
            mantisRecords = JSON.parse(dataAttr);
        }
    } catch (e) {
        console.error('Failed to parse person task table data:', e);
        return;
    }

    // 配置参数
    var startDate = new Date('2026-02-01');
    var daysToShow = 60; // 显示30天的数据

    // 生成日期列
    function generateDateColumns() {
        var columns = [];
        for (var i = 0; i < daysToShow; i++) {
            var date = new Date(startDate);
            date.setDate(date.getDate() + i);
            columns.push({
                date: date,
                dateStr: date.toISOString().split('T')[0],
                displayStr: (date.getMonth() + 1) + '/' + date.getDate()
            });
        }
        return columns;
    }

    // 检查任务在指定日期是否处于已确认状态
    function isTaskConfirmedOnDate(task, dateStr) {
        if (task.status !== '已确认') {
            return false;
        }
        var taskStart = task.startDate;
        var taskEnd = task.endDate || task.startDate;
        return dateStr >= taskStart && dateStr <= taskEnd;
    }

    // 获取任务在指定日期的详细信息
    function getTaskDetailsForDate(task, dateStr) {
        if (!isTaskConfirmedOnDate(task, dateStr)) {
            return null;
        }
        return {
            text: task.text,
            startDate: task.startDate,
            endDate: task.endDate || task.startDate,
            status: task.status
        };
    }

    // 生成表格
    function generateTable() {
        var dateColumns = generateDateColumns();
        var tableHead = document.getElementById('person-task-table-head');
        var tableBody = document.getElementById('person-task-table-body');

        if (!tableHead || !tableBody) {
            return;
        }

        // 生成表头
        var headerHtml = '<tr><th style="min-width: 100px; text-align: center; position: sticky; left: 0; background: #f5f5f5; z-index: 10;">人员</th>';
        for (var i = 0; i < dateColumns.length; i++) {
            var col = dateColumns[i];
            var isWeekend = col.date.getDay() === 0 || col.date.getDay() === 6;
            var bgStyle = isWeekend ? 'background: #f0f0f0;' : '';
            headerHtml += '<th style="min-width: 50px; text-align: center; ' + bgStyle + '">' + col.displayStr + '</th>';
        }
        headerHtml += '</tr>';
        tableHead.innerHTML = headerHtml;

        // 生成表格内容
        var bodyHtml = '';
        for (var r = 0; r < mantisRecords.length; r++) {
            var person = mantisRecords[r];
            var personName = person.text || '';
            var children = person.children || [];

            bodyHtml += '<tr>';
            bodyHtml += '<td style="min-width: 100px; text-align: center; position: sticky; left: 0; background: #fff; z-index: 9; font-weight: bold;">' + escapeHtml(personName) + '</td>';

            for (var d = 0; d < dateColumns.length; d++) {
                var dateCol = dateColumns[d];
                var isWeekend = dateCol.date.getDay() === 0 || dateCol.date.getDay() === 6;
                var bgStyle = isWeekend ? 'background: #f9f9f9;' : '';

                // 统计当天已确认状态的任务
                var confirmedTasks = [];
                var maxDaysDiff = 0; // 记录最大天数差
                for (var c = 0; c < children.length; c++) {
                    var task = children[c];
                    var taskDetail = getTaskDetailsForDate(task, dateCol.dateStr);
                    if (taskDetail) {
                        confirmedTasks.push(taskDetail);
                        // 计算任务开始日期与当前单元格日期的天数差
                        var taskStart = new Date(task.startDate);
                        var cellDate = new Date(dateCol.dateStr);
                        var daysDiff = Math.floor((cellDate - taskStart) / (1000 * 60 * 60 * 24));
                        if (daysDiff > maxDaysDiff) {
                            maxDaysDiff = daysDiff;
                        }
                    }
                }

                var count = confirmedTasks.length;
                var cellContent = count > 0 ? count : '';
                var cellClass = count > 0 ? 'has-tasks' : '';

                // 根据任务开始日期设置数字颜色
                var textColorStyle = '';
                if (count > 0) {
                    if (maxDaysDiff >= 14) {
                        textColorStyle = 'color: #d9534f;'; // 红色 - 14天前开始
                    } else if (maxDaysDiff >= 7) {
                        textColorStyle = 'color: #f0ad4e;'; // 橙色 - 7天前开始
                    }
                }

                bodyHtml += '<td style="min-width: 50px; text-align: center; ' + bgStyle + ' ' + textColorStyle + '" class="' + cellClass + '"';
                if (count > 0) {
                    bodyHtml += ' data-toggle="popover" data-html="true" data-container="body" data-placement="auto" data-content="' + generatePopoverContent(confirmedTasks) + '"';
                }
                bodyHtml += '>' + cellContent + '</td>';
            }

            bodyHtml += '</tr>';
        }
        tableBody.innerHTML = bodyHtml;

        // 初始化 popover
        initializePopovers();
    }

    // 生成 popover 内容
    function generatePopoverContent(tasks) {
        var content = '<div style=\"text-align: left; max-width: 400px;\">';
        for (var i = 0; i < tasks.length; i++) {
            var task = tasks[i];
            content += '<div style=\"margin-bottom: 2px;\">';
            content += '<div style=\"text-align: left;\">' + escapeHtml(task.text) + '</div>';
            content += '<small style=\"color: #666;\">' + task.startDate + ' ~ ' + task.endDate + '</small>';
            content += '</div>';
            if (i < tasks.length - 1) {
                content += '<hr style=\"margin: 1px 0;\">';
            }
        }
        content += '</div>';
        // 转义双引号用于 HTML 属性
        return content.replace(/"/g, '&quot;');
    }

    // HTML 转义
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // 初始化 Bootstrap popover (Bootstrap 3)
    function initializePopovers() {
        if (typeof jQuery === 'undefined' || !jQuery.fn.popover) {
            console.warn('jQuery or Bootstrap popover not loaded yet, retrying...');
            setTimeout(initializePopovers, 500);
            return;
        }

        // Bootstrap 3: 初始化 popover
        jQuery('#person-task-table [data-toggle="popover"]').popover({
            trigger: 'hover',
            html: true
        });

        console.log('Popovers initialized successfully');
    }

    // 添加表格样式
    function addTableStyles() {
        var style = document.createElement('style');
        style.textContent = `
            #person-task-table {
                table-layout: fixed;
                border-collapse: collapse;
                width: auto;
                min-width: 100%;
            }
            #person-task-table th,
            #person-task-table td {
                border: 1px solid #ddd;
                padding: 4px 2px;
                font-size: 12px;
            }
            #person-task-table th {
                background: #f5f5f5;
                font-weight: bold;
                position: sticky;
                top: 0;
                z-index: 11;
            }
            #person-task-table td.has-tasks {
                background: #e3f2fd !important;
                color: #1976d2;
                font-weight: bold;
                cursor: pointer;
            }
            #person-task-table td.has-tasks:hover {
                background: #bbdefb !important;
            }
            #person-task-table-container {
                overflow: auto;
            }
            #person-task-table thead th:first-child,
            #person-task-table tbody td:first-child {
                position: sticky;
                left: 0;
                z-index: 10;
                border-right: 2px solid #ddd;
            }
            #person-task-table thead th:first-child {
                z-index: 12;
                background: #f5f5f5;
            }
            div.popover {
                max-width: 640px;
            }
        `;
        document.head.appendChild(style);
    }

    // 延迟初始化，确保 DOM 完全加载
    function init() {
        addTableStyles();
        generateTable();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // 当切换到该 tab 时重新初始化 popover (Bootstrap 3)
    function setupTabListener() {
        if (typeof jQuery === 'undefined') {
            setTimeout(setupTabListener, 500);
            return;
        }

        jQuery('#gantt-tabs a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
            var target = jQuery(e.target).attr('href');
            if (target === '#person-task-table-tab') {
                // 延迟一点初始化，确保 tab 内容已完全显示
                setTimeout(initializePopovers, 200);
            }
        });
    }

    // 设置 tab 监听器
    setupTabListener();
})();
