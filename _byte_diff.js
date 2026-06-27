const fs = require('fs');
const orig = fs.readFileSync('_test_orig.js', 'utf8');
const curr = fs.readFileSync('_test.js', 'utf8');

// Find first byte that differs
for (let i = 0; i < Math.max(orig.length, curr.length); i++) {
  if (orig.charCodeAt(i) !== curr.charCodeAt(i)) {
    console.log('First diff at byte/char position ' + i);
    console.log('ORIG char code:', orig.charCodeAt(i), 'hex:', orig.charCodeAt(i).toString(16));
    console.log('CURR char code:', curr.charCodeAt(i), 'hex:', curr.charCodeAt(i).toString(16));
    console.log('ORIG context:', JSON.stringify(orig.substring(Math.max(0, i - 50), i + 50)));
    console.log('CURR context:', JSON.stringify(curr.substring(Math.max(0, i - 50), i + 50)));
    
    // Now check if this is a structural diff or just encoding
    // Find next same char
    let origOffset = i, currOffset = i;
    let found = false;
    for (let look = 1; look < 200; look++) {
      if (orig.charCodeAt(origOffset + look) === curr.charCodeAt(currOffset)) {
        console.log('Resyncs after', look, 'original chars');
        found = true;
        break;
      }
    }
    if (!found) {
      // Check if just encoding difference
      console.log('ORIG chars:', [orig.charCodeAt(i), orig.charCodeAt(i+1), orig.charCodeAt(i+2)].join(','));
      console.log('CURR chars:', [curr.charCodeAt(i), curr.charCodeAt(i+1), curr.charCodeAt(i+2)].join(','));
    }
    break;
  }
}
