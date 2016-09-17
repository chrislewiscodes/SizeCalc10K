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
			
			
			//$human_height = 66; //5 foot 6, arbitrary
			//$human_width = $human_height * 144.1 / 704.5; //from svg viewbox
			
			$human_height = 48;
			$human_width = $human_height * 267/183;
			
			//$eye_x = $human_width * 0.7;
			//$eye_y = $human_width * 0.333;
			
			$eye_x = $human_width * 0.75;
			$eye_y = $human_width * 0.07;
			
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

/*
	//original man
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
*/

/* //original object
	<rect fill="#EEEEEE" stroke="none" stroke-width="{$onepx}" x="{$corner1[0]}" y="{$corner1[1]}" width="$size" height="$size" />
	<text x="{$fontpos[0]}" y="{$fontpos[1]}" style="font-family:Verdana,sans-serif;font-weight:bold;font-size:{$fontsize};stroke:none;fill:black">A</text>
*/
			$ribbon = implode(',', array(
				"M{$corner1[0]},{$corner1[1]}",
				'l' . ($size*0.5) . ',' . ($size*0.6),
				'l' . ($size*0.5) . ',-' . ($size*0.6),
				'l-' . ($size*0.15) . ',0',
				'l-' . ($size*0.35) . ',' . ($size*0.45),
				'l-' . ($size*0.35) . ',-' . ($size*0.45),
				'z',
			));
			$medal = array($corner1[0]+$halfsize, $corner1[1] + $size*0.75, $size*0.27, $size*0.2);

			print <<<EOF
<svg id="visualization" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="{$px_width}px" height="{$px_height}px" viewBox="0 0 {$image_width} {$image_height}">
	<!-- the observer -->

<?xml version="1.0" encoding="UTF-8" standalone="no"?>
	<svg
	   xmlns:dc="http://purl.org/dc/elements/1.1/"
	   xmlns:cc="http://creativecommons.org/ns#"
	   xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
	   xmlns:svg="http://www.w3.org/2000/svg"
	   xmlns="http://www.w3.org/2000/svg"
	   version="1.0"
	   x="{$human_x}" y="{$human_y}"
	   width="{$human_width}"
	   height="{$human_height}"
	   viewBox="20 62 267 183"
	   id="svg2">
	  <defs
	     id="defs20" />
	  <metadata
	     id="metadata4">
	<rdf:RDF>
	  <cc:Work
	     rdf:about="">
	    <dc:format>image/svg+xml</dc:format>
	    <dc:type
	       rdf:resource="http://purl.org/dc/dcmitype/StillImage" />
	    <dc:title></dc:title>
	  </cc:Work>
	</rdf:RDF>
	</metadata>
	  <g
	     transform="matrix(0.1,0,0,-0.1,0,300)"
	     id="g6"
	     style="fill:#000000;stroke:none">
	    <path
	       d="M 883,2359 C 744,2339 586,2291 475,2236 308,2152 224,2093 244,2072 c 3,-2 33,5 67,17 34,11 154,33 267,48 194,26 210,26 355,15 178,-14 308,-39 398,-77 107,-44 129,-47 166,-22 77,52 78,136 1,201 -107,92 -381,138 -615,105 z"
	       id="path8"
	       style="fill:#0084c9;fill-opacity:1;stroke:none" />
	    <path
	       d="m 1969,2333 c -109,-56 -148,-168 -96,-276 68,-139 248,-169 359,-60 179,175 -37,450 -263,336 z"
	       id="path10" />
	    <path
	       d="m 1989,1832 c -159,-102 -145,-432 25,-540 35,-22 55,-27 131,-30 168,-8 367,61 496,171 98,83 201,247 155,247 -13,0 -90.5414,-54.755 -145.9886,-93.8764 C 2594.5641,1547.0022 2437,1484 2341,1465 c -116,-24 -166,-17 -208,29 -37,40 -41,68 -22,171 15,79 5,130 -31,167 -16,15 -32,28 -37,28 -5,0 -29,-13 -54,-28 z"
	       id="path12"
	       style="fill:#e5053a;fill-opacity:1;stroke:none" />
	    <path
	       d="m 753,1770 c -81,-49 -84,-156 -9,-339 73,-178 73,-246 -3,-361 C 666,955 447,751 280,640 c -79,-52 -75,-49 -68,-56 11,-10 269,85 372,138 365,188 519,382 476,601 -14,72 -104,356 -126,399 -16,32 -52.7869,65.8764 -116.13494,62.3821 C 803.86506,1784.3821 771,1781 753,1770 z"
	       id="path14"
	       style="fill:#fca311;fill-opacity:1;stroke:none" />
	    <path
	       d="m 1101,1662 c -39,-39 -41,-86 -5,-162 48,-105 197,-216 383,-286 47,-18 87,-39 89,-46 1,-8 -16,-24 -38,-37 -23,-12 -114,-73 -203,-134 -89,-62 -198,-133 -242,-159 -53,-32 -73,-48 -58,-48 12,0 98,15 191,34 236,48 405,120 525,226 57,51 77,86 77,138 0,33 -8,48 -57,102 -58,64 -255,233 -338,290 -109.3253,73.8424 -190,100 -245,107 -40.5057,-0.6179 -51,3 -79,-25 z"
	       id="path16"
	       style="fill:#009e49;fill-opacity:1;stroke:none" />
	  </g>
	</svg>

	<!-- arrowhead -->
	<defs>
	<path stroke="none" id='arrowhead' d='M0,0L{$arrowhalf},{$arrowheight}L-{$arrowhalf},{$arrowheight}Z'/>
	</defs>

	<!-- the object -->
	<g transform="translate(-{$arrowsize}, 0)">
	<path fill="#CCCCCC" stroke="none" d="{$ribbon}" transform="translate({$medal[0]},0) scale(0.7,1) translate(-{$medal[0]},0)"/>
	<circle fill="gold" stroke="none" cx="{$medal[0]}" cy="{$medal[1]}" r="{$medal[2]}"/>
	<circle fill="none" stroke="#FC6" stroke-width="$onepx" cx="{$medal[0]}" cy="{$medal[1]}" r="{$medal[3]}"/>
	</g>

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
