<?php

if (!defined('ABSPATH')) {
    die('Code your dream');
}

class ThueAPI_Gateway extends WC_Payment_Gateway
{

    public function __construct()
    {

        $this->id = 'thueapi';
        $this->icon = $this->get_option('bankIcon') ?: apply_filters('woocommerce_bacs_icon', plugin_dir_url(__DIR__) . 'assets/images/bank.svg');
        $this->has_fields = false;
        $this->method_title = __('ThueAPI - Thanh toán đơn giản với hệ thống tự động !', 'woocommerce');
        $this->method_description = __('Giải pháp xử lý giao dịch tự động cho đơn hàng thanh toán bằng hình thức chuyển khoản qua các ngân hàng tại Việt Nam. <br/>Các ngân hàng thông dụng như: Vietcombank, Techcombank, ACB, Momo, MBBank, TPBank, VPBank...', 'woocommerce');

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->receivedOrderTextBeforePaid = $this->get_option('receivedOrderTextBeforePaid');
        $this->receivedOrderTextAfterPaid = $this->get_option('receivedOrderTextAfterPaid');
        $this->transferGuide = $this->get_option('transferGuide');

        $this->token = $this->get_option('token');

        if (strlen($this->token) < 50) {
            $this->update_option('token', $this->randomString());
        }

        $this->bankAccounts = get_option('woocommerce_bacs_accounts', [
            [
                'account_name' => $this->get_option('account_name'),
                'account_number' => $this->get_option('account_number'),
                'bank_name' => $this->get_option('bank_name'),
            ]
        ]);

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'updateBankAccounts']);
//        add_action('woocommerce_thankyou', [$this, 'thankyouPage']);
        add_action('woocommerce_thankyou_order_received_text', [$this, 'thankyouPage'], 10, 2);
        add_action('woocommerce_api_' . $this->id, [$this, 'paymentProcess']);
    }

    public function init_form_fields()
    {

        $this->form_fields = [
            'enabled' => [
                'title' => __('Enable/Disable', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('ThueAPI', 'woocommerce'),
                'default' => 'no',
            ],
            'title' => [
                'title' => __('Title', 'woocommerce'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
                'default' => "Chuyển khoản ngân hàng 24/7",
                'desc_tip' => true,
            ],
            'description' => [
                'title' => __('Description', 'woocommerce'),
                'type' => 'textarea',
                'description' => __('Payment method description that the customer will see on your checkout.', 'woocommerce'),
                'default' => "Thực hiện thanh toán chuyển khoản vào tài khoản ngân hàng của chúng tôi. Vui lòng điền Mã đơn hàng của bạn trong phần Nội dung thanh toán. Đơn hàng của bạn sẽ đươc xác nhận tự động ngay sau khi tài khoản ngân hàng của chúng tôi nhận được tiền.",
                'desc_tip' => true,
            ],
            'receivedOrderTextBeforePaid' => [
                'title' => __('Thông báo trước khi thanh toán', 'woocommerce'),
                'type' => 'textarea',
                'description' => 'Thông báo sẽ hiển thị ở trang Order Received',
                'default' => '<h2>Hãy chọn ngân hàng để chuyển khoản</h2>',
                'desc_tip' => true,
            ],
            'receivedOrderTextAfterPaid' => [
                'title' => __('Thông báo sau khi thanh toán', 'woocommerce'),
                'type' => 'textarea',
                'description' => 'Thông báo sẽ hiển thị ở trang Order Received',
                'default' => '<h2>Cám ơn bạn đã đặt hàng trên website của chúng tôi !</h2>',
                'desc_tip' => true,
            ],
            'transferGuide' => [
                'title' => __('Hướng dẫn thanh toán', 'woocommerce'),
                'type' => 'textarea',
                'default' => 'Vui lòng chuyển tiền đến STK: %account tại %bank kèm nội dung %order để hệ thống xác nhận đơn hàng tự động cho giao dịch bạn.',
                'desc_tip' => true,
            ],
            'bankAccounts' => [
                'type' => 'bankAccounts',
            ],
            'bankIcon' => array(
                'title' => __('Bank Icon', 'woocommerce'),
                'type' => 'text',
                'description' => '',
                'default' => __('', 'woocommerce'),
                'desc_tip' => false,
                'class' => 'uploadinput',
            ),
            'token' => [
                'title' => __('Token', 'woocommerce'),
                'type' => 'text',
                'description' => 'Token',
                'default' => '',
                'required' => 'true',
                'desc_tip' => true,
            ],
            'syntaxPrefix' => [
                'title' => __('Ký tự định danh', 'woocommerce'),
                'type' => 'text',
                'description' => 'Ký tự này giúp định danh và nhận biết các giao dịch thanh toán cho đơn hàng',
                'default' => 'DH',
                'required' => 'true',
                'desc_tip' => true,
            ],
            'orderStatusAfterPaid' => [
                'title' => __('Trạng thái thanh toán đủ'),
                'type' => 'select',
                'description' => __('Vui lòng chọn một trạng thái sau khi thanh toán đủ.', 'woocommerce'),
                'default' => 'wc-completed',
                'class' => 'status_type wc-enhanced-select',
                'options' => wc_get_order_statuses(),
                'desc_tip' => true,

            ],
            'orderStatusAfterOverPaid' => [
                'title' => __('Trạng thái thanh toán dư'),
                'type' => 'select',
                'description' => __('Vui lòng chọn một trạng thái sau khi thanh toán dư.', 'woocommerce'),
                'default' => 'wc-over-paid',
                'class' => 'status_type wc-enhanced-select',
                'options' => wc_get_order_statuses(),
                'desc_tip' => true,

            ],
            'orderStatusAfterLessPaid' => [
                'title' => __('Trạng thái thanh toán thiếu'),
                'type' => 'select',
                'description' => __('Vui lòng chọn một trạng thái sau khi thanh toán thiếu.', 'woocommerce'),
                'default' => 'wc-less-paid',
                'class' => 'status_type wc-enhanced-select',
                'options' => wc_get_order_statuses(),
                'desc_tip' => true,

            ],
        ];

    }

    private function randomString($length = 50)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function paymentProcess()
    {
        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'checkClientPayment') {

            $orderId = intval(preg_replace('#(\D+)#', '', $_REQUEST['order']));

            $order = wc_get_order($orderId);

            if (!$order) {

                die(json_encode([
                    'success' => false,
                    'message' => 'No order found !'
                ]));
            }

            if ($order->has_status(['completed', 'over-paid'])) {

                die(json_encode([
                    'success' => true,
                    'message' => 'Order paid successfully'
                ]));
            }

            die(json_encode([
                'success' => false,
                'message' => 'waiting payment...'
            ]));

        } else {

            $raw = file_get_contents('php://input');

            $transaction = json_decode($raw);

            if (json_last_error() != JSON_ERROR_NONE) {

                die(json_encode([
                    'success' => false,
                    'message' => 'No data !'
                ]));
            }

            $token = $this->getHeader('X-THUEAPI');

            if ($token != $this->token) {

                die(json_encode([
                    'success' => false,
                    'message' => 'Token incorrect !'
                ]));
            }

            if (empty($transaction->txn_id)) {

                die(json_encode([
                    'success' => false,
                    'message' => 'No transaction txn id !'
                ]));
            }

            $orderId = intval(preg_replace('#(\D+)#', '', $transaction->content));

            $order = wc_get_order($orderId);

            if (!$order) {

                die(json_encode([
                    'success' => false,
                    'message' => 'No order found !'
                ]));
            }

            if ($order->has_status(['completed', 'over-paid'])) {

                die(json_encode([
                    'success' => false,
                    'message' => 'Order was paid !'
                ]));
            }

            $money = $order->get_total();
            $paid = $transaction->money;

            if ($paid < $money) {

                $order->add_order_note('Số tiền thanh toán không đủ với giá trị đơn hàng !');
                $order->update_status($this->get_option('orderStatusAfterLessPaid'));

                die(json_encode([
                    'success' => false,
                    'message' => 'Not enough money !'
                ]));

            } else {

                $order->payment_complete();

                // Đơn hàng sẽ tự động trừ stock nếu thuộc wc-processing hoặc wc-completed
                if (!in_array($this->get_option('orderStatusAfterPaid'), ['wc-processing', 'wc-completed', 'wc-over-paid'])) {
                    wc_reduce_stock_levels($order);
                }

                if ($paid > $money) {
                    $order->update_status($this->get_option('orderStatusAfterOverPaid'));
                } else {
                    $order->update_status($this->get_option('orderStatusAfterPaid'));
                }

                die(json_encode([
                    'success' => true,
                    'message' => 'Order paid successfully'
                ]));

            }
        }

    }

    private function getHeader($header)
    {
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                if (str_replace(' ', '-', ucwords(str_replace('_', ' ', substr($name, 5)))) == $header)
                    return $value;
            }
        }

        return false;
    }

    public function generate_bankAccounts_html()
    {
        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc"><?php esc_html_e('Tài khoản ngân hàng:', 'woocommerce'); ?></th>
            <td class="forminp" id="bacs_accounts">
                <div class="wc_input_table_wrapper">
                    <table class="widefat wc_input_table sortable" cellspacing="0">
                        <thead>
                        <tr>
                            <th class="sort">&nbsp;</th>
                            <th><?php esc_html_e('Tên tài khoản', 'woocommerce'); ?></th>
                            <th><?php esc_html_e('Số tài khoản', 'woocommerce'); ?></th>
                            <th><?php esc_html_e('Ngân hàng', 'woocommerce'); ?></th>
                        </tr>
                        </thead>
                        <tbody class="accounts">
                        <?php
                        $i = -1;
                        if ($this->bankAccounts) {
                            foreach ($this->bankAccounts as $account) {
                                $i++;

                                echo '<tr class="account">
										<td class="sort"></td>
										<td><input type="text" value="' . esc_attr(wp_unslash($account['account_name'])) . '" name="bacs_account_name[' . esc_attr($i) . ']" /></td>
										<td><input type="text" value="' . esc_attr($account['account_number']) . '" name="bacs_account_number[' . esc_attr($i) . ']" /></td>
										<td><input type="text" value="' . esc_attr(wp_unslash($account['bank_name'])) . '" name="bacs_bank_name[' . esc_attr($i) . ']" /></td>
									</tr>';
                            }
                        }
                        ?>
                        </tbody>
                        <tfoot>
                        <tr>
                            <th colspan="7">
                                <a href="#" class="add button"><?php esc_html_e('+ Thêm tài khoản', 'woocommerce'); ?></a>
                                <a href="#" class="remove_rows button"><?php esc_html_e('Xóa tài khoản', 'woocommerce'); ?></a>
                            </th>
                        </tr>
                        </tfoot>
                    </table>
                </div>
                <script type="text/javascript">
                    jQuery(function () {
                        jQuery('#bacs_accounts').on('click', 'a.add', function () {

                            var size = jQuery('#bacs_accounts').find('tbody .account').length;

                            jQuery('<tr class="account">\
									<td class="sort"></td>\
									<td><input type="text" name="bacs_account_name[' + size + ']" /></td>\
									<td><input type="text" name="bacs_account_number[' + size + ']" /></td>\
									<td><input type="text" name="bacs_bank_name[' + size + ']" /></td>\
								</tr>').appendTo('#bacs_accounts table tbody');

                            return false;
                        });
                    });
                </script>
            </td>
        </tr>
        <?php
        return ob_get_clean();

    }

    public function updateBankAccounts()
    {

        $accounts = [];

        if (isset($_POST['bacs_account_name']) && isset($_POST['bacs_account_number']) && isset($_POST['bacs_bank_name'])) {

            $account_names = wc_clean(wp_unslash($_POST['bacs_account_name']));
            $account_numbers = wc_clean(wp_unslash($_POST['bacs_account_number']));
            $bank_names = wc_clean(wp_unslash($_POST['bacs_bank_name']));

            foreach ($account_names as $i => $name) {
                if (!isset($account_names[$i])) {
                    continue;
                }

                $accounts[] = [
                    'account_name' => $account_names[$i],
                    'account_number' => $account_numbers[$i],
                    'bank_name' => $bank_names[$i]
                ];
            }
        }

        update_option('woocommerce_bacs_accounts', $accounts);
    }

    public function thankyouPage($orderText, $order)
    {
        if ($order && $this->id === $order->get_payment_method()) {
            echo $order->is_paid() ? $this->receivedOrderTextAfterPaid : $this->bankLists($order->id);
        }
    }

    protected function bankLists($order_id = '')
    {
        if (empty($this->bankAccounts)) {
            return;
        }

        $bacs_accounts = apply_filters('woocommerce_bacs_accounts', $this->bankAccounts, $order_id);

        printf(
            '<section id="thueapi"><banks assets="%s" accounts="%s" header="%s" footer="%s" order="%s" endpoint="%s"/></section>',
            plugin_dir_url(__DIR__),
            htmlentities(json_encode($bacs_accounts, JSON_HEX_QUOT)),
            wp_kses_post(wpautop(wptexturize($this->receivedOrderTextBeforePaid))),
            wp_kses_post(wpautop(wptexturize($this->transferGuide))),
            $this->get_option('syntaxPrefix') . $order_id,
            site_url('/wc-api/' . $this->id)
        );

    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        if ($order->get_total() > 0) {
            $order->update_status(apply_filters('woocommerce_bacs_process_payment_order_status', 'on-hold', $order), __('Awaiting BACS payment', 'woocommerce'));
        } else {
            $order->payment_complete();
        }

        WC()->cart->empty_cart();

        return [
            'result' => 'success',
            'redirect' => $this->get_return_url($order),
        ];

    }

}