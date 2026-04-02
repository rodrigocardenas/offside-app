#!/usr/bin/env node

/**
 * Automated test for Pre Match Modal
 * Tests the complete flow: search -> select -> submit
 */

const { chromium } = require('playwright');

(async () => {
    console.log('\n===== 🧪 PRE MATCH MODAL TEST =====\n');

    let browser;
    try {
        // Launch browser
        browser = await chromium.launch({ headless: false });
        const page = await browser.newPage();

        console.log('📂 Opening debug modal test page...');
        await page.goto('http://offsideclub.test/debug/modal-test', {
            waitUntil: 'networkidle'
        });

        // Wait for page to load
        await page.waitForLoadState('networkidle');

        console.log('✅ Page loaded');

        // Set up console logging
        page.on('console', msg => {
            const text = msg.text();
            if (text.includes('OPENING') || text.includes('SELECTING') || text.includes('SUBMITTING') || text.includes('ERROR') || text.includes('SUCCESS')) {
                console.log(`📋 Console: ${text}`);
            }
        });

        // Wait a bit and then click "Open Modal"
        console.log('\n1️⃣ Opening modal...');
        await page.click('button', { index: 0 });
        await page.waitForTimeout(500);

        // Type in search
        console.log('2️⃣ Typing "real" in search box...');
        await page.fill('#searchInput', 'real');
        await page.waitForTimeout(300);

        // Get dropdown items
        const matchItems = await page.$$('.match-item');
        console.log(`   Found ${matchItems.length} matches`);

        if (matchItems.length > 0) {
            // Click first match
            console.log('3️⃣ Clicking first match (Real Madrid vs Barcelona)...');
            await page.click('.match-item');
            await page.waitForTimeout(500);

            // Check if display shows selected match
            const displayText = await page.textContent('#display');
            console.log(`   Display shows: ${displayText}`);

            // Check hidden input value
            const hiddenValue = await page.inputValue('#matchInput');
            console.log(`   Hidden input value: "${hiddenValue}"`);

            if (!hiddenValue) {
                console.log('❌ ERROR: Hidden input is empty!');
                process.exit(1);
            }

            // Click submit
            console.log('4️⃣ Clicking Submit button...');

            // Capture any alert
            page.once('dialog', async dialog => {
                console.log(`\n📢 Alert appeared: "${dialog.message()}"`);
                await dialog.accept();
            });

            await page.click('button:has-text("Submit")');
            await page.waitForTimeout(500);

            console.log('\n✅ TEST PASSED!');
            console.log('   Value was properly stored and submitted');
            process.exit(0);
        } else {
            console.log('❌ No matches found in dropdown');
            process.exit(1);
        }

    } catch (error) {
        console.error('❌ Test failed:', error.message);
        process.exit(1);
    } finally {
        if (browser) {
            await browser.close();
        }
    }
})();
