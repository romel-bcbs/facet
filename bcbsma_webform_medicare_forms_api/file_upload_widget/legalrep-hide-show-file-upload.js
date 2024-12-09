(function ($, Drupal) {
  Drupal.behaviors.FileCategoryShowHide = {
    attach: function (context, settings) {
      $(window).bind("pageshow", function () {
        //Show the selected legal doc section after page load.
        legal_document_section_show_class = localStorage.getItem('legal_document_section_added').split(',');
        legal_document_section_show_class.pop();
        const document_selector = document.querySelectorAll(".document-type-selector");
        document_selector.forEach(findAllElementsToShow);

        function findAllElementsToShow(item, index, arr) {
          if (jQuery.inArray($(document_selector[index]).val(), legal_document_section_show_class) != '-1' && !jQuery.isEmptyObject(legal_document_section_show_class)) {
            $(document_selector[index]).parent().parent().show();
          }
        }
        setTimeout(function () {
          remove_control_select_type_field();
        }, 200);

      });

      //Set previous value for Document selector.
      $('select.document-type-selector').bind('change', function () {
        if ($(this).data("prev") != $(this).val()) {
          document_type_selector_visible_inplace = [];
          $(".document-type-selector:visible").each(function (index) {
            document_type_selector_visible_inplace[index] = $(this).data("prev");
          });
          $(this).parent().find('.form-item--error-message-inplace').remove();
          if (jQuery.inArray($(this).val(), document_type_selector_visible_inplace) != '-1' && $(this).data("prev") != $(this).val()) {
            $(this).val($(this).data("prev"));
            $(this).parent().append("<div class='form-item--error-message-inplace'><strong>Please make another selection; this documentation type has already been selected.</strong></div>");
            return;
          }
          legal_document_section_added_localStorage = localStorage.getItem('legal_document_section_added');
          if (legal_document_section_added_localStorage == null) {
            legal_document_section_added_localStorage = "";
          }
          if (legal_document_section_added_localStorage.indexOf($(this).val()) == '-1') {
            localStorage.setItem('legal_document_section_added', legal_document_section_added_localStorage + $(this).val() + ',');
          }
          //Destroy the local storage - for previous val.
          if (typeof $(this).data("prev") !== 'undefined') {
            if ($(this).data("prev") != $(this).val()) {
              legal_document_section_added_removed = localStorage.getItem('legal_document_section_added').replace($(this).data("prev") + ',', '');
              localStorage.setItem('legal_document_section_added', legal_document_section_added_removed);
            }
          }
          $(this).data("prev", $(this).val());
        }
      });
      //Enable Add another section button - on change of options.
      $('select#edit-controll-select-type-of-document').on('change', function () {
        if ($(this).val() == "") {
          return;
        }
        $(".form-item-controll-select-type-of-document .form-item--error-message").remove();
        document_type_selector_visible = [];
        $(".document-type-selector:visible").each(function (index) {
          document_type_selector_visible[index] = $(this).val();
        });
        if (jQuery.inArray($(this).val(), document_type_selector_visible) != '-1') {
          $('.form-item-controll-select-type-of-document').append("<div class='form-item--error-message'><strong>Please make another selection; this documentation type has already been selected.</strong></div>");
          return;
        }
        console.log($(this).val());
        legal_document_section_added_localStorage = localStorage.getItem('legal_document_section_added');
        if (legal_document_section_added_localStorage == null) {
          legal_document_section_added_localStorage = "";
        }
        if (legal_document_section_added_localStorage.indexOf($('select#edit-controll-select-type-of-document').val()) == '-1') {
          localStorage.setItem('legal_document_section_added', legal_document_section_added_localStorage + $('select#edit-controll-select-type-of-document').val() + ',');
        }
        visible_length = $('.type_of_legal_rep_container_group:visible').length;
        if (visible_length < 5 && visible_length >= 0) {
          $('.type_of_legal_rep_container_group:hidden:first select.document-type-selector option[value = "choose_type_of_legal_rep"]').removeAttr("selected");
          $('.type_of_legal_rep_container_group:hidden:first select.document-type-selector option[value = "' + $(this).val() + '"]').attr("selected", "selected").data("prev", $(this).val()).change();
          $('.type_of_legal_rep_container_group:hidden:first input.file_section_added').attr('checked', true);
          $('.type_of_legal_rep_container_group:hidden:first').show();
          $('.controll_select_type_of_document_item').addClass("displayNone");
          $('.legal-rep-btn').removeClass("form-item displayNone");
          $(this).val('');
          if (visible_length >= 1) {
            $('html, body').animate({ scrollTop: $('.type_of_legal_rep_container_group:visible:last').offset().top - 200 }, 0);
          }
        }
        if (visible_length == 0) {
          $('.legal-rep-btn').removeClass("form-item displayNone");
        }
        visible_length = $('.type_of_legal_rep_container_group:visible').length;
        if (visible_length >= 5) {
          $('.legal-rep-btn').addClass("form-item displayNone");
        }
        remove_remove_representative_link();
      });
      //Add legal rep doc section
      $(".add_another_section_button").on("click", function () {
        $(".form-item-controll-select-type-of-document .form-item--error-message").remove();
        $('.controll_select_type_of_document_item').removeClass("displayNone");
        $('.legal-rep-btn').addClass("form-item displayNone");
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
        $('.type_of_legal_rep_container_group:last').after($(this).parent().parent().parent());
        $(this).parent().parent().parent().hide();
        if ($('.type_of_legal_rep_container_group:visible').length < 5) {
          $('.legal-rep-btn').removeClass("form-item displayNone");
        }
        // unset the uploaded file object stored in localstorage per file category.
        localStorage.setItem($(this).parent().parent().parent().find("select.document-type-selector").val(), '');
        // unset the legelrep section added list stored in localstorage.
        legal_document_section_added_removed = localStorage.getItem('legal_document_section_added').replace($(this).parent().parent().parent().find("select.document-type-selector").val() + ',', '');
        localStorage.setItem('legal_document_section_added', legal_document_section_added_removed);
        remove_remove_representative_link();
      });
      function remove_remove_representative_link() {
        if ($('.type_of_legal_rep_container_group:visible').length <= 1) {
          $('.type_of_legal_rep_container_group:visible .remove_this_document_section').hide();
        }
        else {
          $('.type_of_legal_rep_container_group:visible .remove_this_document_section:hidden').show();
        }
      }
      function remove_control_select_type_field() {
        remove_control_length = $('.type_of_legal_rep_container_group:visible').length;
        if (remove_control_length >= 1) {
          $('.controll_select_type_of_document_item').addClass("displayNone");
          if (remove_control_length >= 1 && remove_control_length < 5) {
            $('.legal-rep-btn').removeClass("form-item displayNone");
          }
        }
      }
    }
  };
})(jQuery, Drupal);
