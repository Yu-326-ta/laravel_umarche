<?php
 
namespace App\Http\Controllers\Owner;
 
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shop;//69
use Illuminate\Support\Facades\Auth;
 
class ShopController extends Controller
{
    //認証しているかのチェック
    public function __construct()
    {
        $this->middleware('auth:owners'); 


        //shopControllerにのみ働くmiddlewareを定義
        $this->middleware(function($request, $next) {
            $id = $request->route()->parameter('shop'); //shopのid取得 [Route::get('edit/{shop}・・・')]の {shop}に該当
            if(!is_null($id)) { 
                $shopsOwnerId = Shop::findOrFail($id)->owner->id;
                $shopId = (int)$shopsOwnerId; //キャスト 文字列→数値
                $ownerId = Auth::id();
                if ($shopId !== $ownerId) {
                    abort(404);
                }
            }
            return $next($request);
        });
    }
 
    public function index()
    {
        //$ownerId = Auth::id(); // 認証されているid
        $shops = Shop::where('owner_id', Auth::id())->get();// whereは検索条件
 
        return view('owner.shops.index',
        compact('shops'));
    }
 
    public function edit($id)
    {
        //
    }
 
    public function update(Request $request, $id)
    {
 
    }
}