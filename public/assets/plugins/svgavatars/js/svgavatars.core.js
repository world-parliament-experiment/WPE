/**************************************************************************************
 * @name:       svgavatars.core.js - jQuery plugin for creating vector avatars
 * @version:    1.5
 * @URL:        https://svgavatars.com
 * @copyright:  (c) 2014-2019 DeeThemes (https://codecanyon.net/user/DeeThemes)
 * @licenses:   https://codecanyon.net/licenses/regular
 *              https://codecanyon.net/licenses/extended
***************************************************************************************/
window.jQuery(document).ready(function($) {
	"use strict";

	// getting default options from the svgavatars.defaults.js file and setting global vars
	var options = window.svgAvatarsOptions(),
		SVG = window.SVG,
		tinycolor = window.tinycolor,
		canvg = window.canvg;

	// set the version (please do not edit!)
	options.version = '1.4';

	// getting a translation
	var translation = window.svgAvatarsTranslation();

	// in case of forgetting to add the ending slash to the main folder's URL/path
	if ( !options.pathToFolder.match(/(\/)$/) && options.pathToFolder.trim() !== '' ) {
		options.pathToFolder += '/';
	}

	// extend SVGJS lib with special methods for moving and scaling controls
	SVG.extend(SVG.Element, {
		svgaCenterScale: function(sx, sy) {
			var temp = this.bbox(),
				cx = temp.cx,
				cy = temp.cy;
			if (!sy) {
				sy = sx;
			}
			return this.transform({
				a: sx,
				b: 0,
				c: 0,
				d: sy,
				e: cx - sx * cx,
				f: cy - sy * cy
			});
		},
		svgaLeft: function(times, step) {
			times = times ? times : 3;
			step = step ? step : leftRightStep;
			var leftright = this.data('leftright'),
				updown = this.data('updown');
			if ( leftright > -(times * step) ) {
				this.move(leftright - step, updown);
				this.data('leftright', leftright - step/* - 0.0000001*/);
			}
			return this;
		},
		svgaRight: function(times, step) {
			times = times ? times : 3;
			step = step ? step : leftRightStep;
			var leftright = this.data('leftright'),
				updown = this.data('updown');
			if ( leftright < times * step ) {
				this.move(leftright + step, updown);
				this.data('leftright', leftright + step/* + 0.0000001*/);
			}
			return this;
		},
		svgaUp: function(times, step) {
			times = times ? times : 3;
			step = step ? step : upDownStep;
			var leftright = this.data('leftright'),
				updown = this.data('updown');
			if ( updown > -(times * step) ) {
				this.move(leftright, updown - step);
				this.data('updown', updown - step/* - 0.0000001*/);
			}
			return this;
		},
		svgaDown: function(times, step) {
			times = times ? times : 3;
			step = step ? step : upDownStep;
			var leftright = this.data('leftright'),
				updown = this.data('updown');
			if ( updown < times * step ) {
				this.move(leftright, updown + step);
				this.data('updown', updown + step/* + 0.0000001*/);
			}
			return this;
		},
		svgaScaleUp: function(times, stepX, stepY) {
			times = times ? times : 3;
			stepX = stepX ? stepX : scaleStep;
			stepY = stepY ? stepY : stepX;
			var scaleX = this.data('scaleX') + 0.0000001,
				scaleY = this.data('scaleY') + 0.0000001;
			if ( scaleX < 1 + times * stepX ) {
				this.svgaCenterScale(scaleX + stepX, scaleY + stepY);
				this.data('scaleX', scaleX + stepX + 0.0000001);
				this.data('scaleY', scaleY + stepY + 0.0000001);
			}
			return this;
		},
		svgaScaleDown: function(times,stepX,stepY) {
			times = times ? times : 3;
			stepX = stepX ? stepX : scaleStep;
			stepY = stepY ? stepY : stepX;
			var scaleX = this.data('scaleX') - 0.0000001,
				scaleY = this.data('scaleY') - 0.0000001;
			if ( scaleX > 1 - times * stepX ) {
				this.svgaCenterScale(scaleX - stepX, scaleY - stepY);
				this.data('scaleX', scaleX - stepX - 0.0000001);
				this.data('scaleY', scaleY - stepY - 0.0000001);
			}
			return this;
		},
		svgaRotateLeft: function(times, step, cx, cy) {
			times = times ? times : 2;
			step = step ? step : rotateStep;
			var rotate = this.data('rotate');
			if ( rotate > -(times * step) ) {
				this.rotate(rotate - step - 0.0000001, cx, cy);
				this.data('rotate', rotate - step);
			}
			return this;
		},
		svgaRotateRight: function(times, step, cx, cy) {
			times = times ? times : 2;
			step = step ? step : rotateStep;
			var rotate = this.data('rotate');
			if ( rotate < times * step ) {
				this.rotate(rotate + step + 0.0000001, cx, cy);
				this.data('rotate', rotate + step);
			}
			return this;
		},
		svgaCancelRotate: function() {
			return this.rotate(0.0000001).data('rotate', 0.0000001, true);
		}
	});

	// initial variables (please do not change)
	var iOS = (navigator.userAgent.match(/(iPad|iPhone|iPod)/g) ? true : false),
		Android = (navigator.userAgent.match(/(Android)/g) ? true : false),
		Opera = (navigator.userAgent.match(/(Opera)/g) ? true : false),
		Win8tablet = (navigator.platform.toLowerCase().indexOf("win") !== -1 && navigator.userAgent.toLowerCase().indexOf("touch") !== -1) ? true : false,
		upDownStep = 1,
		leftRightStep = 1,
		scaleStep = 0.05,
		rotateStep = 15;

	// append HTML code to initial target element - "svgAvatars"
	var markup = [
	'<div id="svga-container" class="svga-no-touch">',
		'<div id="svga-canvas-wrap">',
			'<canvas id="svga-canvas"></canvas>',
		'</div>',
		'<div class="svga-row">',
			'<div class="svga-col-left">',
				'<div class="svga-vert-order-glob-controls">',
					'<div class="svga-row row-glob-controls">',
						'<div id="svga-glob-controls" class="scrollbar scroll-simple_outer"></div>',
					'</div>',
				'</div>',
				'<div class="svga-vert-order-colors">',
					'<div class="svga-row row-colors">',
						'<div id="svga-custom-color"><input type="text"></div>',
						'<div id="svga-colors-wrap">',
							'<div id="svga-colors" class="scrollbar scroll-simple_outer"></div>',
						'</div>',
					'</div>',
				'</div>',
				'<div class="svga-vert-order-svgcanvas">',
					'<div class="svga-row row-svgcanvas">',
						'<div id="svga-svgmain"></div>',
					'</div>',
				'</div>',
			'</div>',
			'<div class="svga-col-right">',
				'<div class="svga-vert-order-controls">',
					'<div class="svga-row row-controls">',
						'<div id="svga-controls" class="scrollbar scroll-simple_outer"></div>',
					'</div>',
				'</div>',
				'<div class="svga-vert-order-main">',
					'<div class="svga-android-hack">',
						'<div class="svga-vert-order-elements">',
							'<div class="svga-row row-elements">',
								'<div id="svga-elements" class="scrollbar scroll-simple_outer"></div>',
							'</div>',
						'</div>',
						'<div class="svga-vert-order-bodyzones">',
							'<div class="svga-row row-bodyzones">',
								'<div id="svga-bodyzones"></div>',
							'</div>',
						'</div>',
						'<div class="svga-vert-order-blocks">',
							'<div class="svga-row">',
								'<div id="svga-blocks" class="scrollbar scroll-simple_outer"></div>',
							'</div>',
						'</div>',
					'</div>',
				'</div>',
				'<div class="svga-row">',
					'<div id="svga-footermenu">',
						'<ul>',
							'<li id="svga-randomavatar"><div></div><span class="svga-mobilehidden">',translation.randomMsg,'</span></li>',
							'<li id="svga-resetavatar"><div></div><span class="svga-mobilehidden">',translation.resetMsg,'</span></li>',
							'<li id="svga-saveavatar"><div></div><span class="svga-mobilehidden">',translation.saveMsg,'</span></li>',
							'<li id="svga-shareavatar"><div></div><span class="svga-mobilehidden">',translation.shareMsg,'</span></li>',
							'<li id="svga-gravataravatar"><div></div><span class="svga-mobilehidden">',translation.gravatarMsg,'</span></li>',
							'<li id="svga-downloadavatar"><div></div><span class="svga-mobilehidden">',translation.downloadMsg,'</span>',
								'<ul>',
									'<li id="svga-svgfile">',translation.svgFormatMsg,'</li>',
									'<li id="svga-png-two">','png - ' + options.pngSecondDownloadSize + 'x' + options.pngSecondDownloadSize,'</li>',
									'<li id="svga-png-one">','png - ' + options.pngFirstDownloadSize + 'x' + options.pngFirstDownloadSize,'</li>',
								'</ul>',
							'</li>',
						'</ul>',
					'</div>',
				'</div>',
			'</div>',
		'</div>',
		'<p class="svga-credit">',translation.authoredMsg,'<a href="http://svgavatars.com" target="_blank" rel="nofollow">svgAvatars.com</a></p>',
		'<div id="svga-start-overlay">&nbsp;</div>',
		'<div id="svga-work-overlay">&nbsp;</div>',
		'<div id="svga-loader">',translation.waitMsg,'</div>',
		'<div id="svga-gender">',
			translation.welcomeSlogan,
			'<div id="svga-starticons-wrap">',
				'<div id="svga-start-boys"></div>',
				'<div id="svga-start-girls"></div>',
			'</div>',
			translation.welcomeMsg,
		'</div>',
		'<div id="svga-dialog">',
			translation.confirmMsg,
			'<div id="svga-dialog-btns">',
				'<div id="svga-dialog-ok">',translation.okMsg,'</div>',
				'<div id="svga-dialog-cancel">',translation.cancelMsg,'</div>',
			'</div>',
		'</div>',
		'<div id="svga-gravatar">',
			translation.gravatarTitle,
			'<div id="svga-gravatar-fields">',
					'<input class="svga-input" type="text" id="svga-gravatar-email" value="" placeholder="',translation.gravatarEmail,'">',
					'<input class="svga-input" type="password" id="svga-gravatar-pass" value="" placeholder="',translation.gravatarPwd,'">',
				'<p>',translation.gravatarRating,'<label class="svga-gr-rating"><input type="radio" name="svga-gr-rating" value="0" checked> G</label>',
					'<label class="svga-gr-rating"><input type="radio" name="svga-gr-rating" value="1"> PG</label>',
					'<label class="svga-gr-rating"><input type="radio" name="svga-gr-rating" value="2"> R</label>',
					'<label class="svga-gr-rating"><input type="radio" name="svga-gr-rating" value="3"> X</label></p>',
			'</div>',
			translation.gravatarNote,
			'<div id="svga-gravatar-btns">',
				'<div id="svga-gravatar-ok">',translation.installMsg,'</div>',
				'<div id="svga-gravatar-cancel">',translation.cancelMsg,'</div>',
			'</div>',
		'</div>',
		'<div id="svga-message">',
			'<div id="svga-message-text"></div>',
			'<div class="svga-close">',translation.closeMsg,'</div>',
		'</div>',
		'<div id="svga-gravatar-message">',
			'<div id="svga-gravatar-message-text"></div>',
			'<div id="svga-tryagain">',translation.tryAgainMsg,'</div>',
		'</div>',
		'<div id="svga-ios">',
			'<div id="svga-ios-text">',
				translation.iosMsg,
			'</div>',
			'<div id="svga-ios-image"></div>',
			'<div class="svga-close">',translation.closeMsg,'</div>',
		'</div>',
		'<div id="svga-share-block">',
			'<div id="svga-share-image"></div>',
			'<ul>',
				'<li id="svga-share-twitter"><a href="#"><span id="svga-twitter-icon"></span></a></li>',
				'<li id="svga-share-pinterest"><a href="#"><span id="svga-pinterest-icon"></span></a></li>',
			'</ul>',
			'<div class="svga-close">',translation.closeMsg,'</div>',
		'</div>',
	'</div>'
	].join('');

	if (SVG.supported) {
		$('#svgAvatars').empty().html(markup);
	} else {
		markup = [
		'<div id="svga-container" class="svga-no-touch">',
			'<h3 class="svga-nosvg">',translation.alertSvgSupportError,'</h3>',
		'</div>'
		].join('');
		$('#svgAvatars').empty().html(markup);
	}

	// apply class of chosen color scheme
	switch (options.colorScheme) {
		case 'light': {
			$('#svga-container').addClass('svga-light');
			break;
		}
		case 'dark': {
			$('#svga-container').addClass('svga-dark');
			break;
		}
		case 'custom': {// this custom option is for WordPress Plugin only
			$('#svga-container').addClass('svga-custom');
			break;
		}
		default: {
			$('#svga-container').addClass('svga-light');
			break;
		}
	}

	// exit from the script if SVG is not supported
	if (!SVG.supported) {
		return;
	}

	// setting HTML5 canvas attrs for converting from SVG to PNG
	$('#svga-canvas').attr({'width': 200, 'height': 200});

	// hiding unwanted options
	if (options.hideSaveButton) {
		$('#svga-saveavatar').remove();
	}
	if (options.hideShareButton) {
		$('#svga-shareavatar').remove();
	}
	if (options.hideSvgDownloadButton && options.hidePngFirstDownloadButton && options.hidePngSecondDownloadButton) {
		$('#svga-downloadavatar').remove();
	} else {
		if (options.hidePngFirstDownloadButton) {
			$('#svga-png-one').remove();
		}
		if (options.hidePngSecondDownloadButton) {
			$('#svga-png-two').remove();
		}
		if (options.hideSvgDownloadButton) {
			$('#svga-svgfile').remove();
		}
	}
	if (options.hideGravatar) {
		$('#svga-gravataravatar').remove();
	}

	// hiding unwanted share services
	if (!options.twitter) {
		$('#svga-share-twitter').remove();
	}
	if (!options.pinterest) {
		$('#svga-share-pinterest').remove();
	}
	if (!options.twitter && !options.pinterest) {
		$('#svga-shareavatar').remove();
	}

	// iOS Safari doesn't allow to force download any files, so it's not possible to save an SVG file.
	// Also only one PNG dimensions will be available for download when '#svga-downloadavatar' is taped.
	if (iOS) {
		$('#svga-container').removeClass('svga-no-touch');
		$('#svga-downloadavatar > ul').remove();
	}
	// hide SVG download on Android devices
	if (Android && options.hideSvgDownloadOnAndroid) {
		$('#svga-svgfile').remove();
	}
	// add special class for Opera browser
	if (Opera) {
		$('#svga-container').addClass('svga-opera');
	}
	// add special class and download option for Win8 phones and tablets
	// Win phones doesn't correctly work with hover event and 
	// doesn't allow to swipe the content (colors, graphic parts, etc.) in mobile view (breakpoint 481px)
	if (Win8tablet) {
		$('#svga-container').addClass('svga-win8tablet');
		$('#svga-downloadavatar > ul').remove();
	}

	// gender icons on a start screen
	$('#svga-start-boys').append('<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="80px" height="80px" viewBox="0 0 80 80"><path class="svga-icon-boy" d="M73.22,72.6c-1.05-6.99-8.49-9.28-14.35-10.97c-3.07-0.89-6.98-1.58-9.48-3.72C47.3,56.13,47.5,50.9,49,49.8c3.27-2.39,5.26-7.51,6.14-11.25c0.25-1.07-0.36-0.46,0.81-0.64c0.71-0.11,2.13-2.3,2.64-3.21c1.02-1.83,2.41-4.85,2.42-8.02c0.01-2.23-1.09-2.51-2.41-2.29c-0.43,0.07-0.93,0.21-0.93,0.21c1.42-1.84,1.71-8.22-0.67-13.4C53.56,3.71,44.38,2,40,2c-2.35,0-7.61,1.63-7.81,3.31c-3.37,0.19-7.7,2.55-9.2,5.89c-2.41,5.38-1.48,11.4-0.68,13.4c0,0-0.5-0.14-0.93-0.21c-1.32-0.21-2.42,0.07-2.41,2.29c0.01,3.16,1.41,6.19,2.43,8.02c0.51,0.91,1.93,3.1,2.64,3.21c1.17,0.18,0.56-0.42,0.81,0.64c0.89,3.74,3.09,9.03,6.14,11.25c1.69,2.04,1.7,6.33-0.39,8.11c-2.84,2.43-7.37,3.07-10.84,4.12c-5.86,1.77-13.29,4.9-13.27,12.25C6.51,76.73,7.7,78,10.13,78h59.74c2.43,0,3.68-1.27,3.63-3.72C73.5,74.28,73.4,73.81,73.22,72.6C72.63,68.73,73.4,73.81,73.22,72.6z"/></svg>');
	$('#svga-start-girls').append('<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="80px" height="80px" viewBox="0 0 80 80"><path class="svga-icon-girl" d="M71,74.56c-0.08-5.44-4.21-7.67-8.81-9.63c-3.65-1.55-12.07-2.23-13.83-6.23c-0.83-1.89-0.22-3.15,0.11-5.85c6.95,0.23,17.72-5.29,19.02-10.4c0.65-2.55-2.79-4.44-4.22-6.01c-1.86-2.04-3.3-4.5-4.29-7.07c-2.17-5.61-0.2-11.18-2.14-16.7C54.18,5.14,46.53,2.01,40,2.01l0,0c0,0,0,0,0,0s0,0,0,0l0,0c-6.53,0-14.18,3.13-16.83,10.66c-1.94,5.51,0.03,11.09-2.14,16.7c-0.99,2.57-2.44,5.03-4.29,7.07c-1.43,1.58-4.87,3.46-4.22,6.01c1.3,5.1,12.07,10.62,19.02,10.4c0.34,2.7,0.94,3.95,0.11,5.85c-1.75,3.99-10.18,4.67-13.83,6.23c-4.6,1.96-8.74,4.2-8.81,9.63c-0.04,2.79-0.04,3.43,3.49,3.43H67.5C71.04,77.99,71.04,77.35,71,74.56z"/></svg>');

	// gender is choosen
	$('#svga-start-boys').on('click', function() {
		$('#svga-gender').hide();
		$.ajax({
			url: options.pathToFolder + 'json/svgavatars-male-data.json?ver=' + options.version,
			dataType: 'json',
			cache: true,
			global: false
		}).done(function(jsonData) {
			svgAvatars('boys', jsonData);
		}).fail(function() {
			$('#svga-message-text').html(translation.alertJsonError).addClass('svga-error');
			$('#svga-loader').hide();
			$('.svga-close').hide();
			$('#svga-work-overlay').fadeIn('fast');
			$('#svga-message').fadeIn('fast');
		});
	});
	$('#svga-start-girls').on('click', function() {
		$('#svga-gender').hide();
		$.ajax({
			url: options.pathToFolder + 'json/svgavatars-female-data.json?ver=' + options.version,
			dataType: 'json',
			cache: true,
			global: false
		}).done(function(jsonData) {
			svgAvatars('girls', jsonData);
		}).fail(function() {
			$('#svga-message-text').html(translation.alertJsonError).addClass('svga-error');
			$('#svga-loader').hide();
			$('.svga-close').hide();
			$('#svga-work-overlay').fadeIn('fast');
			$('#svga-message').fadeIn('fast');
		});
	});

	// if only one gender is left, skip starting screen
	if (options.showGender === 'boysonly') {
		$('#svga-start-boys').trigger('click');
	} else if (options.showGender === 'girlsonly') {
		$('#svga-start-girls').trigger('click');
	}

	// the main function for creating avatars
	function svgAvatars(gender, jsonData) {
		var colorsStorage;
		if (gender === 'boys' ) {
			colorsStorage = {
				backs:'#ecf0f1',
				humanbody:'#f0c7b1',
				clothes:'#386e77',
				hair:'#2a232b',
				ears:'#f0c7b1',
				faceshape:'#f0c7b1',
				chinshadow:'#f0c7b1',
				facehighlight:'#f0c7b1',
				eyebrows:'#2a232b',
				eyesback:'#000000',
				eyesfront:'#000000',
				eyesiris:'#4e60a3',
				glasses:'#26120B',
				mustache:'#2a232b',
				beard:'#2a232b',
				mouth:'#da7c87'};
			$('#svga-container').addClass('svga-boys');
		} else if (gender === 'girls') {
			colorsStorage = {
				backs:'#ecf0f1',
				humanbody:'#F3D4CF',
				clothes:'#09aac5',
				hair:'#2a232b',
				ears:'#F3D4CF',
				faceshape:'#F3D4CF',
				chinshadow:'#F3D4CF',
				facehighlight:'#F3D4CF',
				eyebrows:'#2a232b',
				eyesback:'#000000',
				eyesfront:'#000000',
				eyesiris:'#4e60a3',
				glasses:'#26120B',
				mouth:'#f771a9'};
			$('#svga-container').addClass('svga-girls');
		} else {
			return;
		}

		//variables init
		var blockNames = ['face','eyes','hair','clothes','backs'],
			bodyZoneList;
		if (gender === 'boys') {
			bodyZoneList = ['backs','faceshape','chinshadow','facehighlight','humanbody','clothes','hair','ears','eyebrows','eyesback','eyesiris','eyesfront','glasses','mouth','mustache','beard','nose'];
		} else {
			bodyZoneList = ['backs','faceshape','chinshadow','facehighlight','humanbody','clothes','hair','ears','eyebrows','eyesback','eyesiris','eyesfront','glasses','mouth','nose'];
		}
		var elementsStorage = {},
			iconsSvgPathData = {
				up:'M8.425,3.176c-0.235-0.234-0.614-0.234-0.849,0L2.769,7.984c-0.235,0.234-0.235,0.613,0,0.85l0.565,0.564c0.234,0.235,0.614,0.235,0.849,0L7,6.58V12.4C7,12.732,7.268,13,7.6,13H8.4C8.731,13,9,12.73,9,12.4V6.58l2.818,2.819c0.234,0.234,0.614,0.234,0.849,0l0.565-0.566c0.234-0.234,0.234-0.613,0-0.848L8.425,3.176z',
				down:'M7.575,12.824c0.235,0.234,0.614,0.234,0.849,0l4.808-4.809c0.235-0.234,0.235-0.613,0-0.85l-0.565-0.564c-0.234-0.235-0.614-0.235-0.849,0L9,9.42V3.6C9,3.268,8.732,3,8.4,3H7.6C7.269,3,7,3.27,7,3.6v5.82L4.182,6.602c-0.234-0.234-0.615-0.234-0.849,0L2.768,7.168c-0.234,0.234-0.234,0.613,0,0.848L7.575,12.824z',
				left:'M3.176,7.575c-0.234,0.235-0.234,0.614,0,0.849l4.809,4.808c0.234,0.235,0.613,0.235,0.85,0l0.564-0.565c0.235-0.234,0.235-0.614,0-0.849L6.58,9H12.4C12.732,9,13,8.732,13,8.4V7.6C13,7.269,12.73,7,12.4,7H6.58l2.819-2.818c0.234-0.234,0.234-0.615,0-0.849L8.832,2.768c-0.234-0.234-0.613-0.234-0.848,0L3.176,7.575z',
				right:'M12.824,8.425c0.234-0.235,0.234-0.614,0-0.849L8.016,2.769c-0.234-0.235-0.613-0.235-0.85,0L6.602,3.334c-0.235,0.234-0.235,0.614,0,0.849L9.42,7H3.6C3.268,7,3,7.268,3,7.6V8.4C3,8.731,3.27,9,3.6,9h5.82l-2.818,2.818c-0.234,0.234-0.234,0.614,0,0.849l0.566,0.565c0.234,0.234,0.613,0.234,0.848,0L12.824,8.425z',
				tightly:'M12.594,8l3.241,3.205c0.22,0.216,0.22,0.567,0,0.783l-0.858,0.85c-0.219,0.217-0.575,0.217-0.795,0L9.683,8.393c-0.221-0.216-0.221-0.568,0-0.785l4.499-4.445c0.22-0.217,0.576-0.217,0.795,0l0.858,0.849c0.22,0.217,0.22,0.568,0,0.785L12.594,8z M0.164,11.205c-0.219,0.216-0.219,0.567,0,0.783l0.859,0.85c0.221,0.217,0.575,0.217,0.795,0l4.499-4.445c0.22-0.217,0.22-0.568,0-0.785L1.818,3.163c-0.221-0.217-0.576-0.217-0.795,0L0.164,4.012c-0.219,0.217-0.219,0.568,0,0.785L3.405,8L0.164,11.205z',
				wider:'M3.039,8.001l3.203,3.203c0.217,0.216,0.217,0.567,0,0.784l-0.85,0.85c-0.217,0.217-0.567,0.217-0.785,0L0.163,8.393c-0.217-0.216-0.217-0.568,0-0.785l4.444-4.445c0.218-0.217,0.568-0.217,0.785,0l0.85,0.849c0.217,0.217,0.217,0.568,0,0.785L3.039,8.001z M9.758,11.204c-0.217,0.216-0.217,0.567,0,0.784l0.85,0.849c0.217,0.218,0.568,0.218,0.785,0l4.445-4.444c0.217-0.218,0.217-0.568,0-0.785l-4.445-4.445c-0.219-0.217-0.568-0.217-0.785,0l-0.85,0.849c-0.217,0.217-0.217,0.568,0,0.785l3.203,3.204L9.758,11.204z',
				scaledown:'M13.974,12.904l-2.716-2.715c-0.222-0.223-0.582-0.223-0.804,0L8.82,8.541c0.708-0.799,1.229-1.865,1.229-3.017C10.049,3.026,8.023,1,5.524,1S1,3.026,1,5.524c0,2.499,2.025,4.524,4.524,4.524c0.899,0,1.791-0.307,2.496-0.758l1.63,1.701c-0.223,0.223-0.223,0.582,0,0.805l2.716,2.717c0.222,0.221,0.582,0.221,0.804,0l0.804-0.805C14.196,13.486,14.196,13.127,13.974,12.904z M5.485,8.461c-1.662,0-3.009-1.378-3.009-3.041s1.347-3.009,3.009-3.009c1.661,0,3.071,1.347,3.071,3.009S7.146,8.461,5.485,8.461z M7.5,6h-4V5h4V6z',
				scaleup:'M13.974,12.904l-2.716-2.715c-0.222-0.223-0.582-0.223-0.804,0L8.82,8.541c0.708-0.799,1.229-1.865,1.229-3.016C10.049,3.026,8.023,1,5.524,1S1,3.026,1,5.524c0,2.499,2.025,4.524,4.524,4.524c0.899,0,1.792-0.307,2.496-0.758l1.63,1.701c-0.223,0.223-0.223,0.582,0,0.805l2.716,2.717c0.222,0.221,0.582,0.221,0.804,0l0.804-0.805C14.196,13.486,14.196,13.127,13.974,12.904z M5.485,8.46c-1.662,0-3.009-1.378-3.009-3.04c0-1.662,1.347-3.009,3.009-3.009c1.661,0,3.071,1.347,3.071,3.009C8.557,7.082,7.146,8.46,5.485,8.46z M7.5,6H6v1.5H5V6H3.5V5H5V3.5h1V5h1.5V6z',
				eb1:'M5.453,8.316C5.129,7.499,4.146,6.352,1.492,5.521C1.087,5.393,0.868,4.982,1.003,4.602c0.135-0.379,0.572-0.586,0.98-0.458c2.996,0.938,4.917,2.505,5.015,4.088c0.026,0.4-0.3,0.767-0.728,0.767C5.875,8.998,5.531,8.514,5.453,8.316z M9.021,8.313C8.66,8.077,8.593,7.626,8.841,7.301c0.983-1.288,3.5-1.651,6.569-0.948c0.415,0.095,0.669,0.489,0.567,0.877c-0.102,0.39-0.518,0.628-0.937,0.533c-2.718-0.623-4.315-0.188-4.939,0.282C9.908,8.191,9.5,8.625,9.021,8.313z',
				eb2:'M9.729,8.998c-0.428,0-0.753-0.366-0.728-0.767c0.098-1.583,2.02-3.149,5.016-4.088c0.407-0.128,0.845,0.079,0.979,0.458c0.136,0.38-0.083,0.792-0.488,0.919c-2.654,0.831-3.638,1.978-3.961,2.796C10.469,8.514,10.125,8.998,9.729,8.998z M5.898,8.045C5.274,7.576,3.677,7.141,0.959,7.764C0.54,7.858,0.124,7.62,0.022,7.23C-0.079,6.842,0.175,6.448,0.59,6.353c3.069-0.703,5.586-0.34,6.569,0.948c0.248,0.325,0.181,0.776-0.18,1.012C6.5,8.625,6.092,8.191,5.898,8.045z',
				eb3:'M5.453,8.316C5.129,7.499,4.146,6.352,1.492,5.521C1.087,5.393,0.868,4.982,1.003,4.602c0.135-0.379,0.572-0.586,0.98-0.458c2.996,0.938,4.917,2.505,5.015,4.088c0.026,0.4-0.3,0.767-0.728,0.767C5.875,8.998,5.531,8.514,5.453,8.316z M9.729,8.998c-0.428,0-0.753-0.366-0.728-0.767c0.098-1.583,2.02-3.149,5.016-4.088c0.407-0.128,0.845,0.079,0.979,0.458c0.136,0.38-0.083,0.792-0.488,0.919c-2.654,0.831-3.638,1.978-3.961,2.796C10.469,8.514,10.125,8.998,9.729,8.998z',
				eb4:'M5.728,6.662C4.873,6.458,3.369,6.605,1.166,8.303C0.829,8.562,0.367,8.506,0.133,8.176C-0.1,7.848-0.019,7.371,0.32,7.111C2.807,5.195,5.192,4.52,6.545,5.348c0.343,0.208,0.456,0.685,0.211,1.036C6.528,6.708,5.935,6.711,5.728,6.662z M9.244,6.383C8.999,6.033,9.111,5.556,9.455,5.348c1.353-0.828,3.737-0.152,6.225,1.764c0.339,0.26,0.421,0.737,0.187,1.065c-0.233,0.33-0.695,0.386-1.032,0.127c-2.203-1.698-3.707-1.845-4.563-1.641C10.065,6.712,9.471,6.708,9.244,6.383z',
				ebcancel:'M11.294,3.091l1.617,1.615c0.119,0.12,0.119,0.315,0,0.436L10.052,8l2.858,2.858c0.12,0.12,0.12,0.314,0.001,0.435l-1.617,1.618c-0.12,0.119-0.314,0.119-0.435-0.001l-2.858-2.859l-2.86,2.86c-0.12,0.119-0.314,0.119-0.435-0.001L3.09,11.293c-0.12-0.12-0.12-0.314,0-0.435L5.949,8L3.09,5.142c-0.12-0.12-0.12-0.315,0-0.436l1.616-1.615c0.12-0.121,0.314-0.121,0.435,0l2.86,2.858l2.858-2.858C10.979,2.97,11.174,2.97,11.294,3.091z',
				tiltleft:'M13.399,10h-0.851c-0.165,0-0.299-0.135-0.31-0.3C12.085,7.494,10.244,5.75,8,5.75c-1.393,0-2.627,0.67-3.402,1.705l1.335,1.333C6.049,8.904,6.01,9,5.845,9H2.3C2.135,9,2,8.865,2,8.699V5.156C2,4.99,2.095,4.951,2.212,5.068l1.354,1.354C4.611,5.129,6.208,4.3,8,4.3c3.047,0,5.535,2.393,5.691,5.4C13.7,9.865,13.564,10,13.399,10z',
				tiltright:'M2.309,9.7C2.465,6.693,4.953,4.3,8,4.3c1.792,0,3.389,0.829,4.434,2.122l1.354-1.354C13.905,4.951,14,4.99,14,5.156v3.543C14,8.865,13.865,9,13.7,9h-3.545C9.99,9,9.951,8.904,10.067,8.787l1.335-1.333C10.627,6.42,9.393,5.75,8,5.75c-2.244,0-4.085,1.744-4.239,3.95C3.75,9.865,3.616,10,3.451,10h-0.85C2.435,10,2.3,9.865,2.309,9.7z',
				back:'M1.17,6.438l4.406,4.428C5.811,11.1,6,11.021,6,10.689V8c0,0,8,0,8,7C14,3,6,4,6,4V1.311c0-0.332-0.189-0.41-0.424-0.176L1.17,5.563C0.943,5.805,0.943,6.197,1.17,6.438z',
				forward:'M14.829,6.438l-4.405,4.428C10.189,11.1,10,11.021,10,10.689V8c0,0-8,0-8,7C2,3,10,4,10,4V1.311c0-0.332,0.189-0.41,0.424-0.176l4.405,4.429C15.057,5.805,15.057,6.197,14.829,6.438z',
				random:'M24.311,14.514c-0.681,0-1.225,0.553-1.318,1.227c-0.599,4.243-4.245,7.512-8.655,7.512c-2.86,0-6.168-2.057-7.711-4.112l-3.655-4.412c-0.196-0.205-0.547-0.292-0.74-0.131C2.107,14.702,2,14.833,2,14.974v9.503c0,0.339,0.194,0.42,0.436,0.181l2.782-2.782c2.149,2.658,5.436,4.358,9.119,4.358c6.056,0,11.04-4.594,11.657-10.489c0.072-0.678-0.488-1.231-1.169-1.231H24.311z M3.689,13.486c0.681,0,1.225-0.553,1.319-1.227c0.598-4.243,4.245-7.512,8.654-7.512c2.861,0,5.816,1.542,7.71,4.112l3.655,4.412c0.195,0.205,0.547,0.293,0.739,0.13C25.893,13.299,26,13.167,26,13.026V3.522c0-0.339-0.195-0.419-0.437-0.181l-2.782,2.784c-2.149-2.659-5.435-4.36-9.119-4.36c-6.056,0-11.04,4.595-11.656,10.49c-0.072,0.678,0.488,1.231,1.169,1.231H3.689z M15.277,16.982h-1.896l-0.006-0.231c-0.026-1.809,1.087-2.446,2.282-3.581c1.224-1.162-0.229-2.5-1.542-2.339c-0.789,0.097-1.337,0.646-1.574,1.385c-0.166,0.517-0.158,0.653-0.158,0.653l-1.744-0.545c0.051-0.838,0.446-1.583,1.071-2.137c1.202-1.06,3.252-1.16,4.651-0.442c1.418,0.727,2.229,2.522,1.196,3.913C16.638,14.899,15.247,15.266,15.277,16.982z M13.382,19.719v-1.977h1.974v1.977H13.382z',
				reset:'M5.515,5.515c-4.686,4.686-4.686,12.284,0,16.971c4.687,4.687,12.284,4.686,16.971,0c4.687-4.687,4.687-12.284,0-16.971C17.799,0.829,10.201,0.828,5.515,5.515z M6.929,6.929C10.595,3.263,16.4,3.04,20.328,6.258L6.257,20.328C3.04,16.4,3.263,10.595,6.929,6.929z M21.071,21.071c-3.667,3.666-9.471,3.89-13.4,0.671l14.071-14.07C24.961,11.601,24.737,17.405,21.071,21.071z',
				save:'M25.64,7.142l-4.761-4.779C20.679,2.162,20.282,2,20,2H3.026C2.458,2,2,2.459,2,3.027v21.946C2,25.539,2.458,26,3.026,26h21.947C25.541,26,26,25.539,26,24.974V8.02C26,7.736,25.839,7.341,25.64,7.142z M14,4v5h-4V4H14z M20,24H8v-8h12V24z M24,24h-2v-8.973C22,14.461,21.541,14,20.969,14H7.027C6.458,14,6,14.461,6,15.027V24H4V4h2v4.97C6,9.538,6.458,10,7.027,10h7.862C15.456,10,16,9.538,16,8.97V4h3.146c0.281,0,0.674,0.162,0.873,0.363l3.623,3.257C23.838,7.817,24,8.212,24,8.495V24z M19,18H9v-1h10V18z M19,20H9v-1h10V20z M19,22H9v-1h10V22z',
				download:'M28,24.8c0,0.663-0.537,1.2-1.2,1.2H1.2C0.537,26,0,25.463,0,24.8v-4.2C0,20.293,0.297,20,0.6,20H3.4C3.703,20,4,20.293,4,20.6V22h20v-1.4c0-0.307,0.299-0.6,0.601-0.6h2.8c0.302,0,0.6,0.293,0.6,0.6V24.8z M14.873,19.658l8.857-8.811C24.199,10.379,24.043,10,23.379,10H18V3.2C18,2.538,17.463,2,16.801,2H11.2C10.537,2,10,2.538,10,3.2V10H4.621c-0.662,0-0.82,0.379-0.352,0.848l8.855,8.811C13.607,20.113,14.391,20.113,14.873,19.658z',
				gravatar:'M16.39,7.2c0,0.19,0,5.34,0,5.53c-0.03,3.21-4.73,3.26-4.76,0.04c-0.01-1.37-0.01-7.55,0-8.25c0.01-1.54,1.01-2.53,2.54-2.52c5.75,0.05,10.74,4.38,11.62,10.07c1.01,6.54-3.25,12.55-9.72,13.74c-10.9,2-18.81-11.34-10.64-20.16c2.23-2.4,5.84,0.85,3.56,3.24C4.1,14,8.12,21.58,14.54,21.13C22.24,20.58,23.72,9.79,16.39,7.2z',
				share:'M21.32,19.51l-3.7-2.61c0.66-1.59,0.46-3.4-0.58-4.83l3.01-3.4C22.39,9.74,25,8.03,25,5.5C25,3.57,23.43,2,21.5,2c-2.74,0-4.43,3.03-2.95,5.37l-2.98,3.36c-1.51-0.91-3.33-1.01-5-0.07L7.62,7.44C7.85,7.01,8,6.52,8,6c0-3.96-6-3.97-6,0c0,2.14,2.17,3.59,4.15,2.77l2.91,3.19c-1.41,1.82-1.41,4.25-0.02,6.07l-2.8,3.1C4.57,20.6,3,21.87,3,23.5C3,24.88,4.12,26,5.5,26c1.8,0,3.06-1.87,2.25-3.57l2.8-3.1c2.08,1.18,4.46,0.72,5.97-0.79l3.63,2.56C18.74,25.57,26,26.53,26,22C26,19.58,23.29,18.18,21.32,19.51z',
				twitter:'M27 2H5C3.34 2 2 3.34 2 5v22c0 1.66 1.34 3 3 3h22c1.66 0 3-1.34 3-3V5C30 3.34 28.66 2 27 2zM24.75 11.57c0.26 6.54-4.58 13.43-12.83 13.43 -3.9 0-6.55-1.75-6.92-2.03 3.58 0.47 6.14-1.37 6.68-1.87 -2.1 0.04-3.84-1.7-4.22-3.13 1.02 0.24 2.04-0.08 2.04-0.08 -2.49-0.5-3.68-2.79-3.62-4.48 0.77 0.4 1.32 0.5 2.04 0.56 -1.74-1.17-2.74-3.73-1.4-6.03 3.69 4.4 8.37 4.67 9.3 4.72 -0.86-4.93 4.74-7.25 7.69-4.12 1.03-0.2 1.99-0.58 2.86-1.09 -0.34 1.05-1.05 1.94-1.98 2.5C25.32 9.84 26.19 9.6 27 9.24 26.39 10.14 25.63 10.94 24.75 11.57z',
				pinterest:'M27 2H5C3.34 2 2 3.34 2 5v22c0 1.66 1.34 3 3 3h22c1.66 0 3-1.34 3-3V5C30 3.34 28.66 2 27 2zM17.36 20.23c-1.28-0.1-1.81-0.73-2.82-1.34C14 21.78 13.32 24.55 11.33 26c-0.62-4.37 0.9-7.65 1.61-11.13 -1.2-2.03 0.15-6.1 2.68-5.1 3.12 1.24-2.71 7.53 1.21 8.32 4.09 0.82 5.75-7.09 3.22-9.66 -3.66-3.71-10.66-0.08-9.8 5.23 0.21 1.3 1.55 1.69 0.54 3.49C5.64 16 7.18 5.94 14.95 5.07 27.93 3.62 25.78 20.89 17.36 20.23L17.36 20.23z'
			},
			controlNames = ['up','down','left','right','tightly','wider','scaledown','scaleup','eb1','eb2','eb3','eb4','ebcancel'],
			globalControlNames = ['up','down','left','right','scaledown','scaleup','tiltleft','tiltright'],
			menuNames = ['random','reset','save','share','gravatar','download'],
			shareNames = ['twitter','pinterest'],
			shareIconsColors = ['#19A6CA','#CB2028'],
			bodyZonesStorage = {
				backs:'backs',
				face:'faceshape',
				eyes:'eyesfront',
				hair:'hair',
				clothes:'clothes'
			},
			changesCounter = 1,
			onCanvas = false,
			bodyZone = 'faceshape',
			action,
			afterRandom = false;

		// creating svg groups in SVG canvas
		var rect, text;
		function initGroups() {
			var draw, gr, subGr, headGroup;
			function addControlsMove(group) {
				var names = ['updown', 'leftright', 'rotate'];
				for (var i = 0; i < names.length; i++) {
					group.data(names[i], 0, true);
				}
				group.attr('transform', 'matrix(1,0,0,1,0,0)');
			}
			function addControlsScale(group) {
				var names = ['scaleX', 'scaleY'];
				for (var i = 0; i < names.length; i++) {
					group.data(names[i], 1, true);
				}
				group.attr('transform', 'matrix(1,0,0,1,0,0)');
			}

			draw = SVG('svga-svgmain').attr({id:'svga-svgcanvas', width:null, height:null, 'class':'svga-svg', viewBox:'0 0 200 200', preserveAspectRatio:'xMinYMin meet'});
			draw = draw.group().attr('id', 'svga-group-wrapper');

			// backgrounds
			draw.group().attr('id', 'svga-group-backs-single');

			// body without background
			draw = draw.group().attr('id', 'svga-group-humanwrap-move');
			addControlsMove(draw);
			draw = draw.group().attr('id', 'svga-group-humanwrap');
			addControlsScale(draw);

			// back hair
			gr = draw.group().attr('id', 'svga-group-hair-back-move');
			addControlsMove(gr);
			gr = gr.group().attr('id', 'svga-group-hair-back');
			addControlsScale(gr);

			// body, chin's shadow, and clothes
			draw.group().attr('id', 'svga-group-humanbody-single');
			draw.group().attr('id', 'svga-group-chinshadow-single');
			draw.group().attr('id', 'svga-group-clothes-single');

			// main head group
			headGroup = draw.group().attr('id', 'svga-group-head');
			addControlsMove(headGroup);

			// ears
			gr = draw.group().attr('id', 'svga-group-ears-left-move');
			addControlsMove(gr);
			headGroup.add(gr);
			gr = gr.group().attr('id', 'svga-group-ears-left');
			addControlsScale(gr);
			gr = draw.group().attr('id', 'svga-group-ears-right-move');
			addControlsMove(gr);
			headGroup.add(gr);
			gr = gr.group().attr('id', 'svga-group-ears-right');
			addControlsScale(gr);

			// face shape
			gr = draw.group().attr('id', 'svga-group-faceshape-wrap');
			addControlsScale(gr);
			headGroup.add(gr);
			gr.group().attr('id', 'svga-group-faceshape-single');

			// mouth
			gr = draw.group().attr('id', 'svga-group-mouth-single-move');
			addControlsMove(gr);
			headGroup.add(gr);
			gr = gr.group().attr('id', 'svga-group-mouth-single');
			addControlsScale(gr);

			// left eye
			gr = draw.group().attr('id', 'svga-group-eyes-left-move');
			addControlsMove(gr);
			headGroup.add(gr);
			gr = gr.group().attr('id', 'svga-group-eyes-left');
			addControlsScale(gr);
			gr.group().attr('id', 'svga-group-eyesback-left');
			subGr = gr.group().attr('id', 'svga-group-eyesiriswrapper-left');
			subGr = subGr.group().attr('id', 'svga-group-eyesiriscontrol-left');
			addControlsMove(subGr);
			subGr = subGr.group().attr('id', 'svga-group-eyesiris-left');
			addControlsScale(subGr);
			gr.group().attr('id', 'svga-group-eyesfront-left');

			// right eye
			gr = draw.group().attr('id', 'svga-group-eyes-right-move');
			addControlsMove(gr);
			headGroup.add(gr);
			gr = gr.group().attr('id', 'svga-group-eyes-right');
			addControlsScale(gr);
			gr.group().attr('id', 'svga-group-eyesback-right');
			subGr = gr.group().attr('id', 'svga-group-eyesiriswrapper-right');
			subGr = subGr.group().attr('id', 'svga-group-eyesiriscontrol-right');
			addControlsMove(subGr);
			subGr = subGr.group().attr('id', 'svga-group-eyesiris-right');
			addControlsScale(subGr);
			gr.group().attr('id', 'svga-group-eyesfront-right');

			// face highlight
			headGroup.group().attr('id', 'svga-group-facehighlight-single');

			// left eyebrow
			gr = headGroup.group().attr('id', 'svga-group-eyebrows-left-move');
			addControlsMove(gr);
			gr = gr.group().attr('id', 'svga-group-eyebrows-left-rotate');
			addControlsMove(gr);
			gr = gr.group().attr('id', 'svga-group-eyebrows-left');
			addControlsScale(gr);

			// right eyebrow
			gr = headGroup.group().attr('id', 'svga-group-eyebrows-right-move');
			addControlsMove(gr);
			gr = gr.group().attr('id', 'svga-group-eyebrows-right-rotate');
			addControlsMove(gr);
			gr = gr.group().attr('id', 'svga-group-eyebrows-right');
			addControlsScale(gr);

			// nose
			gr = headGroup.group().attr('id', 'svga-group-nose-single-move');
			addControlsMove(gr);
			gr = gr.group().attr('id', 'svga-group-nose-single');
			addControlsScale(gr);

			if (gender === 'boys') {
				// beard
				gr = headGroup.group().attr('id', 'svga-group-beardwrap');
				addControlsScale(gr);
				gr = gr.group().attr('id', 'svga-group-beard-single-move');
				addControlsMove(gr);
				gr = gr.group().attr('id', 'svga-group-beard-single');
				addControlsScale(gr);

				// mustache
				gr = headGroup.group().attr('id', 'svga-group-mustache-single-move');
				addControlsMove(gr);
				gr = gr.group().attr('id', 'svga-group-mustache-single');
				addControlsScale(gr);
			}

			// front hair
			gr = headGroup.group().attr('id', 'svga-group-hair-front');
			addControlsScale(gr);

			// glasses
			gr = headGroup.group().attr('id', 'svga-group-glasses-single-move');
			addControlsMove(gr);
			gr = gr.group().attr('id', 'svga-group-glasses-single');
			addControlsScale(gr);

			if (options.shareCredit) {
				draw = SVG.get('svga-group-wrapper');
				rect = draw.rect(200, 15).move(0, 185).fill('#ecf0f1').opacity(0.5);
				text = draw.text(options.shareCredit)
					.font({
						'font-family': "Roboto, Helvetica, Arial, sans-serif",
						size:   '10px',
						anchor: 'start',
						weight: 400})
					.fill('#333')
					.center( 100, 192 );
				rect.hide();
				text.hide();
			}
		} // end initGroups()
		initGroups();

		// creating blocks for groups of graphic parts (shapes)
		for (var i = 0; i < blockNames.length; i++) {
			$('#svga-blocks').append('<div id="svga-blocks-'  + blockNames[i] + '" class="svga-blocks" data-blockname="' + blockNames[i] + '">' +  translation.blockTitles[blockNames[i]] + '</div>');
		}
		$('.svga-blocks:last').addClass('svga-last');
		$('#svga-blocks-backs').data('bodyzones', 'backs');
		$('#svga-blocks-face').data('bodyzones', 'faceshape,nose,mouth,ears');
		$('#svga-blocks-eyes').data('bodyzones', 'eyesfront,eyesiris,eyebrows,glasses');
		if (gender === 'boys') {
			$('#svga-blocks-hair').data('bodyzones', 'hair,mustache,beard');
		} else {
			$('#svga-blocks-hair').data('bodyzones', 'hair');
		}
		$('#svga-blocks-clothes').data('bodyzones', 'clothes');

		// creating zones: shapes, mouths, eyes, clothes, etc.
		for (var currentZone in jsonData) {
			if (jsonData.hasOwnProperty(currentZone)) {
				$('#svga-bodyzones').append('<div id="svga-bodyzones-' + currentZone + '" class="svga-bodyzones" data-bodyzone="' + currentZone + '" data-controls="' + jsonData[currentZone].controls + '" data-block="' + jsonData[currentZone].block + '">' + translation.bodyZoneTitles[currentZone] + '</div>');
				$('#svga-bodyzones-' + currentZone).hide();
			}
		}

		// creating controls (left, right, scale, etc.)
		for (var cont in iconsSvgPathData) {
			if (iconsSvgPathData.hasOwnProperty(cont)) {
				if (controlNames.indexOf(cont) > -1) {
					$('#svga-controls').append('<div id="svga-controls-' + cont + '" class="svga-controls"><svg class="svga-control-icon" xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 16 16" preserveAspectRatio="xMinYMin meet"><path class="svga-control-icon-path" d="' + iconsSvgPathData[cont] + '"/></svg></div>');
					$('#svga-controls-' + cont).hide();
				}
			}
		}

		// creating global controls (left, right, scale, etc.)
		for (cont in iconsSvgPathData) {
			if (iconsSvgPathData.hasOwnProperty(cont)) {
				if (globalControlNames.indexOf(cont) > -1) {
					$('#svga-glob-controls').append('<div id="svga-glob-controls-' + cont + '" class="svga-glob-controls"><svg class="svga-control-icon" xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 16 16" preserveAspectRatio="xMinYMin meet"><path class="svga-control-icon-path" d="' + iconsSvgPathData[cont] + '"/></svg></div>');
				}
			}
		}

		// creating icons in menu (random, reset, save, download)
		for (i = 0; i < menuNames.length; i++) {
			$('#svga-' + menuNames[i] + 'avatar > div').append('<svg class="svga-menu-icon" xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 28 28" preserveAspectRatio="xMinYMin meet"><path class="svga-menu-icon-path" d="' + iconsSvgPathData[menuNames[i]] + '"/></svg>');
		}

		//creating socials icons
		for (i = 0; i < shareNames.length; i++) {
			$('#svga-'+shareNames[i] + '-icon').append('<svg class="svga-share-icons" xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 32 32" preserveAspectRatio="xMinYMin meet"><path class="svga-share-icons-path" fill="' + shareIconsColors[i] + '" d="' + iconsSvgPathData[shareNames[i]] + '"/></svg>');
		}

		var shapesCounter,
			shapeSidePosition,
			currentGroup;
		//drawing all elements thumbnails
		for (currentZone in jsonData) {
			if (jsonData.hasOwnProperty(currentZone)) {
				var currentScaleFactor = jsonData[currentZone].scaleFactor,
					currentColors = jsonData[currentZone].colors;
				$('#svga-elements').append('<div class="svga-elements-wrap" id="svga-elements-' + currentZone + '"></div>');
				for (i = 0; i < jsonData[currentZone].shapes.length; i++) {
					$('#svga-elements-' + currentZone).append('<div class="svga-elements" id="svga-elements-' + currentZone + '-' + i + '" data-zone="' + currentZone + '" data-shape="' + i + '"></div>');
					currentGroup = SVG('svga-elements-' + currentZone + '-' + i)
						.size('100%', '100%')
						.attr({
							id:'svga-svgcanvas-elements-' + currentZone + '-' + i, 
							width: null, 
							height: null, 
							class: 'svga-svg', 
							viewBox: '0 0 200 200',
							preserveAspectRatio: 'xMinYMin meet'
						}).group();
					shapesCounter = i;
					for (shapeSidePosition in jsonData[currentZone].shapes[shapesCounter]) {
						if (jsonData[currentZone].shapes[shapesCounter].hasOwnProperty(shapeSidePosition)) {
							if (shapeSidePosition === 'right' ||
								shapeSidePosition === 'single' ||
								shapeSidePosition === 'back' ||
								shapeSidePosition === 'front') {
								if (jsonData[currentZone].shapes[shapesCounter][shapeSidePosition].length) {
									drawElement(currentGroup, currentZone, shapesCounter, shapeSidePosition);
									var bBox = currentGroup.bbox();
									currentGroup.transform({ 
										scale: currentScaleFactor
									}).transform({ 
										x: -bBox.x * currentScaleFactor + (200 - bBox.width * currentScaleFactor) / 2
									}).transform({ 
										y: -bBox.y * currentScaleFactor + (200 - bBox.height * currentScaleFactor) / 2
									});
									if (currentZone === 'clothes') {currentGroup.move(10, 5);}
									if (currentZone === 'hair' && shapesCounter > 0) {currentGroup.move(0, 20);}
								} else {
									$('#svga-elements-' + currentZone + '-' + i).empty().append('<div></div>').addClass('empty');
								}
							}
						}
					}
				}
				$('#svga-elements-' + currentZone).hide();
				$('#svga-colors').append('<div id="svga-colors-' + currentZone + '" class="svga-colors-set"></div>');
				for (i = 0; i < currentColors.length; i++) {
					$('#svga-colors-' + currentZone).append('<div></div>');
					$('#svga-colors-' + currentZone + ' div:last-child').css('background-color', currentColors[i]);
				}
				$('#svga-colors-' + currentZone).hide();
			}
		}
		$('#svga-custom-color').hide(); 

		// first initial drawing on SVG canvas
		shapesCounter = 0;
		for (i = 0; i < bodyZoneList.length; i++) {
			if (bodyZoneList[i] === 'backs' || bodyZoneList[i] === 'hair') {
				shapesCounter = 1;
			}
			for (shapeSidePosition in jsonData[bodyZoneList[i]].shapes[shapesCounter]) {
				if (jsonData[bodyZoneList[i]].shapes[shapesCounter].hasOwnProperty(shapeSidePosition)) {
					var id = 'svga-group-' + bodyZoneList[i] + '-' + shapeSidePosition;
					$('#' + id).empty();
					currentGroup = SVG.get(id);
					onCanvas = true;
					drawElement(currentGroup, bodyZoneList[i], shapesCounter, shapeSidePosition);
				}
			}
			elementsStorage[bodyZoneList[i]] = shapesCounter;
			shapesCounter = 0;
		}

		// main func for drawing every SVG elements (paths)
		function drawElement(currentGroup, bodyZone, shapesCounter, shapeSidePosition) {
			var currentZoneData = jsonData[bodyZone],
				currentElementData = currentZoneData.shapes[shapesCounter][shapeSidePosition],
				colorZone,
				i,
				currentSvgShape,
				fillType,
				ifGradient,
				currentFill,
				currentStroke,
				strokeType,
				gradient,
				path,
				gradientStop = function(stop) {
					for (var j = 0; j < currentSvgShape.gradientStops.length; j++) {
						var colorZone,
							stopColor = currentSvgShape.gradientStops[j].color,
							currentStopColor,
							s;
						ifGradient = true;
						if (currentSvgShape.fromskin) {
							colorZone = 'faceshape';
						} else {
							colorZone = bodyZone;
						}
						currentStopColor = colorTones(stopColor, colorZone, ifGradient);
						s = stop.at(currentSvgShape.gradientStops[j]);
						s.update({color: currentStopColor});
						s.data('stoptype', stopColor);
					}
				};
			for (i = 0; i < currentElementData.length; i++) {
				currentSvgShape = currentElementData[i];
				path = currentGroup.path(currentSvgShape.path, true);
				path.data('colored', currentSvgShape.colored, true);
				path.data('transparence', currentSvgShape.transparence, true);
				path.data('fillType', currentSvgShape.fill);
				path.data('strokeType', currentSvgShape.stroke);
				if (currentSvgShape.fromskin) {
					path.data('fromskin', currentSvgShape.fromskin, true);
				}
				if (path.data('colored') === true) {
					if (currentSvgShape.fromskin) {
						colorZone = 'faceshape';
					} else {
						colorZone = bodyZone;
					}
					fillType = path.data('fillType');
					ifGradient = false;
					currentFill = colorTones(fillType, colorZone, ifGradient);
					path.attr('fill', currentFill);
					strokeType = path.data('strokeType');
					currentStroke = colorTones(strokeType, colorZone, ifGradient);
					path.attr({
						'stroke': currentStroke,
						'stroke-width': currentSvgShape.strokeWidth,
						'stroke-linecap': currentSvgShape.strokeLinecap,
						'stroke-linejoin': currentSvgShape.strokeLinejoin,
						'stroke-miterlimit': currentSvgShape.strokeMiterlimit
					});
				} else {
					if (currentSvgShape.fill === 'gradient') {
						if (onCanvas) {
							removeElementsByClass('svga-on-canvas-' + bodyZone + '-gradient-' + shapeSidePosition + '-' + i);
						} else {
							var currentGradient = document.getElementById('svga-' + bodyZone + '-gradient-' + shapeSidePosition + '-' + shapesCounter + '-' + i);
							if (currentGradient) {
								removeElement(currentGradient);
							}
						}
						gradient = currentGroup.gradient(currentSvgShape.type, gradientStop);
						if (currentSvgShape.x1) {
							gradient.attr({x1: currentSvgShape.x1});
						}
						if (currentSvgShape.y1) {
							gradient.attr({y1: currentSvgShape.y1});
						}
						if (currentSvgShape.x2) {
							gradient.attr({x2: currentSvgShape.x2});
						}
						if (currentSvgShape.y2) {
							gradient.attr({y2: currentSvgShape.y2});
						}
						if (currentSvgShape.cx) {
							gradient.attr({cx: currentSvgShape.cx});
						}
						if (currentSvgShape.cy) {
							gradient.attr({cy: currentSvgShape.cy});
						}
						if (currentSvgShape.fx) {
							gradient.attr({fx: currentSvgShape.fx});
						}
						if (currentSvgShape.fy) {
							gradient.attr({fy: currentSvgShape.fy});
						}
						if (currentSvgShape.r) {
							gradient.attr({r: currentSvgShape.r});
						}
						if (currentSvgShape.gradientTransform) {
							gradient.attr({gradientTransform: currentSvgShape.gradientTransform});
						}
						if (currentSvgShape.gradientUnits) {
							gradient.attr({gradientUnits: currentSvgShape.gradientUnits});
						}
						if (onCanvas) {
							gradient.attr('class','svga-on-canvas-' + bodyZone + '-gradient-' + shapeSidePosition + '-' + i);
						} else {
							gradient.attr('id','svga-' + bodyZone + '-gradient-' + shapeSidePosition + '-' + shapesCounter + '-' + i);
						}
						path.attr({fill: gradient});
					} else {
						path.attr({fill: currentSvgShape.fill});
						path.attr({
							'stroke': currentSvgShape.stroke,
							'stroke-width': currentSvgShape.strokeWidth,
							'stroke-linecap': currentSvgShape.strokeLinecap,
							'stroke-linejoin': currentSvgShape.strokeLinejoin,
							'stroke-miterlimit': currentSvgShape.strokeMiterlimit
						});
					}
				}
				if (currentSvgShape.opacity) {
					path.attr({opacity: currentSvgShape.opacity});
				}
				if (onCanvas) {
					if (currentSvgShape.id) {
						path.attr('id', currentSvgShape.id + '-' + shapeSidePosition);
					}
					if (bodyZone === 'eyesback') {
						SVG.get('svga-group-eyesiriswrapper-' + shapeSidePosition).transform({x: 0, y: 0});
					}
					if (currentSvgShape.irisx || currentSvgShape.irisy) {
						SVG.get('svga-group-eyesiriswrapper-' + shapeSidePosition).move(currentSvgShape.irisx, currentSvgShape.irisy);
					}
					if (bodyZone === 'hair' && currentSvgShape.hideears) {
						SVG.get('svga-group-ears-left-move').hide();
						SVG.get('svga-group-ears-right-move').hide();
					} else if (bodyZone === 'hair') {
						SVG.get('svga-group-ears-left-move').show();
						SVG.get('svga-group-ears-right-move').show();
					}
				}
				if (!onCanvas && currentSvgShape.hideonthumbs) {
					path.remove();
				}
				if (onCanvas && currentSvgShape.hideoncanvas) {
					path.remove();
				}
			}
		}// end drawElement(...)

		// calculating colors (shadows and highlights) based on main color, options.saturationDelta, and options.brightnessDelta values
		function colorTones (type, bodyZone, ifGradient) {
			var color;
			switch (type) {
				case 'none': {
					color = "none";
					break;
				}
				case 'tone': {
					color = colorsStorage[bodyZone];
					break;
				}
				case 'hl05': {
					color = ShadowHighlight(colorsStorage[bodyZone], -0.5 * options.saturationDelta, 0.5 * options.brightnessDelta);
					break;
				}
				case 'hl1': {
					color = ShadowHighlight(colorsStorage[bodyZone], -options.saturationDelta, options.brightnessDelta);
					break;
				}
				case 'hl2': {
					color = ShadowHighlight(colorsStorage[bodyZone], -2 * options.saturationDelta, 2 * options.brightnessDelta);
					break;
				}
				case 'sd05': {
					color = ShadowHighlight(colorsStorage[bodyZone], 0.5 * options.saturationDelta, -0.5 * options.brightnessDelta);
					break;
				}case 'sd1': {
					color = ShadowHighlight(colorsStorage[bodyZone], options.saturationDelta, -options.brightnessDelta);
					break;
				}
				case 'sd2': {
					color = ShadowHighlight(colorsStorage[bodyZone], 2 * options.saturationDelta, -2 * options.brightnessDelta);
					break;
				}
				case 'sd3': {
					color = ShadowHighlight(colorsStorage[bodyZone], 3 * options.saturationDelta, -3 * options.brightnessDelta);
					break;
				}
				case 'sd35': {
					color = ShadowHighlight(colorsStorage[bodyZone], 3.5 * options.saturationDelta, -3.5 * options.brightnessDelta);
					break;
				}
				default: {
					color = colorsStorage[bodyZone];
					if (ifGradient) {
						color = type;
					}
					break;
				}
			}
			return color;
		}

		// loop for color change
		function multyZoneColor (tempZones, currentColor) {
			var tempZonesLength = tempZones.length,
				i,
				shapesCounter,
				currentGroup,
				colorCalc = function(j, children) {
					var colorZone,
						fillType,
						ifGradient,
						currentFill,
						strokeType,
						currentStroke,
						gradID,
						gradient;
					if (this.data('colored')) {
						if (this.data('fromskin') === true) {
							colorZone = 'faceshape';
						} else {
							colorZone = tempZones[i];
						}
						fillType = this.data('fillType');
						ifGradient = false;
						currentFill = colorTones(fillType, colorZone, ifGradient);
						this.attr('fill', currentFill);
						strokeType = this.data('strokeType');
						currentStroke = colorTones(strokeType, colorZone, ifGradient);
						this.stroke(currentStroke);
					} else {
						if (this.data('fillType') === 'gradient') {
							gradID = this.attr('fill');
							gradID = gradID.replace(/url\(\#/,'').replace(/\)/,'');
							gradient = SVG.get(gradID);
							if (gradient) {
								if (this.data('fromskin') === true) {
										colorZone = 'faceshape';
									} else {
										colorZone = tempZones[i];
									}
								gradient.each(function(k, children) {
									ifGradient = true;
									var stoptype = this.data('stoptype'),
										currentStopColor = colorTones(stoptype, colorZone, ifGradient);
									this.update({color: currentStopColor});
								}); 
							}
						}
					}
				};

			for (i = 0; i < tempZonesLength; i++) {
				if (tempZonesLength > 1 && tempZones[i] !== 'mouth' && tempZones[i] !== 'eyesfront') {
					colorsStorage[tempZones[i]] = currentColor;
				} else {
					colorsStorage[bodyZone] = currentColor;
				}
				if (tempZones[i] === 'facehighlight' || tempZones[i] === 'humanbody') {
					shapesCounter = 0;
				} else {
					shapesCounter = elementsStorage[tempZones[i]];
				}
				for (shapeSidePosition in jsonData[tempZones[i]].shapes[shapesCounter]) {
					if (jsonData[tempZones[i]].shapes[shapesCounter].hasOwnProperty(shapeSidePosition)) {
						currentGroup = SVG.get('svga-group-' + tempZones[i] + '-' + shapeSidePosition);
						currentGroup.each(colorCalc);
					}
				}
			}
		}

		// color change based on samples
		$('.svga-colors-set > div').on('click', function() {
			var that = $(this),
				tempZones,
				currentColor;
			that.siblings().removeClass('svga-active');
			that.addClass('svga-active');
			shapesCounter = 0;
			currentColor = that.css('background-color');
			currentColor = tinycolor(currentColor).toHexString();
			$('#svga-custom-color > input').spectrum('set', currentColor);
			switch (bodyZone) {
				case 'faceshape': {
					tempZones = ['faceshape', 'humanbody', 'chinshadow', 'facehighlight', 'ears', 'mouth', 'nose', 'eyesfront'];
					multyZoneColor(tempZones, currentColor);
					break;
				}
				case 'ears': {
					tempZones = ['faceshape', 'humanbody', 'chinshadow', 'facehighlight', 'ears', 'mouth', 'nose', 'eyesfront'];
					multyZoneColor (tempZones, currentColor);
					break;
				}
				case 'nose': {
					tempZones = ['faceshape', 'humanbody', 'chinshadow', 'facehighlight', 'ears', 'mouth', 'nose', 'eyesfront'];
					multyZoneColor (tempZones, currentColor);
					break;
				}
				default: {
					tempZones = [bodyZone];
					multyZoneColor (tempZones, currentColor);
					break;
				}
			}
			changesCounter = ++changesCounter;
		});

		// Spectrum (color picker) dinamic color change
		function updateColors(color) {
			var currentColor = tinycolor(color).toHexString(),
				tempZones;
			switch (bodyZone) {
				case 'faceshape': {
					tempZones = ['faceshape','humanbody','chinshadow','facehighlight','ears','mouth','nose','eyesfront'];
					multyZoneColor(tempZones,currentColor);
					break;
				}
				case 'ears': {
					tempZones = ['faceshape','humanbody','chinshadow','facehighlight','ears','mouth','nose','eyesfront'];
					multyZoneColor (tempZones,currentColor);
					break;
				}
				case 'nose': {
					tempZones = ['faceshape','humanbody','chinshadow','facehighlight','ears','mouth','nose','eyesfront'];
					multyZoneColor (tempZones,currentColor);
					break;
				}
				default: {
					tempZones = [bodyZone];
					multyZoneColor (tempZones,currentColor);
					break;
				}
			}
		}
		$('#svga-custom-color > input').spectrum({
			color: '#000000',
			clickoutFiresChange: true,
			showInput: true,
			showInitial: true,
			showButtons: false,
			move: updateColors,
			change: updateColors
		});
		$('.sp-replacer').on('click', function() {
			$('#svga-colors-' + bodyZone + ' div.svga-active').removeClass('svga-active');
			changesCounter = ++changesCounter;
		});

		//click (tap) event on blocks
		$('.svga-blocks').on('click', function() {
			var that = $(this),
				bodyZones = that.data('bodyzones').split(','), //local var in this func
				currentBlock = that.data('blockname');
			that.siblings().removeClass('svga-active');
			that.addClass('svga-active');
			$('#svga-bodyzones').children().hide();
			for (var i = 0; i < bodyZones.length; i++) {
				$('#svga-bodyzones-' + bodyZones[i]).show();
			}
			$('#svga-bodyzones').children().removeClass('svga-active');
			$('#svga-bodyzones-' + bodyZonesStorage[currentBlock]).addClass('svga-active').trigger('click');
		});

		//click (tap) event on zones
		$('.svga-bodyzones').on('click', function() {
			var that = $(this);
			bodyZone = that.data('bodyzone');
			var currentBlock = that.data('block'),
				getControls = that.data('controls').split(',');
			that.siblings().removeClass('svga-active');
			that.addClass('svga-active');
			$('#svga-elements').children().hide();
			$('#svga-elements-' + bodyZone).show();
			$('#svga-colors').children().hide();
			$('#svga-custom-color').hide(); 
			if (jsonData[bodyZone].colors) {
				$('#svga-colors-' + bodyZone).show();
				$('#svga-custom-color').show(); 
			}
			$('#svga-controls').children().hide();
			for (var i = 0; i < getControls.length; i++) {
				$('#svga-controls-' + getControls[i]).css('display','inline-block');
			}
			bodyZonesStorage[currentBlock] = bodyZone;
			changesCounter = --changesCounter;
			$('#svga-elements-' + bodyZone + '-' + elementsStorage[bodyZone]).addClass('svga-active-element').trigger('click');
		});

		//click (tap) event on elements
		$('.svga-elements').on('click', function() {
			var that = $(this),
				elementData = that.data(),
				currentZone = elementData.zone; // new local var in this func
			if (currentZone === 'eyesfront') {
				currentZone = ['eyesback','eyesfront'];
			} else if (currentZone === 'faceshape') {
				currentZone = ['faceshape','chinshadow'];
			} else {
				currentZone = currentZone.split();
			}
			for (var i = 0; i < currentZone.length; i++) {
				$('#svga-custom-color > input').spectrum("set", colorsStorage[currentZone[i]]);
				if (currentZone[i] === 'facehighlight' || currentZone[i] === 'humanbody') {
					shapesCounter = 0;
				} else {
					shapesCounter = elementData.shape;
				}
				for (shapeSidePosition in jsonData[currentZone[i]].shapes[shapesCounter]) {
					if (jsonData[currentZone[i]].shapes[shapesCounter].hasOwnProperty(shapeSidePosition)) {
						var currentGroup = SVG.get('svga-group-' + currentZone[i] + '-' + shapeSidePosition);
						$('#svga-group-' + currentZone[i] + '-' + shapeSidePosition).empty();
						onCanvas = true;
						drawElement(currentGroup, currentZone[i], shapesCounter, shapeSidePosition); 
					}
				}
				that.siblings().removeClass('svga-active-element');
				that.addClass('svga-active-element');
				elementsStorage[currentZone[i]] = shapesCounter;
			}
			changesCounter = ++changesCounter;
		});

		// click (tap) event on global control buttons
		$('.svga-glob-controls').on('click', function() {
			var id = $(this).attr('id'),
				group = SVG.get('svga-group-humanwrap-move');
			switch (id) {
				case 'svga-glob-controls-up': {
					group.svgaUp(3,2);
					break;
				}
				case 'svga-glob-controls-down': {
					group.svgaDown(3,2);
					break;
				}
				case 'svga-glob-controls-left': {
					group.svgaLeft(4,2);
					break;
				}
				case 'svga-glob-controls-right': {
					group.svgaRight(4,2);
					break;
				}
				case 'svga-glob-controls-scaleup': {
					SVG.get('svga-group-humanwrap').svgaScaleUp();
					break;
				}
				case 'svga-glob-controls-scaledown': {
					SVG.get('svga-group-humanwrap').svgaScaleDown();
					break;
				}
				case 'svga-glob-controls-tiltleft': {
					SVG.get('svga-group-hair-back-move').svgaRotateLeft(1,3,100,150);
					SVG.get('svga-group-head').svgaRotateLeft(1,3,100,150);
					break;
				}
				case 'svga-glob-controls-tiltright':{
					SVG.get('svga-group-hair-back-move').svgaRotateRight(1,3,100,150);
					SVG.get('svga-group-head').svgaRotateRight(1,3,100,150);
					break;
				}
				default:
					break;
			}
			changesCounter = ++changesCounter;
		});

		// click (tap) event on control buttons
		$('#svga-controls-up').on('click', function() {
			var currentGroup;
			switch (bodyZone) {
				case 'mouth': {
					currentGroup = SVG.get('svga-group-mouth-single-move');
					currentGroup.svgaUp();
					break;
				}
				case 'ears': {
					currentGroup = SVG.get('svga-group-ears-left-move');
					currentGroup.svgaUp(1);
					currentGroup = SVG.get('svga-group-ears-right-move');
					currentGroup.svgaUp(1);
					break;
				}
				case 'nose': {
					currentGroup = SVG.get('svga-group-nose-single-move');
					currentGroup.svgaUp(4);
					break;
				}
				case 'eyebrows': {
					currentGroup = SVG.get('svga-group-eyebrows-left-move');
					currentGroup.svgaUp(4);
					currentGroup = SVG.get('svga-group-eyebrows-right-move');
					currentGroup.svgaUp(4);
					break;
				}
				case 'eyesfront': {
					currentGroup = SVG.get('svga-group-eyes-left-move');
					currentGroup.svgaUp(2);
					currentGroup = SVG.get('svga-group-eyes-right-move');
					currentGroup.svgaUp(2);
					break;
				}
				case 'eyesiris': {
					currentGroup = SVG.get('svga-group-eyesiriscontrol-left');
					currentGroup.svgaUp();
					currentGroup = SVG.get('svga-group-eyesiriscontrol-right');
					currentGroup.svgaUp();
					break;
				}
				case 'mustache': {
					currentGroup = SVG.get('svga-group-mustache-single-move');
					currentGroup.svgaUp(4);
					break;
				}
				case 'beard': {
					currentGroup = SVG.get('svga-group-beard-single-move');
					currentGroup.svgaUp(4);
					break;
				}
				case 'glasses': {
					currentGroup = SVG.get('svga-group-glasses-single-move');
					currentGroup.svgaUp(5);
					break;
				}
				default: {
					break;
				}
			}
			changesCounter = ++changesCounter;
		});
		$('#svga-controls-down').on('click', function() {
			var currentGroup;
			switch (bodyZone) {
				case 'mouth': {
					currentGroup = SVG.get('svga-group-mouth-single-move');
					currentGroup.svgaDown();
					break;
				}
				case 'ears': {
					currentGroup = SVG.get('svga-group-ears-left-move');
					currentGroup.svgaDown(1);
					currentGroup = SVG.get('svga-group-ears-right-move');
					currentGroup.svgaDown(1);
					break;
				}
				case 'nose': {
					currentGroup = SVG.get('svga-group-nose-single-move');
					currentGroup.svgaDown(4);
					break;
				}
				case 'eyebrows': {
					currentGroup = SVG.get('svga-group-eyebrows-left-move');
					currentGroup.svgaDown(4);
					currentGroup = SVG.get('svga-group-eyebrows-right-move');
					currentGroup.svgaDown(4);
					break;
				}
				case 'eyesfront': {
					currentGroup = SVG.get('svga-group-eyes-left-move');
					currentGroup.svgaDown(2);
					currentGroup = SVG.get('svga-group-eyes-right-move');
					currentGroup.svgaDown(2);
					break;
				}
				case 'eyesiris': {
					currentGroup = SVG.get('svga-group-eyesiriscontrol-left');
					currentGroup.svgaDown();
					currentGroup = SVG.get('svga-group-eyesiriscontrol-right');
					currentGroup.svgaDown();
					break;
				}
				case 'mustache': {
					currentGroup = SVG.get('svga-group-mustache-single-move');
					currentGroup.svgaDown(4);
					break;
				}
				case 'beard': {
					currentGroup = SVG.get('svga-group-beard-single-move');
					currentGroup.svgaDown(4);
					break;
				}
				case 'glasses': {
					currentGroup = SVG.get('svga-group-glasses-single-move');
					currentGroup.svgaDown(5);
					break;
				}
				default: {
					break;
				}
			}
			changesCounter = ++changesCounter;
		});
		$('#svga-controls-left').on('click', function() {
			var currentGroup;
			switch (bodyZone) {
				case 'mouth': {
					currentGroup = SVG.get('svga-group-mouth-single-move');
					currentGroup.svgaLeft(2,0.5);
					break;
				}
				case 'nose': {
					currentGroup = SVG.get('svga-group-nose-single-move');
					currentGroup.svgaLeft(2,0.5);
					break;
				}
				case 'eyesiris': {
					currentGroup = SVG.get('svga-group-eyesiriscontrol-left');
					currentGroup.svgaLeft();
					currentGroup = SVG.get('svga-group-eyesiriscontrol-right');
					currentGroup.svgaLeft();
					break;
				}
				case 'mustache': {
					currentGroup = SVG.get('svga-group-mustache-single-move');
					currentGroup.svgaLeft(2,0.5);
					break;
				}
				case 'beard': {
					currentGroup = SVG.get('svga-group-beard-single-move');
					currentGroup.svgaLeft(2,0.5);
					break;
				}
				default: {
					break;
				}
			}
			changesCounter = ++changesCounter;
		});
		$('#svga-controls-right').on('click', function() {
			var currentGroup;
			switch (bodyZone) {
				case 'mouth': {
					currentGroup = SVG.get('svga-group-mouth-single-move');
					currentGroup.svgaRight(2,0.5);
					break;
				}
				case 'nose': {
					currentGroup = SVG.get('svga-group-nose-single-move');
					currentGroup.svgaRight(2,0.5);
					break;
				}
				case 'eyesiris': {
					currentGroup = SVG.get('svga-group-eyesiriscontrol-left');
					currentGroup.svgaRight();
					currentGroup = SVG.get('svga-group-eyesiriscontrol-right');
					currentGroup.svgaRight();
					break;
				}
				case 'mustache': {
					currentGroup = SVG.get('svga-group-mustache-single-move');
					currentGroup.svgaRight(2,0.5);
					break;
				}
				case 'beard': {
					currentGroup = SVG.get('svga-group-beard-single-move');
					currentGroup.svgaRight(2,0.5);
					break;
				}
				default: {
					break;
				}
			}
			changesCounter = ++changesCounter;
		});
		$('#svga-controls-scaleup').on('click', function() {
			var currentGroup;
			switch (bodyZone) {
				case 'faceshape': {
					currentGroup = SVG.get('svga-group-faceshape-wrap');
					currentGroup.svgaScaleUp(2, 0.02, 0.0001);
					currentGroup = SVG.get('svga-group-hair-back');
					currentGroup.svgaScaleUp(2, 0.02, 0.0001);
					currentGroup = SVG.get('svga-group-hair-front');
					currentGroup.svgaScaleUp(2, 0.02, 0.0001);
					if (gender === 'boys') {
						currentGroup = SVG.get('svga-group-beardwrap');
						currentGroup.svgaScaleUp(2, 0.02, 0.0001);
					}
					currentGroup = SVG.get('svga-group-ears-left-move');
					currentGroup.svgaLeft(2,1.5);
					currentGroup = SVG.get('svga-group-ears-right-move');
					currentGroup.svgaRight(2,1.5);
					break;
				}
				case 'mouth': {
					currentGroup = SVG.get('svga-group-mouth-single');
					currentGroup.svgaScaleUp(7);
					break;
				}
				case 'nose': {
					currentGroup = SVG.get('svga-group-nose-single');
					currentGroup.svgaScaleUp(5);
					break;
				}
				case 'ears': {
					currentGroup = SVG.get('svga-group-ears-left');
					currentGroup.svgaScaleUp(1);
					currentGroup = SVG.get('svga-group-ears-right');
					currentGroup.svgaScaleUp(1);
					break;
				}
				case 'eyebrows': {
					currentGroup = SVG.get('svga-group-eyebrows-left');
					currentGroup.svgaScaleUp();
					currentGroup = SVG.get('svga-group-eyebrows-right');
					currentGroup.svgaScaleUp();
					break;
				}
				case 'eyesfront': {
					currentGroup = SVG.get('svga-group-eyes-left');
					currentGroup.svgaScaleUp(2);
					currentGroup = SVG.get('svga-group-eyes-right');
					currentGroup.svgaScaleUp(2);
					break;
				}
				case 'eyesiris': {
					currentGroup = SVG.get('svga-group-eyesiris-left');
					currentGroup.svgaScaleUp(4);
					currentGroup = SVG.get('svga-group-eyesiris-right');
					currentGroup.svgaScaleUp(4);
					break;
				}
				case 'mustache': {
					currentGroup = SVG.get('svga-group-mustache-single');
					currentGroup.svgaScaleUp(4);
					break;
				}
				case 'beard': {
					currentGroup = SVG.get('svga-group-beard-single');
					currentGroup.svgaScaleUp(3);
					break;
				}
				case 'glasses': {
					currentGroup = SVG.get('svga-group-glasses-single');
					currentGroup.svgaScaleUp(3);
					break;
				}
				default: {
					break;
				}
			}
			changesCounter = ++changesCounter;
		});
		$('#svga-controls-scaledown').on('click', function() {
			var currentGroup;
			switch (bodyZone) {
				case 'faceshape': {
					currentGroup = SVG.get('svga-group-faceshape-wrap');
					currentGroup.svgaScaleDown(2, 0.02, 0.0001);
					currentGroup = SVG.get('svga-group-hair-back');
					currentGroup.svgaScaleDown(2, 0.02, 0.0001);
					currentGroup = SVG.get('svga-group-hair-front');
					currentGroup.svgaScaleDown(2, 0.02, 0.0001);
					if (gender === 'boys') {
						currentGroup = SVG.get('svga-group-beardwrap');
						currentGroup.svgaScaleDown(2, 0.02, 0.0001);
					}
					currentGroup = SVG.get('svga-group-ears-left-move');
					currentGroup.svgaRight(2,1.5);
					currentGroup = SVG.get('svga-group-ears-right-move');
					currentGroup.svgaLeft(2,1.5);
					break;
				}
				case 'mouth': {
					currentGroup = SVG.get('svga-group-mouth-single');
					currentGroup.svgaScaleDown(7);
					break;
				}
				case 'nose': {
					currentGroup = SVG.get('svga-group-nose-single');
					currentGroup.svgaScaleDown(5);
					break;
				}
				case 'ears': {
					currentGroup = SVG.get('svga-group-ears-left');
					currentGroup.svgaScaleDown(1);
					currentGroup = SVG.get('svga-group-ears-right');
					currentGroup.svgaScaleDown(1);
					break;
				}
				case 'eyebrows': {
					currentGroup = SVG.get('svga-group-eyebrows-left');
					currentGroup.svgaScaleDown(2);
					currentGroup = SVG.get('svga-group-eyebrows-right');
					currentGroup.svgaScaleDown(2);
					break;
				}
				case 'eyesfront': {
					currentGroup = SVG.get('svga-group-eyes-left');
					currentGroup.svgaScaleDown(2);
					currentGroup = SVG.get('svga-group-eyes-right');
					currentGroup.svgaScaleDown(2);
					break;
				}
				case 'eyesiris': {
					currentGroup = SVG.get('svga-group-eyesiris-left');
					currentGroup.svgaScaleDown(4);
					currentGroup = SVG.get('svga-group-eyesiris-right');
					currentGroup.svgaScaleDown(4);
					break;
				}
				case 'mustache': {
					currentGroup = SVG.get('svga-group-mustache-single');
					currentGroup.svgaScaleDown(4);
					break;
				}
				case 'beard': {
					currentGroup = SVG.get('svga-group-beard-single');
					currentGroup.svgaScaleDown(3);
					break;
				}
				case 'glasses': {
					currentGroup = SVG.get('svga-group-glasses-single');
					currentGroup.svgaScaleDown(3);
					break;
				}
				default: {
					break;
				}
			}
			changesCounter = ++changesCounter;
		});
		$('#svga-controls-tightly').on('click', function() {
			var currentGroup;
			switch (bodyZone) {
				case 'eyebrows': {
					currentGroup = SVG.get('svga-group-eyebrows-left-move');
					currentGroup.svgaRight();
					currentGroup = SVG.get('svga-group-eyebrows-right-move');
					currentGroup.svgaLeft();
					break;
				}
				case 'eyesfront': {
					currentGroup = SVG.get('svga-group-eyes-left-move');
					currentGroup.svgaRight();
					currentGroup = SVG.get('svga-group-eyes-right-move');
					currentGroup.svgaLeft();
					break;
				}
				default: {
					break;
				}
			}
			changesCounter = ++changesCounter;
		});
		$('#svga-controls-wider').on('click', function() {
			var currentGroup;
			switch (bodyZone) {
				case 'eyebrows': {
					currentGroup = SVG.get('svga-group-eyebrows-left-move');
					currentGroup.svgaLeft();
					currentGroup = SVG.get('svga-group-eyebrows-right-move');
					currentGroup.svgaRight();
					break;
				}
				case 'eyesfront': {
					currentGroup = SVG.get('svga-group-eyes-left-move');
					currentGroup.svgaLeft();
					currentGroup = SVG.get('svga-group-eyes-right-move');
					currentGroup.svgaRight();
					break;
				}
				default: {
					break;
				}
			}
			changesCounter = ++changesCounter;
		});
		$('#svga-controls-eb1').on('click', function() {
			var currentGroup;
			currentGroup = SVG.get('svga-group-eyebrows-left-rotate');
			currentGroup.svgaCancelRotate().svgaRotateRight(1,rotateStep/2);
			currentGroup = SVG.get('svga-group-eyebrows-right-rotate');
			currentGroup.svgaCancelRotate().svgaRotateRight(1,rotateStep/4);
			changesCounter = ++changesCounter;
		});
		$('#svga-controls-eb2').on('click', function() {
			var currentGroup;
			currentGroup = SVG.get('svga-group-eyebrows-left-rotate');
			currentGroup.svgaCancelRotate().svgaRotateLeft(1,rotateStep/4);
			currentGroup = SVG.get('svga-group-eyebrows-right-rotate');
			currentGroup.svgaCancelRotate().svgaRotateLeft(1,rotateStep/2);
			changesCounter = ++changesCounter;
		});
		$('#svga-controls-eb3').on('click', function() {
			var currentGroup;
			currentGroup = SVG.get('svga-group-eyebrows-left-rotate');
			currentGroup.svgaCancelRotate().svgaRotateRight(1,rotateStep/2);
			currentGroup = SVG.get('svga-group-eyebrows-right-rotate');
			currentGroup.svgaCancelRotate().svgaRotateLeft(1,rotateStep/2);
			changesCounter = ++changesCounter;
		});
		$('#svga-controls-eb4').on('click', function() {
			var currentGroup;
			currentGroup = SVG.get('svga-group-eyebrows-left-rotate');
			currentGroup.svgaCancelRotate().svgaRotateLeft(1,rotateStep/1.4);
			currentGroup = SVG.get('svga-group-eyebrows-right-rotate');
			currentGroup.svgaCancelRotate().svgaRotateRight(1,rotateStep/1.4);
			changesCounter = ++changesCounter;
		});
		$('#svga-controls-ebcancel').on('click', function() {
			var currentGroup;
			currentGroup = SVG.get('svga-group-eyebrows-left-rotate');
			currentGroup.svgaCancelRotate();
			currentGroup = SVG.get('svga-group-eyebrows-right-rotate');
			currentGroup.svgaCancelRotate();
		});

		// reset avatar
		function resetAvatar() {
			if (gender === 'boys') {
				colorsStorage = {
					backs:'#ecf0f1',
					humanbody:'#f0c7b1',
					clothes:'#386e77',
					hair:'#2a232b',
					ears:'#f0c7b1',
					faceshape:'#f0c7b1',
					chinshadow:'#f0c7b1',
					facehighlight:'#f0c7b1',
					eyebrows:'#2a232b',
					eyesback:'#000000',
					eyesfront:'#000000',
					eyesiris:'#4e60a3',
					glasses:'#26120B',
					mustache:'#2a232b',
					beard:'#2a232b',
					mouth:'#da7c87'
				};
			} else  {
				colorsStorage = {
					backs:'#ecf0f1',
					humanbody:'#F3D4CF',
					clothes:'#09aac5',
					hair:'#2a232b',
					ears:'#F3D4CF',
					faceshape:'#F3D4CF',
					chinshadow:'#F3D4CF',
					facehighlight:'#F3D4CF',
					eyebrows:'#2a232b',
					eyesback:'#000000',
					eyesfront:'#000000',
					eyesiris:'#4e60a3',
					glasses:'#26120B',
					mouth:'#f771a9'
				};
			}
			$('#svga-svgmain').empty();
			initGroups();
			for (var currentZone in jsonData) {
				if (jsonData.hasOwnProperty(currentZone)) {
					if (currentZone === 'backs' || currentZone === 'hair') {
						shapesCounter = 1;
					} else {
						shapesCounter = 0;
					}
					if (jsonData.hasOwnProperty(currentZone)) {
						$('#svga-elements-' + currentZone + '-' + shapesCounter).trigger('click');
					}
				}
			}
			$('#svga-colors-faceshape > div:nth-child(1)').trigger('click');
			changesCounter = 2;
			afterRandom = false;
		}

		// random avatar
		function randomAvatar() {
			resetAvatar();
			var colorCounter,
				hairColorCounter = getRandomInt(0,19),
				makeMustache,
				makeBeard;
			if (getRandomInt(0,2) > 1) {
				if (getRandomInt(0,1) === 0) {
					makeMustache = true;
					makeBeard = false;
				} else {
					makeMustache = false;
					makeBeard = true;
				}
			}
			for (var currentZone in jsonData) {
				if (jsonData.hasOwnProperty(currentZone)) {
					var shapesCounter = false; // new local var
					switch (currentZone) {
						case 'ears': {
							shapesCounter = getRandomInt(0,6);
							break;
						}
						case 'eyesiris': {
							shapesCounter = getRandomInt(0,7);
							break;
						}
						case 'hair': {
							if (gender === 'boys') {
								shapesCounter = getRandomInt(0,17);
							} else {
								shapesCounter = getRandomInt(0,14);
							}
							break;
						}
						case 'mustache': {
							if (makeMustache) {
								shapesCounter = getRandomInt(1,12);
							}
							break;
						}
						case 'beard': {
							if (makeBeard) {
								shapesCounter = getRandomInt(1,12);
							}
							break;
						}
						case 'glasses': {
							if (getRandomInt(0,2) > 1) {
								shapesCounter = getRandomInt(0,17);    
							}
							break;
						}
						default: {
							shapesCounter = getRandomInt(0,14);
							break;
						}
					}
					colorCounter = getRandomInt(0,19);
					if (shapesCounter) {
						if (currentZone === 'hair' || currentZone === 'mustache' || currentZone === 'beard' || currentZone === 'eyebrows') {
							$('#svga-elements-' + currentZone + '-' + shapesCounter).trigger('click');
							bodyZone = currentZone;
							$('#svga-colors-' + bodyZone + ' > div:nth-child(' + hairColorCounter + ')').trigger('click');
						} else {
							$('#svga-elements-' + currentZone + '-' + shapesCounter).trigger('click');
							bodyZone = currentZone;
							$('#svga-colors-' + bodyZone + ' > div:nth-child(' + colorCounter + ')').trigger('click');
						}
					}
				}
			}
			$('#svga-blocks-face').trigger('click');
			$('#svga-bodyzones-faceshape').trigger('click');
			$('#svga-colors-faceshape .svga-active').trigger('click');
			changesCounter = 2;
			afterRandom = true;
		}

		// click on reset
		$('#svga-resetavatar').on('click', function() {
			if (changesCounter <= 2 && !afterRandom) {
				resetAvatar();
			} else {
				action = 'reset';
				$('#svga-blocks-face').trigger('click');
				$('#svga-bodyzones-faceshape').trigger('click');
				$('#svga-work-overlay').fadeIn('fast');
				$('#svga-dialog').fadeIn('fast');
			}
		});

		// click on random
		$('#svga-randomavatar').on('click', function() {
			if (changesCounter <= 2) {
				randomAvatar();
			} else {
				action = 'random';
				$('#svga-work-overlay').fadeIn('fast');
				$('#svga-dialog').fadeIn('fast');
			}
		});

		// dialogs
		$('#svga-dialog-ok').on('click', function() {
			if (action === 'reset') {
				resetAvatar();
				$('#svga-work-overlay').fadeOut('fast');
				$('#svga-dialog').fadeOut('fast');
			} else if (action === 'random') {
				randomAvatar();
				$('#svga-work-overlay').fadeOut('fast');
				$('#svga-dialog').fadeOut('fast');
			}
		});
		$('#svga-dialog-cancel').on('click', function() {
			$('#svga-work-overlay').fadeOut('fast');
			$('#svga-dialog').fadeOut('fast');
		});
		$('.svga-close').on('click', function() {
			$('#svga-loader').hide();
			$('#svga-work-overlay').fadeOut('fast');
			$('#svga-message').fadeOut('fast');
			$('#svga-ios').fadeOut('fast');
			$('#svga-share-block').fadeOut('fast');
		});
		$('#svga-tryagain').on('click', function() {
			$('#svga-loader').hide();
			$('#svga-message').hide();
			$('#svga-ios').hide();
			$('#svga-share-block').hide();
			$('#svga-gravatar-message').fadeOut('fast');
			$('#svga-gravataravatar').trigger('click');
		});

		// save avatar on a server
		$('#svga-saveavatar').on('click', function() {
			$('#svga-work-overlay').fadeIn('fast');
			$('#svga-canvas').attr({width: options.savingSize, height: options.savingSize});
			if (rect) {
				rect.hide();
			}
			if (text) {
				text.hide();
			}
			var svg = $('#svga-svgmain').html(),
				img,
				filename,
				canvas;
			svg = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="' + options.savingSize + 'px" height="' + options.savingSize + 'px" viewBox="0 0 200 200" style="overflow:hidden!important;">' + svg.replace(/<svg(.*?)>/, '').replace(/data-(.*?)\"(.*?)\"/g, '').replace(/   /g, ' ').replace(/  /g, ' ').replace(/  /g, ' ');

			if (options.userPro && options.userPro === true) { // for WordPress only (UserPro plugin is used)
				canvas = document.getElementById('svga-canvas');
				canvg(canvas, svg, { ignoreMouse: true, ignoreDimensions: true });
				img = canvas.toDataURL("image/png");
				filename = 'svgA' + (Math.random() + '').replace('0.', '') + '.png';
				$('#svga-canvas').attr({width: 200, height: 200});

				$.ajax({
					url: options.pathToFolder + 'php/save-userpro-avatar.php',
					type: 'post',
					dataType: 'text',
					data: { 
						imgdata: img,
						filename: filename
					},
					cache:false
				}).done(function(response) {
					if (response === 'saved') {
						$('#svga-message-text').html(translation.alertUserProSuccess).removeClass('svga-error');
						$('#svga-work-overlay').fadeIn('fast');
						$('#svga-message').fadeIn('fast');
					} else if (response === 'error_wordpress') {
						$('#svga-work-overlay').fadeIn('fast');
						$('#svga-message-text').html(translation.alertWordpressFail).addClass('svga-error');
						$('#svga-message').fadeIn('fast');
					} else if (response === 'login_fail') {
						$('#svga-work-overlay').fadeIn('fast');
						$('#svga-message-text').html(translation.alertUserProPngFail).addClass('svga-error');
						$('#svga-message').fadeIn('fast');
					} else if (response === 'png_fail') {
						$('#svga-work-overlay').fadeIn('fast');
						$('#svga-message-text').html(translation.alertUserProLoginFail).addClass('svga-error');
						$('#svga-message').fadeIn('fast');
					} else {
						$('#svga-work-overlay').fadeIn('fast');
						$('#svga-message-text').html(translation.alertUserProRequireFail).addClass('svga-error');
						$('#svga-message').fadeIn('fast');
					}
				}).fail(function() {
					$('#svga-message-text').html(translation.alertError).addClass('svga-error');
					$('#svga-work-overlay').fadeIn('fast');
					$('#svga-message').fadeIn('fast');
				});
			} else { // no UserPro integration
				if (options.saveFileFormat === 'png') {
					canvas = document.getElementById('svga-canvas');
					canvg(canvas, svg, { ignoreMouse: true, ignoreDimensions: true });
					img = canvas.toDataURL("image/png");
					filename = 'svgA' + (Math.random() + '').replace('0.', '') + '.png';
				} else if (options.saveFileFormat === 'svg') {
					img = svg;
					filename = 'svgA' + (Math.random() + '').replace('0.', '') + '.svg';
				}
				$('#svga-canvas').attr({width: 200, height: 200});
				$.ajax({
					url: Routing.generate('user_avatar_save'), // options.pathToFolder + 'php/save-ready-avatar.php',
					type: 'post',
					dataType: 'text',
					data: { 
						imgdata: img,
						filename: filename
					},
					cache: false
				}).done(function(response) {
					if (response === 'saved') {
						$('#svga-message-text').html(translation.alertSuccess).removeClass('svga-error');
						$('#svga-work-overlay').fadeIn('fast');
						$('#svga-message').fadeIn('fast');
					} else if (response === 'error_wordpress') {
						$('#svga-work-overlay').fadeIn('fast');
						$('#svga-message-text').html(translation.alertWordpressFail).addClass('svga-error');
						$('#svga-message').fadeIn('fast');
					} else if (response === 'error_uploads_dir') {
						$('#svga-work-overlay').fadeIn('fast');
						$('#svga-message-text').html(translation.alertErrorUploadsDir).addClass('svga-error');
						$('#svga-message').fadeIn('fast');
					} else if (response === 'error_file_data') {
						$('#svga-work-overlay').fadeIn('fast');
						$('#svga-message-text').html(translation.alertErrorFileData).addClass('svga-error');
						$('#svga-message').fadeIn('fast');
					} else if (response === 'error_file_type') {
						$('#svga-work-overlay').fadeIn('fast');
						$('#svga-message-text').html(translation.alertErrorFileType).addClass('svga-error');
						$('#svga-message').fadeIn('fast');
					} else {
						$('#svga-work-overlay').fadeIn('fast');
						$('#svga-message-text').html(translation.alertError).addClass('svga-error');
						$('#svga-message').fadeIn('fast');
					}
				}).fail(function() {
					$('#svga-work-overlay').fadeIn('fast');
					$('#svga-message-text').html(translation.alertError).addClass('svga-error');
					$('#svga-message').fadeIn('fast');
				});
			}
		});

		// block of functions for installing as Gravatar
		$('#svga-gravataravatar').on('click', function() {
			$('#svga-work-overlay').fadeIn('fast');
			$('#svga-gravatar').fadeIn('fast');
		});
		$('#svga-gravatar-cancel').on('click', function() {
			$('#svga-gravatar').hide();
			$('#svga-work-overlay').fadeOut('fast');
		});
		$('#svga-gravatar-ok').on('click', function() {
			$('#svga-gravatar').hide();
			$('#svga-loader').show();
			$('#svga-canvas').attr({width: options.gravatarSize, height: options.gravatarSize});
			if (rect) {
				rect.hide();
			}
			if (text) {
				text.hide();
			}
			var svg = $('#svga-svgmain').html(),
				canvas,
				img,
				email,
				pass,
				rating;
			svg = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="' + options.gravatarSize+ 'px" height="' + options.gravatarSize + 'px" viewBox="0 0 200 200" style="overflow:hidden!important;">' + svg.replace(/<svg(.*?)>/, '').replace(/data-(.*?)\"(.*?)\"/g, '').replace(/   /g, ' ').replace(/  /g, ' ').replace(/  /g, ' ');
			canvas = document.getElementById('svga-canvas');
			canvg(canvas, svg, { ignoreMouse: true, ignoreDimensions: true });
			img = canvas.toDataURL("image/png");
			email = $('#svga-gravatar-email').val();
			pass = $('#svga-gravatar-pass').val();
			rating = $('input[name=svga-gr-rating]:checked').val();
			$('#svga-canvas').attr({width: 200, height: 200});
			$.ajax({
				url: options.pathToFolder + "php/send-to-gravatar.php",
				type: "post",
				dataType: 'text',
				data: { 
					imgdata: img,
					datastring1: email,
					datastring2: pass,
					rating: rating
				},
				cache:false
			}).done(function(response) {
				if (response === 'success') {
					$('#svga-message-text').removeClass('svga-error');
					$('#svga-loader').hide();
					$('#svga-message-text').html(translation.alertSuccessGravatar);
					$('#svga-message').fadeIn('fast');
				} else {
					switch (response) {
						case 'ratingfail': {
							overlayMessage(translation.alertErrorRatingFail);
							break;
						}
						case 'emailfail': {
							overlayMessage(translation.alertErrorEmailFail);
							break;
						}
						case 'passwordfail': {
							overlayMessage(translation.alertErrorPasswordFail);
							break;
						}
						case 'imagefail': {
							overlayMessage(translation.alertErrorImageFail);
							break;
						}
						case 'faultcode-8': {
							$('#svga-loader').hide();
							$('#svga-message-text').html(translation.alertErrorFaultCode8).addClass('svga-error');
							$('#svga-work-overlay').fadeIn('fast');
							$('#svga-message').fadeIn('fast');
							break;
						}
						case 'faultcode-9': {
							overlayMessage(translation.alertErrorFaultCode9);
							break;
						}
						default: {
							overlayMessage(translation.alertCommonErrorGravatar);
							break;
						}
					}
				}
			}).fail(function() {
				overlayMessage(translation.alertCommonErrorGravatar);
			});
		});
		function overlayMessage(error) {
			$('#svga-loader').hide();
			$('#svga-gravatar-message-text').html(error).addClass('svga-error');
			$('#svga-work-overlay').fadeIn('fast');
			$('#svga-gravatar-message').fadeIn('fast');
		}

		// get result PNG file with first dimensions
		$('#svga-png-one').on('click', function() {
			$('#svga-work-overlay').fadeIn('fast');
			$('#svga-canvas').attr({width: options.pngFirstDownloadSize, height: options.pngFirstDownloadSize});
			if (rect) {
				rect.hide();
			}
			if (text) {
				text.hide();
			}
			var svg = $('#svga-svgmain').html(),
				canvas,
				img,
				filename;
			svg = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="' + options.pngFirstDownloadSize+ 'px" height="' + options.pngFirstDownloadSize+ 'px" viewBox="0 0 200 200" style="overflow:hidden!important;">' + svg.replace(/<svg(.*?)>/, '').replace(/data-(.*?)\"(.*?)\"/g, '').replace(/   /g, ' ').replace(/  /g, ' ').replace(/  /g, ' ');
			canvas = document.getElementById('svga-canvas');
			canvg(canvas, svg, { ignoreMouse: true, ignoreDimensions: true });
			img = canvas.toDataURL("image/png");
			filename = 'svgA' + (Math.random() + '').replace('0.', '') + '.png';
			$('#svga-canvas').attr({width: 200, height: 200});
			$.ajax({
				url: options.pathToFolder + "php/save-temp-avatar.php",
				type: "post",
				dataType: 'text',
				data: { 
					imgdata: img,
					filename: filename
				},
				cache:false
			}).done(function(response){
				if (response === 'saved') {
					$('#svga-message-text').removeClass('svga-error');
					getFile(filename, 'png');
					$('#svga-work-overlay').fadeOut('fast');
				} else {
					$('#svga-message-text').html(translation.alertErrorDownload).addClass('svga-error');
					$('#svga-work-overlay').fadeIn('fast');
					$('#svga-message').fadeIn('fast');
				}
			}).fail(function(){
				$('#svga-message-text').html(translation.alertErrorDownload).addClass('svga-error');
				$('#svga-work-overlay').fadeIn('fast');
				$('#svga-message').fadeIn('fast');
			});
		});

		//get result PNG file with second dimensions
		$('#svga-png-two').on('click', function() {
			$('#svga-work-overlay').fadeIn('fast');
			$('#svga-canvas').attr({width: options.pngSecondDownloadSize, height: options.pngSecondDownloadSize});
			if (rect) {
				rect.hide();
			}
			if (text) {
				text.hide();
			}
			var svg = $('#svga-svgmain').html(),
				canvas,
				img,
				filename;
			svg = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="' + options.pngSecondDownloadSize+ 'px" height="' + options.pngSecondDownloadSize+ 'px" viewBox="0 0 200 200" style="overflow:hidden!important;">' + svg.replace(/<svg(.*?)>/, '').replace(/data-(.*?)\"(.*?)\"/g, '').replace(/   /g, ' ').replace(/  /g, ' ').replace(/  /g, ' ');
			canvas = document.getElementById('svga-canvas');
			canvg(canvas, svg, { ignoreMouse: true, ignoreDimensions: true });
			img = canvas.toDataURL("image/png");
			filename = 'svgA' + (Math.random() + '').replace('0.', '') + '.png';
			$('#svga-canvas').attr({width: 200, height: 200});
			$.ajax({
				url: options.pathToFolder + "php/save-temp-avatar.php",
				type: "post",
				dataType: 'text',
				data: { 
					imgdata: img,
					filename: filename
				},
				cache:false
			}).done(function(response) {
				if (response === 'saved') {
					$('#svga-message-text').removeClass('svga-error');
					getFile(filename, 'png');
					$('#svga-work-overlay').fadeOut('fast');
				} else {
					$('#svga-message-text').html(translation.alertErrorDownload).addClass('svga-error');
					$('#svga-work-overlay').fadeIn('fast');
					$('#svga-message').fadeIn('fast');
				}
			}).fail(function() {
				$('#svga-message-text').html(translation.alertErrorDownload).addClass('svga-error');
				$('#svga-work-overlay').fadeIn('fast');
				$('#svga-message').fadeIn('fast');
			});
		});

		//get result PNG file on iOS devices
		if (iOS) {
			$('#svga-downloadavatar').on('click', function() {
				$('#svga-work-overlay').fadeIn('fast');
				$('#svga-canvas').attr({width: options.pngiOSDownloadSize, height: options.pngiOSDownloadSize});
				if (rect) {
					rect.hide();
				}
				if (text) {
					text.hide();
				}
				var svg = $('#svga-svgmain').html(),
					canvas,
					img,
					filename;
				svg = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="' + options.pngiOSDownloadSize+ 'px" height="' + options.pngiOSDownloadSize+ 'px" viewBox="0 0 200 200" style="overflow:hidden!important;">' + svg.replace(/<svg(.*?)>/, '').replace(/data-(.*?)\"(.*?)\"/g, '').replace(/   /g, ' ').replace(/  /g, ' ').replace(/  /g, ' ');
				canvas = document.getElementById('svga-canvas');
				canvg(canvas, svg, { ignoreMouse: true, ignoreDimensions: true });
				img = canvas.toDataURL("image/png");
				filename = 'svgA' + (Math.random() + '').replace('0.', '') + '.png';
				$('#svga-canvas').attr({width: 200, height: 200});
				$.ajax({
					url: options.pathToFolder + "php/save-temp-avatar.php",
					type: "post",
					dataType: 'text',
					data: { 
						imgdata: img,
						filename: filename
					},
					cache:false
				}).done(function(response) {
					if (response === 'saved') {
						$('#svga-message-text').removeClass('svga-error');
						var iosImg = $('<img />')
							.on('error', function() {
								$( this ).hide();
								$('#svga-message-text').html(translation.alertErrorImage).addClass('svga-error');
								$('#svga-work-overlay').fadeIn('fast');
								$('#svga-message').fadeIn('fast');
							})
							.attr('src', options.pathToFolder + 'temp-avatars/'+filename)
							.on('load', function() {
								if (!this.complete || typeof this.naturalWidth === "undefined" || this.naturalWidth === 0) {
									$('#svga-message-text').html(translation.alertErrorImage).addClass('svga-error');
									$('#svga-work-overlay').fadeIn('fast');
									$('#svga-message').fadeIn('fast');
								} else {
									$("#svga-ios-image").empty().append(iosImg);
									$('#svga-work-overlay').fadeIn('fast');
									$('#svga-ios').fadeIn('fast');
								}
							});
					} else {
						$('#svga-message-text').html(translation.alertErrorDownload).addClass('svga-error');
						$('#svga-work-overlay').fadeIn('fast');
						$('#svga-message').fadeIn('fast');
					}
				}).fail(function() {
					$('#svga-message-text').html(translation.alertErrorDownload).addClass('svga-error');
					$('#svga-work-overlay').fadeIn('fast');
					$('#svga-message').fadeIn('fast');
				});
			});
		}

		//get result PNG file on Win8 tablets and phones
		if (Win8tablet) {
			$('#svga-downloadavatar').on('click', function() {
				$('#svga-work-overlay').fadeIn('fast');
				$('#svga-canvas').attr({width: options.pngWin8TabletDownloadSize, height: options.pngWin8TabletDownloadSize});
				if (rect) {
					rect.hide();
				}
				if (text) {
					text.hide();
				}
				var svg = $('#svga-svgmain').html(),
					canvas,
					img,
					filename;
				svg = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="' + options.pngWin8TabletDownloadSize+ 'px" height="' + options.pngWin8TabletDownloadSize+ 'px" viewBox="0 0 200 200" style="overflow:hidden!important;">' + svg.replace(/<svg(.*?)>/, '').replace(/data-(.*?)\"(.*?)\"/g, '').replace(/   /g, ' ').replace(/  /g, ' ').replace(/  /g, ' ');
				canvas = document.getElementById('svga-canvas');
				canvg(canvas, svg, { ignoreMouse: true, ignoreDimensions: true });
				img = canvas.toDataURL("image/png");
				filename = 'svgA' + (Math.random() + '').replace('0.', '') + '.png';
				$('#svga-canvas').attr({width: 200, height: 200});
				$.ajax({
					url: options.pathToFolder + "php/save-temp-avatar.php",
					type: "post",
					dataType: 'text',
					data: { 
						imgdata: img,
						filename: filename
					},
					cache:false
				}).done(function(response) {
					if (response === 'saved') {
						$('#svga-message-text').removeClass('svga-error');
						getFile(filename, 'png');
						$('#svga-work-overlay').fadeOut('fast');
					} else {
						$('#svga-message-text').html(translation.alertErrorDownload).addClass('svga-error');
						$('#svga-work-overlay').fadeIn('fast');
						$('#svga-message').fadeIn('fast');
					}
				}).fail(function() {
					$('#svga-message-text').html(translation.alertErrorDownload).addClass('svga-error');
					$('#svga-work-overlay').fadeIn('fast');
					$('#svga-message').fadeIn('fast');
				});
			});
		}

		//get result SVG file
		$('#svga-svgfile').on('click', function() {
			$('#svga-work-overlay').fadeIn('fast');
			if (rect) {
				rect.hide();
			}
			if (text) {
				text.hide();
			}
			var svg = $('#svga-svgmain').html(),
				filename;
			svg = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="' + options.svgDownloadSize + 'px" height="' + options.svgDownloadSize + 'px" viewBox="0 0 200 200" style="overflow:hidden!important;">' + svg.replace(/<svg(.*?)>/, '').replace(/data-(.*?)\"(.*?)\"/g, '').replace(/   /g, ' ').replace(/  /g, ' ').replace(/  /g, ' ');
			filename = 'svgA' + (Math.random() + '').replace('0.', '') + '.svg';
			$.ajax({
				url: options.pathToFolder + "php/save-temp-avatar.php",
				type: "post",
				dataType: 'text',
				data: { 
					imgdata: svg,
					filename: filename
				},
				cache:false
			}).done(function(response) {
				if (response === 'saved') {
					$('#svga-message-text').removeClass('svga-error');
					getFile(filename, 'svg');
					$('#svga-work-overlay').fadeOut('fast');
				} else {
					$('#svga-message-text').html(translation.alertErrorDownload).addClass('svga-error');
					$('#svga-work-overlay').fadeIn('fast');
					$('#svga-message').fadeIn('fast');
				}
			}).fail(function() {
				$('#svga-message-text').html(translation.alertErrorDownload).addClass('svga-error');
				$('#svga-work-overlay').fadeIn('fast');
				$('#svga-message').fadeIn('fast');
			});
		});

		//share ready avatar
		$('#svga-shareavatar').on('click', function() {
			$('#svga-work-overlay').fadeIn('fast');
			$('#svga-canvas').attr({width: options.shareImageSize, height: options.shareImageSize});
			if (rect) {
				rect.show();
			}
			if (text) {
				text.show();
			}
			var svg = $('#svga-svgmain').html(),
				canvas,
				img,
				filename;
			svg = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="' + options.shareImageSize+ 'px" height="' + options.shareImageSize+ 'px" viewBox="0 0 200 200" style="overflow:hidden!important;">' + svg.replace(/<svg(.*?)>/, '').replace(/data-(.*?)\"(.*?)\"/g, '').replace(/   /g, ' ').replace(/  /g, ' ').replace(/  /g, ' ');
			canvas = document.getElementById('svga-canvas');
			canvg(canvas, svg, { ignoreMouse: true, ignoreDimensions: true });
			img = canvas.toDataURL("image/png");
			filename = 'svgA' + (Math.random() + '').replace('0.', '') + '.png';
			$('#svga-canvas').attr({width: 200, height: 200});
			if (rect) {
				rect.hide();
			}
			if (text) {
				text.hide();
			}
			$.ajax({
				url: options.pathToFolder + "php/save-temp-avatar.php",
				type: "post",
				dataType: 'text',
				data: { 
					imgdata: img,
					filename: filename
				},
				cache:false
			}).done(function(response) {
				if (response === 'saved') {
					$('#svga-message-text').removeClass('svga-error');
					var shareImg = $('<img />')
						.on('error', function() {
							$(this).hide();
							$('#svga-message-text').html(translation.alertErrorImage).addClass('svga-error');
							$('#svga-work-overlay').fadeIn('fast');
							$('#svga-message').fadeIn('fast');
						})
						.attr('src', options.pathToFolder + 'temp-avatars/' + filename)
						.on('load', function() {
							if (!this.complete || typeof this.naturalWidth === "undefined" || this.naturalWidth === 0) {
								$('#svga-message-text').html(translation.alertErrorImage).addClass('svga-error');
								$('#svga-work-overlay').fadeIn('fast');
								$('#svga-message').fadeIn('fast');
							} else {
								$("#svga-share-image").empty().append(shareImg);
								$('#svga-work-overlay').fadeIn('fast');
								$('#svga-share-block').fadeIn('fast');
							}
						});
				} else {
					$('#svga-message-text').html(translation.alertErrorDownload).addClass('svga-error');
					$('#svga-work-overlay').fadeIn('fast');
					$('#svga-message').fadeIn('fast');
				}
			}).fail(function() {
				$('#svga-message-text').html(translation.alertErrorDownload).addClass('svga-error');
				$('#svga-work-overlay').fadeIn('fast');
				$('#svga-message').fadeIn('fast');
			});
		});

		//Share functions
		function shareTwitter() {
			window.open('https://twitter.com/intent/tweet?original_referer=' + encodeURIComponent(options.shareLink) + '&text=' + encodeURIComponent(options.shareTitle) + '%20' + encodeURIComponent(options.shareLink),'Twitter','menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=600,width=600');
		}
		function sharePinterest() {
			var shareImg = $('#svga-share-image img')[0].src;
			window.open('//pinterest.com/pin/create/button/?url=' + encodeURIComponent(options.shareLink) + '&media=' + encodeURIComponent(shareImg) + '&description=' + encodeURIComponent(options.shareTitle),'Pinterest','menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=600,width=600');
		}
		$("#svga-share-twitter").on('click', function() {
			shareTwitter();
			return false;
		});
		$("#svga-share-pinterest").on('click', function() {
			sharePinterest();
			return false;
		});

		//func to force download a previously saved avatar from a temporary folder
		function getFile(tempFile, fileType) {
			var fD = new FileDownloader(options.pathToFolder + 'php/download-temp-avatar.php');
			fD.fileRequest(tempFile, fileType);
			function FileDownloader(url) {
				var fileNameInput,
					downloadFileNameInput,
					tempForm;
				this.url = url;
				this.fileRequest = function(tempFile, fileType) {
					if(!url) {
						return;
					}
					fileNameInput = document.createElement("input");
					fileNameInput.setAttribute("name", 'filename');
					fileNameInput.setAttribute("value", tempFile);
					downloadFileNameInput = document.createElement("input");
					downloadFileNameInput.setAttribute("name", 'downloadingname');
					downloadFileNameInput.setAttribute("value", options.downloadingName);
					tempForm = document.createElement("form");
					tempForm.method = 'get';
					tempForm.action = url;
					tempForm.target = '_top';
					tempForm.appendChild(fileNameInput);
					tempForm.appendChild(downloadFileNameInput);
					document.body.appendChild(tempForm) ;
					tempForm.submit() ;
					document.body.removeChild(tempForm) ;
				};
			}
		}

		//Helper functions
		function removeElement(el) {
			el.parentNode.removeChild(el);
		}
		function removeElementsByClass(className) {
			var elements = document.getElementsByClassName(className);
			while (elements.length > 0) {
				elements[0].parentNode.removeChild(elements[0]);
			}
		}
		function ShadowHighlight(color, ds, dv) {
			var c = tinycolor(color).toHsv();
			c.s = c.s + ds;
			if (c.s < 0) {
				c.s = 0;
			}
			if (c.s > 1) {
				c.s = 1;
			}
			c.v = c.v + dv;
			if (c.v < 0) {
				c.v = 0;
			}
			if (c.v > 1) {
				c.v = 1;
			}
			return tinycolor(c).toHexString();
		}
		function getRandomInt (min, max) {
			return Math.floor(Math.random() * (max - min + 1)) + min;
		}

		//first init state
		$('.svga-col-left .sp-dd').remove();
		$('#svga-blocks-face').trigger('click');
		$('#svga-bodyzones-faceshape').trigger('click');
		$('#svga-elements-faceshape-' + elementsStorage.faceshape).trigger('click').addClass('svga-active-element');
		$('#svga-colors-faceshape > div:nth-child(1)').trigger('click');

		//hide 'Please wait...'
		$('#svga-loader').hide();

		//for random and reset modals
		changesCounter = 2;
		afterRandom = false;

		//set equal height of left and right columns
		setTimeout( function() {
			equalHeight();
		}, 100);

		$('#svga-start-overlay').hide();

	} // end main func svgAvatars()

	//func for calculating and setting equal height of left and right columns
	function equalHeight() {
		var left = $('.svga-col-left'),
			right = $('.svga-col-right');
		left.height('auto');
		right.height('auto');
		if (window.innerWidth >= 481) {
			var leftH = left.height(),
				rightH = right.height();
			if (leftH >= rightH) {
				right.height(leftH);
			} else {
				left.height(rightH);
			}
		}
	}
	//responsive equal height
	$(window).on('resize orientationchange', function(){
		equalHeight();
	});

	//Custom scrolbars in mobile mode
	$('.scrollbar').scrollbar({'showArrows':false, 'ignoreMobile':false});

}); // end document ready