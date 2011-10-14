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

window.onload = function() {
	if ( document.getElementById('newmeeting-date') ) {
		new JsDatePick({
			useMode: 2,
			dateFormat: "%Y-%m-%d",
			target: 'newmeeting-date',
			limitToToday: false,
			imgPath:'/media/img',
			cellColorScheme: 'beige'
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
};
