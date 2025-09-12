const dropArea = document.getElementById('dropArea');
const fileInput = document.getElementById('fileInput');
const logsDiv = document.getElementById('logs');
const progressBar = document.getElementById('progressBar');
const summaryDiv = document.getElementById('summary');
const filterSelect = document.getElementById('filterLogs');
let allLogs = [];
dropArea.addEventListener('click', ()=>fileInput.click());
dropArea.addEventListener('dragover', e=>{ e.preventDefault(); dropArea.classList.add('bg-gray-200'); });
dropArea.addEventListener('dragleave', e=>{ e.preventDefault(); dropArea.classList.remove('bg-gray-200'); });
dropArea.addEventListener('drop', e=>{
    e.preventDefault();
    dropArea.classList.remove('bg-gray-200');
    fileInput.files = e.dataTransfer.files;
});

filterSelect.addEventListener('change', renderLogs);

document.getElementById('importForm').addEventListener('submit', async function(e){
    e.preventDefault();
    logsDiv.innerHTML = '';
    summaryDiv.innerHTML = '';
    progressBar.style.width = '0%';
    allLogs = [];

    const formData = new FormData(this);

    const response = await fetch('process.php', {method:'POST', body:formData});
    const reader = response.body.getReader();
    const decoder = new TextDecoder();
    let done = false;

    while(!done){
        const {value, done: readerDone} = await reader.read();
        done = readerDone;
        if(value){
            const chunk = decoder.decode(value);
            chunk.split("\n").forEach(line=>{
                if(line.trim()){
                    try{
                        const json = JSON.parse(line);
                        if(json.log){
                            allLogs.push(json);
                            renderLogs();
                            if(json.total){
                                const percent = Math.round((json.current/json.total)*100);
                                progressBar.style.width = percent + '%';
                            }
                        }
                    }catch(e){}
                }
            });
        }
    }

    const final = await response.json();
    if(final.summary){
        summaryDiv.innerHTML = `
            <p class="font-bold">Total Rows: ${final.summary.total}</p>
            <p class="font-bold text-green-600">Inserted: ${final.summary.inserted}</p>
            <p class="font-bold text-yellow-600">Updated: ${final.summary.updated}</p>
        `;
    }
    if(final.error){
        summaryDiv.innerHTML = `<p class="text-red-600 font-bold">Error: ${final.error}</p>`;
    }
});

function renderLogs(){
    const filter = filterSelect.value;
    logsDiv.innerHTML = '';
    allLogs.forEach(l=>{
        if(filter==='all' || l.type===filter){
            let color='text-black';
            if(l.type==='insert') color='text-green-600';
            if(l.type==='update') color='text-yellow-600';
            if(l.type==='error') color='text-red-600';
            logsDiv.innerHTML += `<div class="${color}">${l.log}</div>`;
        }
    });
    logsDiv.scrollTop = logsDiv.scrollHeight;
}
