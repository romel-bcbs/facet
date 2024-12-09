(function ($, Drupal) {
  Drupal.behaviors.FileUploadComp = {
    attach: function (context, settings) {
      $(window).on('load', function () {
        setTimeout(function () {
          $('input.error, select.error').first().focus();
        }, 200);
      });
      const configDup = {
        maxFileSizeMB: drupalSettings.LegalRepFileUpload.config.angular_file_upload.max_file_size_mb,
        maxFilesUpload: drupalSettings.LegalRepFileUpload.config.angular_file_upload.max_files_upload,
        totalFilesLimitExceededMB: drupalSettings.LegalRepFileUpload.config.angular_file_upload.total_files_limit_exceeded_mb,
        acceptedFileFormat: drupalSettings.LegalRepFileUpload.config.angular_file_upload.accepted_file_format.split(','),
        apiParams: {
          apiBaseUrl: drupalSettings.LegalRepFileUpload.config.angular_file_upload.apiBaseUrl,
          clientName: drupalSettings.LegalRepFileUpload.config.angular_file_upload.clientName,
          clientSessionId: drupalSettings.LegalRepFileUpload.config.angular_file_upload.clientSessionId,
          clientVersion: drupalSettings.LegalRepFileUpload.config.angular_file_upload.clientVersion,
          trackingId: drupalSettings.LegalRepFileUpload.config.angular_file_upload.trackingId,
          apiEnvironment: drupalSettings.LegalRepFileUpload.config.angular_file_upload.apiEnvironment,
        },
        uploadErrorMessages: {
          atLeastOneFile:
            drupalSettings.LegalRepFileUpload.config.angular_file_upload.atleast_one_file,
          requiredDocumentation:
            drupalSettings.LegalRepFileUpload.config.angular_file_upload.required_documentation,
          sizeLimitExceeded:
            drupalSettings.LegalRepFileUpload.config.angular_file_upload.size_limit_exceeded,
          totalLmitExceeded:
            drupalSettings.LegalRepFileUpload.config.angular_file_upload.total_limit_exceeded,
          docLimitExceeded:
            drupalSettings.LegalRepFileUpload.config.angular_file_upload.doc_limit_exceeded,
          unsupportedFormat:
            drupalSettings.LegalRepFileUpload.config.angular_file_upload.unsupported_format,
        },
      };
      const el2 = document.querySelectorAll("bcbsma-file-upload-comp");
      el2.forEach(addListnerToAllTag)
      function addListnerToAllTag(item, index, arr) {
        el2[index].config = configDup;
        el2[index].value = localStorage.getItem($(el2[index]).parent().find("select.document-type-selector").val());
        el2[index].addEventListener("onFileUploadListener", handleFileUpload);
        el2[index].addEventListener("onFileUploadingIndicator", onFileUploadingIndicator);
      }
      function onFileUploadingIndicator(e2) {
        console.log('FileUpload - Started');
        console.log(e2.detail);
        if (e2.detail) {
          $(this).parent().find(".upload_progress_status").attr('checked', true);
        }
        else {
          $(this).parent().find(".upload_progress_status").attr('checked', false);
        }
      }
      function handleFileUpload(e2) {
        $file_id = "";
        console.log(e2.detail);
        $.each(e2.detail.files, function (index, value) {
          $file_id += value.attachmentId + "::" + value.secretId + ",";
        });
        localStorage.setItem($(this).parent().find("select.document-type-selector").val(), JSON.stringify(e2.detail));
        console.log('Files :' + $file_id);
        $(this).parent().find(".files_uploaded").val($file_id);
        console.log('Files Error :' + e2.detail.error);
        if (e2.detail.error) {
          $(this).parent().find(".files_uploaded_status").attr('checked', true);
        }
        else {
          $(this).parent().find(".files_uploaded_status").attr('checked', false);
        }
        if (e2.detail.isApiError == true) {
          Drupal.ModalMessage.showByKey('ajax-error');
        }
      }
    }
  };
})(jQuery, Drupal);
