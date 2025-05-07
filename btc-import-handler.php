<?php
if (!defined('ABSPATH') || !is_admin()) {
    exit;
}

function btc_handle_csv_upload() {
    if (
        !isset($_FILES['btc_csv_file']) ||
        !file_exists($_FILES['btc_csv_file']['tmp_name']) ||
        !isset($_POST['taxonomy']) ||
        !isset($_POST['term_name']) ||
        !check_admin_referer('btc_create_terms')
    ) {
        return;
    }

    $taxonomy = sanitize_text_field($_POST['taxonomy']);
    $term_name = sanitize_text_field($_POST['term_name']);
    $file = $_FILES['btc_csv_file']['tmp_name'];

    $handle = fopen($file, 'r');
    if (!$handle) {
        echo '<div class="notice notice-error"><p>Could not read the CSV file.</p></div>';
        return;
    }

    $message = '';
    while (($data = fgetcsv($handle)) !== false) {
        $slug = sanitize_title(trim($data[0]));
        if (empty($slug)) continue;

        $result = wp_insert_term($term_name, $taxonomy, ['slug' => $slug]);
        if (is_wp_error($result)) {
            $message .= '<div class="notice notice-error"><p>Error with <code>' . esc_html($slug) . '</code>: ' . esc_html($result->get_error_message()) . '</p></div>';
        } else {
            $message .= '<div class="notice notice-success"><p>Term <code>' . esc_html($slug) . '</code> created.</p></div>';
        }
    }
    fclose($handle);

    echo $message;
}
