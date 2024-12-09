(function ($, Drupal) {
  $(document).ready(function () {
    $plan_listing_advantage_plan = localStorage.getItem('plan-listing-advantage-plan');
    if (!$plan_listing_advantage_plan) {
      let url = new URL(window.location.href);
      url.searchParams.set('onboarding_start', 1);
      Drupal.ModalMessage.showByKey('onboarding-experience-popup', { link: { href: url, target: "_self" } });
      localStorage.setItem('plan-listing-advantage-plan', '1');
    }
  });

  Drupal.behaviors.TourPopup = {
    attach: function (context, settings) {
      const tour = new Shepherd.Tour({
        defaultStepOptions: {
          cancelIcon: {
            enabled: true
          },
          classes: 'shepherd-theme-arrows bg-purple-dark',
          scrollTo: { behavior: 'smooth', block: 'center' }
        }
      });
      const steps = [];
      console.log($(window).width());
      $content_list = drupalSettings.advantage_plan_popup_message_desktop.config;
      if ($(window).width() <= 768) {
        $content_list = drupalSettings.advantage_plan_popup_message_mobile.config;
      }

      $content_list_split = $content_list.split(/\r?\n/);
      var i;
      var last_val;
      last_val = $content_list_split.length - 1;
      for (i = 0; i < $content_list_split.length; ++i) {
        $content_list_split_inner = $content_list_split[i].split('|||');
        if (i == 0) {
          // First Step.
          steps.push({
            attachTo: {
              element: $content_list_split_inner[0],
              on: $content_list_split_inner[3]
            },
            classes: 'shepherd-open shepherd-theme-arrows shepherd-transparent-text',
            buttons: [
              {
                action() {
                  return this.next();
                },
                classes: 'shepherd-button-secondary-next',
                text: 'Next'
              }
            ],
            title: $content_list_split_inner[1],
            text: $content_list_split_inner[2]
          });
          continue;
        }
        if (last_val == i) {
          // Last Step
          steps.push({
            attachTo: {
              element: $content_list_split_inner[0],
              on: $content_list_split_inner[3]
            },
            buttons: [
              {
                action() {
                  return this.back();
                },
                classes: 'shepherd-button-secondary-back',
                text: 'Back'
              },
              {
                action() {
                  return this.cancel();
                },
                classes: 'shepherd-button-secondary-finish',
                text: 'Finish Tour'
              }
            ],
            title: $content_list_split_inner[1],
            text: $content_list_split_inner[2]
          });
          continue;
        }
        // All the steps except First and Last.
        steps.push({
          attachTo: {
            element: $content_list_split_inner[0],
            on: $content_list_split_inner[3]
          },
          buttons: [
            {
              action() {
                return this.back();
              },
              classes: 'shepherd-button-secondary-back',
              text: 'Back'
            },
            {
              action() {
                return this.next();
              },
              classes: 'shepherd-button-secondary-next',
              text: 'Next'
            }
          ],
          title: $content_list_split_inner[1],
          text: $content_list_split_inner[2]
        });
      }
      tour.addSteps(steps);
      if ('onboarding_start' in drupalSettings.path.currentQuery) {
        if ((typeof (drupalSettings.path.currentQuery.onboarding_start) != "undefined" && drupalSettings.path.currentQuery.onboarding_start !== null)) {
          $plan_listing_advantage_plan = localStorage.getItem('plan-listing-advantage-plan-tour');
          localStorage.setItem('plan-listing-advantage-plan-tour', '1');
          if (!$plan_listing_advantage_plan) {
            setTimeout(function () {
              tour.start();
            }, 1000);
          }
        }
      }
      $('[custom-attributes="start-the-tour"] a')
        .unbind("click")
        .bind('click', function () {
          localStorage.setItem('plan-listing-advantage-plan', '1');
          tour.hide();
          tour.start();
        });
    }
  };
})(jQuery, Drupal);
