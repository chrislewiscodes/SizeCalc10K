<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8" />

	<!-- DESIGN BY NICK SHERMAN http://nicksherman.com/      CODE BY CHRIS LEWIS http://chrissam42.com/ -->

	<title>Size Calculator</title>
	<meta name='description' content='A tool to calculate and convert between physical size, perceived size, and distance of an object.'>

	<meta name="viewport" content="initial-scale=1.0">
	<meta name='og:image' content="http://sizecalc.com/thumbnail.png"><!-- for Facebook -->
	<link rel='canonical' href="http://sizecalc.com/">
	<link rel='shortcut icon' href='favicon.ico'>

	<link href="//cloud.webtype.com/css/b80b5172-bf3d-4dfd-b20d-1c0fbfabfaa3.css" rel="stylesheet" type="text/css" />
	<link rel="stylesheet" href="style.css" />

	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
	<script src="range.js"><!-- range input support for older Firefox --></script>
	<script src="hidpi-canvas.js"><!-- automatic Retina-zation of canvas drawing --></script>
	<script src="js.js"></script>
</head>
<body>

<div id='formwrapper'>
<form>
	<h1><a href='./'>Size Calculator</a></h1>
	<!--<h2>Solve for …</h2>-->
	<p>Enter any two values to calculate the third.</p>
	
	<div class="solve-for distance" data-what="distance">
		<h3>
			<!--<label><input tabindex='1' type="radio" name="solve-for" value="distance">Viewing distance</label>-->
			Viewing Distance
		</h3>
		<input type='checkbox' id='distance-lock' value='lock' class='lock'><label class='lock' for='distance-lock' title='Lock this value'></label>
		<input tabindex='2' id='distance-value' type="number" name="distance-value">
		<select tabindex='2' class='length' id='distance-units' name="distance-units">
			<optgroup label="English">
				<option value="inches" selected>inches</option>
				<option value="feet">feet</option>
				<option value="yards">yards</option>
				<option value="miles">miles</option>
			</optgroup>
			<optgroup label="Metric">
				<option value="millimeters">millimeters</option>
				<option value="centimeters">centimeters</option>
				<option value="meters">meters</option>
				<option value="kilometers">kilometers</option>
			</optgroup>
			<optgroup label="Astronomical">
				<option value="lightyears">light years</option>
			</optgroup>
		</select>
		<input tabindex='3' type="range" name="distance-value-range">
	</div>
	
	<div class="solve-for physical-size" data-what="physical-size">
		<h3>
			<!--<label><input tabindex='1' type="radio" name="solve-for" value="physical-size">Physical size</label>-->
			Physical Size
		</h3>
		<input type='checkbox' id='physical-size-lock' value='lock' class='lock'><label class='lock' for='physical-size-lock' title='Lock this value'></label>
		<input tabindex='2' id='physical-size-value' type="number" name="physical-size-value">
		<select tabindex='2' class='length' id='physical-size-units' name="physical-size-units">
			<optgroup label="English">
				<option value="points" selected>points</option>
				<option value="picas">picas</option>
				<option value="inches">inches</option>
				<option value="feet">feet</option>
				<option value="yards">yards</option>
				<option value="miles">miles</option>
			</optgroup>
			<optgroup label="Metric">
				<option value="millimeters">millimeters</option>
				<option value="centimeters">centimeters</option>
				<option value="meters">meters</option>
				<option value="kilometers">kilometers</option>
			</optgroup>
			<optgroup label="Astronomical">
				<option value="light-years">light years</option>
			</optgroup>
		</select>
		<input tabindex='3' type="range" name="physical-size-value-range">
	</div>
	
	<div class="solve-for perceived-size" data-what="perceived-size">
		<h3>
			<!--<label><input tabindex='1' type="radio" name="solve-for" value="perceived-size">Perceived size</label>-->
			Perceived Size
		</h3>
		<input type='checkbox' id='perceived-size-lock' value='lock' class='lock'><label class='lock' for='perceived-size-lock' title='Lock this value'></label>
		<input tabindex='2' id='perceived-size-value' type="number" name="perceived-size-value">
		<select tabindex='2' class='angle' id='perceived-size-units' name="perceived-size-units">
			<option value="arcseconds">arcseconds</option>
			<option value="arcminutes">arcminutes</option>
			<option value="rpx">reference pixels</option>
			<option value="degrees" selected>degrees</option>
			<option value="radians">radians</option>
		</select>
		<input tabindex='3' type="range" name="perceived-size-value-range" min='1' max='179'>
	</div>
	
	<div class='clearer'></div>
</form>
</div>

<div id='illustration_container_container'>
<div id='illustration_container'>
	<img id='man' src='man.svg' alt='Man' class='default'>
	<canvas id='illustration'>
		For a fancy visualization, view this site in a browser that supports the &lt;canvas&gt; tag: IE 10, Firefox 16, Safari and Chrome are good choices.
	</canvas>
</div>
</div>

<div id='credits'>
Size Calculator is a project by <a href='http://nicksherman.com/'>Nick Sherman</a> and <a href='http://chrislewis.codes/'>Chris Lewis</a>. Follow <a href='http://twitter.com/SizeCalculator'>@SizeCalculator</a> on Twitter.
</div>

<div style='visibility:hidden;position:absolute;width:1px;height:1px;top:0;left:-10px;font-family:Scout'>Be a love and load Scout for me</div>

<div id='chrissam42' style='position:absolute;top:0;right:0;'></div>

</body>
</html>
