import https from 'https';
import http from 'http';
import fs from 'fs';

const options = {
  key: fs.readFileSync('./key.pem'),
  cert: fs.readFileSync('./cert.pem')
};

const PROXY_PORT = 8000;
const LARAVEL_PORT = 8001;

// Create HTTPS server that proxies to Laravel
https.createServer(options, (req, res) => {
  const proxyReq = http.request({
    host: '127.0.0.1',
    port: LARAVEL_PORT,
    path: req.url,
    method: req.method,
    headers: {
      ...req.headers,
      'X-Forwarded-Proto': 'https',
      'X-Forwarded-For': req.socket.remoteAddress,
      'X-Forwarded-Host': req.headers.host
    }
  }, (proxyRes) => {
    res.writeHead(proxyRes.statusCode, proxyRes.headers);
    proxyRes.pipe(res);
  });

  req.pipe(proxyReq);

  proxyReq.on('error', (e) => {
    console.error(`Problem with proxy request: ${e.message}`);
    res.writeHead(502);
    res.end();
  });
}).listen(PROXY_PORT, () => {
  console.log(`HTTPS Proxy running at https://localhost:${PROXY_PORT}`);
  console.log(`Proxying to Laravel at http://127.0.0.1:${LARAVEL_PORT}`);
});
