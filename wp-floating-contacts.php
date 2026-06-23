<?php
/**
 * Plugin Name: WP Floating Contacts
 * Description: Floating contact widget with WhatsApp, Telegram, Email and Phone buttons.
 * Version: 1.0.22
 * Author: Tsala[pov]
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Floating_Contacts {
    private string $option_name = 'wfc_settings';
    private string $version_option_name = 'wfc_plugin_version';
    private string $assets_version_option_name = 'wfc_assets_version';
    private string $plugin_version = '1.0.21';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_footer', [$this, 'render_widget']);
        add_action('init', [$this, 'maybe_upgrade_settings']);
        add_action('update_option_' . $this->option_name, [$this, 'clear_caches_after_settings_update'], 10, 3);
        add_action('add_option_' . $this->option_name, [$this, 'clear_caches_after_settings_add'], 10, 2);
    }

    public function get_default_settings(): array {
        return [
            'enabled' => '1',
            'position' => 'right',
            'main_color' => '#25d366',
            'whatsapp_color' => '#25d366',
            'telegram_color' => '#229ed9',
            'email_color' => '#ff4266',
            'phone_color' => '#222222',

            'button_size' => '58',
            'icon_size' => '26',
            'buttons_gap' => '10',
            'offset_bottom' => '40',
            'offset_side' => '40',
            'mobile_offset_bottom' => '32',
            'mobile_offset_side' => '24',

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

    public function maybe_upgrade_settings(): void {
        $stored_version = get_option($this->version_option_name, '');

        if (version_compare((string) $stored_version, '1.0.21', '>=')) {
            return;
        }

        $settings = get_option($this->option_name, []);

        if (is_array($settings) && !empty($settings)) {
            $old_to_new_defaults = [
                'offset_bottom' => ['32', '40'],
                'offset_side' => ['32', '40'],
                'mobile_offset_bottom' => ['22', '32'],
                'mobile_offset_side' => ['18', '24'],
            ];

            foreach ($old_to_new_defaults as $key => [$old_value, $new_value]) {
                if (!isset($settings[$key]) || (string) $settings[$key] === $old_value) {
                    $settings[$key] = $new_value;
                }
            }

            update_option($this->option_name, $settings);
        }

        update_option($this->version_option_name, $this->plugin_version);
    }

    public function clear_caches_after_settings_add(string $option, $value): void {
        $this->clear_caches_after_settings_update(null, $value, $option);
    }

    public function clear_caches_after_settings_update($old_value, $value, string $option): void {
        update_option($this->assets_version_option_name, (string) time(), false);

        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }

        if (function_exists('rocket_clean_domain')) {
            rocket_clean_domain();
        }

        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
        }

        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache();
        }

        if (function_exists('sg_cachepress_purge_cache')) {
            sg_cachepress_purge_cache();
        }

        if (class_exists('LiteSpeed_Cache_API') && method_exists('LiteSpeed_Cache_API', 'purge_all')) {
            LiteSpeed_Cache_API::purge_all();
        } elseif (has_action('litespeed_purge_all')) {
            do_action('litespeed_purge_all');
        }

        if (class_exists('autoptimizeCache') && method_exists('autoptimizeCache', 'clearall')) {
            autoptimizeCache::clearall();
        }
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

    private function sanitize_number_setting($value, int $default, int $min, int $max): string {
        $number = absint($value);

        if ($number < $min || $number > $max) {
            $number = $default;
        }

        return (string) $number;
    }

    public function sanitize_settings($input): array {
        $input = is_array($input) ? $input : [];

        $telegram_value = ltrim(sanitize_text_field($input['telegram_value'] ?? ''), '@');
        $telegram_value = preg_replace('/[^A-Za-z0-9_]/', '', $telegram_value);

        $email_value = sanitize_text_field($input['email_value'] ?? '');
        $email_value = preg_replace('/^mailto:/i', '', trim($email_value));
        $email_value = sanitize_email($email_value);

        return [
            'enabled' => !empty($input['enabled']) ? '1' : '0',
            'position' => in_array($input['position'] ?? 'right', ['right', 'left'], true) ? $input['position'] : 'right',
            'main_color' => sanitize_hex_color($input['main_color'] ?? '#25d366') ?: '#25d366',
            'whatsapp_color' => sanitize_hex_color($input['whatsapp_color'] ?? '#25d366') ?: '#25d366',
            'telegram_color' => sanitize_hex_color($input['telegram_color'] ?? '#229ed9') ?: '#229ed9',
            'email_color' => sanitize_hex_color($input['email_color'] ?? '#ff4266') ?: '#ff4266',
            'phone_color' => sanitize_hex_color($input['phone_color'] ?? '#222222') ?: '#222222',

            'button_size' => $this->sanitize_number_setting($input['button_size'] ?? 58, 58, 36, 96),
            'icon_size' => $this->sanitize_number_setting($input['icon_size'] ?? 26, 26, 14, 64),
            'buttons_gap' => $this->sanitize_number_setting($input['buttons_gap'] ?? 10, 10, 0, 40),
            'offset_bottom' => $this->sanitize_number_setting($input['offset_bottom'] ?? 40, 40, 0, 160),
            'offset_side' => $this->sanitize_number_setting($input['offset_side'] ?? 40, 40, 0, 160),
            'mobile_offset_bottom' => $this->sanitize_number_setting($input['mobile_offset_bottom'] ?? 32, 32, 0, 120),
            'mobile_offset_side' => $this->sanitize_number_setting($input['mobile_offset_side'] ?? 24, 24, 0, 120),

            'whatsapp_enabled' => !empty($input['whatsapp_enabled']) ? '1' : '0',
            'whatsapp_label' => sanitize_text_field($input['whatsapp_label'] ?? 'WhatsApp'),
            'whatsapp_value' => preg_replace('/[^0-9]/', '', $input['whatsapp_value'] ?? ''),

            'telegram_enabled' => !empty($input['telegram_enabled']) ? '1' : '0',
            'telegram_label' => sanitize_text_field($input['telegram_label'] ?? 'Telegram'),
            'telegram_value' => $telegram_value,

            'email_enabled' => !empty($input['email_enabled']) ? '1' : '0',
            'email_label' => sanitize_text_field($input['email_label'] ?? 'Email'),
            'email_value' => $email_value,

            'phone_enabled' => !empty($input['phone_enabled']) ? '1' : '0',
            'phone_label' => sanitize_text_field($input['phone_label'] ?? 'Call us'),
            'phone_value' => preg_replace('/[^0-9+]/', '', $input['phone_value'] ?? ''),
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

                    <tr>
                        <th scope="row">Dropdown button colors</th>
                        <td>
                            <label style="display:inline-block;margin-right:16px;">
                                WhatsApp
                                <input type="color" name="<?php echo esc_attr($this->option_name); ?>[whatsapp_color]" value="<?php echo esc_attr($settings['whatsapp_color']); ?>">
                            </label>

                            <label style="display:inline-block;margin-right:16px;">
                                Telegram
                                <input type="color" name="<?php echo esc_attr($this->option_name); ?>[telegram_color]" value="<?php echo esc_attr($settings['telegram_color']); ?>">
                            </label>

                            <label style="display:inline-block;margin-right:16px;">
                                Email
                                <input type="color" name="<?php echo esc_attr($this->option_name); ?>[email_color]" value="<?php echo esc_attr($settings['email_color']); ?>">
                            </label>

                            <label style="display:inline-block;margin-right:16px;">
                                Phone
                                <input type="color" name="<?php echo esc_attr($this->option_name); ?>[phone_color]" value="<?php echo esc_attr($settings['phone_color']); ?>">
                            </label>

                            <p class="description">These colors are used for the round buttons in the dropdown list.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Layout</th>
                        <td>
                            <label style="display:inline-block;margin-right:16px;margin-bottom:10px;">
                                Button size, px
                                <input type="number" min="36" max="96" name="<?php echo esc_attr($this->option_name); ?>[button_size]" value="<?php echo esc_attr($settings['button_size']); ?>" class="small-text">
                            </label>

                            <label style="display:inline-block;margin-right:16px;margin-bottom:10px;">
                                Icon size, px
                                <input type="number" min="14" max="64" name="<?php echo esc_attr($this->option_name); ?>[icon_size]" value="<?php echo esc_attr($settings['icon_size']); ?>" class="small-text">
                            </label>

                            <label style="display:inline-block;margin-right:16px;margin-bottom:10px;">
                                Gap, px
                                <input type="number" min="0" max="40" name="<?php echo esc_attr($this->option_name); ?>[buttons_gap]" value="<?php echo esc_attr($settings['buttons_gap']); ?>" class="small-text">
                            </label>

                            <br>

                            <label style="display:inline-block;margin-right:16px;margin-bottom:10px;">
                                Bottom offset, px
                                <input type="number" min="0" max="160" name="<?php echo esc_attr($this->option_name); ?>[offset_bottom]" value="<?php echo esc_attr($settings['offset_bottom']); ?>" class="small-text">
                            </label>

                            <label style="display:inline-block;margin-right:16px;margin-bottom:10px;">
                                Side offset, px
                                <input type="number" min="0" max="160" name="<?php echo esc_attr($this->option_name); ?>[offset_side]" value="<?php echo esc_attr($settings['offset_side']); ?>" class="small-text">
                            </label>

                            <br>

                            <label style="display:inline-block;margin-right:16px;margin-bottom:10px;">
                                Mobile bottom offset, px
                                <input type="number" min="0" max="120" name="<?php echo esc_attr($this->option_name); ?>[mobile_offset_bottom]" value="<?php echo esc_attr($settings['mobile_offset_bottom']); ?>" class="small-text">
                            </label>

                            <label style="display:inline-block;margin-right:16px;margin-bottom:10px;">
                                Mobile side offset, px
                                <input type="number" min="0" max="120" name="<?php echo esc_attr($this->option_name); ?>[mobile_offset_side]" value="<?php echo esc_attr($settings['mobile_offset_side']); ?>" class="small-text">
                            </label>

                            <p class="description">Button size controls both the main button and dropdown buttons. Side offset is applied to the selected left/right position.</p>
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

    private function get_asset_version(string $relative_path): string {
        $path = plugin_dir_path(__FILE__) . $relative_path;
        $file_version = file_exists($path) ? (string) filemtime($path) : $this->plugin_version;
        $settings_version = (string) get_option($this->assets_version_option_name, '');

        return $settings_version !== '' ? $file_version . '-' . $settings_version : $file_version;
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
            $this->get_asset_version('assets/css/widget.css')
        );

        wp_enqueue_script(
            'wp-floating-contacts-script',
            plugin_dir_url(__FILE__) . 'assets/js/widget.js',
            [],
            $this->get_asset_version('assets/js/widget.js'),
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
                $email = preg_replace('/^mailto:/i', '', trim($value));
                $email = sanitize_email($email);

                return is_email($email) ? 'mailto:' . $email : '';

            case 'phone':
                $phone = preg_replace('/[^0-9+]/', '', $value);
                return $phone ? 'tel:' . $phone : '';

            default:
                return '';
        }
    }

    public function get_icon_url(string $type): string {
        $icons = [
            'whatsapp' => 'assets/img/whatsapp.svg',
            'telegram' => 'assets/img/telegram.svg',
            'email' => 'assets/img/email.svg',
            'phone' => 'assets/img/phone.svg',
            'chat' => 'assets/img/chat.svg',
            'close' => 'assets/img/close.svg',
        ];

        if (empty($icons[$type])) {
            return '';
        }

        $url = plugin_dir_url(__FILE__) . $icons[$type];

        return apply_filters('wfc_icon_url', $url, $type);
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
                'icon' => $this->get_icon_url('whatsapp'),
                'class' => 'wfc-whatsapp',
                'color' => $settings['whatsapp_color'],
            ],
            'telegram' => [
                'enabled' => $settings['telegram_enabled'],
                'label' => $settings['telegram_label'],
                'value' => $settings['telegram_value'],
                'icon' => $this->get_icon_url('telegram'),
                'class' => 'wfc-telegram',
                'color' => $settings['telegram_color'],
            ],
            'email' => [
                'enabled' => $settings['email_enabled'],
                'label' => $settings['email_label'],
                'value' => $settings['email_value'],
                'icon' => $this->get_icon_url('email'),
                'class' => 'wfc-email',
                'color' => $settings['email_color'],
            ],
            'phone' => [
                'enabled' => $settings['phone_enabled'],
                'label' => $settings['phone_label'],
                'value' => $settings['phone_value'],
                'icon' => $this->get_icon_url('phone'),
                'class' => 'wfc-phone',
                'color' => $settings['phone_color'],
            ],
        ];

        $visible_channels = [];

        foreach ($channels as $type => $channel) {
            $url = $this->build_channel_url($type, $channel['value']);

            if ($channel['enabled'] === '1' && $url !== '') {
                $channel['url'] = $url;
                $channel['type'] = $type;
                $channel['opens_new_tab'] = in_array($type, ['whatsapp', 'telegram'], true);
                $visible_channels[$type] = $channel;
            }
        }

        if (empty($visible_channels)) {
            return;
        }

        $position_class = $settings['position'] === 'left' ? 'wfc-left' : 'wfc-right';
        ?>

        <div
            class="wfc-widget <?php echo esc_attr($position_class); ?>"
            style="--wfc-main-color: <?php echo esc_attr($settings['main_color']); ?>; --wfc-size: <?php echo esc_attr($settings['button_size']); ?>px; --wfc-icon-size: <?php echo esc_attr($settings['icon_size']); ?>px; --wfc-gap: <?php echo esc_attr($settings['buttons_gap']); ?>px; --wfc-offset-bottom: <?php echo esc_attr($settings['offset_bottom']); ?>px; --wfc-offset-side: <?php echo esc_attr($settings['offset_side']); ?>px; --wfc-mobile-offset-bottom: <?php echo esc_attr($settings['mobile_offset_bottom']); ?>px; --wfc-mobile-offset-side: <?php echo esc_attr($settings['mobile_offset_side']); ?>px;"
        >
            <button class="wfc-main-button" type="button" aria-label="Open contact options" aria-expanded="false">
                <span class="wfc-main-icon wfc-main-icon-chat" aria-hidden="true">
                    <img class="wfc-main-icon-img" src="<?php echo esc_url($this->get_icon_url('chat')); ?>" alt="" decoding="async">
                </span>

                <span class="wfc-main-icon wfc-main-icon-close" aria-hidden="true">
                    <img class="wfc-main-icon-img" src="<?php echo esc_url($this->get_icon_url('close')); ?>" alt="" decoding="async">
                </span>
            </button>

            <div class="wfc-channels">
                <?php foreach ($visible_channels as $channel): ?>
                    <a
                        href="<?php echo esc_url($channel['url']); ?>"
                        <?php if (!empty($channel['opens_new_tab'])): ?>target="_blank" rel="noopener noreferrer"<?php endif; ?>
                        class="wfc-channel <?php echo esc_attr($channel['class']); ?>"
                        data-wfc-type="<?php echo esc_attr($channel['type']); ?>"
                        style="--wfc-channel-color: <?php echo esc_attr($channel['color']); ?>;"
                        aria-label="<?php echo esc_attr($channel['label']); ?>"
                        title="<?php echo esc_attr($channel['label']); ?>"
                    >
                        <span class="wfc-channel-icon" aria-hidden="true">
                            <img src="<?php echo esc_url($channel['icon']); ?>" alt="" decoding="async">
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php
    }
}

new WP_Floating_Contacts();