var App = (function () {

    var instance;

    function createInstance() {
        return new MainApp($("body"));
    }

    return {
        getInstance: function () {
            if (!instance) {
                instance = createInstance();
            }
            return instance;
        }
    };

})();

(function (window, $, Routing) {

    window.MainApp = function ($wrapper) {

        this.$wrapper = $wrapper;

        this.init();

        this.$wrapper.on(
            'click',
            this._selectors.favouriteButton,
            this.handleFavourites.bind(this)
        );

        this.$wrapper.on(
            'click',
            this._selectors.friendButton,
            this.handleFriends.bind(this)
        );

    };

    $.extend(window.MainApp.prototype, {
        _selectors: {
            favouriteButton: '.btn-favourite',
            friendButton: '.btn-friend'
        },
        init: function () {
            console.log('initialize App...');
            this.handleCookieConsent();

        },
        handleCookieConsent: function() {

            window.cookieconsent.initialise({
                "palette": {
                    "popup": {
                        "background": "#000"
                    },
                    "button": {
                        "background": "#f1d600"
                    }
                },
                "theme": "classic",
                "position": "bottom-left",
                "showLink": false
            });

        },
        handleFavourites: function (e) {

            e.preventDefault();

            var $button = $(e.currentTarget);
            var id = $button.attr("data-initiative-id");

            var self = this;

            $.ajax({
                url: Routing.generate('user_favourite', {'id': id}),
                method: 'GET'
            }).then(function (data) {
                console.log(data.message);
                var $item = $('.btn-favourite[data-initiative-id=' + id + ']');
                if (data.next === 'add') {
                    $item.html('<i class="far fa-bookmark"></i>');
                    $item.attr('title', 'Bookmark this initiative');
                } else {
                    $item.html('<i class="fas fa-bookmark"></i>');
                    $item.attr('title', 'Delete Bookmark');
                }
            }).catch(function (data) {
                console.log("failed:", data);
            });
            return false;

        },
        handleFriends: function (e) {

            e.preventDefault();

            var $button = $(e.currentTarget);
            var id = $button.attr("data-user-id");

            var self = this;

            $.ajax({
                url: Routing.generate('user_friend', {'id': id}),
                method: 'GET'
            }).then(function (data) {
                console.log(data.message);
                var $item = $('.btn-friend[data-user-id=' + id + ']');
                if (data.next === 'add') {
                    $item.html('<i class="far fa-heart"></i>');
                    $item.attr("title", "Add as friend");
                } else {
                    $item.html('<i class="fas fa-heart"></i>');
                    $item.attr("title", "Remove as friend");
                }
            }).catch(function (data) {
                console.log("failed:", data);
            });
            return false;

        },
        drawParliament: function ($p_wrapper) {

            var self = this;

            var positions = [
                [-21, 69], [-12, 54], [0, 48], [12, 54], [21, 69],
                [-25, 51], [-16, 38], [-6, 31], [6, 31], [16, 38],
                [25, 51], [-37, 51], [-30, 35], [-21, 22], [-11, 15],
                [0, 12], [11, 15], [21, 22], [30, 35], [37, 51],
                [32, 70], [-32, 70], [42, 70], [-42, 70], [25, 91],
                [-25, 91], [35, 91], [-35, 91], [44, 91], [-44, 91]
            ];

            var width = $p_wrapper.width();
            var height = $p_wrapper.width() / 2;

            $.ajax({
                url: Routing.generate('parliament_members'),
                method: 'POST'
            }).then(function (data) {

                console.log(data.success);

                if (data.success === true) {

                    var members = data.data;

                    positions.forEach(function (position, index) {
                        tx = (position[0] * width / 100 );
                        ty = position[1] * height / 100;
                        if (members[index] === undefined) {
                            if (index < 20) {
                                $p_wrapper.append("<div class='circle' style='transform: translate(" + tx + "px, " + ty + "px'></div>");
                            } else {
                                $p_wrapper.append("<div class='circle circle-empty' style='transform: translate(" + tx + "px, " + ty + "px'></div>");
                            }
                        } else {
                            id = members[index].id;
                            text = members[index].username + " (" + members[index].score + " delegation score)";
                            div =
                                "<div class='circle' style='transform: translate(" + tx + "px, " + ty + "px'>" +
                                "<a href='"+ Routing.generate('user_profile_show', {"id": id}) +"'>" +
                                "<img src='" + Routing.generate('user_profile_avatar', {"id": id}) + "' alt='avatar' title='"  + text + "'>" +
                                "</a>" +
                                "</div>";
                            $p_wrapper.append(div);
                        }
                    });


                }
            }).catch(function (data) {
                console.log("failed:", data);
            });

            return false;

        },
        configureDTCategory: function ($table_wrapper) {
            var table = $table_wrapper.DataTable({
                "responsive": false,
                "autoWidth": true,
                "processing": true,
                "serverSide": true,
                "searching": true,
                "ordering": true,
                "ajax": {
                    "url": Routing.generate('category_type_search', {
                        "id": $table_wrapper.data("category-id"),
                        "slug": $table_wrapper.data("category-slug"),
                        "type": $table_wrapper.data("type")
                    }),
                    "dataSrc": "items",
                    "type": "POST"
                },
                "columns": [
                    {
                        "data": "title",
                        "fnCreatedCell": function (nTd, sData, oData, iRow, iCol) {
                            $(nTd).html("<a href='" + Routing.generate('initiative_show', {
                                id: oData.id,
                                slug: oData.slug
                            }) + "'>" + oData.title + "</a>");
                        }
                    },
                    {
                        "data": "createdBy.username",
                        "width": "25%",
                        "fnCreatedCell": function (nTd, sData, oData, iRow, iCol) {
                            $(nTd).html("<a href='" + Routing.generate('user_profile_show', {id: oData.createdBy.id}) + "'><img src='" + Routing.generate('user_profile_avatar', {id: oData.createdBy.id}) + "' class='small-user-avatar mr-2' >" + oData.createdBy.username + "</a>");
                        }
                    },
                    {
                        "data": "createdAt",
                        "width": "15%"
                    },
                    {
                        "data": "voteStatus",
                        "orderable": false,
                        "className": "text-center",
                        "width": "5%",
                        "fnCreatedCell": function (nTd, sData, oData) {
                            if (oData.voteStatus === "now") {
                                $(nTd).html("<span class=\"badge badge-danger\"> NOW </span>");
                            } else if (oData.voteStatus === "soon") {
                                $(nTd).html("<span class=\"badge badge-warning\"> SOON </span>");
                            } else {
                                $(nTd).html("&nbsp;");
                            }
                        }
                    }
                ],
                "order": [[0, "asc"]],
                "deferRender": true,
                "stateSave": true,
                "stateDuration": 60 * 60 * 24,
                "stateSaveCallback": function (settings, data) {
                    localStorage.setItem('WPE_DataTables_Category_Overview', JSON.stringify(data))
                },
                "stateLoadCallback": function (settings) {
                    return JSON.parse(localStorage.getItem('WPE_DataTables_Category_Overview'))
                }
            });

        },
        configureDTAssembly: function ($table_wrapper) {
            var table = $table_wrapper.DataTable({
                "responsive": false,
                "autoWidth": true,
                "processing": true,
                "serverSide": true,
                "searching": true,
                "ordering": true,
                "ajax": {
                    "url": Routing.generate('assembly_search'),
                    "dataSrc": "items",
                    "type": "POST"
                },
                "columns": [
                    {
                        "data": "id",
                        "fnCreatedCell": function (nTd, sData, oData, iRow, iCol) {
                            $(nTd).html("<img src='" + Routing.generate('user_profile_avatar', {'id': oData.id}) + "' class='user-avatar' />");
                        }
                    },
                    {
                        "data": "username",
                        "fnCreatedCell": function (nTd, sData, oData, iRow, iCol) {
                            $(nTd).html("<a href='" + Routing.generate('user_profile_show', {'id': oData.id}) + "'>" + oData.username + "</a>");
                        }
                    },
                    {
                        "data": "country",
                        "className": "text-center",
                        "width": "10%",
                        "fnCreatedCell": function (nTd, sData, oData, iRow, iCol) {
                            $(nTd).html("<span class='flag-icon flag-icon-" + oData.country.toLowerCase() + "' title='" + oData.country + "'></span>");
                        }
                    },
                    {
                        "data": "city",
                        "width": "25%"
                    },
                    {
                        "data": "registeredAt",
                        "width": "15%"
                    }
                ],
                "columnDefs": [{
                    "targets": 'no-sort',
                    "orderable": false,
                }],
                "order": [[1, "asc"]],
                "deferRender": true,
                "stateSave": true,
                "stateDuration": 60 * 60 * 24,
                "stateSaveCallback": function (settings, data) {
                    localStorage.setItem('WPE_DataTables_GeneralAssembly', JSON.stringify(data))
                },
                "stateLoadCallback": function (settings) {
                    return JSON.parse(localStorage.getItem('WPE_DataTables_GeneralAssembly'))
                }
            });

        }
    });

})(window, jQuery, Routing);