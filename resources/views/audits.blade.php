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
            box-sizing: border-box;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 1rem;
        }
        main {
            position: relative;
        }
        h1 {
            text-decoration: underline;
            font: bold;
            text-align: center;
            padding: 0;
            margin: 0;
        }
        .date-text {
            font-size: 20px;
            text-align: center;
            margin-top: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 16px;
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
    <main>
        <h1>AUDITS</h1>
        @if ($date_from == $date_until)
            <p class="date-text">{{$date_from}}</p>
        @else
            <p class="date-text">{{$date_from}} to {{$date_until}}</p>
        @endif
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
                @if ($audits_length > 0)
                    @foreach ($audits as $audit)
                        <tr>
                            <td class="w-10">{{"{$audit['date']} {$audit['time']}"}}</td>
                            <td class="w-10">{{$audit['user_personnel_number']}}</td>
                            <td class="w-10">{{$audit['user_role']}}</td>
                            <td class="w-15">{{$audit['message']}}</td>
                            <td class="w-10">{{$audit['resource_entity']}}</td>
                            <td class="w-15 ta-left">
                                @if ($audit['old_values'])
                                    @foreach ($audit['old_values'] as $value)
                                        <p>{{$value}}</p>
                                    @endforeach
                                @endif
                            </td>
                            <td class="w-15 ta-left">
                                @if ($audit['new_values'])
                                    @foreach ($audit['new_values'] as $value)
                                        <p>{{$value}}</p>
                                    @endforeach
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="7">No audits</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </main>
</body>
</html>