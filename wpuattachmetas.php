<?php

/*
Plugin Name: WPU Attachments Metas
Plugin URI: https://github.com/WordPressUtilities/wpuattachmetas
Description: Metadatas for Attachments
Version: 0.1
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUAttachMetas {

    private $metas = array();

    public function __construct() {
        /* Load values */
        $this->load_metas();
        /* Display custom fields */
        add_action('edit_form_after_title', array(&$this, 'display_custom_fields'), 20);
        /* Save values */
        add_action('edit_attachment', array(&$this, 'save_custom_fields'), 20);
    }

    /**
     * Load metadatas and ensure content is correct
     */
    public function load_metas() {
        $tmp_metas = apply_filters('wpuattachmetas_metas', $this->metas);
        foreach ($tmp_metas as $key => $tmp_meta) {
            if (!isset($tmp_meta['name'])) {
                $tmp_meta['name'] = ucfirst($key);
            }
            $this->metas[$key] = $tmp_meta;
        }
    }

    /**
     * Display custom fields under attachment edit form
     */
    public function display_custom_fields($post) {
        foreach ($this->metas as $key => $meta) {
            $_id = 'wpuattach_' . $key;
            $_value = get_post_meta($post->ID, $_id, 1);
            echo '<p>';
            echo '<label for="' . $_id . '"><strong>' . $meta['name'] . '</strong></label><br>';
            echo '<input type="text" class="widefat" name="' . $_id . '" id="wpuattach_' . $key . '" value="' . esc_attr($_value) . '" />';
            echo '</p>';
        }
    }

    /**
     * Save custom fields when attachment is saved
     */
    public function save_custom_fields($attachment_id) {
        foreach ($this->metas as $key => $meta) {
            $_id = 'wpuattach_' . $key;
            if (isset($_POST[$_id])) {
                update_post_meta($attachment_id, $_id, esc_html($_POST[$_id]));
            }
        }
    }
}

$WPUAttachMetas = new WPUAttachMetas();
