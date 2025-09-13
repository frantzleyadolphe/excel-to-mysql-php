<?php
    require __DIR__ . '/../vendor/autoload.php';
    require __DIR__ . '/../src/ExcelToMySQL.php';

    use Frantzley\ExcelToMySQL;

    $logs = [
        ["type" => "success", "title" => "Processing Request", "message" => "Your data is being processed. This may take a moment."],
        ["type" => "warning", "title" => "Incomplete Setup", "message" => "Some fields are missing. Please review and try again."],
        ["type" => "error", "title" => "Update Failed", "message" => "Could not update the file. Please try again."],
    ];

    function getLogs($type)
    {
        return match ($type) {
            "success" => "bg-blue-100 border-l-4 border-blue-500 text-blue-700",
            "warning" => "bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700",
            "error"   => "bg-gray-100 border-l-4 border-gray-500 text-gray-700",
            default   => "bg-white border-l-4 border-gray-300 text-gray-800"
        };
    }

    function getIcon($type)
    {
        return match ($type) {
            "success" => "✔️",
            "warning" => "⚠️",
            "error"   => "❌",
            default   => "ℹ️"
        };
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Excel to MySQL</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 p-6">
    <div class="max-w-3xl mx-auto bg-white p-6 rounded-xl shadow relative ">
        <h1 class="text-xl font-bold mb-4 text-center">Pran done nan yon fichye excel pou mete nan yon baz done MySQL 📊⏳</h1>

        <form id="uploadForm" class="space-y-4">
            <input type="file" name="excel_file" accept=".xls,.xlsx" class="block w-full text-sm text-gray-600
                file:mr-4 file:py-2 file:px-4
                file:rounded-lg file:border-0
                file:text-sm file:font-semibold
                file:bg-green-200 file:text-black-700
                hover:file:bg-green-700 hover:file:text-white" required>

            <input type="text" name="table_name" placeholder="Non tab la pou mete done yo" class="w-full p-2 border rounded-lg" required>
            <input type="text" name="unique_key" placeholder="kle kolon inik lan (opsyonel)" class="w-full p-2 border rounded-lg" >

            <button type="submit" class="bg-green-200 text-black px-4 py-2 rounded-lg w-full hover:text-white hover:bg-green-700 ">Lanse enpotasyon an</button>
        </form>

        <!-- Filter logs -->
        <div class="mt-4 flex items-center space-x-2">
            <label for="logFilter" class="font-semibold">Filtre logs:</label>
            <select id="logFilter" class="p-1 border rounded-lg text-black-700">
                <option value="all" selected >Tout</option>
                <option value="insert">Insert</option>
                <option value="update">Update</option>
                <option value="error">Error</option>
            </select>
        </div>

        <!-- Progress bar -->
        <div class="mt-4">
            <label class="font-bold mb-2 block">Ba de pwogresyon ⏳</label>
            <div class="w-full bg-gray-200 rounded-lg h-5 overflow-hidden">
                <div id="progressBar" class="bg-green-900 h-5 w-0 transition-all duration-500"></div>
            </div>
            <label class="text-lg text-black font-bold mt-2 block">Rapo sou rezilta yo 📝</label>
            <div id="logs" class="mt-4  overflow-y-auto p-2 border rounded-lg bg-green-100"></div>
        </div>


    </div>

<script src="app.js"></script>
</body>
</html>
