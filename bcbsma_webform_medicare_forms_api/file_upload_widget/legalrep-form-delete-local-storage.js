(function ($, Drupal) {
  Drupal.behaviors.LegalrepSectionLocalVariableDelete = {
    attach: function (context, settings) {
      console.log("Local storage deleted");
      localStorage.setItem('legal_document_section_added', '');
      localStorage.setItem('power_of_attorney', '');
      localStorage.setItem('health_care_proxy', '');
      localStorage.setItem('executor_of_estate', '');
      localStorage.setItem('court_appointed_guardian_or_conservatorship', '');
      localStorage.setItem('other_legal_doc', '');
    }
  };
})(jQuery, Drupal);
