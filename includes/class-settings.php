<?php
/**
 * Класс настроек плагина
 *
 * @package DevBrothers_No_Comments
 */

if (!defined('ABSPATH')) {
    exit;
}

class DBNC_Settings {

    /**
     * @var string
     */
    private $option_name = 'dbnc_settings';

    /**
     * @var DBNC_Comment_Deleter
     */
    private $deleter;

    /**
     * @param DBNC_Comment_Deleter $deleter
     */
    public function __construct($deleter) {
        $this->deleter = $deleter;
    }

    /**
     * @return array
     */
    public function get_settings() {
        $defaults = [
            'disable_comments' => 0,
        ];

        $settings = get_option($this->option_name, $defaults);
        return wp_parse_args($settings, $defaults);
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Недостаточно прав', 'devbrothers-no-comments'));
        }

        $settings_saved = false;

        if (isset($_POST['dbnc_save_settings']) &&
            isset($_POST['_wpnonce']) &&
            wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'dbnc_settings_nonce')) {
            $this->save_settings_internal();
            $settings_saved = true;
        }

        $settings       = $this->get_settings();
        $comments_count = $this->deleter->get_comments_count();

        if ($settings_saved) {
            echo '<div class="notice notice-success"><p>' .
                 esc_html__('Настройки сохранены.', 'devbrothers-no-comments') .
                 '</p></div>';
        }

        ?>
        <form method="post" action="">
            <?php wp_nonce_field('dbnc_settings_nonce'); ?>

            <!-- Категория: Основные настройки -->
            <div id="general" class="devbrothers-settings-category">
                <h2>
                    <span class="dashicons dashicons-admin-generic"></span>
                    <?php esc_html_e('Основные настройки', 'devbrothers-no-comments'); ?>
                </h2>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Отключение комментариев', 'devbrothers-no-comments'); ?>
                        </th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text">
                                    <span><?php esc_html_e('Отключение комментариев', 'devbrothers-no-comments'); ?></span>
                                </legend>

                                <label>
                                    <input type="checkbox"
                                           name="dbnc_disable_comments"
                                           value="1"
                                           <?php checked($settings['disable_comments'], 1); ?> />
                                    <strong><?php esc_html_e('Отключить комментарии глобально на всем сайте', 'devbrothers-no-comments'); ?></strong>
                                </label>

                                <p class="description">
                                    <?php esc_html_e('При включении этой опции комментарии будут полностью отключены на всем сайте:', 'devbrothers-no-comments'); ?>
                                </p>
                                <ul class="dbnc-feature-list">
                                    <li><?php esc_html_e('Форма комментариев будет скрыта', 'devbrothers-no-comments'); ?></li>
                                    <li><?php esc_html_e('Комментарии будут отключены через REST API', 'devbrothers-no-comments'); ?></li>
                                    <li><?php esc_html_e('Комментарии будут отключены через XML-RPC', 'devbrothers-no-comments'); ?></li>
                                    <li><?php esc_html_e('Пункты меню комментариев будут скрыты в админке', 'devbrothers-no-comments'); ?></li>
                                </ul>
                            </fieldset>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Категория: Удаление комментариев -->
            <div id="delete" class="devbrothers-settings-category">
                <h2>
                    <span class="dashicons dashicons-trash"></span>
                    <?php esc_html_e('Удаление комментариев', 'devbrothers-no-comments'); ?>
                </h2>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Удалить все комментарии', 'devbrothers-no-comments'); ?>
                        </th>
                        <td>
                            <p>
                                <?php
                                printf(
                                    /* translators: %d: number of comments */
                                    esc_html__('На сайте найдено комментариев: %d', 'devbrothers-no-comments'),
                                    absint($comments_count)
                                );
                                ?>
                            </p>

                            <?php if (!$this->deleter->can_delete_comments()) : ?>
                                <p class="description"><?php esc_html_e('Для удаления нужно право модерации комментариев.', 'devbrothers-no-comments'); ?></p>
                            <?php elseif ($comments_count > 0) : ?>
                                <button type="button"
                                        id="dbnc-delete-button"
                                        class="button dbnc-button-danger">
                                    <span class="dashicons dashicons-trash"></span>
                                    <?php esc_html_e('Удалить все комментарии', 'devbrothers-no-comments'); ?>
                                </button>

                                <p class="description dbnc-warning">
                                    <span class="dashicons dashicons-warning"></span>
                                    <?php esc_html_e('ВНИМАНИЕ: Это действие необратимо! Все комментарии и их метаданные будут удалены из базы данных.', 'devbrothers-no-comments'); ?>
                                </p>
                            <?php else : ?>
                                <p class="description">
                                    <?php esc_html_e('На сайте нет комментариев для удаления.', 'devbrothers-no-comments'); ?>
                                </p>
                            <?php endif; ?>

                            <div id="dbnc-delete-progress" class="dbnc-progress-wrapper">
                                <div class="dbnc-progress-inner">
                                    <p><strong><?php esc_html_e('Идет удаление...', 'devbrothers-no-comments'); ?></strong></p>
                                </div>
                            </div>

                            <div id="dbnc-delete-result" class="dbnc-result-wrapper"></div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Категория: О плагине -->
            <div id="about" class="devbrothers-settings-category">
                <h2>
                    <span class="dashicons dashicons-info"></span>
                    <?php esc_html_e('О плагине', 'devbrothers-no-comments'); ?>
                </h2>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Версия', 'devbrothers-no-comments'); ?>
                        </th>
                        <td>
                            <p><?php echo esc_html(DBNC_VERSION); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Информация', 'devbrothers-no-comments'); ?>
                        </th>
                        <td>
                            <p><?php esc_html_e('Плагин позволяет полностью отключить комментарии на сайте WordPress и удалить все существующие комментарии одной кнопкой.', 'devbrothers-no-comments'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Кнопка сохранения -->
            <p class="submit">
                <button type="submit" name="dbnc_save_settings" class="button button-primary">
                    <span class="dashicons dashicons-yes"></span>
                    <?php esc_html_e('Сохранить настройки', 'devbrothers-no-comments'); ?>
                </button>
            </p>
        </form>
        <?php
    }

    private function save_settings_internal() {
        if (!isset($_POST['_wpnonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'dbnc_settings_nonce')) {
            return;
        }

        $settings = [
            'disable_comments' => isset($_POST['dbnc_disable_comments']) ? 1 : 0,
        ];

        update_option($this->option_name, $settings);
    }
}
