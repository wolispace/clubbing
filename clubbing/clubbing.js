const clubId = extractClubIdFromUrl();

function extractClubIdFromUrl() {
  let queryParam = window.location.href;
  return queryParam.replace(window.location.origin, '').replace('/clubbing/', '').replace('?', '');
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