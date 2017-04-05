<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

//use Illuminate\Routing\Controller;
use App\Http\Controllers\Controller as Controller;

//use Illuminate\Routing\Controller;
use \Auth;
use App\Listing;
use DB;


class ListingController extends Controller
{
 //   public function index()
  //  {
   //     //checks if user is logged in, then checks if they're banned
    //    if(Auth::check()){
     //       if(Auth::user()->banned){
      //      return view('banned');
       //     }
        //}
        //$booklistings = DB::select('select * from listings');
        //return view('listing.index')->with('booklistings', $booklistings);
    //}


    //This function is for sorting the index page
    
    public function index(Request $request){
        $sortby = $request->get('sortby');
        $order = $request->get('order');

        if ($sortby && $order) {
            $booklistings = Listing::orderBy($sortby, $order)->get();
        } else {
            $booklistings = Listing::all();
        }

        return view('listing.index', compact('booklistings', 'sortby', 'order'));
    }

     //   $subjects = DB::table('Categorys')->pluck('subject');
    //    $booklistings = DB::select('select * from listings');
   //     return view('listing.index',[
  //          'booklistings' => $booklistings,
 //           'subjects' => $subjects]);
//
   // }


    public function showCategoryOnly()
    {
        $cat_id = 0;
        try {
            $cat_id = $_GET['categories'];
        } catch (\Exception $e) {

        }
        if ($cat_id == 0) {
            $booklistings = DB::select('select * from listings where del = 0');
        } else {
            $booklistings = DB::select('select * from listings where catId =? and del = 0', [$cat_id]);
        }
        return view('listing.index')->with('booklistings', $booklistings);
    }

    public function showlisting(Listing $listing)
    {

        return view('listing.listing')->with('listing', $listing);

    }
    public function store(Request $request)
    {
        $listing = new Listing;

        $this->validate($request, [
            'title' => 'required',
            'isbn' => 'required',
            'price' => 'required',
            'edition' => 'required',
            'condition' => 'required',
            'description' => 'required',
            'image' => 'required',
            'courseInfo' => 'required'
        ]);
        $listing->name = $request->title;
        $listing->userId = Auth::User()->id;
        $catId = 1;
        $listing->catId = $catId;
        $listing->isbn = $request->isbn;
        $listing->price = $request->price;
        $listing->edition = $request->edition;
        $listing->condition = $request->condition;
        $listing->description = $request->description;
        $listing->imageLink = $request->imageLink;
        $listing->courseInfo = $request->courseInfo;

        $volumeId = $request->volumeId;
        $book = json_decode(file_get_contents('https://www.googleapis.com/books/v1/volumes/'.$volumeId.'?key=AIzaSyA9d3aNH0Nmd7weoAQQ7hOBwNgoYvjh_qQ'), true);

        $priceInfo = $book['saleInfo']['saleability'];
        if($priceInfo == "FOR_SALE"){
            $priceInfo = $book['saleInfo']['listPrice']['amount'];
        } else {
            $priceInfo = -1;
        }
        $listing->retailPrice = $priceInfo;
        //$listing->pubDate = $request->pubDate;//needs to be added to db


        $listing->save();
        $imageName = $listing->id . '.' . $request->file('image')->getClientOriginalExtension();

        $listing->imagePath = $imageName;
        $request->file('image')->move(
            base_path() . '/storage/app/public/', $imageName
        );
        $listing->save();

        return redirect()->to('listing/' . $listing->id);

    }

    public function fileReport($id, Request $request)
    {
        $reason = $request->input('reason');
        DB::insert("INSERT INTO reportedListings(listingId, reason) VALUES (?, ?)", [$id, $reason]);
        DB::table('reportedListings')->increment('reported');

        return redirect()->to('/')->with('reported', 'reported');

    }

    public function myListing() {
        if(Auth::guest()) {
            return redirect()->to('/login');
        } else {
            $id = Auth::User()->id;
            $data = DB::select('select * from listings where userId = ? and del = 0', [$id]);
            
            return view('listing.mylisting')->with('listings', $data);
        }
    }

    public function deleteListing($id) {
        $del = Listing::find($id);
        if($del->userId == Auth::User()->id) {
            $del->del = 1;
            $del->save();

            return redirect()->to('/mylistings')->with('delete', 'delete');
        } else {
            return redirect()->to('/');
        }
    }

    public function editView($id) {
        $listing = Listing::find($id);

        return view('listing.editlisting')->with('listing', $listing);
    }

    public function edit($id, Request $request) {
        $listing = Listing::find($id);
        if($listing->userId == Auth::User()->id) {
            $input = $request->all();
            $listing->price = $request->input('price');
            $listing->condition = $request->input('condition');
            $listing->description = $request->input('description');
            $listing->name = $request->input('name');
            $listing->edition = $request->input('edition');
            $listing->save();

            return redirect()->to('/listing/'.$id);
        } else {
            return redirect()->to('/');
        }
    }

}
