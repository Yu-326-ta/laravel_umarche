<?php
 
namespace App\Http\Controllers\Owner;
 
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shop;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use InterventionImage;
use App\Http\Requests\UploadImageRequest;
 
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
        $shop = Shop::findOrFail($id);
        return view('owner.shops.edit',compact('shop'));
    }
 
    public function update(UploadImageRequest $request, $id)
    {
        $imageFile=$request->image;
        if(!is_null($imageFile) && $imageFile->isValid()){
            // Storage::putFile('public/shops', $imageFile); リサイズなし

            $fileName = uniqid(rand().'_');
            $extension = $imageFile->extension();
            $fileNameToStore = $fileName.'.'.$extension;
            $resizedImage = InterventionImage::make($imageFile)->resize(1920, 1080)->encode();

            Storage::put('public/shops/'.$fileNameToStore, $resizedImage);

        }

        return redirect()->route('owner.shops.index');
    }
}