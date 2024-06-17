<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Correo electrónico informativo</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
            margin: 0;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        h3 {
            color: #fff;
            background-color: #069169;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        p {
            line-height: 1.6;
        }
        footer {
            text-align: center;
            margin-top: 30px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <h3>{{$datos['numero_informe']}}</h3>
        <p>{{$datos['mensaje']}}</p>
    </div>
    <footer>
        <p>Este correo electrónico es de naturaleza administrativa y no requiere respuesta.</p>
        <p>Por favor, no responda a este mensaje.</p>
    </footer>
</body>
</html>