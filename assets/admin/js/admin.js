(function (wp, $, __) {
    'use strict';
    var book_preview_selector;

    $(function () {
        // Store the old id.
        var wp_media_post_id = wp.media.model.settings.post.id;

        // Saved post attachment id.
        var book_preview_id_input = $('#book_preview_id');
        var book_preview_indicator = $('#book_preview_indicator');

        $('#book_preview_select_button').click(function (e) {
            e.preventDefault();

            // If the media frame already exists, reopen it.
            if (book_preview_selector) {
                // Set the post ID.
                book_preview_selector.uploader.uploader.param('post_id', book_preview_id_input.val());
                // Open frame.
                book_preview_selector.open();
                return;
            }

            wp.media.model.settings.post.id = book_preview_id_input.val();
            // Create the media frame.
            book_preview_selector = wp.media.frames.media_frame = wp.media({
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
            book_preview_selector.on('select', function () {
                var media = book_preview_selector.state().get('selection').first().toJSON();

                book_preview_id_input.val(media.id);
                book_preview_indicator.text(media.title);

                // Restore the main post ID.
                wp.media.model.settings.post.id = wp_media_post_id;
            });

            book_preview_selector.open();
        });

        // Restore the main ID when the add media button is pressed.
        $('a.add_media').click(function () {
            wp.media.model.settings.post.id = wp_media_post_id;
        });

        $('#remote_book_preview_button').click(function() {
            book_preview_id_input.val('');
            book_preview_indicator.text(__.missing_book_preview);
        });
    });
})(window.wp, window.jQuery, window.dl_translations);