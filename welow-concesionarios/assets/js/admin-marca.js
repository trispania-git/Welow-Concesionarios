/**
 * JS Admin: Media uploader para metaboxes de Marca.
 * Welow Concesionarios
 */
(function ($) {
    'use strict';

    $(document).ready(function () {

        // Botón de subir imagen
        $(document).on('click', '.welow-upload-btn', function (e) {
            e.preventDefault();

            var button    = $(this);
            var targetId  = button.data('target');
            var previewId = button.data('preview');

            var frame = wp.media({
                title: 'Seleccionar imagen',
                button: { text: 'Usar esta imagen' },
                multiple: false,
            });

            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();
                var imgUrl     = attachment.sizes && attachment.sizes.medium
                    ? attachment.sizes.medium.url
                    : attachment.url;

                $('#' + targetId).val(attachment.id);
                $('#' + previewId).html(
                    '<img src="' + imgUrl + '" alt="" />'
                );

                button.text('Cambiar imagen');

                // Mostrar botón quitar si no existe
                if (button.siblings('.welow-remove-btn').length === 0) {
                    button.after(
                        '<button type="button" class="button welow-remove-btn" ' +
                        'data-target="' + targetId + '" ' +
                        'data-preview="' + previewId + '">Quitar</button>'
                    );
                }
            });

            frame.open();
        });

        // Botón de quitar imagen
        $(document).on('click', '.welow-remove-btn', function (e) {
            e.preventDefault();

            var button    = $(this);
            var targetId  = button.data('target');
            var previewId = button.data('preview');

            $('#' + targetId).val('');
            $('#' + previewId).html('');

            button.siblings('.welow-upload-btn').text('Seleccionar imagen');
            button.remove();
        });

    });
})(jQuery);
