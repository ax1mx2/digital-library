(function (wp, $, __) {
    'use strict';
    var book_preview_selector;

    $(function () {
        // Saved post attachment id.
        var book_preview_id_input = $('#book_preview_id');
        var book_preview_indicator = $('#book_preview_indicator');

        $('#book_preview_select_button').click(function (e) {
            e.preventDefault();

            // Create the media frame, if it does not exist.
            if (!book_preview_selector) {
                // Set the post ID.
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

                // Display previously selected book.
                book_preview_selector.on('open', function () {
                    var selection = book_preview_selector.state().get('selection');

                    var attachment_id = book_preview_id_input.val();
                    if (attachment_id) {
                        var attachment = wp.media.attachment(attachment_id);
                        attachment.fetch();
                        selection.set(attachment ? [attachment] : []);
                    } else {
                        selection.set([]);
                    }
                });

                // Handle book preview selection.
                book_preview_selector.on('select', function () {
                    var media = book_preview_selector.state().get('selection').first().toJSON();

                    book_preview_id_input.val(media.id);
                    book_preview_indicator.text(media.title);
                });
            }
            book_preview_selector.open();
        });


        $('#remote_book_preview_button').click(function () {
            book_preview_id_input.val('');
            book_preview_indicator.text(__.missing_book_preview);
        });
    });
})(window.wp, window.jQuery, window.dl_translations);