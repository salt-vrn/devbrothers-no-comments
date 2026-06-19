/**
 * DevBrothers No Comments Admin Scripts
 *
 * @package DevBrothers_No_Comments
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        var deleteInProgress = false;

        $('#dbnc-delete-button').on('click', function(e) {
            e.preventDefault();

            if (deleteInProgress) {
                return;
            }

            var confirmText = dbncData.confirm_text || 'Are you sure? This action is irreversible!';
            if (!confirm(confirmText)) {
                return;
            }

            var doubleConfirm = dbncData.double_confirm_text || 'WARNING: All comments will be permanently deleted!\n\nThis action cannot be undone.\nDo you really want to continue?';
            if (!confirm(doubleConfirm)) {
                return;
            }

            startDelete();
        });

        function startDelete() {
            deleteInProgress = true;

            $('#dbnc-delete-button')
                .prop('disabled', true)
                .html('<span class="dashicons dashicons-update dbnc-spinning"></span> ' +
                      escapeHtml(dbncData.deleting_text || 'Удаление...'));

            $('#dbnc-delete-progress').slideDown();
            $('#dbnc-delete-result').slideUp();

            $.ajax({
                url: dbncData.ajax_url,
                type: 'POST',
                data: {
                    action: 'dbnc_delete_comments',
                    nonce: dbncData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showSuccess(response.data.message, response.data.deleted);
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        showError(response.data.message || dbncData.unknown_error_text || 'An unknown error occurred');
                    }
                },
                error: function(xhr, status, error) {
                    var errorMsg = (dbncData.ajax_error_text || 'AJAX Error: {error}').replace('{error}', error);
                    showError(errorMsg);
                },
                complete: function() {
                    deleteInProgress = false;
                }
            });
        }

        function showSuccess(message, deleted) {
            $('#dbnc-delete-button')
                .prop('disabled', false)
                .html('<span class="dashicons dashicons-yes-alt"></span> ' +
                      escapeHtml(dbncData.completed_text || 'Завершено!'));

            $('#dbnc-delete-progress').slideUp();

            var resultHtml = '<div class="notice notice-success">';
            resultHtml += '<p><strong>' + escapeHtml(message) + '</strong></p>';
            if (deleted !== undefined) {
                var deletedText = deleted === 1
                    ? (dbncData.comment_deleted_text || 'comment deleted')
                    : (dbncData.comments_deleted_text || 'comments deleted');
                resultHtml += '<p>' + String(deleted) + ' ' + escapeHtml(deletedText) + '</p>';
            }
            resultHtml += '<p>' + escapeHtml(dbncData.page_reload_text || 'Page will be refreshed in a few seconds...') + '</p>';
            resultHtml += '</div>';

            $('#dbnc-delete-result').html(resultHtml).slideDown();

            $('html, body').animate({
                scrollTop: $('#dbnc-delete-result').offset().top - 100
            }, 500);
        }

        function showError(message) {
            $('#dbnc-delete-button')
                .prop('disabled', false)
                .html('<span class="dashicons dashicons-trash"></span> ' +
                      escapeHtml(dbncData.delete_button_text || 'Delete all comments'));

            $('#dbnc-delete-progress').slideUp();

            var errorHtml = '<div class="notice notice-error">';
            errorHtml += '<p><strong>' + escapeHtml(dbncData.error_text || 'Error!') + '</strong></p>';
            errorHtml += '<p>' + escapeHtml(message) + '</p>';
            errorHtml += '</div>';

            $('#dbnc-delete-result').html(errorHtml).slideDown();
        }

        function escapeHtml(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        $(window).on('beforeunload', function() {
            if (deleteInProgress) {
                return dbncData.leave_page_warning || 'Comment deletion is not yet complete. Are you sure you want to leave?';
            }
        });

    });

})(jQuery);
