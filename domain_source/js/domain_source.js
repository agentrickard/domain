/**
 * @file
 * Attaches behaviors for the Domain Source module.
 *
 * If Domain Access is present, we show.hide selected publishing domains. This approach
 * currently only works with a select field.
 */
(function ($) {

  "use strict";

  /**
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.domainSourceAllowed = {
    attach: function () {
      // Get the domains selected by the domain access field.
      var getDomains = function() {
        var domains = new Array();
        $( "#edit-field-domain-access :checked" ).each(function(index, obj) {
          domains.push(obj.value);
        });
        setOptions(domains);
      }

      // Onload, fire initial settings.
      getDomains();

     // Based on selected domains, show/hide the selection options.
     function setOptions(domains) {
        $( "#edit-field-domain-source option" ).each(function(index, obj) {
          if (jQuery.inArray(obj.value, domains) == -1 && obj.value != '_none') {
            $("#edit-field-domain-source option[value=" + obj.value + "]").hide();
          }
          else {
            $("#edit-field-domain-source option[value=" + obj.value + "]").show();
          }
        });
      }

      // When the selections change, recalculate the select options.
      $( "#edit-field-domain-access" ).on( "click", getDomains );
    }
  };

})(jQuery);
