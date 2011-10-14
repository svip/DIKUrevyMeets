var currentId = 1;

function addMeet ( ) {
	var fieldset = document.getElementById('newmeeting-0').cloneNode(true);
	var master = document.getElementById('schedule');
	currentId++;
	fieldset.setAttribute('id', 'newmeeting-'+currentId);
	fixIds(fieldset);
	if ( !document.getElementById('newmeeting-0-ignore') ) {
		var tickbox = document.createElement('input');
		tickbox.setAttribute('type', 'checkbox');
		tickbox.setAttribute('id', 'newmeeting-'+currentId+'-ignore');
		tickbox.setAttribute('name', 'newmeeting-'+currentId+'-ignore');
		var label = document.createElement('label');
		label.setAttribute('for', 'newmeeting-'+currentId+'-ignore');
		label.appendChild(document.createTextNode('Ignorér'));
		fieldset.insertBefore(tickbox, fieldset.getElementsByTagName('label')[0]);
		fieldset.insertBefore(label, fieldset.getElementsByTagName('label')[0]);
		fieldset.insertBefore(document.createElement('br'), fieldset.getElementsByTagName('label')[1]);
	}
	master.appendChild(fieldset);
}

function addEat ( ) {
	var fieldset = document.getElementById('newmeeting-1').cloneNode(true);
	var master = document.getElementById('schedule');
	currentId++;
	fieldset.setAttribute('id', 'newmeeting-'+currentId);
	fixIds(fieldset);
	master.appendChild(fieldset);
}

function fixIds ( fieldset ) {
	var kids = fieldset.getElementsByTagName('input');
	for ( var i = 0; i < kids.length; i++ ) {
		if ( kids[i].getAttribute('id')!=null )
			// hidden input has no id
			kids[i].setAttribute('id', kids[i].getAttribute('id').replace(/[0-9]+/, currentId) );
		kids[i].setAttribute('name', kids[i].getAttribute('name').replace(/[0-9]+/, currentId) );
	}
	var kids = fieldset.getElementsByTagName('label');
	for ( var i = 0; i < kids.length; i++ ) {
		kids[i].setAttribute('for', kids[i].getAttribute('for').replace(/[0-9]+/, currentId) );
	}
}

function clear ( el ) {
	while ( el.firstChild )
		el.removeChild ( el.firstChild );
}

function getPos ( obj ) {
	var curleft = curtop = 0;
	if ( obj.offsetParent ) {
		do {
			curleft += obj.offsetLeft;
			curtop += obj.offsetTop;
		} while ( obj = obj.offsetParent );
	}
	return [curleft, curtop];
}

function addTag ( tag ) {
	var el = document.getElementById('newmeeting-tags') ? document.getElementById('newmeeting-tags') : document.getElementById('meeting-tags');
	v = el.value.split(',');
	s = '';
	for ( var i = 0; i < v.length - 1; i++ ) {
		if ( s != '' )
			s += ', ';
		s += v[i].trim();
	}
	if ( s != '' )
		s += ', ';
	el.value = s + tag;
	document.getElementById('tagsuggestionbox').style.display = 'none';
}

function tagSuggestionBox ( input, tags ) {
	if ( !document.getElementById('tagsuggestionbox') ) {
		var div = document.createElement('div');
		div.setAttribute('id', 'tagsuggestionbox');
		pos = getPos ( input );
		div.setAttribute('style', 'position: absolute; left: ' + pos[0] + 'px; top: ' + (pos[1]+25) + 'px; width: 200px; border: 1px solid #000; display: none; background: #fff; z-index: 9999; margin: 0; padding: 2px 5px;');
		var ul = document.createElement('ul');
		ul.setAttribute('style', 'margin: 0; padding: 0; list-style: none;' );
		div.appendChild(ul);
		document.getElementsByTagName('body')[0].appendChild(div);
	}
	document.getElementById('tagsuggestionbox').style.display = 'block';
	var el = document.getElementById('tagsuggestionbox').getElementsByTagName('ul')[0];
	clear(el);
	if ( tags.length == 0 ) {
		document.getElementById('tagsuggestionbox').style.display = 'none';
	} else {
		for ( var i = 0; i < tags.length; i++ ) {
			var li = document.createElement('li');
			var a = document.createElement('a');
			a.setAttribute('href', 'javascript://');
			a.setAttribute('onclick', 'addTag(\'' + tags[i] + '\');');
			a.setAttribute('style', 'display: block;');
			a.appendChild(document.createTextNode(tags[i]));
			li.appendChild(a);
			el.appendChild(li);
		}
	}
}

function tagSearch ( e ) {
	s = e.target.value;
	s = s.split(',');
	if ( s.length > 0 )
		s = s[s.length-1].trim();
	else
		s = '';
	request = XMLHttpRequest();
	request.open('GET', "./?do=gettags&search=" + s,
		true );
	request.onreadystatechange = function () {
		if ( request.readyState == 4 && request.status == 200 ) {
			tagSuggestionBox ( e.target, eval(request.responseText) );
		}
	}
	request.send(null);
}

window.onload = function() {
	if ( document.getElementById('newmeeting-date') ) {
		new JsDatePick({
			useMode: 2,
			dateFormat: "%Y-%m-%d",
			target: 'newmeeting-date',
			limitToToday: false,
			imgPath:'/media/img',
			cellColorScheme: 'ocean_blue'
		});
	}
	if ( document.getElementById('meeting-date') ) {
		new JsDatePick({
			useMode: 2,
			dateFormat: "%Y-%m-%d",
			target: 'meeting-date',
			limitToToday: false,
			imgPath:'/media/img',
			cellColorScheme: 'ocean_blue'
		});
	}
	if ( document.getElementById('newmeeting-tags') ) {
		document.getElementById('newmeeting-tags').onfocus = tagSearch;
		document.getElementById('newmeeting-tags').onkeyup = tagSearch;
	}
	if ( document.getElementById('meeting-tags') ) {
		document.getElementById('meeting-tags').onfocus = tagSearch;
		document.getElementById('meeting-tags').onkeyup = tagSearch;
	}
};
