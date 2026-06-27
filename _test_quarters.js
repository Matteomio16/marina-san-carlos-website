const fs = require('fs');
const js = fs.readFileSync('_test.js', 'utf8');
const origJs = fs.readFileSync('_test_orig.js', 'utf8');

// Test: try each quarter
const quarters = [0.25, 0.5, 0.75, 1.0];
for (const q of quarters) {
  const end = Math.floor(js.length * q);
  try {
    new Function('DCLogic', 'StreamableLogic', 'React', js.substring(0, end) + '\nreturn Component;');
    console.log(`First ${Math.round(q*100)}% (${end} chars): OK`);
  } catch(e) {
    console.log(`First ${Math.round(q*100)}% (${end} chars): ERROR: ${e.message}`);
  }
}

// Now do the same for original
for (const q of quarters) {
  const end = Math.floor(origJs.length * q);
  try {
    new Function('DCLogic', 'StreamableLogic', 'React', origJs.substring(0, end) + '\nreturn Component;');
    console.log(`ORIG first ${Math.round(q*100)}% (${end} chars): OK`);
  } catch(e) {
    console.log(`ORIG first ${Math.round(q*100)}% (${end} chars): ERROR: ${e.message}`);
  }
}
