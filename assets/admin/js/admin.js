(function (wp, $, __) {
    'use strict';
    debugger;
    var media_frame;

    $(function () {
        // Store the old id.
        var wp_media_post_id = wp.media.model.settings.post.id;
        // Saved post attachment id.
        var book_preview_id_input = $('#book_preview_id');

        $('#book_preview_select_button').click(function (e) {
            e.preventDefault();

            // If the media frame already exists, reopen it.
            if (media_frame) {
                // Set the post ID.
                media_frame.uploader.uploader.param('post_id', book_preview_id_input.val());
                // Open frame.
                media_frame.open();
                return;
            }

            wp.media.model.settings.post.id = book_preview_id_input.val();
            // Create the media frame.
            media_frame = wp.media.frames.media_frame = wp.media({
                frame: 'select',
                title: __.media_frame_title || 'Please select a book preview',
                button: {
                    text: __.select_preview_button || 'Use this book preview'
                },
                library: {
                    type: 'application/pdf',
                    ordre: 'DESC',
                    orderby: 'date',
                },
                multiple: false
            });

            // Handle book preview selection.
            media_frame.on('select', function () {
                var media = media_frame.state().get('selection').first().toJSON();

                book_preview_id_input.val(media.id);
                // Restore the main post ID.
                wp.media.model.settings.post.id = wp_media_post_id;
            });

            media_frame.open();
        });

        // Restore the main ID when the add media button is pressed.
        $('a.add_media').click(function () {
            wp.media.model.settings.post.id = wp_media_post_id;
        });
    });
})(window.wp, window.jQuery, window.dl_translations);