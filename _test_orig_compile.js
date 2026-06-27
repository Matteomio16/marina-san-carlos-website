const fs = require('fs');
const js = fs.readFileSync('_test_orig.js', 'utf8');
try {
  new Function('DCLogic', 'StreamableLogic', 'React', js + '\nreturn Component;');
  console.log('ORIGINAL: OK');
} catch(e) {
  console.log('ORIGINAL ERROR:', e.message);
}
