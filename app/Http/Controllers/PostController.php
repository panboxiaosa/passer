<?php
/**
 * Created by PhpStorm.
 * User: Think
 * Date: 2015/4/5
 * Time: 14:27
 */

namespace App\Http\Controllers;



use App\Http\Middleware\MateMiddleware;
use App\Legacy;
use App\Mate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;

class PostController extends Controller{

    private static $PEERS = 'peers';
	private static $EACHPAGE = 16;
    public function recommend(){
        if(Session::get(MateMiddleware::$VERIFY) == null){
            $result = DB::table('legacy')->orderBy('publish', 'desc')->paginate(PostController::$EACHPAGE);
            return $this->buildAbsResult($result);
        }
        if(Session::get(PostController::$PEERS) != null)
            $possible = Session::get(PostController::$PEERS);
        else
            $possible = $this->getPeer(Session::get(MateMiddleware::$VERIFY));
        $resultPre = Legacy::whereIn('seller', $possible)->orderBy('publish', 'asc');
        if($resultPre->count() == 0)
            $result = DB::table('legacy')->orderBy('publish', 'desc')->paginate(PostController::$EACHPAGE);
        else
            $result = $resultPre->paginate(PostController::$EACHPAGE);
        return $this->buildAbsResult($result);
    }

    private function getPeer($id){
        $mate = Mate::find($id);
        $schooler = Mate::where('schoolid', '=', $mate->schoolid)->orderBy('register', 'desc')->get(['id'])->toArray();
        $regional = Mate::where('region', '=', $mate->region)->whereNotIn('id', $schooler)->orderBy('register', 'desc')->get(['id'])->toArray();
        $possible = array_merge($schooler, $regional);
        Session::put(PostController::$PEERS, $possible);
        return$possible;
    }

    private function buildAbsResult($legacies){
        $result = [];
        foreach($legacies as $legacy)
            $result[] = ['id'=>$legacy->id, 'seller'=>$legacy->seller, 'abs'=>$legacy->abs, 'img'=>$legacy->img,'publish'=>strtotime($legacy->publish)*1000, 'price'=>$legacy->price, 'type'=>$legacy->type];
        return json_encode($result);
    }

    public function search(){
        return DB::select('select id,abs,img,publish,price,seller from legacy where des like  ? ;',['%'.Input::get('keyword').'%']);

    }

    public function personal(){
        $mate = Mate::find(Input::get('id'));
        $legacies = $mate->legacies()->paginate(10);
        return $this->buildAbsResult($legacies);
    }

    public function detail(){
        $legacy = Legacy::find(Input::get('id'));
        return $legacy->des;
    }

    public function publish(){
        $imgs = Input::file();
        if($imgs != [null] && $imgs != null){
            foreach($imgs as $img) {
                $oneName = $img->getClientOriginalName();
                $img->move(base_path() . '/../../nginx/passerImg', $oneName);
            }
        }
        $content = Input::get('content');
        $postArray = json_decode($content, TRUE);
        $result = "";
        foreach($postArray as $item){
            $legacy = Legacy::create(['des'=>$item["des"],
                'seller'=>Session::get(MateMiddleware::$VERIFY),
                'img'=>$item['img'],
                'abs'=>$item['abs'],
                'price'=>$item['price'],
                'type'=>$item['type']]);
            $result = $result . $legacy->id . ";";
        }
        return substr($result, 0, -1);
    }

} 
