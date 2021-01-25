<?php

use App\Models\News;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    $news_list = News::query()
        ->select([
            'id',
            'title',
            'partners_news',
            DB::raw('SUBSTR(`text`, 1, 200) as text'),
            'news_time',
            'original_link'
        ])
        ->orderBy('news_time', 'desc')
        ->limit(15)
        ->get();

    return view('index', compact('news_list'));
});
Route::get('/news/{id}', function ($id) {
    $news = News::query()
        ->select([
            'id',
            'title',
            'text',
            'news_time',
            'original_link',
            'img'
        ])
        ->where('id', $id)
        ->first();

    if(empty($news)){
        return abort(404);
    }

    return view('newsSite', compact('news'));
})->where('id', '[0-9]+');
