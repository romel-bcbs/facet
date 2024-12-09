(function ($, Drupal) {
  Drupal.behaviors.ModalPopup = {
    attach: function (context, settings) {
      $('.model-close').on('click', function () {
        $(".ui-dialog-content").dialog("close");
      });
    }
  }
})(jQuery, Drupal);
