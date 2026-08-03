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
    background:
    linear-gradient(
        135deg,
        #e3f2fd,
        #ffffff
    )!important;
}
body { 
  overflow-x: hidden;
}
.ticket-wrapper {
    min-height:100vh;
    display:flex;
    justify-content:center;
    padding:40px 15px;
}
.ticket-card {
    width:850px;
    background:white;
    border-radius:20px;
    box-shadow:
    0 10px 35px rgba(0,0,0,.15);
    overflow:hidden;
}
.ticket-header {
    background:
    linear-gradient(
        135deg,
        #0288d1,
        #01579b
    );
    color:white;
    text-align:center;
    padding:35px;
}
.ticket-icon {
    width:75px;
    height:75px;
    background:white;
    color:#0288d1;
    border-radius:50%;
    margin:auto;
    display:flex;
    justify-content:center;
    align-items:center;
    font-size:35px;
}
.ticket-header h3 {
    margin-top:15px;
    font-weight:700;
}
.ticket-header p {
    opacity:.9;
}
.ticket-body {
    padding:35px;
}
.form-section {
    background:#f8f9fa;
    border-radius:15px;
    padding:20px;
    margin-bottom:20px;
    border-left:5px solid #0288d1;
}
.form-section h6 {
  font-weight:700;
  color:#37474f;
  margin-bottom:20px;
}
.form-section h6 i {
  color:#0288d1;
  margin-right:8px;
}
.form-control {
  border-radius:10px;
  min-height:42px;
}
.select2-container {
  width:100%!important;
}
.select2-selection {
  border-radius:10px!important;
  min-height:30px!important;
}
textarea.form-control {
  min-height:120px;
}
.btn-primary {
  width:100%;
  height:50px;
  border-radius:12px;
  font-size:17px;
  font-weight:600;
}
.btn-primary:hover {
  transform:translateY(-1px);
}
</style>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-8">

        <?php echo $render; ?> <?php echo $select2; ?>

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
      } else {
        Swal.fire({
          icon: "error",
          text: json["error"],
          confirmButtonText: "Aceptar",
          allowOutsideClick: false
        });
      }
    });
</script>
</body>
</html>