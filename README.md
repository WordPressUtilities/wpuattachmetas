Attachments metas
=================

Adds extra fields to the media view.

How to install :
---

* Put this folder to your wp-content/plugins/ folder.
* Activate the plugin in "Plugins" admin section.

How to add fields :
--

Put the code below in your theme's functions.php file. Add new fields at your convenience.

```php
add_filter( 'wpuattachmetas_metas', 'set_wpuattachmetas_metas', 10, 3 );
function set_wpuattachmetas_metas( $fields ) {
    $fields['wpu_media_price'] = array(
        'label' => 'Price'
    );
    $fields['wpu_media_select'] = array(
        'label' => 'Select',
        'input' => 'select'
    );
    return $fields;
}
```

How to hide default fields ( caption & alt-text ) :
--

Put the code below in your theme's functions.php file.

```php
add_filter('wpuattachmetas_hide_default_fields', '__return_false');
```

Fields parameters :
---

* "label" : String (optional) / Add a label to the field administration. Default to ID value.
* "helps" : String (optional) / Add a help string under the field.
* "input" : String (optional) / Set a kind of form field. Default to "text".
* "show_in_edit" : Bool (optional) / Show/Hide in edit view.
* "show_in_modal" : Bool (optional) / Show/Hide in modal view.
* "required" : Bool (optional) / Field is required.
* "select_values" : Array (optional) / Associative arrays with keys and labels for a selector.

Input types :
---

* "text" : input type text.
* "number" : input type number.
* "url" : input type url.
* "email" : input type email.
* "textarea" : input type textarea.
* "select" : Display a select based on the values contained in "select_values".
* "html" : Display the content of the parameter named "html".

Roadmap :
---

- [x] Edition in the modal view.
- [x] Edition in the add medias view.
- [x] Field type select.
- [ ] Field type taxonomy.
- [ ] Field type post.
- [ ] Field type checkbox.
- [ ] User level.
