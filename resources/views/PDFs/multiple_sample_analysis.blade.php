<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chemical Analysis Worksheet</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h1 {
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #f4f4f4;
        }
        input[type="text"] {
            width: 80%;
            padding: 5px;
            text-align: center;
        }
        .parameter-section {
            margin-top: 20px;
        }
        .parameter-title {
            font-weight: bold;
            text-align: left;
            margin-bottom: 5px;
        }
        .dilution-container {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .dilution-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .dilution-row input {
            width: 40%;
        }
    </style>
</head>
<body>

<table>
    <thead>
        <tr>
            <th>Código de Laboratório</th>
            <th>Área Responsável</th>
            <th>Parâmetro</th>
            <th>Diluição</th>
            <th>Resultado</th>
            <th>Resultado Final</th> <!-- New Column for Final Result -->
        </tr>
    </thead>
    <tbody>
        @foreach($models as $model)
            @php
                $rowCount = 0;
                foreach ($model['parameters'] as $parameter) {
                    if (isset($parameter->extra_data['dilutions'])) {
                        $rowCount += count($parameter->extra_data['dilutions']);
                    }
                }
            @endphp

            @foreach($model['parameters'] as $parameterIndex => $parameter)
                @if(isset($parameter->extra_data['dilutions']) && count($parameter->extra_data['dilutions']) > 0)
                    @foreach($parameter->extra_data['dilutions'] as $dilutionIndex => $dilution)
                        <tr>
                            @if($dilutionIndex === 0) <!-- Only show Model ID and Name on the first dilution -->
                                <td rowspan="{{ count($parameter->extra_data['dilutions']) }}">{{ $model['code'] }}</td>
                                <td rowspan="{{ count($parameter->extra_data['dilutions']) }}">{{ $model['department'] }}</td>
                            @endif
                            <td>{{ $dilutionIndex === 0 ? $parameter->name : '-' }}</td>
                            <td>{{ $dilution['ratio'] }}</td> <!-- Dilution Ratio -->
                            <td>
                                <input type="text" name="result_{{ $model['code'] }}_{{ $parameter->name }}_{{ $dilutionIndex }}" value="{{ $dilution['result'] ?? '' }}">
                            </td>
                            <td>
                                <input type="text" name="final_result_{{ $model['code'] }}_{{ $parameter->name }}_{{ $dilutionIndex }}" value="{{ $dilution['result'] ?? '' }}" value="{{ $dilution['final_result'] ?? '' }}"> <!-- Final Result -->
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td>{{ $model['code'] }}</td>
                        <td>{{ $model['department'] }}</td>
                        <td>{{ $parameter->name }}</td> <!-- Parameter Name -->
                        <td colspan="3">Sem Diluição</td>
                    </tr>
                @endif
            @endforeach
        @endforeach
    </tbody>
</table>

</body>
</html>
