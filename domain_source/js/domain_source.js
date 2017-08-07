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
        var domains = $( "#edit-field-domain-access input:checked" ).each.val();

      };
      countChecked();

      $( "#edit-field-domain-access input" ).on( "click", countChecked );
    }
  };

})(jQuery);
