(function ($, Drupal) {
  Drupal.behaviors.LocalDateTimePopup = {
    attach: function (context, settings) {
      const HundredYearFromNow = new Date();
      HundredYearFromNow.setFullYear(HundredYearFromNow.getFullYear() + 100);
      $('div.datetime-local-popup input[id*="seminar-date"]').attr("type", 'datetime-local');
      $('div.datetime-local-popup input[id*="seminar-date"]').attr("min", '1900-00-01T00:00');
      $('div.datetime-local-popup input[id*="seminar-date"]').attr("max", HundredYearFromNow.getFullYear() +'-12-30T23:59');
    }
  };
})(jQuery, Drupal);
