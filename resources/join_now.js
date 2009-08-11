			function join_now_button(e, openid, return_to) {
				e.preventDefault();
				var form = document.createElement('form');
				form.addEventListener('submit', login_submit, false);
				form.style.margin = 'auto';
				form.style.textAlign = 'center';
				form.style.fontSize = '1.3em';
				form.style.width = '50%';
				form.action = e.target.href + (return_to ? '?return_to='+return_to : '');
				form.method = 'get';
				var label = document.createElement('label');
				form.appendChild(label);
				label['for'] = 'openid_identifier';
				label.appendChild(document.createTextNode('Email address or OpenID '));
				label.style.display = 'block';
				var input = document.createElement('input');
				form.appendChild(input);
				input.type = 'text';
				input.id = 'openid_identifier';
				input.name = 'openid_identifier';
				input.value = openid;
				input.style.width = '92%';
				input.style.fontSize = '1.5em';
				input.style.paddingLeft = '35px';
				input.style.minHeight = '33px';
				input = document.createElement('input');
				form.appendChild(input);
				input.type = 'submit';
				input.value = 'Proceed';
				input.style.width = '100%';
				input.id = 'openid_form_submit';
				var error = document.createElement('div');
				error.id = 'openid_form_error';
				error.style.textAlign = 'center';
				e.target.parentNode.insertBefore(form, e.target);
				e.target.parentNode.insertBefore(error, form);
				e.target.parentNode.removeChild(e.target);
			}

