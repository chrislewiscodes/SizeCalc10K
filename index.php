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
			
			$this->d = is_numeric($distance) && $distance > 0 ? (float)$distance : false;
			$this->du = isset($this->LENGTH[$dunits]) ? $dunits : 'feet';
			$this->s = is_numeric($size) && $size > 0 ? (float)$size : false;
			$this->su = isset($this->LENGTH[$sunits]) ? $sunits : 'inches';
			$this->a = is_numeric($angle) && $angle > 0 ? (float)$angle : false;
			$this->au = isset($this->ANGLE[$aunits]) ? $aunits : 'degrees';
			
			//set defaults if everything is blank
			if ($this->d === false and $this->a === false and $this->s === false) {
				$this->d = 3;
				$this->s = 12;
				$this->a = 18.925;
			} else {
				//if one field is blank, solve for it regardless of what button was clicked
				if ($this->d === false and $this->s > 0 and $this->a > 0) {
					$_GET['solve-for'] = 'distance';
				} else if ($this->s === false and $this->d > 0 and $this->a > 0) {
					$_GET['solve-for'] = 'size';
				} # leave angle blank
			}
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

			$halfsize = $size / 2;

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
			$human_width = $human_height * 145.6/720; //from svg viewbox
			
			$eye_x = $human_width * 0.7;
			$eye_y = $human_width * 0.333;
			
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

			print <<<EOF
<svg id="observer" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 {$image_width} {$image_height}">
	<defs>
		<!-- arrowhead -->
		<path stroke="none" id='arrowhead' d='M0,0L{$arrowhalf},{$arrowheight}L-{$arrowhalf},{$arrowheight}Z'/>
	</defs>

	<!-- the object -->
	<rect fill="#EEEEEE" stroke="none" stroke-width="{$onepx}" x="{$corner1[0]}" y="{$corner1[1]}" width="$size" height="$size" />
	<text x="{$fontpos[0]}" y="{$fontpos[1]}" style="font-family:Verdana,sans-serif;font-weight:bold;font-size:{$fontsize}px;stroke:none;fill:black">A</text>

	<!-- the observer -->
	<svg version="1.1" xmlns="http://www.w3.org/2000/svg" x="{$human_x}" y="{$human_y}" width="{$human_width}" height="{$human_height}" 
		 viewBox="1 15.5 145.6 720">
		<path d="M47,15.5c-5.7,0.6-14.1,7.3-16.5,10.9c-0.8,2-1.6,4-2.4,6c-1.2,1.9-4.1,1.3-6.1,2.5c-2.5,1.4-7.7,5.3-8.8,8.1
			c-1.1,2.6,0.4,4.9,0.2,7c-0.1,2.1-1.4,5.6-1.8,8.1C9.8,69.2,16.1,76.5,20,82.6c2,3.7,4,7.5,6.1,11.2c5.2,6.7,13.2,9.2,15.2,19.5
			c-2.8,0.4-5.7,0.9-8.5,1.3c-0.1,3.8-0.1,7.6-0.2,11.4c0.3,1.3,1.9,2.2,1.4,3.8c-0.6,2-6.7,7.2-8.3,9.1c-4.5,5.4-8.8,12.6-12.1,18.8
			c-5.8,10.9-8.4,31.1-10.8,45c-1.2,6.6-0.3,11.7-1.3,17.4c0.1,4.2,0.3,8.3,0.4,12.4c-1.5,12.2-0.9,26.9,0.7,39.2
			c0.2,3.5,0.4,7.1,0.6,10.6c1.9,7.3,3.7,14.6,5.6,21.9c0.3,8.7,0.5,17.5,0.8,26.2c0.4,6.1,0.7,12.3,1.1,18.4
			c-1,5.8-2.7,12.4-3.8,18.8c0.1,17,0.2,33.9,0.3,50.9c2.6,2.9,7.8,3.9,9,5.4c2.7,3.6,5.3,11.1,7.5,15.1c1.6,2.9-3.2,14.3-2,20.3
			c1,5.1,0.9,10.6,2.1,16.8c0.7,3.5,2.3,8.5,1.4,12.9c-0.8,4.2-2.3,9.5-1.6,13.8c1.2,5.3,2.4,10.7,3.6,16c-1.2,1-2.4,2-3.5,3
			c0.3,6.6-1.3,10-2.4,15.1c0,4,0.1,8,0.1,11.9c-1.1,5-1.7,10.4-2.9,15.4c-0.3,10.3-0.6,20.6-0.8,30.9c0,11.2,0,22.5,0,33.8
			c0.3,11.1,0.7,22.1,1,33.2c0.7,3.7,0,7.9-0.4,10.7c-1.1,6.8,2,12.6,1.1,19.2c-0.3,1.9-2.1,2.2-1.4,4.2c1.7,1.2,3.4,2.3,5,3.5
			c0.4,5.6,0.1,14.4,1.3,21.4c0,0,2.5,1.6,7.7,1.3c0.4,3.1,0.3,6.3,1.4,9.7c11.8,2.9,20.7,0.2,28.8,1.2c21,0.9,46.3,2.2,53.7,1.8
			c7.3-0.4,19.2-3.7,21.5-7.3c0.5-5-1.7-8.8-5.2-9.6c-6-1.3-7.1-1.7-8-1.5c0.1-0.2,0.2-0.1,2.8-1c1-7.3-1.9-9.8-6.4-10.5
			c-5-0.8-15-2.7-18.9-3.7c-4-1.1-7.8-3.5-10.8-5.4c-1.5-1-3.2-2.4-4.7-3.4c0.1-8-3-8.3-6.6-12.5c1.8-9.9-5.1-15.4-3.6-23.8
			c0.8-4.3,3.9-7.5,4.8-12.9c-0.1-5.8-0.2-11.6-0.3-17.4c0.3-11.1,0.7-22.2,1-33.3c1.2-9.9,3.7-17.7-1.1-25.4c1.4-1.8,3.7-2.3,5.3-3.8
			c2.7-3.8,3.8-7.8,4-13.5s5.5-18.4,4-29.6c3.6-15.4,6.8-33.1,11-47.9c2.8-10,3.6-19.1,6.4-28.2c0.9-3.2,0.6-7.8,2.8-9.7
			c13.7,0.5,27.1-10.7,24.3-27.3c-0.5-2.9,0.4-5-0.8-7.5c0.2,0,0.3,0,0.5,0c2.9-1.5,7.4-1.3,8.4-4.4c0.8-2.3-2.3-7.3-2.9-10.3
			c-3.1-14.1-2.5-28.2-8.5-38.9c-6.1-11-5.3-26.9-8.4-43.1c-0.3-3.6-0.6-7.3-0.9-10.9c-4-16.4-5.6-34.7-9.7-51.6
			c-2.6-10.7-0.8-23.3-3.9-33.1c-3.2-10.1-7.8-20.4-13.1-28.9c-2.1-3.3-5.7-6.9-6.8-10.9c-1.2-4.2,0.9-8.4,1.5-11.2
			c0.5-2.8-1.3-4.6-1-6.7c0-0.2,0-0.3,0-0.5c0.6-1.8,3.2-2.8,2.4-6.1c-1-4.1-9.8-9.6-12.6-13.1c1.2-2.2,2.5-4.4,3.7-6.6
			c1-3.8,0.7-6.5,2.3-9.2c4.5-1.2,13.3-1.3,16.5-4.1c3.2-2.8,0.7-5.3,1.3-9.1c0.3-2.1,2.3-3,3-4.5c0.8-1.7-0.8-3.2-0.7-4.4
			c0.6-0.9,1.2-1.8,1.7-2.7c0.5-2.4-1.6-3.7-0.4-5.7c1.3-1.9,4.8-2.6,6.4-4.4c-0.7-8-9.8-9.4-10.4-17.1c1.6-1,2.7-2.6,2.7-5
			c-1-1.5-5.6-10.4-4.6-13.4c0.9-2.6,3.9-4.3,1.8-8.9c-2.7-5.9-10.2-8.6-15.7-12c-3.6-2.2-8-8.9-13.9-4.2c-1.1-0.6-1.5-1.5-2.4-2.3
			c-1.6,0.8-3.5,2-4.3,2.7C62.8,16.8,53.1,19.2,47,15.5z"/>
	</svg>

	<!-- the angle -->
	<path fill="none" stroke="{$angle_color}" stroke-width="{$onepx}" stroke-dasharray="$dashlength" d="M{$corner1[0]},{$corner1[1]}L{$eye_x},{$eye_y}L{$corner2[0]},{$corner2[1]}"/>
	<path fill="none" stroke="{$angle_color}" stroke-width="{$onepx}" d="M{$eye_x},{$eye_y}m{$arc_start[0]},-{$arc_start[1]}a{$arc_radius},{$arc_radius},0,0,1,0,{$arc_height}"/>
	<use xlink:href='#arrowhead' fill="{$angle_color}" transform="translate({$arc_start[0]},-{$arc_start[1]}) translate({$eye_x},{$eye_y}) rotate(-{$angle_half})"/>
	<use xlink:href='#arrowhead' fill="{$angle_color}" transform="translate({$arc_start[0]},{$arc_start[1]}) translate({$eye_x},{$eye_y}) rotate({$angle_half}) rotate(180)"/>

	<!-- the size -->
	<path fill="none" stroke="{$size_color}" stroke-width="{$onepx}" d="M{$corner1[0]},{$corner1[1]} l0,{$size}"/>
	<use xlink:href='#arrowhead' fill="{$size_color}" transform="translate({$corner1[0]},{$corner1[1]})"/>
	<use xlink:href='#arrowhead' fill="{$size_color}" transform="translate({$corner2[0]},{$corner2[1]}) rotate(180)"/>

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
			
			#if (feature("fonts")) { include("fonts.css"); }
			include("style.css");
			ob_end_flush();
		?>
	</style>
	<?php endif; ?>

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
	<p>Enter any two values and click <button type="button" aria-disabled="true" tabindex="-1"><?php print $button; ?></button> next to the remaining field that you want to calculate.</p>
	
	<div class="solve-for distance" data-what="distance">
		<h3>Viewing Distance</h3>
		<input tabindex='2' id='distance-value' type="number" name="distance" min="0" step="any" value="<?php echo $sizecalc->fieldValue('distance'); ?>">
		<select tabindex='2' class='length' id='distance-units' name="distance-units">
			<?php $sizecalc->printLengthOptions('distance', 3); ?>
		</select>
		<button tabindex="3" name="solve-for" type="submit" value="distance" aria-label="Solve for distance"><?php print $button; ?></button>
	</div>
	
	<div class="solve-for size" data-what="size">
		<h3>Physical Size</h3>
		<input tabindex='2' id='size-value' type="number" name="size" min="0" step="any" value="<?php echo $sizecalc->fieldValue('size'); ?>">
		<select tabindex='2' class='length' id='size-units' name="size-units">
			<?php $sizecalc->printLengthOptions('size', 3); ?>
		</select>
		<button tabindex="3" name="solve-for" type="submit" value="size" aria-label="Solve for physical size"><?php print $button; ?></button>
	</div>
	
	<div class="solve-for angle" data-what="angle">
		<h3>Perceived Size</h3>
		<input tabindex='2' id='angle-value' type="number" name="angle" min="0" step="any" value="<?php echo $sizecalc->fieldValue('angle'); ?>">
		<select tabindex='2' class='angle' id='angle-units' name="angle-units">
			<?php $sizecalc->printAngleOptions('angle', 3); ?>
		</select>
		<button tabindex="3" name="solve-for" type="submit" value="angle" aria-label="Solve for perceived size"><?php print $button; ?></button>
	</div>
</form>
</div>

<?php $sizecalc->printSVG(true); ?>

<div id='credits'>
Size&nbsp;Calculator is a project by <a href='http://nicksherman.com/'>Nick&nbsp;Sherman</a> and <a href='https://chrislewis.codes/'>Chris&nbsp;Lewis</a>. Follow&nbsp;<a href='https://twitter.com/SizeCalculator'>@SizeCalculator</a> on&nbsp;Twitter.
</div>

</body>
</html>
