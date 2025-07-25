<html lang="en-US">
<head>
	<meta charset="utf-8"/>
	<style>
		:root {
			--primary-bg-color: #c1ce79;
			--primary-text-color: rgb(110, 77, 50);
			--secondary-bg-color: #535046;
			--secondary-text-color: white;
			--tertiary-bg-color: #000000;
			--tertiary-text-color: #6c8175;
			--button-bg-color: #1c2e69;
			--button-text-color: #fff;
			--message-red-bg-color: rgb(255, 213, 196);
			--message-red-text-color: rgb(165, 6, 6);
		}
		body {
			font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
			background-color: var(--primary-bg-color);
			color: var(--primary-text-clolor);
			margin: 0px;
			text-align: center;
		}
		.header {
			display:block;
			background-color: var(--secondary-bg-color);
			color: var(--secondary-text-color);
			padding: 20px;
			margin-bottom:20px;
		}
		.trailer {
			margin-top: 10px;
			background-color: var(--message-red-bg-color);
			color: var(--message-red-text-color);
			padding: 10px;
			text-align: center;
		}
		.main-content {
			margin: auto;
			padding-left: 10px;
			padding-top: 50px;
			display: inline-block;
			padding: 30px;
			margin-left: 20px;
			width: 90%;
		}
		#footer {
			position: fixed;
			bottom: 0px;
			text-align: center;
			padding: 4px;
			display: block;
			width:100%;
			background-color: var(--tertiary-bg-color);
			color: var(--tertiary-text-color);
		}
		.loginform button, .logoff {
			background-color: var(--button-bg-color);
			color: var(--button-text-color);
			border-radius: 5px;
			text-decoration: none;
			font-size: 1.2em;
			margin-top: 20px;
			display: block;
			padding: 5px;
		}

	</style>
	<meta name="viewport" content="width=device-width,initial-scale=1"/>
</head>
	<div>
	<div class=header>
{{header}}
	</div>
	<div class="main-content">
		{{content}}

	</div>
	<div id=footer>
		Copyright Simplicity 2024
	</div>
</body>
</html>
