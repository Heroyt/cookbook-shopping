/// <reference types="node" />

import { readFileSync } from 'node:fs';
import { describe, expect, it } from 'vitest';

const readSource = (relativePath: string): string =>
    readFileSync(new URL(relativePath, import.meta.url), 'utf8');

describe('Vite development proxy', () => {
    it('publishes assets on the HTTPS application origin without a path prefix', () => {
        const config = readSource('../../vite.config.ts');

        expect(config).toContain("host: '0.0.0.0'");
        expect(config).toContain(
            "origin: 'https://nginx.cook-book-shopping-list.orb.local'",
        );
        expect(config).not.toContain(
            "origin: 'https://nginx.cook-book-shopping-list.orb.local/@vite'",
        );
        expect(config).toContain("protocol: 'wss'");
        expect(config).toContain('clientPort: 443');
        expect(config).toContain("path: '/@vite/ws'");
    });

    it('proxies Vite client and source paths before Laravel handles the page', () => {
        const config = readSource('../../docker/dev/nginx.conf');

        expect(config).toContain(
            'location ~ ^/(resources|node_modules|@vite|@fs|@id)',
        );
        expect(config).toContain('proxy_pass http://laravel:5173;');
        expect(config).toContain('proxy_set_header Upgrade $http_upgrade;');
        expect(config.indexOf('location ~ ^/(resources')).toBeLessThan(
            config.indexOf('location / {'),
        );
    });
});
