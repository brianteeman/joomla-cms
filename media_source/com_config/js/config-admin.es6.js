/**
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
import JoomlaDialog from 'joomla.dialog';

/**
 * Helper method to show a Confirmation popup for unsaved changes.
 * @returns {Promise}
 */
const confirmUnsavedChanges = () => {
  return new Promise((resolve) => {
    let result = false;
    const popup = new JoomlaDialog();
    popup.popupType = 'inline';
    popup.popupContent = Joomla.Text._('COM_CONFIG_UNSAVED_CHANGES_CONFIRM');
    popup.textHeader = Joomla.Text._('WARNING');
    popup.popupButtons = [
      {
        label: Joomla.Text._('COM_CONFIG_UNSAVED_CHANGES_PROCEED'),
        data: { buttonOk: '' },
        onClick: () => {
          result = true;
          popup.destroy();
        },
      },
      {
        label: Joomla.Text._('JCANCEL'),
        data: { buttonCancel: '' },
        onClick: () => {
          result = false;
          popup.destroy();
        },
        className: 'button button-secondary btn btn-outline-secondary',
      },
    ];
    popup.cancelable = false;
    popup.classList.add('joomla-dialog-confirm');
    popup.addEventListener('joomla-dialog:close', () => resolve(result));
    popup.show();
  });
};

/**
 * Watch on unsaved changes, and warn User before navigation to another configuration form
 */
const watchOnUnsavedChanges = () => {
  const form = document.querySelector('#application-form, #component-form');
  const navigation = form?.querySelector('.sidebar-nav');

  if (!form || !navigation) {
    return;
  }

  // Keep track of the prev state of the form
  let prevFormState;
  const getState = () => {
    return new URLSearchParams(new FormData(form)).toString();
  };

  form.addEventListener('changed', () => {
    prevFormState = getState();
  });
  prevFormState = getState();

  let confirmationReceived = false;
  navigation.addEventListener('click', async (event) => {
    const link = event.target.closest('a');
    if (!link?.href || link.href === '#' || link.target) {
      return;
    }

    // Allow modified clicks and links opening another browsing context.
    if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
      return;
    }

    // Compare the form state
    if (confirmationReceived || prevFormState === getState()) {
      return;
    }

    event.preventDefault();

    // Ask confirmation
    await confirmUnsavedChanges()
      .then((result) => {
        if (result) {
          confirmationReceived = true;
          link.click();
        }
      }).catch((e) => {
        console.error(e);
      });
  });
};

watchOnUnsavedChanges();
