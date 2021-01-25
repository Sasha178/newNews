@extends('layouts.app')

@section('content')
    <div class="container-body-news">
        <ol class="breadcrumb">
            <li><a href="/">Главная</a></li>
            <li class="active">{{$news->title}}</li>
        </ol>
        <h1>{{$news->title}}</h1>
        <div class="panel panel-default">
            <div class="panel-body">
                <div class="pull-left">
                    {{$news->news_time}}
                </div>
                <div class="pull-right">
                    <a href="{{$news->original_link}}" target="_blank">
                        Ссылка на оригинал
                    </a>
                </div>
            </div>
        </div>
        @if($news->title)
            <div class="text-center">
                <img src="{{$news->img}}" alt="" width="80%">
            </div>
        @endif
        <br>
        <p class="text-justify">
            {{$news->text}}
        </p>
    </div>
@endsection
