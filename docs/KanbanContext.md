# MantisBT 工作看板插件 - 项目上下文

## 项目概述
- **插件名称**: Kanban (工作看板)
- **MantisBT 版本**: 2.27.1
- **开发目录**: `/home/user/projects/mantis-plugin-kanban/`

## 文件结构

```
mantis-plugin-kanban/
├── Kanban.php              # 主插件文件，注册插件、菜单、权限控制
├── files/
│   └── kanban.css          # 插件样式文件
├── lang/
│   ├── strings_english.txt # 英文语言文件
│   └── strings_chinese_simplified.txt # 简体中文语言文件
├── pages/
│   └── kanban_page.php     # 主页面逻辑和显示
└── .trae/
    └── project_context.md  # 本文档
```

## 核心功能

### 1. 角色分类 Tab 页签
- **开发人员** (DEVELOPER): 显示开发角色用户
- **报告人员** (REPORTER): 显示报告角色用户
- **管理人员** (MANAGER/ADMIN): 显示经理和管理员角色用户

### 2. 问题状态分类
- **正在处理**: 已确认 (CONFIRMED)
- **待处理**: 新建 (NEW_) / 已分配 (ASSIGNED) / 反馈 (FEEDBACK) / 认可 (ACKNOWLEDGED)

### 3. 权限控制
- 仅系统管理员和项目经理可见菜单
- 通过 `user_has_access()` 方法控制

## 关键代码片段

### 获取用户列表 (按角色)
```php
function kanban_get_users_by_role($p_role, $p_project_id = ALL_PROJECTS)
```
- DEVELOPER: 使用 `handler_id` 关联查询
- REPORTER: 使用 `reporter_id` 关联查询
- MANAGER: 使用 `project_user_list_table` 查询

### 获取问题列表
```php
function kanban_get_user_issues_by_statuses($p_user_id, $p_statuses, $p_project_id = ALL_PROJECTS)
```

### 获取状态变更日期
```php
function kanban_get_first_status_change_date($p_bug_id, $p_user_id, $p_status)
```
- 查询 `bug_history` 表
- `field_name = 'status'`
- 返回首次变更为指定状态的日期
- 无历史记录时返回 `date_submitted`

## 数据库表

- `bug` - 问题主表
- `bug_history` - 问题历史记录表
- `project_user_list_table` - 项目用户关联表

## 已知问题与解决方案

### 1. user_get_all_rows() 函数不存在
**问题**: Call to undefined function user_get_all_rows()
**解决**: 使用直接数据库查询替代

### 2. 日期显示为 1970-01-01
**问题**: 使用 strtotime() 转换 Unix 时间戳
**解决**: 直接使用数据库返回的 Unix 时间戳

### 3. ROLE_DEVELOPER 常量不存在
**问题**: Undefined constant "ROLE_DEVELOPER"
**解决**: 使用 "DEVELOPER" 字符串代替

## 样式说明

- 使用 MantisBT 自带的 ACE Admin 主题样式
- 不覆盖 `.nav-tabs` 相关 CSS
- 仅保留表格和问题列表的自定义样式

## i18n 变量

- `$s_plugin_Kanban_menu_title` - 菜单标题
- `$s_plugin_Kanban_page_title` - 页面标题
- `$s_plugin_Kanban_no_issues` - 无问题提示
- `$s_plugin_Kanban_tab_developers` - 开发人员标签
- `$s_plugin_Kanban_tab_reporters` - 报告人员标签
- `$s_plugin_Kanban_tab_managers` - 管理人员标签

## 开发注意事项

1. **状态常量**: 使用 MantisBT 内置状态常量 (NEW_, ASSIGNED, CONFIRMED, FEEDBACK, ACKNOWLEDGED)
2. **用户显示格式**: `姓名 (用户名)`
3. **问题显示格式**: `[目标版本] 问题摘要 (状态变更日期)`
4. **数据库查询**: 使用 `db_get_table()` 获取表名，使用 `db_param()` 防止 SQL 注入

## 参考资料

- MantisBT 开发者指南: https://mantisbt.org/docs/master/en-US/Developers_Guide/
- MantisBT GitHub: https://github.com/mantisbt/mantisbt
