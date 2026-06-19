<?php
/**
 * Класс для удаления комментариев
 *
 * @package DevBrothers_No_Comments
 */

if (!defined('ABSPATH')) {
    exit;
}

class DBNC_Comment_Deleter {

    /**
     * @return int
     */
    public function get_comments_count() {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->comments");

        return (int) $count;
    }

    /**
     * @return array
     */
    public function delete_all_comments() {
        global $wpdb;

        if (!current_user_can('manage_options')) {
            return [
                'success' => false,
                'message' => __('You do not have permission to perform this action.', 'devbrothers-no-comments'),
            ];
        }

        $count_before = $this->get_comments_count();

        if ($count_before === 0) {
            return [
                'success' => true,
                'message' => __('No comments to delete.', 'devbrothers-no-comments'),
                'deleted' => 0,
            ];
        }

        // Запоминаем посты с комментариями, чтобы точечно сбросить их кеш после удаления.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $affected_post_ids = $wpdb->get_col("SELECT DISTINCT comment_post_ID FROM $wpdb->comments");

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->query("DELETE FROM $wpdb->commentmeta");

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->query("DELETE FROM $wpdb->comments");

        $this->update_post_comment_counts($affected_post_ids);

        return [
            'success' => true,
            'message' => sprintf(
                /* translators: %d: number of deleted comments */
                __('Successfully deleted %d comment(s).', 'devbrothers-no-comments'),
                $count_before
            ),
            'deleted' => $count_before,
        ];
    }

    /**
     * @param int[] $affected_post_ids
     */
    private function update_post_comment_counts($affected_post_ids = []) {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->query("UPDATE $wpdb->posts SET comment_count = 0 WHERE comment_count <> 0");

        // Инвалидируем кеши комментариев и затронутых постов вместо полного wp_cache_flush().
        if (function_exists('wp_cache_set_comments_last_changed')) {
            wp_cache_set_comments_last_changed();
        }

        foreach ((array) $affected_post_ids as $post_id) {
            clean_post_cache((int) $post_id);
        }
    }

    public function ajax_delete_comments() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'dbnc_delete_nonce')) {
            wp_send_json_error([
                'message' => __('Security check failed.', 'devbrothers-no-comments'),
            ]);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('You do not have permission to perform this action.', 'devbrothers-no-comments'),
            ]);
        }

        $result = $this->delete_all_comments();

        if ($result['success']) {
            wp_send_json_success([
                'message' => $result['message'],
                'deleted' => $result['deleted'],
            ]);
        } else {
            wp_send_json_error([
                'message' => $result['message'],
            ]);
        }
    }
}
