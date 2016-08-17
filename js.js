/*
 * DESIGN BY NICK SHERMAN http://nicksherman.com/
 * CODE BY CHRIS LEWIS http://chrissam42.com/
 */


//google analytics

function SizeCalculatorClass() {
	
	// MULTIPLY to convert FROM units
	// DIVIDE to convert TO units
	
	this.ANGLE = {
		'arcseconds': 1/(60*60)*Math.PI/180, 
		'arcminutes': 1/60*Math.PI/180,
		'rpx': Math.PI/180*0.021315384,
		'degrees': Math.PI/180,
		'radians': 1
	};
	
	this.LENGTH = {
		'px': 1/96,
		'points': 1/72,
		'picas': 1/6,
		'inches': 1,
		'feet': 12,
		'yards': 36,
		'miles': 12*5280,
		'millimeters': 1/25.4,
		'centimeters': 1/2.54,
		'meters': 39.3701,
		'kilometers': 39.3701*1000,
		'lightyears': 3.72461748e17
	};
	
	this.reference_distance = {
		'#phone': [10,'inches',  'points'],
		'#tablet': [15,'inches', 'points'],
		'#laptop': [20,'inches', 'points'],
		'#desktop': [25,'inches','points']
	};
	
	this.justtyped = this.touchone = this.touchtwo = this.solvefor = null;

	//when an input gets set, its slider gets rescaled to a range of 1/5 to 5x the input value
	this.setSliderRange = function(slider,baseval,units) {
		if (units in SizeCalculator.ANGLE) {
			slider.prop({
				'min': 0,
				'max': Math.PI/SizeCalculator.ANGLE[units],
				'value': baseval,
				'step': units == 'radians' ? .01 : Math.floor(Math.PI/SizeCalculator.ANGLE[units]/slider.width())
			});
		} else {
			var factor = 5;
			var range = baseval*(factor-1/factor);
			var exactstep = range/slider.width();
			var magnitude;
			if (exactstep >= 1)
				magnitude = 1;
			else {
				magnitude = Math.pow(10,-Math.floor(Math.log(exactstep)/Math.log(10))+1);
			}
			slider.prop({
				'min': magnitude > 1 ? Math.round(magnitude*baseval/5)/magnitude : Math.floor(baseval/5),
				'max': magnitude > 1 ? Math.round(magnitude*baseval*factor)/magnitude : Math.floor(baseval*factor),
				'value': baseval,
				'step': magnitude > 1 ? Math.round(magnitude*exactstep)/magnitude : Math.floor(exactstep)
			});
		}
	}

	this.updateHash = function() {
		if (SizeCalculator.touchone && SizeCalculator.touchtwo && SizeCalculator.solvefor) {
			var newhash = [];
			if (SizeCalculator.solvefor != 'distance')
				newhash.push("distance=" + $('#distance-value').val() + $('#distance-units').val());
			if (SizeCalculator.solvefor != 'physical-size')
				newhash.push("physical-size=" + $('#physical-size-value').val() + $('#physical-size-units').val());
			if (SizeCalculator.solvefor != 'perceived-size')
				newhash.push("perceived-size=" + $('#perceived-size-value').val() + $('#perceived-size-units').val());
			newhash.push(SizeCalculator.solvefor + "-units=" + $('#' + SizeCalculator.solvefor + "-units").val());

			if (!SizeCalculator.observer.hasClass('default')) {
				newhash.push('observer=' + encodeURIComponent(SizeCalculator.observer.prop('src')));
				newhash.push('eyeleft=' + SizeCalculator.eyeballratio.left);
				newhash.push('eyetop=' + SizeCalculator.eyeballratio.top);
				newhash.push('oheight=' + SizeCalculator.realobserverheight);
			}

			if (SizeCalculator.object) {
				newhash.push('object=' + encodeURIComponent(SizeCalculator.object.prop('src')));
			}

			try {
				window.history.replaceState({},'','#' + newhash.join('&'));
			}
			catch (e) {
				window.location.hash = newhash.join('&');
			}
		}
	}

	//this gets run every time an input is changed. it does all the geometric math and fills in the solve-for field
	this.doEverything = function(evt) {

		//what value is being updated
		var el = $(this);
		var select = this.tagname=='SELECT' ? el : el.siblings('select');
		var lock = el.is('input.lock') ? el : el.siblings('input.lock');
		var number = el.is('input[type=number]') ? el : el.siblings('input[type=number]');
		var range = el.is('input[type=range]') ? el : el.siblings('input[type=range]');
		
		var whatami = el.closest('div.solve-for').data('what');

		if (el.is(lock)) {
			//lock click
			
			//un-lock all the others
			if (this.checked) {
				$('input[type=number]').data('locked',false);
				$('input.lock').not(el).prop('checked',false);
			}

			//set this as locked
			$('#' + this.id.replace('-lock','-value')).data('locked',this.checked);
		}

		$('input.solved').removeClass('solved distance physical-size perceived-size');

		if (el.is(range)) {
			//all the sliders do is fill in their sibling number field
			if (!this.value) return;
			number.val(this.value).data('locked',false);
			lock.prop('checked',false);
		}
		else if (el.is(number)) {
			//whenever you type a number, re-scale the slider to center on the new value
			SizeCalculator.setSliderRange(range,this.value,select.val());
			if (evt.type == 'keyup') {
				SizeCalculator.justtyped = whatami;
				el.data('locked',false);
				lock.prop('checked',false);
			}
		}

		if (this.tagName=='SELECT' && SizeCalculator.solvefor && whatami == SizeCalculator.solvefor) {
		}
		else if (this.tagName=='SELECT' && whatami != SizeCalculator.justtyped) {
			var currentval = parseFloat(el.data('canonicalvalue'));
			if (currentval) {
				number.val(currentval/SizeCalculator[el.data('unittype')][el.val()]).trigger('change');
				return;
			}
		}
		else if (whatami != SizeCalculator.touchone) {
			//keep track of the last two fields to be updated; the remaining one is the one to solve for
			if (SizeCalculator.touchone && $('#' + SizeCalculator.touchone + '-value').data('locked')) {
				SizeCalculator.touchtwo = whatami;
			} else {
				SizeCalculator.touchtwo = SizeCalculator.touchone;
				SizeCalculator.touchone = whatami;
			}
			SizeCalculator.justtyped = null;
		}
	
		//console.log(SizeCalculator.touchtwo + ',' + SizeCalculator.touchone);

		//grab the conversion factors for the selected units
		var distanceratio = SizeCalculator.LENGTH[$('#distance-units').val()];
		var perceivedratio = SizeCalculator.ANGLE[$('#perceived-size-units').val()];
		var physicalratio = SizeCalculator.LENGTH[$('#physical-size-units').val()];

		//normalize the entered numbers to standard units		
		var distance = parseFloat($('#distance-value').val() || 0)*distanceratio;
		var perceived = parseFloat($('#perceived-size-value').val() || 0)*perceivedratio;
		var physical = parseFloat($('#physical-size-value').val() || 0)*physicalratio;

		$('#distance-units').data('canonicalvalue',distance);
		$('#perceived-size-units').data('canonicalvalue',perceived);
		$('#physical-size-units').data('canonicalvalue',physical);

		SizeCalculator.updateHash();

		//figure out which field we're solving for
		if ((SizeCalculator.touchone=='distance' || SizeCalculator.touchtwo=='distance') && (SizeCalculator.touchone=='perceived-size' || SizeCalculator.touchtwo=='perceived-size'))
			SizeCalculator.solvefor = 'physical-size';
		else if ((SizeCalculator.touchone=='distance' || SizeCalculator.touchtwo=='distance') && (SizeCalculator.touchone=='physical-size' || SizeCalculator.touchtwo=='physical-size'))
			SizeCalculator.solvefor = 'perceived-size';
		else if ((SizeCalculator.touchone=='physical-size' || SizeCalculator.touchtwo=='physical-size') && (SizeCalculator.touchone=='perceived-size' || SizeCalculator.touchtwo=='perceived-size'))
			SizeCalculator.solvefor = 'distance';
		else
			return;
	
		//console.log(SizeCalculator.solvefor);

		// all the actual math to solve for each measurement
		switch (SizeCalculator.solvefor) {
			case 'distance':
				if (!perceived || !physical) return;
				var finaldistance = physical/2 / Math.tan(perceived/2/SizeCalculator.ANGLE.radians) / distanceratio;
				finaldistance = Math.round(1000000*finaldistance)/1000000;
				$('#distance-value').addClass('solved distance').val(finaldistance);
				SizeCalculator.setSliderRange($('#distance-value').nextAll('input[type=range]'),finaldistance,$('#distance-units').val());
				break;
			case 'perceived-size':
				if (!distance || !physical) return;
				var finalperceived = 2 * Math.atan2(physical/2,distance)*SizeCalculator.ANGLE.radians / perceivedratio;
				perceived = finalperceived * perceivedratio;
				finalperceived = Math.round(1000000*finalperceived)/1000000;
				$('#perceived-size-value').addClass('solved perceived-size').val(finalperceived);
				SizeCalculator.setSliderRange($('#perceived-size-value').nextAll('input[type=range]'),finalperceived,$('#perceived-size-units').val());
				break;
			case 'physical-size':
				if (!distance || !perceived) return;
				var finalphysical = 2 * distance * Math.tan(perceived/2/SizeCalculator.ANGLE.radians) / physicalratio;
				finalphysical = Math.round(1000000*finalphysical)/1000000;
				$('#physical-size-value').addClass('solved physical-size').val(finalphysical);
				SizeCalculator.setSliderRange($('#physical-size-value').nextAll('input[type=range]'),finalphysical,$('#physical-size-units').val());
				break;
		}

		/*
		//update the mobile/tablet/laptop/desktop reference numbers		
		for (var i in SizeCalculator.reference_distance) {
			var svg = $(i);
			if (!svg.length) continue;
			var units = SizeCalculator.reference_distance[i][1];
			var distance = SizeCalculator.reference_distance[i][0]*SizeCalculator.LENGTH[units];
			var result = Math.round(2 * distance * Math.tan(perceived/2/SizeCalculator.ANGLE.radians) / SizeCalculator.LENGTH[SizeCalculator.reference_distance[i][2]]);
			svg.children()[1].firstChild.textContent=result + " " + SizeCalculator.reference_distance[i][2];
		}
		*/
	
		//do the visualization
		SizeCalculator.doIllustration();
	}

	//set up observer-replacement stuff
	this.initTheObserver = function(originleft,origintop,realheight) {
		this.observer = $('#observer');
		var w = this.observer.width(), h = this.observer.height();
		this.observeraspect = w / h;
		this.eyeballratio = {'left':originleft || 148/207, 'top':origintop || 70/1024};
		this.realobserverheight = realheight || 67; //inches

		var posform = function() {
			var pos = SizeCalculator.illustration.offset();
			var winwidth = SizeCalculator.illustration.outerWidth();
			var winheight = SizeCalculator.illustration.outerHeight();
			var mywidth = $('#replacetheobserver').outerWidth();
			var myheight = $('#replacetheobserver').outerHeight();

			$('#replacetheobserver').css({
				'top': (pos.top + (winheight-myheight)/2) + 'px',
				'left': (pos.left + (winwidth-mywidth)/2) + 'px',
				'max-width': (winwidth*0.8) + 'px',
				'max-height': (winheight*0.8) + 'px'
			});
		}
	}

	//this gets run once on page load. sets up invariant things like HTML element references, defaults, colors	
	this.initIllustration = function() {
		this.initTheObserver();
		this.container = $('#illustration_container');

		//default pixel sizes
		this.defaultobserverheight = 1024;
		this.defaulttextheight = 144;

		this.illustration = $('#illustration');
		this.canvas = this.illustration.get(0).getContext('2d');
		
		this.distancecolor = $('div.solve-for.distance').css('color');
		this.sizecolor = $('div.solve-for.physical-size').css('color');
		this.anglecolor = $('div.solve-for.perceived-size').css('color');

		this.scale = 1;
		this.textheight = this.defaulttextheight;
		this.textwidth = this.defaulttextheight;
		this.object = null;
		this.objectaspect = 1;
		this.setObserverHeight(this.defaultobserverheight);
		this.windowResize(); //this calls the actual illustration
		this.observer.css('visibility','visible'); //to avoid showing flash of unsized observer
	}

	//set a bunch of stuff that is dependent on the window size
	this.windowResize = function(evt) {
		//canvas dimensions
		this.fullwidth = this.container.width();
		//this.container.css('height','');
		this.fullheight = Math.max($(window).height() - this.container.position().top, parseInt(this.container.css('min-height')) || 0);

		//size the illustration to always fit in the window height with no scrolling
		this.container.outerHeight(this.fullheight);
		//this.containerpadding = parseFloat(this.container.css('padding-left'));

		//size the canvas
		this.illustration.attr({'width':this.fullwidth, 'height': this.fullheight });
		this.illustration.css({'width':this.fullwidth + 'px', 'height': this.fullheight + 'px' });

		//and reset this.canvas -- hidpi.js fixes the scaling for hi-dpi devices in getContext
		this.canvas = this.illustration.get(0).getContext('2d');

		//regenerate the image
		this.doIllustration();
	}


	//this figures out the display sizes for everything based on the "physical" measurements entered in the form
	this.setSizes = function() {
		//grab all the numbers from the form
		var distanceratio = this.LENGTH[$('#distance-units').val()];
		var perceivedratio = this.ANGLE[$('#perceived-size-units').val()];
		var physicalratio = this.LENGTH[$('#physical-size-units').val()];

		var distance = parseFloat($('#distance-value').val() || 0)*distanceratio;
		var perceived = parseFloat($('#perceived-size-value').val() || 0)*perceivedratio;
		var physical = parseFloat($('#physical-size-value').val() || 0)*physicalratio;

		//incomplete data, bail
		if (!distance || !perceived || !physical) {
			return;
		}

		//all we have to do is find the "real" physical width of the system and scale it to the screen
		var realwidth = physical*this.objectaspect + distance + this.realobserverheight*this.observeraspect*this.eyeballratio.left; //distance of eyeball from edge of canvas

		//conversion factor from "real" units to pixels
		var scale = this.fullwidth / (realwidth * 96);

		//text height is directly set by the "physical size" entry
		this.textheight = physical * 96 * scale;
		if (this.object) {
			this.textwidth = this.textheight * this.object.width() / this.object.height() + 12;
		}
		else {
			this.textwidth = physical * 96 * scale;
		}

		//further scaling if the text is too big to fit on screen. this cales the whole illustration down so it fits
		this.scale = Math.min(1,this.fullheight/this.textheight);

		//set the observer's height
		this.setObserverHeight(this.realobserverheight*96*scale);
	}


	//several things are dependent on the observer's height, primarly the position of his eyeball
	this.setObserverHeight = function(h) {
		this.observerheight = h * this.scale;
		this.observer.height(this.observerheight);
		this.observer.width(this.observerheight*this.observeraspect);

		this.eyeball = {
			'left':this.observerheight*this.observeraspect*this.eyeballratio.left,
			'top':this.observerheight*this.eyeballratio.top
		};
	}

	//function to draw an arrowhead with the point at x,y and any given clockwize angle (in radians)	
	this.arrowheadsize = 7;
	this.arrowhead = function(x,y,angle,color) {
		angle = angle || 0;

		if (color)
			this.canvas.fillStyle = color;

		this.canvas.beginPath();
		this.canvas.moveTo(x,y);
		this.canvas.lineTo(x+Math.cos(angle+Math.PI/3)*this.arrowheadsize,y+Math.sin(angle+Math.PI/3)*this.arrowheadsize);
		this.canvas.lineTo(x+Math.cos(angle+Math.PI*2/3)*this.arrowheadsize,y+Math.sin(angle+Math.PI*2/3)*this.arrowheadsize);
		this.canvas.closePath();
		this.canvas.fill();
	}


	//this takes all the calculations above and draws the image
	this.doIllustration = function() {
		this.setSizes();
		
		//eyeball
		var origin = [Math.floor(this.eyeball.left),Math.floor(this.eyeball.top)];

		//pixels of distance
		var distance = this.fullwidth - this.textwidth - origin[0];

		//top left corner of the text box
		var boxcorner = [Math.floor(origin[0]+distance)+0.5,Math.floor(origin[1]-this.textheight/2)+0.5];
		this.boxcorner = boxcorner;

		//half of the perceived size angle
		var angle = Math.atan2(this.textheight/2,distance);

		//CLEAR ALL THE THINGS
		this.canvas.setTransform(1,0,0,1,0,0);
		this.canvas.clearRect(0,0,this.fullwidth,this.fullheight);
		this.observer.css('top',0);
		this.container.css('left',0);

		//NOTE TRANSFORMS ARE APPLIED IN REVERSE ORDER

		//shrink and center the whole thing if the text is too big
		if (this.scale < 1) {
			this.container.css('left',this.fullwidth*(1-this.scale)/2 + 'px');
			this.canvas.translate(origin[0]*(1-this.scale),origin[1]*(1-this.scale));
			this.canvas.scale(this.scale,this.scale);
		}

		//if the text box goes off the top, move everything down
		if (boxcorner[1] < 0) {
			this.canvas.translate(0,-boxcorner[1]);
			this.observer.css('top',(-boxcorner[1]*this.scale) + 'px');
		}
	
		if (this.object) {
			this.object.css({
				'left': (boxcorner[0]*this.scale+origin[0]*(1-this.scale)+12+1) + 'px',
				'top': Math.max(0,boxcorner[1]*this.scale) + 'px',
				'height': (this.textheight * this.scale) + 'px'
			});
		}
		else {
			//TEXT
			this.canvas.fillStyle='#EEE';
			this.canvas.fillRect(boxcorner[0],boxcorner[1],this.textwidth,this.textheight);
	
			//this.canvas.font = 'bold ' + (this.textheight) + 'px "Scout RE"';
			this.canvas.font = (this.textheight) + 'px Scout';
			this.canvas.textAlign='center';
			this.canvas.fillStyle='black';
	
			//center
			/*
			this.canvas.textBaseline='middle';
			var horigin = boxcorner[0] + this.textwidth/2;
			var vorigin = origin[1];
			*/
			
			this.canvas.textBaseline='bottom';
			var horigin = boxcorner[0] + this.textwidth/2;
			var vorigin = boxcorner[1] + this.textheight;
			
			this.canvas.fillText('Aa',horigin,vorigin);
		}
		
		// ANGLE
		this.canvas.strokeStyle = this.anglecolor;
		if ('setLineDash' in this.canvas)
			this.canvas.setLineDash([6,8]);
		else if ('webkitLineDash' in this.canvas)
			this.canvas.webkitLineDash = [6,8];
		else if ('mozDash' in this.canvas)
			this.canvas.mozDash = [6,8];
		this.canvas.beginPath()
		this.canvas.moveTo(origin[0],origin[1]);
		this.canvas.lineTo(boxcorner[0],boxcorner[1]);
		this.canvas.moveTo(origin[0],origin[1]);
		this.canvas.lineTo(boxcorner[0],boxcorner[1]+this.textheight);
		this.canvas.stroke();

		// DISTANCE
		this.canvas.strokeStyle = this.distancecolor;
		if ('setLineDash' in this.canvas)
			this.canvas.setLineDash([]);
		else if ('webkitLineDash' in this.canvas)
			this.canvas.webkitLineDash = [];
		else if ('mozDash' in this.canvas)
			this.canvas.mozDash = [];
		this.canvas.beginPath()
		this.canvas.moveTo(origin[0],origin[1]);
		this.canvas.lineTo(boxcorner[0]-1,origin[1]);
		this.canvas.stroke();
		this.arrowhead(origin[0],origin[1],Math.PI*3/2,this.distancecolor);
		this.arrowhead(origin[0]+distance,origin[1],Math.PI/2,this.distancecolor);

		// ARC
		//  this is down here so it goes on top of the distance line
		this.canvas.strokeStyle = this.anglecolor;
		//arc(x,y,radius,startangle,endangle,counterclockwise)
		this.canvas.beginPath();
		//this.canvas.arc(origin[0],origin[1],Math.min((boxcorner[0]-origin[0])*0.9,Math.sqrt(Math.pow(boxcorner[0]-origin[0],2)+Math.pow(this.textheight/2,2))*0.6),angle,-angle,true);
		var arcwhere = 0.5
		this.canvas.arc(origin[0],origin[1],distance*arcwhere,angle,-angle,true);
		this.canvas.stroke();
		this.arrowhead(origin[0]+distance*arcwhere*Math.cos(-angle),origin[1]-this.textheight/2*arcwhere*Math.cos(-angle),-angle,this.anglecolor);
		this.arrowhead(origin[0]+distance*arcwhere*Math.cos(angle),origin[1]+this.textheight/2*arcwhere*Math.cos(-angle),Math.PI+angle,this.anglecolor);
			
		// HEIGHT
		this.canvas.strokeStyle =this.sizecolor;
		this.canvas.beginPath()
		this.canvas.moveTo(boxcorner[0],boxcorner[1]);
		this.canvas.lineTo(boxcorner[0],boxcorner[1]+this.textheight);
		this.canvas.stroke();
		this.arrowhead(boxcorner[0],boxcorner[1],0,this.sizecolor);
		this.arrowhead(boxcorner[0],boxcorner[1]+this.textheight,Math.PI,this.sizecolor);
	}
}

var SizeCalculator = new SizeCalculatorClass;

$(window).on('load',function() {
	SizeCalculator.initIllustration();
	
	$(window).on('resize',$.proxy(SizeCalculator.windowResize,SizeCalculator));

	$('#distance-units').data('unittype','LENGTH');
	$('#physical-size-units').data('unittype','LENGTH');
	$('#perceived-size-units').data('unittype','ANGLE');

	$('input').on('keyup change input',SizeCalculator.doEverything);
	$('select').on('change',SizeCalculator.doEverything);

	//initiate values

	//this has to come before the others or it will delete the object from the url
	var observer;
	if (/observer=([^&]+)/.test(window.location.hash)) {
		observer = {'url': RegExp.$1};
	}
	if (/eyeleft=([^&]+)/.test(window.location.hash)) {
		observer.left = parseFloat(RegExp.$1);
	}
	if (/eyetop=([^&]+)/.test(window.location.hash)) {
		observer.top = parseFloat(RegExp.$1);
	}
	if (/oheight=([^&]+)/.test(window.location.hash)) {
		observer.height = parseFloat(RegExp.$1);
	}

	var object;	
	if (/object=([^&]+)/.test(window.location.hash)) {
		object = decodeURIComponent(RegExp.$1);
	}


	if (observer) {
		$('#observer').replaceWith("<img id='observer' src='" + decodeURIComponent(observer.url) + "'>");
		SizeCalculator.updateHash();
		$('#observer').on('load',function() {
			SizeCalculator.initTheObserver(observer.left,observer.top,observer.height);
			SizeCalculator.doIllustration();
		});
	}

	if (object) {
		SizeCalculator.loadNewObject(object);
	}

	var initvals, unitvals;
	if (initvals = /([a-z-]+)=([\d.e-]+)([a-z]+)&([a-z-]+)=([\d.e-]+)([a-z]+)/.exec(window.location.hash)) {
		//do the units first so they don't recalculate the value
		$('#' + initvals[1] + '-units').val(initvals[3]).trigger('change');
		$('#' + initvals[1] + '-value').val(initvals[2]).trigger('change');
		$('#' + initvals[4] + '-units').val(initvals[6]).trigger('change');
		$('#' + initvals[4] + '-value').val(initvals[5]).trigger('change');
		if (unitvals = /([\w-]+-units)=(\w+)/.exec(window.location.hash)) {
			$('#' + unitvals[1]).val(unitvals[2]).trigger('change');
		}
	}

	SizeCalculator.updateHash();
});
