document.addEventListener('DOMContentLoaded', function () {
    var fileFrame;
    var uploadButton = document.getElementById('upload-btn');
    var imageField = document.getElementById('image_url');
    var imageIdField = document.getElementById('image_id');
    var logoPreview = document.getElementById('logo-preview');

    if (uploadButton) {
        uploadButton.addEventListener('click', function (event) {
            event.preventDefault();

            if (fileFrame) {
                fileFrame.open();
                return;
            }

            fileFrame = wp.media.frames.fileFrame = wp.media({
                title: 'Wybierz Logo Strony',
                button: {
                    text: 'Użyj tego obrazu'
                },
                multiple: false
            });

            fileFrame.on('select', function () {
                var attachment = fileFrame.state().get('selection').first().toJSON();
                if (imageField) {
                    imageField.value = attachment.url;
                }
                if (imageIdField) {
                    imageIdField.value = attachment.id;
                }
                if (logoPreview) {
                    logoPreview.src = attachment.url;
                }
            });

            fileFrame.open();
        });
    }
});
