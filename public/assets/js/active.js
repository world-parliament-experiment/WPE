(function ($) {
  "use strict";
  console.log($('#submitPhoneNumberForm'));

  var browserWindow = $(window);
  var countdown;
  var duration = 60;
  // :: 1.0 Preloader Active Code
  browserWindow.on("load", function () {
    $(".preloader").fadeOut("slow", function () {
      $(this).remove();
    });
  });

  // :: 2.0 Nav Active Code
  if ($.fn.classyNav) {
    $("#magNav").classyNav();
  }

  // :: 3.0 Sticky Active Code
  if ($.fn.sticky) {
    $("#sticker").sticky({
      topSpacing: 0,
    });
  }

  // :: 4.0 Sliders Active Code
  if ($.fn.owlCarousel) {
    var welcomeSlides = $(".hero-area");

    welcomeSlides.owlCarousel({
      items: 1,
      margin: 0,
      loop: true,
      nav: true,
      navText: [
        '<i class="fas fa-angle-left"></i>',
        '<i class="fas fa-angle-right"></i>',
      ],
      dots: false,
      autoplay: true,
      autoplayTimeout: 5000,
      smartSpeed: 1000,
    });

    welcomeSlides.on("translate.owl.carousel", function () {
      var slideLayer = $("[data-animation]");
      slideLayer.each(function () {
        var anim_name = $(this).data("animation");
        $(this)
          .removeClass("animated " + anim_name)
          .css("opacity", "0");
      });
    });

    welcomeSlides.on("translated.owl.carousel", function () {
      var slideLayer = welcomeSlides
        .find(".owl-item.active")
        .find("[data-animation]");
      slideLayer.each(function () {
        var anim_name = $(this).data("animation");
        $(this)
          .addClass("animated " + anim_name)
          .css("opacity", "1");
      });
    });

    $("[data-delay]").each(function () {
      var anim_del = $(this).data("delay");
      $(this).css("animation-delay", anim_del);
    });

    $("[data-duration]").each(function () {
      var anim_dur = $(this).data("duration");
      $(this).css("animation-duration", anim_dur);
    });

    $(".trending-post-slides").owlCarousel({
      items: 3,
      margin: 30,
      loop: true,
      nav: true,
      navText: [
        '<i class="fas fa-angle-left"></i>',
        '<i class="fas fa-angle-right"></i>',
      ],
      dots: false,
      autoplay: true,
      autoplayTimeout: 4000,
      smartSpeed: 1000,
      responsive: {
        0: {
          items: 1,
        },
        992: {
          items: 2,
        },
        1500: {
          items: 3,
        },
      },
    });

    $(".featured-video-posts-slide").owlCarousel({
      items: 1,
      margin: 0,
      loop: true,
      nav: true,
      navText: [
        '<i class="ti-angle-left"></i>',
        '<i class="ti-angle-right"></i>',
      ],
      dots: false,
      autoplay: true,
      autoplayTimeout: 4000,
      smartSpeed: 1000,
    });

    $(".most-viewed-videos-slide").owlCarousel({
      items: 3,
      margin: 30,
      loop: true,
      nav: true,
      navText: [
        '<i class="fas fa-angle-left"></i>',
        '<i class="fas fa-angle-right"></i>',
      ],
      dots: false,
      autoplay: true,
      autoplayTimeout: 4000,
      smartSpeed: 1000,
      responsive: {
        0: {
          items: 1,
        },
        992: {
          items: 2,
        },
        1500: {
          items: 3,
        },
      },
    });

    $(".sports-videos-slides").owlCarousel({
      items: 2,
      margin: 30,
      loop: true,
      nav: true,
      navText: [
        '<i class="fas fa-angle-left"></i>',
        '<i class="fas fa-angle-right"></i>',
      ],
      dots: false,
      autoplay: true,
      autoplayTimeout: 4000,
      smartSpeed: 1000,
      responsive: {
        0: {
          items: 1,
        },
        992: {
          items: 2,
        },
        1200: {
          items: 1,
        },
        1500: {
          items: 2,
        },
      },
    });
  }

  // :: 5.0 ScrollUp Active Code
  if ($.fn.scrollUp) {
    browserWindow.scrollUp({
      scrollSpeed: 1500,
      scrollText: '<i class="fas fa-angle-up"></i>',
    });
  }

  // :: 6.0 Tooltip Active Code
  if ($.fn.tooltip) {
    $('[data-toggle="tooltip"]').tooltip();
  }

  // :: 7.0 Prevent Default a Click
  $('a[href="#"]').on("click", function (e) {
    e.preventDefault();
  });

  // :: 8.0 Wow Active Code
  if (browserWindow.width() > 767) {
    new WOW().init();
  }

  function startCountdown() {
    console.log('This is after load');
    var timerElement = $("#timer");
    var buttonElement = '<b><a id="resendButton" href="">resend otp</a></b>';

    timerElement.text("(" + duration + "s)");

    countdown = setInterval(function () {
      duration--;
      timerElement.text("(" + duration + "s)");

      if (duration <= 0) {
        clearInterval(countdown);
        $("#timerDiv").append(buttonElement.toString());
        timerElement.text("");
      }
    }, 1000);
  }
  $("#changeNumber").click(function () {
    var currentHtml = $(this).html();

    var newHtml = currentHtml === "update" ? "Change" : "update";
    $(this).html(newHtml);

    var disabled = $("#verify_form_mobileNumber").prop("readonly");
    disabled = !disabled;
    $("#verify_form_mobileNumber").prop("readonly", disabled);
  });


  $(document).on('submit','#submitPhoneNumberForm',function (e) {
    console.log('LOaded');
    e.preventDefault();
    var formData = $("#submitPhoneNumberForm").serialize();
    var action = $("#submitPhoneNumberForm").attr('action');
    $.ajax({
      url: action,
      async:false,
      method: "POST",
      data: formData,
      success: function (response) {
        $(".toast").toast("show");
        $("#verify_form_mobileNumber").val(" #verify_form_mobileNumber");
        if (duration > 0) {
          clearInterval(countdown);
          duration = 60;
          startCountdown();
        }
      },
      error: function (xhr, status, error) {
        console.error(error);
      },
    });

    return false;
  });

  $("#resendButton").click(function (e) {
    e.preventDefault();
    window.location.href = "/otp/get-otp";
    $(".toast").toast("show");
    $("#submitOtpVerify").prop("disabled", false);
    startCountdown();
  });

  startCountdown();
})(jQuery);
