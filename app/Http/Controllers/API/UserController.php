<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Log;
use App\Models\User;
use App\Models\WalletLog;
use App\Models\Bet;
use App\Http\Requests\DepositRequest;
use Hash;
use DB;
use Response;
use Auth;
use Image;

class UserController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth');
    // }
    
    public function index(Request $request)
    {
        Log::info($request);
        Log::info($request->user()->id);
        return User::all();
    }
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required',
            'password' => 'required'
        ]);
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'user' => $user
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        log::info($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $std = User::findOrFail($id);
        $std->update($request->all());

        return $std;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $std = User::findOrFail($id);
        $std->delete();

        return 204;
    }
    public function getUser($id)
    {
        return User::where('id',$id)->first();
    }

    public function transactions(Request $request)
    {     
        $user = User::find($request->user()->id);
        $logs = WalletLog::where('user_id','=',$user->id)->get();
        return Response::json(['success'=>'true','logs'=>$logs],200, array(),JSON_PRETTY_PRINT);
    }

    public function accountDetails(Request $request)
    {
        return User::find(auth()->user()->id);
    }
    public function accountDetailsPost(Request $request)
    {     
        // log::info(request()->getHttpHost());
        $this->validate($request, [
 
            'email' => 'required',
        ]);

	
        DB::beginTransaction();
        try
        { 
            $user =Auth::user();
            $user->first_name = $request->input('first_name') ?? null;
            $user->middle_name = $request->input('middle_name') ?? null;
            $user->last_name = $request->input('last_name') ?? null;
            $user->address = $request->input('address') ?? null;
            $user->gender = $request->input('gender') ?? null;
            $user->municipality_city = $request->input('municipality_city') ?? null;
            $user->province_region = $request->input('province_region') ?? null;
            if(Auth::user()->image != $request->image){
                if(!empty($request->image) && strlen($request->image)>50)
                {
                    $photo = time().'.' . explode('/', explode(':', substr($request->image, 0, strpos($request->image, ';')))[1])[1];
                    Image::make($request->image)->save(public_path('img/').$photo);
                    $request->merge(['photo' => 'http://'.request()->getHttpHost().'/img/'.$photo]);
                    $user->image = $request->input('photo');
                }else{
                    $photo= '';
                    $request->merge(['photo' => $photo]);
                    $user->image = "test";
                }
            }
            $user->save();
            
            DB::table('activity_logs')->insert([
                'username'  =>  Auth::user()->username . '@' . \Request::ip(),
                'entry'  =>  'added User :' . $request->input('name'),
                'comment'  =>  '',
                'family'  =>  'insert',
                'created_at' => \Carbon\Carbon::now()
            ]);

            DB::commit();
            
        }
        catch(\Exception $e)
        {
            // DB::rollback();
            Log::alert($e);

            return Response::json(['success'=>'false','msg' =>'Server Error 500'],200, array(),JSON_PRETTY_PRINT);
        }
        catch(\Throwable $e)
        {
            DB::rollback();
            Log::alert($e);
            Response::json(['success'=>'false','msg' =>'Server Error 500'],200, array(),JSON_PRETTY_PRINT);
        } 
        return Response::json(['success'=>'true','msg' =>'User successfully added','user'=>$user],200, array(),JSON_PRETTY_PRINT);

 
    }


    public function deposit(DepositRequest $request)
    {
        $validated = $request->validated();
        $user = User::find($request->user()->id);
        DB::beginTransaction();
        try
        { 
            $wallet = WalletLog::create([
                'user_id'  =>  $request->user()->id,
                'amount'  =>  $request->input('amount'),
                'balance_before'=>$user->points,
                'balance_after'=>$user->points + $request->input('amount'),
                'details'=>$request->input('details') ?? null,
                'type'=>'deposit',
            ]);

            $user->points = $user->getCurrentPoints();
            $user->save();
            DB::table('activity_logs')->insert([
                'username'  =>  Auth::user()->username . '@' . \Request::ip(),
                'entry'  =>  'Deposit Load :' . $request->input('amount') . " to " . $request->user()->id,
                'comment'  =>  '',
                'family'  =>  'insert',
                'created_at' => \Carbon\Carbon::now()
            ]);

            DB::commit();
            
        }
        catch(\Exception $e)
        {
            DB::rollback();
            Log::alert($e);

            return Response::json(['success'=>'false','msg' =>'Server Error 500'],200, array(),JSON_PRETTY_PRINT);
        }
        catch(\Throwable $e)
        {
            DB::rollback();
            Log::alert($e);
            return Response::json(['success'=>'false','msg' =>'Server Error 500'],200, array(),JSON_PRETTY_PRINT);
        } 
        return Response::json(['success'=>'true','msg' =>'Load successfully added',],200, array(),JSON_PRETTY_PRINT);


       
    }
    public function withdraw(DepositRequest $request)
    {
        $validated = $request->validated();
        $user = User::find($request->user()->id);
        $amt = $user->getCurrentPoints();
        if($request->input('amount')>$amt)
        {
            return Response::json(['success'=>'false','msg' =>'Amount Greater than Load'],200, array(),JSON_PRETTY_PRINT);
        }
        DB::beginTransaction();
        try
        { 
            $wallet = WalletLog::create([
                'user_id'  =>  $request->user()->id,
                'amount'  =>  $request->input('amount'),
                'balance_before'=>$user->points,
                'balance_after'=>$user->points - $request->input('amount'),
                'details'=>$request->input('details') ?? null,
                'type'=>'withdraw',
            ]);

            $user->points = $user->getCurrentPoints();
            $user->save();
            DB::table('activity_logs')->insert([
                'username'  =>  Auth::user()->username . '@' . \Request::ip(),
                'entry'  =>  'Withdraw Load :' . $request->input('amount') . " to " . $request->user()->id,
                'comment'  =>  '',
                'family'  =>  'insert',
                'created_at' => \Carbon\Carbon::now()
            ]);

            DB::commit();
            
        }
        catch(\Exception $e)
        {
            DB::rollback();
            Log::alert($e);

            return Response::json(['success'=>'false','msg' =>'Server Error 500'],200, array(),JSON_PRETTY_PRINT);
        }
        catch(\Throwable $e)
        {
            DB::rollback();
            Log::alert($e);
            return Response::json(['success'=>'false','msg' =>'Server Error 500'],200, array(),JSON_PRETTY_PRINT);
        } 
        return Response::json(['success'=>'true','msg' =>'Load successfully Withdrawn',],200, array(),JSON_PRETTY_PRINT);
    }
    public function changeMobile (Request $request)
    {
        return User::find($request->user()->id);
    }
    public function changeMobilePost (Request $request)
    {
        $this->validate($request, [
            // 'new_phone' => 'required|min:10|regex: /^(9|\+9)\d{9}$/',
            'new_phone' => 'required|min:10',
            'password' => 'required|min:6',
        ]);
        $user = Auth::user();
        
        DB::beginTransaction();
        
        try { 
            if (Hash::check($request->password, $user->password)) {
                if (User::where('phone', '=', $request->new_phone)->exists()) {
                    return Response::json(['success'=>'false','msg' =>'Phone Number Already Exist'],200, array(),JSON_PRETTY_PRINT); 
                 }else{
                     $user->phone = $request->new_phone;
                     $user->save();
                 }
                
                DB::table('activity_logs')->insert([
                    'username'  =>  Auth::user()->username . '@' . \Request::ip(),
                    'entry'  =>  'Mobile Changed',
                    'comment'  =>  '',
                    'family'  =>  'insert',
                    'created_at' => \Carbon\Carbon::now()
                ]);
        
                DB::commit();
                return Response::json(['success'=>'true','msg' =>'Phone Number updated successfully'],200, array(),JSON_PRETTY_PRINT);
            } else {
                DB::rollback();
                return Response::json(['success'=>'false','msg' =>'Incorrect password'],200, array(),JSON_PRETTY_PRINT);
            }
        } catch(\Exception $e) {
            DB::rollback();
            Log::alert($e);
            return Response::json(['success'=>'false','msg' =>$e],200, array(),JSON_PRETTY_PRINT);

        } catch(\Throwable $e) {
            DB::rollback();
            Log::alert($e);
            return Response::json(['success'=>'false','msg' =>'Server Error 500'],200, array(),JSON_PRETTY_PRINT);
        }
    }
    public function changePassword(Request $request)
    {
        $this->validate($request, [
            'current_password' => 'required|min:6',
            'password' => 'required|min:6|confirmed',
            'password_confirmation' => 'required|min:6'
        ]);
        
        $user = Auth::user();
        
        DB::beginTransaction();
        
        try { 
            if (Hash::check($request->current_password, $user->password)) {
                $user->password = Hash::make($request->password);
                $user->save();
                
                DB::table('activity_logs')->insert([
                    'username'  =>  Auth::user()->username . '@' . \Request::ip(),
                    'entry'  =>  'Password Changed',
                    'comment'  =>  '',
                    'family'  =>  'insert',
                    'created_at' => \Carbon\Carbon::now()
                ]);
        
                DB::commit();
                return Response::json(['success'=>'true','msg' =>'Password updated successfully'],200, array(),JSON_PRETTY_PRINT);
            } else {
                DB::rollback();
                return Response::json(['success'=>'false','msg' =>'Incorrect password'],200, array(),JSON_PRETTY_PRINT);
            }
        } catch(\Exception $e) {
            DB::rollback();
            Log::alert($e);
            return Response::json(['success'=>'false','msg' =>'Server Error 500'],200, array(),JSON_PRETTY_PRINT);

        } catch(\Throwable $e) {
            DB::rollback();
            Log::alert($e);
            return Response::json(['success'=>'false','msg' =>'Server Error 500'],200, array(),JSON_PRETTY_PRINT);
        }
    }
    public function userBet(Request $request)
    {
        log::info($request);
        $user = User::find(Auth::user()->id);
        $amt = $user->points;
        DB::beginTransaction();
        
        try { 
            $totalPrice = 0;
            if($user->points < $request->matchwinner[0]['stakeBet'] || $user->points < $request->handicap[0]['stakeBet'])
            {
                return Response::json(['success'=>'false','msg' =>'Invalid amount'],402, array(),JSON_PRETTY_PRINT);
            }
            $bets = $request->all();
            foreach ($bets as $type => $data) {
                foreach ($data as $item) {
                  // create a new bet object
                  $bet = new Bet;
                  $bet->user_id = Auth::user()->id;
                  $bet->game_id = $item['sport_id'];
                  $bet->sport_key = $item['sport_key'];
                  $bet->sport_title = $item['sport_title'];
                  $bet->home_team = $item['home_team'];
                  $bet->away_team = $item['away_team'];
                  $bet->bet_amount = $item['stakeBet'];
                  $bet->commence_time = \Carbon\Carbon::parse($item['commence_time']);
                  $bet->type = $item['type'];
                  $bet->outcomes = 'waiting';
                  // set type-specific properties
                  if ($type == 'matchwinner') {
                    $bet->bet_data = json_encode($item['MatchWinner']);
                    foreach ($item['MatchWinner'] as $x) {
                        $totalPrice += $x["price"];
                    }
                  } else if ($type == 'handicap') {
                    $bet->bet_data = json_encode($item['PointHandicap']);
                    foreach ($item['PointHandicap'] as $x) {
                        $totalPrice += $x["price"];
                    }
                  }
                  $bet->save();
                }
            }
              
              //decrease user points
              $user = User::findOrFail(Auth::user()->id);
              $user->points -= $bet->bet_amount;
              $user->update();        
              
            DB::table('activity_logs')->insert([
                'username'  =>  Auth::user()->username . '@' . \Request::ip(),
                'entry'  =>  'Bet successful',
                'comment'  =>  '',
                'family'  =>  'insert',
                'created_at' => \Carbon\Carbon::now()
            ]);
        
            DB::commit();
            return Response::json(['success'=>'true','msg' =>'Bet Successfully Created', 'current_points'=>$user->points, 'stake'=>$bet->bet_amount, 'odds'=>$totalPrice],200, array(),JSON_PRETTY_PRINT);

        } catch(\Exception $e) {
            DB::rollback();
            Log::alert($e);
            return Response::json(['success'=>'false','msg' =>'Server Error 500', 'error_massage', $e],200, array(),JSON_PRETTY_PRINT);

        } catch(\Throwable $e) {
            DB::rollback();
            Log::alert($e);
            return Response::json(['success'=>'false','msg' =>'Server Error 500', 'error_massage', $e],200, array(),JSON_PRETTY_PRINT);
        }
    }
}
