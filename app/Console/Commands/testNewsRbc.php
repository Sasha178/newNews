<?php

namespace App\Console\Commands;

use App\Models\News;
use Carbon\Carbon;
use Illuminate\Console\Command;
use PHPHtmlParser\Dom;

class testNewsRbc extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'listNewNews:rbc';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Парсинг новостного интернет ресурса с rbk.ru';

    private $parse_url  = 'https://www.rbc.ru/';

    private $limit_news = 14;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dom = new Dom;
        $main_host = parse_url($this->parse_url)['host'];
        $dom->loadStr($this->getContent($this->parse_url));
        $news_block = $dom->find('.js-news-feed-list > a');

        $news = [];

        foreach ($news_block as $item) {

            try{
                $link  = $this->getNewsLink($item);
                $title = $this->getNewsTitle($item);
                $time  = $this->getNewsTime($item);
                $id    = $this->getNewsId($item);
            }catch (\Exception $e){
                echo "Ошибка парсинга блока новостей " . $link ?? null;
                echo "\n".$e->getMessage();
                echo "\n".$e->getTraceAsString();
                continue;
            }


            $link_host = parse_url($link)['host'] ?? null;
            $news[] = [
                'title'         => trim($title),
                'link'          => $link,
                'id'            => $id,
                'partners_news' => $link_host != $main_host,
                'news_time'     => Carbon::createFromTimestamp($time),
            ];

        }

        //собираем id новостей
        $news_ids = array_column($news, 'id');


        //ищем в базе id уже записанных новостей, чтобы не записывать повторно
        $db_news = News::query()
            ->select(['external_id'])
            ->whereIn('external_id', $news_ids)
            ->get()
            ->toArray();

        $news_ids = array_column($db_news, 'external_id');

        $batch = [];

        $count_news = 0;

        foreach ($news as $i => $item){
            if(in_array($item['id'], $news_ids)){
                continue;
            }

            $count_news++;

            $batch[$i] = [
                'external_id'   => $item['id'],
                'title'         => $item['title'],
                'img'           => null,
                'text'          => null,
                'original_link' => $item['link'],
                'partners_news' => $item['partners_news'],
                'news_time'     => $item['news_time'],
            ];

            if($item['partners_news']){
                continue;
            }

            try {
                $dom->loadStr($this->getContent($item['link']));
                $news_body = $dom->find('[data-id="'.$item['id'].'"]') ?? null;

                if($news_body && count($image_box = $news_body->find('.article__main-image__image') ?? null)){
                    $batch[$i]['img'] = $image_box->getAttribute('src') ?? null;
                }
                $batch[$i]['text'] = $this->getNewsText($news_body);

            }catch (\Exception $e){
                echo "Ошибка парсинга полной новости: ".$item['link'];
                echo "\n".$e->getMessage();
                echo "\n".$e->getTraceAsString();
                continue;
            }
            if($count_news >= $this->limit_news){
                break;
            }

        }

        News::insert($batch);

        dd(count($batch).' новых новостей');
    }

    //получение текста новости
    private function getNewsText($news_body){
        $text = '';
        foreach ($news_body->find('p') as $p){
            $text.=$p->text;
        }

        return $text;
    }

    private function getNewsLink($news_body){
        return $news_body->getAttribute('href');
    }

    private function getNewsTitle($news_body){
        return $news_body->find('.news-feed__item__title')->text;
    }

    private function getNewsTime($news_body){
        return $news_body->getAttribute('data-modif');
    }

    private function getNewsId($news_body){
        return explode('id_newsfeed_', $news_body->getAttribute('id'))[1];
    }


    private function getContent($url){
        $curl = curl_init($url);
        $options = array(
            CURLOPT_HTTPHEADER => [
                'user-agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.122 Safari/537.36',
            ],
            CURLOPT_CONNECTTIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FRESH_CONNECT => 0 ,
            CURLOPT_RETURNTRANSFER => 1,

        );
        curl_setopt_array($curl, $options);
        $result = curl_exec($curl);
        $content = curl_getinfo($curl);
        $content['errno'] = curl_errno($curl);
        $content['error'] = curl_error($curl);
        $content['result'] = $result;
        curl_close($curl);

        if (!$content['errno']) {
            return $content['result'];
        }
        else {
            dd(["ERROR get data from url", $url, $content['error']]);
        }
    }
}
