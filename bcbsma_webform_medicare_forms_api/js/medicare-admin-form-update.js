(function ($, Drupal) {
  Drupal.behaviors.TimeStampUpdate = {
    attach: function (context, settings) {
      // Append timestamp to created date field which has class appendtimestamp - to overcome drupal core issue
      $("div.appendtimestamp input#edit-created-1,div.appendtimestamp input#edit-created-2").change(function () {
        console.log($(this).is("#edit-created-2"));
        if (this.value.indexOf(' ') != -1) {
          $(this).val(this.value);
        } else {
          $date_changed_array = this.value.split(' ');
          $timestamp_to_append = ($(this).is("#edit-created-2")) ? " 23:59:59" : " 00:00:00";
          $(this).val($date_changed_array[0] + $timestamp_to_append);
        }
      });
    }
  };
})(jQuery, Drupal);
