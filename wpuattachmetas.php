<?php

/*
Plugin Name: WPU Attachments Metas
Plugin URI: https://github.com/WordPressUtilities/wpuattachmetas
Description: Metadatas for Attachments
Version: 0.5.1
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUAttachMetas {

    private $pluginkey = 'wpuattach_';
    private $pluginversion = '0.5.1';

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
            case 'email':
            case 'url':
                $meta['html'] = '<input type="' . $input . '" class="text" ' . $field_idnamehtml . ' value="' . esc_attr($meta['value']) . '">';
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
        }
    }

    public function admin_footer() {
        echo <<<EOT
<script>
/* Delete image */
jQuery('.azazazazaz .x').click(function(e) {
    var \$this = jQuery(this),
        \$parent = \$this.closest('.wpubasesettings-mediabox'),
        \$imgPreview = \$parent.find('.img-preview');
        \$imgField = \$parent.find('input[type="hidden"]');
    e.preventDefault();
    \$imgPreview.css({'display':'none'});
    \$imgField.val('');
});

/* Add image */
jQuery('.wpuattachmetas-image-link').click(function(e) {
    var \$this = jQuery(this),
        \$parent = \$this.parent(),
        \$imgPreview = \$parent.find('img');
        \$imgField = \$parent.find('input[type="hidden"]');

    var frame = wp.media({multiple: false });

    // When an image is selected in the media frame...
    frame.on('select', function() {
        var attachment = frame.state().get('selection').first().toJSON();
        \$imgPreview.css({'display':'block'});
        \$imgPreview.find('img').attr('src',attachment.url);
        // Send the attachment id to our hidden input
        \$imgField.val(attachment.id);
        console.log(\$imgField);
    });

    // Finally, open the modal on click
    frame.open();

    e.preventDefault();
});

</script>
EOT;
    }
}

$WPUAttachMetas = new WPUAttachMetas();
