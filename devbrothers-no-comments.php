<?php
/**
 * Plugin Name: DevBrothers No Comments
 * Plugin URI: https://devbrothers.ru/no-comments/
 * Description: Completely disable comments on your WordPress site. Remove all existing comments with one click.
 * Version: 1.0.0
 * Author: DevBrothers
 * Author URI: https://devbrothers.ru
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: devbrothers-no-comments
 * Requires at least: 5.8
 * Tested up to: 7.0
 * Requires PHP: 7.4
 * Requires Plugins: devbrothers-admin-panel
 */

if (!defined('ABSPATH')) {
    exit;
}

define('DBNC_VERSION', '1.0.0');
define('DBNC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DBNC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DBNC_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('DBNC_PREFIX', 'dbnc');

class DevBrothers_No_Comments {

    /**
     * @var DevBrothers_No_Comments
     */
    private static $instance = null;

    /**
     * @var DBNC_Comment_Disabler
     */
    public $disabler;

    /**
     * @var DBNC_Comment_Deleter
     */
    public $deleter;

    /**
     * @var DBNC_Settings
     */
    public $settings;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->maybe_migrate_from_legacy();
        $this->init_hooks();
        $this->init_components();
    }

    private function load_dependencies() {
        require_once DBNC_PLUGIN_DIR . 'includes/class-comment-disabler.php';
        require_once DBNC_PLUGIN_DIR . 'includes/class-comment-deleter.php';
        require_once DBNC_PLUGIN_DIR . 'includes/class-settings.php';
    }

    private function init_hooks() {
        add_action('admin_init', [$this, 'redirect_legacy_admin_page']);
        add_action('devbrothers_ready', [$this, 'register_in_devbrothers']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Перенос настроек со старого плагина devbrothers-disable-comments (dbdc_settings).
     */
    public function maybe_migrate_from_legacy() {
        if (get_option('dbnc_settings') !== false) {
            return;
        }

        $legacy = get_option('dbdc_settings', false);
        if (is_array($legacy)) {
            update_option('dbnc_settings', $legacy);
        }
    }

    /**
     * Редирект со старого пункта меню DevBrothers (id: disable-comments).
     */
    public function redirect_legacy_admin_page() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin page slug for legacy menu redirect.
        if (!isset($_GET['page'])) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin page slug for legacy menu redirect.
        $page = sanitize_text_field(wp_unslash($_GET['page']));
        if ($page === 'devbrothers-plugin-disable-comments') {
            wp_safe_redirect(admin_url('admin.php?page=devbrothers-plugin-no-comments'));
            exit;
        }
    }

    private function init_components() {
        $this->disabler = new DBNC_Comment_Disabler();
        $this->deleter  = new DBNC_Comment_Deleter();
        $this->settings = new DBNC_Settings($this->deleter);

        add_action('wp_ajax_dbnc_delete_comments', [$this->deleter, 'ajax_delete_comments']);
    }

    public function register_in_devbrothers() {
        if (!function_exists('devbrothers_register_plugin')) {
            return;
        }

        devbrothers_register_plugin([
            'id'          => 'no-comments',
            'name'        => __('No Comments', 'devbrothers-no-comments'),
            'name_ru'     => __('Отключение комментариев', 'devbrothers-no-comments'),
            'description' => __('Полное отключение комментариев на сайте', 'devbrothers-no-comments'),
            'version'     => DBNC_VERSION,
            'icon'        => 'dashicons-admin-comments',
            'settings_callback' => [$this->settings, 'render_settings_page'],
            'categories'  => [
                [
                    'id'   => 'general',
                    'name' => __('Основные настройки', 'devbrothers-no-comments'),
                    'icon' => 'dashicons-admin-generic',
                ],
                [
                    'id'   => 'delete',
                    'name' => __('Удаление комментариев', 'devbrothers-no-comments'),
                    'icon' => 'dashicons-trash',
                ],
                [
                    'id'   => 'about',
                    'name' => __('О плагине', 'devbrothers-no-comments'),
                    'icon' => 'dashicons-info',
                ],
            ],
        ]);
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'devbrothers') === false) {
            return;
        }

        wp_enqueue_style(
            'dbnc-admin',
            DBNC_PLUGIN_URL . 'assets/css/admin.css',
            ['devbrothers-admin'],
            DBNC_VERSION
        );

        wp_enqueue_script(
            'dbnc-admin',
            DBNC_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            DBNC_VERSION,
            true
        );

        wp_localize_script('dbnc-admin', 'dbncData', [
            'ajax_url'            => admin_url('admin-ajax.php'),
            'nonce'               => wp_create_nonce('dbnc_delete_nonce'),
            'deleting_text'       => __('Удаление...', 'devbrothers-no-comments'),
            'completed_text'      => __('Завершено!', 'devbrothers-no-comments'),
            'error_text'          => __('Ошибка!', 'devbrothers-no-comments'),
            'confirm_text'        => __('Вы уверены? Это действие необратимо!', 'devbrothers-no-comments'),
            'double_confirm_text' => __("ВНИМАНИЕ: Все комментарии будут удалены безвозвратно!\n\nЭто действие нельзя отменить.\nВы действительно хотите продолжить?", 'devbrothers-no-comments'),
            'ajax_error_text'     => __('Ошибка AJAX: {error}', 'devbrothers-no-comments'),
            'unknown_error_text'  => __('Произошла неизвестная ошибка', 'devbrothers-no-comments'),
            'comments_deleted_text' => __('комментариев удалено', 'devbrothers-no-comments'),
            'comment_deleted_text'  => __('комментарий удален', 'devbrothers-no-comments'),
            'page_reload_text'    => __('Страница будет обновлена через несколько секунд...', 'devbrothers-no-comments'),
            'delete_button_text'  => __('Удалить все комментарии', 'devbrothers-no-comments'),
            'leave_page_warning'  => __('Удаление комментариев еще не завершено. Вы уверены, что хотите покинуть страницу?', 'devbrothers-no-comments'),
        ]);
    }
}

function dbnc_plugin() {
    return DevBrothers_No_Comments::get_instance();
}

add_action('plugins_loaded', 'dbnc_plugin', 10);
