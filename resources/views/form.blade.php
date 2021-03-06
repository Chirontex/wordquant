<!DOCTYPE html>
<html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{env('APP_NAME')}}</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-eOJMYsd53ii+scO/bJGFsiCZc+5NDVN2yr8+0RDqr0Ql0h+rP48ckxlpbzKgwra6" crossorigin="anonymous">
        <link rel="stylesheet" href="css/form.css?v=0.0.2">
    </head>
    <body class="text-center">
        <main class="form-container">
            @if (isset($notice))
            <div class="alert alert-{{$notice['type']}}">
                {{$notice['text']}}
            </div>
            @endif
            <form action="" method="post">
                <h1 class="h3 mb-3">Введите строку:</h1>
                @csrf
                <textarea name="text" cols="30" rows="10" class="form-control form-control-sm" required="true">@if(isset($prev_text)){{$prev_text}}@endif</textarea>
                <div class="my-3 text-center">
                    <button class="btn btn-sm btn-primary">Сохранить</button>
                </div>
            </form>
        </main>
    </body>
</html>