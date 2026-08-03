@include('layouts/header')
@include('layouts/sidebar')
<link href='{{ $_ENV["BASE_URL"] }}css/sweetalert2.min.css' rel="stylesheet">
<style>
    .artify-button-save {
        display: none!important;
    }

    .ticket-wrapper {
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
  border-radius:12px;
  font-size:17px;
  font-weight:600;
}
.btn-primary:hover {
  transform:translateY(-1px);
}
</style>
<div class="content-wrapper">
    <section class="content">
        <div class="card mt-4">
            <div class="card-body">

                <div class="row">
                    <div class="col-md-12">
                        {!! $render !!}
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        {!! $render_area !!}
                    </div>
                </div>

            </div>
        </div>
    </section>
</div>
<div id="artify-ajax-loader">
    <img width="300" src='{{ $_ENV["BASE_URL"] }}app/libs/artify/images/ajax-loader.gif' class="artify-img-ajax-loader"/>
</div>
@include('layouts/footer')
<script src='{{ $_ENV["BASE_URL"] }}js/sweetalert2.all.min.js'></script>
<script>
    $(document).on("artify_after_ajax_action", function(event, obj, data){
        var dataAction = obj.getAttribute('data-action');
        var dataId = obj.getAttribute('data-id');

        if(dataAction == "add"){
        
        }

        if(dataAction == "edit"){
        
        }

        change_state();
    });
    $(document).on("artify_after_submission", function(event, obj, data) {
        let json = JSON.parse(data);

        if (json.message) {
            $(".alert-success").hide();
            $(".alert-danger").hide();

            Swal.fire({
                icon: "success",
                text: json["message"],
                confirmButtonText: "Aceptar",
                allowOutsideClick: false
            });
        }
    });
</script>