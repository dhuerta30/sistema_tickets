<!DOCTYPE html>
<html lang="es">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?=$_ENV["APP_NAME"]?></title>

	<!-- Google Font: Source Sans Pro -->
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
	<!-- Font Awesome -->
	<link rel="stylesheet" href="<?=$_ENV["BASE_URL"]?>theme/plugins/fontawesome-free/css/all.min.css">
	<!-- Theme style -->
	<link rel="stylesheet" href="<?=$_ENV["BASE_URL"]?>theme/dist/css/adminlte.min.css">
	<link rel="stylesheet" href="<?=$_ENV["BASE_URL"]?>css/style.css">
	<link rel="stylesheet" href="<?=$_ENV["BASE_URL"]?>css/shCore.css">
	<link rel="stylesheet" href="<?=$_ENV["BASE_URL"]?>css/shThemeDjango.css">
</head>

<body class="hold-transition sidebar-mini">
	<style>
		body {
			padding-right: 0!important;
		}
		[class*=sidebar-dark-] .nav-treeview>.nav-item>.nav-link {
			color: #c2c7d0;
			font-size: 12px!important;
		}

		.list-none {
			list-style: none;
		}

		.brand-link {
			display: block;
			font-size: 1.25rem;
			line-height: 1.5;
			padding: .1rem .5rem;
			transition: width .3s ease-in-out;
			white-space: nowrap;
		}

		#loader {  
			position:fixed;
			top:0;
			left:0;
			width:100%;
			height:100%;
			z-index:9999999999999999999999999999999;
			background-color: #fff;
			display: none;
		}
		.ajax-loader {
			position: absolute;
			left: 50%;
			top: 50%;
			margin-left: -32px; /* -1 * image width / 2 */
			margin-top: -32px;  /* -1 * image height / 2 */
			display: block;     
		}

		.nav-sidebar .nav-link p {
			display: inline;
			margin: 0;
			white-space: nowrap!important;
		}
	</style>
	<!-- Site wrapper -->
	<div class="wrapper">
		<!-- Navbar -->
		<nav class="main-header navbar navbar-expand navbar-white navbar-light">
			<!-- Left navbar links -->
			<ul class="navbar-nav">
				<li class="nav-item">
					<a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
				</li>
			</ul>
		</nav>

		<div id="loader">
			<img  width="100" src="<?=$_ENV["BASE_URL"]?>app/libs/artify/images/ajax-loader.gif" class="ajax-loader"/>
		</div>