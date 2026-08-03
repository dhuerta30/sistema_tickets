<?php

namespace App\Controllers;

use App\core\Token;
use App\core\Request;
use App\core\ArtifyStencil;
use App\core\Redirect;
use App\core\DB;

class formularioFallaController {
    public function index()
    {
        date_default_timezone_set("America/Santiago");
        $fecha = date("Y-m-d");
        $artify = DB::ArtifyCrud();

        $html_template = '
        <div class="ticket-wrapper">
            <div class="ticket-card">
                <div class="ticket-header">
                    <div class="ticket-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>Generar Ticket</h3>
                    <p>Mesa de Ayuda TI Hospitalaria</p>
                </div>
                <div class="ticket-body">
                    {fecha}
                    {estado}
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-section">
                                <h6>
                                    <i class="fas fa-user"></i>
                                    Datos del Funcionario
                                </h6>
                                <div class="form-group row">
                                    <label class="control-label col-md-3 col-form-label">Nombre:</label>
                                    <div class="col-md-9">
                                        {nombre}
                                        <span><strong>Campo Obligatorio</strong></span>
                                        <p class="pdocrud_help_block help-block form-text with-errors"></p>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="control-label col-md-3 col-form-label">Correo:</label>
                                    <div class="col-md-9">
                                        {correo}
                                        <span><strong>Campo Obligatorio</strong></span>
                                        <p class="pdocrud_help_block help-block form-text with-errors"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-section">
                                <h6>
                                    <i class="fa fa-building"></i>
                                    Ubicación
                                </h6>
                                <div class="form-group row">
                                    <label class="control-label col-md-3 col-form-label">Área:</label>
                                    <div class="col-md-9">
                                        {area}
                                        <span><strong>Campo Obligatorio</strong></span>
                                        <p class="pdocrud_help_block help-block form-text with-errors"></p>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="control-label col-md-3 col-form-label">Sector:</label>
                                    <div class="col-md-9">
                                        {sector_funcionario}
                                        <span><strong>Campo Obligatorio</strong></span>
                                        <p class="pdocrud_help_block help-block form-text with-errors"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-section">
                        <h6>
                            <i class="fa fa-wrench"></i>
                            Información del requerimiento
                        </h6>
                        <div class="form-group row">
                            <label class="control-label col-md-3 col-form-label">Fallas:</label>
                            <div class="col-md-9">
                                {fallas}
                                <span><strong>Campo Obligatorio</strong></span>
                                <p class="pdocrud_help_block help-block form-text with-errors"></p>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="control-label col-md-3 col-form-label">Ubicación:</label>
                            <div class="col-md-9">
                                {ubicacion}
                                <span><strong>Campo Obligatorio</strong></span>
                                <p class="pdocrud_help_block help-block form-text with-errors"></p>
                            </div>
                        </div>
                    </div>
                    <div class="form-section">
                        <h6>
                            <i class="fa fa-camera"></i>
                            Evidencia
                        </h6>
                        <div class="form-group row">
                            <label class="control-label col-md-3 col-form-label">Foto:</label>
                            <div class="col-md-9">
                                {foto}
                                <p class="pdocrud_help_block help-block form-text with-errors"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
        $artify->set_template($html_template);
        $artify->addPlugin("select2");
        $artify->addCallback("before_insert", [$this, "capturar_foto"]);
        $artify->addCallback("after_insert", [$this, "insertar_ticket"]);
        $artify->fieldNotMandatory("nombreTecnico");
        $artify->fieldHideLable("nombreTecnico");
        $artify->fieldDataAttr("nombreTecnico", array("style"=>"display:none"));
        $artify->formFieldValue("estado", "Pendiente");
        $artify->formFieldValue("fecha", $fecha);
        $artify->fieldHideLable("estado");
        $artify->fieldDataAttr("estado", array("style"=>"display:none"));
        $artify->fieldTypes("correo", "email");

        $artify->fieldHideLable("fecha");
        $artify->fieldDataAttr("fecha", array("style"=>"display:none"));
        $artify->fieldTypes("ubicacion", "textarea");
    
        $artify->buttonHide("cancel");
        $artify->setLangData("save",'Generar Ticket');

        $artify->fieldRenameLable("fallas", "¿Cual es su requerimiento?");
        $artify->fieldRenameLable("nombre", "Nombre Completo del Funcionario");
        $artify->fieldRenameLable("area", "Unidad");
        $artify->fieldRenameLable("correo", "Correo del Funcionario");
        $artify->fieldRenameLable("sector_funcionario", "Unidad Origen Requerimiento");
        $artify->fieldRenameLable("ubicacion", "Descripción");

        $artify->relatedData('sector_funcionario','sector','id_sector','nombre_sector');

        $artify->fieldTypes('fallas','select');
        $artify->fieldDataBinding('fallas','fallas','id_falla','nombre_fa', 'db');

        $artify->fieldTypes('area','select');
        $artify->fieldDataBinding("area", "area", "id_area", "nombre", "db");

        $artify->fieldDependent("fallas", "area", "id_area");

        $artify->fieldCssClass("fallas", array("fallas"));
        $artify->fieldCssClass("ubicacion", array("descripcion"));

        $artify->fieldCssClass("nombre", array("nombre"));
        $artify->fieldCssClass("correo", array("correo"));
        $artify->fieldCssClass("area", array("area"));
        $artify->fieldCssClass("fallas", array("fallas"));
        $artify->fieldCssClass("sector_funcionario", array("sector_funcionario"));
        $artify->fieldCssClass("foto", array("foto"));

        $artify->fieldNotMandatory("foto");

        $artify->fieldTypes("foto", "file_multi");
        $artify->fieldAttributes("foto", array("accept" => "image/*", "capture"=>"camera"));

        $artify->fieldDesc("foto", "Campo Opcional");

        $artify->formFields(array("nombre","fecha","correo", "area", "fallas", "sector_funcionario", "ubicacion", "estado", "foto"));
        
        $render = $artify->dbTable("tickets")->render("insertform");
        $select2 = $artify->loadPluginJsCode("select2",".sector_funcionario, .fallas");

        $stencil = new ArtifyStencil();
        echo $stencil->render('formularioFalla', [
            'render' =>$render,
            'select2' => $select2
        ]);
    }

    public function capturar_foto($data, $obj){
        if (!empty($data["tickets"]["foto"])) {
            $extPermitidas = ["png", "PNG", "jpg", "jpeg", "webp", "svg"];
            $fotos = explode(",", $data["tickets"]["foto"]);
            $soloNombres = [];
            foreach ($fotos as $foto) {
                $foto = trim($foto);
                if ($foto === "") continue;
                $nombre = basename($foto);
                $ext = pathinfo($nombre, PATHINFO_EXTENSION);
                if (!in_array($ext, $extPermitidas)) {
                    $error_msg = array(
                        "message" => "",
                        "error" => "El Archivo Subido no es una imagen",
                        "redirectionurl" => ""
                    );
                    die(json_encode($error_msg));
                }
                $soloNombres[] = $nombre;
            }
            $data["tickets"]["foto"] = implode(",", $soloNombres);
        }
        return $data;
    }

    public function insertar_ticket($data, $obj){
        $id = $data;
        $queryfy = $obj->getQueryfyObj();
        $queryfy->where("id_tickets", $id);
        $result = $queryfy->select("tickets");
        $correo = $result[0]["correo"];
        $area = $result[0]["area"];
        $fallas = $result[0]["fallas"];
        $queryfy->where("id_area", $area);
        $dbAreas = $queryfy->select("area");
        $nombreArea = substr($dbAreas[0]["nombre"], 0, 4);
        $queryfy->where("id_falla", $fallas);
        $dbFallas = $queryfy->select("fallas");
        $nombreFalla = substr($dbFallas[0]["nombre_fa"], 0, 4);
        $prefijoTicket = $nombreArea . $nombreFalla;
        $queryfy->where("n_ticket", '%'. $prefijoTicket . '%', "LIKE");
        $ticketsExistentes = $queryfy->select("tickets");
        $siguienteNumero = 1;
        if(!empty($ticketsExistentes)){
            $numeros = [];
            foreach($ticketsExistentes as $t){
                $n = str_replace($prefijoTicket, '', $t['n_ticket']);
                if(is_numeric($n)){
                    $numeros[] = (int)$n;
                }
            }
            if(!empty($numeros)){
                $siguienteNumero = max($numeros) + 1;
            }
        }
        $numeroTicketFormateado = str_pad($siguienteNumero, 2, '0', STR_PAD_LEFT);
        $n_ticket_final = $prefijoTicket . $numeroTicketFormateado;
        $queryfy->where("correo", $correo);
        $queryfy->update("tickets", array("n_ticket" => $n_ticket_final));
        $emailBody = "Se ha generado el Ticket con número $n_ticket_final satisfactoriamente, a la brevedad un técnico tomará su solicitud";
        $subject = "Ticket Generado";
        $to = $correo;
        DB::PHPMail($to, $correo, $subject, $emailBody);
        $obj->setLangData("success", "Se ha generado el Ticket con número $n_ticket_final satisfactoriamente, a la brevedad un técnico tomará su solicitud");
        return $data;
    }

    public function registrar_funcionarios(){
        $artify = DB::ArtifyCrud();
        $artify->buttonHide("cancel");
        $artify->setLangData("save",'Registrar');
        $artify->addCallback("before_insert", [$this, "agregar_funcionarios"]);
        $render = $artify->dbTable("funcionarios")->render("insertform");

        $stencil = new ArtifyStencil();
        echo $stencil->render('registrar_funcionarios', [
            'render' =>$render
        ]);
    }

    public function agregar_funcionarios($data, $obj){
        return $data;
    }
}
