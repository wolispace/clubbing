const clubId = extractClubIdFromUrl();

function extractClubIdFromUrl() {
  let queryParam = window.location.href;
  return queryParam.replace(window.location.origin, '').replace('/clubbing/', '').replace('?', '');
}

async function editSection(section) {
  const buttons = [
    {caption: "Delete", 
      onclick: "deleteSection()",
    },
    {caption: "Save", 
      onclick: "saveSection()",
    },
    {caption: "Cancel", 
      onclick: "closeDialog()",
    }
  ];
  const params = {action: 'load', page: clubId, section: section, buttons: buttons}
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
  console.log(html);
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

