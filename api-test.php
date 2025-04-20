<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test API SNCF Vêtements</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .endpoint {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .response {
            margin-top: 10px;
            padding: 10px;
            background-color: #f5f5f5;
            border-radius: 5px;
            white-space: pre-wrap;
        }
        button {
            padding: 8px 16px;
            background-color: #00005A;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #000080;
        }
        input {
            padding: 8px;
            margin-right: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .error {
            color: red;
        }
        .success {
            color: green;
        }
    </style>
</head>
<body>
    <h1>Test de l'API SNCF Vêtements</h1>

    <div class="endpoint">
        <h2>GET /api/produits</h2>
        <button onclick="testGetProduits()">Tester</button>
        <div id="produits-response" class="response"></div>
    </div>

    <div class="endpoint">
        <h2>GET /api/stock</h2>
        <button onclick="testGetStock()">Tester</button>
        <div id="stock-response" class="response"></div>
    </div>

    <div class="endpoint">
        <h2>GET /api/commandes</h2>
        <input type="number" id="user-id" placeholder="ID Utilisateur" value="1">
        <button onclick="testGetCommandes()">Tester</button>
        <div id="commandes-response" class="response"></div>
    </div>

    <script>
        const API_TOKEN = 'SNCF_API_TOKEN_2024';
        const BASE_URL = '/api';

        async function fetchAPI(endpoint, options = {}) {
            const defaultOptions = {
                headers: {
                    'Authorization': `Bearer ${API_TOKEN}`,
                    'Content-Type': 'application/json'
                }
            };

            try {
                const response = await fetch(BASE_URL + endpoint, { ...defaultOptions, ...options });
                const data = await response.json();
                return { success: response.ok, data };
            } catch (error) {
                return { success: false, error: error.message };
            }
        }

        async function testGetProduits() {
            const responseDiv = document.getElementById('produits-response');
            const result = await fetchAPI('/produits');
            responseDiv.innerHTML = JSON.stringify(result, null, 2);
            responseDiv.className = 'response ' + (result.success ? 'success' : 'error');
        }

        async function testGetStock() {
            const responseDiv = document.getElementById('stock-response');
            const result = await fetchAPI('/stock');
            responseDiv.innerHTML = JSON.stringify(result, null, 2);
            responseDiv.className = 'response ' + (result.success ? 'success' : 'error');
        }

        async function testGetCommandes() {
            const userId = document.getElementById('user-id').value;
            const responseDiv = document.getElementById('commandes-response');
            const result = await fetchAPI(`/commandes?utilisateur_id=${userId}`);
            responseDiv.innerHTML = JSON.stringify(result, null, 2);
            responseDiv.className = 'response ' + (result.success ? 'success' : 'error');
        }
    </script>
</body>
</html>