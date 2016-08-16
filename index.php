<?php
	namespace SizeCalc;
	
	$button = "☚";
	
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
			$this->d = is_numeric($distance) ? (float)$distance : 3;
			$this->du = isset($this->LENGTH[$dunits]) ? $dunits : 'feet';
			$this->s = is_numeric($size) ? (float)$size : 12;
			$this->su = isset($this->LENGTH[$sunits]) ? $sunits : 'inches';
			$this->a = is_numeric($angle) ? (float)$angle : 18.924644;
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
					return $this->d = round($physical/2 / tan($perceived/2/$this->ANGLE['radians']) / $distanceratio, 6);
				
				case 'size':
					return $this->s = round(2 * $distance * tan($perceived/2/$this->ANGLE['radians']) / $physicalratio, 6);
				
				case 'angle':
					return $this->a = round(2 * atan2($physical/2,$distance)*$this->ANGLE['radians'] / $perceivedratio, 6);
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
	<meta name='og:image' content="http://sizecalc.com/thumbnail.png"><!-- for Facebook -->
	<link rel='canonical' href="https://10k.sizecalc.com/">
	<link rel='shortcut icon' href='favicon.ico'>

	<?php if (feature("css")): ?>
		<?php if (feature("fonts")): ?>
	<link href="//cloud.webtype.com/css/b80b5172-bf3d-4dfd-b20d-1c0fbfabfaa3.css" rel="stylesheet" type="text/css" />
		<?php endif; ?>
	<link rel="stylesheet" href="style.css" />
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
<form>
	<h1><a href='./'>Size Calculator</a></h1>
	<!--<h2>Solve for …</h2>-->
	<p>Enter any two values and click the <?php print $button; ?> to calculate the third.</p>
	
	<div class="solve-for distance" data-what="distance">
		<h3>
			<!--<label><input tabindex='1' type="radio" name="solve-for" value="distance">Viewing distance</label>-->
			Viewing Distance
		</h3>
		<!--
		<input type='checkbox' id='distance-lock' value='lock' class='lock'><label class='lock' for='distance-lock' title='Lock this value'></label>
		-->
		<input tabindex='2' id='distance-value' type="number" name="distance" value="<?php echo $sizecalc->fieldValue('distance'); ?>">
		<select tabindex='2' class='length' id='distance-units' name="distance-units">
			<?php $sizecalc->printLengthOptions('distance', 3); ?>
		</select>
		<button name="solve-for" type="submit" value="distance"><?php print $button; ?></button>
		<!--
		<input tabindex='3' type="range" name="distance-range">
		-->
	</div>
	
	<div class="solve-for size" data-what="size">
		<h3>
			<!--<label><input tabindex='1' type="radio" name="solve-for" value="size">Physical size</label>-->
			Physical Size
		</h3>
		<!--
		<input type='checkbox' id='size-lock' value='lock' class='lock'><label class='lock' for='size-lock' title='Lock this value'></label>
		-->
		<input tabindex='2' id='size-value' type="number" name="size" value="<?php echo $sizecalc->fieldValue('size'); ?>">
		<select tabindex='2' class='length' id='size-units' name="size-units">
			<?php $sizecalc->printLengthOptions('size', 3); ?>
		</select>
		<button name="solve-for" type="submit" value="size"><?php print $button; ?></button>
		<!--
		<input tabindex='3' type="range" name="size-range">
		-->
	</div>
	
	<div class="solve-for angle" data-what="angle">
		<h3>
			<!--<label><input tabindex='1' type="radio" name="solve-for" value="angle">Perceived size</label>-->
			Perceived Size
		</h3>
		<!--
		<input type='checkbox' id='angle-lock' value='lock' class='lock'><label class='lock' for='angle-lock' title='Lock this value'></label>
		-->
		<input tabindex='2' id='angle-value' type="number" name="angle" value="<?php echo $sizecalc->fieldValue('angle'); ?>">
		<select tabindex='2' class='angle' id='angle-units' name="angle-units">
			<?php $sizecalc->printAngleOptions('angle', 3); ?>
		</select>
		<button name="solve-for" type="submit" value="angle"><?php print $button; ?></button>
		<!--
		<input tabindex='3' type="range" name="angle-range" min='1' max='179'>
		-->
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
