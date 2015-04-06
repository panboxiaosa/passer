<?php
/**
 * Created by PhpStorm.
 * User: Think
 * Date: 2015/4/5
 * Time: 13:52
 */

namespace App\Http\Controllers;


use App\Http\Middleware\MateMiddleware;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Mate;

class MateController extends Controller {
    public function register(){
        Mate::create(['email'=>Input::get('email'),
            'password'=>Input::get('password'),
            'nickname'=>Input::get('nickname'),
            'school'=>Input::get('school'),
            'major'=>Input::get('major'),
            'inform'=>false]);
        return 'success';
    }

    public function login(){
        $email = Input::get('email');
        $password = Input::get('password');
        try{
            $mate = Mate::where('email', '=', $email)->firstOrFail();
        }catch (ModelNotFoundException $e){
            return 'email_fail';
        }
        if($mate->password == $password) {
            Session::put(MateMiddleware::$VERIFY, $mate->id);
            return 'success';
        }else
            return 'password_fail';
    }

}