<?php
/**
 * Gantt Plugin
 *
 * @package Gantt
 * @author Plugin Author
 * @copyright Copyright (c) 2026
 * @license GNU General Public License v2 or later
 */

class GanttPlugin extends MantisPlugin {

    function register() {
        $this->name = plugin_lang_get( 'title' );
        $this->description = plugin_lang_get( 'description' );
        $this->page = 'config';

        $this->version = '0.1';
        $this->requires = array(
            'MantisCore' => '2.0.0',
        );

        $this->author = 'Plugin Author';
        $this->contact = 'author@example.com';
        $this->url = 'https://example.com';
    }

    function init() {
        plugin_event_hook( 'EVENT_MENU_MAIN', 'menu_main' );
    }

    function menu_main( $p_event ) {
        return array(
            array(
                'title' => plugin_lang_get( 'gantt' ),
                'url' => plugin_page( 'gantt_page' ),
                'access_level' => VIEWER,
                'icon' => 'fa-bar-chart',
            ),
        );
    }
}
