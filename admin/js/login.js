var Login = function() {
	var loginForm = null;

	var loginFormData = {
		fields_width: 200,
		title: SL.get('cms/admin/login-title'),
		rows: [ {
			id: 'data',
			title: SL.get('cms/admin/login'),
			title_cls: 'req',
			type: 'text',
			def: window.DATA,
			validate: [ SValidators.required ]
		}, {
			id: 'value',
			title: SL.get('cms/admin/password'),
			title_cls: 'req',
			type: 'password',
			validate: [ SValidators.required ]
		} ],
		buttons: [ {
			id: 'login',
			title: SL.get('cms/admin/login-button'),
			validate: true
		} ]
	};

	function showLoginForm() {
		S.get('login-form-wrap').innerHTML = [
			'<input type="hidden" name="enter" value="enter" />',
			'<input type="submit" style="font-size:1px;visibility:hidden;" />' // fix for chrome
		].join('');

		S.get('login-form-wrap').onsubmit = function() {
			if (S.get('login-form-wrap').getAttribute('_real_submit')) {
				return true;
			} else {
				loginForm.get_button('login').press();
				return false;
			}
		}

		loginForm = $new(SFormView, loginFormData);
		loginForm.render(S.get('login-form-wrap'));

		if (S.is_ie) {
			loginForm.dom().style.textAlign = 'left';
		}

		loginForm.get_button('login').set_handler(function() {
			S.get('login-form-wrap').setAttribute('_real_submit', 'yep');
			S.get('login-form-wrap').submit();
		});

		if (window.ERROR) {
			loginForm.set_error(window.ERROR);
		}
	}

	return {
		init: function() {
			showLoginForm();
		}
	};
}();

S.add_handler(window, 'load', Login.init);
