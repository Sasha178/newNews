@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Список новостей</h1>
        <ul class="media-list">
            @foreach($news_list as $news)
                <li class="media">
                    <div class="media-body">
                        <h3 class="media-heading">
                            {{$news->title}}
                            @if($news->partners_news) <span class="glyphicon glyphicon-share-alt"
                                                            aria-hidden="true"></span> @endif
                        </h3>
                        @if($news->text)
                           <p>{{$news->text}}...</p>
                        @endif

                        <p>
                            <a @if($news->partners_news) href="{{$news->original_link}}" target="_blank"
                               @else href="/news/{{$news->id}}" @endif>
                                <button> Подробне</button>
                            </a>
                            <small>
                                {{$news->news_time}}
                            </small>
                        </p>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
@endsection
