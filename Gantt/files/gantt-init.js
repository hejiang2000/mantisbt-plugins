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
