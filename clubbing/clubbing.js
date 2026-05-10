const clubId = extractClubIdFromUrl();

function extractClubIdFromUrl() {
  let queryParam = window.location.href;
  return queryParam.replace(window.location.origin, '').replace('/clubbing/', '').replace('?', '');
}

function editSection(section) {
  const params = {page: clubId, section: section}
  const json = getJson(params);
  showDialog(json.html, json.buttons);
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

function showDialog(html, params) {
  window.scrollTo({ top: 0, behavior: 'smooth' });
  document.querySelector('.overlay').classList.add('visible');
  const dialog = document.querySelector('.dialog');

  let buttons = buildDialogButtons(params);

  dialog.innerHTML = `<div class="dialog-close" 
  onclick="closeDialog()"><i class="fas fa-close"></i></div>
  ${html}
  ${buttons}`;
  dialog.classList.add('visible');
}

function closeDialog() {
  document.querySelector('.overlay').classList.remove('visible');
  document.querySelector('.dialog').classList.remove('visible');
}

function buildDialogButtons(params) {
  let html = `<div class="dialogbuttons">`;
  if (params?.delete == 1) {
    html += `<div class="button" onclick="deleteForm()">Delete</div>`;
  }
  if (params?.close == 1) {
    html += `<div class="button" onclick="closeDialog()">Close</div>`;
  } 
  if (params?.cancel == 1) {
    html += `<div class="button" onclick="closeDialog()">Cancel</div>`;
  }
  if (params?.save == 1) {
    html += `<div class="button" onclick="saveForm()">Save</div>`;
  }
  html += '</div>';
  return html;
}
