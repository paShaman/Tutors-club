<div class="users-grid"></div>

<script>
    $(function() {

        $(".users-grid").jsGrid({
            height: "auto",
            width: "100%",

            sorting: true,
            paging: true,
            autoload: true,
            pageLoading: true,
            filtering: true,

            controller: {
                loadData: function(filter) {
                    var d = $.Deferred();

                    $.ajax({
                        url: "/admin/users-list",
                        dataType: "json",
                        data: filter
                    }).done(function(response) {
                        d.resolve(response.data);
                    });

                    return d.promise();
                }
            },

            fields: [
                { name: "id", type: "text", width: 50 },
                { name: "avatar", type: "text", width: 50, filtering: false,
                    itemTemplate: function(value) {
                        return '<span class="avatar" style="background-image: url(' + value + ')"></span>';
                    }
                },
                { name: "email", type: "text" },
                { name: "email_verified_at", type: "text", filtering: false, },
                { name: "first_name", type: "text" },
                { name: "last_name", type: "text" },
                { name: "middle_name", type: "text" },
                { name: "force_login", type: "text", sorting: false, filtering: false,
                    itemTemplate: function(value) {
                        return '<a class="btn waves-effect waves-light btn-sm btn-primary" href="'+ value +'" target="_blank">Авторизоваться</a>';
                    }
                },
            ]
        });

    });
</script>