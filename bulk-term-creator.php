<?php
/**
 * Plugin Name: Bulk Term Creator
 * Description: Quickly create multiple terms with the same name but different slugs.
 * Version: 1.1
 * Author: DEJI98
 */


add_action('admin_menu', 'btc_add_top_level_menu');

function btc_add_top_level_menu() {
    add_menu_page(
        'Bulk Term Creator',       // Page title
        'Bulk Term Creator',       // Menu title
        'manage_options',          // Capability
        'bulk-term-creator',       // Menu slug
        'btc_render_page',         // Callback function
        'dashicons-tag',           // Icon (you can change this)
        6                         // Position (optional)
    );

    add_submenu_page(
        'bulk-term-creator',            // Parent slug (MUST match top-level menu slug)
        'CSV Term Import',              // Page title
        'CSV Import',                   // Menu title (in sidebar)
        'manage_options',              // Capability
        'btc-csv-import',              // Menu slug
        'btc_render_csv_upload_page'   // Callback function to render page
    );    
}


if (is_admin() && isset($_GET['page']) && $_GET['page'] === 'bulk-term-creator') {
    require_once plugin_dir_path(__FILE__) . 'btc-import-handler.php';
}

function btc_render_page() {
    if (!current_user_can('manage_options')) return;

    $message = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('btc_create_terms')) {
        $taxonomy = sanitize_text_field($_POST['taxonomy']);
        $term_name = sanitize_text_field($_POST['term_name']);

        // Get raw input and normalize newlines
        $raw_input = str_replace(["\r\n", "\r"], "\n", $_POST['term_slugs']);
        // Replace newlines with commas, then split by comma
        $slug_list = explode(',', str_replace("\n", ',', $raw_input));
        $slugs = array_filter(array_map('sanitize_title', array_map('trim', $slug_list)));

        foreach ($slugs as $slug) {
            $result = wp_insert_term($term_name, $taxonomy, ['slug' => $slug]);
            if (is_wp_error($result)) {
                $message .= '<div class="notice notice-error"><p>Error with slug <code>' . esc_html($slug) . '</code>: ' . esc_html($result->get_error_message()) . '</p></div>';
            } else {
                $message .= '<div class="notice notice-success"><p>Term <code>' . esc_html($slug) . '</code> created.</p></div>';
            }
        }
    }

    $taxonomies = get_taxonomies(['public' => true], 'objects');

    if (!empty($_FILES['btc_csv_file'])) {
        btc_handle_csv_upload(); // from included file
    }
    

    echo '<div class="wrap"><h1>Bulk Term Creator</h1>';
    echo $message;
    echo '<form method="post" enctype="multipart/form-data">';
    wp_nonce_field('btc_create_terms');
    echo '<table class="form-table">
        <tr>
            <th scope="row"><label for="term_name">Term Name</label></th>
            <td><input name="term_name" type="text" id="term_name" value="" class="regular-text" required></td>
        </tr>
        <tr>
            <th scope="row"><label for="taxonomy">Taxonomy</label></th>
            <td><select name="taxonomy" id="taxonomy">';
    foreach ($taxonomies as $tax) {
        echo '<option value="' . esc_attr($tax->name) . '">' . esc_html($tax->label) . ' (' . esc_html($tax->name) . ')</option>';
    }
    echo '</select></td></tr>
        <tr>
            <th scope="row"><label for="term_slugs">Slugs (comma or newline separated)</label></th>
            <td><textarea name="term_slugs" id="term_slugs" rows="10" class="large-text code" required></textarea>
            <p class="description">Example: <code>slug-one, slug-two, slug-three</code> or one per line.</p></td>
        </tr>
        <tr>
            <th scope="row"><label for="btc_csv_file">Upload CSV (optional)</label></th>
            <td><input type="file" name="btc_csv_file" id="btc_csv_file" accept=".csv">
            <p class="description">CSV must contain one slug per line.</p></td>
        </tr>
    </table>';
    submit_button('Create Terms');
    echo '</form></div>';
}
