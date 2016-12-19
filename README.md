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
            'name' => 'Media price'
        );
        return $fields;
    }

Fields parameters :
---

* "name" : String (optional) / Adds a label to the field administration. Default to ID value.
* "type" : String (optional) / Set a kind of form field. Default to "text".

Fields types :
---

* "text" : input type text.

Roadmap :
---

- [ ] Edition in the modal view.
- [ ] Edition in the add medias view.
- [ ] Field type select.
- [ ] Field type checkbox.
