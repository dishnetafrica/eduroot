-- ============================================================
-- Add migration_log table to eduroot_master
-- ============================================================
USE `eduroot_master`;

CREATE TABLE IF NOT EXISTS `migration_log` (
    `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `sql_preview` VARCHAR(500)    NOT NULL,
    `total`       INT             NOT NULL,
    `success`     INT             NOT NULL,
    `failed`      INT             NOT NULL DEFAULT 0,
    `errors`      JSON            NULL,
    `run_by`      VARCHAR(100)    NULL,
    `run_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
-- Nginx wildcard vhost (nginx.conf or sites-available)
-- Place this on your server — handles ALL subdomains pointing
-- to the same document root.
-- ============================================================

/*
server {
    listen 80;
    server_name *.eduroot.in eduroot.in;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name *.eduroot.in eduroot.in;

    # Wildcard SSL cert (get from Let's Encrypt with certbot --wildcard)
    ssl_certificate     /etc/letsencrypt/live/eduroot.in/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/eduroot.in/privkey.pem;

    root /var/www/eduroot;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?/$request_uri;
    }

    location ~ \.php$ {
        fastcgi_pass   unix:/run/php/php8.1-fpm.sock;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include        fastcgi_params;
    }

    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2)$ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
    }
}
*/


-- ============================================================
-- Apache wildcard vhost (.htaccess already correct, add this
-- to your Apache sites-enabled/000-default.conf)
-- ============================================================

/*
<VirtualHost *:80>
    ServerName eduroot.in
    ServerAlias *.eduroot.in
    Redirect permanent / https://eduroot.in/
</VirtualHost>

<VirtualHost *:443>
    ServerName eduroot.in
    ServerAlias *.eduroot.in

    DocumentRoot /var/www/eduroot
    DirectoryIndex index.php

    SSLEngine on
    SSLCertificateFile    /etc/letsencrypt/live/eduroot.in/cert.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/eduroot.in/privkey.pem
    SSLCertificateChainFile /etc/letsencrypt/live/eduroot.in/chain.pem

    <Directory /var/www/eduroot>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
*/


-- ============================================================
-- DNS: set ONE wildcard record — never touch again
-- ============================================================
-- In your DNS provider (Cloudflare, GoDaddy, etc):
--
--   Type   Name         Value              TTL
--   A      @            YOUR_SERVER_IP     Auto
--   A      *            YOUR_SERVER_IP     Auto
--   CNAME  www          eduroot.in         Auto
--
-- The *.eduroot.in wildcard catches every subdomain automatically.
-- When you provision greenvalley.eduroot.in, DNS is already working.
-- No DNS changes needed per school.
-- ============================================================


-- ============================================================
-- Let's Encrypt wildcard SSL (run once, auto-renews)
-- ============================================================
-- certbot certonly \
--   --dns-cloudflare \
--   --dns-cloudflare-credentials ~/.secrets/cloudflare.ini \
--   -d eduroot.in \
--   -d "*.eduroot.in"
--
-- This gives you a single cert that covers ALL subdomains.
-- ============================================================
