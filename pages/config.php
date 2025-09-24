<?php
/**
 * Feishu Notifier Configuration Page
 */
auth_reauthenticate();
access_ensure_global_level(config_get('manage_plugin_threshold'));

layout_page_header(plugin_lang_get('title'));
layout_page_begin('manage_overview_page.php');

print_manage_menu('manage_plugin_page.php');

?>

<div class="col-md-12 col-xs-12">
    <div class="space-10"></div>
    <div class="form-container">
        <form method="post" action="<?php echo plugin_page('config_update') ?>">
            <?php echo form_security_field('plugin_FeishuNotifier_config_update') ?>
            
            <div class="widget-box widget-color-blue2">
                <div class="widget-header widget-header-small">
                    <h4 class="widget-title lighter">
                        <?php echo plugin_lang_get('title') ?>
                    </h4>
                </div>
                
                <div class="widget-body">
                    <div class="widget-main no-padding">
                        <div class="table-responsive">
                            <table class="table table-bordered table-condensed">
                                <tr>
                                    <td class="category" width="30%">
                                        <?php echo plugin_lang_get('webhook_url') ?>
                                    </td>
                                    <td width="70%">
                                        <input type="text" name="webhook_url" 
                                               value="<?php echo string_attribute(plugin_config_get('webhook_url')) ?>" 
                                               size="80" />
                                        <br />
                                        <span class="small">
                                            <?php echo plugin_lang_get('webhook_url_info') ?>
                                        </span>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <td class="category">
                                        <?php echo plugin_lang_get('enabled') ?>
                                    </td>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="enabled" 
                                                   <?php check_checked(plugin_config_get('enabled'), ON) ?> />
                                            <?php echo plugin_lang_get('enabled_label') ?>
                                        </label>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <td class="category">
                                        <?php echo plugin_lang_get('notifications') ?>
                                    </td>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="notify_on_new" 
                                                   <?php check_checked(plugin_config_get('notify_on_new'), ON) ?> />
                                            <?php echo plugin_lang_get('notify_on_new') ?>
                                        </label><br />
                                        
                                        <label>
                                            <input type="checkbox" name="notify_on_update" 
                                                   <?php check_checked(plugin_config_get('notify_on_update'), ON) ?> />
                                            <?php echo plugin_lang_get('notify_on_update') ?>
                                        </label><br />
                                        
                                        <label>
                                            <input type="checkbox" name="notify_on_close" 
                                                   <?php check_checked(plugin_config_get('notify_on_close'), ON) ?> />
                                            <?php echo plugin_lang_get('notify_on_close') ?>
                                        </label><br />
                                        
                                        <label>
                                            <input type="checkbox" name="notify_on_reopen" 
                                                   <?php check_checked(plugin_config_get('notify_on_reopen'), ON) ?> />
                                            <?php echo plugin_lang_get('notify_on_reopen') ?>
                                        </label>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <td class="category">
                                        <?php echo plugin_lang_get('description_settings') ?>
                                    </td>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="include_description" 
                                                   <?php check_checked(plugin_config_get('include_description'), ON) ?> />
                                            <?php echo plugin_lang_get('include_description') ?>
                                        </label><br />
                                        
                                        <label>
                                            <?php echo plugin_lang_get('max_description_length') ?>:
                                            <input type="number" name="max_description_length" 
                                                   value="<?php echo plugin_config_get('max_description_length') ?>" 
                                                   min="1" max="1000" />
                                        </label>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="widget-toolbox padding-8 clearfix">
                        <input type="submit" class="btn btn-primary btn-white btn-round" 
                               value="<?php echo lang_get('update') ?>" />
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
layout_page_end();
?>