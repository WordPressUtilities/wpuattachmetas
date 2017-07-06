<?php

/*
Plugin Name: WPU Attachments Metas
Plugin URI: https://github.com/WordPressUtilities/wpuattachmetas
Description: Metadatas for Attachments
Version: 0.6.0
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUAttachMetas {

    private $pluginkey = 'wpuattach_';
    private $pluginversion = '0.6.0';

    private $metas = array();

    public function __construct() {
        add_action('plugins_loaded', array(&$this, 'plugins_loaded'));
        /* Display fields */
        add_filter('attachment_fields_to_edit', array(&$this, 'display_custom_fields'), 10, 2);
        /* Save values */
        add_action('edit_attachment', array(&$this, 'save_custom_fields'), 20);
        /* Load custom CSS */
        add_action('admin_enqueue_scripts', array(&$this, 'admin_css'), 11);
    }

    public function plugins_loaded() {
        /* Load translation */
        load_plugin_textdomain('wpuattachmetas', false, dirname(plugin_basename(__FILE__)) . '/lang/');

        include 'inc/WPUBaseAdminPage/WPUBaseAdminPage.php';
        $admin_pages = array(
            'search' => array(
                'section' => 'upload.php',
                'name' => __('Search', 'wpuattachmetas'),
                'function_content' => array(&$this,
                    'page_content__main'
                ),
                'function_action' => array(&$this,
                    'page_action__main'
                )
            )
        );

        $pages_options = array(
            'id' => 'wpuattachmetas',
            'level' => 'upload_files',
            'basename' => plugin_basename(__FILE__)
        );

        // Init admin page
        $this->adminpages = new \wpuattachmetas\WPUBaseAdminPage();
        $this->adminpages->init($pages_options, $admin_pages);

    }

    public function page_content__main() {
        $metas = apply_filters('wpuattachmetas_metas', array());
        $search_values = array();
        $transient_results = 'wpuattachmetas_search_' . get_current_user_id();
        $search = get_transient($transient_results);
        if ($search !== false) {
            $search_values = $search['values'];
            delete_transient($transient_results);
        }

        echo '<table class="form-table"><tbody>';
        foreach ($metas as $key => $meta) {
            if (!isset($meta['search_enabled']) || !$meta['search_enabled']) {
                continue;
            }
            $values = $this->get_values_for_meta($key);
            echo '<tr>';
            echo '<th scope="row"><label for="wpuattachmetas_key_' . $key . '">' . esc_html($meta['label']) . ' :</label></th>';
            echo '<td>';
            echo '<select style="max-width:300px" name="wpuattachmetas_key_' . $key . '" id="wpuattachmetas_key_' . $key . '">';
            echo '<option value="">' . __('Select a value', 'wpuattachmetas') . '</option>';
            foreach ($values as $value) {
                echo '<option ' . (isset($search_values[$key]) && $search_values[$key] == $value ? 'selected' : '') . ' value="' . esc_attr($value) . '">' . esc_html($value) . '</option>';
            }
            echo '</select><br />';
            echo __('Or', 'wpuattachmetas') . ' <input style="max-width:300px" name="wpuattachmetas_val_' . $key . '" id="wpuattachmetas_val_' . $key . '" value="' . (isset($search_values[$key]) ? esc_attr($search_values[$key]) : '') . '" />';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        submit_button(__('Search', 'wpuattachmetas'), 'primary', 'wpuattachmetas_search');

        if ($search !== false) {
            if (empty($search['results'])) {
                echo '<p>' . __('No results for this query', 'wpuattachmetas') . '</p>';
            } else {
                echo '<div>';
                foreach ($search['results'] as $id) {
                    $thumb_url = wp_get_attachment_image_src($id, 'thumbnail');
                    if (!is_array($thumb_url)) {
                        continue;
                    }
                    echo '<a href="' . get_edit_post_link($id) . '">';
                    echo '<img height="100" width="100" style="object-fit:cover" src="' . $thumb_url[0] . '" alt="" />';
                    echo '</a>';
                }
                echo '</div>';
            }
        }

    }

    public function page_action__main() {
        global $wpdb;
        $meta_query = array('relation' => 'AND');
        $metas = apply_filters('wpuattachmetas_metas', array());
        $search = array('results' => array(), 'values' => array());
        foreach ($metas as $key => $meta) {
            if (!isset($meta['search_enabled']) || !$meta['search_enabled']) {
                continue;
            }
            if (!isset($_POST['wpuattachmetas_key_' . $key]) || empty($_POST['wpuattachmetas_key_' . $key])) {
                continue;
            }
            $value = $_POST['wpuattachmetas_key_' . $key];
            if (isset($_POST['wpuattachmetas_val_' . $key]) && !empty($_POST['wpuattachmetas_val_' . $key])) {
                $value = $_POST['wpuattachmetas_val_' . $key];
            }
            $meta_query[] = array(
                'key' => $key,
                'value' => trim($value),
                'compare' => 'LIKE'
            );
            $search['values'][$key] = $value;
        }

        if (count($meta_query) > 1) {
            $search['results'] = get_posts(array(
                'posts_per_page' => -1,
                'post_type' => 'attachment',
                'meta_query' => $meta_query,
                'fields' => 'ids'
            ));
        }

        set_transient('wpuattachmetas_search_' . get_current_user_id(), $search, 60);
    }

    public function admin_css() {
        $hide_default_fields = apply_filters('wpuattachmetas_hide_default_fields', false);
        if ($hide_default_fields) {
            wp_enqueue_style('wpuattachmetas_hide_default_fields', plugins_url('assets/css/hide-default-fields.css', __FILE__), array(), $this->pluginversion);
        }
        wp_enqueue_style('wpuattachmetas_style', plugins_url('assets/css/style.css', __FILE__), array(), $this->pluginversion);
        wp_enqueue_script('wpuattachmetas_script_image', plugins_url('assets/js/image.js', __FILE__), array(), $this->pluginversion);

        $screen = false;
        if (function_exists('get_current_screen')) {
            $screen = get_current_screen();
        }
        if (is_object($screen) && $screen->base == 'post' && $screen->id == 'attachment') {
            wp_enqueue_media();
        }

    }

    /**
     * Load metadatas and ensure content is correct
     */
    public function load_metas($post) {
        $this->metas = array();
        if (!is_object($post)) {
            return;
        }

        $screen = false;
        if (function_exists('get_current_screen')) {
            $screen = get_current_screen();
        }
        $is_rich_attachment_page = (is_object($screen) && $screen->base == 'post' && $screen->id == 'attachment');

        $metas = apply_filters('wpuattachmetas_metas', array());
        foreach ($metas as $key => $meta) {
            if (!isset($meta['input'])) {
                $meta['input'] = 'text';
            }
            if (!isset($meta['placeholder'])) {
                $meta['placeholder'] = '';
            }
            $meta['original_input'] = $meta['input'];
            $meta['key'] = $this->pluginkey . $key;
            $field_name = 'attachments[' . $post->ID . '][' . $meta['key'] . ']';
            $field_id = 'attachments-' . $post->ID . '-' . $meta['key'];
            $field_idnamehtml = 'id="' . $field_id . '" name="' . $field_name . '" ';
            $_value = get_post_meta($post->ID, $key, 1);
            $meta['value'] = $_value ? $_value : '';

            if (!isset($meta['label'])) {
                $meta['label'] = ucfirst($key);
            }

            if (!isset($meta['select_values'])) {
                $meta['select_values'] = array(__('No', 'wpuattachmetas'), __('Yes', 'wpuattachmetas'));
            }

            $input = $meta['input'];
            $meta['input'] = 'html';

            switch ($input) {
            case 'select':
                $meta['html'] = '<select ' . $field_idnamehtml . '><option value="" disabled selected style="display:none">' . __('Select', 'wpuattachmetas') . '</option>';
                foreach ($meta['select_values'] as $skey => $var) {
                    $meta['html'] .= '<option ' . ($meta['value'] == $skey ? 'selected' : '') . ' value="' . $skey . '">' . $var . '</option>';
                }
                $meta['html'] .= '</select>';
                break;
            case 'blank':
                $meta['label'] = '<span class="wpuattachmetas-title" style="">&nbsp;<span>' . $meta['label'] . '</span></span>';
                $meta['html'] = '&nbsp;';
                break;
            case 'attachment':
                $_preview_size = $is_rich_attachment_page ? 27 : 20;
                $_att_id = false;
                $_img_url = 'http://placehold.it/' . $_preview_size . 'x' . $_preview_size;
                if (is_numeric($meta['value'])) {
                    $src = wp_get_attachment_image_src($meta['value'], 'thumbnail');
                    if (is_array($src)) {
                        $_att_id = $meta['value'];
                        $_img_url = $src[0];
                    }
                }
                $meta['html'] = '<div style="line-height:' . $_preview_size . 'px">';
                $meta['html'] .= '<img style="width:' . $_preview_size . 'px;height:' . $_preview_size . 'px;object-fit:cover;vertical-align:middle" src="' . $_img_url . '"" alt="" /> ';
                $_label = $_att_id !== false ? __('Change image', 'wpuattachmetas') : __('Add an image', 'wpuattachmetas');
                if ($is_rich_attachment_page) {
                    $meta['html'] .= '<button class="button primary wpuattachmetas-image-link" data-attid="' . ($_att_id !== false ? $_att_id : 0) . '" data-altlabel="' . esc_attr(__('Change image', 'wpuattachmetas')) . '" type="button">' . esc_html($_label) . '</button>';
                } else {
                    $meta['html'] .= '<a target="_blank" style="display: inline-block;vertical-align:middle" href="' . get_edit_post_link($post->ID) . '">' . esc_html($_label) . '</a>';
                }
                $meta['html'] .= '<input type="hidden" ' . $field_idnamehtml . ' value="' . esc_attr($meta['value']) . '">';
                $meta['html'] .= '</div>';
                break;
            case 'editor':
                if ($is_rich_attachment_page) {
                    ob_start();
                    wp_editor($meta['value'], $field_id, array(
                        'media_buttons' => false,
                        'teeny' => true,
                        'textarea_name' => $field_name,
                        'textarea_rows' => 2
                    ));
                    $screen = get_current_screen();
                    $meta['html'] = ob_get_clean();
                } else {
                    $meta['html'] = '<textarea ' . $field_idnamehtml . ' >' . esc_html($meta['value']) . '</textarea>';
                }
                break;
            case 'number':
            case 'date':
            case 'text':
            case 'email':
            case 'url':
                $meta['html'] = '<input placeholder="' . $meta['placeholder'] . '" type="' . $input . '" class="text" ' . $field_idnamehtml . ' value="' . esc_attr($meta['value']) . '">';
                break;
            }

            $this->metas[$key] = $meta;
        }
    }

    /**
     * Display custom fields
     */
    public function display_custom_fields($form_fields, $post) {
        $this->load_metas($post);
        foreach ($this->metas as $key => $meta) {
            $form_fields[$meta['key']] = $meta;
        }
        return $form_fields;
    }

    /**
     * Save custom fields when attachment
     */
    public function save_custom_fields($attachment_id) {
        $tmp_post = get_post($attachment_id);
        $this->load_metas($tmp_post);
        if (!isset($_REQUEST['attachments'])) {
            return;
        }
        $_req = $_REQUEST['attachments'][$attachment_id];
        foreach ($this->metas as $key => $meta) {
            if (!isset($_req[$meta['key']])) {
                continue;
            }
            $old_value = $meta['value'];
            $new_value = $_req[$meta['key']];

            switch ($meta['original_input']) {
            case 'select':
                if (!array_key_exists($new_value, $meta['select_values'])) {
                    $new_value = $old_value;
                }
                break;
            case 'email':
                if (filter_var($new_value, FILTER_VALIDATE_EMAIL) === false) {
                    $new_value = $old_value;
                }
                break;
            case 'url':
                if (filter_var($new_value, FILTER_VALIDATE_URL) === false) {
                    $new_value = $old_value;
                }
                break;
            case 'attachment':
            case 'number':
                if (!is_numeric($new_value)) {
                    $new_value = $old_value;
                }
                break;
            case 'editor':
                // Keep the current value
                break;
            default:
                $new_value = esc_html($new_value);
            }

            update_post_meta($attachment_id, $key, $new_value);
            $this->get_values_for_meta($key, true);
        }
    }

    public function get_values_for_meta($key, $refresh = false) {

        $cache_id = 'wpuattachmetas_' . $key . '_cached_values';

        // GET CACHED VALUE
        $values = wp_cache_get($cache_id);
        if (!is_array($values) !== false || $refresh) {

            // COMPUTE RESULT
            global $wpdb;
            $wpdb_values = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key='%s' AND meta_value <>'' ORDER BY meta_value ASC", $key));

            $values = array();
            foreach ($wpdb_values as $val) {
                $values[] = $val->meta_value;
            }

            // CACHE RESULT
            wp_cache_set($cache_id, $values, 'wpuattachmetas', 0);
        }

        return $values;
    }
}

$WPUAttachMetas = new WPUAttachMetas();
