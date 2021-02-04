
if ( cmplz_wp_has_consent() ) {
	var request = new XMLHttpRequest();
	request.open('POST', burst.url, true);
	console.log(burst.url);
	var url = location.pathname;
	var test_version = test_version;
	var data = {
		'url': url,
		'test_version': test_version,
	};

	console.log(data);
	request.setRequestHeader('Content-type', 'application/json')
	request.send(JSON.stringify(data)) // Make sure to stringify
}
/**
 * wrapper to check consent for wp consent API. If consent API is not active, do nothing
 */
function cmplz_wp_has_consent() {
	if (typeof wp_has_consent == 'function') {
		return wp_has_consent('statistics-anonymous');
	}
	return true;
}
