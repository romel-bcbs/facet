
(function ($) {
  $(document).ready(function () {
    // Start - Webform Submit button is monitored for disabled attribute change.
    const targetElement = $('#edit-actions-webform-submit');
    const observer = new MutationObserver(function (mutationsList) {
      for (const mutation of mutationsList) {
        if (mutation.type === 'attributes') {
          $(targetElement).trigger('attributeChanged', {
            attributeName: mutation.attributeName,
            oldValue: mutation.oldValue,
            newValue: targetElement.attr(mutation.attributeName)
          });
        }
      }
    });
    // Initiate - observing the element.
    observer.observe(targetElement[0], { attributes: true });
  });
  // Listen for the 'attributeChanged' event.
  $(document).on('attributeChanged', '#edit-actions-webform-submit', function (event, data) {
    if (data.attributeName == 'disabled' && this.id == 'edit-actions-webform-submit' ) {
      if (data.newValue == 'disabled') {
        console.log('Attribute changed:', data.attributeName, data.oldValue, data.newValue);
        this.setAttribute("aria-disabled", "true");
        $("[id=edit-actions-webform]").attr({
          "aria-disabled": "true",
          "tabindex": "0",
        });
      }
      else {
        this.setAttribute("aria-disabled", "false");
        $("[id=edit-actions-webform]").attr({
          "aria-disabled": "false",
          "tabindex": "-1",
        });
      }
    }
  });
  // End - Webform Submit button is monitored for disabled attribute change.

  $(window).on('load', function () {
    $("#edit-actions-webform-submit").attr({
      "aria-disabled": "true",
      "tabindex": "0",
    });
    $("#edit-actions-webform-submit").parent().attr({
      "aria-disabled": "true",
      "tabindex": "0",
    });
    if (!$('input.error, select.error').first().hasClass("hasDatepicker")) {
      $('input.error, select.error').first().focus();
    }
  });
  $(document).ready(function () {
    $(window).bind("pageshow", function () {
      var form = $('form');
      form[0].reset();
    });
    // Disable Cut copy paste in Confirm email
    $('.disable-copy-paste').on("cut copy paste", function (e) {
      e.preventDefault();
    });
    // Disable Cut copy paste in Confirm Member ID - which has Input mask
    $('.disable-copy-paste-with-inputmask').bind("cut copy paste", function (e) {
      e.preventDefault();
      $(this).val("");
    });
  });
}) (jQuery);

(function ($, Drupal) {
  Drupal.behaviors.WebformHierarchyBehavior = {
    attach: function (context, settings) {
        // setTimeout(function () {
        //   $('input.error, select.error').first().focus();
        // }, 200);
      $('a.click_redirect').on('click', function (e) {
        setTimeout(function () {
          $('.ui-dialog-titlebar-close').click();
          document.forms.reset();
        }, 200);
      });
      //Add Custom input mask to the field has this class & has 'data-inputmask-mask: 99/99/9999' under Element custom attributes (YAML)
      $('input.add-custom-input-mask').on('mousedown : click', function (e) {
        $(this).inputmask();
      });
      $('input.add-custom-input-mask').on('focus', function (e) {
        $(this).click();
      });
      /** Start - To disable the 'No Change' Attribute in Addon field if the 'No Change' option selected in 'New Plan' Field
       * - User should select either one option
       * */
      $('select[id*="edit-plan-change-current-plans"]').change(function () {
        // Disable the New plan field - During dynamic load
        $('select[id*="plan-change-updated-plans"]').attr("disabled", true);
      });
      $('select[id*="plan-change-updated-plans"]').change(function () {
        $('input#edit-medex-vision-hearing-no-change').attr("disabled", false);
        if ($('select[id*="plan-change-updated-plans"]').val().toLowerCase() == 'no change') {
          $('input#edit-medex-vision-hearing-no-change').prop('checked', false);;
          $('input#edit-medex-vision-hearing-no-change').attr("disabled", true);
        }
      });
    }
  };
})(jQuery, Drupal);
