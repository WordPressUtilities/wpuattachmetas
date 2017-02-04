<?php

/*
Plugin Name: WPU Attachments Metas
Plugin URI: https://github.com/WordPressUtilities/wpuattachmetas
Description: Metadatas for Attachments
Version: 0.3
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUAttachMetas {

    private $pluginkey = 'wpuattach_';

    private $metas = array();

    public function __construct() {
        add_action('wp_loaded', array(&$this, 'wp_loaded'));
        /* Display fields */
        add_filter('attachment_fields_to_edit', array(&$this, 'display_custom_fields'), 10, 2);
        /* Save values */
        add_action('edit_attachment', array(&$this, 'save_custom_fields'), 20);
        /* Load custom CSS */
        add_action('admin_enqueue_scripts', array(&$this, 'admin_css'), 11);
    }

    public function wp_loaded() {
        /* Load translation */
        load_plugin_textdomain('wpuattachmetas', false, dirname(plugin_basename(__FILE__)) . '/lang/');

    }

    public function admin_css() {
        $hide_default_fields = apply_filters('wpuattachmetas_hide_default_fields', false);
        if ($hide_default_fields) {
            wp_enqueue_style('wpuattachmetas_hide_default_fields', plugins_url('assets/css/hide-default-fields.css', __FILE__));
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
        $metas = apply_filters('wpuattachmetas_metas', array());
        foreach ($metas as $key => $meta) {
            if (!isset($meta['input'])) {
                $meta['input'] = 'text';
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
            if ($input != 'text') {
                $meta['input'] = 'html';
            }

            switch ($input) {
            case 'select':
                $meta['html'] = '<select ' . $field_idnamehtml . '><option value="" disabled selected style="display:none;">' . __('Select', 'wpuattachmetas') . '</option>';
                foreach ($meta['select_values'] as $skey => $var) {
                    $meta['html'] .= '<option ' . ($meta['value'] == $skey ? 'selected' : '') . ' value="' . $skey . '">' . $var . '</option>';
                }
                $meta['html'] .= '</select>';
                break;
            case 'number':
            case 'email':
            case 'url':
                $meta['html'] = '<input type="' . $input . '" class="text" ' . $field_idnamehtml . ' value="' . esc_attr($meta['value']) . '">';

                break;

            }

            $this->metas[$key] = $meta;
        }
    }

    /**
     * Disply custom fields
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
            case 'number':
                if (!is_numeric($new_value)) {
                    $new_value = $old_value;
                }
                break;
            default:

            }

            update_post_meta($attachment_id, $key, esc_html($new_value));
        }
    }

}

$WPUAttachMetas = new WPUAttachMetas();
