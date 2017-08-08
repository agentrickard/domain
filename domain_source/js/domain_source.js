/**
 * @file
 * Attaches behaviors for the Domain Source module.
 */
(function ($) {

  "use strict";

  /**
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.domainSourceAllowed = {
    attach: function () {
      var countChecked = function() {
        var domains = new Array();
        $( "#edit-field-domain-access input:checked" ).each(function(index, obj) {
          domains.push(obj.value);
        });
        setOptions(domains);
      }
      countChecked();

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


      $( "#edit-field-domain-access input" ).on( "click", countChecked );
    }
  };

})(jQuery);
