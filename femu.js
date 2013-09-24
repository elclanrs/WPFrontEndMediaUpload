jQuery(function($) {

  // Add image hidden fields to main form
  $.fn.frontEndMediaUpload = function(images) {
    var self = this;

    $(images).each(function() {

      var $image = $(this).find('.wp-femu-image')
        , filename = $image.length ? $image.attr('src').split('/').pop() : null;

      if ($image.length) {
        self.append('<input type="hidden" name="wp-femu-attachment-'+ this.id +'" value="'+ filename +'"/>');
      }
    });
  };

  // Handle preview
  $('form.wp-femu-form').each(function() {

    var $form = $(this)
      , $iframe = $form.find('iframe')
      , $loading = $('<img class="wp-femu-uploading" src="'+ FrontEndMediaUpload.loader +'"/><p>Uploading</p>')
      , $preview = $form.find('.wp-femu-preview-inner')
      , maxFileSize = $form.find('[name=wp-femu-option-filesize]').val();

    $form.find('input[type=file]').change(function() {

      $form.submit();
      $preview.empty().append($loading);

      $iframe.off('load.wp-femu').on('load.wp-femu', function() {

        var $img = $iframe.contents().find('img')
          , $error = $iframe.contents().find('span');

        $preview.append($img, $error);
        $loading.remove();
      });
    });
  });
});
