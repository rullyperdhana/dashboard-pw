<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Missing Payrolls Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }

        h1 {
            text-align: center;
            color: #c62828;
            margin-bottom: 5px;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #c62828;
            color: white;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #999;
        }

        .summary {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #fce4ec;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <h1>Missing Payrolls Report</h1>
    <p class="subtitle">Period: {{ $monthName }} {{ $year }}</p>

    <div class="summary">
        <strong>View By:</strong> {{ strtoupper($viewBy) }} |
        <strong>Total Missing:</strong> {{ $totalCount }}
    </div>

    @if($viewBy === 'skpd')
        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="70%">SKPD Name</th>
                    <th width="25%">SKPD Code</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item['nama_skpd'] ?? '-' }}</td>
                        <td>{{ $item['kode_skpd'] ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @elseif($viewBy === 'upt')
        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="45%">UPT Name</th>
                    <th width="50%">SKPD Name</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item['upt'] ?? '-' }}</td>
                        <td>{{ $item['nama_skpd'] ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @elseif($viewBy === 'employees')
        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="15%">NIP</th>
                    <th width="25%">Name</th>
                    <th width="20%">Position</th>
                    <th width="15%">UPT</th>
                    <th width="20%">SKPD</th>
                </tr>
            </thead>
            <tbody>
                @php $no = 1; @endphp
                @foreach($data as $skpdGroup)
                    @foreach($skpdGroup['employees'] as $emp)
                        <tr>
                            <td>{{ $no++ }}</td>
                            <td>{{ $emp['nip'] ?? '-' }}</td>
                            <td>{{ $emp['nama'] ?? '-' }}</td>
                            <td>{{ $emp['jabatan'] ?? '-' }}</td>
                            <td>{{ $emp['upt'] ?? '-' }}</td>
                            <td>{{ $skpdGroup['skpd_name'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        Generated on {{ now()->format('d M Y H:i') }}
    </div>
</body>

</html>