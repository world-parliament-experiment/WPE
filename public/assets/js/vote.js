'use strict';

(function(window, $, Routing) {

    window.VoteApp = function ($wrapper) {
        this.$wrapper = $wrapper;

        this.loadContent();

        this.$wrapper.on(
            'submit',
            'form[name=app_bundle_future_vote_form]',
            this.handleFutureVoteFormSubmit.bind(this)
        );

        this.$wrapper.on(
            'submit',
            'form[name=app_bundle_current_vote_form]',
            this.handleCurrentVoteFormSubmit.bind(this)
        );

        this.$wrapper.on(
            'click',
            '.voteBtn',
            this.handleVoting.bind(this)
        );

    };

    $.extend(window.VoteApp.prototype, {
        _storage: {
            submitButton: null,
            spinner: '<i class="fas fa-spinner fa-spin fa-3x fa-fw"></i>'
        },
        loadContent: function() {

            console.log('load vote content...');

            var self = this;
            var id = self.$wrapper.data("id");
            var slug = self.$wrapper.data("slug");
            var url = Routing.generate('initiative_show_vote', {'id': id, 'slug': slug});

            self.$wrapper.html(self._storage.spinner);

            $.ajax({
                url: url
            }).then(function(response) {
                console.log(response);
                if (response.success) {
                    if (response.data.type === 'message') {
                        self._renderTemplate('#js-vote-message-template', response.data);
                    } else if (response.data.type === 'countdown') {
                        self._renderTemplate('#js-vote-counter-template', response.data);
                        self._startCountdown();
                    } else if (response.data.type === 'form_future') {
                        self._renderTemplate('#js-vote-future-form-template', response.data);
                        self._endCountdown();
                    } else if (response.data.type === 'form_current') {
                        self._renderTemplate('#js-vote-current-form-template', response.data);
                        self._endCountdown();
                    } else if (response.data.type === 'info') {
                        self._renderTemplate('#js-vote-info-template', response.data);
                        self._endCountdown();
                    }
                }
                throw "Something went wrong. Please try again later!";
                // self.$wrapper.html(data);
            }).catch(function(jqXHR) {
                var data = JSON.parse(jqXHR.responseText);
                console.log(data);
                self.$wrapper.html(data.message);
            })
        },
        handleFutureVoteFormSubmit: function (e) {
            e.preventDefault();
            console.log('start submitting future vote...');

            var self = this;
            var id = self.$wrapper.data("id");

            var $form = $(e.currentTarget);

            var formData = {};

            $.each($form.serializeArray(), function(key, fieldData) {
                formData[fieldData.name] = fieldData.value
            });

            if (self._storage.submitButton) {
                formData[self._storage.submitButton] = '';
            }

            var url = Routing.generate('initiative_vote_future', {'id': id});

            console.log(url);
            console.log(formData);

            self.$wrapper.html(self._storage.spinner);

            $.ajax({
                type: 'post',
                url: url,
                data: formData
            }).then(function(data) {
                self.$wrapper.html('<p>' + data.message + '</p>');
            }).catch(function(data) {
                console.log(data);
            });

        },
        handleCurrentVoteFormSubmit: function (e) {
            e.preventDefault();
            console.log('start submitting current vote...');

            var self = this;
            var id = self.$wrapper.data("id");

            var $form = $(e.currentTarget);

            var formData = {};

            $.each($form.serializeArray(), function(key, fieldData) {
                formData[fieldData.name] = fieldData.value
            });

            if (self._storage.submitButton) {
                formData[self._storage.submitButton] = '';
            }

            var url = Routing.generate('initiative_vote_current', {'id': id});

            console.log(url);
            console.log(formData);

            $.ajax({
                type: 'post',
                url: url,
                data: formData
            }).then(function(data) {
                self.$wrapper.html('<p>' + data.message + '</p>');
            }).catch(function(data) {
                console.log(data);
            });

        },
        handleVoting: function (e) {

            e.preventDefault();

            console.log('start voting...');

            var $btn = $(e.currentTarget);
            var self = this;

            var $form = $btn.closest('form');

            if($btn.attr('name')) {

                console.log('clicked: ' + $btn.attr('name'));
                var title = $btn.data("title");

                $.confirm({
                    title: false,
                    columnClass: 'medium',
                    content: '<p>You will vote with ' +
                        '<strong style="font-size: 20px;"> ' + title + ' </strong>' +
                        ' if you proceed.</p>',
                    buttons: {
                        vote: {
                            text: '<i class="fas fa-vote-yea fa-fw"></i> vote',
                            keys: ['y', 'enter'],
                            btnClass: 'btn-success',
                            action: function () {
                                self._storage.submitButton = $btn.attr('name');
                                $form.submit();
                            }
                        },
                        cancel: {
                            text: '<i class="fas fa-times fa-fw"></i> cancel',
                            keys: ['N'],
                            btnClass: 'btn-danger'
                        }
                    }
                });
            }

        },

        _startCountdown: function() {
            $('[data-countdown]').each(function() {
                var $this = $(this), finalDate = $(this).data('countdown');
                $this.countdown(finalDate, function(event) {
                    $this.html(event.strftime('The Voting starts in '
                        + '<span>%-w</span> week%!w '
                        + '<span>%-d</span> day%!d '
                        + '<span>%H</span> hr '
                        + '<span>%M</span> min '
                        + '<span>%S</span> sec'
                        + '.'
                    ));
                });
            });
        },
        _endCountdown: function() {
            $('[data-countdown]').each(function() {
                var $this = $(this), finalDate = $(this).data('countdown');
                $this.countdown(finalDate, function(event) {
                    $this.html(event.strftime('The Voting ends in '
                        + '<span>%-w</span> week%!w '
                        + '<span>%-d</span> day%!d '
                        + '<span>%H</span> hr '
                        + '<span>%M</span> min '
                        + '<span>%S</span> sec'
                        + '.'
                    ));
                });
            });
        },
        _renderTemplate: function(template, data) {
            var self = this;
            var tplText = $(template).html();
            var tpl = _.template(tplText);
            var html = tpl(data);
            self.$wrapper.html(html);
        }
    });

})(window, jQuery, Routing);