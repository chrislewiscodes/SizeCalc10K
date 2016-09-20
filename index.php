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
	<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="{$human_x}" y="{$human_y}" width="{$human_width}" height="{$human_height}" viewBox="1 15 145.6 720">
		<path d="M26.6,414.3c2.2,2.5,0.9,10.8,0.2,14.9c-0.7,4.1,2.3,6.5,2.2,11c-0.1,4.5,0.2,24.2,0,32.6
			c-0.2,8.5,0.9,15.9-1.5,19.6s0.1,8.9-0.2,12.2s-3.3,10.2-2.6,15.5c0.7,5.4-4.2,10.4-3,12.2s3.3,6.8,3.3,9.7c0,2.9-2.5,7.6-3.9,17.1
			s-1.9,17.9-1.4,28.7s0.1,33.3-0.7,38.5s1.8,22.2,1.8,29.6c0,7.4,1.8,10.4,1.8,14.8c0,4.4,1.5,16.3,0.7,19.6
			c-0.7,3.3-0.4,8.5-0.4,8.5l3.2,2.3c0,0-1.2,6.5-1.2,9.3c0,2.6,0.1,6.2,1,9c0,0,2.2,1.5,3.3,1.5c0.4,4.8,0.4,9.6,9.1,10.6
			c8.7,1.1,32,1.3,40.5,1.8s29.4,2.2,38.5,2.2c13.5,0,17.6-1.9,23.2-5.4c0-7.6-3.6-12.8-10.7-13.5c-1.3-5.8-6.4-9.7-12.3-9.7
			c-5.9,0-7.6,0.1-12-0.6c-4.4-0.7-31.1-14.1-31.1-15.5c2.2-0.4,4-1.5,4-1.5s-1.7-6.3-1-9.6c0.7-3.3,3.3-7.4,1.5-12.2
			c-1.8-4.8,1.5-16.6,0.4-23.7c-1.1-7,0-24.4,0.4-34.4c0.4-10,2.6-34,5.2-41.4c2.6-7.4,6.7-16.5,4.8-25.8c-1.3-6.4,3.8-21.1,5.7-30.2
			c2-9,3.1-22,3.7-26.9s4.4-16.6,6.4-26.8s3.2-35.4,5.1-41.5c1.9-6.1,1.6-32.4-0.8-37.5c0,0,1.3-1.4,2.4-1.9c0.3,0,0.5,4,0.5,4
			s3.8,0.2,5,0.3c-0.7,4.2-1,9.8,0.8,11.7c1.8,2,4.4,10.1,5.2,13.7c0.8,3.6-0.1,7.4-0.3,9.2c-0.2,1.8,0.7,4.7,0.8,5.6
			c0.1,0.8-0.5,1.4-1.2,2.5c-2.1-0.3-4.2-0.7-6.4-0.5s-3.5,2.1-3.8,3.4c0.7,1,1.8,3.7,1.8,3.7s8.4,2.9,9.7,3.1c1.3,0.2,5-0.9,8.5-1.8
			c3.4-0.9,7.8-1.6,8.6-4.9s1.5-8.8,1.8-11.3c0.3-2.5,1.9-6.9,1.7-8.4c-0.2-1.5-2.4-4.2-4-8.1c-1.6-3.9-6.1-17.8-7.7-24.1
			c1.4-0.4,1.6-2.2,1.8-4c0.2-1.8-2.2-5.9-3.2-9.5c-1-3.5-5.1-14.6-6.2-17.6c-1.1-3.1,0.7-9.3,0.7-11.4c0-2.1-4.6-13.1-6.1-15.6
			c-0.2-0.7,0.7-3.9,0.2-9.4s1.5-19.7,1.4-24.4s-0.6-14.8-0.4-21.7c0.2-6.9,0.7-13.3,0-19s-0.7-17.3-1.7-23.4
			c-1-6.2-0.7-11.1-2.2-19.2c-1.5-8.1-6.9-11.9-9.4-18.4s-8.2-13.2-12.4-20.3c-4.2-7.1-10.9-17.9-10.9-17.9l0.2-2.8c0,0-1.8-2-3.7-3.7
			c0-0.7,0.2-3,0.9-6.1c0.7-3.1,0.5-13.4,3.4-18.4c2.3-0.3,11.1,0.2,15.2-0.6c4.1-0.8,5.8-3.6,5.8-4.9s-0.3-4.9-0.3-6.4
			c0-1.5,3.1-3.5,3.1-4.7s-0.4-2.2-0.8-3.8c1-0.9,1.8-2.2,1.8-3.5c0-0.8-0.5-2.1-0.9-3.5c-0.3-1.4-0.4-3.7,1.2-3.9s4.3-2.1,4.3-3.9
			s-2.8-4.9-4.8-7.1c-2.8-3.1-5.1-4.5-5.2-6.2c0.9-1.9,1.8-5.2,1.8-6.4s-0.4-4.2-1.5-6.8c-1.2-2.8-1.4-5.1-1.4-6.6
			c0-2.5,2.5-4.1,2.5-5.8s-1.1-3.8-4.1-7.5c-3-3.7-10-6.2-13-8c-3-1.8-6.7-4.9-8.6-5.3c-1.8-0.4-4.4,1.6-4.4,1.6l-1.9-1.8
			c0,0-2.6,0.7-4.8,2.2c-2.2-1.8-10.3-1.8-14.2-2S49,15.8,49,15.8s-2.4,0.1-6,1.3S32.7,24.6,31,26.7c-1.7,2.1-0.6,6.9-4.6,7.4
			s-8.2,3.9-10.6,6.2c-2.3,2.3-1.9,6.9-1.9,9.9c0,2.9-2,8.1-2,13.9s6.4,15,8,17.4c1.6,2.4,5.7,6.7,6.7,8.5s0.5,5.9,8.4,8.1
			c12.5,3.4,12,15.8,8.8,23.7l-3.3-0.6c0,0-1.3,2-2.2,4.6c-0.2,2.4-6.6,7.4-10.7,11.5c-4.1,4-11.8,13.7-15.2,24.1
			c-2.9,9-1.7,23.8-2.2,34.9c-0.4,11.1,4.1,24,4.8,30c0.7,5.9,5.9,20.3,5.9,26.3s0,12.2,1.1,15.5s4.4,16.6,4.1,21.5
			c-0.4,4.8,2.6,14.4,1.8,18.1c-0.7,3.7-7.4,12.9-7.8,14.8c-0.4,1.8,2,4.4,2.7,8.8C5.4,369.6,14.2,400.1,26.6,414.3z"/>
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
