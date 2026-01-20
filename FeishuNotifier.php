<?php
/**
 * Feishu Notifier Plugin for MantisBT
 * Sends bug updates to Feishu group via webhook
 */
class FeishuNotifierPlugin extends MantisPlugin {
    
    public function register() {
        $this->name = plugin_lang_get('title');
        $this->description = plugin_lang_get('description');
        $this->page = 'config';
        $this->version = '1.0';
        $this->requires = array(
            'MantisCore' => '2.25.0',
        );
        $this->author = 'hejiang';
        $this->contact = 'hejiang@tju.edu.cn';
        $this->url = '';
    }

    public function config() {
        return array(
            'enabled' => ON,
            'webhook_url' => 'https://open.feishu.cn/open-apis/bot/v2/hook/{bot-id}',
            'notify_on_new' => ON,
            'notify_on_update' => ON,
            'notify_on_close' => ON,
            'notify_on_reopen' => ON,
            'include_description' => OFF,
            'max_description_length' => 200,
        );
    }

    public function hooks() {
        return array(
            'EVENT_REPORT_BUG' => 'bug_reported',
            'EVENT_UPDATE_BUG_DATA' => 'bug_updated',
            'EVENT_BUG_ACTION' => 'bug_action',
            'EVENT_MANAGE_PROJECT_UPDATE_FORM' => 'project_config_form',
            'EVENT_MANAGE_PROJECT_UPDATE' => 'project_config_update',
        );
    }

    /**
     * Handle new bug reports
     */
    public function bug_reported($p_event, $p_bug_data) {
        error_log("Feishu Notifier: bug_reported called, event: {$p_event}");
        error_log("Feishu Notifier: bug_data ID: " . (isset($p_bug_data->id) ? $p_bug_data->id : 'null'));
        
        if (!$this->is_enabled()) {
            error_log("Feishu Notifier: Plugin disabled, returning bug data");
            return $p_bug_data;
        }
        
        if (!$this->get_config('notify_on_new')) {
            error_log("Feishu Notifier: notify_on_new disabled, returning bug data");
            return $p_bug_data;
        }
        
        error_log("Feishu Notifier: Sending NEW notification");
        $this->send_notification($p_bug_data, 'NEW');
        error_log("Feishu Notifier: bug_reported completed, returning bug data");
        return $p_bug_data;
    }

    /**
     * Handle bug updates
     */
    public function bug_updated($p_event, $p_bug_data, $p_bug_data_prev) {
        error_log("Feishu Notifier: bug_updated called, event: {$p_event}");
        error_log("Feishu Notifier: bug_data ID: " . (isset($p_bug_data->id) ? $p_bug_data->id : 'null'));
        error_log("Feishu Notifier: bug_data_prev ID: " . (isset($p_bug_data_prev->id) ? $p_bug_data_prev->id : 'null'));
        
        if (!$this->is_enabled()) {
            error_log("Feishu Notifier: Plugin disabled, returning bug data");
            return $p_bug_data;
        }
        
        if (!$this->get_config('notify_on_update')) {
            error_log("Feishu Notifier: notify_on_update disabled, returning bug data");
            return $p_bug_data;
        }
        
        error_log("Feishu Notifier: Status check - prev: {$p_bug_data_prev->status}, current: {$p_bug_data->status}");
        
        // Check if status changed to closed
        if ($p_bug_data_prev->status != $p_bug_data->status) {
            if ($p_bug_data->status == CLOSED) {
                error_log("Feishu Notifier: Bug closed, checking notify_on_close");
                if ($this->get_config('notify_on_close')) {
                    error_log("Feishu Notifier: Sending CLOSED notification");
                    $this->send_notification($p_bug_data, 'CLOSED');
                }
                error_log("Feishu Notifier: Returning bug data after close check");
                return $p_bug_data;
            }
            
            // Check if reopened
            if ($p_bug_data_prev->status == CLOSED && $p_bug_data->status != CLOSED) {
                error_log("Feishu Notifier: Bug reopened, checking notify_on_reopen");
                if ($this->get_config('notify_on_reopen')) {
                    error_log("Feishu Notifier: Sending REOPENED notification");
                    $this->send_notification($p_bug_data, 'REOPENED');
                }
                error_log("Feishu Notifier: Returning bug data after reopen check");
                return $p_bug_data;
            }
        }
        
        error_log("Feishu Notifier: Sending UPDATED notification");
        $this->send_notification($p_bug_data, 'UPDATED');
        error_log("Feishu Notifier: bug_updated completed, returning bug data");
        return $p_bug_data;
    }

    /**
     * Handle bug actions (like resolve, close, etc.)
     */
    public function bug_action($p_event, $p_action, $p_bug_ids) {
        $bug_ids = is_array($p_bug_ids) ? $p_bug_ids : [$p_bug_ids];
        error_log("Feishu Notifier: bug_action called, event: {$p_event}, action: {$p_action}");
        error_log("Feishu Notifier: bug_ids count: " . count($bug_ids));
        
        if (!$this->is_enabled()) {
            error_log("Feishu Notifier: Plugin disabled, returning");
            return;
        }
        
        if (count($bug_ids) == 0) {
            error_log("Feishu Notifier: No bug IDs provided, returning");
            return;
        }
        
        foreach ($bug_ids as $bug_id) {
            error_log("Feishu Notifier: Processing bug ID: {$bug_id}");
            $bug_data = bug_get($bug_id);
            if ($bug_data) {
                error_log("Feishu Notifier: Bug data retrieved successfully, sending notification");
                $this->send_notification($bug_data, strtoupper($p_action));
            } else {
                error_log("Feishu Notifier: Failed to retrieve bug data for ID: {$bug_id}");
            }
        }
        error_log("Feishu Notifier: bug_action completed");
    }

    /**
     * Send notification to Feishu
     */
    private function send_notification($p_bug_data, $p_action) {
        error_log("Feishu Notifier: send_notification called, action: {$p_action}");
        error_log("Feishu Notifier: bug ID: " . (isset($p_bug_data->id) ? $p_bug_data->id : 'null'));
        
        // Get project ID from bug data
        $project_id = isset($p_bug_data->project_id) ? $p_bug_data->project_id : null;
        
        // Try to get project-specific webhook URL first
        $webhook_url = '';
        if ($project_id) {
            $webhook_url = plugin_config_get('webhook_url', '', false, null, $project_id);
            error_log("Feishu Notifier: Project-specific webhook_url (project {$project_id}): " . (empty($webhook_url) ? 'empty' : 'set'));
        }
        
        // Fall back to global webhook URL if project-specific one is not set
        if (empty($webhook_url)) {
            $webhook_url = $this->get_config('webhook_url');
            error_log("Feishu Notifier: Using global webhook_url: " . (empty($webhook_url) ? 'empty' : 'set'));
        }
        
        if (empty($webhook_url)) {
            error_log("Feishu Notifier: Webhook URL empty, not sending notification");
            return;
        }
        
        error_log("Feishu Notifier: Building message");
        $message = $this->build_message($p_bug_data, $p_action);
        error_log("Feishu Notifier: Message built, sending to Feishu");
        $this->send_to_feishu($webhook_url, $message);
        error_log("Feishu Notifier: send_notification completed");
    }

    /**
     * Build notification message
     */
    private function build_message($p_bug_data, $p_action) {
        error_log("Feishu Notifier: build_message called, action: {$p_action}");
        
        $project_name = project_get_name($p_bug_data->project_id);
        $bug_id = $p_bug_data->id;
        $summary = $p_bug_data->summary;
        $status = get_enum_element('status', $p_bug_data->status);
        $severity = get_enum_element('severity', $p_bug_data->severity);
        $priority = get_enum_element('priority', $p_bug_data->priority);
        $reporter = user_get_name($p_bug_data->reporter_id);
        $handler = $p_bug_data->handler_id ? user_get_name($p_bug_data->handler_id) : 'Unassigned';
        
        $mantis_url = config_get('path') . 'view.php?id=' . $bug_id;
        
        error_log("Feishu Notifier: Project: {$project_name}, Bug ID: {$bug_id}, Summary: {$summary}");
        error_log("Feishu Notifier: Status: {$status}, Severity: {$severity}, Priority: {$priority}");
        error_log("Feishu Notifier: Reporter: {$reporter}, Handler: {$handler}");
        
        // Action text mapping
        $action_texts = array(
            'NEW' => '🐛 新建',
            'UPDATED' => '📝 更新',
            'CLOSED' => '✅ 关闭',
            'REOPENED' => '🔄 重新打开',
            'RESOLVE' => '🔧 解决',
            'CLOSE' => '✅ 关闭'
        );
        
        $action_text = isset($action_texts[$p_action]) ? $action_texts[$p_action] : $p_action;
        error_log("Feishu Notifier: Action text: {$action_text}");
        
        $content = array(
            "msg_type" => "post",
            "content" => array(
                "post" => array(
                    "zh_cn" => array(
                        "title" => "[{$project_name}] {$action_text} - Bug #{$bug_id}",
                        "content" => array(
                            array(
                                array(
                                    "tag" => "text",
                                    "text" => "摘要: {$summary}\n"
                                )
                            ),
                            array(
                                array(
                                    "tag" => "text",
                                    "text" => "状态: {$status} | 严重程度: {$severity} | 优先级: {$priority}\n"
                                )
                            ),
                            array(
                                array(
                                    "tag" => "text",
                                    "text" => "报告人: {$reporter} | 处理人: {$handler}\n"
                                )
                            ),
                            array(
                                array(
                                    "tag" => "a",
                                    "text" => "查看详情",
                                    "href" => $mantis_url
                                )
                            )
                        )
                    )
                )
            )
        );
        
        // Add description if enabled
        if ($this->get_config('include_description') && !empty($p_bug_data->description)) {
            $description = $p_bug_data->description;
            $max_length = $this->get_config('max_description_length');
            if (strlen($description) > $max_length) {
                $description = substr($description, 0, $max_length) . '...';
            }
            
            error_log("Feishu Notifier: Adding description, length: " . strlen($description));
            
            array_unshift($content['content']['post']['zh_cn']['content'], array(
                array(
                    "tag" => "text",
                    "text" => "描述: {$description}\n"
                )
            ));
        } else {
            error_log("Feishu Notifier: Description not included");
        }
        
        error_log("Feishu Notifier: Message built successfully");
        return $content;
    }

    /**
     * Send message to Feishu webhook
     */
    private function send_to_feishu($webhook_url, $message) {
        error_log("Feishu Notifier: send_to_feishu called");
        
        $json_message = json_encode($message, JSON_UNESCAPED_UNICODE);
        error_log("Feishu Notifier: JSON message: " . substr($json_message, 0, 200) . "...");
        
        $ch = curl_init($webhook_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_message);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        error_log("Feishu Notifier: Executing cURL request");
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        error_log("Feishu Notifier: HTTP code: {$http_code}");
        error_log("Feishu Notifier: cURL error: " . ($curl_error ?: 'none'));
        error_log("Feishu Notifier: Response: " . substr($response, 0, 200));
        
        if ($http_code != 200) {
            error_log("Feishu Notifier: Failed to send notification. HTTP code: {$http_code}, Response: {$response}");
        } else {
            error_log("Feishu Notifier: Notification sent successfully");
        }
    }

    /**
     * Check if plugin is enabled
     */
    private function is_enabled() {
        error_log("Feishu Notifier: is_enabled called");
        $enabled = plugin_config_get('enabled') == ON;
        error_log("Feishu Notifier: Plugin enabled status: " . ($enabled ? 'true' : 'false'));
        return $enabled;
    }

    /**
     * Get plugin configuration
     */
    private function get_config($p_key, $p_default = null) {
        error_log("Feishu Notifier: get_config called for key: {$p_key}");
        $value = plugin_config_get($p_key, $p_default);
        error_log("Feishu Notifier: get_config returning: " . (is_array($value) ? 'array' : (is_bool($value) ? ($value ? 'true' : 'false') : $value)));
        return $value;
    }
    
    /**
     * Display project-specific configuration form
     */
    public function project_config_form($p_event, $p_project_id) {
        $t_webhook_url = plugin_config_get('webhook_url', '', false, null, $p_project_id);
        
        echo '<div class="form-group">';
        echo '<label class="col-sm-3 control-label">', plugin_lang_get('webhook_url'), '</label>';
        echo '<div class="col-sm-9">';
        echo '<input type="text" name="plugin_FeishuNotifier_webhook_url" class="form-control" value="', string_attribute($t_webhook_url), '" size="60" />';
        echo '<span class="help-block">', plugin_lang_get('webhook_url_info'), '</span>';
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Handle project-specific configuration update
     */
    public function project_config_update($p_event, $p_project_id) {
        $t_webhook_url = gpc_get_string('plugin_FeishuNotifier_webhook_url', '');
        plugin_config_set('webhook_url', $t_webhook_url, NO_USER, $p_project_id);
    }
}