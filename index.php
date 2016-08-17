<?php
	namespace SizeCalc;
	
	$button = "⬅︎";
	
	function feature($feature) {
		$nofeatures = array('nofeatures', 'nothing', 'lynx');
		if (count(array_intersect($nofeatures, array_keys($_GET)))) {
			return false;
		}
		if (isset($_GET["no$feature"])) {
			return false;
		}
		return true;
	}

	class SizeCalculator {
		private $sliderFactor = 5;
		private $precision = 3;

		private $d, $du;
		private $s, $su;
		private $a, $au;

		private $ANGLE, $LENGTH;
		
		function __construct($distance, $dunits, $size, $sunits, $angle, $aunits) {
			// MULTIPLY to convert FROM units
			// DIVIDE to convert TO units
			
			$this->ANGLE = array(
				'arcseconds' => 1/(60*60)*M_PI/180, 
				'arcminutes' => 1/60*M_PI/180,
				'rpx' => M_PI/180*0.021315384,
				'degrees' => M_PI/180,
				'radians' => 1
			);
			
			$this->LENGTH = array(
				'px' => 1/96,
				'points' => 1/72,
				'picas' => 1/6,
				'inches' => 1,
				'feet' => 12,
				'yards' => 36,
				'miles' => 12*5280,
				'millimeters' => 1/25.4,
				'centimeters' => 1/2.54,
				'meters' => 39.3701,
				'kilometers' => 39.3701*1000,
				'lightyears' => 3.72461748e17
			);
			
			//always set the values to something sensible even if nonsense was given
			$this->d = is_numeric($distance) ? (float)$distance : 6;
			$this->du = isset($this->LENGTH[$dunits]) ? $dunits : 'feet';
			$this->s = is_numeric($size) ? (float)$size : 12;
			$this->su = isset($this->LENGTH[$sunits]) ? $sunits : 'inches';
			$this->a = is_numeric($angle) ? (float)$angle : 9.527;
			$this->au = isset($this->ANGLE[$aunits]) ? $aunits : 'degrees';
		}
	
		public function sliderRange($field) {
			if ($field === 'angle') {
				return array(0, M_PI/$this->ANGLE[$this->au]);
				//'step' => units == 'radians' ? .01 : Math.floor(M_PI/$this->ANGLE[units]/slider.width())
			} else {
				return array($this->{$field[0]} / $this->sliderFactor, $this->{$field[0]} * $this->sliderFactor);
			}
		}
		
		public function sliderMin($field) {
			$range = $this->sliderRange($field);
			return $range[0];
		}
		
		public function sliderMax($field) {
			$range = $this->sliderRange($field);
			return $range[1];
		}
	
		public function solveFor($field) {
			//grab the conversion factors for the selected units
			$distanceratio = $this->LENGTH[$this->du];
			$perceivedratio = $this->ANGLE[$this->au];
			$physicalratio = $this->LENGTH[$this->su];
		
			//normalize the entered numbers to standard units		
			$distance = $this->d*$distanceratio;
			$perceived = $this->a*$perceivedratio;
			$physical = $this->s*$physicalratio;
	
			switch ($field) {
				case 'distance': 
					return $this->d = round($physical/2 / tan($perceived/2/$this->ANGLE['radians']) / $distanceratio, $this->precision);
				
				case 'size':
					return $this->s = round(2 * $distance * tan($perceived/2/$this->ANGLE['radians']) / $physicalratio, $this->precision);
				
				case 'angle':
					return $this->a = round(2 * atan2($physical/2,$distance)*$this->ANGLE['radians'] / $perceivedratio, $this->precision);
			}
		}
		
		public function absoluteValue($field) {
			$letter = $field[0];
			$unit = $letter . 'u';
			if (isset($this->ANGLE[$this->$unit])) {
				return $this->$letter * $this->ANGLE[$this->$unit];
			} else {
				return $this->$letter * $this->LENGTH[$this->$unit];
			}
		}
		
		public function fieldValue($field) {
			return $this->{$field[0]};
		}
		
		public function fieldUnits($field) {
			$u = $field[0] . 'u';
			return $this->$u;
		}

		public function printOptions($options, $selected="", $indent=0) {
			$tabs = str_repeat("\t", $indent);
			$first = true;
			foreach ($options as $value => $label) {
				if ($first) $first = false;
				else print "\n$tabs";
				print "<option value='$value'";
				if ($selected === $value) print " selected";
				print ">$label</option>";
			}
		}
		
		public function printOptionGroups($groups, $selected="", $indent=0) {
			$tabs = str_repeat("\t", $indent);
			$first = true;
			foreach ($groups as $group => $options) {
				if ($first) $first = false;
				else print "\n$tabs";
				print "<optgroup label='$group'>";
				$this->printOptions($options, $selected, $indent+1);
				print "\n$tabs</optgroup>";
			}
		}
		
		public function printLengthOptions($field, $indent=0) {
			$selected = $this->fieldUnits($field);
			$groups = array(
				'English' => array(
					"inches" => "inches",
					"feet" => "feet",
					"yards" => "yards",
					"miles" => "miles",
				), 'Metric' => array(
					"millimeters" => "millimeters",
					"centimeters" => "centimeters",
					"meters" => "meters",
					"kilometers" => "kilometers",
				), 'Astronomical' => array(
					"lightyears" => "light years",
				)
			);
			
			return $this->printOptionGroups($groups, $selected, $indent);
		}

		public function printAngleOptions($field, $indent=0) {
			$selected = $this->fieldUnits($field);
			$options = array(
				"arcseconds" => "arcseconds",
				"arcminutes" => "arcminutes",
				"rpx" => "reference pixels",
				"degrees" => "degrees",
				"radians" => "radians",
			);

			return $this->printOptions($options, $selected, $indent);
		}
		
		public function printSVG($embedded = false) {
			$standalone = !$embedded;

			if ($standalone) {
				ini_set('display_errors', false);
			}

			$distance = $this->absoluteValue('distance');
			$size = $this->absoluteValue('size');
			$angle = $this->absoluteValue('angle');

			$halfsize = $size/2;

			$distance_color = '#FF0000';
			$size_color = '#00ABFF';
			$angle_color = '#39A848';
			
			$angle_degrees = $angle * 180/M_PI;
			$angle_half = $angle_degrees/2;
			$arc_where = 0.5;
			$arc_radius = $distance * $arc_where;
			$arc_start = array($arc_radius * cos($angle/2), $arc_radius * sin($angle/2));
			$arc_height = 2 * $arc_radius * sin($angle/2);
			
			
			$human_height = 66; //5 foot 6, arbitrary
			$human_width = $human_height * 69/107; //from svg viewbox
			
			$eye_x = $human_width * 0.44;
			$eye_y = $human_width * 0.42;
			
			$human_x = 0;
			$human_y = max(0, $halfsize - $eye_y);
			
			$eye_y += $human_y;
			
			$corner1 = array($eye_x+$distance, $eye_y-$halfsize);
			$corner2 = array($eye_x+$distance, $eye_y+$halfsize);
			
			$fontsize = $size * 1;
			$fontpos = array($corner1[0] + $halfsize - 0.8*$fontsize/2, $corner1[1] + $halfsize + $fontsize*0.36);
			
			$image_width = $eye_x + $distance + $size;
			$image_height = max($human_y + $human_height, $size);
			
			//convert to 1000 pixels wide
			$px_width = 1000;
			$topx = $px_width / $image_width;
			$px_height = $image_height * $topx;
			
			$onepx = 1/$topx;
			$dashlength = 5*$onepx;
			
			$arrowsize=4*$onepx;
			$arrowheight = $arrowsize*sqrt(3);
			$arrowhalf = $arrowheight/2;

			if ($standalone) {
				header("Content-type: image/svg+xml");
				print <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
EOF;
			}

/* //original object
	<rect fill="#EEEEEE" stroke="none" stroke-width="{$onepx}" x="{$corner1[0]}" y="{$corner1[1]}" width="$size" height="$size" />
	<text x="{$fontpos[0]}" y="{$fontpos[1]}" style="font-family:Verdana,sans-serif;font-weight:bold;font-size:{$fontsize};stroke:none;fill:black">A</text>
*/

			$target_stretch = 2;
			$target = array($eye_x+$distance, $eye_y);
			$targets = array();
			$colors = array('yellow', 'red', 'blue', 'black', 'white');
			for ($i=4; $i>=0; $i--) {
				$major = $halfsize * ($i+1) / 5;
				$minor = $major / $target_stretch;
				$targets[] = "<ellipse stroke='black' fill='{$colors[$i]}' stroke-width='$onepx' cx='{$target[0]}' cy='{$target[1]}' rx='$minor' ry='$major'/>";
			}
			$image_width -= $size - $halfsize/$target_stretch;
			$px_height = $image_height * $px_width / $image_width;

			print <<<EOF
<svg id="visualization" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="{$px_width}px" height="{$px_height}px" viewBox="0 0 {$image_width} {$image_height}">
	<!-- the object -->
	{$targets[0]}
	{$targets[1]}
	{$targets[2]}
	{$targets[3]}
	{$targets[4]}

	<!-- the observer -->
	<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="{$human_x}" y="{$human_y}" width="{$human_width}" height="{$human_height}" viewBox="28 7 69 107">
		<path style="fill:none;" d="M56.008,71.582l0.023-0.172c0-0.047-0.023-0.07-0.023-0.125V71.582z"/>
		<path style="fill:none;" d="M56.008,71.582l0.023-0.172c0-0.047-0.023-0.07-0.023-0.125V71.582z"/>
		<path d="M52.602,30.441c4.82,0,8.688-3.898,8.688-8.695s-3.867-8.688-8.688-8.688c-4.789,0-8.695,3.891-8.695,8.688
			S47.812,30.441,52.602,30.441z"/>
		<path d="M54.625,32.645H32.938c-5.242,0-5.242,6.805,0,6.805h21.688C59.836,39.457,59.836,32.645,54.625,32.645z"/>
		<path d="M95.914,36.02c0-9.633-3.438-18.836-9.742-26.086c0.031-0.031-1.586-1.75-1.586-1.75L60.117,32.645l-0.414,0.406
			c0.07,0.109,0.188,0.18,0.234,0.305c0.375,0.672,0.586,1.414,0.672,2.164c0.023,0.305,0,0.617-0.039,0.938
			c-0.047,0.648-0.172,1.281-0.453,1.875l-0.023,0.062c-0.062,0.109-0.086,0.25-0.156,0.367c-0.047,0.086-0.102,0.133-0.164,0.211
			c-1.07,1.648-2.914,2.609-5.148,2.609H42.688v31.875c0,0.023,0.023,0.047,0.023,0.07l-6.734,33.836
			c-0.516,2.633,1.203,5.195,3.844,5.711c2.633,0.539,5.172-1.172,5.711-3.812L52.5,74.176v-0.039h3.172v0.039l6.984,35.086
			c0.539,2.641,3.078,4.352,5.742,3.812c2.617-0.516,4.32-3.078,3.812-5.711l-6.797-34.141V44.637l19.172,19.172
			c0,0,1.617-1.727,1.617-1.742C92.477,54.816,95.914,45.613,95.914,36.02z M55.672,74.09v-0.305c0,0.047,0.031,0.07,0.031,0.133
			L55.672,74.09z M91.5,39.449c-0.734,7.211-3.594,14.039-8.305,19.602c-4.367-4.336-12.398-12.391-17.758-17.75v-1.852H91.5z
			 M91.5,32.645H63.453c5.148-5.125,14.789-14.766,19.719-19.688C87.906,18.527,90.789,25.387,91.5,32.645z"/>
	</svg>

	<!-- arrowhead -->
	<defs>
	<path stroke="none" id='arrowhead' d='M0,0L{$arrowhalf},{$arrowheight}L-{$arrowhalf},{$arrowheight}Z'/>
	</defs>

	<!-- the size -->
	<path fill="none" stroke="{$size_color}" stroke-width="{$onepx}" d="M{$corner1[0]},{$corner1[1]} l0,{$size}"/>
	<use xlink:href='#arrowhead' fill="{$size_color}" transform="translate({$corner1[0]},{$corner1[1]})"/>
	<use xlink:href='#arrowhead' fill="{$size_color}" transform="translate({$corner2[0]},{$corner2[1]}) rotate(180)"/>

	<!-- the angle -->
	<path fill="none" stroke="{$angle_color}" stroke-width="{$onepx}" stroke-dasharray="$dashlength" d="M{$corner1[0]},{$corner1[1]}L{$eye_x},{$eye_y}L{$corner2[0]},{$corner2[1]}"/>
	<path fill="none" stroke="{$angle_color}" stroke-width="{$onepx}" d="M{$eye_x},{$eye_y}m{$arc_start[0]},-{$arc_start[1]}a{$arc_radius},{$arc_radius},0,0,1,0,{$arc_height}"/>
	<use xlink:href='#arrowhead' fill="{$angle_color}" transform="translate({$arc_start[0]},-{$arc_start[1]}) translate({$eye_x},{$eye_y}) rotate(-{$angle_half})"/>
	<use xlink:href='#arrowhead' fill="{$angle_color}" transform="translate({$arc_start[0]},{$arc_start[1]}) translate({$eye_x},{$eye_y}) rotate({$angle_half}) rotate(180)"/>

	<!-- the distance -->
	<path fill="none" stroke="{$distance_color}" stroke-width="{$onepx}" d="M{$eye_x},{$eye_y} l{$distance},0"/>
	<use xlink:href='#arrowhead' fill="{$distance_color}" transform="translate({$eye_x},{$eye_y}) rotate(-90)"/>
	<use xlink:href='#arrowhead' fill="{$distance_color}" transform="translate({$distance},0) translate({$eye_x},{$eye_y}) rotate(90)"/>
</svg>
EOF;
			if ($standalone) exit;
		}
	}

	function g($k) {
		return isset($_GET[$k]) ? $_GET[$k] : "";
	}

	$sizecalc = new SizeCalculator(g('distance'), g('distance-units'), g('size'), g('size-units'), g('angle'), g('angle-units'));
	
	if (!empty($_GET['solve-for'])) {
		$sizecalc->solveFor($_GET['solve-for']);
	}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8" />

	<!-- DESIGN BY NICK SHERMAN http://nicksherman.com/		CODE BY CHRIS LEWIS https://chrislewis.codes/ -->

	<title>Size Calculator</title>
	<meta name='description' content='A tool to calculate and convert between physical size, perceived size, and distance of an object.'>

	<meta name="viewport" content="initial-scale=1.0">
	<meta name='og:image' content="https://10k.sizecalc.com/thumbnail.png"><!-- for Facebook -->
	<link rel='canonical' href="https://10k.sizecalc.com/">

	<?php if (feature("css")): print "\n"; ?>
	<style>
		<?php
			ob_start(function($out) {
				$out = str_replace("\r\n", "\n", $out);
				return trim(preg_replace('/\n(?!\n)/', "\\0\t\t", $out)) . "\n";
			});
			
			if (feature("fonts")) { include("fonts.css"); }
			include("style.css");
			ob_end_flush();
		?>
	</style>
	<?php endif; ?>

	<?php /* if (feature("js") || feature("javascript")): ?>
	<script src="hidpi-canvas.js"><!-- automatic Retina-zation of canvas drawing --></script>
	<script src="js.js"></script>
	<?php endif; */ ?>

	<script>
		(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');
		ga('create', 'UA-45370560-2', 'auto');
		ga('send', 'pageview');
	</script>
</head>
<body>

<div id='formwrapper'>
<form method="get">
	<h1><a href='./'>Size Calculator</a></h1>
	<!--<h2>Solve for …</h2>-->
	<p>Enter any two values and click the <button type="button" aria-disabled><?php print $button; ?></button> to calculate the third.</p>
	
	<div class="solve-for distance" data-what="distance">
		<h3>
			<!--<label><input type="radio" name="solve-for" value="distance">Viewing distance</label>-->
			<label for='distance-value'>Viewing Distance</label>
		</h3>
		<!--
		<input type='checkbox' id='distance-lock' value='lock' class='lock'><label class='lock' for='distance-lock' title='Lock this value'></label>
		-->
		<input id='distance-value' type="number" name="distance" min="0" step="any" value="<?php echo $sizecalc->fieldValue('distance'); ?>">
		<select class='length' id='distance-units' name="distance-units" aria-label="Units for distance">
			<?php $sizecalc->printLengthOptions('distance', 3); ?>
		</select>
		<button name="solve-for" type="submit" value="distance" aria-label="Calculate distance"><?php print $button; ?></button>
		<!--
		<input type="range" name="distance-range">
		-->
	</div>
	
	<div class="solve-for size" data-what="size">
		<h3 id='size-label'>
			<!--<label><input type="radio" name="solve-for" value="size">Physical size</label>-->
			<label for='size-value'>Physical Size</label>
		</h3>
		<!--
		<input type='checkbox' id='size-lock' value='lock' class='lock'><label class='lock' for='size-lock' title='Lock this value'></label>
		-->
		<input id='size-value' type="number" name="size" min="0" step="any" value="<?php echo $sizecalc->fieldValue('size'); ?>">
		<select class='length' id='size-units' name="size-units" aria-label="Units for physical size">
			<?php $sizecalc->printLengthOptions('size', 3); ?>
		</select>
		<button name="solve-for" type="submit" value="size" aria-label="Calculate physical size"><?php print $button; ?></button>
		<!--
		<input type="range" name="size-range">
		-->
	</div>
	
	<div class="solve-for angle" data-what="angle">
		<h3 id='angle-label'>
			<!--<label><input type="radio" name="solve-for" value="angle">Perceived size</label>-->
			<label for='angle-value'>Perceived Size</label>
		</h3>
		<!--
		<input type='checkbox' id='angle-lock' value='lock' class='lock'><label class='lock' for='angle-lock' title='Lock this value'></label>
		-->
		<input id='angle-value' type="number" name="angle" min="0" step="any" value="<?php echo $sizecalc->fieldValue('angle'); ?>">
		<select class='angle' id='angle-units' name="angle-units" aria-label="Units for perceived-size angle">
			<?php $sizecalc->printAngleOptions('angle', 3); ?>
		</select>
		<button name="solve-for" type="submit" value="angle" aria-label="Calculate perceived size"><?php print $button; ?></button>
		<!--
		<input type="range" name="angle-range" min='1' max='179'>
		-->
	</div>
	
	<div class='clearer'></div>
</form>
</div>

<figure id='visualization-container'>
<?php $sizecalc->printSVG(true); ?>
</figure>

<div id='credits'>
Size&nbsp;Calculator is a project by <a href='http://nicksherman.com/'>Nick&nbsp;Sherman</a> and <a href='http://chrislewis.codes/'>Chris&nbsp;Lewis</a>. Follow&nbsp;<a href='https://twitter.com/SizeCalculator'>@SizeCalculator</a> on&nbsp;Twitter.
</div>

</body>
</html>
