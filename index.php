<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brechó Kokero</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #000;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            text-align: center;
            flex-direction: column;
        }

        h1 {
            color: #32CD32;
            margin-bottom: 10px;
        }

        h2 {
            color: #fff; 
            margin-bottom: 20px;
        }

        p {
            color: #fff; 
            margin-bottom: 30px;
        }

        .loading {
            margin: 30px auto;
            width: 50px;
            height: 50px;
            border: 6px solid rgba(50, 205, 50, 0.2);
            border-top: 6px solid #32CD32;
            border-radius: 50%;
            animation: spin 1.5s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        .dev-message {
            font-size: 18px;
            color: #32CD32;
            margin-top: 20px;
        }

        .logo {
            margin-top: 30px;
            margin-bottom: 30px;
        }

    </style>
</head>
<body>

    <div class="logo">
        <img src="img/logo.png" alt="Logotipo Brechó Kokero">
    </div>

    <h1>Brechó Kokero</h1>
    <h2>Site em andamento</h2>
    <p>Volte mais tarde!</p>
    
    <div class="loading"></div>

    <div class="dev-message">Estamos trabalhando para você!</div>

</body>
</html>
