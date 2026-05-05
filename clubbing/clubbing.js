// The show starts here
document.addEventListener('DOMContentLoaded', async () => {
  const clubList = await getJson({action: "clubList"});
  console.log(clubList);
});

async function getJson(params) {
    const response = await fetch(`?j=${JSON.stringify(params)}`);
    const result = await response.json();
    return result;
}