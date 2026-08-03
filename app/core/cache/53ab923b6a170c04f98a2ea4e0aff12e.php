<?php include '/home/hospitaldem/public_html/sistema_tickets/app/core/cache/8a64a4f37db2e0589ae7310721965099.php'; ?>
<?php include '/home/hospitaldem/public_html/sistema_tickets/app/core/cache/4478e7d1813936c227a256f8ae72001c.php'; ?>
<link rel="stylesheet" href="https://unpkg.com/grapesjs/dist/css/grapes.min.css">
<link href='<?php echo htmlspecialchars($_ENV["BASE_URL"], ENT_QUOTES, 'UTF-8'); ?>css/sweetalert2.min.css' rel="stylesheet">
<style>
.btn.btn-default {
    background: #fff;
}
@media (min-width: 576px){
	.modal-dialog {
		max-width: 700px!important;
		margin: 1.75rem auto;
	}
}

.note-editor.note-frame, .note-editor.note-airframe {
    width: 100%;
}

.mostrar_columnas_grilla, 
.mostrar_campos_busqueda,
.nombre_columnas,
.type_callback,
.tabla_principal_union, 
.campos_relacion_union_tabla_secundaria, 
.campos_relacion_union_tabla_principal, 
.tabla_secundaria_union,
.mostrar_campos_formulario,
.mostrar_campos_formulario_editar,
.nombre_campos,
.mostrar_campos_filtro,
.campos_no_requeridos,
.ocultar_label {
    background-color: #f9f9f9;
    font-family: Arial, sans-serif;
    font-size: 14px;
    color: #333;
    outline: none;
    min-height: 300px;
}

.mostrar_columnas_grilla,
.mostrar_campos_busqueda, 
.nombre_columnas, 
.type_callback, 
.tabla_principal_union, 
.campos_relacion_union_tabla_secundaria, 
.campos_relacion_union_tabla_principal, 
.tabla_secundaria_union option,
.mostrar_campos_formulario,
.mostrar_campos_formulario_editar,
.nombre_campos,
.mostrar_campos_filtro,
.campos_no_requeridos,
.ocultar_label {
    margin: 2px;  
}

.mt-customs {
    margin-top: 55px;
}

.bootstrap-tagsinput input {
    width: 200px!important;
}

.mt-custom {
    margin-top: 44px;
}

label {
    margin-bottom: 6px!important;
}

.tag {
    background-color: #5cb85c;
    color: #fff;
    padding: 2px 5px;
    border-radius: 3px;
    margin-right: 5px;
    margin-bottom: 5px;
    cursor: pointer;
    font-size: 0.9rem;
}

.fwb {
    font-weight: bold;
}

label:not(.form-check-label):not(.custom-file-label) {
    font-size: 13px;
    display:flex;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice {
    background-color: #000000!important;
    border: 1px solid #000000!important;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
    color: #fff!important;
}

.select2-container .select2-selection--single {
    height: 38px!important;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    top: 7px!important;
}

.select2-container {
    width:100%!important;
}

.artify_leftjoin_row_1 {
    width: 7.692307692307692%;
}

body {
    overflow-x: hidden;
}

.bootstrap-switch.bootstrap-switch-focused {
    border-color: #ccc!important;
    box-shadow: none!important;
}

.bootstrap-switch .bootstrap-switch-handle-off.bootstrap-switch-primary, .bootstrap-switch .bootstrap-switch-handle-on.bootstrap-switch-primary {
    color: white!important;
    background: green!important;
}

.bootstrap-switch .bootstrap-switch-handle-off.bootstrap-switch-default, .bootstrap-switch .bootstrap-switch-handle-on.bootstrap-switch-default {
    color: white!important;
    background: red!important;
}

.circle-number {
    background: green;
    padding: 4px 10px;
    border-radius: 50%;
    color: #fff;
}

.gjs-block-label {
    font-size: 18px;
    position: relative;
    top: 20px;
}

  .gjs-blocks-c {
    margin-bottom: 20px!important;
   }

  .gjs-block-section {
    background-color: #f0f8ff; /* Color de fondo claro */
    border: 1px solid #007bff; /* Color de borde */
  }
  
  .gjs-block {
    color: #333; /* Color del texto */
    padding: 10px; /* Espaciado interno */
    border-radius: 4px; /* Bordes redondeados */
    width: 100%;
    min-height: 82px!important;
  }

  .gjs-four-color-h {
    background: linear-gradient(to right, #ff7e5f, #feb47b); /* Gradiente de color */
  }

  /* Ejemplo para cambiar el color de texto */
  .gjs-block-section .form-label {
    color: #007bff; /* Color de las etiquetas */
  }
  
</style>
<div class="content-wrapper">
	<section class="content">
		<div class="card mt-4">
			<div class="card-body">

                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" id="create-tablas-tab" data-toggle="tab" href="#create-tablas" role="tab" aria-controls="create-tablas" aria-selected="true"><span class="circle-number">1</span> Crear Tablas</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="create-modulos-tab" data-toggle="tab" href="#create-modulos" role="tab" aria-controls="create-modulos" aria-selected="false"><span class="circle-number">2</span> Generador de Módulos</a>
                    </li>
                    <!--<li class="nav-item" role="presentation">
                        <a class="nav-link" id="create-pdf-tab" data-toggle="tab" href="#create-pdf" role="tab" aria-controls="create-pdf" aria-selected="false"><span class="circle-number">3</span> Configuraciones de PDF</a>
                    </li>-->
                </ul>
            
                <div class="tab-content pt-3" id="myTabContent">
                    <div class="tab-pane fade show active" id="create-tablas" role="tabpanel" aria-labelledby="create-tablas-tab">
                        <?=$render_tablas?>
                    </div>
                    <div class="tab-pane fade" id="create-modulos" role="tabpanel" aria-labelledby="create-modulos-tab">
                        <?php echo $render; ?>
                        <?php echo $switch; ?>
                        <?php echo $tags; ?>
                    </div>
                    <div class="tab-pane fade" id="create-pdf" role="tabpanel" aria-labelledby="create-pdf-tab">
                        <?php echo $render_pdf; ?>
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
<script src="https://unpkg.com/grapesjs"></script>
<script>
$(document).on("change", ".generar_jwt_token", function() {
    var val = $(this).val();

    if(val == "Si"){
        $(".autenticar_jwt_token").removeAttr("disabled", "disabled");
        $(".tiempo_caducidad_token").removeAttr("disabled", "disabled");
        $(".generar_token_api").removeClass("d-none");
    } else {
        $(".generar_token_api").addClass("d-none");
        $(".autenticar_jwt_token").attr("disabled", "disabled");
        $(".tiempo_caducidad_token").attr("disabled", "disabled");
        $(".autenticar_jwt_token").val("");
    }
});

$(document).on("click", ".generar_token_api", function(){
    $.ajax({
        type: "POST",
        url: "<?=$_ENV["BASE_URL"]?>generarToken",
        dataType: 'json',
        beforeSend: function() {
            $("#artify-ajax-loader").show();
        },
        success: function(data){
            $("#artify-ajax-loader").hide();
            let token = data["data"];
            $(".autenticar_jwt_token").val(token);
            Swal.fire({
                title: "Genial!",
                text: "Token Generado con éxito",
                icon: "success",
                confirmButtonText: "Aceptar"
            });
        }
    });
});

$(document).on("artify_after_ajax_action", function(event, obj, data){
    var dataAction = obj.getAttribute('data-action');
    var dataId = obj.getAttribute('data-id');

    if(dataAction == "add"){

        $('.ocultar_id_tabla').change(function() {
            // Obtén el valor del select
            var selectedValue = $(this).val();
            
            // Si el valor es "Si", oculta las opciones que comienzan con "id"
            if (selectedValue === 'Si') {
                $('.ocultar_label option, .mostrar_campos_formulario option, .mostrar_campos_formulario_editar option, .campos_no_requeridos option, .nombre_campos option').each(function() {
                    // Si el valor de la opción comienza con "id", ocúltala
                    if ($(this).val().startsWith('id')) {
                        $(this).hide();
                    }
                });
            } else {
                // Si el valor no es "Si", muestra todas las opciones
                $('.ocultar_label option, .mostrar_campos_formulario option, .mostrar_campos_formulario_editar option, .campos_no_requeridos option, .nombre_campos option').show();
            }
        });

        $(".valor_predeterminado_de_campo").tagsinput();

        const editor = grapesjs.init({
            container: '#editor',
            height: '679px',
            width: '100%',
            fromElement: true,
            storageManager: false,
            deviceManager: {
                devices: [
                { name: 'Escritorio', width: '' } // Solo define la vista de escritorio
                ]
            },
            blockManager: {
                appendTo: '#blocks',
                blocks: [
                {
                    id: 'row',
                    label: 'Agregar Fila',
                    attributes: { class: 'gjs-block-section' },
                    content: `
                    <div class="row pt-4" style="display: flex; justify-content: center; background: lightblue; padding: 50px; border: 1px solid #007bff;">
                        <!-- Las columnas se colocarán aquí -->
                    </div>`,
                },
                {
                    id: 'card',
                    label: 'Agregar Tarjeta',
                    attributes: { class: 'gjs-block-section' },
                    content: `
                    <div class="card" style="width:100%;">
                        <div class="card-header" style="background-color:green; padding: 10px; color: #fff;">
                            Titulo de la Tarjeta
                        </div>
                        <div class="card-body" style="background-color: blue; padding: 30px; color: #fff;">
                            <!-- Acá contenido de la tarjeta -->
                        </div>
                    </div>`,
                },
                {
                    id: 'column1',
                    label: 'Agregar 1 Columna',
                    attributes: { class: 'gjs-block-column' },
                    content: `
                    <div class="col-md" style="background: orange; border: 1px solid #000; height: 150px; display: flex; align-items: center; justify-content: center; height: 150px; width: 250px;">
                        <!-- Acá el contenido -->
                    </div>`,
                },
                {
                    id: 'campo',
                    label: 'Agregar Campo',
                    attributes: { class: 'gjs-block-field' },
                    content: `
                    <div class="form-group">
                        <label class="form-label" style="color:#fff; font-size: 20px;">Nombre Columna:</label>
                        <span class="editable" data-gjs-editable="true" style="color:#fff; font-size: 20px;">{nombre_campo}</span>
                        <p class="artify_help_block help-block form-text with-errors"></p>
                    </div>`,
                },
                {
                    id: 'column2',
                    label: 'Agregar 2 Columnas',
                    attributes: { class: 'gjs-block-column' },
                    content: `
                    <div class="col-md" style="background: orange; border: 1px solid #000; height: 150px; display: flex; align-items: center; justify-content: center; height: 150px; width: 250px;">
                        <!-- Acá el contenido -->
                    </div>
                    <div class="col-md" style="background: orange; border: 1px solid #000; height: 150px; display: flex; align-items: center; justify-content: center; height: 150px; width: 250px;">
                        <!-- Acá el contenido -->
                    </div>`,
                },
                {
                    id: 'column3',
                    label: 'Agregar 3 Columnas',
                    attributes: { class: 'gjs-block-column' },
                    content: `
                    <div class="col-md" style="background: orange; border: 1px solid #000; height: 150px; display: flex; align-items: center; justify-content: center; height: 150px; width: 250px;">
                        <!-- Acá el contenido -->
                    </div>
                    <div class="col-md" style="background: orange; border: 1px solid #000; height: 150px; display: flex; align-items: center; justify-content: center; height: 150px; width: 250px;">
                        <!-- Acá el contenido -->
                    </div>
                    <div class="col-md" style="background: orange; border: 1px solid #000; height: 150px; display: flex; align-items: center; justify-content: center; height: 150px; width: 250px;">
                       <!-- Acá el contenido -->
                    </div>`,
                },
                {
                    id: 'column4',
                    label: 'Agregar 4 Columnas',
                    attributes: { class: 'gjs-block-column' },
                    content: `
                    <div class="col-md" style="background: orange; border: 1px solid #000; height: 150px; display: flex; align-items: center; justify-content: center; height: 150px; width: 250px;">
                       <!-- Acá el contenido -->
                    </div>
                    <div class="col-md" style="background: orange; border: 1px solid #000; height: 150px; display: flex; align-items: center; justify-content: center; height: 150px; width: 250px;">
                       <!-- Acá el contenido -->
                    </div>
                    <div class="col-md" style="background: orange; border: 1px solid #000; height: 150px; display: flex; align-items: center; justify-content: center; height: 150px; width: 250px;">
                       <!-- Acá el contenido -->
                    </div>
                    <div class="col-md" style="background: orange; border: 1px solid #000; height: 150px; display: flex; align-items: center; justify-content: center; height: 150px; width: 250px;">
                       <!-- Acá el contenido -->
                    </div>`,
                },
                ],
            },
        });

        editor.Panels.removePanel('devices-c');
        editor.setDevice('Escritorio');

        editor.Panels.addButton('options', [{
            id: 'edit-html',
            className: 'fa fa-code',
            command: 'edit-html',
            attributes: { title: 'Editar HTML' }
        }]);

        editor.Commands.add('edit-html', {
            run(editor, sender) {
                sender && sender.set('active', false); // Desactiva el botón

                // Obtener el HTML y CSS actuales del editor
                const html = editor.getHtml();
                const css = editor.getCss();

                // Crear el modal para edición de HTML
                const modal = document.createElement('div');
                modal.style.position = 'fixed';
                modal.style.top = '0';
                modal.style.left = '0';
                modal.style.width = '100%';
                modal.style.height = '100%';
                modal.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
                modal.style.display = 'flex';
                modal.style.flexDirection = 'column';
                modal.style.justifyContent = 'center';
                modal.style.alignItems = 'center';
                modal.style.zIndex = '9999';

                const textarea = document.createElement('textarea');
                textarea.style.width = '80%';
                textarea.style.height = '60%';
                textarea.style.marginBottom = '10px';
                textarea.style.fontSize = '16px';
                textarea.value = `<style>${css}</style>\n${html}`;
                
                // Agregar una clase al textarea
                textarea.classList.add('custom-textarea'); // Aquí agregamos la clase 'custom-textarea'

                const saveButton = document.createElement('button');
                saveButton.classList.add('btn');
                saveButton.classList.add('btn-success');
                saveButton.innerText = 'Guardar';
                saveButton.style.marginRight = '10px';
                saveButton.onclick = () => {
                    // Aplicar el HTML modificado al editor
                    editor.setComponents(textarea.value);
                    document.body.removeChild(modal); // Cerrar el modal
                };

                const closeButton = document.createElement('button');
                closeButton.classList.add('btn');
                closeButton.classList.add('btn-danger');
                closeButton.innerText = 'Cerrar';
                closeButton.onclick = () => {
                    document.body.removeChild(modal); // Cerrar el modal sin guardar
                };

                const buttonContainer = document.createElement('div');
                buttonContainer.appendChild(saveButton);
                buttonContainer.appendChild(closeButton);

                modal.appendChild(textarea);
                modal.appendChild(buttonContainer);

                // Agregar el modal al DOM
                document.body.appendChild(modal);

                $(textarea).summernote({
                    height: 600, // Tamaño del editor
                    placeholder: 'Edita el HTML aquí...', // Texto de marcador de posición
                    toolbar: [
                        ['style', ['bold', 'italic', 'underline']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['insert', ['link', 'picture']],
                        ['view', ['fullscreen', 'codeview']]
                    ]
                });
            }
        });

        editor.I18n.addMessages({
            en: {
                // indicate the locale to update
                styleManager: {
                    empty: 'Nuevo mensaje de estado vacío',
                },
            },
        });

        editor.on('update', () => {
            const htmlContent = editor.getHtml();

            // Usar expresión regular para eliminar la etiqueta <body> y su contenido
            const cleanedHtmlContent = htmlContent
                .replace(/<body>([\s\S]*)<\/body>/, '$1') // Eliminar etiquetas <body>
                .replace(/<span class="editable">(.*?)<\/span>/g, '$1') // Eliminar <span class="editable">
                .trim(); // Eliminar espacios en blanco al inicio y al final

            const elements = document.querySelectorAll('.estructura_de_columnas_y_campos');
            elements.forEach((element) => {
                element.value = cleanedHtmlContent;
            });
        });

        function generateCreateTableQuery() {
            const tableName = $('.nombre_tabla').val().trim();
            if (!tableName) {
                $('.query_tabla').val('Por favor, ingrese el nombre de la tabla.');
                return;
            }

            let columns = [];
            
            // Recorrer cada grupo de columnas y construir la definición de cada columna
            $('.column-group').each(function(index) {
                const columnName = $(this).find('.columnName').val().trim();
                let columnType = $(this).find('.columnType').val().trim();
                const columnNull = $(this).find('.columnNull').val();
                const isPrimaryKey = $(this).find('.primaryKey').is(':checked');
                const isAutoIncrement = $(this).find('.autoIncrement').is(':checked');
                const columnLength = $(this).find('.longitud').val().trim();

                if (columnName && columnType) {
                    // Si el tipo es INT, añadir (11) por defecto
                    if (columnType === 'INT') {
                        columnType += `(${columnLength})`;
                    } 
                    // Si el tipo es VARCHAR, añadir la longitud ingresada o por defecto (100)
                    else if (columnType === 'VARCHAR') {
                        columnType += `(${columnLength})`;
                    }

                    let columnDefinition = `${columnName} ${columnType} ${columnNull}`;

                    // Solo la primera fila (index 0) puede ser clave primaria y autoincremental
                    if (index === 0) {
                        if (isAutoIncrement) {
                            columnDefinition += ' AUTO_INCREMENT';
                        }
                        if (isPrimaryKey) {
                            columnDefinition += ' PRIMARY KEY';
                        }
                    }

                    columns.push(columnDefinition);
                }
            });

            if (columns.length === 0) {
                $('.query_tabla').val('Debe definir al menos una columna.');
                return;
            }
            
            // Construir la consulta CREATE TABLE
            let query = `${columns.join(",\n")}`;

            // Mostrar la consulta en el textarea
            $('.query_tabla').val(query);
        }

        $(document).on('change', '.columnType', function() {
            const selectedType = $(this).val();
            const lengthField = $(this).closest('.column-group').find('.oculto');

            if (selectedType === 'INT' || selectedType === 'VARCHAR') {
                lengthField.removeClass('d-none');
            } else {
                lengthField.addClass('d-none');
                lengthField.find('input').val(''); // Limpiar el campo al ocultarlo
            }
            generateCreateTableQuery();
        });

        $(document).on('keyup change', '.longitud', generateCreateTableQuery);

        // Llamar a la función cada vez que se cambia un valor
        $('.nombre_tabla, .autoIncrement, .primaryKey').on('keyup change', generateCreateTableQuery);
        $('#columnsContainer').on('keyup change', '.columnName, .columnType, .columnNull', generateCreateTableQuery);
        // Agregar una nueva fila de columna
       
        // Añadir nueva columna
        $('.addColumn').on('click', function() {
            $('#columnsContainer').append(`
                <div class="form-group column-group">
                    <label class="form-label">Definir Columnas:</label>
                    <div class="row">
                        <div class="col-md">
                            <input type="text" class="columnName form-control" placeholder="Nombre de la columna (Ej: id)">
                        </div>
                        <div class="col-md">
                            <select class="columnType form-control tipo">
								<option value="">Seleccionar</option>
								<option value="INT">Entero</option>
								<option value="VARCHAR">Carácteres</option>
								<option value="TEXT">Texto</option>
								<option value="LONGTEXT">Texto Largo</option>
								<option value="DATE">Fecha</option>
								<option value="TIME">Hora</option>
								<option value="DATETIME">Fecha y Hora</option>
								<option value="TIMESTAMP">Marca de Tiempo</option>
								<option value="YEAR">Año</option>
							</select>
                        </div>
                        <div class="col-md oculto d-none">
							<input type="text" class="longitud form-control">
						</div>
                        <div class="col-md">
                            <select class="columnNull form-control">
                                <option value="">Seleccionar</option>	
                                <option value="NOT NULL">NO Vacio</option>
                                <option value="NULL">Vacio</option>
                            </select>
                        </div>
                        <div class="col-md-12 mt-2">
                            <!-- Solo la primera fila tendrá las opciones -->
                            <div class="primary-options" style="display:none;">
                                <label>
                                    <input type="checkbox" class="primaryKey" /> Clave Primaria
                                </label>
                                <label>
                                    <input type="checkbox" class="autoIncrement" /> Autoincremental
                                </label>
                            </div>
                            <button type="button" class="btn btn-danger removeColumn">Eliminar</button>
                        </div>
                    </div>
                </div>
            `);
            $('#columnsContainer .column-group').first().find('.primary-options').show();
            generateCreateTableQuery();
        });

        // Eliminar columna
        $(document).on('click', '.removeColumn', function() {
            $(this).closest('.column-group').remove(); // Elimina el grupo de columnas correspondiente
            generateCreateTableQuery();
        });

        $("form").on("keypress", "input", function(event) {
            if (event.key === "Enter") {
                event.preventDefault();
                return false;
            }
        });

        $('.type_callback').find('option[value=""]').remove();
        $('.campos_relacion_union_tabla_principal ').find('option[value=""]').remove();
        $('.tabla_principal_union').find('option[value=""]').remove();
        $('.tabla_secundaria_union').find('option[value=""]').remove();
        $('.campos_relacion_union_tabla_secundaria').find('option[value=""]').remove();

        $('#limpiarSelect').click(function() {
            // Limpia la selección
            $('.type_callback').val(null).trigger('change');
            
            // Reinicia la selección para asegurarte de que "Seleccionar" sea la única opción visible
            $('.type_callback').find('option[value=""]').prop('selected', false); // Selecciona "Seleccionar"
            $('.type_callback').trigger('change'); // Dispara el evento change para que se refleje en select2
        });

        $('#limpiarSelectEditar').click(function() {
            // Limpia la selección
            $('.mostrar_campos_formulario_editar').val(null).trigger('change');
            
            // Reinicia la selección para asegurarte de que "Seleccionar" sea la única opción visible
            $('.mostrar_campos_formulario_editar').find('option[value=""]').prop('selected', false); // Selecciona "Seleccionar"
            $('.mostrar_campos_formulario_editar').trigger('change'); // Dispara el evento change para que se refleje en select2
        });

        $('#limpiarSelectInsertar').click(function() {
            // Limpia la selección
            $('.mostrar_campos_formulario').val(null).trigger('change');
            
            // Reinicia la selección para asegurarte de que "Seleccionar" sea la única opción visible
            $('.mostrar_campos_formulario').find('option[value=""]').prop('selected', false); // Selecciona "Seleccionar"
            $('.mostrar_campos_formulario').trigger('change'); // Dispara el evento change para que se refleje en select2
        });

        $('#limpiarSelectCampos').click(function() {
            // Limpia la selección
            $('.nombre_campos').val(null).trigger('change');
            
            // Reinicia la selección para asegurarte de que "Seleccionar" sea la única opción visible
            $('.nombre_campos').find('option[value=""]').prop('selected', false); // Selecciona "Seleccionar"
            $('.nombre_campos').trigger('change'); // Dispara el evento change para que se refleje en select2
        });

        $('#limpiarSelectFiltros').click(function() {
            // Limpia la selección
            $('.mostrar_campos_filtro').val(null).trigger('change');
            
            // Reinicia la selección para asegurarte de que "Seleccionar" sea la única opción visible
            $('.mostrar_campos_filtro').find('option[value=""]').prop('selected', false); // Selecciona "Seleccionar"
            $('.mostrar_campos_filtro').trigger('change'); // Dispara el evento change para que se refleje en select2
        });

        $('#limpiarBuscador').click(function() {
            // Limpia la selección
            $('.mostrar_campos_busqueda').val(null).trigger('change');
            
            // Reinicia la selección para asegurarte de que "Seleccionar" sea la única opción visible
            $('.mostrar_campos_busqueda').find('option[value=""]').prop('selected', false); // Selecciona "Seleccionar"
            $('.mostrar_campos_busqueda').trigger('change'); // Dispara el evento change para que se refleje en select2
        });

        $('#limpiarGrilla').click(function() {
            // Limpia la selección
            $('.mostrar_columnas_grilla').val(null).trigger('change');
            
            // Reinicia la selección para asegurarte de que "Seleccionar" sea la única opción visible
            $('.mostrar_columnas_grilla').find('option[value=""]').prop('selected', false); // Selecciona "Seleccionar"
            $('.mostrar_columnas_grilla').trigger('change'); // Dispara el evento change para que se refleje en select2
        });

        $('#limpiarColumnas').click(function() {
            // Limpia la selección
            $('.nombre_columnas').val(null).trigger('change');
            
            // Reinicia la selección para asegurarte de que "Seleccionar" sea la única opción visible
            $('.nombre_columnas').find('option[value=""]').prop('selected', false); // Selecciona "Seleccionar"
            $('.nombre_columnas').trigger('change'); // Dispara el evento change para que se refleje en select2
        });

        $('#limpiarLista').click(function() {
            // Limpia la selección
            $('.campos_no_requeridos').val(null).trigger('change');
            
            // Reinicia la selección para asegurarte de que "Seleccionar" sea la única opción visible
            $('.campos_no_requeridos').find('option[value=""]').prop('selected', false); // Selecciona "Seleccionar"
            $('.campos_no_requeridos').trigger('change'); // Dispara el evento change para que se refleje en select2
        });

        $("#limpiarLabel").click(function() {
            // Limpia la selección
            $('.ocultar_label').val(null).trigger('change');
            
            // Reinicia la selección para asegurarte de que "Seleccionar" sea la única opción visible
            $('.ocultar_label').find('option[value=""]').prop('selected', false); // Selecciona "Seleccionar"
            $('.ocultar_label').trigger('change'); // Dispara el evento change para que se refleje en select2
        });

        const fullOptions = $('.type_callback option').clone(); // Clonamos todas las opciones

        $('.crud_type').on('change', function() {
            const selectedValue = $(this).val();

            // Filtrar opciones del multiselect según la selección
            if (selectedValue === 'Formulario de inserción') {
                $('.type_callback').empty() // Limpiar opciones
                    .append(
                        fullOptions.filter(function() {
                            return $(this).val() === 'Antes de Insertar' || $(this).val() === 'Despues de Insertar';
                        })
                    );
            } else if (selectedValue === 'Formulario de edición') {
                $('.type_callback').empty() // Limpiar opciones
                    .append(
                        fullOptions.filter(function() {
                            return $(this).val() === 'Antes de Actualizar' || $(this).val() === 'Despues de Actualizar';
                        })
                    );
            } else if (selectedValue === 'SQL') {
            $('.type_callback').empty() // Limpiar opciones
                .append(
                    fullOptions.filter(function() {
                        return $(this).val() === 'Formatear Datos Grilla SQL' || $(this).val() === 'Antes de los Datos de la Grilla SQL (Filtro y/o Busqueda)'
                        || $(this).val() === 'Antes de Eliminar' || $(this).val() === 'Despues de Eliminar';
                    })
                );

            } else {
                // Restaurar todas las opciones si se selecciona cualquier otro valor
                $('.type_callback').empty().append(fullOptions);
            }

        });

        $('.type_union').tagsinput();
        
        $(".nuevo_nombre_columnas").tagsinput();

        $(function() {
            var textosPermitidosListUnion = ['Interna', 'Izquierda'];

            $('.type_union').tagsinput({
                allowDuplicates: true,
                typeaheadjs: {
                    name: 'textosPermitidosListUnion',
                    source: function(query, syncResults) {
                        // Filtra los elementos de la lista permitida según el término de búsqueda
                        var matches = textosPermitidosListUnion.filter(function(item) {
                            return item.toLowerCase().indexOf(query.toLowerCase()) !== -1;
                        });
                        syncResults(matches);
                    }
                }
            });

            $('.type_union').on('beforeItemAdd', function(event) {
                var texto = event.item;

                // Si el texto no está en la lista de permitidos, cancelamos la adición
                if (textosPermitidosListUnion.indexOf(texto) === -1) {
                    event.cancel = true;
                    Swal.fire({
                        title: "Lo siento",
                        text: 'Este texto no está permitido.',
                        confirmButtonText: "Aceptar",
                        icon: "error"
                    });
                }
            });
        });

        $(function() {
            var textosPermitidosList = ['Imagen', 'Archivo Único', 'Multiples Archivos', 'Radiobox', 'Checkbox', 'Combobox', 'Combobox Multiple', 'Campo de Texto', 'Campo de Área de Texto', 'Campo de Fecha', 'Campo de Fecha y Hora', 'Campo de Hora'];

            $('.type_fields').tagsinput({
                allowDuplicates: true,
                typeaheadjs: {
                    name: 'textosPermitidosList',
                    source: function(query, syncResults) {
                        // Filtra los elementos de la lista permitida según el término de búsqueda
                        var matches = textosPermitidosList.filter(function(item) {
                            return item.toLowerCase().indexOf(query.toLowerCase()) !== -1;
                        });
                        syncResults(matches);
                    }
                }
            });

            $('.type_fields').on('beforeItemAdd', function(event) {
                var texto = event.item;

                // Si el texto no está en la lista de permitidos, cancelamos la adición
                if (textosPermitidosList.indexOf(texto) === -1) {
                    event.cancel = true;
                    Swal.fire({
                        title: "Lo siento",
                        text: 'Este texto no está permitido.',
                        confirmButtonText: "Aceptar",
                        icon: "error"
                    });
                }
            });
        });

        $('.tabla_principal_union').change(function() {
            let val = $('.tabla_principal_union').val(); // Obtenemos el array de valores seleccionados
            let lastSelected = val[val.length - 1]; // Obtenemos el último valor seleccionado
            
            $.ajax({
                type: "POST",
                url: "<?=$_ENV["BASE_URL"]?>obtener_campos_relacion_union_interna",
                dataType: "json",
                data: {
                    lastSelected: lastSelected
                },
                beforeSend: function() {
                    $("#artify-ajax-loader").show();
                },
                success: function(data){
                    $("#artify-ajax-loader").hide();

                    $(".campos_relacion_union_tabla_principal").empty();
                    $.each(data["data"], function(index, obj){
                        $(".campos_relacion_union_tabla_principal").append(`
                            <option value="${obj}">${obj}</option>
                        `);
                    });
                }
            });
        });

        $('.tabla_secundaria_union_izquierda').change(function() {
            let lastSelected = $(".tabla_secundaria_union_izquierda").val(); // Obtenemos el array de valores seleccionados
            
            $.ajax({
                type: "POST",
                url: "<?=$_ENV["BASE_URL"]?>obtener_campos_relacion_union_interna",
                dataType: "json",
                data: {
                    lastSelected: lastSelected
                },
                beforeSend: function() {
                    $("#artify-ajax-loader").show();
                },
                success: function(data){
                    $("#artify-ajax-loader").hide();

                    $(".campos_relacion_union_tabla_secundaria_izquierda").empty();
                    $.each(data["data"], function(index, obj){
                        $(".campos_relacion_union_tabla_secundaria_izquierda").append(`
                            <option value="${obj}">${obj}</option>
                        `);
                    });
                }
            });
        });


        $('.tabla_principal_union_izquierda').change(function() {
            let lastSelected = $('.tabla_principal_union_izquierda').val(); // Obtenemos el array de valores seleccionados
            
            $.ajax({
                type: "POST",
                url: "<?=$_ENV["BASE_URL"]?>obtener_campos_relacion_union_interna",
                dataType: "json",
                data: {
                    lastSelected: lastSelected
                },
                beforeSend: function() {
                    $("#artify-ajax-loader").show();
                },
                success: function(data){
                    $("#artify-ajax-loader").hide();

                    $(".campos_relacion_union_tabla_principal_izquierda").empty();
                    $.each(data["data"], function(index, obj){
                        $(".campos_relacion_union_tabla_principal_izquierda").append(`
                            <option value="${obj}">${obj}</option>
                        `);
                    });
                }
            });
        });

        $('.tabla_secundaria_union').change(function() {
            let val = $(".tabla_secundaria_union").val(); // Obtenemos el array de valores seleccionados
            let lastSelected = val[val.length - 1]; // Obtenemos el último valor seleccionado
            
            $.ajax({
                type: "POST",
                url: "<?=$_ENV["BASE_URL"]?>obtener_campos_relacion_union_interna",
                dataType: "json",
                data: {
                    lastSelected: lastSelected
                },
                beforeSend: function() {
                    $("#artify-ajax-loader").show();
                },
                success: function(data){
                    $("#artify-ajax-loader").hide();

                    $(".campos_relacion_union_tabla_secundaria").empty();
                    $.each(data["data"], function(index, obj){
                        $(".campos_relacion_union_tabla_secundaria").append(`
                            <option value="${obj}">${obj}</option>
                        `);
                    });
                }
            });
        });

        $('.tabla_principal_union, .tabla_secundaria_union').on('change', function () {
            let principalValues = $('.tabla_principal_union').val() || [];

            // Obtener los valores seleccionados del segundo multiselect
            let secundariaValues = $('.tabla_secundaria_union').val() || [];

            // Comprobar si alguno de los valores del segundo multiselect coincide con los del primero
            let commonValues = secundariaValues.filter(value => principalValues.includes(value));

            if (commonValues.length > 0) {
                // Si hay valores comunes, eliminar esos valores del segundo multiselect
                secundariaValues = secundariaValues.filter(value => !commonValues.includes(value));
                $('.tabla_secundaria_union').val(secundariaValues).trigger('change');

                // Mostrar la alerta con SweetAlert2
                Swal.fire({
                    icon: 'warning',
                    title: 'Valores duplicados',
                    text: 'No se permite la misma tabla en ambos Campos.',
                    confirmButtonText: "Aceptar"
                });
            }
        });

        $('.tabla_principal_union_izquierda, .tabla_secundaria_union_izquierda').on('change', function () {
            // Obtener el valor seleccionado del primer select
            let principalValue = $('.tabla_principal_union_izquierda').val();

            // Obtener el valor seleccionado del segundo select
            let secundariaValue = $('.tabla_secundaria_union_izquierda').val();

            // Comprobar si el valor del segundo select es igual al del primero
            if (principalValue === secundariaValue) {
                // Si los valores son iguales, restablecer el segundo select (desmarcar o asignar un valor vacío)
                $('.tabla_secundaria_union_izquierda').val('').trigger('change');

                // Mostrar la alerta con SweetAlert2
                Swal.fire({
                    icon: 'warning',
                    title: 'Valores duplicados',
                    text: 'No se permite la misma tabla en ambos campos.',
                    confirmButtonText: "Aceptar"
                });
            }
        });


        $(function() {
            var textosPermitidos = ['casilla de verificacion', 'seleccion', 'fecha', 'texto'];

            $('.tipo_de_filtro').tagsinput({
                allowDuplicates: true,
                typeaheadjs: {
                    name: 'textosPermitidos',
                    source: function(query, syncResults) {
                        // Filtra los elementos de la lista permitida según el término de búsqueda
                        var matches = textosPermitidos.filter(function(item) {
                            return item.toLowerCase().indexOf(query.toLowerCase()) !== -1;
                        });
                        syncResults(matches);
                    }
                }
            });

            $('.tipo_de_filtro').on('beforeItemAdd', function(event) {
                var texto = event.item;

                // Si el texto no está en la lista de permitidos, cancelamos la adición
                if (textosPermitidos.indexOf(texto) === -1) {
                    event.cancel = true;
                    Swal.fire({
                        title: "Lo siento",
                        text: 'Este texto no está permitido.',
                        confirmButtonText: "Aceptar",
                        icon: "error"
                    });
                }
            });
        });

        $(".regresar_tablas").click(function(){
            $('.leftjoin_tr').remove();
        });

        $(".artify-cancel-btn").click(function(){
            $('a[data-action="delete_row"]').click();
        });

        /*$("#create-tablas-tab, #create-pdf-tab").click(function(){
            $(".regresar_modulos").click();
        });*/

        $("#create-modulos-tab, #create-pdf-tab, #config-api-tab").click(function(){
            $('.leftjoin_tr').remove();
            $('.regresar_tablas').click();
        });

        $(".campos_requeridos").change(function(){
            let valor = $(this).val();

            if(valor == "Si"){
                $(".ocultar_campos_requeridos").removeClass("d-none");
            } else {
                $(".ocultar_campos_requeridos").addClass("d-none");
            }
        });

        $(".activar_union_interna").change(function(){
            let valor = $(this).val();

            if(valor == "Si"){
                $(".tabla_principal_union").removeAttr("disabled", "disabled");
                $(".tabla_secundaria_union").removeAttr("disabled", "disabled");
                $(".campos_relacion_union_tabla_principal").removeAttr("disabled", "disabled");
                $(".campos_relacion_union_tabla_secundaria").removeAttr("disabled", "disabled");
                $(".esconder_tipo_union").removeClass("d-none");
                $(".union_ocultar").removeClass("d-none");
            } else {
                $(".tabla_principal_union").attr("disabled", "disabled");
                $(".tabla_secundaria_union").attr("disabled", "disabled");
                $(".campos_relacion_union_tabla_principal").attr("disabled", "disabled");
                $(".campos_relacion_union_tabla_secundaria").attr("disabled", "disabled");
                $(".esconder_tipo_union").addClass("d-none");
                $(".union_ocultar").addClass("d-none");
            }
        });

        $(document).on("click", ".cargar_datos_izquierda", function(){
            let activar_union_izquierda = $(".activar_union_izquierda").val();
            let tabla_principal_union_izquierda = $(".tabla_principal_union_izquierda").val();
            let campos_relacion_union_tabla_principal_izquierda = $(".campos_relacion_union_tabla_principal_izquierda").val();
            let tabla_secundaria_union_izquierda = $(".tabla_secundaria_union_izquierda").val();
            let campos_relacion_union_tabla_secundaria_izquierda = $(".campos_relacion_union_tabla_secundaria_izquierda").val();

            if(activar_union_izquierda == "Si"){

                if(tabla_principal_union_izquierda == ""){
                    Swal.fire({
                        title: "Lo siento",
                        text: 'Ingrese Tabla Principal Union',
                        confirmButtonText: "Aceptar",
                        icon: "error"
                    }); 
                } else if(campos_relacion_union_tabla_principal_izquierda == ""){
                    Swal.fire({
                        title: "Lo siento",
                        text: 'Ingrese Relación Principal Union',
                        confirmButtonText: "Aceptar",
                        icon: "error"
                    }); 
                } else if(tabla_secundaria_union_izquierda == ""){
                    Swal.fire({
                        title: "Lo siento",
                        text: 'Ingrese Tabla secundaria Union',
                        confirmButtonText: "Aceptar",
                        icon: "error"
                    }); 
                } else if(tabla_secundaria_union_izquierda == ""){
                    Swal.fire({
                        title: "Lo siento",
                        text: 'Ingrese Relación Secundaria Union',
                        confirmButtonText: "Aceptar",
                        icon: "error"
                    });
                } else {
                    $.ajax({
                        type: "POST",
                        url: "<?=$_ENV["BASE_URL"]?>obtener_campos_union_izquierda",
                        dataType: "json",
                        data: {
                            tabla_principal_union_izquierda: tabla_principal_union_izquierda,
                            campos_relacion_union_tabla_principal_izquierda: campos_relacion_union_tabla_principal_izquierda,
                            tabla_secundaria_union_izquierda: tabla_secundaria_union_izquierda,
                            campos_relacion_union_tabla_secundaria_izquierda: campos_relacion_union_tabla_secundaria_izquierda
                        },
                        beforeSend: function() {
                            $("#artify-ajax-loader").show();
                        },
                        success: function(data){
                            $("#artify-ajax-loader").hide();

                            $(".ocultar_label, .ordenar_grilla_por, .mostrar_campos_busqueda, .mostrar_campos_formulario, .mostrar_columnas_grilla, .mostrar_campos_filtro, .mostrar_campos_formulario_editar, .nombre_columnas, .nombre_campos, .campos_no_requeridos").empty();
                            //$(".tabla_principal_union_izquierda, .tabla_secundaria_union_izquierda").html(`<option value>Seleccionar</option>`);

                            $.each(data["tabla1"], function(index, obj){
                                $(".ocultar_label, .ordenar_grilla_por, .mostrar_campos_busqueda, .mostrar_campos_formulario, .mostrar_columnas_grilla, .mostrar_campos_filtro, .mostrar_campos_formulario_editar, .nombre_columnas, .nombre_campos, .campos_no_requeridos").append(`
                                    <option value="${obj}">${obj}</option>
                                `);
                            });

                            $.each(data["tabla2"], function(index, obj){
                                $(".ocultar_label, .ordenar_grilla_por, .mostrar_campos_busqueda, .mostrar_campos_formulario, .mostrar_columnas_grilla, .mostrar_campos_filtro, .mostrar_campos_formulario_editar, .nombre_columnas, .nombre_campos, .campos_no_requeridos").append(`
                                    <option value="${obj}">${obj}</option>
                                `);
                            });
                        }
                    });
                }
            } else {
                Swal.fire({
                    title: "Lo siento",
                    text: 'Debe Activar la Unión Izquierda para cargar los campos.',
                    confirmButtonText: "Aceptar",
                    icon: "error"
                }); 
            }
        });

        $(".activar_union_izquierda").change(function(){
            let valor = $(this).val();

            if(valor == "Si"){
                $(".tabla_principal_union_izquierda").removeAttr("disabled", "disabled");
                $(".tabla_secundaria_union_izquierda").removeAttr("disabled", "disabled");
                $(".campos_relacion_union_tabla_principal_izquierda").removeAttr("disabled", "disabled");
                $(".campos_relacion_union_tabla_secundaria_izquierda").removeAttr("disabled", "disabled");
                $(".esconder_tipo_union_izquierda").removeClass("d-none");
                $(".union_ocultar_izquierda").removeClass("d-none");
            } else {
                $(".tabla_principal_union_izquierda").attr("disabled", "disabled");
                $(".tabla_secundaria_union_izquierda").attr("disabled", "disabled");
                $(".campos_relacion_union_tabla_principal_izquierda").attr("disabled", "disabled");
                $(".campos_relacion_union_tabla_secundaria_izquierda").attr("disabled", "disabled");
                $(".esconder_tipo_union_izquierda").addClass("d-none");
                $(".union_ocultar_izquierda").addClass("d-none");
            }
        });

        $(".active_filter").change(function(){
            let valor = $(this).val();

            if(valor == "Si"){
                $(".mostrar_campos_filtro").removeAttr("disabled", "disabled");
                $(".posicion_filtro").removeAttr("disabled", "disabled");
                $(".esconder_tipo_filtro").removeClass("d-none");
                $(".tipo_de_filtro").removeAttr("disabled", "disabled");
            } else {
                $(".mostrar_campos_filtro").attr("disabled", "disabled");
                $(".posicion_filtro").attr("disabled", "disabled");
                $(".esconder_tipo_filtro").addClass("d-none");
                $(".tipo_de_filtro").attr("disabled", "disabled");
            }
        });

        $(".template_fields").change(function(){
            let valor = $(this).val();

            if(valor == "Si"){
                $(".ocultar_editor").removeClass("d-none");
            } else {
                $(".ocultar_editor").addClass("d-none");
            }
        });

        $(".tabla").change(function(){
            let val = $(this).val();

            $.ajax({
                type: "POST",
                url: "<?=$_ENV["BASE_URL"]?>obtener_id_tabla",
                dataType: 'json',
                data: {
                    val: val
                },
                beforeSend: function() {
                    $("#artify-ajax-loader").show();
                },
                success: function(data){
                    $("#artify-ajax-loader").hide();

                    if (val != "") {
                        // Asignar el valor del ID
                        $(".id_tabla").val(data["id_tablas"]);

                        $(".name_view").val(val);

                        $(".file_callback").val("function_"+val);

                        let controllerName = val.charAt(0).toUpperCase() + val.slice(1);
                        $(".controller_name").val(controllerName);

                        // Limpiar los selectores de campos y añadir la opción "Seleccionar"
                        $(".ocultar_label, .tabla_principal_union, .tabla_principal_union_izquierda, .tabla_secundaria_union, .tabla_secundaria_union_izquierda, .mostrar_campos_busqueda, .mostrar_campos_formulario, .mostrar_columnas_grilla, .mostrar_campos_filtro, .mostrar_campos_formulario_editar, .campos_condicion, .ordenar_grilla_por, .nombre_columnas, .nombre_campos, .campos_no_requeridos").empty();
                        $(".tabla_principal_union_izquierda, .tabla_secundaria_union_izquierda").html(`<option value>Seleccionar</option>`);
                        // Añadir nuevas opciones desde el resultado del ajax
                        $.each(data["columnas_tablas"], function(index, obj){
                            $(".ocultar_label, .mostrar_campos_busqueda, .mostrar_campos_formulario, .mostrar_columnas_grilla, .mostrar_campos_filtro, .mostrar_campos_formulario_editar, .campos_condicion, .ordenar_grilla_por, .nombre_columnas, .nombre_campos, .campos_no_requeridos").append(`
                                <option value="${obj}">${obj}</option>
                            `);
                        });

                        $.each(data["tablas"], function(index, obj){
                            $(".tabla_principal_union, .tabla_secundaria_union, .tabla_principal_union_izquierda, .tabla_secundaria_union_izquierda").append(`
                                <option value="${obj.nombre_tabla}">${obj.nombre_tabla}</option>
                            `);
                        });
                    } else {
                        // Limpiar los campos si val está vacío y añadir la opción "Seleccionar"
                        $(".ocultar_label, .mostrar_campos_busqueda, .mostrar_campos_formulario, .mostrar_columnas_grilla, .mostrar_campos_filtro, .mostrar_campos_formulario_editar, .campos_condicion, .ordenar_grilla_por, .nombre_columnas, .nombre_campos, .campos_no_requeridos").empty();
                        
                        // Vaciar el valor de id_tabla
                        $(".id_tabla").val("");

                        $(".name_view").val("");

                        $(".controller_name").val("");
                    }
                }
            });
        });

        $.ajax({
            type: "POST",
            url: "<?=$_ENV["BASE_URL"]?>obtener_tablas",
            dataType: "json",
            beforeSend: function() {
                $("#artify-ajax-loader").show();
            },
            success: function(data){
                $("#artify-ajax-loader").hide();
                
                // Limpiar opciones anteriores del select
                $(".tabla").empty();
                $(".tabla").html(`<option value>Seleccionar</option>`);
                // Añadir nuevas opciones desde el resultado del ajax
                $.each(data["tablas"], function(index, obj){
                    $(".tabla").append(`
                        <option value="${obj.nombre_tabla}">${obj.nombre_tabla}</option>
                    `);
                });
                
                // Actualizar select2 para que reconozca los nuevos valores
                $(".tabla").trigger('change'); 
            }
        });

        // Inicializar select2
        $(".tabla").select2();

        $(".titulo_modulo").text("Agregar");
        $('.siguiente_1').click(function() {
            $('#pdf-tab').tab('show');
        });

        $('.siguiente_2').click(function() {
            $('#Api-tab').tab('show');
        });

        $('.anterior').click(function() {
            $('#modulos-tab').tab('show');
        });

        $('.atras').click(function() {
            $('#pdf-tab').tab('show');
        });

        $(".modificar_tabla_col").hide();
        $(".campos_view_tabla").hide();

        $(".activate_pdf").change(function() {
            var val = $(this).val();

            if(val == "Si"){
                $(".logo_pdf").removeAttr("disabled", "disabled");
                $(".marca_de_agua_pdf").removeAttr("disabled", "disabled");
                $(".consulta_pdf").removeAttr("disabled", "disabled");
            } else {
                $(".logo_pdf").attr("disabled", "disabled");
                $(".marca_de_agua_pdf").attr("disabled", "disabled");
                $(".consulta_pdf").attr("disabled", "disabled");
            }
        });

        $(".template_fields").change(function() {
            var template_fields = $(this).val();

            if(template_fields == "Si"){
                $(".cantidad_campos_a_mostrar_plantilla_html").removeAttr("disabled", "disabled");
            } else {
                $(".cantidad_campos_a_mostrar_plantilla_html").attr("disabled", "disabled");
            }
        });
        
        $(".activar_recaptcha").change(function() {
            var val = $(this).val();

            if(val == "Si"){
                $(".sitekey_recaptcha").removeAttr("disabled", "disabled");
                $(".sitesecret_repatcha").removeAttr("disabled", "disabled");
            } else {
                $(".sitekey_recaptcha").attr("disabled", "disabled");
                $(".sitesecret_repatcha").attr("disabled", "disabled");
            }
        });

        $(".activate_nested_table").change(function() {
            var val = $(this).val();

            if(val == "Si"){
                $(".agregar_muestras").removeClass("d-none");
                $(".nivel").removeAttr("disabled", "disabled");
                $(".tabla_db").removeAttr("disabled", "disabled");
                $(".consulta_crear_tabla").removeAttr("disabled", "disabled");
                $(".name_controller_db").removeAttr("disabled", "disabled");
                $(".name_view_db").removeAttr("disabled", "disabled");
                $(".tabla_db").val("tabla_secundaria");
                $(".consulta_crear_tabla").val("id INT(11) AUTO_INCREMENT PRIMARY KEY,\n" +
                "nombre VARCHAR(255) NOT NULL,\n" +
                "apellido VARCHAR(255) NOT NULL,\n" +
                "categoria INT(11) NOT NULL,\n" +
                "producto VARCHAR(100) NOT NULL");
                $(".leftjoin_grilla").removeClass("d-none");
            } else {
                $(".agregar_muestras").addClass("d-none");
                $(".leftjoin_grilla").addClass("d-none");
                $(".nivel").attr("disabled", "disabled");
                $(".tabla_db").attr("disabled", "disabled");
                $(".consulta_crear_tabla").attr("disabled", "disabled");
                $(".name_controller_db").attr("disabled", "disabled");
                $(".name_view_db").attr("disabled", "disabled");
                $(".tabla_db").val("");
                $(".consulta_crear_tabla").val("");
            }
        });

        $(".crud_type").change(function() {
            var val = $(this).val();

            if (val == "CRUD") {
                $(".query").removeAttr("required").attr("disabled", "disabled");
                $(".mostrar_columnas_grilla").removeAttr("disabled", "disabled");
                $(".mostrar_campos_busqueda").removeAttr("disabled", "disabled");
                $(".mostrar_columna_acciones_grilla").removeAttr("disabled", "disabled");
                $(".mostrar_campos_formulario_editar").removeAttr("disabled", "disabled");
                $(".posicion_botones_accion_grilla").removeAttr("disabled", "disabled");
                $(".refrescar_grilla").removeAttr("disabled", "disabled");
                $(".nombre_columnas").removeAttr("disabled", "disabled");
                $(".ocultar_nuevo_nombre_columnas").removeClass("d-none");
                $(".ocultar_opcion_filtros").removeClass("d-none");

                $(".actions_buttons_grid").removeAttr("disabled", "disabled");
                $(".totalRecordsInfo").removeAttr("disabled", "disabled");
                $(".text_no_data").removeAttr("disabled", "disabled");
                $(".nuevo_nombre_columnas").removeAttr("disabled", "disabled");
                $(".actions_buttons_grid").bootstrapSwitch('disabled', false);

                $(".clone_row").removeAttr("disabled", "disabled");
                $(".activar_numeracion_columnas").removeAttr("disabled", "disabled");
                $(".mostrar_paginacion").removeAttr("disabled", "disabled");
                $(".cantidad_de_registros_por_pagina").removeAttr("disabled", "disabled");
                $(".activar_registros_por_pagina").removeAttr("disabled", "disabled");
                $(".posicionarse_en_la_pagina").removeAttr("disabled", "disabled");
                $(".activar_edicion_en_linea").removeAttr("disabled", "disabled");
                $(".activate_deleteMultipleBtn").removeAttr("disabled", "disabled");
                $(".active_popup").removeAttr("disabled", "disabled");
                $(".active_search").removeAttr("disabled", "disabled");
                $(".button_add").removeAttr("disabled", "disabled");
                $(".active_filter").removeAttr("disabled", "disabled");
                $(".function_filter_and_search").removeAttr("disabled", "disabled");
                $(".ordenar_grilla_por").removeAttr("disabled", "disabled");
                $(".tipo_orden").removeAttr("disabled", "disabled");

                $("input[value='Ver']").prop('disabled', false);
                $("input[value='Editar']").prop('disabled', false);
                $("input[value='Eliminar']").prop('disabled', false);
                $("input[value='Guardar y regresar']").prop('disabled', false);
                $("input[value='Regresar']").prop('disabled', false);
                $("input[value='Personalizado PDF']").prop('disabled', false);

                $("input[type='checkbox'][value='Ver']").closest('label').show();
                $("input[type='checkbox'][value='Editar']").closest('label').show();
                $("input[type='checkbox'][value='Eliminar']").closest('label').show();
                $("input[type='checkbox'][value='Guardar y regresar']").closest('label').show();
                $("input[type='checkbox'][value='Regresar']").closest('label').show();
                $("input[type='checkbox'][value='Personalizado PDF']").closest('label').show();

                $("input[value='Ver']").bootstrapSwitch('disabled', false);
                $("input[value='Editar']").bootstrapSwitch('disabled', false);
                $("input[value='Eliminar']").bootstrapSwitch('disabled', false);
                $("input[value='Guardar y regresar']").bootstrapSwitch('disabled', false);
                $("input[value='Regresar']").bootstrapSwitch('disabled', false);
                $("input[value='Personalizado PDF']").bootstrapSwitch('disabled', false);

            } else if (val == "Formulario de edición") {
                $(".query").attr("disabled", "disabled").val("");
                $(".mostrar_columnas_grilla").attr("disabled", "disabled");
                $(".mostrar_campos_busqueda").attr("disabled", "disabled");
                $(".mostrar_columna_acciones_grilla").attr("disabled", "disabled");
                $(".mostrar_campos_formulario").attr("disabled", "disabled");
                $(".posicion_botones_accion_grilla").attr("disabled", "disabled");
                $(".refrescar_grilla").attr("disabled", "disabled");
                $(".nombre_columnas").attr("disabled", "disabled");
                $(".ocultar_nuevo_nombre_columnas").addClass("d-none");
                $(".ocultar_opcion_filtros").addClass("d-none");

                $(".actions_buttons_grid").attr("disabled", "disabled");
                $(".totalRecordsInfo").attr("disabled", "disabled");
                $(".text_no_data").attr("disabled", "disabled");
                $(".nuevo_nombre_columnas").attr("disabled", "disabled");
                $(".actions_buttons_grid").bootstrapSwitch('disabled', true);

                $(".clone_row").attr("disabled", "disabled");
                $(".activar_numeracion_columnas").attr("disabled", "disabled");
                $(".mostrar_paginacion").attr("disabled", "disabled");
                $(".cantidad_de_registros_por_pagina").attr("disabled", "disabled");
                $(".activar_registros_por_pagina").attr("disabled", "disabled");
                $(".posicionarse_en_la_pagina").attr("disabled", "disabled");
                $(".activar_edicion_en_linea").attr("disabled", "disabled");
                $(".activate_deleteMultipleBtn").attr("disabled", "disabled");
                $(".active_popup").attr("disabled", "disabled");
                $(".active_search").attr("disabled", "disabled");
                $(".button_add").attr("disabled", "disabled");
                $(".active_filter").attr("disabled", "disabled");
                $(".function_filter_and_search").attr("disabled", "disabled");
                $(".ordenar_grilla_por").attr("disabled", "disabled");
                $(".tipo_orden").attr("disabled", "disabled");

                $("input[value='Ver']").prop('disabled', true);
                $("input[value='Editar']").prop('disabled', true);
                $("input[value='Eliminar']").prop('disabled', true);
                $("input[value='Guardar y regresar']").prop('disabled', true);
                $("input[value='Regresar']").prop('disabled', true);
                $("input[value='Personalizado PDF']").prop('disabled', true);

                $("input[type='checkbox'][value='Ver']").closest('label').hide();
                $("input[type='checkbox'][value='Editar']").closest('label').hide();
                $("input[type='checkbox'][value='Eliminar']").closest('label').hide();
                $("input[type='checkbox'][value='Guardar y regresar']").closest('label').hide();
                $("input[type='checkbox'][value='Regresar']").closest('label').hide();
                $("input[type='checkbox'][value='Personalizado PDF']").closest('label').hide();

                $("input[value='Ver']").bootstrapSwitch('disabled', true);
                $("input[value='Editar']").bootstrapSwitch('disabled', true);
                $("input[value='Eliminar']").bootstrapSwitch('disabled', true);
                $("input[value='Guardar y regresar']").bootstrapSwitch('disabled', true);
                $("input[value='Regresar']").bootstrapSwitch('disabled', true);
                $("input[value='Personalizado PDF']").bootstrapSwitch('disabled', true);

            } else if(val == "Formulario de inserción"){
                $(".query").attr("disabled", "disabled").val("");
                $(".mostrar_columnas_grilla").attr("disabled", "disabled");
                $(".mostrar_campos_busqueda").attr("disabled", "disabled");
                $(".mostrar_columna_acciones_grilla").attr("disabled", "disabled");
                $(".mostrar_campos_formulario_editar").attr("disabled", "disabled");
                $(".posicion_botones_accion_grilla").attr("disabled", "disabled");
                $(".refrescar_grilla").attr("disabled", "disabled");
                $(".nombre_columnas").attr("disabled", "disabled");
                $(".ocultar_nuevo_nombre_columnas").addClass("d-none");
                $(".ocultar_opcion_filtros").addClass("d-none");

                $(".actions_buttons_grid").attr("disabled", "disabled");
                $(".totalRecordsInfo").attr("disabled", "disabled");
                $(".text_no_data").attr("disabled", "disabled");
                $(".nuevo_nombre_columnas").attr("disabled", "disabled");
                $(".actions_buttons_grid").bootstrapSwitch('disabled', true);

                $(".clone_row").attr("disabled", "disabled");
                $(".activar_numeracion_columnas").attr("disabled", "disabled");
                $(".mostrar_paginacion").attr("disabled", "disabled");
                $(".cantidad_de_registros_por_pagina").attr("disabled", "disabled");
                $(".activar_registros_por_pagina").attr("disabled", "disabled");
                $(".posicionarse_en_la_pagina").attr("disabled", "disabled");
                $(".activar_edicion_en_linea").attr("disabled", "disabled");
                $(".activate_deleteMultipleBtn").attr("disabled", "disabled");
                $(".active_popup").attr("disabled", "disabled");
                $(".active_search").attr("disabled", "disabled");
                $(".button_add").attr("disabled", "disabled");
                $(".active_filter").attr("disabled", "disabled");
                $(".function_filter_and_search").attr("disabled", "disabled");
                $(".ordenar_grilla_por").attr("disabled", "disabled");
                $(".tipo_orden").attr("disabled", "disabled");

                $("input[value='Ver']").prop('disabled', true);
                $("input[value='Editar']").prop('disabled', true);
                $("input[value='Eliminar']").prop('disabled', true);
                $("input[value='Guardar y regresar']").prop('disabled', true);
                $("input[value='Regresar']").prop('disabled', true);
                $("input[value='Personalizado PDF']").prop('disabled', true);

                $("input[type='checkbox'][value='Ver']").closest('label').hide();
                $("input[type='checkbox'][value='Editar']").closest('label').hide();
                $("input[type='checkbox'][value='Eliminar']").closest('label').hide();
                $("input[type='checkbox'][value='Guardar y regresar']").closest('label').hide();
                $("input[type='checkbox'][value='Regresar']").closest('label').hide();
                $("input[type='checkbox'][value='Personalizado PDF']").closest('label').hide();

                $("input[value='Ver']").bootstrapSwitch('disabled', true);
                $("input[value='Editar']").bootstrapSwitch('disabled', true);
                $("input[value='Eliminar']").bootstrapSwitch('disabled', true);
                $("input[value='Guardar y regresar']").bootstrapSwitch('disabled', true);
                $("input[value='Regresar']").bootstrapSwitch('disabled', true);
                $("input[value='Personalizado PDF']").bootstrapSwitch('disabled', true);

            } else {
                $(".query").attr("required", "required").removeAttr("disabled");
                $(".query").val("SELECT\n" +
                "nombre as nombre,\n" +
                "apellido as apellido,\n" +
                "categoria as categoria\n" +
                "producto as producto FROM personas");

                $(".mostrar_columnas_grilla").attr("disabled", "disabled");
                $(".mostrar_campos_busqueda").removeAttr("disabled", "disabled");
                $(".mostrar_columna_acciones_grilla").removeAttr("disabled", "disabled");
                $(".mostrar_campos_formulario_editar").removeAttr("disabled", "disabled");
                $(".posicion_botones_accion_grilla").removeAttr("disabled", "disabled");
                $(".refrescar_grilla").removeAttr("disabled", "disabled");
                $(".nombre_columnas").removeAttr("disabled", "disabled");
                $(".ocultar_nuevo_nombre_columnas").removeClass("d-none");
                $(".ocultar_opcion_filtros").removeClass("d-none");

                $(".actions_buttons_grid").removeAttr("disabled", "disabled");
                $(".totalRecordsInfo").removeAttr("disabled", "disabled");
                $(".text_no_data").removeAttr("disabled", "disabled");
                $(".nuevo_nombre_columnas").removeAttr("disabled", "disabled");
                $(".actions_buttons_grid").bootstrapSwitch('disabled', false);

                $(".clone_row").removeAttr("disabled", "disabled");
                $(".activar_numeracion_columnas").removeAttr("disabled", "disabled");
                $(".mostrar_paginacion").removeAttr("disabled", "disabled");
                $(".cantidad_de_registros_por_pagina").removeAttr("disabled", "disabled");
                $(".activar_registros_por_pagina").removeAttr("disabled", "disabled");
                $(".posicionarse_en_la_pagina").removeAttr("disabled", "disabled");
                $(".activar_edicion_en_linea").removeAttr("disabled", "disabled");
                $(".activate_deleteMultipleBtn").removeAttr("disabled", "disabled");
                $(".active_popup").removeAttr("disabled", "disabled");
                $(".active_search").removeAttr("disabled", "disabled");
                $(".button_add").removeAttr("disabled", "disabled");
                $(".active_filter").removeAttr("disabled", "disabled");
                $(".function_filter_and_search").removeAttr("disabled", "disabled");
                $(".ordenar_grilla_por").attr("disabled", "disabled");
                $(".tipo_orden").attr("disabled", "disabled");

                $("input[value='Ver']").prop('disabled', false);
                $("input[value='Editar']").prop('disabled', false);
                $("input[value='Eliminar']").prop('disabled', false);
                $("input[value='Guardar y regresar']").prop('disabled', false);
                $("input[value='Regresar']").prop('disabled', false);
                $("input[value='Personalizado PDF']").prop('disabled', false);

                $("input[type='checkbox'][value='Ver']").closest('label').hide();
                $("input[type='checkbox'][value='Editar']").closest('label').hide();
                $("input[type='checkbox'][value='Eliminar']").closest('label').hide();
                $("input[type='checkbox'][value='Guardar y regresar']").closest('label').hide();
                $("input[type='checkbox'][value='Regresar']").closest('label').hide();
                $("input[type='checkbox'][value='Personalizado PDF']").closest('label').hide();

                $("input[value='Ver']").bootstrapSwitch('disabled', false);
                $("input[value='Editar']").bootstrapSwitch('disabled', false);
                $("input[value='Eliminar']").bootstrapSwitch('disabled', false);
                $("input[value='Guardar y regresar']").bootstrapSwitch('disabled', false);
                $("input[value='Regresar']").bootstrapSwitch('disabled', false);
                $("input[value='Personalizado PDF']").bootstrapSwitch('disabled', false);
            }
        });
    }

    if(dataAction == "edit"){

        $(".campos_requeridos").change(function(){
            let campos_requeridos = $(this).val();

            if(campos_requeridos == "Si"){
                $(".ocultar_campos_requeridos").removeClass("d-none");
            } else {
                $(".ocultar_campos_requeridos").addClass("d-none");
            }
        });

        const editor = grapesjs.init({
            container: '#editor',
            height: '679px',
            width: '100%',
            fromElement: true,
            storageManager: false,
            deviceManager: {
                devices: [
                { name: 'Escritorio', width: '' } // Solo define la vista de escritorio
                ]
            },
            blockManager: {
                appendTo: '#blocks',
                blocks: [
                {
                    id: 'row',
                    label: 'Agregar Fila',
                    attributes: { class: 'gjs-block-section' },
                    content: `
                    <div class="row pt-4" style="display: flex; justify-content: center; background: lightblue; padding: 50px; border: 1px solid #007bff;">
                        <!-- Las columnas se colocarán aquí -->
                    </div>`,
                },
                {
                    id: 'card',
                    label: 'Agregar Tarjeta',
                    attributes: { class: 'gjs-block-section' },
                    content: `
                    <div class="card" style="width:100%;">
                        <div class="card-header" style="background-color:green; padding: 10px; color: #fff;">
                            Titulo de la Tarjeta
                        </div>
                        <div class="card-body" style="background-color: blue; padding: 30px; color: #fff;">
                            <!-- Acá contenido de la tarjeta -->
                        </div>
                    </div>`,
                },
                {
                    id: 'column1',
                    label: 'Agregar 1 Columna',
                    attributes: { class: 'gjs-block-column' },
                    content: `
                    <div class="col-md" style="background: orange; border: 1px solid #000; height: 150px; display: flex; align-items: center; justify-content: center; height: 150px; width: 250px;">
                        <!-- Acá el contenido -->
                    </div>`,
                },
                {
                    id: 'campo',
                    label: 'Agregar Campo',
                    attributes: { class: 'gjs-block-field' },
                    content: `
                    <div class="form-group">
                        <label class="form-label" style="color:#fff; font-size: 20px;">Nombre Columna:</label>
                        <span class="editable" data-gjs-editable="true" style="color:#fff; font-size: 20px;">{nombre_campo}</span>
                        <p class="artify_help_block help-block form-text with-errors"></p>
                    </div>`,
                },
                {
                    id: 'column2',
                    label: 'Agregar 2 Columnas',
                    attributes: { class: 'gjs-block-column' },
                    content: `
                    <div class="col-md" style="background: orange; border: 1px solid #000; height: 150px; display: flex; align-items: center; justify-content: center; height: 150px; width: 250px;">
                        <!-- Acá el contenido -->
                    </div>
                    <div class="col-md" style="background: orange; border: 1px solid #000; height: 150px; display: flex; align-items: center; justify-content: center; height: 150px; width: 250px;">
                        <!-- Acá el contenido -->
                    </div>`,
                },
                {
                    id: 'column3',
                    label: 'Agregar 3 Columnas',
                    attributes: { class: 'gjs-block-column' },
                    content: `
                    <div class="col-md" style="background: orange; border: 1px solid #000; height: 150px; display: flex; align-items: center; justify-content: center; height: 150px; width: 250px;">
                        <!-- Acá el contenido -->
                    </div>
                    <div class="col-md" style="background: orange; border: 1px solid #000; height: 150px; display: flex; align-items: center; justify-content: center; height: 150px; width: 250px;">
                        <!-- Acá el contenido -->
                    </div>
                    <div class="col-md" style="background: orange; border: 1px solid #000; height: 150px; display: flex; align-items: center; justify-content: center; height: 150px; width: 250px;">
                       <!-- Acá el contenido -->
                    </div>`,
                },
                {
                    id: 'column4',
                    label: 'Agregar 4 Columnas',
                    attributes: { class: 'gjs-block-column' },
                    content: `
                    <div class="col-md" style="background: orange; border: 1px solid #000; height: 150px; display: flex; align-items: center; justify-content: center; height: 150px; width: 250px;">
                       <!-- Acá el contenido -->
                    </div>
                    <div class="col-md" style="background: orange; border: 1px solid #000; height: 150px; display: flex; align-items: center; justify-content: center; height: 150px; width: 250px;">
                       <!-- Acá el contenido -->
                    </div>
                    <div class="col-md" style="background: orange; border: 1px solid #000; height: 150px; display: flex; align-items: center; justify-content: center; height: 150px; width: 250px;">
                       <!-- Acá el contenido -->
                    </div>
                    <div class="col-md" style="background: orange; border: 1px solid #000; height: 150px; display: flex; align-items: center; justify-content: center; height: 150px; width: 250px;">
                       <!-- Acá el contenido -->
                    </div>`,
                },
                ],
            },
        });

        editor.Panels.removePanel('devices-c');
        editor.setDevice('Escritorio');

        editor.Panels.addButton('options', [{
            id: 'edit-html',
            className: 'fa fa-code',
            command: 'edit-html',
            attributes: { title: 'Editar HTML' }
        }]);

        editor.Commands.add('edit-html', {
            run(editor, sender) {
                sender && sender.set('active', false); // Desactiva el botón

                // Obtener el HTML y CSS actuales del editor
                const html = editor.getHtml();
                const css = editor.getCss();

                // Crear el modal para edición de HTML
                const modal = document.createElement('div');
                modal.style.position = 'fixed';
                modal.style.top = '0';
                modal.style.left = '0';
                modal.style.width = '100%';
                modal.style.height = '100%';
                modal.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
                modal.style.display = 'flex';
                modal.style.flexDirection = 'column';
                modal.style.justifyContent = 'center';
                modal.style.alignItems = 'center';
                modal.style.zIndex = '9999';

                const textarea = document.createElement('textarea');
                textarea.style.width = '80%';
                textarea.style.height = '60%';
                textarea.style.marginBottom = '10px';
                textarea.style.fontSize = '16px';
                textarea.value = `<style>${css}</style>\n${html}`;
                
                // Agregar una clase al textarea
                textarea.classList.add('custom-textarea'); // Aquí agregamos la clase 'custom-textarea'

                const saveButton = document.createElement('button');
                saveButton.classList.add('btn');
                saveButton.classList.add('btn-success');
                saveButton.innerText = 'Guardar';
                saveButton.style.marginRight = '10px';
                saveButton.onclick = () => {
                    // Aplicar el HTML modificado al editor
                    editor.setComponents(textarea.value);
                    document.body.removeChild(modal); // Cerrar el modal
                };

                const closeButton = document.createElement('button');
                closeButton.classList.add('btn');
                closeButton.classList.add('btn-danger');
                closeButton.innerText = 'Cerrar';
                closeButton.onclick = () => {
                    document.body.removeChild(modal); // Cerrar el modal sin guardar
                };

                const buttonContainer = document.createElement('div');
                buttonContainer.appendChild(saveButton);
                buttonContainer.appendChild(closeButton);

                modal.appendChild(textarea);
                modal.appendChild(buttonContainer);

                // Agregar el modal al DOM
                document.body.appendChild(modal);

                $(textarea).summernote({
                    height: 600, // Tamaño del editor
                    placeholder: 'Edita el HTML aquí...', // Texto de marcador de posición
                    toolbar: [
                        ['style', ['bold', 'italic', 'underline']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['insert', ['link', 'picture']],
                        ['view', ['fullscreen', 'codeview']]
                    ]
                });
            }
        });

        editor.I18n.addMessages({
            en: {
                // indicate the locale to update
                styleManager: {
                    empty: 'Nuevo mensaje de estado vacío',
                },
            },
        });

        editor.on('update', () => {
            const htmlContent = editor.getHtml();

            // Usar expresión regular para eliminar la etiqueta <body> y su contenido
            const cleanedHtmlContent = htmlContent
                .replace(/<body>([\s\S]*)<\/body>/, '$1') // Eliminar etiquetas <body>
                .replace(/<span class="editable">(.*?)<\/span>/g, '$1') // Eliminar <span class="editable">
                .trim(); // Eliminar espacios en blanco al inicio y al final

            const elements = document.querySelectorAll('.estructura_de_columnas_y_campos');
            elements.forEach((element) => {
                element.value = cleanedHtmlContent;
            });
        });

        $(".nuevo_nombre_columnas").tagsinput();

        $(".activar_union_interna").change(function(){
            let activar_union_interna = $(this).val();

            if(activar_union_interna == "Si"){
                $(".tabla_principal_union").removeAttr("disabled", "disabled");
                $(".tabla_secundaria_union").removeAttr("disabled", "disabled");
                $(".campos_relacion_union_tabla_principal").removeAttr("disabled", "disabled");
                $(".campos_relacion_union_tabla_secundaria").removeAttr("disabled", "disabled");
                $(".esconder_tipo_union").removeClass("d-none");
                $(".union_ocultar").removeClass("d-none");
            } else {
                $(".tabla_principal_union").attr("disabled", "disabled");
                $(".tabla_secundaria_union").attr("disabled", "disabled");
                $(".campos_relacion_union_tabla_principal").attr("disabled", "disabled");
                $(".campos_relacion_union_tabla_secundaria").attr("disabled", "disabled");
                $(".esconder_tipo_union").addClass("d-none");
                $(".union_ocultar").addClass("d-none");
            }
        });

        $(document).on("click", ".cargar_datos_izquierda", function(){
            let activar_union_izquierda = $(".activar_union_izquierda").val();
            let tabla_principal_union_izquierda = $(".tabla_principal_union_izquierda").val();
            let campos_relacion_union_tabla_principal_izquierda = $(".campos_relacion_union_tabla_principal_izquierda").val();
            let tabla_secundaria_union_izquierda = $(".tabla_secundaria_union_izquierda").val();
            let campos_relacion_union_tabla_secundaria_izquierda = $(".campos_relacion_union_tabla_secundaria_izquierda").val();

            if(activar_union_izquierda == "Si"){

                if(tabla_principal_union_izquierda == ""){
                    Swal.fire({
                        title: "Lo siento",
                        text: 'Ingrese Tabla Principal Union',
                        confirmButtonText: "Aceptar",
                        icon: "error"
                    }); 
                } else if(campos_relacion_union_tabla_principal_izquierda == ""){
                    Swal.fire({
                        title: "Lo siento",
                        text: 'Ingrese Relación Principal Union',
                        confirmButtonText: "Aceptar",
                        icon: "error"
                    }); 
                } else if(tabla_secundaria_union_izquierda == ""){
                    Swal.fire({
                        title: "Lo siento",
                        text: 'Ingrese Tabla secundaria Union',
                        confirmButtonText: "Aceptar",
                        icon: "error"
                    }); 
                } else if(tabla_secundaria_union_izquierda == ""){
                    Swal.fire({
                        title: "Lo siento",
                        text: 'Ingrese Relación Secundaria Union',
                        confirmButtonText: "Aceptar",
                        icon: "error"
                    });
                } else {
                    $.ajax({
                        type: "POST",
                        url: "<?=$_ENV["BASE_URL"]?>obtener_campos_union_izquierda",
                        dataType: "json",
                        data: {
                            tabla_principal_union_izquierda: tabla_principal_union_izquierda,
                            campos_relacion_union_tabla_principal_izquierda: campos_relacion_union_tabla_principal_izquierda,
                            tabla_secundaria_union_izquierda: tabla_secundaria_union_izquierda,
                            campos_relacion_union_tabla_secundaria_izquierda: campos_relacion_union_tabla_secundaria_izquierda
                        },
                        beforeSend: function() {
                            $("#artify-ajax-loader").show();
                        },
                        success: function(data){
                            $("#artify-ajax-loader").hide();

                            $(".ocultar_label, .ordenar_grilla_por, .mostrar_campos_busqueda, .mostrar_campos_formulario, .mostrar_columnas_grilla, .mostrar_campos_filtro, .mostrar_campos_formulario_editar, .nombre_columnas, .nombre_campos, .campos_no_requeridos").empty();
                            //$(".tabla_principal_union_izquierda, .tabla_secundaria_union_izquierda").html(`<option value>Seleccionar</option>`);

                            $.each(data["tabla1"], function(index, obj){
                                $(".ocultar_label, .ordenar_grilla_por, .mostrar_campos_busqueda, .mostrar_campos_formulario, .mostrar_columnas_grilla, .mostrar_campos_filtro, .mostrar_campos_formulario_editar, .nombre_columnas, .nombre_campos, .campos_no_requeridos").append(`
                                    <option value="${obj}">${obj}</option>
                                `);
                            });

                            $.each(data["tabla2"], function(index, obj){
                                $(".ocultar_label, .ordenar_grilla_por, .mostrar_campos_busqueda, .mostrar_campos_formulario, .mostrar_columnas_grilla, .mostrar_campos_filtro, .mostrar_campos_formulario_editar, .nombre_columnas, .nombre_campos, .campos_no_requeridos").append(`
                                    <option value="${obj}">${obj}</option>
                                `);
                            });
                        }
                    });
                }
            } else {
                Swal.fire({
                    title: "Lo siento",
                    text: 'Debe Activar la Unión Izquierda para cargar los campos.',
                    confirmButtonText: "Aceptar",
                    icon: "error"
                }); 
            }
        });

        $(".activar_union_izquierda").change(function(){
            let activar_union_izquierda = $(this).val();

            if(activar_union_izquierda == "Si"){
                $(".tabla_principal_union_izquierda").removeAttr("disabled", "disabled");
                $(".tabla_secundaria_union_izquierda").removeAttr("disabled", "disabled");
                $(".campos_relacion_union_tabla_principal_izquierda").removeAttr("disabled", "disabled");
                $(".campos_relacion_union_tabla_secundaria_izquierda").removeAttr("disabled", "disabled");
                $(".esconder_tipo_union_izquierda").removeClass("d-none");
                $(".union_ocultar_izquierda").removeClass("d-none");
            } else {
                $(".tabla_principal_union_izquierda").attr("disabled", "disabled");
                $(".tabla_secundaria_union_izquierda").attr("disabled", "disabled");
                $(".campos_relacion_union_tabla_principal_izquierda").attr("disabled", "disabled");
                $(".campos_relacion_union_tabla_secundaria_izquierda").attr("disabled", "disabled");
                $(".esconder_tipo_union_izquierda").addClass("d-none");
                $(".union_ocultar_izquierda").addClass("d-none");
            }
        });


        
        let active_filter = $(".active_filter").val();

        if(active_filter == "Si"){
            $(".mostrar_campos_filtro").removeAttr("disabled", "disabled");
            $(".posicion_filtro").removeAttr("disabled", "disabled");
            $(".esconder_tipo_filtro").removeClass("d-none");
            $(".tipo_de_filtro").removeAttr("disabled", "disabled");
        } else {
            $(".mostrar_campos_filtro").attr("disabled", "disabled");
            $(".posicion_filtro").attr("disabled", "disabled");
            $(".esconder_tipo_filtro").addClass("d-none");
            $(".tipo_de_filtro").attr("disabled", "disabled");
        }
        

        $(".active_filter").change(function(){
            let active_filter = $(this).val();

            if(active_filter == "Si"){
                $(".mostrar_campos_filtro").removeAttr("disabled", "disabled");
                $(".posicion_filtro").removeAttr("disabled", "disabled");
                $(".esconder_tipo_filtro").removeClass("d-none");
                $(".tipo_de_filtro").removeAttr("disabled", "disabled");
            } else {
                $(".mostrar_campos_filtro").attr("disabled", "disabled");
                $(".posicion_filtro").attr("disabled", "disabled");
                $(".esconder_tipo_filtro").addClass("d-none");
                $(".tipo_de_filtro").attr("disabled", "disabled");
            }
        });

        $(".activate_pdf").change(function() {
            var activate_pdf = $(this).val();

            if(activate_pdf == "Si"){
                $(".logo_pdf").removeAttr("disabled", "disabled");
                $(".marca_de_agua_pdf").removeAttr("disabled", "disabled");
                $(".consulta_pdf").removeAttr("disabled", "disabled");
            } else {
                $(".logo_pdf").attr("disabled", "disabled");
                $(".marca_de_agua_pdf").attr("disabled", "disabled");
                $(".consulta_pdf").attr("disabled", "disabled");
            }
        });
        
        $(".activar_recaptcha").change(function() {
            var activar_recaptcha = $(this).val();

            if(activar_recaptcha == "Si"){
                $(".sitekey_recaptcha").removeAttr("disabled", "disabled");
                $(".sitesecret_repatcha").removeAttr("disabled", "disabled");
            } else {
                $(".sitekey_recaptcha").attr("disabled", "disabled");
                $(".sitesecret_repatcha").attr("disabled", "disabled");
            }
        });

        $(".activate_nested_table").change(function() {
            var activate_nested_table = $(this).val();

            if(activate_nested_table == "Si"){
                $(".agregar_muestras").removeClass("d-none");
                $(".nivel").removeAttr("disabled", "disabled");
                $(".tabla_db").removeAttr("disabled", "disabled");
                $(".consulta_crear_tabla").removeAttr("disabled", "disabled");
                $(".name_controller_db").removeAttr("disabled", "disabled");
                $(".name_view_db").removeAttr("disabled", "disabled");
                $(".tabla_db").val("tabla_secundaria");
                $(".consulta_crear_tabla").val("id INT(11) AUTO_INCREMENT PRIMARY KEY,\n" +
                "nombre VARCHAR(255) NOT NULL,\n" +
                "apellido VARCHAR(255) NOT NULL,\n" +
                "categoria INT(11) NOT NULL,\n" +
                "producto VARCHAR(100) NOT NULL");
                $(".leftjoin_grilla").removeClass("d-none");
            } else {
                $(".agregar_muestras").addClass("d-none");
                $(".leftjoin_grilla").addClass("d-none");
                $(".nivel").attr("disabled", "disabled");
                $(".tabla_db").attr("disabled", "disabled");
                $(".consulta_crear_tabla").attr("disabled", "disabled");
                $(".name_controller_db").attr("disabled", "disabled");
                $(".name_view_db").attr("disabled", "disabled");
                $(".tabla_db").val("");
                $(".consulta_crear_tabla").val("");
            }
        });

        $(".tabla_left").hide();

        $("form").on("keypress", "input", function(event) {
            if (event.key === "Enter") {
                event.preventDefault();
                return false;
            }
        });

        $('.tipo_de_filtro').tagsinput({
            allowDuplicates: true
        });

        $(".tabla_anidada").removeClass("d-none");

        $(".regresar_tablas").click(function(){
            $('.leftjoin_tr').remove();
        });

        /*$("#create-tablas-tab, #create-pdf-tab").click(function(){
            $(".regresar_modulos").click();
        });*/

        $("#create-modulos-tab, #create-pdf-tab, #config-api-tab").click(function(){
            $('.leftjoin_tr').remove();
            $('.regresar_tablas').click();
        });

        $(".artify-button-add-row").attr("data-action", "edit_row_artify");

        $(".modificar_campo").each(function() {
            $(this).on('change', function() {
                checkInput();
            });
        });

        function checkInput() {
            let hasValue = false; // Variable para verificar si hay algún valor

            // Itera sobre cada campo de entrada
            $(".modificar_campo").each(function() {
                // Comprobar si el campo actual no está vacío
                if ($(this).val().trim() === "Si") {
                    hasValue = true; // Hay al menos un campo que tiene valor
                    return false; // Rompe el bucle each
                }
            });

            // Mostrar u ocultar el botón basado en si hay valores
            if (hasValue) {
                $(".generar_modificacion").removeClass("d-none"); // Mostrar el botón
            } else {
                $(".generar_modificacion").addClass("d-none"); // Ocultar el botón
            }
        }

        $(function() {
            var textosPermitidosList = ['Imagen', 'Archivo Único', 'Multiples Archivos', 'Radiobox', 'Checkbox', 'Combobox', 'Combobox Multiple', 'Campo de Texto', 'Campo de Área de Texto', 'Campo de Fecha', 'Campo de Fecha y Hora', 'Campo de Hora'];

            $('.type_fields').tagsinput({
                allowDuplicates: true,
                typeaheadjs: {
                    name: 'textosPermitidosList',
                    source: function(query, syncResults) {
                        // Filtra los elementos de la lista permitida según el término de búsqueda
                        var matches = textosPermitidosList.filter(function(item) {
                            return item.toLowerCase().indexOf(query.toLowerCase()) !== -1;
                        });
                        syncResults(matches);
                    }
                }
            });

            $('.type_fields').on('beforeItemAdd', function(event) {
                var texto = event.item;

                // Si el texto no está en la lista de permitidos, cancelamos la adición
                if (textosPermitidosList.indexOf(texto) === -1) {
                    event.cancel = true;
                    Swal.fire({
                        title: "Lo siento",
                        text: 'Este texto no está permitido.',
                        confirmButtonText: "Aceptar",
                        icon: "error"
                    });
                }
            });
        });


        $(".crud_type").change(function() {
            var crud_type = $(this).val();

            if (crud_type == "CRUD") {
                $(".query").removeAttr("required").attr("disabled", "disabled");
                $(".mostrar_columnas_grilla").removeAttr("disabled", "disabled");
                $(".mostrar_campos_busqueda").removeAttr("disabled", "disabled");
                $(".mostrar_columna_acciones_grilla").removeAttr("disabled", "disabled");
                $(".mostrar_campos_formulario_editar").removeAttr("disabled", "disabled");
                $(".posicion_botones_accion_grilla").removeAttr("disabled", "disabled");
                $(".refrescar_grilla").removeAttr("disabled", "disabled");
                $(".nombre_columnas").removeAttr("disabled", "disabled");
                $(".ocultar_nuevo_nombre_columnas").removeClass("d-none");
                $(".ocultar_opcion_filtros").removeClass("d-none");

                $(".actions_buttons_grid").removeAttr("disabled", "disabled");
                $(".totalRecordsInfo").removeAttr("disabled", "disabled");
                $(".text_no_data").removeAttr("disabled", "disabled");
                $(".nuevo_nombre_columnas").removeAttr("disabled", "disabled");
                $(".actions_buttons_grid").bootstrapSwitch('disabled', false);

                $(".clone_row").removeAttr("disabled", "disabled");
                $(".activar_numeracion_columnas").removeAttr("disabled", "disabled");
                $(".mostrar_paginacion").removeAttr("disabled", "disabled");
                $(".cantidad_de_registros_por_pagina").removeAttr("disabled", "disabled");
                $(".activar_registros_por_pagina").removeAttr("disabled", "disabled");
                $(".posicionarse_en_la_pagina").removeAttr("disabled", "disabled");
                $(".activar_edicion_en_linea").removeAttr("disabled", "disabled");
                $(".activate_deleteMultipleBtn").removeAttr("disabled", "disabled");
                $(".active_popup").removeAttr("disabled", "disabled");
                $(".active_search").removeAttr("disabled", "disabled");
                $(".button_add").removeAttr("disabled", "disabled");
                $(".active_filter").removeAttr("disabled", "disabled");
                $(".function_filter_and_search").removeAttr("disabled", "disabled");
                $(".ordenar_grilla_por").removeAttr("disabled", "disabled");
                $(".tipo_orden").removeAttr("disabled", "disabled");

                $("input[value='Ver']").prop('disabled', false);
                $("input[value='Editar']").prop('disabled', false);
                $("input[value='Eliminar']").prop('disabled', false);
                $("input[value='Guardar y regresar']").prop('disabled', false);
                $("input[value='Regresar']").prop('disabled', false);
                $("input[value='Personalizado PDF']").prop('disabled', false);

                $("input[type='checkbox'][value='Ver']").closest('label').show();
                $("input[type='checkbox'][value='Editar']").closest('label').show();
                $("input[type='checkbox'][value='Eliminar']").closest('label').show();
                $("input[type='checkbox'][value='Guardar y regresar']").closest('label').show();
                $("input[type='checkbox'][value='Regresar']").closest('label').show();
                $("input[type='checkbox'][value='Personalizado PDF']").closest('label').show();

                $("input[value='Ver']").bootstrapSwitch('disabled', false);
                $("input[value='Editar']").bootstrapSwitch('disabled', false);
                $("input[value='Eliminar']").bootstrapSwitch('disabled', false);
                $("input[value='Guardar y regresar']").bootstrapSwitch('disabled', false);
                $("input[value='Regresar']").bootstrapSwitch('disabled', false);
                $("input[value='Personalizado PDF']").bootstrapSwitch('disabled', false);

            } else if (crud_type == "Formulario de edición") {
                $(".query").attr("disabled", "disabled").val("");
                $(".mostrar_columnas_grilla").attr("disabled", "disabled");
                $(".mostrar_campos_busqueda").attr("disabled", "disabled");
                $(".mostrar_columna_acciones_grilla").attr("disabled", "disabled");
                $(".mostrar_campos_formulario").attr("disabled", "disabled");
                $(".posicion_botones_accion_grilla").attr("disabled", "disabled");
                $(".refrescar_grilla").attr("disabled", "disabled");
                $(".nombre_columnas").attr("disabled", "disabled");
                $(".ocultar_nuevo_nombre_columnas").addClass("d-none");
                $(".ocultar_opcion_filtros").addClass("d-none");

                $(".actions_buttons_grid").attr("disabled", "disabled");
                $(".totalRecordsInfo").attr("disabled", "disabled");
                $(".text_no_data").attr("disabled", "disabled");
                $(".nuevo_nombre_columnas").attr("disabled", "disabled");
                $(".actions_buttons_grid").bootstrapSwitch('disabled', true);

                $(".clone_row").attr("disabled", "disabled");
                $(".activar_numeracion_columnas").attr("disabled", "disabled");
                $(".mostrar_paginacion").attr("disabled", "disabled");
                $(".cantidad_de_registros_por_pagina").attr("disabled", "disabled");
                $(".activar_registros_por_pagina").attr("disabled", "disabled");
                $(".posicionarse_en_la_pagina").attr("disabled", "disabled");
                $(".activar_edicion_en_linea").attr("disabled", "disabled");
                $(".activate_deleteMultipleBtn").attr("disabled", "disabled");
                $(".active_popup").attr("disabled", "disabled");
                $(".active_search").attr("disabled", "disabled");
                $(".button_add").attr("disabled", "disabled");
                $(".active_filter").attr("disabled", "disabled");
                $(".function_filter_and_search").attr("disabled", "disabled");
                $(".ordenar_grilla_por").attr("disabled", "disabled");
                $(".tipo_orden").attr("disabled", "disabled");

                $("input[value='Ver']").prop('disabled', true);
                $("input[value='Editar']").prop('disabled', true);
                $("input[value='Eliminar']").prop('disabled', true);
                $("input[value='Guardar y regresar']").prop('disabled', true);
                $("input[value='Regresar']").prop('disabled', true);
                $("input[value='Personalizado PDF']").prop('disabled', true);

                $("input[type='checkbox'][value='Ver']").closest('label').hide();
                $("input[type='checkbox'][value='Editar']").closest('label').hide();
                $("input[type='checkbox'][value='Eliminar']").closest('label').hide();
                $("input[type='checkbox'][value='Guardar y regresar']").closest('label').hide();
                $("input[type='checkbox'][value='Regresar']").closest('label').hide();
                $("input[type='checkbox'][value='Personalizado PDF']").closest('label').hide();

                $("input[value='Ver']").bootstrapSwitch('disabled', true);
                $("input[value='Editar']").bootstrapSwitch('disabled', true);
                $("input[value='Eliminar']").bootstrapSwitch('disabled', true);
                $("input[value='Guardar y regresar']").bootstrapSwitch('disabled', true);
                $("input[value='Regresar']").bootstrapSwitch('disabled', true);
                $("input[value='Personalizado PDF']").bootstrapSwitch('disabled', true);

            } else if(crud_type == "Formulario de inserción"){
                $(".query").attr("disabled", "disabled").val("");
                $(".mostrar_columnas_grilla").attr("disabled", "disabled");
                $(".mostrar_campos_busqueda").attr("disabled", "disabled");
                $(".mostrar_columna_acciones_grilla").attr("disabled", "disabled");
                $(".mostrar_campos_formulario_editar").attr("disabled", "disabled");
                $(".posicion_botones_accion_grilla").attr("disabled", "disabled");
                $(".refrescar_grilla").attr("disabled", "disabled");
                $(".nombre_columnas").attr("disabled", "disabled");
                $(".ocultar_nuevo_nombre_columnas").addClass("d-none");
                $(".ocultar_opcion_filtros").addClass("d-none");

                $(".actions_buttons_grid").attr("disabled", "disabled");
                $(".totalRecordsInfo").attr("disabled", "disabled");
                $(".text_no_data").attr("disabled", "disabled");
                $(".nuevo_nombre_columnas").attr("disabled", "disabled");
                $(".actions_buttons_grid").bootstrapSwitch('disabled', true);

                $(".clone_row").attr("disabled", "disabled");
                $(".activar_numeracion_columnas").attr("disabled", "disabled");
                $(".mostrar_paginacion").attr("disabled", "disabled");
                $(".cantidad_de_registros_por_pagina").attr("disabled", "disabled");
                $(".activar_registros_por_pagina").attr("disabled", "disabled");
                $(".posicionarse_en_la_pagina").attr("disabled", "disabled");
                $(".activar_edicion_en_linea").attr("disabled", "disabled");
                $(".activate_deleteMultipleBtn").attr("disabled", "disabled");
                $(".active_popup").attr("disabled", "disabled");
                $(".active_search").attr("disabled", "disabled");
                $(".button_add").attr("disabled", "disabled");
                $(".active_filter").attr("disabled", "disabled");
                $(".function_filter_and_search").attr("disabled", "disabled");
                $(".ordenar_grilla_por").attr("disabled", "disabled");
                $(".tipo_orden").attr("disabled", "disabled");

                $("input[value='Ver']").prop('disabled', true);
                $("input[value='Editar']").prop('disabled', true);
                $("input[value='Eliminar']").prop('disabled', true);
                $("input[value='Guardar y regresar']").prop('disabled', true);
                $("input[value='Regresar']").prop('disabled', true);
                $("input[value='Personalizado PDF']").prop('disabled', true);

                $("input[type='checkbox'][value='Ver']").closest('label').hide();
                $("input[type='checkbox'][value='Editar']").closest('label').hide();
                $("input[type='checkbox'][value='Eliminar']").closest('label').hide();
                $("input[type='checkbox'][value='Guardar y regresar']").closest('label').hide();
                $("input[type='checkbox'][value='Regresar']").closest('label').hide();
                $("input[type='checkbox'][value='Personalizado PDF']").closest('label').hide();

                $("input[value='Ver']").bootstrapSwitch('disabled', true);
                $("input[value='Editar']").bootstrapSwitch('disabled', true);
                $("input[value='Eliminar']").bootstrapSwitch('disabled', true);
                $("input[value='Guardar y regresar']").bootstrapSwitch('disabled', true);
                $("input[value='Regresar']").bootstrapSwitch('disabled', true);
                $("input[value='Personalizado PDF']").bootstrapSwitch('disabled', true);

            } else {
                $(".query").attr("required", "required").removeAttr("disabled");
                $(".query").val("SELECT\n" +
                "nombre as nombre,\n" +
                "apellido as apellido,\n" +
                "categoria as categoria\n" +
                "producto as producto FROM personas");

                $(".mostrar_columnas_grilla").attr("disabled", "disabled");
                $(".mostrar_campos_busqueda").removeAttr("disabled", "disabled");
                $(".mostrar_columna_acciones_grilla").removeAttr("disabled", "disabled");
                $(".mostrar_campos_formulario_editar").removeAttr("disabled", "disabled");
                $(".posicion_botones_accion_grilla").removeAttr("disabled", "disabled");
                $(".refrescar_grilla").removeAttr("disabled", "disabled");
                $(".nombre_columnas").removeAttr("disabled", "disabled");
                $(".ocultar_nuevo_nombre_columnas").removeClass("d-none");
                $(".ocultar_opcion_filtros").removeClass("d-none");

                $(".actions_buttons_grid").removeAttr("disabled", "disabled");
                $(".totalRecordsInfo").removeAttr("disabled", "disabled");
                $(".text_no_data").removeAttr("disabled", "disabled");
                $(".nuevo_nombre_columnas").removeAttr("disabled", "disabled");
                $(".actions_buttons_grid").bootstrapSwitch('disabled', false);

                $(".clone_row").removeAttr("disabled", "disabled");
                $(".activar_numeracion_columnas").removeAttr("disabled", "disabled");
                $(".mostrar_paginacion").removeAttr("disabled", "disabled");
                $(".cantidad_de_registros_por_pagina").removeAttr("disabled", "disabled");
                $(".activar_registros_por_pagina").removeAttr("disabled", "disabled");
                $(".posicionarse_en_la_pagina").removeAttr("disabled", "disabled");
                $(".activar_edicion_en_linea").removeAttr("disabled", "disabled");
                $(".activate_deleteMultipleBtn").removeAttr("disabled", "disabled");
                $(".active_popup").removeAttr("disabled", "disabled");
                $(".active_search").removeAttr("disabled", "disabled");
                $(".button_add").removeAttr("disabled", "disabled");
                $(".active_filter").removeAttr("disabled", "disabled");
                $(".function_filter_and_search").removeAttr("disabled", "disabled");
                $(".ordenar_grilla_por").attr("disabled", "disabled");
                $(".tipo_orden").attr("disabled", "disabled");

                $("input[value='Ver']").prop('disabled', false);
                $("input[value='Editar']").prop('disabled', false);
                $("input[value='Eliminar']").prop('disabled', false);
                $("input[value='Guardar y regresar']").prop('disabled', false);
                $("input[value='Regresar']").prop('disabled', false);
                $("input[value='Personalizado PDF']").prop('disabled', false);

                $("input[type='checkbox'][value='Ver']").closest('label').hide();
                $("input[type='checkbox'][value='Editar']").closest('label').hide();
                $("input[type='checkbox'][value='Eliminar']").closest('label').hide();
                $("input[type='checkbox'][value='Guardar y regresar']").closest('label').hide();
                $("input[type='checkbox'][value='Regresar']").closest('label').hide();
                $("input[type='checkbox'][value='Personalizado PDF']").closest('label').hide();

                $("input[value='Ver']").bootstrapSwitch('disabled', false);
                $("input[value='Editar']").bootstrapSwitch('disabled', false);
                $("input[value='Eliminar']").bootstrapSwitch('disabled', false);
                $("input[value='Guardar y regresar']").bootstrapSwitch('disabled', false);
                $("input[value='Regresar']").bootstrapSwitch('disabled', false);
                $("input[value='Personalizado PDF']").bootstrapSwitch('disabled', false);
            }
        });
    

        $(".artify-cancel-btn").click(function(){
            $("#generateSQL").addClass("d-none");
        });

        //$("#generateSQL").removeClass("d-none");
        $(".eliminar_filas").removeClass("d-none");
        $(".modificar_campo").removeClass("d-none");
        $(".agregar_campo").removeClass("d-none");

        /*$("input[type='text'][name='estructura_tabla#$nombre_campo[]']").each(function() {
            $(this).attr('readonly', true); // Limpia el valor del campo de texto
        });*/

        $("#generateSQL").on("click", function() {
            let sqlStatements = `\n`;
            const rows = document.querySelectorAll(".artify-table tbody tr");

            rows.forEach((row) => {
                const nombreCampo = row.querySelector("input[name='nombre_campo[]']").value;
                const nuevoNombreCampo = row.querySelector("input[name='nombre_nuevo_campo[]']").value;
                const tipoCampo = row.querySelector("select[name='tipo[]']").value;
                const caracteres = row.querySelector("input[name='caracteres[]']").value;
                const autoincremental = row.querySelector("select[name='autoincremental[]']").value;
                const indice = row.querySelector("select[name='indice[]']").value;
                const valorNulo = row.querySelector("select[name='valor_nulo[]']").value;

                // Verificar si el checkbox de modificado existe
                let modificado = row.querySelector("select[name='modificar_campo[]']").value;

                if (modificado == "Si") {
                    // Construir el tipo de datos
                    let tipoSQL = "";
                    let valorSQL = "";
                    if (tipoCampo === "Caracteres") {
                        tipoSQL += `VARCHAR(${caracteres})`;
                    }

                    if (tipoCampo === "Entero") {
                        tipoSQL += `INT(${caracteres})`;
                    }

                    if (tipoCampo === "Fecha") {
                        tipoSQL += `DATE`;
                    }

                    if (tipoCampo === "Texto") {
                        tipoSQL += `TEXT`;
                    }

                    if (tipoCampo === "Hora") {
                        tipoSQL += `TIME`;
                    }

                    if (valorNulo === "No") {
                        valorSQL += `NOT NULL`;
                    } else {
                        valorSQL += `NULL`;
                    }

                    let alterSQL = "";
                    // Verificar si es autoincremental
                    if (autoincremental === "Si" && indice === "Primario" && valorNulo === "No") {
                        alterSQL += "MODIFY "+ nombreCampo + " " + tipoSQL +" NOT NULL; \n" +
                        "DROP PRIMARY KEY; \n" +
                        "CHANGE " + nombreCampo + " " + nuevoNombreCampo + " " + tipoSQL + " NOT NULL; \n" +
                        "MODIFY " + nuevoNombreCampo + " " + tipoSQL + " AUTO_INCREMENT PRIMARY KEY NOT NULL;";
                    } else {
                        // Construir la columna
                        alterSQL = `CHANGE ${nombreCampo} ${nuevoNombreCampo} ${tipoSQL} ${valorSQL}`;
                    }

                    // Añadir esta consulta al resultado final
                    sqlStatements += alterSQL + ",\n";
                }
            });

            // Remover la última coma y salto de línea si hay campos modificados
            if (sqlStatements.trim().length > 0) {
                sqlStatements = sqlStatements.trim().slice(0, -1);
            }

            // Colocar el resultado en el textarea
            document.querySelector(".modificar_tabla").value = sqlStatements;

            $("input[type='text'][name='estructura_tabla#$nombre_nuevo_campo[]']").each(function() {
                $(this).val(''); // Limpia el valor del campo de texto
            });
            $(".modificar_campo").val("");
        });

        $(".artify-actions.btn.btn-danger.eliminar_filas").first().remove();
        
        $(".nombre_tabla").attr("readonly", true);

        /*$("#create-tablas-tab, #create-pdf-tab").click(function(){
            $(".regresar_modulos").click();
        });*/

        let tabla = $(".nombre_tabla").val();

        $.ajax({
            type: "POST",
            url: "<?=$_ENV["BASE_URL"]?>obtener_columnas_tabla",
            dataType: 'json',
            data: {
                tabla: tabla
            },
            beforeSend: function() {
                $("#artify-ajax-loader").show();
            },
            success: function(data){
                $("#artify-ajax-loader").hide();

                $(".campo_anterior").each(function(index) {
                    // If there are enough data items, set the value
                    if (data["columnas_tablas"][index]) {
                        $(this).attr('readonly', true);
                        $(this).val(data["columnas_tablas"][index]);
                    } else {
                        // Clear the field if no corresponding data is available
                        $(this).val('');
                    }
                });
            }
        });

        $.ajax({
            type: "POST",
            url: "<?=$_ENV["BASE_URL"]?>obtener_tabla_id",
            dataType: "json",
            data: {
                dataId: dataId
            },
            beforeSend: function() {
                $("#artify-ajax-loader").show();
            },
            success: function(data){
                $("#artify-ajax-loader").hide();
                
                if (data && data.modulos && data.modulos.length > 0) {
                    // Agregar la opción seleccionada al select
                    $(".tabla").append(`
                        <option selected value="${data.modulos[0].tabla}">${data.modulos[0].tabla}</option>
                    `);
                    
                    // Actualizar select2 para que reconozca los nuevos valores
                    $(".tabla").trigger('change'); 

                    let val = $(".tabla").val();

                    $.ajax({
                        type: "POST",
                        url: "<?=$_ENV["BASE_URL"]?>obtener_id_tabla",
                        dataType: 'json',
                        data: {
                            val: val
                        },
                        beforeSend: function() {
                            $("#artify-ajax-loader").show();
                        },
                        success: function(data){
                            $("#artify-ajax-loader").hide();

                            if (val != "") {
                                // Asignar el valor del ID
                                $(".id_tabla").val(data["id_tablas"]);

                                $(".name_view").val(val);

                                let controllerName = val.charAt(0).toUpperCase() + val.slice(1);
                                $(".controller_name").val(controllerName);

                                // Limpiar los selectores de campos y añadir la opción "Seleccionar"
                                $(".ocultar_label, .tabla_principal_union, .tabla_principal_union_izquierda, .tabla_secundaria_union, .tabla_secundaria_union_izquierda, .mostrar_campos_busqueda, .mostrar_campos_formulario, .mostrar_columnas_grilla, .mostrar_campos_filtro, .mostrar_campos_formulario_editar, .campos_condicion, .ordenar_grilla_por, .nombre_columnas, .nombre_campos, .campos_no_requeridos").empty();
                                $(".tabla_principal_union_izquierda, .tabla_secundaria_union_izquierda").html(`<option value>Seleccionar</option>`);
                                // Añadir nuevas opciones desde el resultado del ajax
                                $.each(data["columnas_tablas"], function(index, obj){
                                    $(".ocultar_label, .mostrar_campos_busqueda, .mostrar_campos_formulario, .mostrar_columnas_grilla, .mostrar_campos_filtro, .mostrar_campos_formulario_editar, .campos_condicion, .ordenar_grilla_por, .nombre_columnas, .nombre_campos, .campos_no_requeridos").append(`
                                        <option value="${obj}">${obj}</option>
                                    `);
                                });

                                $.each(data["tablas"], function(index, obj){
                                    $(".tabla_principal_union, .tabla_secundaria_union, .tabla_principal_union_izquierda, .tabla_secundaria_union_izquierda").append(`
                                        <option value="${obj.nombre_tabla}">${obj.nombre_tabla}</option>
                                    `);
                                });
                            } else {
                                // Limpiar los campos si val está vacío y añadir la opción "Seleccionar"
                                $(".ocultar_label, .mostrar_campos_busqueda, .mostrar_campos_formulario, .mostrar_columnas_grilla, .mostrar_campos_filtro, .mostrar_campos_formulario_editar, .campos_condicion, .ordenar_grilla_por, .nombre_columnas, .nombre_campos, .campos_no_requeridos").empty();
                                
                                // Vaciar el valor de id_tabla
                                $(".id_tabla").val("");

                                $(".name_view").val("");

                                $(".controller_name").val("");
                            }
                        }
                    });

                } else {
                    console.warn("No se encontraron módulos válidos en la respuesta.");
                }
            }
        });

        if (!$(".tabla").hasClass("select2-hidden-accessible")) {
            $(".tabla").select2();
        }

        var template_fields = $(".template_fields").val();

        if(template_fields == "Si"){
            $(".ocultar_editor").removeClass("d-none");
            $(".cantidad_campos_a_mostrar_plantilla_html").removeAttr("disabled", "disabled");
        } else {
            $(".ocultar_editor").addClass("d-none");
            $(".cantidad_campos_a_mostrar_plantilla_html").attr("disabled", "disabled");
        }

        $(".template_fields").change(function() {
            var template_fields = $(this).val();

            if(template_fields == "Si"){
                $(".ocultar_editor").removeClass("d-none");
                $(".cantidad_campos_a_mostrar_plantilla_html").removeAttr("disabled", "disabled");
            } else {
                $(".ocultar_editor").addClass("d-none");
                $(".cantidad_campos_a_mostrar_plantilla_html").attr("disabled", "disabled");
            }
        });

        var valcaptcha = $(".activar_recaptcha").val();

        if(valcaptcha == "Si"){
            $(".sitekey_recaptcha").removeAttr("disabled", "disabled");
            $(".sitesecret_repatcha").removeAttr("disabled", "disabled");
        } else {
            $(".sitekey_recaptcha").attr("disabled", "disabled");
            $(".sitesecret_repatcha").attr("disabled", "disabled");
        }

        $(".activar_recaptcha").change(function() {
            var val = $(this).val();

            if(val == "Si"){
                $(".sitekey_recaptcha").removeAttr("disabled", "disabled");
                $(".sitesecret_repatcha").removeAttr("disabled", "disabled");
            } else {
                $(".sitekey_recaptcha").attr("disabled", "disabled");
                $(".sitesecret_repatcha").attr("disabled", "disabled");
            }
        });

        $(".titulo_modulo").text("Editar");
        $('.siguiente_1').click(function() {
            $('#pdf-tab').tab('show');
        });

        $('.siguiente_2').click(function() {
            $('#Api-tab').tab('show');
        });

        $('.anterior').click(function() {
            $('#modulos-tab').tab('show');
        });

        $('.atras').click(function() {
            $('#pdf-tab').tab('show');
        });

        $(".modificar_tabla_col").show();

        var crud_type = $(".crud_type").val();

        if (crud_type == "CRUD") {
            $(".query").removeAttr("required").attr("disabled", "disabled");
        } else if (crud_type == "Modulo de Inventario") {
            $(".query").removeAttr("required").attr("disabled", "disabled");
            $(".tabla").val("Inventario");
            $(".name_view").val("Inventario");
            $(".controller_name").val("Inventario");
        } else {
            $(".id_tabla").removeAttr("disabled").attr("required", "required").val("");
            $(".query").attr("required", "required").removeAttr("disabled");
        }
    }

    if(dataAction == "delete"){
        refrechMenu();
    }
});


function refrechMenu(){
	$.ajax({
		type: "POST",
		url: "<?=$_ENV["BASE_URL"]?>refrescarMenu",
		dataType: "json",
		success: function(response){
            console.log(response);
			$('.menu_generator').html(response);
		}
	});
}

$(document).on("artify_after_submission", function(event, obj, data){
    let json = JSON.parse(data);

    if(json.message){
        refrechMenu();
        Swal.fire({
            title: "Genial!",
            text: json.message,
            icon: "success",
            confirmButtonText: "Aceptar"
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: "Guardado con éxito!",
                    icon: "success",
                    confirmButtonText: "Aceptar"
                });
                $('.artify-back').click();
            }
        });
    }
});

</script>
<?php include '/home/hospitaldem/public_html/sistema_tickets/app/core/cache/eb33b26ce860a017f36edb2761467684.php'; ?>