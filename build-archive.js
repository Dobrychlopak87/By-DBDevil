import fs from 'fs';
import { execSync } from 'child_process';
import path from 'path';

console.log("-> Starting full project build for shared hosting...");
try {
  execSync('npm run build', { stdio: 'inherit' });
  console.log("-> Frontend built successfully.");
} catch (e) {
  console.error("Build failed:", e);
  process.exit(1);
}

const rootHtaccess = `RewriteEngine On
RewriteCond %{HTTP_HOST} ^(www\\.)?dbdevstudio\\.pl$ [NC]
RewriteCond %{REQUEST_URI} !^/home/
RewriteRule ^(.*)$ /home/$1 [L]
`;

const homeHtaccess = `RewriteEngine On
RewriteBase /home/
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^(.*[^/])$ /$1/ [L,R=301]
RewriteRule ^index\\.html$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /home/index.html [L]
`;

try {
  fs.rmSync('archive_temp', { recursive: true, force: true });
} catch(e) {}
fs.mkdirSync('archive_temp');
fs.mkdirSync('archive_temp/home');

fs.writeFileSync('archive_temp/.htaccess', rootHtaccess);
fs.writeFileSync('archive_temp/home/.htaccess', homeHtaccess);

// Copy dist to archive_temp/home
execSync('cp -r dist/* archive_temp/home/');

console.log("-> Temporary structure created. Ready for zipping.");
