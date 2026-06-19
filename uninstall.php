<?php
/**
 * Uninstall DevBrothers No Comments
 *
 * @package DevBrothers_No_Comments
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('dbnc_settings');
delete_option('dbdc_settings');

if (is_multisite()) {
    $dbnc_sites = get_sites(['number' => 0]);

    foreach ($dbnc_sites as $dbnc_site) {
        switch_to_blog($dbnc_site->blog_id);

        delete_option('dbnc_settings');
        delete_option('dbdc_settings');

        restore_current_blog();
    }
}
