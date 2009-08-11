function login_submit_callback(data) {
	if(data.redirect) {
		window.location = data.redirect;
	}
	if(data.method == 'post') {
		var form = document.createElement('form');
		form.method = 'post';
		form.action = data.action;
		var input;
		for(var i in data) {
			if(i == 'method' || i == 'action') continue;
			input = document.createElement('input');
			input.type = 'hidden';
			input.name = i;
			input.value = data[i];
			form.appendChild(input);
		}
		document.getElementById('openid_form_error').appendChild(form);
		form.submit();
	}
	if(data.error) {
		document.getElementById('openid_form_error').style.display = 'block';
		document.getElementById('openid_form_error').style.padding = '1em';
		document.getElementById('openid_form_error').style.color = 'white';
		document.getElementById('openid_form_error').style.backgroundColor = '#f66';
		if(!document.getElementById('openid_form_error').firstChild)
			document.getElementById('openid_form_error').appendChild(document.createTextNode(data.error));
		else
			document.getElementById('openid_form_error').firstChild.innerContent = data.error;
		document.getElementById('openid_form_submit').value = 'Proceed';
		document.getElementById('openid_form_submit').disabled = false;
	}
}

function login_submit(e) {
	e.preventDefault();
	var script = document.createElement('script');
	script.type = 'text/javascript';
	script.src = e.target.getAttribute('action') + (e.target.getAttribute('action').indexOf('?') == '-1' ? '?' : '&') + 'json&callback=login_submit_callback&openid_identifier='+encodeURIComponent(e.target.openid_identifier.value);
	if(e.target.return_to)
		script.src += '&return_to='+encodeURIComponent(e.target.return_to.value);
	if(typeof(e.target.action) != 'string')
		script.src += '&action='+encodeURIComponent(e.target.action.value);
	document.body.appendChild(script);
	document.getElementById('openid_form_submit').value = 'Processing, please wait...';
	document.getElementById('openid_form_submit').disabled = true;
	document.getElementById('openid_form_error').style.display = 'none';
}
