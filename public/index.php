<?php
    require __DIR__ . '/../vendor/autoload.php';
    require __DIR__ . '/../src/ExcelToMySQL.php';

    use Frantzley\ExcelToMySQL;

    // $logs = [
    //     ["type" => "success", "title" => "Processing Request", "message" => "Your data is being processed. This may take a moment."],
    //     ["type" => "warning", "title" => "Incomplete Setup", "message" => "Some fields are missing. Please review and try again."],
    //     ["type" => "error", "title" => "Update Failed", "message" => "Could not update the file. Please try again."],
    // ];

    // function getLogs($type)
    // {
    //     return match ($type) {
    //         "success" => "bg-blue-100 border-l-4 border-blue-500 text-blue-700",
    //         "warning" => "bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700",
    //         "error"   => "bg-gray-100 border-l-4 border-gray-500 text-gray-700",
    //         default   => "bg-white border-l-4 border-gray-300 text-gray-800"
    //     };
    // }

    // function getIcon($type)
    // {
    //     return match ($type) {
    //         "success" => "✔️",
    //         "warning" => "⚠️",
    //         "error"   => "❌",
    //         default   => "ℹ️"
    //     };
    // }
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
   <div class="max-w-6xl mx-auto bg-white p-6 rounded-xl shadow relative">
    <h1 class="text-xl font-bold mb-4 text-center">
        Pran done nan yon fichye excel pou mete nan yon baz done MySQL 📊⏳
    </h1>

    <!-- Grid pou mete form + filtre/progress kote a kote -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Form Section -->
        <form id="uploadForm" class="space-y-4">

            <!-- Bouton pou DB settings -->
            <button type="button" id="toggleDbSettings"
                    class="w-full bg-blue-100 px-4 py-2 rounded-lg font-semibold hover:bg-blue-200 text-left">
                ⚙️ Paramèt baz done
            </button>

            <!-- DB Settings -->
            <div id="dbSettings" class="hidden border p-4 rounded-lg bg-gray-50">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-sm mb-1">
                            DB Host <span title="Adrès sèvè DB (souvan: localhost)" class="cursor-help text-blue-600">❓</span>
                        </label>
                        <input type="text" name="db_host" value=""
                               class="w-full p-2 border rounded-lg" required>
                    </div>
                    <div>
                        <label class="block font-semibold text-sm mb-1">
                            DB User <span title="Non itilizatè MySQL ou (souvan: root)" class="cursor-help text-blue-600">❓</span>
                        </label>
                        <input type="text" name="db_user" value=""
                               class="w-full p-2 border rounded-lg" required>
                    </div>
                    <div>
                        <label class="block font-semibold text-sm mb-1">
                            DB Password <span title="Modpas MySQL ou (kite vid si pa genyen)" class="cursor-help text-blue-600">❓</span>
                        </label>
                        <input type="password" name="db_pass" value=""
                               class="w-full p-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block font-semibold text-sm mb-1">
                            DB Name <span title="Non baz done kote w ap enpòte done yo" class="cursor-help text-blue-600">❓</span>
                        </label>
                        <input type="text" name="db_name"
                               class="w-full p-2 border rounded-lg" required>
                    </div>
                </div>
            </div>

            <!-- File upload -->
            <div>
                <label class="block font-semibold text-sm mb-1">
                    Fichye Excel
                    <span title="Chwazi fichye .xls oswa .xlsx pou enpòte" class="cursor-help text-blue-600">❓</span>
                </label>
                <input type="file" name="excel_file" accept=".xls,.xlsx"
                       class="block w-full text-sm text-gray-600
                        file:mr-4 file:py-2 file:px-4
                        file:rounded-lg file:border-0
                        file:text-sm file:font-semibold
                        file:bg-green-200 file:text-black-700
                        hover:file:bg-green-700 hover:file:text-white" required>
            </div>

            <!-- Table name + Unique key -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border p-4 rounded-lg bg-gray-50">
                <div>
                    <label class="block font-semibold text-sm mb-1">
                        Non Tab la
                        <span title="Non tab nan MySQL kote done yo ap enpòte (kreye otomatik si pa egziste)" class="cursor-help text-blue-600">❓</span>
                    </label>
                    <input type="text" name="table_name" placeholder="ex: users"
                           class="w-full p-2 border rounded-lg" required>
                </div>
                <div>
                    <label class="block font-semibold text-sm mb-1">
                        Kle Kolòn Inik
                        <span title="Kolòn inik pou evite doublon (opsyonèl)" class="cursor-help text-blue-600">❓</span>
                    </label>
                    <input type="text" name="unique_key" placeholder="ex: email"
                           class="w-full p-2 border rounded-lg">
                </div>
            </div>

            <button type="submit"
                    class="bg-green-200 text-black px-4 py-2 rounded-lg w-full hover:text-white hover:bg-green-700">
                Lanse enpotasyon an
            </button>
        </form>

  <!-- Filtre + Progress + Logs -->
<div class="space-y-4">
    <!-- Filtre logs -->
    <div>
        <label for="logFilter" class="font-semibold block mb-1">Filtre logs:</label>
        <select id="logFilter" class="p-2 border rounded-lg w-full text-black-700">
            <option value="all" selected>Tout</option>
            <option value="insert">Insert</option>
            <option value="update">Update</option>
            <option value="error">Error</option>
        </select>
    </div>

    <!-- Progress bar -->
    <div>
        <label class="font-bold mb-2 block">Ba de pwogresyon ⏳</label>
        <div class="w-full bg-gray-200 rounded-lg h-5 overflow-hidden">
            <div id="progressBar" class="bg-green-900 h-5 w-0 transition-all duration-500"></div>
        </div>
    </div>

    <!-- Logs output -->
    <div>
        <label class="text-lg text-black font-bold block mb-2">Rapo sou rezilta yo 📝</label>
        <div id="logs" class="overflow-y-auto h-28 p-2 border rounded-lg bg-green-100"></div>
    </div>
</div>



<script src="app.js"></script>
</body>
</html>
