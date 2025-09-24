<?php
/**
 * Feishu Notifier Configuration Update Page
 */
auth_reauthenticate();
access_ensure_global_level(config_get('manage_plugin_threshold'));

form_security_validate('plugin_FeishuNotifier_config_update');

// Get form values
$f_webhook_url = gpc_get_string('webhook_url', '');
$f_enabled = gpc_get_bool('enabled', false);
$f_notify_on_new = gpc_get_bool('notify_on_new', false);
$f_notify_on_update = gpc_get_bool('notify_on_update', false);
$f_notify_on_close = gpc_get_bool('notify_on_close', false);
$f_notify_on_reopen = gpc_get_bool('notify_on_reopen', false);
$f_include_description = gpc_get_bool('include_description', false);
$f_max_description_length = gpc_get_int('max_description_length', 200);

form_security_purge('plugin_FeishuNotifier_config_update');

// Update plugin configuration
plugin_config_set('webhook_url', $f_webhook_url);
plugin_config_set('enabled', $f_enabled ? ON : OFF);
plugin_config_set('notify_on_new', $f_notify_on_new ? ON : OFF);
plugin_config_set('notify_on_update', $f_notify_on_update ? ON : OFF);
plugin_config_set('notify_on_close', $f_notify_on_close ? ON : OFF);
plugin_config_set('notify_on_reopen', $f_notify_on_reopen ? ON : OFF);
plugin_config_set('include_description', $f_include_description ? ON : OFF);
plugin_config_set('max_description_length', $f_max_description_length);

// Redirect back to config page
print_header_redirect('plugin.php?page=FeishuNotifier/config');