// const fetch = require('node-fetch');


async function test() {
    const sheetId = '1w2r_C3R7kh5CDvlehOHOjd3DPnvCMBQ9SnXZnB6t754';
    const gid = '822458528';
    const url = `https://docs.google.com/spreadsheets/d/${sheetId}/export?format=csv&gid=${gid}`;
    
    try {
        const res = await fetch(url);
        const csvData = await res.text();
        
        // Simulating PHP's fgetcsv
        const parseCSV = (data) => {
            const rows = [];
            let currentRow = [];
            let currentCell = '';
            let inQuotes = false;
            
            for (let i = 0; i < data.length; i++) {
                const char = data[i];
                const nextChar = data[i+1];
                
                if (char === '"' && inQuotes && nextChar === '"') {
                    currentCell += '"';
                    i++;
                } else if (char === '"') {
                    inQuotes = !inQuotes;
                } else if (char === ',' && !inQuotes) {
                    currentRow.push(currentCell);
                    currentCell = '';
                } else if ((char === '\r' || char === '\n') && !inQuotes) {
                    if (char === '\r' && nextChar === '\n') i++;
                    currentRow.push(currentCell);
                    rows.push(currentRow);
                    currentRow = [];
                    currentCell = '';
                } else {
                    currentCell += char;
                }
            }
            if (currentRow.length > 0 || currentCell !== '') {
                currentRow.push(currentCell);
                rows.push(currentRow);
            }
            return rows;
        };

        const rows = parseCSV(csvData);
        console.log(`Total rows: ${rows.length}`);
        
        const queue = [];
        rows.forEach((row, index) => {
            if (index < 5) return;
            
            const cleanRow = row.map(v => v.trim());
            const nick = cleanRow[3] || '';
            if (nick === '' || nick === 'Ник') return;
            
            const status = (cleanRow[7] || '').toLowerCase();
            if (status.includes('сдал')) return;
            
            if (status === '' || status === '-' || status === '—' || status === 'нет') {
                queue.push({
                    nickname: nick,
                    id: cleanRow[4],
                    date: cleanRow[5],
                    curator: cleanRow[6] || 'Не назначен',
                    status: status
                });
            }
        });
        
        console.log(`Queue size: ${queue.length}`);
        console.log('First 5 items:');
        console.log(queue.slice(0, 5));
        
        // Check for specific users from the screenshot
        const vvoide = queue.find(i => i.nickname.includes('vvoide'));
        console.log('Finding .vvoide:', vvoide);
        
    } catch (e) {
        console.error(e);
    }
}

test();
