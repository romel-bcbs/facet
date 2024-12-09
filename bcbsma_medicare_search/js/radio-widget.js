/**
 * @file
 * Transforms links into checkboxes.
 */

(function ($, Drupal, once) {

  'use strict';

  Drupal.facets = Drupal.facets || {};
  Drupal.behaviors.facetsRadioWidget = {
    attach: function (context) {
      Drupal.facets.makeRadioboxes(context);
    }
  };

  window.onbeforeunload = function (e) {
    if (Drupal.facets) {
      var $radioWidgets = $('.js-facets-radio-links');
      if ($radioWidgets.length > 0) {
        $radioWidgets.each(function (index, widget) {
          var $widget = $(widget);
          var $widgetLinks = $widget.find('.facet-item > a');
          $widgetLinks.each(Drupal.facets.updateRadioBox);
        });
      }
    }
  };

  /**
   * Turns all facet links into radioboxes.
   */
  Drupal.facets.makeRadioboxes = function (context) {
    // Find all radiobox facet links and give them a radiobox.
    var $radioboxWidgets = $(once('facets-radiobox-transform', '.js-facets-radiobox-links', context));

    if ($radioboxWidgets.length > 0) {
      $radioboxWidgets.each(function (index, widget) {
        var $widget = $(widget);
        var $widgetLinks = $widget.find('.facet-item > a');

        // Add correct CSS selector for the widget. The Facets JS API will
        // register handlers on that element.
        $widget.addClass('js-facets-widget');

        // Transform links to radioboxes.
        $widgetLinks.each(Drupal.facets.makeRadiobox);

        // We have to trigger attaching of behaviours, so that Facets JS API can
        // register handlers on radiobox widgets.
        Drupal.attachBehaviors(this.parentNode, Drupal.settings);
      });

    }

    // Set indeterminate value on parents having an active trail.
    $('.facet-item--expanded.facet-item--active-trail > input').prop('indeterminate', true);
  };

  /**
   * Replace a link with a checked radiobox.
   */
  Drupal.facets.makeRadiobox = function () {
    var $link = $(this);
    var active = $link.hasClass('is-active');
    var description = $link.html();
    var href = $link.attr('href');
    var id = $link.data('drupal-facet-item-id');

    var radiobox = $('<input type="radio" class="facets-radiobox">')
      .attr('id', id)
      .data($link.data())
      .data('facetsredir', href);
    var label = $('<label for="' + id + '">' + description + '</label>');

    radiobox.on('change.facets', function (e) {
      e.preventDefault();

      var $widget = $(this).closest('.js-facets-widget');

      Drupal.facets.disableFacet($widget);
      $widget.trigger('facets_filter', [href]);
    });

    if (active) {
      radiobox.attr('checked', true);
      label.find('.js-facet-deactivate').remove();
    }

    $link.before(radiobox).before(label).hide();

  };

  /**
   * Update Radio active state.
   */
  Drupal.facets.updateRadiobox = function () {
    var $link = $(this);
    var active = $link.hasClass('is-active');

    if (!active) {
      $link.parent().find('input.facets-radiobox').prop('checked', false);
    }
  };

  /**
   * Disable all facet radioboxes in the facet and apply a 'disabled' class.
   *
   * @param {object} $facet
   *   jQuery object of the facet.
   */
  Drupal.facets.disableFacet = function ($facet) {
    $facet.addClass('facets-disabled');
    $('input.facets-radiobox', $facet).click(Drupal.facets.preventDefault);
    $('input.facets-radiobox', $facet).attr('disabled', true);
  };

  /**
   * Event listener for easy prevention of event propagation.
   *
   * @param {object} e
   *   Event.
   */
  Drupal.facets.preventDefault = function (e) {
    e.preventDefault();
  };

})(jQuery, Drupal, once);
