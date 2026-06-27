const fs = require('fs');
const js = fs.readFileSync('_test.js', 'utf8');
const lines = js.split('\n');
let lo = 0, hi = lines.length;
while (lo < hi) {
  const mid = (lo + hi) >> 1;
  const chunk = lines.slice(0, mid + 1).join('\n');
  try {
    new Function('DCLogic', 'StreamableLogic', 'React', chunk + '\nreturn Component;');
    lo = mid + 1;
  } catch (e) {
    hi = mid;
  }
}
console.log('Error at line ' + (lo + 1));
for (let i = Math.max(0, lo - 5); i < Math.min(lines.length, lo + 5); i++) {
  console.log((i + 1) + ': ' + lines[i].substring(0, 200));
}
