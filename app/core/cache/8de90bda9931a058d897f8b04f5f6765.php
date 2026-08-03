<?php include '/home/hospitaldem/public_html/sistema_tickets/app/core/cache/8a64a4f37db2e0589ae7310721965099.php'; ?>
<?php include '/home/hospitaldem/public_html/sistema_tickets/app/core/cache/4478e7d1813936c227a256f8ae72001c.php'; ?>
<div class="content-wrapper">
    <section class="content">
        <div class="card">
            <div class="card-body">

                <div class="row">
                    <div class="col-md-3">
                        <div class="card p-3 upload_avatar">
                            <?php $isset = isset($_SESSION["usuario"][0]["avatar"]); ?>

                            <?php if (!$isset): ?>
                                <img class="w-100 avatar" src='<?php echo htmlspecialchars($_ENV["BASE_URL"], ENT_QUOTES, 'UTF-8'); ?>theme/img/avatar.jpg' class="card-img-top">
                            <?php else: ?>
                                <img class="w-100 avatar" src='<?php echo htmlspecialchars($_ENV["BASE_URL"], ENT_QUOTES, 'UTF-8'); ?>app/libs/artify/uploads/<?php echo htmlspecialchars($_SESSION["usuario"][0]["avatar"], ENT_QUOTES, 'UTF-8'); ?>' class="card-img-top">
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-9">
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
<script>
    $(document).on("artify_after_submission", function(event, obj, data) {
      $.ajax({
        type: "POST",
        url: '<?php echo htmlspecialchars($_ENV["BASE_URL"], ENT_QUOTES, 'UTF-8'); ?>generar_datos_usuario',
        dataType: "json",
        success: function(response) {
          console.log(response);
          $('.nombre_usuario').text(response['usuario'][0]["nombre"]);
          $(".avatar").attr('src', '<?php echo htmlspecialchars($_ENV["BASE_URL"], ENT_QUOTES, 'UTF-8'); ?>app/libs/artify/uploads/' + response['usuario'][0]['avatar']);
        }
      });
    });
</script>
<?php include '/home/hospitaldem/public_html/sistema_tickets/app/core/cache/eb33b26ce860a017f36edb2761467684.php'; ?>