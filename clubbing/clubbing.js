// The show starts here
document.addEventListener('DOMContentLoaded', async () => {
  const clubList = getJson({});
  console.log(clubList);
});

async function getJson(params) {
    fetch(`?j=${JSON.stringify(params)}`).then(result => {
    if(result.ok) {
      return result;
    }});
}