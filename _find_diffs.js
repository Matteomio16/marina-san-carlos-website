const fs = require('fs');
const a = fs.readFileSync('_test_orig.js', 'utf8');
const b = fs.readFileSync('_test.js', 'utf8');

console.log('Original length:', a.length);
console.log('Current length:', b.length);

// Find first structural diff (skipping normal text additions)
let aIdx = 0, bIdx = 0;
let diffs = [];
while (aIdx < a.length && bIdx < b.length) {
  if (a[aIdx] === b[bIdx]) {
    aIdx++;
    bIdx++;
  } else {
    // Found a diff - record context
    diffs.push({
      aPos: aIdx,
      bPos: bIdx,
      aCtx: a.substring(Math.max(0, aIdx - 30), aIdx + 60),
      bCtx: b.substring(Math.max(0, bIdx - 30), bIdx + 60)
    });
    // Try to resync: skip ahead in b until we find what a has
    let resynced = false;
    for (let skip = 1; skip < 200 && bIdx + skip < b.length; skip++) {
      if (a[aIdx] === b[bIdx + skip]) {
        bIdx += skip;
        resynced = true;
        break;
      }
    }
    if (!resynced) {
      // Skip 1 in both
      aIdx++;
      bIdx++;
    }
    if (diffs.length >= 20) break;
  }
}

console.log('\nFound', diffs.length, 'diff points');
diffs.forEach((d, i) => {
  console.log('\n--- Diff ' + (i+1) + ' ---');
  console.log('ORIG pos=' + d.aPos + ':', JSON.stringify(d.aCtx));
  console.log('CURR pos=' + d.bPos + ':', JSON.stringify(d.bCtx));
});
