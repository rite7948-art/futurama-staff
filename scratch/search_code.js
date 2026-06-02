const fs = require('fs');
const path = require('path');

function searchDir(dir) {
    const files = fs.readdirSync(dir);
    files.forEach(file => {
        const fullPath = path.join(dir, file);
        if (fs.statSync(fullPath).isDirectory()) {
            if (file !== 'node_modules' && file !== '.git' && file !== 'cache') {
                searchDir(fullPath);
            }
        } else if (file.endsWith('.php') || file.endsWith('.js')) {
            const content = fs.readFileSync(fullPath, 'utf8');
            if (content.toLowerCase().includes('point') || content.toLowerCase().includes('поинт') || content.toLowerCase().includes('балл')) {
                console.log(`[CODE] Found in ${fullPath}`);
            }
        }
    });
}

searchDir(path.join(__dirname, '..'));
