<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Api extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->authenticateRequest();
    }

    public function health()
    {
        $currencies = $this->db->select('id, name, symbol, isdefault')->get(db_prefix().'currencies')->result_array();
        $paymentModes = $this->db->select('id, name, active')->get(db_prefix().'payment_modes')->result_array();

        $this->jsonResponse([
            'ok' => true,
            'module' => 'q4i_api',
            'version' => '1.0.0',
            'currencies' => $currencies,
            'payment_modes' => $paymentModes,
        ]);
    }

    public function customers()
    {
        $method = strtoupper($this->input->method(true));
        if ($method === 'POST') {
            return $this->createCustomer();
        }

        $rows = $this->db
            ->select('userid, company, vat, phonenumber, country, city, default_currency, active')
            ->from(db_prefix().'clients')
            ->order_by('userid', 'DESC')
            ->limit(200)
            ->get()
            ->result_array();

        $this->jsonResponse($rows);
    }

    public function customers_search($query = '')
    {
        $term = trim(urldecode((string) $query));
        if ($term === '') {
            $this->jsonResponse([]);
            return;
        }

        $this->db->select('userid, company, vat, phonenumber, country, city, default_currency, active')
            ->from(db_prefix().'clients')
            ->group_start()
                ->like('company', $term)
                ->or_like('phonenumber', $term)
                ->or_like('vat', $term)
            ->group_end()
            ->limit(25);

        $this->jsonResponse($this->db->get()->result_array());
    }

    public function contacts_search($query = '')
    {
        $term = trim(urldecode((string) $query));
        if ($term === '') {
            $this->jsonResponse([]);
            return;
        }

        $this->db->select('id, userid, email, firstname, lastname, is_primary')
            ->from(db_prefix().'contacts')
            ->group_start()
                ->like('email', $term)
                ->or_like('firstname', $term)
                ->or_like('lastname', $term)
            ->group_end()
            ->limit(25);

        $this->jsonResponse($this->db->get()->result_array());
    }

    public function invoices()
    {
        $method = strtoupper($this->input->method(true));
        if ($method === 'POST') {
            return $this->createInvoice();
        }

        $clientId = (int) $this->input->get('clientid');

        $this->db->select('i.id, i.prefix, i.number, i.clientid, i.date, i.duedate, i.subtotal, i.total, i.currency, i.status, i.hash, c.company')
            ->from(db_prefix().'invoices i')
            ->join(db_prefix().'clients c', 'c.userid = i.clientid', 'left')
            ->order_by('i.id', 'DESC')
            ->limit(100);

        if ($clientId > 0) {
            $this->db->where('i.clientid', $clientId);
        }

        $rows = $this->db->get()->result_array();
        foreach ($rows as &$row) {
            if (function_exists('format_invoice_number')) {
                $row['formatted_number'] = format_invoice_number($row['id']);
            } else {
                $row['formatted_number'] = ($row['prefix'] ?? 'INV-').$row['number'];
            }
        }

        $this->jsonResponse($rows);
    }

    protected function createCustomer()
    {
        $input = $this->readInputPayload();
        $company = trim((string) ($input['company'] ?? ''));

        if ($company === '') {
            $this->jsonResponse(['status' => false, 'message' => 'Customer company name is required.'], 422);
            return;
        }

        $this->load->model('clients_model');

        $currencyId = (int) ($input['default_currency'] ?? 0);
        if ($currencyId <= 0) {
            $currencyId = $this->resolveDefaultCurrencyId();
        }

        $clientData = [
            'company' => $company,
            'phonenumber' => trim((string) ($input['phonenumber'] ?? '')),
            'country' => (int) ($input['country'] ?? 160),
            'default_currency' => $currencyId,
            'active' => isset($input['active']) ? (int) $input['active'] : 1,
        ];

        $userid = $this->clients_model->add($clientData);
        if (! $userid) {
            $this->jsonResponse(['status' => false, 'message' => 'Failed to create customer in Perfex CRM.'], 500);
            return;
        }

        $this->jsonResponse([
            'status' => true,
            'userid' => (int) $userid,
            'id' => (int) $userid,
            'message' => 'Customer created successfully.',
        ], 201);
    }

    protected function createInvoice()
    {
        $input = $this->readInputPayload();
        $clientId = (int) ($input['clientid'] ?? 0);

        if ($clientId <= 0) {
            $this->jsonResponse(['status' => false, 'message' => 'Valid clientid is required.'], 422);
            return;
        }

        $client = $this->db->where('userid', $clientId)->get(db_prefix().'clients')->row_array();
        if (! $client) {
            $this->jsonResponse(['status' => false, 'message' => "Perfex customer #{$clientId} not found."], 404);
            return;
        }

        $rawItems = $input['newitems'] ?? [];
        if (! is_array($rawItems) || empty($rawItems)) {
            $this->jsonResponse(['status' => false, 'message' => 'At least one invoice item (newitems) is required.'], 422);
            return;
        }

        $items = [];
        $computedTotal = 0.0;
        $idx = 1;
        foreach ($rawItems as $item) {
            if (! is_array($item)) {
                continue;
            }
            $qty = isset($item['qty']) ? (float) $item['qty'] : 1.0;
            $rate = isset($item['rate']) ? (float) $item['rate'] : 0.0;
            $computedTotal += ($qty * $rate);

            $items[$idx] = [
                'order' => $idx,
                'description' => (string) ($item['description'] ?? 'USSD & Communications Service'),
                'long_description' => (string) ($item['long_description'] ?? ''),
                'qty' => $qty,
                'rate' => $rate,
                'unit' => (string) ($item['unit'] ?? ''),
            ];
            $idx++;
        }

        if (empty($items)) {
            $this->jsonResponse(['status' => false, 'message' => 'No valid line items supplied.'], 422);
            return;
        }

        $this->load->model('invoices_model');

        $currencyId = (int) ($input['currency'] ?? 0);
        if (! $this->currencyExists($currencyId)) {
            $currencyId = ! empty($client['default_currency'])
                ? (int) $client['default_currency']
                : $this->resolveDefaultCurrencyId();
        }

        $number = (int) ($input['number'] ?? 0);
        if ($number <= 0) {
            $number = $this->resolveNextInvoiceNumber();
        }

        $paymentModes = $input['allowed_payment_modes'] ?? [];
        if (! is_array($paymentModes) || empty($paymentModes)) {
            $paymentModes = $this->resolveActivePaymentModes();
        }

        $subtotal = isset($input['subtotal']) && is_numeric($input['subtotal'])
            ? (float) $input['subtotal']
            : round($computedTotal, 2);
        $total = isset($input['total']) && is_numeric($input['total'])
            ? (float) $input['total']
            : round($computedTotal, 2);

        $invoiceData = [
            'clientid' => $clientId,
            'number' => $number,
            'date' => (string) ($input['date'] ?? date('Y-m-d')),
            'duedate' => (string) ($input['duedate'] ?? date('Y-m-d', strtotime('+7 days'))),
            'currency' => $currencyId,
            'subtotal' => $subtotal,
            'total' => $total,
            'adjustment' => 0,
            'discount_type' => '',
            'allowed_payment_modes' => $paymentModes,
            'billing_street' => (string) ($client['billing_street'] ?: $client['address'] ?: ($input['billing_street'] ?? 'Nigeria')),
            'billing_city' => (string) ($client['billing_city'] ?: $client['city'] ?: ''),
            'billing_state' => (string) ($client['billing_state'] ?: $client['state'] ?: ''),
            'billing_zip' => (string) ($client['billing_zip'] ?: $client['zip'] ?: ''),
            'billing_country' => (int) ($client['billing_country'] ?: $client['country'] ?: 160),
            'clientnote' => (string) ($input['clientnote'] ?? ''),
            'newitems' => $items,
        ];

        $invoiceId = $this->invoices_model->add($invoiceData);
        if (! $invoiceId) {
            $this->jsonResponse(['status' => false, 'message' => 'Perfex invoices_model->add() failed to create invoice.'], 500);
            return;
        }

        $this->jsonResponse([
            'status' => true,
            'id' => (int) $invoiceId,
            'invoice_id' => (int) $invoiceId,
            'message' => 'Invoice created successfully.',
        ], 201);
    }

    protected function authenticateRequest()
    {
        if (get_option('q4i_api_enabled') !== '1') {
            $this->jsonResponse(['status' => false, 'message' => 'Q4I API Bridge is currently disabled in Perfex settings.'], 403);
            exit;
        }

        $expected = (string) get_option('q4i_api_token');
        $provided = (string) ($this->input->get_request_header('Authtoken', true)
            ?: $this->input->get_request_header('authtoken', true)
            ?: ($_SERVER['HTTP_AUTHTOKEN'] ?? '')
            ?: $this->input->get('authtoken'));

        if ($expected === '' || $provided === '' || ! hash_equals($expected, trim($provided))) {
            $this->jsonResponse(['status' => false, 'message' => 'Unauthorized: invalid or missing Authtoken.'], 401);
            exit;
        }
    }

    protected function readInputPayload()
    {
        $post = $this->input->post(null, true);
        if (is_array($post) && ! empty($post)) {
            return $post;
        }

        $raw = trim((string) file_get_contents('php://input'));
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    protected function resolveDefaultCurrencyId()
    {
        $base = $this->db->where('isdefault', 1)->get(db_prefix().'currencies')->row_array();
        if ($base) {
            return (int) $base['id'];
        }
        $first = $this->db->get(db_prefix().'currencies')->row_array();

        return $first ? (int) $first['id'] : 1;
    }

    protected function currencyExists($id)
    {
        if ($id <= 0) {
            return false;
        }

        return $this->db->where('id', $id)->count_all_results(db_prefix().'currencies') > 0;
    }

    protected function resolveNextInvoiceNumber()
    {
        $opt = (int) get_option('next_invoice_number');
        $maxRow = $this->db->select_max('number')->get(db_prefix().'invoices')->row_array();
        $maxExisting = (int) ($maxRow['number'] ?? 0);

        return max($opt, $maxExisting + 1, 1);
    }

    protected function resolveActivePaymentModes()
    {
        $modes = $this->db->where('active', 1)->get(db_prefix().'payment_modes')->result_array();
        if (! empty($modes)) {
            return array_map(function ($m) {
                return (string) $m['id'];
            }, $modes);
        }

        return ['1'];
    }

    protected function jsonResponse($data, $statusCode = 200)
    {
        $this->output
            ->set_status_header($statusCode)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
