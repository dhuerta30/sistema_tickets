<?php include '/home/hospitaldem/public_html/sistema_tickets/app/core/cache/8a64a4f37db2e0589ae7310721965099.php'; ?>
<?php include '/home/hospitaldem/public_html/sistema_tickets/app/core/cache/4478e7d1813936c227a256f8ae72001c.php'; ?>
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
<?php include '/home/hospitaldem/public_html/sistema_tickets/app/core/cache/eb33b26ce860a017f36edb2761467684.php'; ?>
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