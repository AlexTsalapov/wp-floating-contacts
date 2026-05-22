<?php
/**
 * Plugin Name: WP Floating Contacts
 * Description: Floating contact widget with WhatsApp, Telegram, Email and Phone buttons.
 * Version: 1.0.11
 * Author: Tsala[pov]
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Floating_Contacts {
    private string $option_name = 'wfc_settings';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_footer', [$this, 'render_widget']);
    }

    public function get_default_settings(): array {
        return [
            'enabled' => '1',
            'position' => 'right',
            'main_color' => '#25d366',

            'whatsapp_enabled' => '1',
            'whatsapp_label' => 'WhatsApp',
            'whatsapp_value' => '',

            'telegram_enabled' => '1',
            'telegram_label' => 'Telegram',
            'telegram_value' => '',

            'email_enabled' => '1',
            'email_label' => 'Email',
            'email_value' => '',

            'phone_enabled' => '1',
            'phone_label' => 'Phone',
            'phone_value' => '',
        ];
    }

    public function get_settings(): array {
        $settings = get_option($this->option_name, []);
        return wp_parse_args($settings, $this->get_default_settings());
    }

    public function add_admin_page(): void {
        add_options_page(
            'Floating Contacts',
            'Floating Contacts',
            'manage_options',
            'wp-floating-contacts',
            [$this, 'render_admin_page']
        );
    }

    public function register_settings(): void {
        register_setting(
            'wfc_settings_group',
            $this->option_name,
            [$this, 'sanitize_settings']
        );
    }

    public function sanitize_settings($input): array {
        $input = is_array($input) ? $input : [];

        return [
            'enabled' => !empty($input['enabled']) ? '1' : '0',
            'position' => in_array($input['position'] ?? 'right', ['right', 'left'], true) ? $input['position'] : 'right',
            'main_color' => sanitize_hex_color($input['main_color'] ?? '#25d366') ?: '#25d366',

            'whatsapp_enabled' => !empty($input['whatsapp_enabled']) ? '1' : '0',
            'whatsapp_label' => sanitize_text_field($input['whatsapp_label'] ?? 'WhatsApp'),
            'whatsapp_value' => sanitize_text_field($input['whatsapp_value'] ?? ''),

            'telegram_enabled' => !empty($input['telegram_enabled']) ? '1' : '0',
            'telegram_label' => sanitize_text_field($input['telegram_label'] ?? 'Telegram'),
            'telegram_value' => sanitize_text_field($input['telegram_value'] ?? ''),

            'email_enabled' => !empty($input['email_enabled']) ? '1' : '0',
            'email_label' => sanitize_text_field($input['email_label'] ?? 'Email'),
            'email_value' => sanitize_email($input['email_value'] ?? ''),

            'phone_enabled' => !empty($input['phone_enabled']) ? '1' : '0',
            'phone_label' => sanitize_text_field($input['phone_label'] ?? 'Call us'),
            'phone_value' => sanitize_text_field($input['phone_value'] ?? ''),
        ];
    }

    public function render_admin_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = $this->get_settings();
        ?>

        <div class="wrap">
            <h1>Floating Contacts</h1>

            <form method="post" action="options.php">
                <?php settings_fields('wfc_settings_group'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">Enable widget</th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[enabled]" value="1" <?php checked($settings['enabled'], '1'); ?>>
                                Show the widget on the website
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Position</th>
                        <td>
                            <select name="<?php echo esc_attr($this->option_name); ?>[position]">
                                <option value="right" <?php selected($settings['position'], 'right'); ?>>Right</option>
                                <option value="left" <?php selected($settings['position'], 'left'); ?>>Left</option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Main button color</th>
                        <td>
                            <input type="color" name="<?php echo esc_attr($this->option_name); ?>[main_color]" value="<?php echo esc_attr($settings['main_color']); ?>">
                        </td>
                    </tr>
                </table>

                <h2>Contact channels</h2>

                <table class="widefat striped" style="max-width: 900px;">
                    <thead>
                        <tr>
                            <th>Enabled</th>
                            <th>Channel</th>
                            <th>Button label</th>
                            <th>Value</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td>
                                <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[whatsapp_enabled]" value="1" <?php checked($settings['whatsapp_enabled'], '1'); ?>>
                            </td>
                            <td><strong>WhatsApp</strong></td>
                            <td>
                                <input type="text" name="<?php echo esc_attr($this->option_name); ?>[whatsapp_label]" value="<?php echo esc_attr($settings['whatsapp_label']); ?>" class="regular-text">
                            </td>
                            <td>
                                <input type="text" name="<?php echo esc_attr($this->option_name); ?>[whatsapp_value]" value="<?php echo esc_attr($settings['whatsapp_value']); ?>" class="regular-text" placeholder="34999999999">
                                <p class="description">Enter the number without +, spaces or brackets. Example: 34999999999</p>
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[telegram_enabled]" value="1" <?php checked($settings['telegram_enabled'], '1'); ?>>
                            </td>
                            <td><strong>Telegram</strong></td>
                            <td>
                                <input type="text" name="<?php echo esc_attr($this->option_name); ?>[telegram_label]" value="<?php echo esc_attr($settings['telegram_label']); ?>" class="regular-text">
                            </td>
                            <td>
                                <input type="text" name="<?php echo esc_attr($this->option_name); ?>[telegram_value]" value="<?php echo esc_attr($settings['telegram_value']); ?>" class="regular-text" placeholder="username">
                                <p class="description">Enter the username without @. Example: mycompany</p>
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[email_enabled]" value="1" <?php checked($settings['email_enabled'], '1'); ?>>
                            </td>
                            <td><strong>Email</strong></td>
                            <td>
                                <input type="text" name="<?php echo esc_attr($this->option_name); ?>[email_label]" value="<?php echo esc_attr($settings['email_label']); ?>" class="regular-text">
                            </td>
                            <td>
                                <input type="email" name="<?php echo esc_attr($this->option_name); ?>[email_value]" value="<?php echo esc_attr($settings['email_value']); ?>" class="regular-text" placeholder="info@example.com">
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[phone_enabled]" value="1" <?php checked($settings['phone_enabled'], '1'); ?>>
                            </td>
                            <td><strong>Phone</strong></td>
                            <td>
                                <input type="text" name="<?php echo esc_attr($this->option_name); ?>[phone_label]" value="<?php echo esc_attr($settings['phone_label']); ?>" class="regular-text">
                            </td>
                            <td>
                                <input type="text" name="<?php echo esc_attr($this->option_name); ?>[phone_value]" value="<?php echo esc_attr($settings['phone_value']); ?>" class="regular-text" placeholder="+34999999999">
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button('Save settings'); ?>
            </form>
        </div>

        <?php
    }

    public function enqueue_assets(): void {
        $settings = $this->get_settings();

        if ($settings['enabled'] !== '1') {
            return;
        }

        wp_enqueue_style(
            'wp-floating-contacts-style',
            plugin_dir_url(__FILE__) . 'assets/css/widget.css',
            [],
            '1.0.11'
        );

        wp_enqueue_script(
            'wp-floating-contacts-script',
            plugin_dir_url(__FILE__) . 'assets/js/widget.js',
            [],
            '1.0.11',
            true
        );
    }

    public function build_channel_url(string $type, string $value): string {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        switch ($type) {
            case 'whatsapp':
                $phone = preg_replace('/[^0-9]/', '', $value);
                return $phone ? 'https://wa.me/' . $phone : '';

            case 'telegram':
                $username = ltrim($value, '@');
                return $username ? 'https://t.me/' . rawurlencode($username) : '';

            case 'email':
                return is_email($value) ? 'mailto:' . sanitize_email($value) : '';

            case 'phone':
                $phone = preg_replace('/[^0-9+]/', '', $value);
                return $phone ? 'tel:' . $phone : '';

            default:
                return '';
        }
    }

    public function get_icon_svg(string $type): string {
        $icons = [
            'whatsapp' => '<svg viewBox="0 0 32 32" width="20" height="20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path fill="currentColor" d="M16.04 3C9.42 3 4.05 8.35 4.05 14.95c0 2.1.55 4.15 1.6 5.95L4 27l6.27-1.64a12.02 12.02 0 0 0 5.77 1.47c6.62 0 11.99-5.35 11.99-11.95C28.03 8.35 22.66 3 16.04 3Zm0 21.77c-1.78 0-3.52-.48-5.03-1.4l-.36-.22-3.72.98.99-3.62-.24-.37a9.77 9.77 0 0 1-1.5-5.2c0-5.43 4.42-9.84 9.86-9.84s9.86 4.41 9.86 9.84-4.42 9.83-9.86 9.83Zm5.4-7.36c-.3-.15-1.75-.86-2.02-.96-.27-.1-.47-.15-.67.15-.2.3-.77.96-.95 1.16-.17.2-.35.22-.64.07-.3-.15-1.25-.46-2.38-1.47-.88-.78-1.47-1.75-1.65-2.04-.17-.3-.02-.46.13-.6.13-.13.3-.35.45-.52.15-.18.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.8.37-.27.3-1.05 1.03-1.05 2.51 0 1.48 1.08 2.91 1.23 3.11.15.2 2.13 3.25 5.16 4.55.72.31 1.28.5 1.72.64.72.23 1.38.2 1.9.12.58-.09 1.75-.72 2-1.41.25-.7.25-1.3.17-1.42-.07-.13-.27-.2-.57-.35Z"/></svg>',

            'telegram' => '<svg viewBox="0 0 240 240" width="20" height="20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path fill="currentColor" d="M120 0C53.7 0 0 53.7 0 120s53.7 120 120 120 120-53.7 120-120S186.3 0 120 0Zm55.8 80.8-19.7 92.9c-1.5 6.6-5.4 8.2-10.9 5.1l-30.1-22.2-14.5 14c-1.6 1.6-3 3-6.1 3l2.2-30.6 55.7-50.3c2.4-2.2-.5-3.4-3.8-1.2l-68.8 43.3-29.6-9.3c-6.4-2-6.6-6.4 1.3-9.5L167.2 71.4c5.4-2 10.1 1.3 8.6 9.4Z"/></svg>',

          'email' => '<svg viewBox="0 0 24 24" width="20" height="20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path fill="currentColor" d="M3.75 5.5h16.5c.69 0 1.25.56 1.25 1.25v10.5c0 .69-.56 1.25-1.25 1.25H3.75c-.69 0-1.25-.56-1.25-1.25V6.75c0-.69.56-1.25 1.25-1.25Zm1.38 2 6.42 5.08c.26.2.64.2.9 0l6.42-5.08H5.13Zm14.37 9V8.84l-5.82 4.6a2.7 2.7 0 0 1-3.36 0L4.5 8.84v7.66h15Z"/></svg>',

           'phone' => '<svg viewBox="0 0 24 24" width="20" height="20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path fill="currentColor" d="M6.54 3.5c.47 0 .9.28 1.09.71l1.72 3.88c.18.41.11.89-.18 1.23l-1.2 1.39c.88 1.69 2.25 3.06 3.94 3.94l1.39-1.2c.34-.29.82-.36 1.23-.18l3.88 1.72c.43.19.71.62.71 1.09v3.04c0 .64-.5 1.17-1.14 1.2-.37.02-.74.03-1.11.03C9.52 20.35 3.65 14.48 3.65 7.13c0-.37.01-.74.03-1.11.03-.64.56-1.14 1.2-1.14h1.66Z"/></svg>',     ];

        return $icons[$type] ?? '';
    }

    public function get_svg_allowed_html(): array {
        return [
            'svg' => [
                'viewBox' => true,
                'viewbox' => true,
                'width' => true,
                'height' => true,
                'fill' => true,
                'xmlns' => true,
                'aria-hidden' => true,
                'focusable' => true,
            ],
            'path' => [
                'd' => true,
                'fill' => true,
                'stroke' => true,
                'stroke-width' => true,
                'stroke-linecap' => true,
                'stroke-linejoin' => true,
            ],
        ];
    }

    public function render_widget(): void {
        $settings = $this->get_settings();

        if ($settings['enabled'] !== '1') {
            return;
        }

        $channels = [
            'whatsapp' => [
                'enabled' => $settings['whatsapp_enabled'],
                'label' => $settings['whatsapp_label'],
                'value' => $settings['whatsapp_value'],
                'icon' => $this->get_icon_svg('whatsapp'),
                'class' => 'wfc-whatsapp',
            ],
            'telegram' => [
                'enabled' => $settings['telegram_enabled'],
                'label' => $settings['telegram_label'],
                'value' => $settings['telegram_value'],
                'icon' => $this->get_icon_svg('telegram'),
                'class' => 'wfc-telegram',
            ],
            'email' => [
                'enabled' => $settings['email_enabled'],
                'label' => $settings['email_label'],
                'value' => $settings['email_value'],
                'icon' => $this->get_icon_svg('email'),
                'class' => 'wfc-email',
            ],
            'phone' => [
                'enabled' => $settings['phone_enabled'],
                'label' => $settings['phone_label'],
                'value' => $settings['phone_value'],
                'icon' => $this->get_icon_svg('phone'),
                'class' => 'wfc-phone',
            ],
        ];

        $visible_channels = [];

        foreach ($channels as $type => $channel) {
            $url = $this->build_channel_url($type, $channel['value']);

            if ($channel['enabled'] === '1' && $url !== '') {
                $channel['url'] = $url;
                $visible_channels[$type] = $channel;
            }
        }

        if (empty($visible_channels)) {
            return;
        }

        $position_class = $settings['position'] === 'left' ? 'wfc-left' : 'wfc-right';
        ?>

        <div class="wfc-widget <?php echo esc_attr($position_class); ?>" style="--wfc-main-color: <?php echo esc_attr($settings['main_color']); ?>;">
            <button class="wfc-main-button" type="button" aria-label="Open contact options" aria-expanded="false">
                <span class="wfc-main-icon wfc-main-icon-chat" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="28" height="28" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 5.5C4 4.12 5.12 3 6.5 3h11C18.88 3 20 4.12 20 5.5v8C20 14.88 18.88 16 17.5 16H9l-4.2 3.15A.5.5 0 0 1 4 18.75V5.5Z" fill="currentColor"/>
                        <path d="M8 8h8M8 11.5h5.5" stroke="var(--wfc-main-color, #25d366)" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </span>

                <span class="wfc-main-icon wfc-main-icon-close" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="30" height="30" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                    </svg>
                </span>
            </button>

            <div class="wfc-channels">
                <?php foreach ($visible_channels as $channel): ?>
                    <a
                        href="<?php echo esc_url($channel['url']); ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="wfc-channel <?php echo esc_attr($channel['class']); ?>"
                    >
                        <span class="wfc-channel-icon">
                            <?php echo wp_kses($channel['icon'], $this->get_svg_allowed_html()); ?>
                        </span>

                        <span class="wfc-channel-label">
                            <?php echo esc_html($channel['label']); ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php
    }
}

new WP_Floating_Contacts();