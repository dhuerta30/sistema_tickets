<?php include 'C:\laragon\www\sistema_tickets\app\core/cache/b9326fb976c20e14ce6494ea2da1c350.php'; ?>
<?php include 'C:\laragon\www\sistema_tickets\app\core/cache/16f1b347bd6377472a7f0f6b931f21ef.php'; ?>
<link href='<?php echo htmlspecialchars($_ENV["BASE_URL"], ENT_QUOTES, 'UTF-8'); ?>css/sweetalert2.min.css' rel="stylesheet">
<link rel="stylesheet" href='<?php echo htmlspecialchars($_ENV["BASE_URL"], ENT_QUOTES, 'UTF-8'); ?>css/fancybox.css' />
<div class="content-wrapper">
    <section class="content">
        <div class="card mt-4">
            <div class="card-body">

                <div class="row">
                    <div class="col-md-12">
                        <?php echo $render; ?>
                    </div>
                </div>

                <div class="cargar_modal"></div>

            </div>
        </div>
    </section>
</div>
<div id="artify-ajax-loader">
    <img width="300" src='<?php echo htmlspecialchars($_ENV["BASE_URL"], ENT_QUOTES, 'UTF-8'); ?>app/libs/artify/images/ajax-loader.gif' class="artify-img-ajax-loader"/>
</div>
<?php include 'C:\laragon\www\sistema_tickets\app\core/cache/64a272ea1253c270fb04c399539a8ce1.php'; ?>
<script src='<?php echo htmlspecialchars($_ENV["BASE_URL"], ENT_QUOTES, 'UTF-8'); ?>js/sweetalert2.all.min.js'></script>
<script src='<?php echo htmlspecialchars($_ENV["BASE_URL"], ENT_QUOTES, 'UTF-8'); ?>js/fancybox.umd.js'></script>
<script>
    $(document).on("artify_after_ajax_action", function(event, obj, data){
        var dataAction = obj.getAttribute('data-action');
        var dataId = obj.getAttribute('data-id');

        if(dataAction == "add"){
        
        }

        if(dataAction == "edit"){
        
        }

        if(dataAction == "save_crud_table_data"){

        }
    });
    $(document).on("artify_after_submission", function(event, obj, data) {
        let json = JSON.parse(data);

        if (json.message) {
            $(".alert-success").hide();
            $(".alert-danger").hide();
            $.ajax({
                type: "POST",
                url: '<?php echo htmlspecialchars($_ENV["BASE_URL"], ENT_QUOTES, 'UTF-8'); ?>cargar_imagenes_configuracion',
                dataType: "json",
                success: function(data){
                    console.log(data);
                    $(".logo_login").attr("src", '<?php echo htmlspecialchars($_ENV["URL_ArtifyCrud"], ENT_QUOTES, 'UTF-8'); ?>' + 'artify/uploads/' + data[0].logo_login);
                    $(".logo_panel").attr("src", '<?php echo htmlspecialchars($_ENV["URL_ArtifyCrud"], ENT_QUOTES, 'UTF-8'); ?>' + 'artify/uploads/' + data[0].logo_panel);
                }
            });

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

        if(json.message == "Ticket Completado con éxito"){
            Swal.fire({
                icon: "success",
                text: json["message"],
                confirmButtonText: "Aceptar",
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $("[data-action='refresh']").click();
                    $("#Completar").modal("hide");
                }
            });
        }

        if(json.message == "Ticket Asignado con éxito"){
            Swal.fire({
                icon: "success",
                text: json["message"],
                confirmButtonText: "Aceptar",
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $("[data-action='refresh']").click();
                    $("#Asignar").modal("hide");
                }
            });
        }
    });

    function actualizarHora() {
        const ahora = new Date();
        const horas = String(ahora.getHours()).padStart(2, '0');
        const minutos = String(ahora.getMinutes()).padStart(2, '0');
        const segundos = String(ahora.getSeconds()).padStart(2, '0');
        const horaActual = `${horas}:${minutos}:${segundos}`;
        
        document.querySelectorAll('.hora_inicio, .hora_asignacion, .hora_termino').forEach(input => {
            input.value = horaActual;
        });
    }

    actualizarHora();
    setInterval(actualizarHora, 1000);

    $(document).on("click", ".completar", function(){
        let id = $(this).data("id");
        
        $.ajax({
            type: "POST",
            url: '<?php echo htmlspecialchars($_ENV["BASE_URL"], ENT_QUOTES, 'UTF-8'); ?>completar_tickets',
            dataType: "html",
            data: {
                id: id
            },
            beforeSend: function() {
                $("#artify-ajax-loader").show();
            },
            success: function(data){
                $("#artify-ajax-loader").hide();
                $('.cargar_modal').html(data);
                $("#Completar").modal("show");
            }
        });
    });

    $(document).on("click", ".asignar", function(){
        let id = $(this).data("id");
        
        $.ajax({
            type: "POST",
            url: '<?php echo htmlspecialchars($_ENV["BASE_URL"], ENT_QUOTES, 'UTF-8'); ?>asignar',
            dataType: "html",
            data: {
                id: id
            },
            beforeSend: function() {
                $("#artify-ajax-loader").show();
            },
            success: function(data){
                $("#artify-ajax-loader").hide();
                $('.cargar_modal').html(data);
                $("#Asignar").modal("show");
            }
        });
    });

    $(document).on('hidden.bs.modal', '#Completar', function () {
        $('.cargar_modal').empty();
    });
</script>