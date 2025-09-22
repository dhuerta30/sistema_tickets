<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Generar Ticket</title>
  <link href='<?php echo htmlspecialchars($_ENV["BASE_URL"], ENT_QUOTES, 'UTF-8'); ?>css/sweetalert2.min.css' rel="stylesheet">
</head>
<body>
  
<style>
  body {
    background: #f3f3f3!important;
  }

  .select2-container {
    width: 100%!important;
  }

  body { 
    overflow-x: hidden;
  }

  /* Ocultamos el input original */
  #fileInput {
    display: none;
  }

  /* Estilo del botón */
  .btn-foto {
    display: inline-block;
    padding: 10px 20px;
    background-color: #28a745;
    color: white;
    font-size: 16px;
    border-radius: 8px;
    cursor: pointer;
    text-align: center;
  }
  .btn-foto:hover {
    background-color: #218838;
  }
</style>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-6">

        <div class="card mt-5">
          <div class="card-header bg-info text-white text-center">Genera tu Ticket</div>
          <div class="card-body"><?php echo $render; ?> <?php echo $select2; ?></div>
        </div>

    </div>
  </div>
</div>

<div id="artify-ajax-loader">
    <img width="300" src='<?php echo htmlspecialchars($_ENV["BASE_URL"], ENT_QUOTES, 'UTF-8'); ?>app/libs/artify/images/ajax-loader.gif' class="artify-img-ajax-loader"/>
</div>
<script src='<?php echo htmlspecialchars($_ENV["BASE_URL"], ENT_QUOTES, 'UTF-8'); ?>js/sweetalert2.all.min.js'></script>
<script>
  $(document).ready(function(){
    $(".fallas").empty();
    $(".fallas").html("<option>Seleccionar</option>");
  });
</script>
<script>
    $(document).on("artify_after_submission", function(event, obj, data) {
      let json = JSON.parse(data);

      $(".alert-success").hide();
      $(".alert-danger").hide();
      if (json.message) {
        Swal.fire({
          icon: "success",
          text: json["message"],
          confirmButtonText: "Aceptar",
          allowOutsideClick: false
        }).then((result) => {
          if (result.isConfirmed) {
            $(".artify-back").click();
            $(".nombre").val("");
            $(".correo").val("");
            $(".area").val("");
            $(".fallas").val("");
            $(".sector_funcionario").select2("destroy");
            $(".sector_funcionario").val("");
            $(".descripcion").val("");
            $(".foto").val("");
            $(".fallas").empty("");
            $(".fallas").html("<option>Seleccionar</option>");
            $(".sector_funcionario").select2();
          }
        });
      }
    });
</script>
</body>
</html>