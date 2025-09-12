<?php
    require __DIR__ . '/../vendor/autoload.php';
    require __DIR__ . '/../src/ExcelToMySQL.php';

    use Frantzley\ExcelToMySQL;

    // Ou ka mete pwosesis AJAX isit la, oswa separe nan process.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Excel to MySQL</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-3xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-2xl font-bold mb-4">Upload Excel pou MySQL</h1>

        <form id="uploadForm" class="space-y-4">
            <input type="file" name="excel_file" accept=".xls,.xlsx" class="block w-full text-sm text-gray-600
                file:mr-4 file:py-2 file:px-4
                file:rounded file:border-0
                file:text-sm file:font-semibold
                file:bg-blue-100 file:text-blue-700
                hover:file:bg-blue-200" required>

            <input type="text" name="table_name" placeholder="Non tab la" class="w-full p-2 border rounded" required>
            <input type="text" name="unique_key" placeholder="Kolòn kle inik (opsyonèl)" class="w-full p-2 border rounded">

            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Kòmanse Import</button>
        </form>

        <!-- Filter logs -->
        <div class="mt-4 flex items-center space-x-2">
            <label for="logFilter" class="font-semibold">Filtre logs:</label>
            <select id="logFilter" class="p-1 border rounded">
                <option value="all" selected>Tout</option>
                <option value="insert">Insert</option>
                <option value="update">Update</option>
                <option value="error">Error</option>
            </select>
        </div>

        <!-- Progress bar -->
        <div class="mt-4">
            <div class="w-full bg-gray-200 rounded h-4 overflow-hidden">
                <div id="progressBar" class="bg-green-500 h-4 w-0 transition-all duration-500"></div>
            </div>
            <div id="logs" class="mt-4 h-64 overflow-y-auto p-2 border rounded bg-gray-50"></div>
        </div>
    </div>

<script src="app.js"></script>
</body>
</html>
