(function ($, Drupal) {
  debugger
	Drupal.behaviors.dcThemeBehavior = {
		attach: function (context, settings) {
      var $sectionPages = $('.p-homepage__section');
      if ($sectionPages.length) {
        $sectionPages.each(function (index, item) {
          var selector = '.p-homepage__section:nth-child('+ (index + 1) +') .slider';
          var swiper = new Swiper(selector, {
            slidesPerView: 'auto',
            spaceBetween: 30,
            scrollbar: {
              el: '.swiper-scrollbar',
              hide: true,
            },
          });
        });
      }

      if (window.location.pathname === '/') {
        var $exposedForms = $('.views-exposed-form');
        $exposedForms.parent().hide();
      }
		}
	};
})(jQuery, Drupal);
