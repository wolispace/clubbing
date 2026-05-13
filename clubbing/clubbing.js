const clubId = extractClubIdFromUrl();

function extractClubIdFromUrl() {
  let queryParam = window.location.href;
  return queryParam.replace(window.location.origin, '').replace('/clubbing/', '').replace('?', '');
}

async function editSection(section) {
  const buttons = [
    {caption: "Delete", 
      onclick: "deleteForm()",
    },
    {caption: "Save", 
      onclick: "saveForm()",
    },
    {caption: "Cancel", 
      onclick: "closeDialog()",
    }
  ];
  const params = {action: 'load', page: clubId, section: section, buttons: buttons}
  const json = await getJson(params);
  showDialog(json.html);
}

async function editPage(page) {
  const buttons = [
    {caption: "Delete", 
      onclick: "deleteForm()",
    },
    {caption: "Save", 
      onclick: "saveForm()",
    },
    {caption: "Cancel", 
      onclick: "closeDialog()",
    }
  ];
  const params = {action: 'load', page: page, buttons: buttons}
  const json = await getJson(params);
  showDialog(json.html);
}

// The show starts here
document.addEventListener('DOMContentLoaded', async () => {
 // sett things up
});

async function getJson(params) {
    const response = await fetch(`?j=${JSON.stringify(params)}`);
    const result = await response.json();
    return result;
}

function showDialog(html) {
  window.scrollTo({ top: 0, behavior: 'smooth' });
  document.querySelector('.overlay').classList.add('visible');
  const dialog = document.querySelector('.dialog');

  dialog.innerHTML = `<div class="dialog-close" 
  onclick="closeDialog()"><i class="fas fa-close"></i></div>
  ${html}`;
  dialog.classList.add('visible');
}

function closeDialog() {
  document.querySelector('.overlay').classList.remove('visible');
  document.querySelector('.dialog').classList.remove('visible');
}

async function saveForm() {
  const form = document.querySelector('form');
  const formData = new FormData(form);
  const response = await fetch('', { method: 'POST', body: formData });
  const result = await response.json();
  window.location.reload();
}

async function deleteForm() {
  if (!confirm('Are you sure you want to delete this?')) {
    return;
  }
  const section = document.querySelector('#section').value;
  const params = {action: 'delete', page: clubId, section: section};
  const json = await getJson(params);
  window.location.reload();
}