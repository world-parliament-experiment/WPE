'use strict';

(function (window, $, Routing) {

    window.SocialApp = function ($wrapper) {
        this.$wrapper = $wrapper;

        this.init();

        console.log(this._storage);
    };

    $.extend(window.SocialApp.prototype, {
        _storage: {
            pageTitle: '',
            pageUrl: ''
        },
        init: function () {

            var self = this;

            var $title = $('meta[property="og:title"]');
            var $url = $('meta[property="og:url"]');

            if ($title !== null) self._storage.pageTitle = $title.attr('content') == undefined ? document.title : encodeURIComponent($title.attr('content'));
            if ($url !== null) self._storage.pageUrl = $url.attr('content') == undefined ? window.location.href : encodeURIComponent($url.attr('content'));

            var share = {
                facebook: {
                    link: ".js-share-facebook",
                    share: function () {
                        self._share("facebook", "https://www.facebook.com/sharer.php?u={pageURL}&t={text}", !0)
                    }
                        .bind(self)
                },
                google: {
                    link: ".js-share-google",
                    share: function () {
                        self._share("google", "https://plus.google.com/share?url={pageURL}", !1)
                    }
                        .bind(self)
                },
                reddit: {
                    link: ".js-share-reddit",
                    share: function () {
                        self._share("reddit", "https://ssl.reddit.com/submit?url={pageURL}", !1)
                    }
                        .bind(self)
                },
                twitter: {
                    link: ".js-share-twitter",
                    share: function () {
                        self._share("twitter", "https://twitter.com/share?url={pageURL}&text={text}", !1)
                    }
                        .bind(self)
                },
                linkedIn: {
                    link: ".js-share-linkedin",
                    share: function () {
                        self._share("linkedIn", "https://www.linkedin.com/cws/share?url={pageURL}", !1)
                    }
                        .bind(self)
                },
                pinterest: {
                    link: ".js-share-pinterest",
                    share: function () {
                        self._share("pinterest", "https://www.pinterest.com/pin/create/link/?url={pageURL}&description={text}", !1)
                    }
                        .bind(self)
                },
                xing: {
                    link: ".js-share-xing",
                    share: function () {
                        self._share("xing", "https://www.xing.com/social_plugins/share?url={pageURL}", !1)
                    }
                        .bind(self)
                },
                whatsApp: {
                    link: ".js-share-whatsapp",
                    share: function () {
                        window.location.href = "whatsapp://send?text=" + self._storage.pageTitle + "%20" + self._storage.pageUrl
                    }
                        .bind(self)
                }
            };

            for (var provider in share) {
                if (share.hasOwnProperty(provider) && null !== share[provider].link) {
                    self.$wrapper.on(
                        'click',
                        share[provider].link,
                        share[provider].share
                    );
                }
            }


        },
        _share: function (e, t, i) {
            var self = this;
            window.open(t.replace(/\{pageURL}/, self._storage.pageUrl).replace(/\{text}/, self._storage.pageTitle + (i ? "%20" + self._storage.pageUrl : "")), e, "height=600,width=600");
        }
    });

})(window, jQuery, Routing);