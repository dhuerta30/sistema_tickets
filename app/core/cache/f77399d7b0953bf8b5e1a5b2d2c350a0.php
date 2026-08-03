<?php include 'C:\laragon\www\sistema_tickets\app\core/cache/b9326fb976c20e14ce6494ea2da1c350.php'; ?>
<?php include 'C:\laragon\www\sistema_tickets\app\core/cache/16f1b347bd6377472a7f0f6b931f21ef.php'; ?>
<link href='<?php echo htmlspecialchars($_ENV["BASE_URL"], ENT_QUOTES, 'UTF-8'); ?>css/sweetalert2.min.css' rel="stylesheet">
<style>
	.select2-container .select2-selection--single {
		height: 38px!important;
	}
	.select2-container--default .select2-selection--single .select2-selection__arrow {
		top: 7px!important;
	}

	.select2-container {
		width:100%!important;
	}
</style>
<div class="content-wrapper">
	<section class="content">
		<div class="card">
			<div class="card-body">

				<ul class="nav nav-pills" id="myTab" role="tablist">
					<li class="nav-item" role="presentation">
						<a class="nav-link active" id="Menu-tab" data-toggle="tab" href="#Menu" role="tab" aria-controls="Menu" aria-selected="true">Todos los Menu</a>
					</li>
					<li class="nav-item" role="presentation">
						<a class="nav-link" id="Submenu-tab" data-toggle="tab" href="#Submenu" role="tab" aria-controls="Submenu" aria-selected="false">Todos los Submenu</a>
					</li>
				</ul>
				<div class="tab-content" id="myTabContent">
					<div class="tab-pane fade show active pt-3" id="Menu" role="tabpanel" aria-labelledby="Menu-tab">
						<?php echo $render; ?>
						<?php echo $select2; ?>
					</div>
					<div class="tab-pane fade pt-3" id="Submenu" role="tabpanel" aria-labelledby="Submenu-tab">
						<div class="crud_submenu"></div>
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
$(document).on("artify_after_ajax_action", function(event, obj, data){
	let action = $(obj).attr('data-action');

	sortable();
	sortableSubmenu();

	if(action == "add"){
		$.ajax({
			url: '<?php echo htmlspecialchars($_ENV["BASE_URL"], ENT_QUOTES, 'UTF-8'); ?>js/icons.json',
			dataType: "json",
			beforeSend: function() {
				$("#artify-ajax-loader").show();
			},
			success: function(data){
				$("#artify-ajax-loader").hide();
				$('.icono_menu, .icono_submenu').html(`<option>Seleccionar Icono</option>`);

				// Recorre cada grupo de íconos
				$.each(data[0].icons, function(index, group){
					// Recorre cada ícono en el grupo
					$.each(group.items, function(index, icon){
						// Agrega cada ícono como una opción al menú desplegable
						$('.icono_menu, .icono_submenu').append(`<option value="${icon}"><i class="${icon}"></i> ${icon}</option>`);
					});
				});
			}
		});
	} else if(action == "edit"){
		let id = $(obj).attr('data-id');

		$.ajax({
            type: "POST",
            url: '<?php echo htmlspecialchars($_ENV["BASE_URL"], ENT_QUOTES, 'UTF-8'); ?>editar_iconos_menu',
            dataType: "json",
            data: { id: id },
			beforeSend: function() {
				$("#artify-ajax-loader").show();
			},
            success: function(data){
                $("#artify-ajax-loader").hide();
				let icono_menu = data['data'][0]['icono_menu'];

                $('.icono_menu').html(`<option>Seleccionar Icono</option>`);
                
                // Recorre cada grupo de íconos
                $.each(data["icons"][0].icons, function(index, group){
                    // Recorre cada ícono en el grupo
                    $.each(group.items, function(index, icon){
                        // Agrega cada ícono como una opción al menú desplegable
						let selected = (icono_menu === icon) ? 'selected' : '';
                    	$('.icono_menu').append(`<option value="${icon}" ${selected}>${icon}</option>`);
                    });
                });
            }
        });

		$.ajax({
            type: "POST",
            url: '<?php echo htmlspecialchars($_ENV["BASE_URL"], ENT_QUOTES, 'UTF-8'); ?>editar_iconos_submenu',
            dataType: "json",
            data: { id: id },
			beforeSend: function() {
				$("#artify-ajax-loader").show();
			},
            success: function(data){
                $("#artify-ajax-loader").hide();
				let icono_submenu = data['data'][0]['icono_submenu'];

                $('.icono_submenu').html(`<option>Seleccionar Icono</option>`);
                
                // Recorre cada grupo de íconos
                $.each(data["icons"][0].icons, function(index, group){
                    // Recorre cada ícono en el grupo
                    $.each(group.items, function(index, icon){
                        // Agrega cada ícono como una opción al menú desplegable
						let selected = (icono_submenu === icon) ? 'selected' : '';
                    	$('.icono_submenu').append(`<option value="${icon}" ${selected}>${icon}</option>`);
                    });
                });
            }
        });
	} else if(action == "delete"){
		refrechMenu();
	}
});

function refrechMenu(){
	$.ajax({
		type: "POST",
		url: '<?php echo htmlspecialchars($_ENV["BASE_URL"], ENT_QUOTES, 'UTF-8'); ?>refrescarMenu',
		dataType: "json",
		success: function(response){
			$('.menu_generator').html(response);
		}
	});
}

$(document).on("artify_after_submission", function(event, obj, data){
    let json = JSON.parse(data);

    if(json.message){

		refrechMenu();

        $('.artify-back').click();
		sortable();
		sortableSubmenu();
        Swal.fire({
            title: "Genial!",
            text: json.message,
            icon: "success",
            confirmButtonText: "Aceptar"
        }).then((result) => {
			/* Read more about isConfirmed, isDenied below */
			if (result.isConfirmed) {
				$("[data-action='refresh']").click();
			} else if (result.isDenied) {
				Swal.fire("Changes are not saved", "", "info");
			}
		});
    }
});

function sortable(){
	$(".artify-table tbody").sortable({
	  handle: '.reordenar_fila',
      helper: function(e, ui) {
        var clone = $(ui).clone();
        clone.css('position', 'absolute');
        return clone.get(0);
      },
      start: function(e, ui) {
        ui.helper.addClass("dragging");
      },
      stop: function(e, ui) {
        ui.item.removeClass("dragging");
      },
	  update: function(event, ui){
		var newOrder = [];
		$(".artify-table tbody tr").each(function() {
			newOrder.push($(this).data("id"));
		});

		updateUrl = '<?php echo htmlspecialchars($_ENV["BASE_URL"], ENT_QUOTES, 'UTF-8'); ?>actualizar_orden_menu';

		$.ajax({
			type: "POST",
			url: updateUrl,
			dataType: "json",
			data: { order: newOrder },
			beforeSend: function() {
				$("#artify-ajax-loader").show();
			},
			success: function(response) {
				$("#artify-ajax-loader").hide();
				$('#artify_search_btn').click();
				refrechMenu();
				Swal.fire({
					title: "Genial!",
					text: response['success'],
					icon: "success",
					confirmButtonText: "Aceptar"
				});
			}
		});

	  }
    }).disableSelection();
}

function sortableSubmenu(){
	$(".submenutable tbody").sortable({
	  handle: '.reordenar_fila_submenu',
      helper: function(e, ui) {
        var clone = $(ui).clone();
        clone.css('position', 'absolute');
        return clone.get(0);
      },
      start: function(e, ui) {
        ui.helper.addClass("dragging");
      },
      stop: function(e, ui) {
        ui.item.removeClass("dragging");
      },
	  update: function(event, ui){
		var newOrderSub = [];
		$(".submenutable tbody tr").each(function() {
			newOrderSub.push($(this).data("id"));
		});

		console.log(newOrderSub);

		updateUrl = '<?php echo htmlspecialchars($_ENV["BASE_URL"], ENT_QUOTES, 'UTF-8'); ?>actualizar_orden_submenu';

		$.ajax({
			type: "POST",
			url: updateUrl,
			dataType: "json",
			data: { order: newOrderSub },
			beforeSend: function() {
				$("#artify-ajax-loader").show();
			},
			success: function(response) {
				$("#artify-ajax-loader").hide();
				$('#artify_search_btn').click();
				refrechMenu();
				Swal.fire({
					title: "Genial!",
					text: response['success'],
					icon: "success",
					confirmButtonText: "Aceptar"
				});
			}
		});

	  }
    }).disableSelection();
}

$(document).ready(function() {
    sortable();
});


$('#Submenu-tab').on('shown.bs.tab', function (e) {
	$.ajax({
		type: "POST",
		url: '<?php echo htmlspecialchars($_ENV["BASE_URL"], ENT_QUOTES, 'UTF-8'); ?>cargar_vista_submenu',
		dataType: "HTML",
		beforeSend: function() {
			$("#pdocrud-ajax-loader").show();
		},
		success: function(data){
			$("#pdocrud-ajax-loader").hide();
			$(".crud_submenu").html(data);
			sortableSubmenu();
			$('select[data-col="id_menu"]').select2({
				placeholder: "Seleccionar",
				allowClear: true
			});
		}
	});
});

$(document).on("artify_before_ajax_action artify_after_ajax_action", function(event, obj, data){
	var dataAction = obj.getAttribute('data-action');

	$('select[data-col="id_menu"]').select2({
		placeholder: "Seleccionar",
		allowClear: true
	});

	if(dataAction == "save_crud_table_data"){
		Swal.fire({
			icon: 'success',
			showCancelButton: false,
			allowOutsideClick: false,
			text: 'Datos Actualizados Correctamnete',
			confirmButtonText: 'Aceptar'
		}).then((result) => {
			if (result.isConfirmed) {
				$("#artify_search_btn").click();
			}
		});
	}
});
</script>
<?php include 'C:\laragon\www\sistema_tickets\app\core/cache/64a272ea1253c270fb04c399539a8ce1.php'; ?>