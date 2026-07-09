<?php include 'C:\laragon\www\sistema_tickets\app\core/cache/b9326fb976c20e14ce6494ea2da1c350.php'; ?>
<?php include 'C:\laragon\www\sistema_tickets\app\core/cache/16f1b347bd6377472a7f0f6b931f21ef.php'; ?>
<div class="content-wrapper">
    <section class="content">
        <div class="card mt-4">
            <div class="card-body">

                <div class="row">
                    <div class="col-md-12">
                        <?php echo $render; ?>
                    </div>
                </div>

            </div>
        </div>
    </section>
</div>
<div id="artify-ajax-loader">
    <img width="300" src='<?php echo htmlspecialchars($_ENV["BASE_URL"], ENT_QUOTES, 'UTF-8'); ?>app/libs/artify/images/ajax-loader.gif' class="artify-img-ajax-loader"/>
</div>
<?php include 'C:\laragon\www\sistema_tickets\app\core/cache/64a272ea1253c270fb04c399539a8ce1.php'; ?>
<script>
    $(document).on("artify_after_submission", function(event, obj, data) {
        let json = JSON.parse(data);

        if (json.message) {
            Swal.fire({
                icon: "success",
                text: json["message"],
                confirmButtonText: "Aceptar",
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $(".artify-back").click();
                }
            });
        }
    });
</script>