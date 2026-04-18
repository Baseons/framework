<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
</head>

<body>
    <style>
        body {
            background-color: #141414;
            padding: 10px
        }

        ::-webkit-scrollbar {
            width: 4px;
            height: 8px;
            background: 0 0
        }

        ::-webkit-scrollbar-thumb {
            background: #646464;
            border-radius: 0
        }

        .dd-code,
        .dd-title {
            max-width: 1400px
        }
    </style>

    @include(path()->framework('Exceptions/views/dump.bns.php'))

    @section('title')
    <a class="dd-title" href="vscode://file{{ $file }}:{{ $line }}">{{ $file }} - {{ $line }}</a>
    @endsection
</body>

</html>