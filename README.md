Attachments metas
=================

Adds extra fields to the media view.

How to install :
---

* Put this folder to your wp-content/plugins/ folder.
* Activate the plugin in "Plugins" admin section.

How to add fields :
--

Put the code below in your theme's functions.php file. Add new fields to your convenance.

    add_filter( 'wpuattachmetas_metas', 'set_wpuattachmetas_metas', 10, 3 );
    function set_wpuattachmetas_metas( $fields ) {
        $fields['wpu_media_price'] = array(
            'label' => 'Media price'
        );
        $fields['wpu_media_price'] = array(
            'label' => 'Media price'
        );
        return $fields;
    }

Fields parameters :
---

* "label" : String (optional) / Adds a label to the field administration. Default to ID value.
* "helps" : String (optional) / Adds a help string under the field.
* "input" : String (optional) / Set a kind of form field. Default to "text".
* "show_in_edit" : Bool (optional) / Show/Hide in edit view.
* "show_in_modal" : Bool (optional) / Show/Hide in modal view.
* "required" : Bool (optional) / Field is required.

Input types :
---

* "text" : input type text.
* "textarea" : input type textarea.
* "select" : display a select based on the values contained in the parameter "select_values"
* "html" : display the content of the parameter named "html".

Roadmap :
---

- [x] Edition in the modal view.
- [x] Edition in the add medias view.
- [x] Field type select.
- [ ] Field type taxonomy.
- [ ] Field type post.
- [ ] Field type checkbox.
- [ ] User level.
