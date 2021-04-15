window.burst_one_request_completed = false;

if ( !burst_is_user_agent() ) {
	burst_track_hit(function() {
		// go to the link
		window.burst_one_request_completed = true;
	});
}

if ( burst_is_experiment_page() ) {
	console.log("is experiment page");
	if ( burst_is_user_agent() ) {
		burst_show_content('control');
	} else {
		window.burst_test_version = burst_get_user_test_version();
		console.log(window.burst_test_version);

		burst_show_content(window.burst_test_version);
	}
} else {
	console.log("Not an experiment page");
}

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
			}

			// Do the async thing
			burst_track_hit(function() {
				// go to the link
				if (is_link) window.location = href;
			});
		})
	})
}

/**
 *
 * @param callback
 */
function burst_track_hit(callback) {
	if ( !burst_wp_has_consent() ) return;

	console.log("track hit function");
	var request = new XMLHttpRequest();
	request.open('POST', burst.url+'hit', true);
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

/**
 * Check if this is a user agent
 * @returns {boolean}
 */
function burst_is_user_agent() {
	var botPattern = "(googlebot\/|bot|Googlebot-Mobile|Googlebot-Image|Google favicon|Mediapartners-Google|bingbot|slurp|java|wget|curl|Commons-HttpClient|Python-urllib|libwww|httpunit|nutch|phpcrawl|msnbot|jyxobot|FAST-WebCrawler|FAST Enterprise Crawler|biglotron|teoma|convera|seekbot|gigablast|exabot|ngbot|ia_archiver|GingerCrawler|webmon |httrack|webcrawler|grub.org|UsineNouvelleCrawler|antibot|netresearchserver|speedy|fluffy|bibnum.bnf|findlink|msrbot|panscient|yacybot|AISearchBot|IOI|ips-agent|tagoobot|MJ12bot|dotbot|woriobot|yanga|buzzbot|mlbot|yandexbot|purebot|Linguee Bot|Voyager|CyberPatrol|voilabot|baiduspider|citeseerxbot|spbot|twengabot|postrank|turnitinbot|scribdbot|page2rss|sitebot|linkdex|Adidxbot|blekkobot|ezooms|dotbot|Mail.RU_Bot|discobot|heritrix|findthatfile|europarchive.org|NerdByNature.Bot|sistrix crawler|ahrefsbot|Aboundex|domaincrawler|wbsearchbot|summify|ccbot|edisterbot|seznambot|ec2linkfinder|gslfbot|aihitbot|intelium_bot|facebookexternalhit|yeti|RetrevoPageAnalyzer|lb-spider|sogou|lssbot|careerbot|wotbox|wocbot|ichiro|DuckDuckBot|lssrocketcrawler|drupact|webcompanycrawler|acoonbot|openindexspider|gnam gnam spider|web-archive-net.com.bot|backlinkcrawler|coccoc|integromedb|content crawler spider|toplistbot|seokicks-robot|it2media-domain-crawler|ip-web-crawler.com|siteexplorer.info|elisabot|proximic|changedetection|blexbot|arabot|WeSEE:Search|niki-bot|CrystalSemanticsBot|rogerbot|360Spider|psbot|InterfaxScanBot|Lipperhey SEO Service|CC Metadata Scaper|g00g1e.net|GrapeshotCrawler|urlappendbot|brainobot|fr-crawler|binlar|SimpleCrawler|Livelapbot|Twitterbot|cXensebot|smtbot|bnf.fr_bot|A6-Indexer|ADmantX|Facebot|Twitterbot|OrangeBot|memorybot|AdvBot|MegaIndex|SemanticScholarBot|ltx71|nerdybot|xovibot|BUbiNG|Qwantify|archive.org_bot|Applebot|TweetmemeBot|crawler4j|findxbot|SemrushBot|yoozBot|lipperhey|y!j-asr|Domain Re-Animator Bot|AddThis)";
	var re = new RegExp(botPattern, 'i');
	var userAgent = navigator.userAgent;

	return re.test(userAgent);
}

/**
 * Check if we're on an experiment page
 * @returns {boolean}
 */
function burst_is_experiment_page(){
	return window.burst_experiment_id !== undefined;
}

function burst_show_content( test_type ){
	//by default, the control is 'visibility:none', which ensures the right amount of space is taken in the dom.
	//the variant is display:none, so not visible at all.
	const variants = document.querySelectorAll(".burst_variant");
	const controls = document.querySelectorAll(".burst_control");
	if ( test_type === 'control' ) {
		for (const variant of variants) {
			variant.style.display = 'none';
		}
		for (const control of controls) {
			control.style.visibility = 'visible';
			control.style.display = 'block';
		}
	} else {
		for (const control of controls) {
			control.style.display = 'none';
		}
		for (const variant of variants) {
			variant.style.display = 'block';
		}
	}
}

/**
 * check if user has cookie: uid && test_version.
 * if no cookie, create a uid, and random choose control/variant.
 * set cookie
 */
function burst_get_user_test_version(){
	//get uid cookie
	var uid = burst_get_cookie('burst_uid');
	if ( uid.length == 0 ) {
		//determine test_version randomly
		var rand = Math.floor(Math.random() * 2);
		if ( rand === 1 ) {
			window.burst_test_version = 'variant';
		} else {
			window.burst_test_version = 'control';
		}
		burst_set_cookie('burst_v', window.burst_test_version );

	} else {
		window.burst_test_version = burst_get_cookie('burst_v');
	}
	return window.burst_test_version;
}

/**
 * Get a cookie by name
 * @param name
 * @returns {string}
 */
function burst_get_cookie(name) {
	name = name + "="; //Create the cookie name variable with cookie name concatenate with = sign
	var cArr = window.document.cookie.split(';'); //Create cookie array by split the cookie by ';'

	//Loop through the cookies and return the cookie value if it find the cookie name
	for (var i = 0; i < cArr.length; i++) {
		var c = cArr[i].trim();
		//If the name is the cookie string at position 0, we found the cookie and return the cookie value
		if (c.indexOf(name) == 0)
			return c.substring(name.length, c.length);
	}

	return "";
}

/**
 * set a cookie
 * @param name
 * @param value
 * @param days
 */

function burst_set_cookie(name, value) {
	var cookiePath = '/';
	var domain = '';
	var secure = ";secure";
	var date = new Date();
	var days = burst.cookie_retention_days;
	date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
	var expires = ";expires=" + date.toGMTString();

	if ( window.location.protocol !== "https:" ) secure = '';

	//if we want to dynamically be able to change the domain, we can use this.
	if ( domain.length > 0 ) {
		domain = ";domain=" + domain;
	}
	document.cookie = name + "=" + value + ";SameSite=Lax" + secure + expires + domain + ";path="+cookiePath;
}

