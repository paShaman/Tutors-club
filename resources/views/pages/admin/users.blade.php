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
                        url: "/admin/user/list",
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
                { name: "email", type: "text", width: 100 },
                { name: "account", type: "text", filtering: false, width: 50 },
                { name: "first_name", type: "text" },
                { name: "last_name", type: "text" },
                { name: "middle_name", type: "text" },
                { name: "roles", type: "text", filtering: false, sorting: false},
                { name: "force_login", type: "text", sorting: false, filtering: false, width: 50,
                    itemTemplate: function(value) {
                        return '<a class="<?=App\Common::BTN?> btn-sm btn-primary" href="'+ value +'" target="_blank"><i class="fa fa-door-open"></i></a>';
                    }
                },
            ]
        });

    });
</script>