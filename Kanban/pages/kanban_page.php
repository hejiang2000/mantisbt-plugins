<?php

auth_ensure_user_authenticated();

layout_page_header(plugin_lang_get('page_title'));
layout_page_begin();

// 获取项目ID
$t_project_id = helper_get_current_project();

// 定义状态常量
// 正在处理的问题状态：已确认
$t_in_progress_statuses = array(CONFIRMED);
// 待处理问题状态：新建、已分配、反馈、认可
$t_pending_statuses = array(NEW_, ASSIGNED, FEEDBACK, ACKNOWLEDGED);

// 获取不同角色的用户
$t_developer_users = kanban_get_users_by_role(DEVELOPER, $t_project_id);
$t_reporter_users = kanban_get_users_by_role(REPORTER, $t_project_id);
$t_manager_users = kanban_get_manager_users($t_project_id);

?>

<!-- <div class="col-md-12 col-xs-12"> -->
    <!-- Tab 页签 -->
    <!-- <div class="widget-box widget-color-blue2">
        <div class="widget-header"> -->
            <ul class="nav nav-tabs padding-18" id="kanban-tabs">
                <li class="active">
                    <a href="#tab-developers" data-toggle="tab">
                        <i class="ace-icon fa fa-code"></i>
                        <?php echo plugin_lang_get('tab_developers'); ?>
                    </a>
                </li>
                <li>
                    <a href="#tab-reporters" data-toggle="tab">
                        <i class="ace-icon fa fa-bug"></i>
                        <?php echo plugin_lang_get('tab_reporters'); ?>
                    </a>
                </li>
                <li>
                    <a href="#tab-managers" data-toggle="tab">
                        <i class="ace-icon fa fa-user-tie"></i>
                        <?php echo plugin_lang_get('tab_managers'); ?>
                    </a>
                </li>
            </ul>
        <!-- </div>

        <div class="widget-body">
            <div class="widget-main no-padding"> -->
                <div class="tab-content">
                    <!-- 开发人员页签 -->
                    <div class="tab-pane active" id="tab-developers">
                        <?php kanban_render_user_table($t_developer_users, $t_in_progress_statuses, $t_pending_statuses, $t_project_id); ?>
                    </div>

                    <!-- 报告人员页签 -->
                    <div class="tab-pane" id="tab-reporters">
                        <?php kanban_render_user_table($t_reporter_users, $t_in_progress_statuses, $t_pending_statuses, $t_project_id); ?>
                    </div>

                    <!-- 管理人员页签 -->
                    <div class="tab-pane" id="tab-managers">
                        <?php kanban_render_user_table($t_manager_users, $t_in_progress_statuses, $t_pending_statuses, $t_project_id); ?>
                    </div>
                </div>
            <!-- </div>
        </div>
    </div>
</div> -->

<?php
layout_page_end();

/**
 * 渲染用户表格
 * @param array $p_users 用户列表
 * @param array $p_in_progress_statuses 进行中状态数组
 * @param array $p_pending_statuses 待处理状态数组
 * @param int $p_project_id 项目ID
 */
function kanban_render_user_table($p_users, $p_in_progress_statuses, $p_pending_statuses, $p_project_id) {
    // 构建状态标签
    $t_in_progress_status_labels = array();
    foreach ($p_in_progress_statuses as $t_status) {
        $t_in_progress_status_labels[] = get_enum_element('status', $t_status);
    }
    $t_pending_status_labels = array();
    foreach ($p_pending_statuses as $t_status) {
        $t_pending_status_labels[] = get_enum_element('status', $t_status);
    }
?>
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover kanban-user-table">
            <thead>
                <tr>
                    <th class="kanban-col-user"><?php echo plugin_lang_get('col_user'); ?></th>
                    <th class="kanban-col-in-progress"><?php echo plugin_lang_get('col_in_progress'); ?> (<?php echo implode('/', $t_in_progress_status_labels); ?>)</th>
                    <th class="kanban-col-pending"><?php echo plugin_lang_get('col_pending'); ?> (<?php echo implode('/', $t_pending_status_labels); ?>)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($p_users as $t_user) {
                    $t_user_id = $t_user['id'];
                    $t_user_name = $t_user['username'];
                    $t_user_realname = $t_user['realname'];

                    // 跳过系统用户
                    if ($t_user_id == 0) {
                        continue;
                    }

                    // 获取该用户的问题
                    $t_in_progress_issues = kanban_get_user_issues_by_statuses($t_user_id, $p_in_progress_statuses, $p_project_id);
                    $t_pending_issues = kanban_get_user_issues_by_statuses($t_user_id, $p_pending_statuses, $p_project_id);
                ?>
                <tr>
                    <td class="kanban-user-cell">
                        <?php if ($t_user_realname) { ?>
                            <span class="kanban-realname"><?php echo string_display_line($t_user_realname); ?></span>
                            <span class="kanban-username">(<?php echo string_display_line($t_user_name); ?>)</span>
                        <?php } else { ?>
                            <span class="kanban-username"><?php echo string_display_line($t_user_name); ?></span>
                        <?php } ?>
                    </td>
                    <td class="kanban-issues-cell">
                        <?php if (!empty($t_in_progress_issues)) { ?>
                            <div class="kanban-issues-list">
                                <?php foreach ($t_in_progress_issues as $t_issue) { ?>
                                    <div class="kanban-issue-item">
                                        <a href="view.php?id=<?php echo $t_issue['id']; ?>" class="kanban-issue-link">
                                            <?php if ($t_issue['target_version']) { ?>
                                                <span class="kanban-issue-version">[<?php echo string_display_line($t_issue['target_version']); ?>]</span>
                                            <?php } ?>
                                            <?php echo string_display_line($t_issue['summary']); ?>
                                            <span class="kanban-issue-date">(<?php echo date('Y-m-d', $t_issue['assigned_date']); ?>)</span>
                                        </a>
                                    </div>
                                <?php } ?>
                            </div>
                        <?php } else { ?>
                            <span class="kanban-no-issues"><?php echo plugin_lang_get('no_issues'); ?></span>
                        <?php } ?>
                    </td>
                    <td class="kanban-issues-cell">
                        <?php if (!empty($t_pending_issues)) { ?>
                            <div class="kanban-issues-list">
                                <?php foreach ($t_pending_issues as $t_issue) { ?>
                                    <div class="kanban-issue-item">
                                        <a href="view.php?id=<?php echo $t_issue['id']; ?>" class="kanban-issue-link">
                                            <?php if ($t_issue['target_version']) { ?>
                                                <span class="kanban-issue-version">[<?php echo string_display_line($t_issue['target_version']); ?>]</span>
                                            <?php } ?>
                                            <?php echo string_display_line($t_issue['summary']); ?>
                                            <span class="kanban-issue-date">(<?php echo date('Y-m-d', $t_issue['assigned_date']); ?>)</span>
                                        </a>
                                    </div>
                                <?php } ?>
                            </div>
                        <?php } else { ?>
                            <span class="kanban-no-issues"><?php echo plugin_lang_get('no_issues'); ?></span>
                        <?php } ?>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
<?php
}

/**
 * 获取指定角色的用户列表
 * @param int $p_role 角色ID
 * @param int $p_project_id 项目ID
 * @return array 用户列表
 */
function kanban_get_users_by_role($p_role, $p_project_id) {
    $t_user_table = db_get_table('user');
    $t_project_user_list_table = db_get_table('project_user_list');

    if ($p_project_id != ALL_PROJECTS && $p_project_id > 0) {
        // 从项目用户列表中获取指定角色的用户
        $t_query = "SELECT u.id, u.username, u.realname
                    FROM $t_user_table u
                    INNER JOIN $t_project_user_list_table pul ON u.id = pul.user_id
                    WHERE pul.project_id = " . db_param() . "
                    AND pul.access_level = " . db_param() . "
                    AND u.id > 0
                    ORDER BY u.username ASC";
        $t_params = array($p_project_id, $p_role);
    } else {
        // 获取全局具有该角色的用户（通过默认项目）
        $t_query = "SELECT DISTINCT u.id, u.username, u.realname
                    FROM $t_user_table u
                    INNER JOIN $t_project_user_list_table pul ON u.id = pul.user_id
                    WHERE pul.access_level = " . db_param() . "
                    AND u.id > 0
                    ORDER BY u.username ASC";
        $t_params = array($p_role);
    }

    $t_result = db_query($t_query, $t_params);

    $t_users = array();
    while ($t_row = db_fetch_array($t_result)) {
        $t_users[] = $t_row;
    }

    return $t_users;
}

/**
 * 获取管理人员列表（经理和管理员）
 * @param int $p_project_id 项目ID
 * @return array 用户列表
 */
function kanban_get_manager_users($p_project_id) {
    $t_user_table = db_get_table('user');
    $t_project_user_list_table = db_get_table('project_user_list');

    // 经理和管理员的角色级别
    $t_manager_level = MANAGER;
    $t_administrator_level = ADMINISTRATOR;

    if ($p_project_id != ALL_PROJECTS && $p_project_id > 0) {
        $t_query = "SELECT u.id, u.username, u.realname
                    FROM $t_user_table u
                    INNER JOIN $t_project_user_list_table pul ON u.id = pul.user_id
                    WHERE pul.project_id = " . db_param() . "
                    AND pul.access_level >= " . db_param() . "
                    AND u.id > 0
                    ORDER BY u.username ASC";
        $t_params = array($p_project_id, $t_manager_level);
    } else {
        $t_query = "SELECT DISTINCT u.id, u.username, u.realname
                    FROM $t_user_table u
                    INNER JOIN $t_project_user_list_table pul ON u.id = pul.user_id
                    WHERE pul.access_level >= " . db_param() . "
                    AND u.id > 0
                    ORDER BY u.username ASC";
        $t_params = array($t_manager_level);
    }

    $t_result = db_query($t_query, $t_params);

    $t_users = array();
    while ($t_row = db_fetch_array($t_result)) {
        $t_users[] = $t_row;
    }

    return $t_users;
}

/**
 * 获取用户指定多个状态的问题
 * @param int $p_user_id 用户ID
 * @param array $p_statuses 状态数组
 * @param int $p_project_id 项目ID
 * @return array 问题列表
 */
function kanban_get_user_issues_by_statuses($p_user_id, $p_statuses, $p_project_id = ALL_PROJECTS) {
    $t_bug_table = db_get_table('bug');

    if (empty($p_statuses)) {
        return array();
    }

    $t_params = array($p_user_id);

    // 构建状态 IN 查询
    $t_status_placeholders = array();
    foreach ($p_statuses as $t_status) {
        $t_status_placeholders[] = db_param();
        $t_params[] = $t_status;
    }

    $t_query = "SELECT id, summary, target_version
                FROM $t_bug_table
                WHERE handler_id = " . db_param() . "
                AND status IN (" . implode(', ', $t_status_placeholders) . ")";

    if ($p_project_id != ALL_PROJECTS) {
        $t_query .= " AND project_id = " . db_param();
        $t_params[] = $p_project_id;
    }

    $t_query .= " ORDER BY id DESC";

    $t_result = db_query($t_query, $t_params);

    $t_issues = array();
    while ($t_row = db_fetch_array($t_result)) {
        // 查询问题首次指派给当前用户的日期
        $t_row['assigned_date'] = kanban_get_first_assigned_date($t_row['id'], $p_user_id);
        $t_issues[] = $t_row;
    }

    return $t_issues;
}

/**
 * 获取问题首次指派给指定用户的日期
 * @param int $p_bug_id 问题ID
 * @param int $p_user_id 用户ID
 * @return int Unix时间戳
 */
function kanban_get_first_assigned_date($p_bug_id, $p_user_id) {
    $t_history_table = db_get_table('bug_history');

    // 查询历史记录中首次将该问题指派给指定用户的时间
    $t_query = "SELECT MIN(date_modified) as first_assigned_date
                FROM $t_history_table
                WHERE bug_id = " . db_param() . "
                AND field_name = 'handler_id'
                AND new_value = " . db_param();

    $t_result = db_query($t_query, array($p_bug_id, $p_user_id));
    $t_row = db_fetch_array($t_result);

    if ($t_row && $t_row['first_assigned_date']) {
        return $t_row['first_assigned_date'];
    }

    // 如果没有找到指派历史，返回问题的创建日期
    $t_bug_table = db_get_table('bug');
    $t_query = "SELECT date_submitted
                FROM $t_bug_table
                WHERE id = " . db_param();
    $t_result = db_query($t_query, array($p_bug_id));
    $t_row = db_fetch_array($t_result);

    return $t_row ? $t_row['date_submitted'] : time();
}
