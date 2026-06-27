const fs = require('fs');
const orig = fs.readFileSync('_test_orig.js', 'utf8').split('\n');
const curr = fs.readFileSync('_test.js', 'utf8').split('\n');

// Find first line that differs
for (let i = 0; i < Math.max(orig.length, curr.length); i++) {
  if (orig[i] !== curr[i]) {
    console.log('First diff at line ' + (i + 1));
    console.log('ORIG: ' + (orig[i] || '<missing>').substring(0, 200));
    console.log('CURR: ' + (curr[i] || '<missing>').substring(0, 200));
    break;
  }
}
