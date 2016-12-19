<?php

/*
Plugin Name: WPU Attachments Metas
Plugin URI: https://github.com/WordPressUtilities/wpuattachmetas
Description: Metadatas for Attachments
Version: 0.2
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUAttachMetas {

    private $pluginkey = 'wpuattach_';

    private $metas = array();

    public function __construct() {
        /* Display fields */
        add_filter('attachment_fields_to_edit', array(&$this, 'display_custom_fields'), 10, 2);
        /* Save values */
        add_action('edit_attachment', array(&$this, 'save_custom_fields'), 20);
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
                $meta['select_values'] = array(__('No'), __('Yes'));
            }
            if ($meta['input'] == 'select') {
                $meta['input'] = 'html';
                $meta['html'] = '<select ' . $field_idnamehtml . '><option value="" disabled selected style="display:none;">' . __('Select') . '</option>';
                foreach ($meta['select_values'] as $skey => $var) {
                    $meta['html'] .= '<option ' . ($meta['value'] == $skey ? 'selected' : '') . ' value="' . $skey . '">' . $var . '</option>';
                }
                $meta['html'] .= '</select>';
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
            default:

            }

            update_post_meta($attachment_id, $key, esc_html($new_value));
        }
    }

}

$WPUAttachMetas = new WPUAttachMetas();
