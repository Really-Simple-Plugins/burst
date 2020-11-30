
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
