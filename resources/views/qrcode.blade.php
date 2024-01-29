<!DOCTYPE html>
<HTML lang="en">
<HEAD>
    <META charset="UTF-8">
    <META name="viewport" content="width=device-width, initial-scale=1.0">
    <LINK href="https://fonts.googleapis.com/css?family=Ubuntu:400,500&display=swap" rel="stylesheet">
    <TITLE>QrCode</TITLE>
    <STYLE type="text/css">
        img.qrcode{
            display: block;
            margin: 10px;
            max-width: 250px;
        }
    </STYLE>
</HEAD>
<BODY>

{{--{{dd($caminhoArquivo)}}--}}
<img class="qrcode" src="{{$caminhoArquivo}}">
</BODY>
</HTML>
