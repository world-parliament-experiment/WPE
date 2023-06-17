/* 
 * 
 * piple bootstrap theme
 */

/***=================easy pie charts================================= */
$('.chart').each(function() {
    var $this = $(this);
    var color = $(this).data('scale-color');

    setTimeout(function() {
        $this.filter(':visible').waypoint(function(direction) {
            $(this).easyPieChart({
                barColor: color,
                trackColor: '#fff',
                onStep: function(from, to, percent) {
                    jQuery(this.el).find('.percent').text(Math.round(percent));
                }
            });
        }, {offset: '100%'});
    }, 500);

});



/*owl carousel for news section*/
$(document).ready(function() {

    $("#owl-example").owlCarousel({
        items: 4,
        autoPlay: true
    });

});



//isotope filter

var winDow = $(window);
// Needed variables
var $container = $('.iso-call');
var $filter = $('.filter');

try {
    $container.imagesLoaded(function() {
        $container.trigger('resize');
        $container.isotope({
            filter: '*',
            layoutMode: 'masonry',
            animationOptions: {
                duration: 750,
                easing: 'linear'
            }
        });

        $('.triggerAnimation').waypoint(function() {
            var animation = $(this).attr('data-animate');
            $(this).css('opacity', '');
            $(this).addClass("animated " + animation);

        },
                {
                    offset: '75%',
                    triggerOnce: true
                }
        );
        setTimeout( 1500);
    });
} catch (err) {
}

winDow.bind('resize', function() {
    var selector = $filter.find('a.active').attr('data-filter');

    try {
        $container.isotope({
            filter: selector,
            animationOptions: {
                duration: 750,
                easing: 'linear',
                queue: false
            }
        });
    } catch (err) {
    }
    return false;
});

// Isotope Filter 
$filter.find('a').click(function() {
    var selector = $(this).attr('data-filter');

    try {
        $container.isotope({
            filter: selector,
            animationOptions: {
                duration: 750,
                easing: 'linear',
                queue: false
            }
        });
    } catch (err) {

    }
    return false;
});


var filterItemA = $('.filter li a');

filterItemA.on('click', function() {
    var $this = $(this);
    if (!$this.hasClass('active')) {
        filterItemA.removeClass('active');
        $this.addClass('active');
    }
});


      /* ==============================================
             WOW plugin triggers animate.css on scroll
             =============================================== */

            var wow = new WOW(
                    {
                        boxClass: 'wow', // animated element css class (default is wow)
                        animateClass: 'animated', // animation css class (default is animated)
                        offset: 100, // distance to the element when triggering the animation (default is 0)
                        mobile: false        // trigger animations on mobile devices (true is default)
                    }
            );
            wow.init();




// transparent header on scroll 
    $(window).scroll(function() {
      if ($(this).scrollTop() > 100) {
        $('.no-bg').addClass('scroll-bg');
      } else {
        $('.no-bg').removeClass('scroll-bg');
      }
    });