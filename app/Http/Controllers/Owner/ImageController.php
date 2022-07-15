<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\UploadImageRequest;
use App\Services\ImageService;
use Illuminate\Support\Facades\Storage;

use function Ramsey\Uuid\v1;

class ImageController extends Controller
{
    //認証しているかのチェック
    public function __construct()
    {
        $this->middleware('auth:owners');

        //shopControllerにのみ働くmiddlewareを定義
        $this->middleware(function($request, $next) {
            $id = $request->route()->parameter('image'); 
            if(!is_null($id)) { 
                $imagesOwnerId = Image::findOrFail($id)->owner->id;
                $imageId = (int)$imagesOwnerId; //キャスト 文字列→数値
                if ($imageId !== Auth::id()) {
                    abort(404);
                }
            }
            return $next($request);
        
        });
    }

public function index()
{
    $images = Image::where('owner_id', Auth::id())->orderBy('updated_at', 'desc')->paginate(20);

    return view('owner.images.index', compact('images'));
}

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('owner.images.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UploadImageRequest $request)
    {
        $imageFiles = $request->file('files');
        if(!is_null($imageFiles)){
            foreach($imageFiles as $imageFile){
                $fileNameToStore = ImageService::upload($imageFile, 'products');
                Image::create([
                    'owner_id' => Auth::id(),
                    'filename' => $fileNameToStore
                ]);
            }
        }

        return redirect()->route('owner.images.index')->with(['message' => '画像登録を実施しました',
        'status' => 'info']);
    }

    
    public function edit($id)
    {
        $image = Image::findOrFail($id);
        return view('owner.images.edit', compact('image'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'string|max:50'
        ]);

        $image = Image::findOrFail($id);
        $image->title = $request->title;
        $image->save();

        return redirect()->route('owner.images.index')->with(['message' => '画像情報を更新しました',
        'status' => 'info']);

    }

    
    public function destroy($id)
    {
        $image = Image::findOrFail($id);

        $imageInProducts = Product::where('image1', $image->id)
        ->orWhere('image2', $image->id)
        ->orWhere('image3', $image->id)
        ->orWhere('image4', $image->id)
        ->get();

        if($imageInProducts){
            $imageInProducts->each(function($product) use($image){
                if($product->image1 === $image->id){
                    $product->image1 = null;
                    $product->save();
                }
                if($product->image2 === $image->id){
                    $product->image2 = null;
                    $product->save();
                }
                if($product->image3 === $image->id){
                    $product->image3 = null;
                    $product->save();
                }
                if($product->image4 === $image->id){
                    $product->image4 = null;
                    $product->save();
                }
            });
        }

        $filePath = 'public/products/'.$image->filename;

         //publicのstorageフォルダの画像を削除
         if(Storage::exists($filePath)) {
            Storage::delete($filePath);
        }

        Image::findOrFail($id)->delete();
        return redirect()->route('owner.images.index')
        ->with(['message'=>' 画像を削除しました', 'status' => 'alert']);
    }
}
