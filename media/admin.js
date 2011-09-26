var currentId = 1;

function addMeet ( ) {
	var fieldset = document.getElementById('newmeeting-0').cloneNode(true);
	var master = document.getElementById('schedule');
	currentId++;
	fieldset.setAttribute('id', 'newmeeting-'+currentId);
	if ( !document.getElementById('newmeeting-0-ignore') ) {
		fieldset.getElementsByTagName('input')[0].id = 'newmeeting-'+currentId+'-title';
		fieldset.getElementsByTagName('input')[0].name = 'newmeeting-'+currentId+'-title';
		fieldset.getElementsByTagName('label')[0].setAttribute('for', 'newmeeting-'+currentId+'-title');
		fieldset.getElementsByTagName('input')[1].id = 'newmeeting-'+currentId+'-start';
		fieldset.getElementsByTagName('label')[1].setAttribute('for', 'newmeeting-'+currentId+'-start');
		fieldset.getElementsByTagName('input')[1].name = 'newmeeting-'+currentId+'-start';
		fieldset.getElementsByTagName('input')[2].id = 'newmeeting-'+currentId+'-end';
		fieldset.getElementsByTagName('input')[2].name = 'newmeeting-'+currentId+'-end';
		fieldset.getElementsByTagName('input')[3].id = 'newmeeting-'+currentId+'-unique';
		fieldset.getElementsByTagName('input')[3].name = 'newmeeting-'+currentId+'-unique';
		fieldset.getElementsByTagName('label')[2].setAttribute('for', 'newmeeting-'+currentId+'-unique');
		fieldset.getElementsByTagName('input')[4].name = 'newmeeting-'+currentId+'-type';
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
	} else {
		fieldset.getElementsByTagName('input')[0].id = 'newmeeting-'+currentId+'-ignore';
		fieldset.getElementsByTagName('input')[0].name = 'newmeeting-'+currentId+'-ignore';
		fieldset.getElementsByTagName('label')[0].setAttribute('for', 'newmeeting-'+currentId+'-ignore');
		fieldset.getElementsByTagName('input')[1].id = 'newmeeting-'+currentId+'-title';
		fieldset.getElementsByTagName('input')[1].name = 'newmeeting-'+currentId+'-title';
		fieldset.getElementsByTagName('label')[1].setAttribute('for', 'newmeeting-'+currentId+'-title');
		fieldset.getElementsByTagName('input')[2].id = 'newmeeting-'+currentId+'-start';
		fieldset.getElementsByTagName('label')[2].setAttribute('for', 'newmeeting-'+currentId+'-start');
		fieldset.getElementsByTagName('input')[2].name = 'newmeeting-'+currentId+'-start';
		fieldset.getElementsByTagName('input')[3].id = 'newmeeting-'+currentId+'-end';
		fieldset.getElementsByTagName('input')[3].name = 'newmeeting-'+currentId+'-end';
		fieldset.getElementsByTagName('input')[4].id = 'newmeeting-'+currentId+'-unique';
		fieldset.getElementsByTagName('input')[4].name = 'newmeeting-'+currentId+'-unique';
		fieldset.getElementsByTagName('label')[3].setAttribute('for', 'newmeeting-'+currentId+'-unique');
		fieldset.getElementsByTagName('input')[5].name = 'newmeeting-'+currentId+'-type';
	}
	master.appendChild(fieldset);
}

function addEat ( ) {
	var fieldset = document.getElementById('newmeeting-1').cloneNode(true);
	var master = document.getElementById('schedule');
	currentId++;
	fieldset.setAttribute('id', 'newmeeting-'+currentId);
	fieldset.getElementsByTagName('input')[0].id = 'newmeeting-'+currentId+'-ignore';
	fieldset.getElementsByTagName('label')[0].setAttribute('for', 'newmeeting-'+currentId+'-ignore');
	fieldset.getElementsByTagName('input')[0].name = 'newmeeting-'+currentId+'-ignore';
	fieldset.getElementsByTagName('input')[1].id = 'newmeeting-'+currentId+'-title';
	fieldset.getElementsByTagName('label')[1].setAttribute('for', 'newmeeting-'+currentId+'-title');
	fieldset.getElementsByTagName('input')[1].name = 'newmeeting-'+currentId+'-title';
	fieldset.getElementsByTagName('input')[2].id = 'newmeeting-'+currentId+'-start';
	fieldset.getElementsByTagName('label')[2].setAttribute('for', 'newmeeting-'+currentId+'-start');
	fieldset.getElementsByTagName('input')[2].name = 'newmeeting-'+currentId+'-start';
	fieldset.getElementsByTagName('input')[3].id = 'newmeeting-'+currentId+'-end';
	fieldset.getElementsByTagName('input')[3].name = 'newmeeting-'+currentId+'-end';
	fieldset.getElementsByTagName('input')[4].id = 'newmeeting-'+currentId+'-spend';
	fieldset.getElementsByTagName('label')[3].setAttribute('for', 'newmeeting-'+currentId+'-spend');
	fieldset.getElementsByTagName('input')[4].name = 'newmeeting-'+currentId+'-spend';
	fieldset.getElementsByTagName('input')[5].id = 'newmeeting-'+currentId+'-unique';
	fieldset.getElementsByTagName('input')[5].name = 'newmeeting-'+currentId+'-unique';
	fieldset.getElementsByTagName('label')[4].setAttribute('for', 'newmeeting-'+currentId+'-unique');
	fieldset.getElementsByTagName('input')[6].name = 'newmeeting-'+currentId+'-type';
	master.appendChild(fieldset);
}

window.onload = function() {
	if ( document.getElementById('newmeeting-date') ) {
		new JsDatePick({
			useMode: 2,
			dateFormat: "%Y-%m-%d",
			target: 'newmeeting-date',
			limitToToday: false,
			imgPath:"/media/img",
			cellColorScheme: 'beige'
		});
	}
};
