if ( burst_wp_has_consent() ) {
	burst_track_hit();

	if (burst.goal === 'click' ) {

	}
}


function burst_track_hit(){
	var request = new XMLHttpRequest();
	request.open('POST', burst.url, true);
	var url = location.pathname;
	var test_version = test_version;
	var experiment_id = experiment_id;
	var data = {
		'url': url,
		'test_version': test_version,
		'experiment_id': experiment_id,
	};

	console.log(data);
	request.setRequestHeader('Content-type', 'application/json')
	request.send(JSON.stringify(data)) // Make sure to stringify
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
