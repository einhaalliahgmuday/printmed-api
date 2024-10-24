<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audits</title>
    <style>
        body {
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 1rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid black;
            padding: .5rem;
            text-align: center;
            word-wrap: break-word;
            /* overflow-wrap: break-word; */
        }
        th {
            background-color: #f2f2f2;
        }
        .w-5 {
            width: 5%;
            overflow: hidden;
        }
        .w-10 {
            width: 10%;
            overflow: hidden;
        }
        .w-15 {
            width: 15%;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <table>
        <thead>
            <tr>
                <th class="w-10">DATETIME</th>
                <th class="w-10">USER ID</th>
                <th class="w-10">ROLE</th>
                <th class="w-15">DESCRIPTION</th>
                <th class="w-10">ENTITY</th>
                <th class="w-15">OLD VALUES</th>
                <th class="w-15">NEW VALUES</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($audits as $audit)
                <tr>
                    <td class="w-10">{{"{$audit['date']} {$audit['time']}"}}</td>
                    <td class="w-10">{{$audit['user_personnel_number']}}</td>
                    <td class="w-10">{{$audit['user_role']}}</td>
                    <td class="w-15">{{$audit['message']}}</td>
                    <td class="w-10">{{$audit['resource_entity']}}</td>
                    <td class="w-15">{{$audit['old_values'] ? json_encode($audit['old_values']) : ""}}</td>
                    <td class="w-15">{{$audit['new_values'] ? json_encode($audit['new_values']) : ""}}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>