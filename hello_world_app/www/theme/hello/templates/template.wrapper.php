<?php //NEVER ALLOW BLANK LINES OF OUTPUT above the $body variable - it breaks the XML feeds ?>
<?php if($showHeader) { ?>
	<!doctype html>
	<html>
	<head>
		<base href="<?php echo getThemeURL(); ?>" />

		<title><?php print( $title ); ?> - Hello World App</title>

		<meta http-equiv="X-UA-Compatible" content="IE=edge" />

		<!-- CSS -->
		<link href="/theme/shared/css/reset.css" rel="stylesheet" type="text/css" />
		<link href="/theme/shared/css/shared.css" rel="stylesheet" type="text/css" />
		<link href="/theme/shared/jquery_ui/custom-theme/jquery-ui-1.8.22.custom.css" rel="stylesheet" type="text/css" />
		<link href="css/hello.css" rel="stylesheet" type="text/css" />
		<?php foreach($css as $stylesheet) { ?>
			<link href="<?php echo $stylesheet; ?>" rel="stylesheet" type="text/css" />
		<?php } ?>
		<link rel="icon" href="images/favicon.ico" />
		<script type="text/javascript" src="/script/libs/jquery.1.8.0.min.js"></script>
		<script type="text/javascript" src="/script/libs/jquery-ui-1.8.23.custom.min.js"></script>
		<script type="text/javascript">
		  if(typeof(console)=='undefined') {
			window.console = { log:function(msg) { } };
		  }

		  var _gaq = _gaq || [];
		  _gaq.push(['_setAccount', 'UA-34398188-1']);
		  _gaq.push(['_setDomainName', 'garrettcounty.org']);
		  _gaq.push(['_trackPageview', window.location.pathname]);

		  (function() {
			var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
			ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		  })();

		</script>
	</head>

	<body data-token="<?php echo md5(date('Y-m-d-H').tools::getTokenKey(date('G'))); ?>">

	<!--[if lte IE 7]>
	<div id="oldbrowser">
		You're using an outdated web browser. Our site may not work properly. Please <a href="https://www.google.com/intl/en/chrome/browser/">download a new version</a>.
	</div>
	<![endif]-->

	<div id="bk-container">

	<div id="body-container">
<?php
} //endif $showHeader
//DO NOT ALLOW EMPTY LINES ABOVE $body!!! IF THERE ARE, IT BREAKS XML FEEDS
echo $body;
//DO NOT ALLOW EMPTY LINES BELOW $body!!! IF THERE ARE, IT BREAKS XML FEEDS
if($showFooter) { ?>
	</div><!--body-container-->

	</div><!--bk-container-->
	<script>
		window.$_SESSION = <?php echo json_encode($_SESSION); ?>;
		window.$_GET = <?php echo json_encode($_GET); ?>;
		window.full_server_url = function() { return "<?php echo getBaseURL(); ?>"; };
	</script>
	<!-- scripts -->
	<script src="/script/plugins/jquery.cookie.js"></script>
	<script src="/script/plugins/jquery.dotimeout.js"></script>
	<script src="/script/plugins/jquery.ba-hashchange.min.js"></script>
	<script src="/script/plugins/jquery.validate.min.js"></script>
	<script src="/script/plugins/jquery.jquote2.min.js"></script>
	<script src="/script/plugins/jquery.ajaxmanager.js"></script>
	<script src="/script/plugins/php.base64.js"></script>
	<script src="/script/libs/shortcut.js"></script>
	<?php foreach($scripts as $script) { ?>
	<script src="<?php echo $script; ?>"></script>
	<?php } ?>
	</body>
	</html>
<?php } //endif $showFooter ?>