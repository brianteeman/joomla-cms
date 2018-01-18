/**
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

(function(document, Joomla) {
	if (!Joomla) {
		throw new Error('Joomla API is not properly initialised');
	}

	Joomla.apps = {
		view: "dashboard",
		id: 0,
		ordering: "",
		cssfiles: [],
		jsfiles: [],
		list: 0,
		loaded: 0,
		update: false
	};

	Joomla.loadweb = function (url) {
		if (!url) {
			return false;
		}

		var pattern1 = new RegExp(apps_base_url);
		var pattern2 = new RegExp("^index\.php");
		if (!(pattern1.test(url) || pattern2.test(url))) {
			window.open(url, "_blank");
			return false;
		}

		url += '&product=' + apps_product + '&release=' + apps_release + '&dev_level=' + apps_dev_level + '&list=' + (Joomla.apps.list ? 'list' : 'grid') + '&pv=' + apps_pv;

		if (Joomla.apps.ordering !== "" && document.querySelector('#com-apps-ordering').value) {
			Joomla.apps.ordering = document.querySelector('#com-apps-ordering').value;
			url += '&ordering=' + Joomla.apps.ordering;
		}

		//jQuery('html, body').animate({ scrollTop: 0 }, 0);
		if (document.querySelector('#myTabContent')) {
			var element = document.querySelector('#appsloading');
			element.style.position = "absolute";
			element.style.left = "0";
			element.style.top = "0";
			element.style.width = "100%";
			element.style.height = "100%";

			document.querySelector('#web').style.position = 'relative';
			document.querySelector('#web').appendChild(element)

			jQuery.event.trigger("ajaxStart");
		}

		// @todo convert to vanilla, (why JSONP?)
		jQuery.ajax({
			url: url,
			dataType: 'jsonp',
			cache: true,
			jsonpCallback: "jedapps_jsonpcallback",
			timeout: 20000,
			success: function (response) {
				document.querySelector('#web-loader').style.display = 'none';
				document.querySelector('#jed-container').innerHTML = response.data.html;
				if (!Joomla.apps.update && response.data.pluginuptodate < 1) {
					Joomla.apps.update = true;
					var txt = apps_obsolete;
					var btn = apps_updateavail2;
					if (response.data.pluginuptodate == 0) {
						txt = apps_updateavail1;
					}

					// @todo use custom element alert
					document.querySelector('#web').insertAdjacentHTML('afterbegin', '<div class="alert alert-info j-jed-message" style="margin-bottom: 20px; line-height: 2em; color:#333333;">' + txt + '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input class="btn" type="button" value="' + btn + '" onclick="Joomla.submitbuttonInstallWebInstaller()" /></div>',);
				}
				if (document.querySelector('#myTabContent')) {
					jQuery.event.trigger("ajaxStop");
				}
			},
			fail: function () {
				document.querySelector('#web-loader').style.display = 'none';
				document.querySelector('#web-loader-error').style.display = 'block'
				if (document.querySelector('#myTabContent')) {
					jQuery.event.trigger("ajaxStop");
				}
			},
			complete: function () {
				if (document.querySelector('#joomlaapsinstallatinput')) {
					document.querySelector('#joomlaapsinstallatinput').value = apps_installat_url;
				}
				Joomla.apps.clickforlinks();
				Joomla.apps.clicker();
				if (Joomla.apps.list) {
					document.querySelector(".list-view").click();
				}
				if (document.querySelector('#myTabContent')) {
					jQuery.event.trigger("ajaxStop");
				}
			},
			error: function (request, status, error) {
				if (request.responseText) {
					document.querySelector('#web-loader-error').innerHTML = request.responseText;
				}
				document.querySelector('#web-loader').style.display = 'none';
				document.querySelector('#web-loader-error').style.display = 'block';
				if (document.querySelector('#myTabContent')) {
					jQuery.event.trigger("ajaxStop");
				}
			}
		});
		return true;
	};

	Joomla.webpaginate = function (url, target) {
		document.querySelector('#web-paginate-loader').style.display = 'block';

		jQuery.get(url, function (response) {
			document.querySelector('#web-paginate-loader').style.display = 'none';
			document.querySelector('#' + target).innerHTML = response.data.html;
		}, 'jsonp').fail(function () {
			document.querySelector('#web-paginate-loader').style.display = 'none';
		});
	};

	Joomla.installfromwebexternal = function (redirect_url) {
		// @todo trnslatable string from PHP
		var redirect_confirm = confirm('You will be redirected to the following link to complete the registration/purchase - \n' + redirect_url);
		if (true == redirect_confirm) {
			document.querySelector('#adminForm').setAttribute('action', redirect_url);
			document.querySelector("input[name=task]").setAttribute("disabled", true);
			document.querySelector("input[name=install_directory]").setAttribute("disabled", true);
			document.querySelector("input[name=install_url]").setAttribute("disabled", true);
			document.querySelector("input[name=installtype]").setAttribute("disabled", true);
			document.querySelector("input[name=filter_search]").setAttribute("disabled", true);
			return true;
		}
		return false;
	};

	Joomla.installfromweb = function (install_url, name) {
		if (!install_url) {
			// @todo trnslatable string from PHP
			alert("This extension cannot be installed via the web. Please visit the developer's website to purchase/download.");
			return false;
		}
		document.querySelector('#install_url').value = install_url;
		document.querySelector('#uploadform-web-url').innerHTML = install_url;
		if (name) {
			document.querySelector('#uploadform-web-name').innerHTML = name;
			document.querySelector('#uploadform-web-name-label').style.display = 'block';
		} else {
			document.querySelector('#uploadform-web-name-label').style.display = 'none';
		}
		// jQuery('#jed-container').slideUp(300);
		document.querySelector('#uploadform-web').style.display = 'block';
		return true;
	};

	Joomla.installfromwebcancel = function () {
		document.querySelector('#uploadform-web').style.display = 'none';
		// jQuery('#jed-container').slideDown(300);
		if (Joomla.apps.list) {
			document.querySelector(".list-view").click();
		}
	};

	Joomla.installfromwebajaxsubmit = function () {
		var tail = '&view=' + Joomla.apps.view;
		if (Joomla.apps.id) {
			tail += '&id=' + Joomla.apps.id;
		}

		if (document.querySelector('#com-apps-searchbox').value) {
			var value = encodeURI(document.querySelector('#com-apps-searchbox').value.toLowerCase().replace(/ +/g, '_').replace(/[^a-z0-9-_]/g, '').trim());
			tail += '&filter_search=' + value;
		}

		var ordering = Joomla.apps.ordering;
		if (ordering !== "" && document.querySelector('#com-apps-ordering').value) {
			ordering = document.querySelector('#com-apps-ordering').value;
		}
		if (ordering) {
			tail += '&ordering=' + ordering;
		}
		Joomla.loadweb(apps_base_url + 'index.php?format=json&option=com_apps' + tail);
	};

	Joomla.apps.clickforlinks = function () {
		[].slice.call(document.querySelectorAll('a.transcode')).forEach(element => {
			var ajaxurl = this.getAttribute('href');
			element.addEventListener('click', function (event) {
				var pattern1 = new RegExp(apps_base_url);
				var pattern2 = new RegExp("^index\.php");
				if (pattern1.test(ajax_url) || pattern2.test(ajax_url)) {
					Joomla.apps.view = ajax_url.replace(/^.+[&\?]view=(\w+).*$/, '$1');
					if (Joomla.apps.view == 'dashboard') {
						Joomla.apps.id = 0;
					}
					else if (Joomla.apps.view == 'category') {
						Joomla.apps.id = ajax_url.replace(/^.+[&\?]id=(\d+).*$/, '$1');
					}
					event.preventDefault();
					Joomla.loadweb(apps_base_url + ajax_url);
				}
				else {
					event.preventDefault();
					Joomla.loadweb(ajax_url);
				}
			});
			this.setAttribute('href', '#');
		});
	};

	Joomla.apps.initialize = function () {
		Joomla.apps.loaded = 1;
		if (document.querySelector('#myTabContent')) {
			document.querySelector('#web').insertAdjacentHTML('afterbegin', '<div id="appsloading"></div>');
			document.querySelector('#web').style.position = 'absolute';

			jQuery('#appsloading').ajaxStart(function () {
				document.querySelector('body').classList.add('ifw-busy');
				this.style.display = 'block';
			}).ajaxStop(function () {
				this.style.display = 'none';
				document.querySelector('body').classList.remove('ifw-busy');
			});
		}

		Joomla.loadweb(apps_base_url + 'index.php?format=json&option=com_apps&view=dashboard');

		Joomla.apps.clickforlinks();

		document.querySelector('#com-apps-searchbox').addEventListener('keypress', function (event) {
			if (event.which == 13) {
				Joomla.apps.initiateSearch();
			}
		});

		document.querySelector('#search-reset').addEventListener('click', function (event) {
			document.querySelector('#com-apps-searchbox').value = '';
			Joomla.apps.initiateSearch();
		});

		document.querySelector('#com-apps-ordering').addEventListener('change', function (event) {
			Joomla.apps.ordering = jQuery(this).prop("selectedIndex");
			Joomla.installfromwebajaxsubmit();
		});

		if (apps_installfrom_url != '') {
			Joomla.installfromweb(apps_installfrom_url);
		}
	};

	Joomla.apps.initiateSearch = function () {
		Joomla.apps.view = 'dashboard';
		Joomla.installfromwebajaxsubmit();
	};

	Joomla.apps.clicker = function () {
		document.querySelector(".grid-view").addEventListener("click", function () {
			Joomla.apps.list = 0;
			document.querySelector(".list-container").classList.add("hidden");
			document.querySelector(".grid-container").classList.remove("hidden");
			document.querySelector("#btn-list-view").classList.remove("active");
			document.querySelector("#btn-grid-view").classList.remove("active");
		});
		document.querySelector(".list-view").addEventListener("click", function () {
			Joomla.apps.list = 1;
			document.querySelector(".grid-container").classList.add("hidden");
			document.querySelector(".list-container").classList.remove("hidden");
			document.querySelector("#btn-grid-view").classList.remove("active");
			document.querySelector("#btn-list-view").classList.add("active");
		});
	};

	Joomla.submitbutton5 = function (pressbutton) {
		var form = document.getElementById('adminForm');

		// do field validation
		if (form.install_url.value !== "" && form.install_url.value !== "http://") {
			Joomla.submitbutton4();
		}
		else if (form.install_url.value === "") {
			alert(apps_btntxt);
		}
		else {
			document.querySelector('#appsloading').style.display = 'block';
			form.installtype.value = 'web';
			form.submit();
		}
	};
})(document, Joomla);
