<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Q4I Comms API Bridge
Description: Lightweight token-authenticated REST API bridge for Q4I USSD & Billing Portal (customers & invoices).
Version: 1.0.0
Requires at least: 2.3.*
Author: Q4 International Ltd
*/

define('Q4I_API_MODULE_NAME', 'q4i_api');

register_activation_hook(Q4I_API_MODULE_NAME, 'q4i_api_activation_hook');

function q4i_api_activation_hook()
{
    if (get_option('q4i_api_token') === '' || get_option('q4i_api_token') === false) {
        add_option('q4i_api_token', bin2hex(random_bytes(24)));
    }
    if (get_option('q4i_api_enabled') === '' || get_option('q4i_api_enabled') === false) {
        add_option('q4i_api_enabled', '1');
    }
}

hooks()->add_action('admin_init', 'q4i_api_init_menu_items');

function q4i_api_init_menu_items()
{
    if (! is_admin()) {
        return;
    }

    $CI = &get_instance();

    $CI->app_menu->add_sidebar_menu_item('q4i-api-bridge', [
        'name' => 'Q4I API',
        'href' => admin_url('q4i_api'),
        'position' => 65,
        'icon' => 'fa fa-plug',
    ]);

    $CI->app_menu->add_setup_menu_item('q4i-api-settings', [
        'name' => 'Q4I API Bridge',
        'href' => admin_url('q4i_api'),
        'position' => 65,
    ]);
}

hooks()->add_filter('app_csrf_exclude_uris', 'q4i_api_exclude_csrf');

function q4i_api_exclude_csrf($urls)
{
    $urls[] = 'q4i_api/api/.*';
    $urls[] = 'api/.*';

    return $urls;
}
