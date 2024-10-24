<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audits</title>
    <style>
        * {
            margin: 0;
            padding: 0;
        }
        body {
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 1rem;
            margin: 3rem;
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
        }
        td p {
            margin-bottom: .5rem;
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
        .ta-left {
            text-align: left;
        }
    </style>
</head>
<body>
    <table>
        <thead>
            <tr>
                <th class="w-10">Timestamp</th>
                <th class="w-10">User ID</th>
                <th class="w-10">Role</th>
                <th class="w-15">Description</th>
                <th class="w-10">Entity</th>
                <th class="w-15">Old Values</th>
                <th class="w-15">New Values</th>
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
                    <td class="w-15 ta-left">
                        @foreach ($audit['old_values'] as $value)
                            <p>{{$value}}</p>
                        @endforeach
                    </td>
                    <td class="w-15 ta-left">
                        @foreach ($audit['new_values'] as $value)
                            <p>{{$value}}</p>
                        @endforeach
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>