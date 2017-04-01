jQuery(document).ready(function() {
    /* Add an image */
    jQuery('.wpuattachmetas-image-link').click(function(e) {
        var $this = jQuery(this),
            $parent = $this.parent(),
            $imgPreview = $parent.find('img'),
            $imgField = $parent.find('input[type="hidden"]');

        var frame = wp.media({
            multiple: false,
        });

        // Open on selected image
        frame.on('open', function() {
            if (!$this.attr('data-attid')) {
                return;
            }
            attachment = wp.media.attachment($this.attr('data-attid'));
            attachment.fetch();
            frame.state().get('selection').add(attachment ? [attachment] : []);
        });

        // When an image is selected in the media frame...
        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            $imgPreview.attr('src', attachment.url);
            $this.attr('data-attid', attachment.id);
            // Send the attachment id to our hidden input
            $imgField.val(attachment.id);
            $this.text($this.attr('data-altlabel'));
        });

        // Finally, open the modal on click
        frame.open();

        e.preventDefault();
    });

});
