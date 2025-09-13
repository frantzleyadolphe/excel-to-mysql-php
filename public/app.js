const form = document.getElementById('uploadForm');
const progressBar = document.getElementById('progressBar');
const logsDiv = document.getElementById('logs');
const logFilter = document.getElementById('logFilter');

const importModal = document.getElementById('importModal');
const closeModal = document.getElementById('closeModal');

let logs = [];

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
                        logs.push({text: `Import fini! Inserted: ${data.summary.inserted}, Updated: ${data.summary.updated}`, type: 'info'});
                        
                        // Reset form ak progress bar
                        form.reset();
                        progressBar.style.width = '0%';
                    }
                    renderLogs();
                    if (data.current) updateProgress(data.current, data.total || total);
                }
            } catch(err) {
                console.error(err);
            }
              // Reset form ak progress bar
                        form.reset();
        });
    }
});



// Fonksyon pou rander logs selon filtre
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
    if (type === 'insert') color = 'text-red-600';
    if (type === 'update') color = 'text-yellow-600';
    if (type === 'error') color = 'text-red-600';
    if (type === 'info') color = 'text-blue-600';

    div.className = `${color} opacity-0 transition-opacity duration-700`;
    div.textContent = text;
    logsDiv.appendChild(div);

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
