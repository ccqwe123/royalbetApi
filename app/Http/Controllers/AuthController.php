<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Auth;
use App\Models\User;
use Log;
use Mail;
use DB;
use Illuminate\Support\Facades\Response;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        log::info($request);
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required',
        ]);
        if(Auth::attempt(['email' => request('email'), 'password' => request('password')])){
           $user = Auth::user();
           $token =  $user->createToken('authToken')->accessToken;
           return response()->json(['success' =>true,'user'=>$user, 'token'=>$token], 200);
       }
       else{
           return response()->json(['error'=>'Unauthorised'], 401);
       }
    }

    public function register(Request $request)
    {
        $characters = '0123456789';
        $verify_code = '';
        $this->validate($request, [ 
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
            'password_confirmation' => 'required|min:6'
        ]);
        $checker = User::where('email', $request->email)->count();
        if($checker > 0)
        {
            return response()->json([
                    'message' => 'Email already exist',
                ]);
        }
     
        for ($i = 0; $i < 6; $i++) {
                $index = rand(0, strlen($characters) - 1);
                $verify_code .= $characters[$index];
            }
        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'verify_code' => $verify_code,
            'reset_timer' => 130,
            'account_is_verified' => 0,
            'details_is_provided' => 0,
            'contact_is_provided' => 0,
        ]);

        $token =$user->createToken('authToken')->accessToken;
        
        $mail_data = [
                'verify_code' => $user->verify_code,
                'email' => $user->email
            ];

        Mail::send(['html' => 'mails.verification_code'], $mail_data, function($message) use ($mail_data) {
            $message->subject('RoyaleBet Account Verification Code');
            $message->to($mail_data['email'], $mail_data['email']);
            $message->to('alasco.paolo@gmail.com', 'RoyaleBet');
        });
        // $user->getAccessToken()['access_token'];
        // $token = $user->createToken('user token')->plainTextToken;
        return response()->json([
                    'user' => $user,
                    'token' => $token
                ]);

    }

    public function verifyUser($id)
    {
        $user = User::where('id', $id)->whereNotNull('verify_code')->first();
            if ($user) {
                return response()->json([
                    'success'=>true,
                    'msg'=>'User not verified',
                    'user'=>$user->email
                ], 200);
            }else{
                return response()->json([
                    'success'=>false,
                    'msg'=>'User verified or invalid',
                ], 422);
            }
            return response()->json([
                'success'=>false,
                'msg'=>'Something went wrong',
            ], 500);
    }
    
    public function verify(Request $request)
    {
        $this->validate($request, [ 
            'pin' => 'required|alpha_num|size:6'
        ]);

        DB::beginTransaction();

        try {
            $user = User::find($request->id);

            if (empty($user)) {
                return Response::json(['success'=>'false','msg' =>'Verification failed!'],422, array(),JSON_PRETTY_PRINT);
            }

            if ($user->verify_code != $request->pin) {
                return Response::json(['success'=>'false','msg' =>'Verification failed!'],422, array(),JSON_PRETTY_PRINT);
            }

            $user->verify_code = null;
            $user->reset_timer = 0;
            $user->account_is_verified = 1;
            $user->save();

            DB::commit();
            return Response::json(['success'=>'true','msg' =>'Verified successfully!'],200, array(),JSON_PRETTY_PRINT);
        } catch (Exception $e) {
            DB::rollback();
            return Response::json(['success'=>'false','msg' =>'Verification failed!'],422, array(),JSON_PRETTY_PRINT);
        }
    }

    public function logoutuser()
    {
        $user = Auth::user();
        if($user)
        {
            $data = Auth::user()->token();
            $data->revoke();
            return response()->json([
                'success'=>true,
                'message'=>'logout successfully'
            ]);
        }else{
            return response()->json([
                'success'=>false,
                'message'=>'unable to logout'
            ]);
        }
    }
    public function forgotPassword(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = User::where('email', $request->email)->first();
            
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $reset_token = '';
            
            for ($i = 0; $i < 64; $i++) {
                $index = rand(0, strlen($characters) - 1);
                $reset_token .= $characters[$index];
            }
            
            $user->reset_token = $reset_token;
            $user->save();
            
            DB::commit();
            
            $mail_data = [
                'link' => $request->baseurl . "/new-password/{$user->id}/{$user->reset_token}",
                'email' => $user->email
            ];
            Mail::send(['html' => 'mails.reset_link'], $mail_data, function($message) use ($mail_data) {
                $message->subject('RoyaleBet Account Forgot Password');
                $message->to($mail_data['email'], $mail_data['email']);
                $message->to('alasco.paolo@gmail.com', 'RoyaleBet');
            });
            log::info($mail_data);
            
            return Response::json(['success'=>'true','msg' =>'success'],200, array(),JSON_PRETTY_PRINT);
        } catch (Exception $e) {
            DB::rollback();
            return Response::json(['success'=>'false','msg' =>'Transaction Failed!'],200, array(),JSON_PRETTY_PRINT);
        }
    }
    public function newPassword(Request $request, $user_id, $reset_token)
    {   
        log::info($request);
        $this->validate($request, [ 
            'password' => 'required|min:6|confirmed',
            'password_confirmation' => 'required|min:6'
        ]);
        
        DB::beginTransaction();
        try {
            $user = User::where('id', $user_id)->where('reset_token', $reset_token)->first();
            if ($user) {
                $user->reset_token = '';
                $user->password = Hash::make($request->password);
                $user->save();
            } else {
                return Response::json(['success'=>'false','msg' =>'User not found'],422, array(),JSON_PRETTY_PRINT);
            }
            $user->reset_token = '';
            $user->password = Hash::make($request->password);
            
            $user->save();
            
            DB::commit();
            
            
            return Response::json(['success'=>'true','msg' =>'success'],200, array(),JSON_PRETTY_PRINT);
        } catch (Exception $e) {
            DB::rollback();
            return Response::json(['success'=>'false','msg' =>'Transaction Failed!'],200, array(),JSON_PRETTY_PRINT);
        }
        
    }    
    public function checkUserforgotPassword(Request $request, $user_id, $reset_token)
    {
        $user = User::where('id', $user_id)->where('reset_token', $reset_token)->first();
        if ($user) {
            return Response::json(['success'=>'true','msg' =>'User'],200, array(),JSON_PRETTY_PRINT);
        } else {
            return Response::json(['success'=>'false','msg' =>'User not found'],422, array(),JSON_PRETTY_PRINT);
        }
    }
}
