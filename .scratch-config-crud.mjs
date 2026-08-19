import { chromium } from 'playwright';

const browser = await chromium.launch({ args: ['--no-sandbox'] });
const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });

const errors = [];
page.on('console', msg => { if (msg.type() === 'error') errors.push(msg.text()); });
page.on('pageerror', err => errors.push(String(err)));

await page.goto('http://localhost:8000/login');
await page.fill('input[type="email"]', 'test@example.com');
await page.fill('input[type="password"]', 'password');
await page.click('button[type="submit"]');
await page.waitForTimeout(1000);

await page.goto('http://localhost:8000/admin/config');
await page.waitForTimeout(600);

// CREATE
await page.click('button:has-text("Nova Configuração")');
await page.waitForTimeout(400);
await page.fill('input[wire\\:model="key"]', 'nome_sistema');
await page.fill('input[wire\\:model="value"]', 'Channelly AI');
await page.fill('textarea[wire\\:model="description"]', 'Nome exibido no sistema');
await page.click('button:has-text("Salvar")');
await page.waitForTimeout(800);
await page.screenshot({ path: '/tmp/claude-1000/-home-yousher-dev-web-channellyai/50e29dd2-7217-4ef2-a49d-fc1766a06908/scratchpad/config-01-created.png', fullPage: true });

// READ (list shows it)
const rowText = await page.locator('table tbody tr').first().innerText();
console.log('First row after create:', rowText);

// UPDATE
await page.click('button:has-text("Editar")');
await page.waitForTimeout(400);
await page.fill('input[wire\\:model="value"]', 'Channelly AI Editado');
await page.click('button:has-text("Salvar")');
await page.waitForTimeout(800);
await page.screenshot({ path: '/tmp/claude-1000/-home-yousher-dev-web-channellyai/50e29dd2-7217-4ef2-a49d-fc1766a06908/scratchpad/config-02-updated.png', fullPage: true });

const rowTextAfterEdit = await page.locator('table tbody tr').first().innerText();
console.log('First row after update:', rowTextAfterEdit);

// DELETE
page.on('dialog', dialog => dialog.accept());
await page.click('button:has-text("Excluir")');
await page.waitForTimeout(800);
await page.screenshot({ path: '/tmp/claude-1000/-home-yousher-dev-web-channellyai/50e29dd2-7217-4ef2-a49d-fc1766a06908/scratchpad/config-03-deleted.png', fullPage: true });

const rowCountAfterDelete = await page.locator('table tbody tr').count();
console.log('Row count after delete:', rowCountAfterDelete);

console.log('Console/page errors:', errors);

await browser.close();
