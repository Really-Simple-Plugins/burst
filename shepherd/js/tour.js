jQuery(document).ready(function($) {
	if (!window.Shepherd) return;
	var stepsArr = Object.entries(burst_tour.steps);
	var steps = [];
	stepsArr.forEach(([key, value]) => {
		steps[key] = value;
	});
	console.log(steps);
	var prevLink = [];
	var prevText = [];
	var nextLink = [];
	var nextText = [];
	var tour = new Shepherd.Tour();

	initTour();

	if ( typeof window._wpLoadBlockEditor !== 'undefined' ) {
		window._wpLoadBlockEditor.then(function () {
			setTimeout(
				function()
				{
					initTour();
				}, 2000);
		});
	}

	function initTour(){
		for (var key in steps) {
			if (steps.hasOwnProperty(key)) {
				var step = steps[key];
				var stepCount = steps.length;
				if ($(step.attach).length) {
					if (key == 0) {
						prevLink[0] = burst_tour.configure_link;
						prevText[0] = burst_tour.configure_text;
					} else {
						prevLink[key] = steps[key - 1].link;
						prevText[key] = burst_tour.backBtnText;
					}

					if (key < stepCount - 1) {
						nextLink[key] = steps[parseInt(key) + 1].link;
						nextText[key] = burst_tour.nextBtnText;
					} else {
						nextLink[key] = nextLink[key-1];
						nextText[key] = burst_tour.endTour;
					}

					tour.on('cancel', cancel_tour);
					tour.options.defaults =
						{
							classes: 'shepherd-theme-arrows',
							scrollTo: true,
							scrollToHandler: function (e) {
								if (typeof ($(e).offset()) !== "undefined") {
									$('html, body').animate({
										scrollTop: $(e).offset().top - 200
									}, 1000);
								}
							},
							showCancelLink: true,
							tetherOptions: {
								constraints: [
									{
										to: 'scrollParent',
										attachment: 'together',
										pin: false
									}
								]
							}
						};

					tour.addStep(key, {
						classes: 'shepherd-theme-arrows burst-shepherd shepherd-has-cancel-link shepherd-step-' + key,
						attachTo: step.attach + ' ' + step.position,
						title: step.title,
						text: burst_tour.html.replace('{content}', step.text),
						buttons: [
							{
								text: prevText[key],
								classes: 'button button-primary',
								action: function () {
									var step_id = $(this).closest('.shepherd-step').data('id');
									if (step_id == 0 ) {
										tour.cancel();
										window.location = prevLink[step_id];
									} else {
										if (prevLink.length > 1 && prevLink[step_id] === steps[step_id].link) {
											if (steps[parseInt(step_id)-1].hasOwnProperty('click') && $(steps[parseInt(step_id)-1].click).length ) {
												$(steps[parseInt(step_id)-1].click).click();
											}
											tour.back();
										} else {
											window.location = prevLink[step_id];
										}
									}
								}
							},
							{
								text: nextText[key],
								action: function () {
									var step_id = $(this).closest('.shepherd-step').data('id');
									if (step_id == stepCount-1 ) {
										tour.cancel();
									} else {
										if (nextLink.length > 1 && nextLink[step_id] === steps[step_id].link) {
											if ( steps[parseInt(step_id)+1].hasOwnProperty('click') && $(steps[parseInt(step_id)+1].click).length ) {
												$(steps[parseInt(step_id)+1].click).click();
											}
											tour.next();
										} else {
											window.location = nextLink[step_id];
										}
									}

								},
								classes: 'button button-primary',
							},
						],

					});
					tour.start();
				}
			}
		}
	}

	/**
	 * Cancel tour
	 */

	function cancel_tour() {
		tour.canceled = true;
		$.ajax({
			type: "POST",
			url: burst_tour.ajaxurl,
			dataType: 'json',
			data: ({
				action: 'burst_cancel_tour',
				token: burst_tour.token,
			})
		});
	};

});
