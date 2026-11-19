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

    public function can_delete_comments() {
        return current_user_can('manage_options') && current_user_can('moderate_comments');
    }

    /**
     * @return array
     */
    public function delete_all_comments() {
        global $wpdb;

        if (!$this->can_delete_comments()) {
            return [
                'success' => false,
                'message' => __('You do not have permission to perform this action.', 'devbrothers-no-comments'),
            ];
        }

        $high_water = $wpdb->get_var("SELECT MAX(comment_ID) FROM $wpdb->comments");
        if ($wpdb->last_error !== '') {
            return ['success' => false, 'message' => __('Could not read comments.', 'devbrothers-no-comments')];
        }
        if (!$high_water) {
            return [
                'success' => true,
                'message' => __('No comments to delete.', 'devbrothers-no-comments'),
                'deleted' => 0,
            ];
        }

        $deleted = 0;
        $failed = 0;
        $cursor = 0;
        $was_deferred = wp_defer_comment_counting();
        wp_defer_comment_counting(true);
        try {
            do {
                $ids = $wpdb->get_col($wpdb->prepare(
                    "SELECT comment_ID FROM $wpdb->comments WHERE comment_ID > %d AND comment_ID <= %d ORDER BY comment_ID LIMIT 100",
                    $cursor,
                    $high_water
                ));
                if ($wpdb->last_error !== '') {
                    return [
                        'success' => false,
                        'message' => sprintf(__('Could not finish deleting comments. Deleted so far: %d.', 'devbrothers-no-comments'), $deleted),
                    ];
                }
                foreach ($ids as $id) {
                    $cursor = (int) $id;
                    if (current_user_can('edit_comment', $cursor) && wp_delete_comment($cursor, true)) {
                        $deleted++;
                    } else {
                        $failed++;
                    }
                }
            } while (count($ids) === 100);
        } finally {
            wp_defer_comment_counting($was_deferred);
        }

        if ($failed > 0) {
            return [
                'success' => false,
                'message' => sprintf(
                    __('Deleted %1$d comment(s); %2$d could not be deleted.', 'devbrothers-no-comments'),
                    $deleted,
                    $failed
                ),
            ];
        }

        return [
            'success' => true,
            'message' => sprintf(
                /* translators: %d: number of deleted comments */
                __('Successfully deleted %d comment(s).', 'devbrothers-no-comments'),
                $deleted
            ),
            'deleted' => $deleted,
        ];
    }

    public function ajax_delete_comments() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'dbnc_delete_nonce')) {
            wp_send_json_error([
                'message' => __('Security check failed.', 'devbrothers-no-comments'),
            ]);
        }

        if (!$this->can_delete_comments()) {
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
