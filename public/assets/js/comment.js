'use strict';

(function (window, $, Routing) {

    var nextReplyIdx = 0;

    window.CommentApp = function ($wrapper) {
        this.$wrapper = $wrapper;

        this.$wrapper.on(
            'submit',
            'form[name=app_bundle_comment_form]',
            this.handleCommentFormSubmit.bind(this)
        );

        this.$wrapper.on(
            'click',
            'a.reply',
            this.handleReplyButtonClick.bind(this)
        );

        this.$wrapper.on(
            'click',
            'a.like',
            this.handleCounterButtonClick.bind(this)
        );

        this.$wrapper.on(
            'submit',
            'form[name=app_bundle_comment_reply_form]',
            this.handleReplyFormSubmit.bind(this)
        );

    };

    $.extend(window.CommentApp.prototype, {
        handleCounterButtonClick: function (e) {
            var self = this;
            e.preventDefault();
            console.log('counter button clicked ...', e);
            var button = e.target;
            $.ajax({
                url: $(button).attr("href"),
                method: 'POST'
            }).then(function (data) {
                $(button)
                    .append(" ")
                    .attr("href", "#");
                $("<span>")
                    .addClass("badge")
                    .addClass("badge-dark")
                    .text(data.value)
                    .appendTo(button);
            }).catch(function (data) {
                console.log("failed:", data);
            });
            return false;
        },
        handleReplyButtonClick: function (e) {
            var self = this;
            e.preventDefault();
            console.log('reply button clicked ...', e);
            var button = e.target;
            var hasContent = false;
            $('form.comment-reply textarea').each(function () {
                var data = CKEDITOR.instances[$(this).attr('id')].getData('');
                if (data != '') {
                    hasContent = true;
                    return false;
                }
            });
            if (hasContent) {
                if (!confirm("Close reply field without saving ?")) {
                    return false;
                }
                $('div.comment-reply').remove();
            } else {
                $('div.comment-reply').remove();
            }
            var commentMeta = $(button).parents(".comment-meta").first();
            if ($(commentMeta).find("form.comment-reply").length == 0) { // form already created ?
                var messageId = "app_bundle_comment_form_message_" + nextReplyIdx;
                var data = {}
                data.action = $(button).attr("href");
                data.messageId = messageId;
                data.token = $("#app_bundle_comment_form__token").val();
                nextReplyIdx += 1
                var replyDiv = self._renderTemplate("#js-comment-reply-form-template", data);
                nextReplyIdx += 1;
                $(replyDiv)
                    .appendTo(commentMeta);
                CKEDITOR.replace(messageId, {
                    "toolbar": [["Bold", "Italic"], ["NumberedList", "BulletedList", "-", "Outdent", "Indent"], ["Link", "Unlink"], ["About"]],
                    "uiColor": "#ffffff",
                    "language": "en"
                });
            }
            return false;
        },
        handleReplyFormSubmit: function (e) {
            e.preventDefault();
            var self = this;
            console.log('start submitting current reply...');
            var form = e.target;
            var singleCommentArea = $(form).parents(".single_comment_area").first();
            console.log("singleCommentArea1", singleCommentArea);
            $.ajax({
                url: $(form).attr("action"),
                data: $(form).serialize(),
                method: 'POST'
            }).then(function (data) {
                console.log("form", form);
                console.log(data);
                self._removeFormErrors(form);
                if (data.status) {
                    var subCommentList = $(singleCommentArea).find("ol.comment_area").first();
                    console.log("subCommentList", subCommentList);
                    if (subCommentList.length == 0) {
                        subCommentList = $("<ol>")
                            .addClass("comment_area")
                            .addClass("children");
                        $(subCommentList).appendTo(singleCommentArea);
                    }
                    var subSingleCommentArea = $("<li>")
                        .addClass("single_comment_area");
                    var commentDiv = self._renderTemplate("#js-comment-reply-block-template", data);

                    $(subSingleCommentArea)
                        .append(commentDiv)
                        .appendTo(subCommentList);

                    $(form).parent().remove();
                } else {
                    console.log("mapping errors:", data.errors);
                    self._mapErrorsToForm(data.errors, form);
                }
            }).catch(function (data) {
                console.log("failed:", data);
            })
            return false;

        },
        handleCommentFormSubmit: function (e) {
            e.preventDefault();
            var self = this;
            console.log('start submitting comment...');
            var form = e.target;
            $.ajax({
                url: $(form).attr("action"),
                data: $(form).serialize(),
                method: 'POST'
            }).then(function (data) {
                console.log("form", form);
                console.log(data);
                self._removeFormErrors(form);
                if (data.status) {
                    var commentDiv = self._renderTemplate("#js-comment-root-block-template", data);
                    var subSingleCommentArea = $("<li>")
                        .addClass("single_comment_area");
                    $(subSingleCommentArea)
                        .append(commentDiv)
                        .appendTo("ol.comment_area_level_0");
                    CKEDITOR.instances["app_bundle_comment_form_message"].setData('');
                } else {
                    console.log("data:", data);
                    self._mapErrorsToForm(data.errors, form);
                }
            }).catch(function (data) {
                console.log("failed:", data);
            });
            return false;

        },
        _renderTemplate: function (template, data) {
            var self = this;
            var tplText = $(template).html();
            var tpl = _.template(tplText);
            var html = tpl(data);
            return html;
        },
        _mapErrorsToForm: function (errorData, form) {

            this._removeFormErrors(form);

            $(form).find('textarea').each(function () {
                console.log("field:", this);

                var fieldName = $(this).attr('name').replace('app_bundle_comment_form[', '').replace(']', '');

                var $wrapper = $(this).closest('.form-group');

                if (!errorData[fieldName]) {

                    // no error!

                    return;

                }

                $(this).addClass('is-invalid');

                var error = $('<div class="invalid-feedback"></div>');

                error.html(errorData[fieldName]);
                $(this).parent().append(error);

                $wrapper.addClass('has-error');

            });

        },


        _removeFormErrors: function (form) {

            $(form).find('div.invalid-feedback').remove();

            $(form).find('textarea').removeClass('is-invalid');

            $(form).find('.form-group').removeClass('has-error');

        }
    });

})(window, jQuery, Routing);