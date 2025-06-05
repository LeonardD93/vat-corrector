<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>VAT Corrector</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-900 p-6">
    <div class="max-w-3xl mx-auto bg-white p-6 rounded shadow space-y-10">
        <h1 class="text-3xl font-bold">VAT Corrector</h1>

        <!-- Upload CSV Form -->
        <div>
            <h2 class="text-xl font-semibold mb-2">1. Upload a CSV file with VAT numbers</h2>
            <form id="vat-upload-form" enctype="multipart/form-data" class="mb-4">
                <input type="file" name="file" accept=".csv" required class="mb-4">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Upload</button>
            </form>
            <div id="results" class="space-y-4"></div>
        </div>

        <hr>

        <!-- Single VAT Check Form -->
        <div>
            <h2 class="text-xl font-semibold mb-2">2. Test a single VAT number</h2>
            <form id="vat-check-form" class="mb-4 flex items-center gap-2 max-w-md">
                <input type="text" name="vat" placeholder="e.g. IT12345678901" required class="border rounded p-2 flex-1">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Test</button>
            </form>
            <div id="vat-check-result" class="text-sm"></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const uploadForm = document.getElementById('vat-upload-form');
            const uploadResults = document.getElementById('results');

            uploadForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                const fileInput = uploadForm.querySelector('input[type="file"]');
                const formData = new FormData();
                formData.append('file', fileInput.files[0]);

                uploadResults.innerHTML = 'Processing...';

                try {
                    const response = await fetch('/api/vat/upload', {
                        method: 'POST',
                        body: formData,
                    });

                    const data = await response.json();

                    if (!response.ok) throw new Error(data.message || 'Upload failed');

                    uploadResults.innerHTML = '';

                    const renderList = (title, list, color) => {
                        if (list.length === 0) return '';
                        let html = `<div><h3 class="font-semibold mb-1">${title}</h3><ul class="list-disc ml-6">`;
                        list.forEach(item => {
                            if (typeof item === 'string') {
                                html += `<li class="${color}">${item}</li>`;
                            } else {
                                html += `<li class="${color}">${item.original} → ${item.corrected} <span class="text-xs text-gray-500">(${item.note})</span></li>`;
                            }
                        });
                        html += '</ul></div>';
                        return html;
                    };

                    uploadResults.innerHTML += renderList('✅ Valid VAT Numbers', data.valid, 'text-green-700');
                    uploadResults.innerHTML += renderList('🔁 Corrected VAT Numbers', data.corrected, 'text-yellow-700');
                    uploadResults.innerHTML += renderList('❌ Invalid VAT Numbers', data.invalid, 'text-red-700');

                } catch (error) {
                    uploadResults.innerHTML = `<p class="text-red-700 font-semibold">${error.message}</p>`;
                }
            });

            // Single VAT check form
            const checkForm = document.getElementById('vat-check-form');
            const checkResult = document.getElementById('vat-check-result');

            checkForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                const vatInput = checkForm.querySelector('input[name="vat"]').value;
                checkResult.innerHTML = 'Checking...';

                try {
                    const res = await fetch('/api/vat/test', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ vat: vatInput })
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
        });
    </script>
</body>
</html>
