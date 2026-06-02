const fetch = require('node-fetch');

async function test() {
    const sheetId = '1w2r_C3R7kh5CDvlehOHOjd3DPnvCMBQ9SnXZnB6t754';
    const gid = '822458528';
    const url = `https://docs.google.com/spreadsheets/d/${sheetId}/export?format=csv&gid=${gid}`;
    
    try {
        const res = await fetch(url);
        const text = await res.text();
        const lines = text.split('\n');
        console.log("ROWS 6 to 15:");
        lines.slice(5, 15).forEach((line, i) => {
            console.log(`Row ${i + 6}:`, line.split(','));
        });
    } catch (e) {
        console.error(e);
    }
}

test();
