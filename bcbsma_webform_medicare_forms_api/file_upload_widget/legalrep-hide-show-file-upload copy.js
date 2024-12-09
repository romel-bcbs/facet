(function ($, Drupal) {
  Drupal.behaviors.FileCategoryShowHide = {
    attach: function (context, settings) {
      $(window).bind("pageshow", function () {
        //Show the selected legal doc section after page load
        legal_document_section_show_class = localStorage.getItem('legal_document_section_added').split(',');;
        $.each(legal_document_section_show_class, function (index, value) {
          if (value) {
            $('.' + value).show();
          }
        });
      });
      //Enable Add another section button - on change of options
      $('select#edit-controll-select-type-of-document').on('change', function () {
        $(".add_another_section_button").addClass("form-item displayNone");
        if (this.value) {
          $('.add_another_section_button').removeClass("form-item displayNone");
        }
      });
      //Add legal rep doc section
      $(".add_another_section_button").on("click", function () {
        $(".form-item-controll-select-type-of-document .form-item--error-message").remove();
        legal_document_section_added_localStorage = localStorage.getItem('legal_document_section_added');
        if (legal_document_section_added_localStorage == null) {
          legal_document_section_added_localStorage = "";
        }
        console.log(legal_document_section_added_localStorage.indexOf($('select#edit-controll-select-type-of-document').val()));
        if (legal_document_section_added_localStorage.indexOf($('select#edit-controll-select-type-of-document').val()) == '-1') {
          localStorage.setItem('legal_document_section_added', legal_document_section_added_localStorage + $('select#edit-controll-select-type-of-document').val() + ',');
        }
        console.log("legal_document_section_added -" + localStorage.getItem('legal_document_section_added'));
        if ($('.' + $('select#edit-controll-select-type-of-document').val() + ':visible').length == 0) {
          $('.' + $('select#edit-controll-select-type-of-document').val()).show();
          $('.' + $('select#edit-controll-select-type-of-document').val() + ' input.file_section_added').attr('checked', true);
          $('html, body').animate({ scrollTop: $('.' + $('select#edit-controll-select-type-of-document').val()).offset().top - 150 }, 0);
          if ($('.type_of_legal_rep_container_group:visible').length >= 5) {
            $('#edit-control-field-container').hide();
          }

        } else {
          $('.form-item-controll-select-type-of-document').append("<div class='form-item--error-message'><strong>Please make another selection, as this has already been previously selected.</strong></div>");
        }
        $('select#edit-controll-select-type-of-document').val("").change();
      });
      $(".remove_this_document_section").on("click", function () {
        $(this).parent().parent().parent().find('input.file_section_added').click();
        $(this).parent().parent().parent().find("input.form-text").val('');
        if ($(this).parent().parent().parent().find("input.form-checkbox.fill_optional_address_checkbox").is(':checked')) {
          $(this).parent().parent().parent().find("input.form-checkbox.fill_optional_address_checkbox").click();
        }
        if ($(this).parent().parent().parent().find("input.form-checkbox.files_upload_status").is(':checked')) {
          $(this).parent().parent().parent().find("input.form-checkbox.files_upload_status").click();
        }
        $(this).parent().parent().parent().find(".remove-icon").click();
        $(this).parent().parent().parent().hide();
        if ($('.type_of_legal_rep_container_group:visible').length < 5) {
          $('#edit-control-field-container').show();
        }
        legal_document_section_added_removed = localStorage.getItem('legal_document_section_added').replace($(this).attr("data-class") + ',', '');
        localStorage.setItem('legal_document_section_added', legal_document_section_added_removed);
      });
    }
  };
})(jQuery, Drupal);
