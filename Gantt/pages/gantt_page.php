<?php
/**
 * Gantt Plugin - Gantt Chart Page using VisActor VTable Gantt
 *
 * @package Gantt
 * @author Plugin Author
 * @copyright Copyright (c) 2026
 * @license GNU General Public License v2 or later
 */

auth_ensure_user_authenticated();

// 获取当前项目
$t_project_id = helper_get_current_project();

// 获取所有开发人员（access_level >= DEVELOPER）
$t_user_table = db_get_table('user');
$t_project_user_list_table = db_get_table('project_user_list');
$t_query_users = "
    SELECT DISTINCT u.id, u.username, u.realname 
    FROM $t_user_table u 
    LEFT JOIN $t_project_user_list_table pul ON u.id = pul.user_id AND pul.project_id = " . db_param() . "
    WHERE u.enabled = 1 
    AND (
        u.access_level = " . DEVELOPER . "
        OR (pul.access_level IS NOT NULL AND pul.access_level = " . DEVELOPER . ")
    )
    ORDER BY u.username
";
$t_result_users = db_query($t_query_users, array($t_project_id));
$t_handlers = array();
while ($t_row = db_fetch_array($t_result_users)) {
    $t_handlers[$t_row['id']] = array(
        'id' => $t_row['id'],
        'username' => $t_row['username'],
        'realname' => $t_row['realname']
    );
}

// 获取任务数据（bug, period, handler, status）
$t_bug_table = db_get_table('bug');
$t_bug_history_table = db_get_table('bug_history');

$t_query_tasks = "
    WITH status_periods AS (
        SELECT 
            bh.bug_id,
            bh.date_modified AS status_start,
            LEAD(bh.date_modified) OVER (PARTITION BY bh.bug_id ORDER BY bh.date_modified) AS status_end,
            bh.new_value AS status_value,
            CASE bh.new_value
                WHEN '10' THEN '新建'
                WHEN '20' THEN '反馈'
                WHEN '30' THEN '认可'
                WHEN '40' THEN '已确认'
                WHEN '50' THEN '已分配'
                WHEN '80' THEN '已解决'
                WHEN '90' THEN '已关闭'
                ELSE bh.new_value
            END AS status_name
        FROM $t_bug_history_table bh
        WHERE bh.field_name = 'status'
    ),
    handler_periods AS (
        SELECT 
            bh.bug_id,
            bh.date_modified AS handler_start,
            LEAD(bh.date_modified) OVER (PARTITION BY bh.bug_id ORDER BY bh.date_modified) AS handler_end,
            CAST(bh.new_value AS UNSIGNED) AS handler_id,
            u.realname AS handler_name
        FROM $t_bug_history_table bh
        LEFT JOIN $t_user_table u ON CAST(bh.new_value AS UNSIGNED) = u.id
        WHERE bh.field_name = 'handler_id'
    )
    SELECT 
        s.bug_id,
        b.summary,
        s.status_start,
        s.status_end,
        h.handler_start,
        h.handler_end,
        GREATEST(s.status_start, h.handler_start) AS start_time,
        LEAST(
            COALESCE(s.status_end, UNIX_TIMESTAMP()),
            COALESCE(h.handler_end, UNIX_TIMESTAMP())
        ) AS end_time,
        s.status_value,
        s.status_name,
        h.handler_id,
        h.handler_name
    FROM status_periods s
    INNER JOIN handler_periods h ON s.bug_id = h.bug_id
        AND s.status_start < COALESCE(h.handler_end, UNIX_TIMESTAMP())
        AND COALESCE(s.status_end, UNIX_TIMESTAMP()) > h.handler_start
    LEFT JOIN $t_bug_table b ON s.bug_id = b.id
    WHERE LEAST(
            COALESCE(s.status_end, UNIX_TIMESTAMP()),
            COALESCE(h.handler_end, UNIX_TIMESTAMP())
        ) >= UNIX_TIMESTAMP('2026-01-01')
    AND (
        s.status_value <> '90' OR GREATEST(s.status_start, h.handler_start) >= UNIX_TIMESTAMP('2026-01-01')
    )
    " . ($t_project_id != ALL_PROJECTS ? " AND b.project_id = " . db_param() : "") . "
    ORDER BY s.bug_id, GREATEST(s.status_start, h.handler_start)
";

$t_params = ($t_project_id != ALL_PROJECTS) ? array($t_project_id) : array();
$t_result_tasks = db_query($t_query_tasks, $t_params);

// 整理任务数据
$t_task_data = array();
while ($t_row = db_fetch_array($t_result_tasks)) {
    $t_task_data[] = array(
        'bug_id' => $t_row['bug_id'],
        'summary' => $t_row['summary'],
        'start_time' => $t_row['start_time'],
        'end_time' => $t_row['end_time'],
        'status_value' => $t_row['status_value'],
        'status_name' => $t_row['status_name'],
        'handler_id' => $t_row['handler_id'],
        'handler_name' => $t_row['handler_name']
    );
}

// 生成 Gantt 任务数据 - 使用嵌套 children 结构
$t_gantt_tasks = array();
$t_gantt_links = array();
$t_task_map = array(); // 用于存储每个 bug 的任务，便于建立依赖关系

// 按 handler 分组存储任务
$t_handler_tasks = array();

// 1. 先生成所有 bug 子任务（task/milestone类型），按 handler 分组
foreach ($t_task_data as $t_task) {
    $t_handler_username = '';
    $t_handler_realname = '';
    foreach ($t_handlers as $t_h) {
        if ($t_h['id'] == $t_task['handler_id']) {
            $t_handler_username = $t_h['username'];
            $t_handler_realname = $t_h['realname'] ? $t_h['realname'] : $t_h['username'];
            break;
        }
    }

    if (empty($t_handler_username)) {
        continue;
    }

    // 初始化 handler 的任务数组
    if (!isset($t_handler_tasks[$t_handler_username])) {
        $t_handler_tasks[$t_handler_username] = array(
            'handler_name' => $t_handler_realname,
            'tasks' => array()
        );
    }

    // 判断是否为里程碑（start_time 或 end_time 为空）
    $t_is_milestone = empty($t_task['start_time']) || empty($t_task['end_time']);

    if ($t_is_milestone) {
        // 里程碑类型
        $t_date = !empty($t_task['start_time']) ? $t_task['start_time'] : $t_task['end_time'];
        $t_task_id = $t_task['bug_id'] . '-' . $t_date;
        $t_gantt_task = array(
            'id' => $t_task_id,
            'text' => '#' . $t_task['bug_id'] . ' ' . $t_task['summary'] . ' (' . $t_task['status_name'] . ')',
            'type' => 'milestone',
            'startDate' => date('Y-m-d', $t_date),
            'status' => $t_task['status_name']
        );
    } else {
        // 普通任务类型
        $t_task_id = $t_task['bug_id'] . '-' . $t_task['start_time'] . '-' . $t_task['end_time'];
        $t_gantt_task = array(
            'id' => $t_task_id,
            'text' => '#' . $t_task['bug_id'] . ' ' . $t_task['summary'] . ' (' . $t_task['status_name'] . ')',
            'type' => 'task',
            'startDate' => date('Y-m-d', $t_task['start_time']),
            'endDate' => date('Y-m-d', $t_task['end_time']),
            'status' => $t_task['status_name']
        );
    }

    // 添加到对应 handler 的任务列表
    $t_handler_tasks[$t_handler_username]['tasks'][] = $t_gantt_task;

    // 记录任务信息用于建立依赖关系
    if (!isset($t_task_map[$t_task['bug_id']])) {
        $t_task_map[$t_task['bug_id']] = array();
    }
    $t_task_map[$t_task['bug_id']][] = array(
        'id' => $t_task_id,
        'end_time' => $t_task['end_time'],
        'start_time' => $t_task['start_time'],
        'type' => $t_is_milestone ? 'milestone' : 'task'
    );
}

// 2. 生成嵌套结构的 handler 父任务（project类型）
foreach ($t_handler_tasks as $t_handler_username => $t_handler_data) {
    $t_gantt_tasks[] = array(
        'id' => $t_handler_username,
        'text' => $t_handler_data['handler_name'],
        'type' => 'project',
        'children' => $t_handler_data['tasks']
    );
}

// 3. 生成 FinishToStart 依赖关系
foreach ($t_task_map as $t_bug_id => $t_bug_tasks) {
    // 按时间排序
    usort($t_bug_tasks, function($a, $b) {
        return $a['start_time'] - $b['start_time'];
    });

    // 建立相邻任务之间的依赖关系
    for ($i = 0; $i < count($t_bug_tasks) - 1; $i++) {
        $t_current_task = $t_bug_tasks[$i];
        $t_next_task = $t_bug_tasks[$i + 1];

        // 前一个任务的 end_date 与后一个任务的 start_date 相等
        if ($t_current_task['end_time'] == $t_next_task['start_time'] && $t_current_task['type'] === 'task') {
            $t_gantt_links[] = array(
                'type' => 'finish_to_start',
                'linkedFromTaskKey' => $t_current_task['id'],
                'linkedToTaskKey' => $t_next_task['id']
            );
        }
    }
}

// 转换为 JSON
$t_tasks_json = json_encode($t_gantt_tasks);
$t_links_json = json_encode($t_gantt_links);

layout_page_header(plugin_lang_get('gantt'));
layout_page_begin();
?>

<div class="col-md-12 col-xs-12">
    <div class="space-10"></div>

    <!-- Tab Navigation -->
    <ul class="nav nav-tabs" id="gantt-tabs">
        <li class="active">
            <a href="#person-task-table-tab" data-toggle="tab">
                <i class="ace-icon fa fa-table"></i>
                任务统计
            </a>
        </li>
        <li>
            <a href="#gantt-chart-tab" data-toggle="tab">
                <i class="ace-icon fa fa-bar-chart"></i>
                <?php echo plugin_lang_get('gantt'); ?>
            </a>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content">
        <!-- Person Task Table Tab -->
        <div class="tab-pane active" id="person-task-table-tab">
            <div class="widget-box widget-color-blue2">
                <div class="widget-header widget-header-small">
                    <h4 class="widget-title lighter">
                        <i class="ace-icon fa fa-table"></i>
                        任务统计
                    </h4>
                </div>

                <div class="widget-body">
                    <div class="widget-main no-padding">
                        <div id="person-task-table-container"
                             style="width: 100%; height: 800px; overflow: auto;"
                             data-gantt-tasks="<?php echo htmlspecialchars($t_tasks_json, ENT_QUOTES, 'UTF-8'); ?>">
                            <table id="person-task-table" class="table table-bordered table-striped table-hover">
                                <thead id="person-task-table-head">
                                    <!-- 表头将由 JavaScript 动态生成 -->
                                </thead>
                                <tbody id="person-task-table-body">
                                    <!-- 表格内容将由 JavaScript 动态生成 -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gantt Chart Tab -->
        <div class="tab-pane" id="gantt-chart-tab">
            <div class="widget-box widget-color-blue2">
                <div class="widget-header widget-header-small">
                    <h4 class="widget-title lighter">
                        <i class="ace-icon fa fa-bar-chart"></i>
                        <?php echo plugin_lang_get('gantt'); ?>
                    </h4>
                </div>

                <div class="widget-body">
                    <div class="widget-main no-padding">
                        <div id="gantt-container"
                             style="width: 100%; height: 800px;"
                             data-gantt-tasks="<?php echo htmlspecialchars($t_tasks_json, ENT_QUOTES, 'UTF-8'); ?>"
                             data-gantt-links="<?php echo htmlspecialchars($t_links_json, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 引入 VisActor VTable 和 VTable Gantt -->
<script src="<?php echo plugin_file('vtable-gantt.min.js'); ?>"></script>
<script src="<?php echo plugin_file('gantt-init.js'); ?>"></script>

<?php
layout_page_end();
