<?php

namespace App\Http\Controllers\Api;

use App\Models\Menu;
use App\Models\MenuCategory;
use App\Models\Shop;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class ShopController extends BaseController
{
    public function __construct()
    {
        header('Access-Control-Allow-Origin:*');
    }

    //提供店铺列表
    public function list(Request $request)
    {

        //接收参数
        $keyword = $request->input('keyword');




        if ($keyword===null){
            $shops = Shop::where('status',1)->get();

        }else{
            $shops = Shop::search($keyword)->where('status',1)->get();
        }






        foreach ($shops as $shop) {
            $shop->distance = rand(1000, 3000);
            $shop->estimate_time = $shop->distance / 100;
        }
        // var_dump($shops);exit;
        return $shops;
    }

    public function index(Request $request)
    {
        //店铺的ID
        $id = $request->input('id');


        //判断redis中有没有缓存
        $data=Redis::get("shop:".$id);

        if ($data){

            return $data;
        }

        //取出当前店铺信息
        $shop = Shop::findOrFail($id);
        //var_dump($shop);exit;
        //给店铺添加没用的东西
        $shop->distance = rand(1000, 3000);
        $shop->estimate_time = $shop->distance / 100;
        //添加评论
        $shop->evaluate = [
            [
                "user_id" => 12344,
                "username" => "w******k",
                "user_img" => "http://www.homework.com/images/slider-pic4.jpeg",
                "time" => "2017-2-22",
                "evaluate_code" => 1,
                "send_time" => 30,
                "evaluate_details" => "不怎么好吃"],
            [
                "user_id" => 12344,
                "username" => "w******k",
                "user_img" => "http://www.homework.com/images/slider-pic4.jpeg",
                "time" => "2017-2-22",
                "evaluate_code" => 4.5,
                "send_time" => 30,
                "evaluate_details" => "很好吃"]
        ];

        //先取出分类 预加载
        $cates = MenuCategory::where("shop_id", $id)->get();

        //


        //dd($cates->toArray());
        //循环分类 取出当前分类下的所有商品
        foreach ($cates as $cate) {
             // $cate->goods_list = Menu::where("cate_id", $cate->id)->get();
            //循环
        /*    foreach ($cate->goods_list as $k=>$v){
                $cate->goods_list[$k]->goods_id=$v->id;
            }*/
              //通过分类的ID找出属于这个分类的所有商品
            $goods=Menu::where("cate_id", $cate->id)->get();

            //循环Goods
          /*  foreach ($goods as $k=>$good){
                $goods[$k]["goods_id"]=$good->id;
            }*/
            $cate->goods_list=$goods;
            // $cate->goods_list=$cate->goodsList;
        }

        //再把分类数据追加到$shop
        $shop->commodity = $cates;
        // dump($cates->toArray());

        //存到redis中 必需要设置过期时间
        Redis::setex("shop:".$id,60*60*24*7,$shop);
        return $shop;
    }
}
