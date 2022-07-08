<?php

namespace App\Http\Controllers;

use App\Models\Instrument, App\Models\ProductSeries;
use App\Models\User, Illuminate\Http\Request;
use App\Models\UserProductLessionPurchase, App\Models\Wishlist;
use App\Models\Offer, App\Models\ProductSeriesLession;
use App\Models\Notification;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function transactionLog(Request $req)
    {
        $userPurchase = DB::table('user_product_lession_purchases')
            ->join('transactions as t', 't.id', '=', 'user_product_lession_purchases.transactionId')
            ->join('users as u1', 'u1.id', '=', 'user_product_lession_purchases.userId')
            ->join('users as u2', 'u2.id', '=', 'user_product_lession_purchases.authorId')
            ->select(
                't.order_id',
                't.transactionId',
                't.amount',
                't.currency',
                't.created_at',
                't.id',
                'user_product_lession_purchases.transactionId AS tid',
                'user_product_lession_purchases.type_of_product',
                'u1.name AS customer_name',
                'u1.email AS customer_email',
                'u2.name AS author_name',
            )
            ->groupBy('tid');

        $authors = [];
        foreach ($userPurchase->get() as $value) {
            array_push($authors, $value->author_name);
        }

        // print_r($authors);
        // die;

        if (!empty($req->get('seriesId'))) {
            $userPurchase = $userPurchase->where('productSeriesId', $req->seriesId);
        }
        if (!empty($req->get('lessionId'))) {
            $userPurchase = $userPurchase->where('productSeriesLessionId', $req->lessionId);
        }
        if (!empty($req->get('tutor'))) {
            // dd($req->get('tutor'));
            $userPurchase = $userPurchase->where('u2.name', 'like', '%' . $req->get('tutor') . '%');
            $old_search = $req->get('tutor');
            // dd($userPurchase);
        }
        // dd($userPurchase);

        if (!empty($req->get('purchase_from')) && !empty($req->get('purchase_to'))) {
            $userPurchase = $userPurchase->where('u2.name', 'like', '%' . $req->get('tutor') . '%');
            $from = date('Y-m-d H:i:s', strtotime($req->get('purchase_from')));
            $to = date('Y-m-d H:i:s', strtotime($req->get('purchase_to')));
            $userPurchase = $userPurchase->where('t.created_at', '>=', $from)->where('t.created_at', '<=', $to);
        }

        if (!empty($req->get('price'))) {
            if ($req->get('price') == 1)
                $userPurchase = $userPurchase->orderBy('t.amount', 'ASC');
            else
                $userPurchase = $userPurchase->orderBy('t.amount', 'DESC');
        }

        $userPurchase = $userPurchase->paginate(5);

        // }
        // echo "<pre>";
        // print_r((array)$userPurchase);
        // die;

        // foreach ($userPurchase as $key => $purchase) {
        //     $offer = (object)[];
        //     $series = (object)[];
        //     $lession = (object)[];
        //     if ($purchase->type_of_product == 'offer') {
        //         $offer = Offer::where('id', $purchase->offerId)->withTrashed()->first();
        // $offer->series = UserProductLessionPurchase::where('transactionId', $purchase->transactionId)->where('type_of_product', $purchase->type_of_product)->where('offerId', $purchase->offerId)->groupBy('productSeriesId')->get();
        //         foreach ($offer->series as $index => $productSeries) {
        //             $productSeries->lession = UserProductLessionPurchase::where('transactionId', $purchase->transactionId)->where('type_of_product', $purchase->type_of_product)->where('offerId', $purchase->offerId)->where('productSeriesId', $productSeries->productSeriesId)->get();
        //         }
        //     } elseif ($purchase->type_of_product == 'series') {
        //         $series = ProductSeries::where('id', $purchase->productSeriesId)->withTrashed()->first();
        //         $series->lession = UserProductLessionPurchase::where('transactionId', $purchase->transactionId)->where('type_of_product', $purchase->type_of_product)->where('productSeriesId', $purchase->productSeriesId)->get();
        //     } elseif ($purchase->type_of_product == 'lession') {
        //         $lession = ProductSeriesLession::where('id', $purchase->productSeriesLessionId)->withTrashed()->first();
        //     }
        //     // putting all the data in to the same Loop
        //     $purchase->offer_data = $offer;
        //     $purchase->series_data = $series;
        //     $purchase->lession_data = $lession;
        //     $purchase->transaction;
        //     $purchase->users_details_all;
        // }
        $available_series = UserProductLessionPurchase::select('product_series.id', 'product_series.title', 'product_series.createdBy')->join('product_series', 'product_series.id', '=', 'user_product_lession_purchases.productSeriesId')->groupBy('product_series.title')->get();
        $available_lessons = UserProductLessionPurchase::select('product_series_lessions.id', 'product_series_lessions.title', 'product_series_lessions.createdBy', 'product_series_lessions.productSeriesId')->join('product_series_lessions', 'product_series_lessions.id', '=', 'user_product_lession_purchases.productSeriesLessionId')->groupBy('product_series_lessions.title')->get();

        return view('reports.transactionLog', compact('userPurchase', 'req', 'available_series', 'available_lessons', 'authors'));
    }

    public function transactionLogDet($tid)
    {
        $data = DB::table('user_product_lession_purchases')->where('transactionId', $tid)->join('product_series_lessions', 'product_series_lessions.id', '=', 'user_product_lession_purchases.productSeriesLessionId')->get();

        $transaction_data = Transaction::FindOrFail($tid);

        $user_id = $data[0]->userId;
        $user_data = User::FindOrFail($user_id);

        $productSeriesId = $data[0]->productSeriesId;
        $productSeries_data = ProductSeries::FindOrFail($productSeriesId);

        return view('reports.transaction_details', compact('data', 'user_data', 'productSeries_data', 'transaction_data'));
    }

    public function transactionLogOld(Request $req)
    {
        $transaction = UserProductLessionPurchase::select('*')->join('transactions', 'transactions.id', 'user_product_lession_purchases.transactionId');

        if (!empty($req->seriesId)) {
            $transaction = $transaction->where('user_product_lession_purchases.productSeriesId', $req->seriesId);
        }
        if (!empty($req->lessionId)) {
            $transaction = $transaction->where('user_product_lession_purchases.productSeriesLessionId', $req->lessionId);
        }
        if ($req->teacherId) {
            $transaction = $transaction->leftjoin('product_series_lessions', 'user_product_lession_purchases.productSeriesLessionId', '=', 'product_series_lessions.id')->where('product_series_lessions.createdBy', $req->teacherId);
        }
        if ($req->keyword) {
            $transaction = $transaction
                ->join('users', 'users.id', 'user_product_lession_purchases.userId')
                ->where('users.name', 'like', '%' . $req->keyword . '%')
                ->orWhere('users.email', 'like', '%' . $req->keyword . '%')
                ->orWhere('transactions.transactionId', 'like', '%' . $req->keyword . '%');
        }
        $transaction = $transaction->latest('user_product_lession_purchases.created_at')->paginate(20);

        $teachers = User::select('id', 'name')->where('user_type', 2)->get();

        $available_series = UserProductLessionPurchase::select('product_series.id', 'product_series.title', 'product_series.createdBy')->join('product_series', 'product_series.id', '=', 'user_product_lession_purchases.productSeriesId')->groupBy('product_series.title')->get();

        $available_lessons = UserProductLessionPurchase::select('product_series_lessions.id', 'product_series_lessions.title', 'product_series_lessions.createdBy', 'product_series_lessions.productSeriesId')->join('product_series_lessions', 'product_series_lessions.id', '=', 'user_product_lession_purchases.productSeriesLessionId')->groupBy('product_series_lessions.title')->get();

        return view('reports.transactionLog', compact('transaction', 'req', 'teachers', 'available_series', 'available_lessons'));
    }

    public function bestSeller(Request $req)
    {
        $purchase_list = UserProductLessionPurchase::select('user_product_lession_purchases.productSeriesId');
        $series = $purchase_list->groupBy('user_product_lession_purchases.productSeriesId')->get();
        if (!empty($req->seriesId)) {
            $purchase_list = $purchase_list->where('user_product_lession_purchases.productSeriesId', $req->seriesId);
        }
        if (!empty($req->instrumentId)) {
            $purchase_list = $purchase_list->join('product_series_lessions', 'product_series_lessions.id', '=', 'user_product_lession_purchases.productSeriesLessionId')
                ->join('instruments', 'instruments.id', '=', 'product_series_lessions.instrumentId')
                ->where('instruments.id', $req->instrumentId);
        }
        if (!empty($req->dateFrom)) {
            $purchase_list = $purchase_list->where('user_product_lession_purchases.created_at', '>=', $req->dateFrom);
        }
        if (!empty($req->dateTo)) {
            $purchase_list = $purchase_list->where('user_product_lession_purchases.created_at', '<=', date('Y-m-d', strtotime($req->dateTo . '+ 1 day')));
        }
        $purchase_list = $purchase_list->groupBy('user_product_lession_purchases.productSeriesId')->pluck('user_product_lession_purchases.productSeriesId')->toArray();
        $data = [];
        foreach ($purchase_list as $key => $value) {
            $list = UserProductLessionPurchase::where('user_product_lession_purchases.productSeriesId', $value);
            $data[] = [
                'from' => date('Y-m-d', strtotime($list->orderBy('user_product_lession_purchases.id', 'DESC')->first()->created_at)),
                'to' => date('Y-m-d', strtotime($list->latest()->first()->created_at)),
                'seriesId' => $list->first()->productSeriesId,
                'seriesName' => $list->first()->product_series_all->title,
                'count' => $list->count(),
            ];
        }
        $instruments = Instrument::all();
        return view('reports.bestSeller', compact('data', 'req', 'series', 'instruments'));
    }

    public function mostViewed(Request $req)
    {
        $series = ProductSeries::where('view_count', '>', 0);
        if (!empty($req->instrumentId)) {
            $series = $series->where('instrumentId', $req->instrumentId);
        }
        if (!empty($req->dateFrom)) {
            $series = $series->where('created_at', '>=', $req->dateFrom);
        }
        if (!empty($req->dateTo)) {
            $series = $series->where('last_count_increased_at', '<=', date('Y-m-d', strtotime($req->dateTo . '+ 1 day')));
        }
        $series = $series->orderBy('view_count', 'desc')->get();
        $instruments = Instrument::all();
        return view('reports.mostViewed', compact('req', 'series', 'instruments'));
    }

    public function productsOrdered(Request $req)
    {
        $data = [];
        $purchaseList = UserProductLessionPurchase::select('productSeriesLessionId');
        // $series = $purchaseList->groupBy('productSeriesLessionId')->get();

        if ($req->instrumentId) {
            $purchaseList = $purchaseList->join('product_series_lessions', 'product_series_lessions.id', '=', 'user_product_lession_purchases.productSeriesLessionId')
                ->join('instruments', 'instruments.id', '=', 'product_series_lessions.instrumentId')
                ->where('instruments.id', $req->instrumentId);
        }
        if (!empty($req->dateFrom)) {
            $purchaseList = $purchaseList->where('user_product_lession_purchases.created_at', '>=', $req->dateFrom);
        }
        if (!empty($req->dateTo)) {
            $purchaseList = $purchaseList->where('user_product_lession_purchases.created_at', '<=', date('Y-m-d', strtotime($req->dateTo . '+ 1 day')));
        }

        $purchaseList = $purchaseList->groupBy('productSeriesLessionId')->pluck('productSeriesLessionId')->toArray();
        foreach ($purchaseList as $key => $value) {
            $list = UserProductLessionPurchase::where('productSeriesLessionId', $value);
            $data[] = [
                'from' => date('Y-m-d', strtotime($list->orderBy('id', 'DESC')->first()->created_at)),
                'to' => date('Y-m-d', strtotime($list->latest()->first()->created_at)),
                'seriesId' => $list->first()->productSeriesLessionId,
                'lessonName' => $list->first()->product_series_lession->title,
                'count' => $list->count(),
            ];
        }

        $instruments = Instrument::all();
        return view('reports.productsOrdered', compact('data', 'req', 'instruments'));
    }

    public function wishlistCount(Request $req)
    {
        $data = [];
        $wishlistData = Wishlist::select('product_id');
        if ($req->instrumentId) {
            $wishlistData = $wishlistData->join('product_series', 'product_series.id', '=', 'wishlists.product_id')
                ->join('instruments', 'instruments.id', '=', 'product_series.instrumentId')
                ->where('instruments.id', $req->instrumentId);
        }
        if (!empty($req->dateFrom)) {
            $wishlistData = $wishlistData->where('wishlists.created_at', '>=', $req->dateFrom);
        }
        if (!empty($req->dateTo)) {
            $wishlistData = $wishlistData->where('wishlists.created_at', '<=', date('Y-m-d', strtotime($req->dateTo . '+ 1 day')));
        }
        $wishlistData = $wishlistData->groupBy('wishlists.product_id')->pluck('wishlists.product_id')->toArray();

        foreach ($wishlistData as $key => $value) {
            $list = Wishlist::where('product_id', $value);
            $data[] = [
                'from' => date('Y-m-d', strtotime($list->orderBy('id', 'DESC')->first()->created_at)),
                'to' => date('Y-m-d', strtotime($list->latest()->first()->created_at)),
                'series_id' => $list->first()->product_id,
                'series_title' => $list->first()->wishlist_series->title,
                'count' => $list->count(),
            ];
        }

        $instruments = Instrument::all();
        return view('reports.wishlists', compact('data', 'req', 'instruments'));
    }

    public function userNotification(Request $req)
    {
        $notification = Notification::with('user_details')->latest();
        if (!empty($req->dateFrom)) {
            $notification = $notification->where('created_at', '>=', $req->dateFrom);
        }
        if (!empty($req->dateTo)) {
            $notification = $notification->where('created_at', '<=', date('Y-m-d', strtotime($req->dateTo . '+ 1 day')));
        }
        if (!empty($req->search)) {
            $notification = $notification->where('message', 'like', '%' . $req->search . '%');
        }
        $notification = $notification->paginate(30);
        return view('admin.reports.userNotification', compact('notification', 'req'));
    }
}
