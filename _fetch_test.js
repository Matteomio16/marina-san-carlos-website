const https = require('https');
const fs = require('fs');

https.get('https://marina-san-carlos-website.vercel.app/', function(res) {
  let d = '';
  res.on('data', function(c) { d += c; });
  res.on('end', function() {
    let i = d.indexOf('data-dc-script');
    if (i < 0) { console.log('No data-dc-script found'); return; }
    let j = d.indexOf('>', i) + 1;
    let k = d.indexOf('</script>', j);
    let js = d.substring(j, k);
    fs.writeFileSync('_deployed.js', js, 'utf8');
    console.log('Deployed JS:', js.length, 'chars');
    try {
      new Function('DCLogic', 'StreamableLogic', 'React', js + '\nreturn Component;');
      console.log('DEPLOYED: OK');
    } catch(e) {
      console.log('DEPLOYED ERROR:', e.message);
    }
  });
});
