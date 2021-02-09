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

      var $actorDetailPage = $('.p-actor-detail');
      if ($actorDetailPage.length) {
        var $fields = $actorDetailPage.children();
        var $galleryImages = $fields.last();
        $galleryImages.addClass('gallery-images');
        var $wrapper = $galleryImages.children().last();
        $wrapper.addClass('swiper-wrapper');
        var $images = $wrapper.children();
        $images.addClass('swiper-slide');
        $('<div class="swiper-scrollbar"></div>').insertAfter($wrapper);

        var swiper = new Swiper('.p-actor-detail .gallery-images', {
          slidesPerView: 'auto',
          spaceBetween: 30,
          scrollbar: {
            el: '.p-actor-detail .gallery-images .swiper-scrollbar',
            hide: true,
          },
        });
      }
		}
	};
})(jQuery, Drupal);
