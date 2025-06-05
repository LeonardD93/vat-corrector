<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <title>VAT Corrector</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100 text-gray-900 p-6">
    <div class="max-w-screen-xl mx-auto bg-white p-6 rounded shadow space-y-10">
        <h1 class="text-3xl font-bold text-center">VAT Corrector</h1>

        <!-- Single VAT Check Form -->
        <div>
            <h2 class="text-xl font-semibold mb-2">1. Check a single VAT number</h2>
            <form id="vat-check-form" class="mb-4 flex items-center gap-2 max-w-md">
                <input type="text" name="vat" placeholder="e.g. IT12345678901" required class="border rounded p-2 flex-1">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Check</button>
            </form>
            <div id="vat-check-result" class="text-sm"></div>
        </div>

        <hr>

        <!-- Upload CSV Form -->
        <div>
            <h2 class="text-xl font-semibold mb-2">2. Upload a CSV file with VAT numbers</h2>
            <form id="vat-upload-form" enctype="multipart/form-data" class="mb-4">
                <label class="block mb-2 font-medium">Choose a CSV file</label>
                <input type="file" name="file" accept=".csv"
                    class="block w-full text-sm text-gray-700 border border-gray-300 rounded-lg cursor-pointer focus:outline-none focus:ring focus:border-blue-500 mb-4" />
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Upload</button>
            </form>
        </div>

        <!-- Download Buttons -->
        <div id="download-section" class="hidden space-y-2">
            <h3 class="font-semibold">📥 Download Results</h3>
            <div class="flex gap-4 flex-wrap">
                <button data-type="valid" class="btn-download bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">Download Valid</button>
                <button data-type="corrected" class="btn-download bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded">Download Corrected</button>
                <button data-type="invalid" class="btn-download bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">Download Invalid</button>
            </div>
        </div>

        <!-- Results -->
        <div id="results" class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-10"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // VAT check (single)
            const checkForm = document.getElementById('vat-check-form');
            const checkResult = document.getElementById('vat-check-result');

            checkForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                const vat = checkForm.querySelector('input[name="vat"]').value;
                checkResult.innerHTML = 'Checking...';

                try {
                    const res = await fetch('/api/vat/check', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            vat
                        })
                    });

                    const data = await res.json();

                    if (!res.ok) throw new Error(data.message || 'Validation failed');

                    if (data.status === 'valid') {
                        checkResult.innerHTML = `<p class="text-green-700 font-semibold">✅ VAT is valid: ${data.vat}</p>`;
                    } else if (data.status === 'corrected') {
                        checkResult.innerHTML = `<p class="text-yellow-700 font-semibold">🔁 Corrected to: ${data.corrected} <span class="text-sm text-gray-600">(${data.note})</span></p>`;
                    } else {
                        checkResult.innerHTML = `<p class="text-red-700 font-semibold">❌ Invalid VAT. Reason: ${data.reason}</p>`;
                    }
                } catch (error) {
                    checkResult.innerHTML = `<p class="text-red-700 font-semibold">${error.message}</p>`;
                }
            });

            // CSV Upload
            const uploadForm = document.getElementById('vat-upload-form');
            const results = document.getElementById('results');
            const downloadSection = document.getElementById('download-section');

            uploadForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                const fileInput = uploadForm.querySelector('input[type="file"]');
                const formData = new FormData();
                formData.append('file', fileInput.files[0]);

                results.innerHTML = 'Processing...';
                downloadSection.classList.add('hidden');

                try {
                    const response = await fetch('/api/vat/upload', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (!response.ok) throw new Error(data.message || 'Upload failed');

                    const createList = (title, list, color) => {
                        if (!list || list.length === 0) return '';
                        let html = `<div><h3 class="text-lg font-semibold mb-2 ${color}">${title}</h3><ul class="list-disc ml-6 text-sm space-y-1">`;
                        list.forEach(item => {
                            if (typeof item === 'string') {
                                html += `<li>${item}</li>`;
                            } else if (item.corrected) {
                                html += `<li>${item.original} → ${item.corrected} <span class="text-xs text-gray-500">(${item.note})</span></li>`;
                            } else if (item.reason) {
                                html += `<li>${item.vat || '—'} (${item.reason})</li>`;
                            } else {
                                html += `<li>${item.vat}</li>`;
                            }
                        });
                        html += '</ul></div>';
                        return html;
                    };

                    results.innerHTML = `
                        ${createList('✅ Valid VAT Numbers', data.valid, 'text-green-700')}
                        ${createList('🔁 Corrected VAT Numbers', data.corrected, 'text-yellow-700')}
                        ${createList('❌ Invalid VAT Numbers', data.invalid, 'text-red-700')}
                    `;

                    // Gestione download solo se presente uuid
                    if (data.uuid) {
                        downloadSection.classList.remove('hidden');

                        document.querySelectorAll('.btn-download').forEach(button => {
                            button.onclick = async () => {
                                const type = button.dataset.type;
                                const url = `/api/vat/export/${data.uuid}/${type}`;

                                try {
                                    const res = await fetch(url);
                                    if (!res.ok) throw new Error('Download failed');

                                    const blob = await res.blob();
                                    const downloadUrl = URL.createObjectURL(blob);
                                    const a = document.createElement('a');
                                    a.href = downloadUrl;
                                    a.download = `${type}_vat.csv`;
                                    document.body.appendChild(a);
                                    a.click();
                                    a.remove();
                                    URL.revokeObjectURL(downloadUrl);
                                } catch (err) {
                                    alert('❌ Download error: ' + err.message);
                                }
                            };
                        });
                    }
                } catch (error) {
                    results.innerHTML = `<p class="text-red-700 font-semibold">${error.message}</p>`;
                }
            });
        });
    </script>
</body>

</html>