
var request = new XMLHttpRequest();
request.open('POST', burst.url, true);
console.log(burst.url);
var url = location.pathname;
var data = [];
data.push(url);
console.log(data);
request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
request.send(data);