<?php
/**
 * Gantt Plugin - Example Page
 *
 * @package Gantt
 * @author Plugin Author
 * @copyright Copyright (c) 2026
 * @license GNU General Public License v2 or later
 */

auth_ensure_user_authenticated();

layout_page_header( plugin_lang_get( 'gantt' ) );
layout_page_begin();
?>

<div class="col-md-12 col-xs-12">
    <div class="space-10"></div>
    
    <div class="widget-box widget-color-blue2">
        <div class="widget-header widget-header-small">
            <h4 class="widget-title lighter">
                <i class="ace-icon fa fa-bar-chart"></i>
                <?php echo plugin_lang_get( 'gantt' ); ?>
            </h4>
        </div>

        <div class="widget-body">
            <div class="widget-main no-padding">
                <div class="table-responsive">
                    <table class="table table-bordered table-condensed table-hover">
                        <thead class="thin-border-bottom">
                            <tr>
                                <th class="center">
                                    <label class="pos-rel">
                                        <input type="checkbox" class="ace" />
                                        <span class="lbl"></span>
                                    </label>
                                </th>
                                <th>
                                    <?php echo lang_get( 'id' ); ?>
                                </th>
                                <th>
                                    <?php echo lang_get( 'summary' ); ?>
                                </th>
                                <th>
                                    <?php echo lang_get( 'status' ); ?>
                                </th>
                                <th>
                                    <?php echo lang_get( 'priority' ); ?>
                                </th>
                                <th>
                                    <?php echo lang_get( 'assignee' ); ?>
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td class="center">
                                    <label class="pos-rel">
                                        <input type="checkbox" class="ace" />
                                        <span class="lbl"></span>
                                    </label>
                                </td>
                                <td class="center">#1</td>
                                <td>Example Task 1</td>
                                <td>Open</td>
                                <td>High</td>
                                <td>Administrator</td>
                            </tr>
                            <tr>
                                <td class="center">
                                    <label class="pos-rel">
                                        <input type="checkbox" class="ace" />
                                        <span class="lbl"></span>
                                    </label>
                                </td>
                                <td class="center">#2</td>
                                <td>Example Task 2</td>
                                <td>In Progress</td>
                                <td>Medium</td>
                                <td>User</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
layout_page_end();
