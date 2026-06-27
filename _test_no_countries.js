const fs = require('fs');
const js = fs.readFileSync('_test.js', 'utf8');
try {
  new Function('DCLogic', 'StreamableLogic', 'React', js + '\nreturn Component;');
  console.log('CURRENT: OK');
} catch(e) {
  console.log('CURRENT ERROR:', e.message);
  
  // Try removing COUNTRIES
  const noCountries = js.replace(/var COUNTRIES = \[[^\]]+\];/s, 'var COUNTRIES = [];');
  try {
    new Function('DCLogic', 'StreamableLogic', 'React', noCountries + '\nreturn Component;');
    console.log('WITHOUT COUNTRIES: OK');
  } catch(e2) {
    console.log('WITHOUT COUNTRIES still errors:', e2.message);
  }
}
