if ( burst_wp_has_consent() ) {
	// if (window.burst_experiment_id !== undefined ) {
		burst_track_hit();
	// }

	if (window.burst_identifier !== undefined ) {
		document.querySelectorAll( window.burst_identifier ).forEach(item => {
			item.addEventListener('click', event => {
				var target = (event.currentTarget) ? event.currentTarget : event.srcElement;
				var is_link = false;
				if (target.tagName.toLowerCase() === "a" && target !== undefined) {
					is_link = true;
					// Don't follow the link yet
					event.preventDefault();
					// Remember the link href
					var href = event.srcElement.attributes.href.textContent;
				} else {
					console.log("no link");
				}

				// Do the async thing
				burst_track_hit(function() {
					// go to the link
					if (is_link) window.location = href;
				});
			})
		})
	}
}

function burst_track_hit(callback) {
	var request = new XMLHttpRequest();
	request.open('POST', burst.url, true);
	var url = location.pathname;
	var conversion = false;

	if (window.burst_is_goal_page !== undefined) {
		conversion = true;
	}

	var data = {
		'url': url,
		'test_version': window.burst_test_version,
		'experiment_id': window.burst_experiment_id,
		'conversion': conversion,
	};

	request.setRequestHeader('Content-type', 'application/json')
	request.send(JSON.stringify(data)) // Make sure to stringify

	if (typeof callback == 'function') {
		callback();
	}
}
/**
 * wrapper to check consent for wp consent API. If consent API is not active, do nothing
 */
function burst_wp_has_consent() {
	if (typeof wp_has_consent == 'function') {
		return wp_has_consent('statistics-anonymous');
	}
	return true;
}
