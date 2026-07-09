<?php include 'C:\laragon\www\sistema_tickets\app\core/cache/b9326fb976c20e14ce6494ea2da1c350.php'; ?>
<?php include 'C:\laragon\www\sistema_tickets\app\core/cache/16f1b347bd6377472a7f0f6b931f21ef.php'; ?>
<link href='<?php echo htmlspecialchars($_ENV["BASE_URL"], ENT_QUOTES, 'UTF-8'); ?>css/sweetalert2.min.css' rel="stylesheet">
<div class="content-wrapper">
    <section class="content">
        <div class="card">
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
<script src='<?php echo htmlspecialchars($_ENV["BASE_URL"], ENT_QUOTES, 'UTF-8'); ?>js/sweetalert2.all.min.js'></script>
<script>
    $(document).ready(function() {
    $(document).on('click', '.export', function(e) {
      e.preventDefault();
      $.ajax({
        type: "POST",
        url: "<?=$_ENV["BASE_URL"]?>export_db",
        dataType: "json",
        beforeSend: function() {
            $("#artify-ajax-loader").show();
        },
        success: function(data) {
          $("#artify-ajax-loader").hide();
          $('#artify_search_btn').click();
            Swal.fire({
                title: "Genial!",
                text: data['success'],
                icon: "success",
                confirmButtonText: "Aceptar"
            });
        },
        error: function() {
            Swal.fire({
                title: "Lo siento!",
                text: 'Error al Exportar',
                icon: "error",
                confirmButtonText: "Aceptar"
            });
        }
      });
    });
  });

  $(document).on("click", ".artify-filter-option-remove, .artify-filter-option", function() {
    $(".artify-filter").val('');
  });

  $(document).on("keyup", "#artify_search_box", function(event) {
    let busqueda = $("#artify_search_box").val();

    if (busqueda == "") {
      $('#artify_search_btn').click();
    }
    
  });
</script>
<?php include 'C:\laragon\www\sistema_tickets\app\core/cache/64a272ea1253c270fb04c399539a8ce1.php'; ?>