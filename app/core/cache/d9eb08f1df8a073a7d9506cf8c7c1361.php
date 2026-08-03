<!DOCTYPE html>
<html lang="es">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo htmlspecialchars($_ENV["APP_NAME"], ENT_QUOTES, 'UTF-8'); ?> | Login</title>
	<!-- Google Font: Source Sans Pro -->
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href='<?php echo htmlspecialchars($_ENV["BASE_URL"], ENT_QUOTES, 'UTF-8'); ?>theme/plugins/fontawesome-free/css/all.min.css }}'>
</head>
<body>
<style>
body {
    background:
    linear-gradient(
        135deg,
        #0d6efd,
        #0b3954
    )!important;
    min-height:100vh;
    font-family:'Source Sans Pro', sans-serif;
}
.hospital-login-wrapper {
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
}
.hospital-login-card {
    width:420px;
    background:white;
    border-radius:20px;
    padding:35px 45px;
    box-shadow:
    0 15px 40px rgba(0,0,0,.25);
}
.hospital-header {
    text-align:center;
    margin-bottom:20px;
}
.hospital-icon {
    width:70px;
    height:70px;
    margin:auto;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#fff;
    color:white;
    border-radius:50%;
    font-size:32px;
    border: 1px solid #d1caca;
}
.hospital-header h3 {
    margin-top:15px;
    font-weight:700;
    color:#263238;
}
.hospital-header p {
    color:#78909c;
    font-size:15px;
}
.hospital-logo {
    text-align:center;
    margin:20px 0;
}
.hospital-logo img {
    max-width:120px;
    max-height:100px;
}
.form-group label {
    font-weight:600;
    color:#455a64;
}
.form-group label i {
    color:#0d6efd;
    width:22px;
}
.form-control {
    height:45px;
    border-radius:10px;
    border:1px solid #ced4da;
}
.form-control:focus {
    border-color:#0d6efd;
    box-shadow:
    0 0 0 .2rem rgba(13,110,253,.15);
}
.btn-login {
    height:48px;
    border-radius:10px;
    font-weight:600;
    font-size:16px;
}
.btn-outline-secondary {
    border-radius:10px;
}
.hospital-footer {
    margin-top:25px;
    text-align:center;
    color:#90a4ae;
}
.hospital-footer i {
    color:#28a745;
}
</style>
<div class="container">
   <div class="row mt-5">
        <div class="col-md-10 m-auto">
            <?= $login; ?>
        </div>
    </li>
</div>
<div id="artify-ajax-loader">
    <img width="300" src='<?php echo htmlspecialchars($_ENV["BASE_URL"], ENT_QUOTES, 'UTF-8'); ?>app/libs/artify/images/ajax-loader.gif' class="artify-img-ajax-loader"/>
</div>
</body>
</html>