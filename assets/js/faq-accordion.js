(function ($) {
    function initAccordion($scope) {
        var $accordion = $scope.find('.ecfw-accordion');
        if (!$accordion.length) {
            return;
        }

        $accordion.each(function () {
            var $instance = $(this);
            $instance.find('.elementor-tab-title[aria-expanded="true"]').each(function () {
                var $title = $(this);
                var $item = $title.closest('.elementor-accordion-item');
                var $content = $item.find('.elementor-tab-content').first();
                $title.addClass('elementor-active');
                $content.removeAttr('hidden').addClass('elementor-active').show();
            });
        });

        $accordion.off('click.ecfw').on('click.ecfw', '.elementor-tab-title', function () {
            var $title = $(this);
            var $item = $title.closest('.elementor-accordion-item');
            var $content = $item.find('.elementor-tab-content').first();
            var isOpen = $title.attr('aria-expanded') === 'true';
            var duration = parseInt($title.closest('.ecfw-accordion').data('animation-duration'), 10);
            if (isNaN(duration)) {
                duration = 200;
            }

            $accordion.find('.elementor-tab-title[aria-expanded="true"]').attr('aria-expanded', 'false').removeClass('elementor-active');
            $accordion.find('.elementor-tab-content:not([hidden])').stop(true, true).slideUp(duration, function () {
                $(this).attr('hidden', true).removeClass('elementor-active');
            });

            if (!isOpen) {
                $title.attr('aria-expanded', 'true').addClass('elementor-active');
                $content.removeAttr('hidden').addClass('elementor-active').stop(true, true).slideDown(duration);
            }
        });
    }

    $(window).on('elementor/frontend/init', function () {
        if (window.elementorFrontend) {
            elementorFrontend.hooks.addAction('frontend/element_ready/ecfw-faq-accordion.default', initAccordion);
        }
    });

    $(document).ready(function () {
        initAccordion($(document));
    });
})(jQuery);
