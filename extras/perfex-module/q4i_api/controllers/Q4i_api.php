<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Q4i_api extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        if (! is_admin()) {
            access_denied('Q4I API Bridge');
        }
    }

    public function index()
    {
        if (get_option('q4i_api_token') === '' || get_option('q4i_api_token') === false) {
            update_option('q4i_api_token', bin2hex(random_bytes(24)));
        }
        if (get_option('q4i_api_enabled') === '' || get_option('q4i_api_enabled') === false) {
            update_option('q4i_api_enabled', '1');
        }

        $currencies = $this->db->get(db_prefix().'currencies')->result_array();
        $payment_modes = $this->db->get(db_prefix().'payment_modes')->result_array();

        $data['title'] = 'Q4I API Bridge Settings';
        $data['api_token'] = (string) get_option('q4i_api_token');
        $data['api_enabled'] = get_option('q4i_api_enabled') === '1';
        $data['currencies'] = $currencies;
        $data['payment_modes'] = $payment_modes;

        $this->load->view('q4i_api/settings', $data);
    }

    public function toggle()
    {
        if ($this->input->method(true) !== 'POST') {
            redirect(admin_url('q4i_api'));
        }

        $current = get_option('q4i_api_enabled') === '1';
        update_option('q4i_api_enabled', $current ? '0' : '1');
        set_alert('success', 'Q4I API Bridge '.($current ? 'disabled' : 'enabled').'.');
        redirect(admin_url('q4i_api'));
    }

    public function regenerate()
    {
        if ($this->input->method(true) !== 'POST') {
            redirect(admin_url('q4i_api'));
        }

        update_option('q4i_api_token', bin2hex(random_bytes(24)));
        set_alert('success', 'Generated a new Q4I API token. Update PERFEX_API_TOKEN in your USSD portal .env.');
        redirect(admin_url('q4i_api'));
    }
}
