const form = document.getElementById('uploadForm');
const progressBar = document.getElementById('progressBar');
const logsDiv = document.getElementById('logs');
const logFilter = document.getElementById('logFilter');

let logs = [];

const sheetSelectorDiv = document.getElementById('sheetSelection');
const sheetSelect = sheetSelectorDiv.querySelector('select[name="sheet_name"]');
const excelFileInput = form.querySelector('input[name="excel_file"]');
const toggleBtn = document.getElementById("toggleDbSettings");
const dbSettings = document.getElementById("dbSettings");

excelFileInput.addEventListener('change', async () => {
    const file = excelFileInput.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('excel_file', file);

    try {
        const response = await fetch('get_sheets.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.sheets && data.sheets.length > 0) {
            sheetSelect.innerHTML = '<option value="">-- Chwazi yon sheet --</option>';
            data.sheets.forEach(sheet => {
                const option = document.createElement('option');
                option.value = sheet;
                option.textContent = sheet;
                sheetSelect.appendChild(option);
            });
            sheetSelectorDiv.classList.remove('hidden');
        } else {
            sheetSelectorDiv.classList.add('hidden');
        }
    } catch (err) {
        console.error('Erè pandan li lis sheet yo:', err);
        sheetSelectorDiv.classList.add('hidden');
    }
});

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    logsDiv.innerHTML = '';
    logs = [];
    progressBar.style.width = '0%';

    const formData = new FormData(form);

    const response = await fetch('process.php', {
        method: 'POST',
        body: formData
    });

    // Nou pran sheet seleksyone a
    const selectedSheet = sheetSelect.value;
    if (selectedSheet) {
        logs.push({text: `Sheet ki te chwazi pou import: "${selectedSheet}" 📄`, type: 'info'});
        renderLogs();
    }


    const reader = response.body.getReader();
    const decoder = new TextDecoder();
    let received = '';
    let total = 0;

    while (true) {
        const {done, value} = await reader.read();
        if (done) break;
        received += decoder.decode(value, {stream: true});
        const lines = received.split("\n");
        received = lines.pop();

        lines.forEach(line => {
            if (!line.trim()) return;
            try {
                const data = JSON.parse(line);
                if (data.log || data.summary) {
                    if (data.log) logs.push({text: data.log, type: data.type});
                    if (data.summary) {
                        logs.push({text: `Import fini! Inserted: ${data.summary.inserted}, Exists: ${data.summary.exists}`, type: 'info'});
                        form.reset();
                        progressBar.style.width = '0%';
                        sheetSelectorDiv.classList.add('hidden');
                    }
                    renderLogs();
                    if (data.current) updateProgress(data.current, data.total || total);
                }
            } catch(err) {
                console.error(err);
            }
        });
    }
});



toggleBtn.addEventListener("click", () => {
    dbSettings.classList.toggle("hidden");
});

// Rander logs selon filtre
function renderLogs() {
    const filter = logFilter.value;
    logsDiv.innerHTML = '';
    logs.forEach(log => {
        if (filter !== 'all' && log.type !== filter) return;
        addLogDiv(log.text, log.type);
    });
}

function addLogDiv(text, type) {
    const div = document.createElement('div');
    let color = 'text-gray-800';
    let icon = '';

    switch(type) {
        case 'insert': color = 'text-green-600'; icon = '✔️'; break;
        case 'exists': color = 'text-red-600'; icon = '⚠️'; break;
        case 'update': color = 'text-yellow-600'; icon = '📝'; break;
        case 'error': color = 'text-red-900'; icon = '❌'; break;
        case 'info': color = 'text-blue-600'; icon = 'ℹ️'; break;
        default: color = 'text-gray-800'; icon = '•';
    }

    div.className = `flex items-center gap-2 ${color} bg-white/20 backdrop-blur-md shadow-md opacity-0 transition-opacity duration-700`;
    div.innerHTML = `<div class="flex items-center gap-2 bg-white w-full h-8 justify-center rounded-lg mb-2"><span>${icon}</span> <span>${text}</span></div>`;
    
    logsDiv.appendChild(div);
    const logCount = logsDiv.children.length;
    logsDiv.style.height = Math.min(logCount * 34, 300) + 'px';

    setTimeout(() => {
        div.classList.add('opacity-100');
        logsDiv.scrollTop = logsDiv.scrollHeight;
    }, 50);
}

function updateProgress(current, total) {
    if (!total) return;
    const percent = Math.round((current / total) * 100);
    progressBar.style.width = percent + '%';
}

logFilter.addEventListener('change', renderLogs);
