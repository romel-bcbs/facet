(function ($, Drupal) {
  Drupal.behaviors.testLocalDateTimePopup = {
    attach: function (context, settings) {
      $("form#views-exposed-form-seminar-listing-seminar-list-view input:radio[name='date_range_list[date_range_list]']").change(function () {
        //console.log($(this).is("#edit-created-2"));
        $("form#views-exposed-form-seminar-listing-seminar-list-view  #edit-date-range-picker-wrapper--2").hide();
        if (this.value != 'custom_date_range') {
          $("form#views-exposed-form-seminar-listing-seminar-list-view #edit-date-range-picker-start-date--2").val("");
          $("form#views-exposed-form-seminar-listing-seminar-list-view #edit-date-range-picker-end-date--2").val("");
        }
        if (this.value == 'custom_date_range') {
          $("form#views-exposed-form-seminar-listing-seminar-list-view  #edit-date-range-picker-wrapper--2").show();
          return;
        }
        var $form = $(this).closest('form');
        $form.find('input[type=submit]').click();
      });
      $("form#views-exposed-form-seminar-listing-seminar-list-view #edit-date-range-picker-start-date--2").change(function () {
        //console.log($(this).is("#edit-created-2"));
        if (!$("form#views-exposed-form-seminar-listing-seminar-list-view #edit-date-range-picker-end-date--2").val()) {
          $("form#views-exposed-form-seminar-listing-seminar-list-view #edit-date-range-picker-end-date--2").focus();
          return;
        }
        var $form = $(this).closest('form');
        $form.find('input[type=submit]').click();
      });
      $("form#views-exposed-form-seminar-listing-seminar-list-view #edit-date-range-picker-end-date--2").change(function () {
        //console.log($(this).is("#edit-created-2"));
        if (!$("form#views-exposed-form-seminar-listing-seminar-list-view #edit-date-range-picker-start-date--2").val()) {
          $("form#views-exposed-form-seminar-listing-seminar-list-view #edit-date-range-picker-start-date--2").focus();
          return;
        }
        var $form = $(this).closest('form');
        $form.find('input[type=submit]').click();
      });
    }
  };
})(jQuery, Drupal);
