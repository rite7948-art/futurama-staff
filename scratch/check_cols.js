const fs = require('fs');
const path = require('path');

const cacheDir = path.join(__dirname, '..', 'cache');
const files = fs.readdirSync(cacheDir);

files.forEach(file => {
    if (file.endsWith('.csv')) {
        const filePath = path.join(cacheDir, file);
        const content = fs.readFileSync(filePath, 'utf8');
        const lines = content.split('\n');
        
        lines.forEach((line, index) => {
            if (line.includes('6,5') || line.includes('6.5') || line.includes('13')) {
                if (line.includes('d1zzwx') || line.includes('asdfwq14') || line.includes('kimoka_star')) {
                    console.log(`[FOUND] ${file}:${index} -> ${line.substring(0, 150)}`);
                }
            }
        });
    }
});
