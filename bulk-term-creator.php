<?php
/**
 * Plugin Name: Bulk Term Creator
 * Description: Quickly create multiple terms with the same name but different slugs.
 * Version: 1.1
 * Author: DEJI98
 */

add_action('admin_menu', function () {
    add_management_page('Bulk Term Creator', 'Bulk Term Creator', 'manage_options', 'bulk-term-creator', 'btc_render_page');
});

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

    echo '<div class="wrap"><h1>Bulk Term Creator</h1>';
    echo $message;
    echo '<form method="post">';
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
    </table>';
    submit_button('Create Terms');
    echo '</form></div>';
}
