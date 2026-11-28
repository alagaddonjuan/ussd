<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="tw-flex tw-items-center tw-justify-between mbot15">
                            <h4 class="no-margin bold">Q4I Comms API Bridge</h4>
                            <span class="label <?php echo $api_enabled ? 'label-success' : 'label-danger'; ?>">
                                <?php echo $api_enabled ? 'Enabled' : 'Disabled'; ?>
                            </span>
                        </div>
                        <p class="text-muted">
                            Connects your <strong>ussd.q4iltd.com</strong> portal to Perfex CRM for customer matching, invoice syncing, and invoice lookup.
                        </p>
                        <hr />

                        <div class="form-group">
                            <label class="control-label">API Token (<code>PERFEX_API_TOKEN</code>)</label>
                            <div class="input-group">
                                <input type="text" id="q4i-api-token-input" class="form-control" readonly value="<?php echo html_escape($api_token); ?>">
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-default" onclick="navigator.clipboard.writeText(document.getElementById('q4i-api-token-input').value); alert('Token copied!');">
                                        Copy Token
                                    </button>
                                </span>
                            </div>
                        </div>

                        <div class="tw-flex tw-gap-2 mbot20">
                            <?php echo form_open(admin_url('q4i_api/toggle'), ['style' => 'display:inline-block;margin-right:8px;']); ?>
                                <button type="submit" class="btn <?php echo $api_enabled ? 'btn-warning' : 'btn-success'; ?>">
                                    <?php echo $api_enabled ? 'Disable API' : 'Enable API'; ?>
                                </button>
                            <?php echo form_close(); ?>

                            <?php echo form_open(admin_url('q4i_api/regenerate'), ['style' => 'display:inline-block;', 'onsubmit' => "return confirm('Regenerating will invalidate the old token in your USSD portal .env. Continue?')"]); ?>
                                <button type="submit" class="btn btn-danger">Regenerate Token</button>
                            <?php echo form_close(); ?>
                        </div>

                        <hr />
                        <h5 class="bold">1. Add to <code>ussd.q4iltd.com/.env</code></h5>
                        <?php
                        $defaultCurrency = 1;
                        foreach ($currencies as $cur) {
                            if ((int) ($cur['isdefault'] ?? 0) === 1 || stripos((string) ($cur['name'] ?? ''), 'NGN') !== false) {
                                $defaultCurrency = (int) $cur['id'];
                                if (stripos((string) ($cur['name'] ?? ''), 'NGN') !== false) {
                                    break;
                                }
                            }
                        }
                        ?>
                        <pre style="background:#f8fafc;border:1px solid #e2e8f0;padding:12px;border-radius:6px;font-size:12px;">PERFEX_CRM_URL="<?php echo rtrim(site_url(), '/'); ?>"
PERFEX_API_TOKEN="<?php echo html_escape($api_token); ?>"
PERFEX_API_PREFIX="/q4i_api/api"
PERFEX_CURRENCY_ID=<?php echo $defaultCurrency; ?>

PERFEX_AUTO_SYNC=true</pre>

                        <h5 class="bold mtop15">2. Available Endpoints</h5>
                        <ul class="list-unstyled" style="font-size:13px;line-height:1.8;">
                            <li><code>GET <?php echo site_url('q4i_api/api/health'); ?></code></li>
                            <li><code>GET/POST <?php echo site_url('q4i_api/api/customers'); ?></code></li>
                            <li><code>GET <?php echo site_url('q4i_api/api/customers_search/{query}'); ?></code></li>
                            <li><code>GET <?php echo site_url('q4i_api/api/contacts_search/{email}'); ?></code></li>
                            <li><code>GET/POST <?php echo site_url('q4i_api/api/invoices'); ?></code></li>
                        </ul>

                        <?php if (! empty($currencies)) : ?>
                            <p class="text-muted mtop10" style="font-size:12px;">
                                <strong>Currencies in Perfex:</strong>
                                <?php foreach ($currencies as $c) : ?>
                                    <code>#<?php echo (int) $c['id']; ?> <?php echo html_escape($c['name']); ?></code>
                                <?php endforeach; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
