<?php

class KanbanPlugin extends MantisPlugin {

    function register() {
        $this->name = plugin_lang_get('title');
        $this->description = plugin_lang_get('description');
        $this->page = 'kanban_page';
        $this->version = '1.0.0';
        $this->requires = array(
            'MantisCore' => '2.0.0',
        );
        $this->author = 'Kanban Plugin Developer';
        $this->contact = '';
        $this->url = '';
    }

    function init() {
        plugin_event_hook('EVENT_MENU_MAIN', 'onMainMenu');
        plugin_event_hook('EVENT_LAYOUT_RESOURCES', 'onLayoutResources');
    }

    function onMainMenu() {
        // 仅系统管理员和项目经理可见
        if (!$this->user_has_access()) {
            return array();
        }

        return array(
            array(
                'title' => plugin_lang_get('menu_title'),
                'access_level' => VIEWER,
                'url' => plugin_page('kanban_page'),
                'icon' => 'fa-columns'
            ),
        );
    }

    /**
     * 检查当前用户是否有权限访问工作看板
     * @return bool 是否有权限
     */
    function user_has_access() {
        $t_current_user_id = auth_get_current_user_id();
        $t_project_id = helper_get_current_project();

        // 系统管理员总是有权限
        if (user_is_administrator($t_current_user_id)) {
            return true;
        }

        // 检查用户是否是当前项目的经理
        if ($t_project_id != ALL_PROJECTS && $t_project_id > 0) {
            $t_user_access_level = user_get_access_level($t_current_user_id, $t_project_id);
            if ($t_user_access_level >= MANAGER) {
                return true;
            }
        }

        return false;
    }

    function onLayoutResources() {
        return '<link rel="stylesheet" type="text/css" href="' . plugin_file('kanban.css') . '" />';
    }
}
