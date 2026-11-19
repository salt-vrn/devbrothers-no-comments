<?php
/**
 * Класс для отключения комментариев
 *
 * @package DevBrothers_No_Comments
 */

if (!defined('ABSPATH')) {
    exit;
}

class DBNC_Comment_Disabler {

    public function __construct() {
        $this->init_filters();
    }

    /**
     * @return bool
     */
    public function is_disabled() {
        $settings = get_option('dbnc_settings', []);
        return !empty($settings['disable_comments']);
    }

    /**
     * Инициализация фильтров для отключения комментариев
     */
    public function init_filters() {
        if (!$this->is_disabled()) {
            return;
        }

        add_action('wp_loaded', [$this, 'remove_post_type_support'], 999);

        // PHP_INT_MAX чтобы ни один плагин не смог переопределить
        add_filter('comments_open', '__return_false', PHP_INT_MAX, 2);
        add_filter('pings_open', '__return_false', PHP_INT_MAX, 2);
        add_filter('comments_array', '__return_empty_array', 20, 2);
        add_filter('get_comments_number', '__return_zero', 20, 2);

        // Жёсткий стоп: блокировка на уровне wp_new_comment() перед вставкой в БД
        add_filter('preprocess_comment', [$this, 'block_comment_insert']);

        add_action('template_redirect', [$this, 'remove_comment_template'], 9);
        add_action('wp_enqueue_scripts', [$this, 'deregister_comment_reply'], 1);

        add_filter('feed_links_show_comments_feed', '__return_false');
        add_action('template_redirect', [$this, 'block_comment_feeds'], 9);

        add_filter('wp_headers', [$this, 'remove_pingback_header']);

        add_filter('rest_endpoints', [$this, 'disable_rest_comments']);
        add_filter('rest_pre_insert_comment', [$this, 'block_rest_comment_insert'], 10, 2);

        add_filter('xmlrpc_methods', [$this, 'disable_xmlrpc_comments']);

        // Виджет "Свежие комментарии"
        add_action('widgets_init', [$this, 'disable_recent_comments_widget']);

        // Убрать <link> на фид комментариев из <head>
        add_action('template_redirect', [$this, 'remove_feed_links_extra'], 9);

        // Admin Bar: убираем на фронтенде и в админке отдельно
        add_action('template_redirect', [$this, 'filter_admin_bar']);
        add_action('admin_init', [$this, 'filter_admin_bar']);

        if (is_admin()) {
            add_action('admin_menu', [$this, 'remove_comment_menu'], 999);
            add_action('admin_menu', [$this, 'block_comment_admin_pages'], 999);
            add_action('wp_dashboard_setup', [$this, 'remove_dashboard_widgets']);
            add_action('admin_print_styles-index.php', [$this, 'dashboard_hide_comments_css']);
            add_action('admin_print_styles-profile.php', [$this, 'dashboard_hide_comments_css']);
            add_filter('pre_option_default_pingback_flag', '__return_zero');
        }

        add_action('enqueue_block_editor_assets', [$this, 'disable_gutenberg_comments']);
    }

    public function remove_post_type_support() {
        $post_types = get_post_types(['public' => true], 'names');

        foreach ($post_types as $post_type) {
            if (post_type_supports($post_type, 'comments')) {
                remove_post_type_support($post_type, 'comments');
            }
            if (post_type_supports($post_type, 'trackbacks')) {
                remove_post_type_support($post_type, 'trackbacks');
            }
        }
    }

    public function remove_comment_template() {
        if (is_singular()) {
            add_filter('comments_template', [$this, 'empty_comments_template'], 20);
        }
    }

    /**
     * @return string
     */
    public function empty_comments_template() {
        return DBNC_PLUGIN_DIR . 'includes/views/empty-comments.php';
    }

    public function deregister_comment_reply() {
        wp_deregister_script('comment-reply');
    }

    public function block_comment_feeds() {
        if (is_comment_feed()) {
            wp_die(
                esc_html__('Comments are disabled.', 'devbrothers-no-comments'),
                '',
                ['response' => 403]
            );
        }
    }

    /**
     * @param array $headers
     * @return array
     */
    public function remove_pingback_header($headers) {
        unset($headers['X-Pingback']);
        return $headers;
    }

    /**
     * @param array $endpoints
     * @return array
     */
    public function disable_rest_comments($endpoints) {
        unset($endpoints['/wp/v2/comments'], $endpoints['/wp/v2/comments/(?P<id>[\d]+)']);
        return $endpoints;
    }

    /**
     * @param mixed           $prepared_comment
     * @param WP_REST_Request $request
     * @return WP_Error
     */
    public function block_rest_comment_insert($prepared_comment, $request) {
        return new WP_Error(
            'rest_comment_disabled',
            __('Comments are disabled.', 'devbrothers-no-comments'),
            ['status' => 403]
        );
    }

    /**
     * @param array $methods
     * @return array
     */
    public function disable_xmlrpc_comments($methods) {
        unset($methods['wp.newComment']);
        return $methods;
    }

    /**
     * Жёсткий стоп перед вставкой комментария через wp_new_comment().
     * Блокирует программные вставки из любых плагинов/тем.
     *
     * @param array $commentdata
     * @return array
     */
    public function block_comment_insert($commentdata) {
        if (defined('DOING_CRON') && DOING_CRON) {
            return $commentdata;
        }

        wp_die(
            esc_html__('Comments are disabled.', 'devbrothers-no-comments'),
            esc_html__('Comments Disabled', 'devbrothers-no-comments'),
            ['response' => 403, 'back_link' => true]
        );

        return $commentdata; // @codeCoverageIgnore
    }

    /**
     * Снять виджет "Свежие комментарии" и его стили
     */
    public function disable_recent_comments_widget() {
        unregister_widget('WP_Widget_Recent_Comments');
        add_filter('show_recent_comments_widget_style', '__return_false');
    }

    /**
     * Убрать <link> на фид комментариев из <head> на singular-страницах
     */
    public function remove_feed_links_extra() {
        if (is_singular()) {
            remove_action('wp_head', 'feed_links_extra', 3);
        }
    }

    /**
     * Убрать комментарии из Admin Bar на фронтенде и в админке
     */
    public function filter_admin_bar() {
        if (is_admin_bar_showing()) {
            remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
        }
    }

    public function remove_comment_menu() {
        remove_menu_page('edit-comments.php');
        remove_submenu_page('options-general.php', 'options-discussion.php');
    }

    /**
     * Заблокировать прямой доступ к админ-страницам комментариев и обсуждения
     */
    public function block_comment_admin_pages() {
        global $pagenow;

        $blocked = ['edit-comments.php', 'comment.php', 'options-discussion.php'];

        if (in_array($pagenow, $blocked, true)) {
            wp_die(
                esc_html__('Comments are disabled.', 'devbrothers-no-comments'),
                '',
                ['response' => 403, 'back_link' => true]
            );
        }
    }

    public function remove_dashboard_widgets() {
        remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
    }

    /**
     * CSS для скрытия элементов комментариев на дашборде и профиле
     */
    public function dashboard_hide_comments_css() {
        wp_register_style('dbnc-dashboard-hide', false, [], DBNC_VERSION);
        wp_enqueue_style('dbnc-dashboard-hide');
        wp_add_inline_style(
            'dbnc-dashboard-hide',
            '#dashboard_right_now .comment-count,
            #dashboard_right_now .comment-mod-count,
            #latest-comments,
            #welcome-panel .welcome-comments,
            .user-comment-shortcuts-wrap {
                display: none !important;
            }'
        );
    }

    public function disable_gutenberg_comments() {
        $blocks = [
            'core/latest-comments',
            'core/post-comments-form',
            'core/comments',
            'core/comment-author-name',
            'core/comment-content',
            'core/comment-date',
            'core/comment-edit-link',
            'core/comment-reply-link',
            'core/comment-template',
            'core/comments-title',
            'core/comments-pagination',
            'core/comments-pagination-next',
            'core/comments-pagination-numbers',
            'core/comments-pagination-previous',
        ];

        $js_lines = [];
        foreach ($blocks as $block) {
            $js_lines[] = "if(wp.blocks.getBlockType('" . $block . "')){wp.blocks.unregisterBlockType('" . $block . "');}";
        }

        wp_add_inline_script(
            'wp-blocks',
            'wp.domReady(function(){' . implode('', $js_lines) . '});'
        );
    }
}
