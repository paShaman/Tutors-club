<div class="border-bottom pb-3 mb-3">
    <a href="#" class="btn waves-effect waves-light btn-primary" data-toggle="modal" data-target="#modalAdminAddPayment"><i class="fa fa-coins"></i> Внести оплату</a>

    {!! $modalAdminAddPayment !!}
</div>

<div class="users-payments-grid"></div>

<script>
    $(function() {

        $(".users-payments-grid").jsGrid({
            height: "auto",
            width: "100%",

            paging: true,
            autoload: true,
            pageLoading: true,
            filtering: true,

            controller: {
                loadData: function(filter) {
                    var d = $.Deferred();

                    $.ajax({
                        url: "/admin/payment/list",
                        dataType: "json",
                        data: filter
                    }).done(function(response) {
                        d.resolve(response.data);
                    });

                    return d.promise();
                }
            },

            fields: [
                { name: "created_at", type: "text", width: 100, filtering: false},
                { name: "amount", type: "text", width: 100, filtering: false },
                { name: "id", type: "text" },
                { name: "user", type: "text" },
                { name: "charged_id", type: "text" },
                { name: "charged_user", type: "text" },
                { name: "reason", type: "text"},
                { name: "external_payment_id", type: "text"},
            ]
        });

    });
</script>