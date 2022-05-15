/**
 * @package     Joomla.Plugin
 * @subpackage  Twofactorauth.webauthn
 *
 * @copyright   (C) 2020 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

((Joomla, document) => {
  'use strict';

  let authData = null;

  const arrayToBase64String = (a) => btoa(String.fromCharCode(...a));

  const base64url2base64 = (input) => {
    let output = input
      .replace(/-/g, '+')
      .replace(/_/g, '/');
    const pad = output.length % 4;
    if (pad) {
      if (pad === 1) {
        throw new Error('InvalidLengthError: Input base64url string is the wrong length to determine padding');
      }
      output += new Array(5 - pad).join('=');
    }
    return output;
  };

  const handleError = (message) => {
    try {
      document.getElementById('plg_twofactorauth_webauthn_validate_button').style.disabled = 'null';
    } catch (e) {
      // Do nothing
    }

    // TODO Use Joomla's messages instead of an alert
    alert(message);
  };

  const setUp = (e) => {
    e.preventDefault();

    // Make sure the browser supports Webauthn
    if (!('credentials' in navigator)) {
      // TODO Use Joomla's messages instead of an alert
      alert(Joomla.JText._('PLG_TWOFACTORAUTH_WEBAUTHN_ERR_NOTAVAILABLE_HEAD'));

      return false;
    }

    const rawPKData = document.forms['com-users-method-edit']
      .querySelectorAll('input[name="pkRequest"]')[0].value;
    const publicKey = JSON.parse(atob(rawPKData));

    // Convert the public key information to a format usable by the browser's credentials manager
    publicKey.challenge = Uint8Array.from(window.atob(base64url2base64(publicKey.challenge)), (c) => c.charCodeAt(0),);

    publicKey.user.id = Uint8Array.from(window.atob(publicKey.user.id), (c) => c.charCodeAt(0));

    if (publicKey.excludeCredentials) {
      publicKey.excludeCredentials = publicKey.excludeCredentials.map((data) => {
        data.id = Uint8Array.from(
          window.atob(base64url2base64(data.id)),
          (c) => c.charCodeAt(0),
        );
        return data;
      });
    }

    // Ask the browser to prompt the user for their authenticator
    navigator.credentials.create({ publicKey })
      .then((data) => {
        const publicKeyCredential = {
          id: data.id,
          type: data.type,
          rawId: arrayToBase64String(new Uint8Array(data.rawId)),
          response: {
            clientDataJSON: arrayToBase64String(
              new Uint8Array(data.response.clientDataJSON)
            ),
            attestationObject: arrayToBase64String(
              new Uint8Array(data.response.attestationObject)
            ),
          },
        };

        // Store the WebAuthn reply
        document.getElementById('twofactorauth-method-code').value = btoa(JSON.stringify(publicKeyCredential));

        // Submit the form
        document.forms['com-users-method-edit'].submit();
      }, (error) => {
        // An error occurred: timeout, request to provide the authenticator refused, hardware / software
        // error...
        handleError(error);
      });

    return false;
  };

  const validate = () => {
    // Make sure the browser supports Webauthn
    if (!('credentials' in navigator)) {
      // TODO Use Joomla's messages instead of an alert
      alert(Joomla.JText._('PLG_TWOFACTORAUTH_WEBAUTHN_ERR_NOTAVAILABLE_HEAD'));

      return;
    }

    const publicKey = authData;

    if (!publicKey.challenge) {
      handleError(Joomla.JText._('PLG_TWOFACTORAUTH_WEBAUTHN_ERR_NO_STORED_CREDENTIAL'));

      return;
    }

    publicKey.challenge = Uint8Array.from(window.atob(base64url2base64(publicKey.challenge)), (c) => c.charCodeAt(0),);

    if (publicKey.allowCredentials) {
      publicKey.allowCredentials = publicKey.allowCredentials.map((data) => {
        data.id = Uint8Array.from(
          window.atob(base64url2base64(data.id)),
          (c) => c.charCodeAt(0),
        );
        return data;
      });
    }

    navigator.credentials.get({ publicKey })
      .then((data) => {
        const publicKeyCredential = {
          id: data.id,
          type: data.type,
          rawId: arrayToBase64String(new Uint8Array(data.rawId)),
          response: {
            authenticatorData: arrayToBase64String(
              new Uint8Array(data.response.authenticatorData)
            ),
            clientDataJSON: arrayToBase64String(
              new Uint8Array(data.response.clientDataJSON)
            ),
            signature: arrayToBase64String(
              new Uint8Array(data.response.signature)
            ),
            userHandle: data.response.userHandle ? arrayToBase64String(
              new Uint8Array(data.response.userHandle)
            ) : null,
          },
        };

        document.getElementById('twofactorauthCode').value = btoa(JSON.stringify(publicKeyCredential));
        document.getElementById('users-tfa-captive-form').submit();
      }, (error) => {
        // Example: timeout, interaction refused...
        handleError(error);
      });
  };

  const onValidateClick = (event) => {
    event.preventDefault();

    authData = JSON.parse(window.atob(Joomla.getOptions('com_users.authData')));

    document.getElementById('plg_twofactorauth_webauthn_validate_button').style.disabled = 'disabled';
    validate();

    return false;
  };

  document.getElementById('twofactorauth-webauthn-missing').style.display = 'none';

  if (typeof (navigator.credentials) === 'undefined') {
    document.getElementById('twofactorauth-webauthn-missing').style.display = 'block';
    document.getElementById('twofactorauth-webauthn-controls').style.display = 'none';
  }

  window.addEventListener('DOMContentLoaded', () => {
    if (Joomla.getOptions('com_users.pagetype') === 'validate') {
      document.getElementById('plg_twofactorauth_webauthn_validate_button')
        .addEventListener('click', onValidateClick);

      document.getElementById('users-tfa-captive-button-submit')
        .addEventListener('click', onValidateClick);
    } else {
      document.getElementById('plg_twofactorauth_webauthn_register_button')
        .addEventListener('click', setUp);
    }

    document.querySelectorAll('.twofactorauth_webauthn_setup').forEach((btn) => {
      btn.addEventListener('click', setUp);
    });
  });
})(Joomla, document);
